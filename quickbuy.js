document.addEventListener("DOMContentLoaded", () => {
    // registry: select.id -> { select, wrapper, trigger, menu }
    const DROPDOWNS = new Map();

    // ---- helper to (re)build menu for a given select ----
    function rebuildDropdownFromSelect(select) {
        const entry = DROPDOWNS.get(select.id);
        if (!entry) return;
        const { trigger, menu } = entry;

        // rebuild menu items from current <option>s
        menu.innerHTML = "";
        Array.from(select.options).forEach((opt) => {
            const item = document.createElement("div");
            item.className = "dropdown-item";
            item.textContent = opt.textContent;
            item.dataset.value = opt.value;
            if (opt.selected) item.classList.add("is-selected");
            menu.appendChild(item);
        });

        // update trigger text
        const current = select.selectedOptions[0];
        trigger.textContent = current ? current.textContent : "Select";
    }

    // =========================================================
    // 1) convert all selects with .opened-list-styling
    // =========================================================
    document
        .querySelectorAll("select.opened-list-styling")
        .forEach((select) => {
            const wrapper = document.createElement("div");
            wrapper.className = "dropdown";

            const trigger = document.createElement("button");
            trigger.type = "button";
            trigger.className = "dropdown-trigger";
            wrapper.appendChild(trigger);

            const menu = document.createElement("div");
            menu.className = "dropdown-menu";
            wrapper.appendChild(menu);

            // hide the original select and move it inside wrapper
            select.classList.add("visually-hidden-select");
            const parent = select.parentNode;
            parent.insertBefore(wrapper, select);
            wrapper.appendChild(select);

            // give id if missing
            if (!select.id) {
                select.id = "sel-" + Math.random().toString(36).slice(2);
            }
            DROPDOWNS.set(select.id, { select, wrapper, trigger, menu });

            rebuildDropdownFromSelect(select);

            // open/close
            trigger.addEventListener("click", (e) => {
                e.stopPropagation();
                wrapper.classList.toggle("open");
            });

            // select item (from custom menu)
            menu.addEventListener("click", (e) => {
                const item = e.target.closest(".dropdown-item");
                if (!item) return;

                menu.querySelectorAll(".dropdown-item").forEach((i) =>
                    i.classList.remove("is-selected")
                );
                item.classList.add("is-selected");
                trigger.textContent = item.textContent;

                select.value = item.dataset.value;
                select.dispatchEvent(new Event("change", { bubbles: true }));

                wrapper.classList.remove("open");
            });

            // close on outside click
            document.addEventListener("click", () => {
                wrapper.classList.remove("open");
            });
        });

    // =========================================================
    // 2) dependent selects (movie -> location -> showtime)
    // =========================================================

    const movieSel = document.getElementById("movie");
    const locSel = document.getElementById("location");
    const showSel = document.getElementById("showtime");

    if (typeof SESSIONS === "undefined") {
        console.error("SESSIONS is not defined from PHP.");
        return;
    }

    // helper: reset a select AND its fake dropdown
    function resetSelect(sel, placeholder) {
        sel.innerHTML = "";
        const opt = document.createElement("option");
        opt.value = "";
        opt.textContent = placeholder;
        sel.appendChild(opt);
        rebuildDropdownFromSelect(sel);
    }

    // ---------- when MOVIE changes ----------
    movieSel.addEventListener("change", () => {
        const movieId = movieSel.value;

        resetSelect(locSel, "Select a cinema");
        resetSelect(showSel, "Select a showtime");
        locSel.disabled = true;
        showSel.disabled = true;

        if (!movieId) return;

        const seenLocs = new Set();

        SESSIONS.forEach((s) => {
            if (String(s.movie_id) === String(movieId)) {
                if (!seenLocs.has(s.location_id)) {
                    const o = document.createElement("option");
                    o.value = s.location_id;
                    o.textContent = s.location_name;
                    locSel.appendChild(o);
                    seenLocs.add(s.location_id);
                }
            }
        });

        if (seenLocs.size > 0) locSel.disabled = false;

        rebuildDropdownFromSelect(locSel);
        rebuildDropdownFromSelect(showSel);
    });

    // ---------- when LOCATION changes ----------
    locSel.addEventListener("change", () => {
        const movieId = movieSel.value;
        const locationId = locSel.value;

        resetSelect(showSel, "Select a showtime");
        showSel.disabled = true;

        if (!movieId || !locationId) {
            rebuildDropdownFromSelect(showSel);
            return;
        }

        SESSIONS.forEach((s) => {
            if (
                String(s.movie_id) === String(movieId) &&
                String(s.location_id) === String(locationId)
            ) {
                const o = document.createElement("option");
                const niceTime = s.session_time
                    ? s.session_time.slice(0, 5)
                    : "";
                o.textContent = `${s.session_date} ${niceTime}`;
                o.value = s.session_id;
                showSel.appendChild(o);
            }
        });

        if (showSel.options.length > 1) showSel.disabled = false;
        rebuildDropdownFromSelect(showSel);
    });

    // =========================================================
    // 3) handle form submission → go to movieDetail.php
    // =========================================================

    const form = document.getElementById("quickBuyForm");
    if (form) {
        form.addEventListener("submit", (e) => {
            e.preventDefault();

            const movieId = movieSel.value;
            const locationId = locSel.value;
            const sessionId = showSel.value;

            if (!movieId || !locationId || !sessionId) {
                alert("Please select movie, location, and showtime.");
                return;
            }

            // try to find full session info in the JS array
            const sess = (window.SESSIONS || []).find(
                (s) => String(s.session_id) === String(sessionId)
            );

            // ✅ CASE A — we found the session, send full info
            if (sess) {
                const date = sess.session_date;
                const time = (sess.session_time || "").slice(0, 5);

                const url =
                    `movieDetail.php?id=${encodeURIComponent(movieId)}` +
                    `&location_id=${encodeURIComponent(locationId)}` +
                    `&date=${encodeURIComponent(date)}` +
                    `&time=${encodeURIComponent(time)}` +
                    `&session_id=${encodeURIComponent(sessionId)}`;

                window.location.href = url;
                return;
            }

            // ❗ CASE B — we DIDN'T find it in JS (your current situation)
            // still pass the session_id so PHP can look it up
            const fallbackUrl =
                `movieDetail.php?id=${encodeURIComponent(movieId)}` +
                `&location_id=${encodeURIComponent(locationId)}` +
                `&session_id=${encodeURIComponent(sessionId)}`;
            window.location.href = fallbackUrl;
        });
    }
});
