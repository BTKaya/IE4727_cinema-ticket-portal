console.log("✅ app.js loaded");

document.addEventListener("DOMContentLoaded", () => {
    const layoutType = parseInt(document.body.dataset.layout || "1", 10);
    const SEAT_PRICE = layoutType === 1 ? 15 : 10; // 8x8 -> $15, 8x10 -> $10

    const seats = document.querySelectorAll(
        ".seat.available, .seat.selected, .seat.booked"
    );
    const confirmBtn = document.getElementById("confirmBooking");
    const selectedSeatsText = document.getElementById("selectedSeatsText");
    const totalPriceText = document.getElementById("totalPrice");

    function updateSelectionSummary() {
        const selectedSeats = [
            ...document.querySelectorAll(".seat.selected:not(.static)"),
        ].map((s) => s.dataset.seat);
        const count = selectedSeats.length;
        const total = count > 0 ? count * SEAT_PRICE : 0;

        if (selectedSeatsText) {
            selectedSeatsText.textContent = count
                ? selectedSeats.join(", ")
                : "None";
        }
        if (totalPriceText) {
            totalPriceText.textContent = total; // shows 0 when none
        }
    }

    // Popup once seats are selected
    function showPopup(title, message, showCartButton) {
        const overlay = document.getElementById("popup-overlay");
        const titleEl = document.getElementById("popup-title");
        const messageEl = document.getElementById("popup-message");
        const closeBtn = document.getElementById("popup-close");
        const cartBtn = document.getElementById("popup-cart");

        titleEl.textContent = title;
        messageEl.textContent = message;
        cartBtn.style.display = showCartButton ? "inline-block" : "none";

        overlay.classList.add("show");

        closeBtn.onclick = () => {
            overlay.classList.remove("show");
            if (showCartButton) location.reload();
        };
    }

    // Toggle seat selection consistently
    document.querySelectorAll(".seat").forEach((seat) => {
        seat.addEventListener("click", () => {
            if (seat.classList.contains("booked")) return;

            if (seat.classList.contains("selected")) {
                seat.classList.remove("selected");
                seat.classList.add("available");
            } else {
                seat.classList.remove("available");
                seat.classList.add("selected");
            }

            updateSelectionSummary();
        });
    });

    updateSelectionSummary();

    // Single seat gap validation helper functions
    function clearSeatErrors() {
        document
            .querySelectorAll(".seat.error")
            .forEach((s) => s.classList.remove("error"));
    }

    function groupSeatsByRow() {
        const rows = {};
        document.querySelectorAll(".seat").forEach((el) => {
            const id = el.dataset.seat;
            if (!id) return;
            const row = id[0];
            const num = parseInt(id.slice(1), 10);
            (rows[row] ||= []).push({ el, num });
        });
        Object.values(rows).forEach((arr) => arr.sort((a, b) => a.num - b.num));
        return rows;
    }

    function findSingleSeatGapOffenders() {
        const offenders = new Set();
        const rows = groupSeatsByRow();

        const layoutType = parseInt(document.body.dataset.layout || "1");
        const blocks =
            layoutType === 1
                ? [
                      [1, 2],
                      [3, 4],
                      [5, 6],
                      [7, 8],
                  ] // 8x8 → 2|2|2|2
                : [
                      [1, 2],
                      [3, 8],
                      [9, 10],
                  ]; // 8x10 → 2|6|2

        for (const row in rows) {
            const seats = rows[row];

            for (const [start, end] of blocks) {
                const blockSeats = seats.filter(
                    (s) => s.num >= start && s.num <= end
                );

                const occ = blockSeats.map((s) =>
                    s.el.classList.contains("booked")
                        ? 2
                        : s.el.classList.contains("selected")
                        ? 1
                        : 0
                );

                // If block is exactly 2 seats → prevent 1 isolated seat
                if (occ.length === 2) {
                    if (
                        (occ[0] === 1 && occ[1] === 0) ||
                        (occ[0] === 0 && occ[1] === 1)
                    ) {
                        blockSeats.forEach((s, i) => {
                            if (occ[i] === 1) offenders.add(s.el);
                        });
                    }
                    continue;
                }

                // Otherwise check classic single-gap rule
                for (let i = 0; i < occ.length; i++) {
                    if (occ[i] !== 0) continue;

                    const left = i > 0 ? occ[i - 1] > 0 : false;
                    const right = i < occ.length - 1 ? occ[i + 1] > 0 : false;

                    const isolated =
                        (i > 0 && i < occ.length - 1 && left && right) ||
                        (i === 0 && right) ||
                        (i === occ.length - 1 && left);

                    if (isolated) {
                        if (i > 0 && occ[i - 1] === 1)
                            offenders.add(blockSeats[i - 1].el);
                        if (i < occ.length - 1 && occ[i + 1] === 1)
                            offenders.add(blockSeats[i + 1].el);
                    }
                }
            }
        }
        return [...offenders];
    }

    // Confirm Booking
    if (confirmBtn) {
        confirmBtn.addEventListener("click", async () => {
            clearSeatErrors();

            const offenders = findSingleSeatGapOffenders();
            if (offenders.length > 0) {
                offenders.forEach((el) => el.classList.add("error"));
                alert(
                    "⚠ You cannot leave a single-seat gap. Please adjust highlighted seats."
                );
                return;
            }

            const selectedSeats = [
                ...document.querySelectorAll(".seat.selected:not(.static)"),
            ].map((s) => s.dataset.seat);

            if (selectedSeats.length === 0) {
                alert("Please select at least one seat.");
                return;
            }

            const date = document.getElementById("screening_date").value;
            const time = document.getElementById("screening_time").value;
            const movieId = new URLSearchParams(window.location.search).get(
                "id"
            );
            const location = document.getElementById("location").value;

            console.log("📤 Sending booking request:", {
                movieId,
                selectedSeats,
                date,
                time,
                location,
            });

            const res = await fetch("save_booking.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    movie_id: movieId,
                    seats: selectedSeats,
                    date,
                    time,
                    location,
                }),
            });
            console.log("📥 Raw response:", await res.clone().text());

            const data = await res.json();
            if (data.success) {
                showPopup(
                    "✅ Seats Saved!",
                    "Your seats have been added to your cart.",
                    true
                );
            } else {
                showPopup("❌ Error", data.message, false);
            }
        });
    }

    // Searchbar Search
    const searchOverlay = document.getElementById("searchOverlay");
    const searchBar = document.getElementById("searchBar");
    const searchInput = document.getElementById("searchInput");
    const searchClose = document.getElementById("searchClose");
    const searchTrigger = document.getElementById("openSearch"); // optional search icon

    function openSearch() {
        searchBar.classList.add("show");
        searchOverlay.classList.add("show");
        searchInput.focus();
    }

    function closeSearch() {
        searchBar.classList.remove("show");
        searchOverlay.classList.remove("show");
        searchInput.value = "";
    }

    if (searchTrigger) searchTrigger.addEventListener("click", openSearch);
    searchClose.addEventListener("click", closeSearch);
    searchOverlay.addEventListener("click", closeSearch);

    // Handle "Enter" key press
    searchInput.addEventListener("keydown", (e) => {
        if (e.key === "Enter") {
            e.preventDefault();
            const query = searchInput.value.trim();
            if (query.length > 0) {
                window.location.href =
                    "searchresult.php?q=" + encodeURIComponent(query);
            }
        }
    });

    document.querySelectorAll("select").forEach((sel) => {
        sel.addEventListener("focus", () => sel.classList.add("open"));
        sel.addEventListener("blur", () => sel.classList.remove("open"));
    });
});
