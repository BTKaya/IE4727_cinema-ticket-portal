document.addEventListener("DOMContentLoaded", () => {
    const DROPDOWNS = {};

    // gets id entry for a select
    function getEntry(select) {
        return DROPDOWNS[select.id];
    }

    // rebuild menu for a given select (styling only, doesn't touch <option> set)
    function rebuildDropdownFromSelect(select) {
        const entry = getEntry(select);
        if (!entry) return;
        const { trigger, menu } = entry;

        // Clear menu fully
        while (menu.firstChild) {
            menu.removeChild(menu.firstChild);
        }

        // takes options from select and turns into dropdown items
        Array.from(select.options).forEach((opt) => {
            if (opt.hidden) return; // in case you later hide any

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
            // prevent double-init
            if (select.dataset.enhanced === "1") return;
            select.dataset.enhanced = "1";

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
            const timeKey = showSel.value;

            if (!movieId || !locationId || !timeKey) {
                alert("Please select movie, location, and showtime.");
                return;
            }

            const [sessionDate, sessionTime] = timeKey.split("|");

            const url =
                "movieDetail.php?id=" +
                encodeURIComponent(movieId) +
                "&location_id=" +
                encodeURIComponent(locationId) +
                "&date=" +
                encodeURIComponent(sessionDate) +
                "&time=" +
                encodeURIComponent(sessionTime);

            window.location.href = url;
        });
    }
});
