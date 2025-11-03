document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".timer-box").forEach(box => {
        let remaining = parseInt(box.getAttribute("data-remaining"), 10);
        let textEl = box.querySelector(".timer-countdown");

        function colorchange(seconds) {
            if (seconds > 420) {
                box.style.color = "#50fa7b";
                textEl.style.color = "#50fa7b";
            } else if (seconds > 300) {
                box.style.color = "#f5e14a";
                textEl.style.color = "#f5e14a";
            } else if (seconds > 120) {
                box.style.color = "#ffb347";
                textEl.style.color = "#ffb347";
            } else {
                box.style.color = "#ff4f4f";
                textEl.style.color = "#ff4f4f";
            }
        }
        function updateTimer() {
            if (remaining <= 0) {
                textEl.textContent = "Expired";
                box.style.color = "#ff4f4f";
                setTimeout(() => window.location.reload(), 1000);
                return;
            }

            colorchange(remaining);

            let m = Math.floor(remaining / 60);
            let s = remaining % 60;
            textEl.textContent = `${m}:${s.toString().padStart(2, '0')}`;
            remaining--;
            setTimeout(updateTimer, 1000);
        }
        updateTimer();
    });
});
