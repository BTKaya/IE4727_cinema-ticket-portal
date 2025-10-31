// js/movie-detail.js

document.addEventListener("DOMContentLoaded", () => {
    // 1. read data passed from PHP (in movieDetail.php)
    const SESSIONS = window.SESSIONS || [];
    const LOCATIONS = window.LOCATIONS || {}; // { 1: "Downtown", 2: "City Mall" }
    const MOVIE_ID = window.MOVIE_ID;

    // possible preselected values (from query string)
    const INIT_DATE = window.INIT_DATE || "";
    const INIT_TIME = window.INIT_TIME || "";
    const INIT_LOC = window.INIT_LOC || "";
    const INIT_LOC_ID = window.INIT_LOC_ID || null; // we added this in PHP

    // 2. get DOM refs
    const dateSel = document.getElementById("screening_date");
    const timeSel = document.getElementById("screening_time");
    const locSel = document.getElementById("location");

    if (!dateSel || !timeSel || !locSel) {
        // page doesn't have the selects (safety)
        return;
    }

    // 3. build unique date list from sessions
    // SESSIONS shape: [{id: 5, session_date: "2025-11-04", session_time: "09:00:00", location_id: 2}, ...]
    const uniqueDates = [...new Set(SESSIONS.map((s) => s.session_date))];

    // ----- helper: populate dates -----
    function populateDates(selected = "") {
        dateSel.innerHTML = '<option value="">-- Select --</option>';
        uniqueDates.forEach((d) => {
            const opt = document.createElement("option");
            opt.value = d;
            opt.textContent = d;
            if (d === selected) {
                opt.selected = true;
            }
            dateSel.appendChild(opt);
        });
    }

    // ----- helper: given a date → get times -----
    function getTimesForDate(d) {
        return [
            ...new Set(
                SESSIONS.filter((s) => s.session_date === d).map((s) =>
                    s.session_time.slice(0, 5)
                ) // "09:00:00" → "09:00"
            ),
        ];
    }

    // ----- helper: given date+time → get location ids -----
    function getLocationsForDateTime(d, t) {
        return [
            ...new Set(
                SESSIONS.filter(
                    (s) => s.session_date === d && s.session_time.startsWith(t)
                ).map((s) => s.location_id)
            ),
        ];
    }

    // ----- helper: populate times -----
    function populateTimes(d, selected = "") {
        timeSel.innerHTML = '<option value="">-- Select --</option>';
        if (!d) return;
        const times = getTimesForDate(d);
        times.forEach((t) => {
            const opt = document.createElement("option");
            opt.value = t;
            opt.textContent = t;
            if (t === selected) {
                opt.selected = true;
            }
            timeSel.appendChild(opt);
        });
    }

    // ----- helper: populate locations -----
    function populateLocations(d, t, selectedName = "") {
        locSel.innerHTML = '<option value="">-- Select --</option>';
        if (!d || !t) return;
        const locIds = getLocationsForDateTime(d, t);
        locIds.forEach((id) => {
            const name = LOCATIONS[id];
            if (!name) return;
            const opt = document.createElement("option");
            opt.value = name; // we still show NAME to user
            opt.textContent = name;
            if (name === selectedName) {
                opt.selected = true;
            }
            locSel.appendChild(opt);
        });
    }

    // ----- helper: redirect when all 3 selected -----
    function redirectIfComplete() {
        const d = dateSel.value;
        const t = timeSel.value;
        const locName = locSel.value;

        if (!(d && t && locName)) return;

        // name → id using LOCATIONS
        const locId = Object.keys(LOCATIONS).find(
            (id) => LOCATIONS[id] === locName
        );

        if (!locId) {
            console.warn("Could not find location_id for name:", locName);
            return;
        }

        // ✅ send location_id, not location name
        window.location.href =
            `movieDetail.php?id=${MOVIE_ID}` +
            `&date=${encodeURIComponent(d)}` +
            `&time=${encodeURIComponent(t)}` +
            `&location_id=${encodeURIComponent(locId)}`;
    }

    // 4. initial population (page load)
    populateDates(INIT_DATE);
    populateTimes(INIT_DATE, INIT_TIME);
    populateLocations(INIT_DATE, INIT_TIME, INIT_LOC);

    // if PHP told us which location_id was chosen, we can force-select it in UI
    if (INIT_LOC_ID && !INIT_LOC) {
        const nameFromId = LOCATIONS[INIT_LOC_ID];
        if (nameFromId) {
            // only set if options already built
            const opt = [...locSel.options].find((o) => o.value === nameFromId);
            if (opt) opt.selected = true;
        }
    }

    // 5. event listeners
    dateSel.addEventListener("change", () => {
        const d = dateSel.value;
        populateTimes(d);
        // reset locations
        locSel.innerHTML = '<option value="">-- Select --</option>';
        redirectIfComplete();
    });

    timeSel.addEventListener("change", () => {
        const d = dateSel.value;
        const t = timeSel.value;
        populateLocations(d, t);
        redirectIfComplete();
    });

    locSel.addEventListener("change", () => {
        redirectIfComplete();
    });
});
