// For debugging
console.log("app.js loaded");

document.addEventListener("DOMContentLoaded", () => {
    const layoutType = parseInt(document.body.dataset.layout || "1", 10);
    const SEAT_PRICE = layoutType === 1 ? 15 : 10;

    const confirmBtn = document.getElementById("confirmBooking");
    const selectedSeatsText = document.getElementById("selectedSeatsText");
    const totalPriceText = document.getElementById("totalPrice");

    // ---------- Selection summary ----------
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
            totalPriceText.textContent = total;
        }
    }

    // ---------- Popup ----------
    function showPopup(title, message, showCartButton) {
        const overlay = document.getElementById("popup-overlay");
        const titleEl = document.getElementById("popup-title");
        const messageEl = document.getElementById("popup-message");
        const closeBtn = document.getElementById("popup-close");
        const cartBtn = document.getElementById("popup-cart");

        if (!overlay || !titleEl || !messageEl || !closeBtn || !cartBtn) return;

        titleEl.textContent = title;
        messageEl.textContent = message;
        cartBtn.style.display = showCartButton ? "inline-block" : "none";

        overlay.classList.add("show");
        closeBtn.onclick = () => {
            overlay.classList.remove("show");
            if (showCartButton) location.reload();
        };
    }

    // ---------- Seat toggling ----------
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

    // ---------- Single-seat-gap validation ----------
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
        const byRow = groupSeatsByRow();

        const lt = parseInt(document.body.dataset.layout || "1", 10);
        const blocks =
            lt === 1
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

        for (const row in byRow) {
            const seats = byRow[row];

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

                // If block has exactly 2 seats → prevent 1 isolated seat
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

                // classic single-gap rule
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

    if (confirmBtn) {
        confirmBtn.addEventListener("click", (e) => {
            e.preventDefault();
            e.stopImmediatePropagation(); // prevent any legacy handlers

            clearSeatErrors();

            const offenders = findSingleSeatGapOffenders();
            if (offenders.length > 0) {
                offenders.forEach((el) => el.classList.add("error"));
                alert(
                    "You cannot leave a single-seat gap. Please adjust your seats."
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

            const dateEl = document.getElementById("screening_date");
            const timeEl = document.getElementById("screening_time");
            const locEl = document.getElementById("location");

            const date = dateEl ? dateEl.value : "";
            const time = timeEl ? timeEl.value : "";
            const locationId = locEl ? locEl.value : ""; // value should be location_id

            if (!date || !time || !locationId) {
                alert("Please choose date, time, and location.");
                return;
            }

            const movieId =
                document.getElementById("movieId")?.value ||
                new URLSearchParams(window.location.search).get("id") ||
                "";

            if (!movieId) {
                alert("Missing movie ID.");
                return;
            }

            // Build a normal POST form so PHP resolves the session server-side
            const form = document.createElement("form");
            form.method = "POST";
            form.action = "save_booking.php";

            const addField = (name, value) => {
                const input = document.createElement("input");
                input.type = "hidden";
                input.name = name;
                input.value = value;
                form.appendChild(input);
            };

            addField("movie_id", movieId);
            addField("date", date);
            addField("time", time);
            addField("location_id", locationId);

            const sessionHidden = document.getElementById("sessionId");
            const sessionId = sessionHidden ? sessionHidden.value : "";
            if (sessionId) addField("session_id", sessionId);

            // seats as repeated fields seats[]
            selectedSeats.forEach((seatName) => {
                const input = document.createElement("input");
                input.type = "hidden";
                input.name = "seats[]";
                input.value = seatName;
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
        });
    }

    // ---------- Searchbar ----------
    const searchOverlay = document.getElementById("searchOverlay");
    const searchBar = document.getElementById("searchBar");
    const searchInput = document.getElementById("searchInput");
    const searchClose = document.getElementById("searchClose");
    const searchTrigger = document.getElementById("openSearch");

    function openSearch() {
        if (!searchBar || !searchOverlay) return;
        searchBar.classList.add("show");
        searchOverlay.classList.add("show");
        searchInput && searchInput.focus();
    }
    function closeSearch() {
        if (!searchBar || !searchOverlay) return;
        searchBar.classList.remove("show");
        searchOverlay.classList.remove("show");
        if (searchInput) searchInput.value = "";
    }

    if (searchTrigger) searchTrigger.addEventListener("click", openSearch);
    if (searchClose) searchClose.addEventListener("click", closeSearch);
    if (searchOverlay) searchOverlay.addEventListener("click", closeSearch);

    if (searchInput) {
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
    }

    // ---------- Select focus styling ----------
    document.querySelectorAll("select").forEach((sel) => {
        sel.addEventListener("focus", () => sel.classList.add("open"));
        sel.addEventListener("blur", () => sel.classList.remove("open"));
    });
});
