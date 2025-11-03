document.addEventListener("DOMContentLoaded", () => {
    const dateSel = document.getElementById("screening_date");
    const timeSel = document.getElementById("screening_time");
    const locSel = document.getElementById("location");

    if (!dateSel || !timeSel || !locSel) return;

    // Hidden inputs from PHP
    const MOVIE_ID = document.getElementById("movieId")?.value || "";
    const INIT_DATE = document.getElementById("initDate")?.value || "";
    const INIT_TIME = document.getElementById("initTime")?.value || "";
    const INIT_LOCID = document.getElementById("initLocation")?.value || "";
    const SESSION_ID = document.getElementById("sessionId")?.value || "";

    // Save original options for rebuilding
    const originalTimeOptions = [...timeSel.options].map((o) =>
        o.cloneNode(true)
    );
    const originalLocOptions = [...locSel.options].map((o) =>
        o.cloneNode(true)
    );

    function normalizeTime(t) {
        return (t || "").slice(0, 5);
    }

    function rebuildSelect(selectEl, optionsClones) {
        const placeholder =
            selectEl.options[0]?.cloneNode(true) ||
            new Option("-- Select --", "");
        selectEl.innerHTML = "";
        selectEl.appendChild(placeholder);
        optionsClones.forEach((clone) => selectEl.appendChild(clone));
        selectEl.selectedIndex = 0;
        selectEl.disabled = optionsClones.length === 0;
    }

    // Filter times by date
    function filterTimesByDate(dateStr) {
        if (!dateStr) {
            rebuildSelect(timeSel, []);
            rebuildSelect(locSel, []);
            return;
        }

        const seen = new Set();
        const matches = originalTimeOptions
            .slice(1)
            .filter((opt) => opt.dataset?.date === dateStr)
            .filter((opt) => {
                const key = normalizeTime(opt.value);
                if (seen.has(key)) return false;
                seen.add(key);
                return true;
            })
            .map((opt) => {
                const c = opt.cloneNode(true);
                c.value = normalizeTime(c.value);
                c.textContent = normalizeTime(c.textContent);
                return c;
            });

        rebuildSelect(timeSel, matches);
        rebuildSelect(locSel, []);
    }

    // Filter locations by date + time
    function filterLocationsByDateTime(dateStr, timeStr) {
        if (!dateStr || !timeStr) {
            rebuildSelect(locSel, []);
            return;
        }

        const allowedLocIds = new Set(
            originalTimeOptions
                .slice(1)
                .filter(
                    (opt) =>
                        opt.dataset?.date === dateStr &&
                        normalizeTime(opt.value) === normalizeTime(timeStr)
                )
                .map((opt) => String(opt.dataset.loc))
        );

        const locMatches = originalLocOptions
            .slice(1)
            .filter((opt) => allowedLocIds.has(String(opt.value)))
            .map((opt) => opt.cloneNode(true));

        rebuildSelect(locSel, locMatches);
    }

    // Redirect when all three values are selected
    function redirectIfComplete() {
        const d = dateSel.value;
        const t = normalizeTime(timeSel.value);
        const loc = locSel.value;

        if (!d || !t || !loc || !MOVIE_ID) return;

        const url =
            "movieDetail.php?id=" +
            encodeURIComponent(MOVIE_ID) +
            "&date=" +
            encodeURIComponent(d) +
            "&time=" +
            encodeURIComponent(t) +
            "&location_id=" +
            encodeURIComponent(loc);

        window.location.href = url;
    }

    // Event listeners
    dateSel.addEventListener("change", () => {
        filterTimesByDate(dateSel.value);
        redirectIfComplete();
    });

    timeSel.addEventListener("change", () => {
        filterLocationsByDateTime(dateSel.value, timeSel.value);
        redirectIfComplete();
    });

    locSel.addEventListener("change", redirectIfComplete);

    // --- Initialize selects ---
    if (INIT_DATE) {
        const dateOpt = [...dateSel.options].find((o) => o.value === INIT_DATE);
        if (!dateOpt) {
            const uniqueDates = Array.from(
                new Set(
                    originalTimeOptions
                        .slice(1)
                        .map((o) => o.dataset?.date)
                        .filter(Boolean)
                )
            );
            const dateClones = uniqueDates.map((d) => new Option(d, d));
            rebuildSelect(dateSel, dateClones);
        }
        dateSel.value = INIT_DATE;
        filterTimesByDate(INIT_DATE);
    } else {
        rebuildSelect(timeSel, []);
        rebuildSelect(locSel, []);
    }

    if (INIT_TIME) {
        const t = normalizeTime(INIT_TIME);
        const tOpt = [...timeSel.options].find(
            (o) => normalizeTime(o.value) === t
        );
        if (tOpt) timeSel.value = t;
        filterLocationsByDateTime(dateSel.value, t);
    }

    if (INIT_LOCID) {
        const locOpt = [...locSel.options].find(
            (o) => String(o.value) === String(INIT_LOCID)
        );
        if (locOpt) locSel.value = String(INIT_LOCID);
    }

    // Booking confirmation button
    const confirmBtn = document.getElementById("confirmBooking");
    if (confirmBtn) {
        confirmBtn.addEventListener("click", () => {
            const sessionHidden = document.getElementById("sessionId");
            const sessionId = sessionHidden ? sessionHidden.value : "";

            if (!sessionId) {
                alert(
                    " Session not found. Please reselect date/time/location."
                );
                return;
            }
        });
    }
});
