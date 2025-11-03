document.addEventListener("DOMContentLoaded", () => {
    const DROPDOWNS = {};

    // gets id entry for a select
    function getEntry(select) {
        return DROPDOWNS[select.id];
    }

    // rebuild menu for a given select
    function rebuildDropdownFromSelect(select) {
        const entry = getEntry(select);
        if (!entry) return;
        const { trigger, menu } = entry;

        // takes options from select and turns into dropdown items
        menu.innerHTML = "";
        Array.from(select.options).forEach((opt) => {
            const item = document.createElement("div");
            item.className = "dropdown-item";
            item.textContent = opt.textContent;
            item.dataset.value = opt.value;
            if (opt.selected) item.classList.add("is-selected");
            menu.appendChild(item);
        });

        const current = select.selectedOptions[0];
        trigger.textContent = current ? current.textContent : "Select";
    }

    // convert all selects with .opened-list-styling
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

            // hide original select and move inside wrapper
            select.classList.add("visually-hidden-select");
            const parent = select.parentNode;
            parent.insertBefore(wrapper, select);
            wrapper.appendChild(select);

            // ensure an id
            if (!select.id) {
                select.id = "sel-" + Math.random().toString(36).slice(2);
            }

            DROPDOWNS[select.id] = { select, wrapper, trigger, menu };
            rebuildDropdownFromSelect(select);

            // open/close
            trigger.addEventListener("click", (e) => {
                e.stopPropagation();
                wrapper.classList.toggle("open");
            });

            // choose item
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

    // dependent selects for quick buy form
    (function () {
        const movieSel = document.getElementById("movie");
        const locSel = document.getElementById("location");
        const showSel = document.getElementById("showtime");

        function filterOptions(selectEl, pred) {
            let any = false;
            [...selectEl.options].forEach((opt, i) => {
                if (i === 0) {
                    opt.hidden = false;
                    opt.disabled = false;
                    return;
                }
                const keep = pred(opt);
                opt.hidden = !keep;
                opt.disabled = !keep;
                if (keep) any = true;
            });
            selectEl.selectedIndex = 0;
            selectEl.disabled = !any;

            // keep custom UI in sync
            rebuildDropdownFromSelect(selectEl);
        }

        movieSel.addEventListener("change", () => {
            const mv = movieSel.value;
            filterOptions(locSel, (opt) => mv && opt.dataset.movieId === mv);
            filterOptions(showSel, () => false);
        });

        locSel.addEventListener("change", () => {
            const mv = movieSel.value;
            const loc = locSel.value;
            filterOptions(
                showSel,
                (opt) =>
                    mv &&
                    loc &&
                    opt.dataset.movieId === mv &&
                    opt.dataset.locationId === loc
            );
        });

        // disable until movie chosen
        locSel.disabled = true;
        showSel.disabled = true;
        rebuildDropdownFromSelect(locSel);
        rebuildDropdownFromSelect(showSel);
    })();

    // handle form submission
    const form = document.getElementById("quickBuyForm");
    if (form) {
        form.addEventListener("submit", (e) => {
            e.preventDefault();

            const movieSel = document.getElementById("movie");
            const locSel = document.getElementById("location");
            const showSel = document.getElementById("showtime");

            const movieId = movieSel.value;
            const locationId = locSel.value;
            const sessionId = showSel.value;

            if (!movieId || !locationId || !sessionId) {
                alert("Please select movie, location, and showtime.");
                return;
            }

            const url =
                "movieDetail.php?id=" +
                encodeURIComponent(movieId) +
                "&location_id=" +
                encodeURIComponent(locationId) +
                "&session_id=" +
                encodeURIComponent(sessionId);

            window.location.href = url;
        });
    }
});
