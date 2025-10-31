document.addEventListener("DOMContentLoaded", () => {
    const menuBtn = document.getElementById("menuBtn");
    const sidePanel = document.getElementById("sidePanel");
    const closePanelBtn = document.getElementById("closePanel");
    const panelOverlay = document.getElementById("panelOverlay");

    const searchToggle = document.getElementById("searchToggle");
    const searchBar = document.getElementById("searchBar");
    const searchInput = document.getElementById("searchInput");
    const searchClose = document.getElementById("searchClose");
    const searchOverlay = document.getElementById("searchOverlay");

    function openPanel() {
        sidePanel.classList.add("is-open");
        panelOverlay.classList.add("is-open");
        sidePanel.removeAttribute("inert");
        sidePanel.setAttribute("aria-hidden", "false");
        document.body.classList.add("body-locked");

        // Move focus into panel for accessibility
        const firstFocusable = sidePanel.querySelector(
            "button, [href], input, select, textarea, [tabindex]:not([tabindex='-1'])"
        );
        if (firstFocusable) firstFocusable.focus();
    }

    function closePanel() {
        // Return focus to the trigger button

        if (menuBtn) {
            menuBtn.focus();
        } else {
            document.body.focus();
        }
        sidePanel.classList.remove("is-open");
        panelOverlay.classList.remove("is-open");
        sidePanel.setAttribute("inert", "");
        sidePanel.setAttribute("aria-hidden", "true");
        document.body.classList.remove("body-locked");
    }

    menuBtn.addEventListener("click", openPanel);
    closePanelBtn.addEventListener("click", closePanel);
    panelOverlay.addEventListener("click", closePanel);

    // Close with Esc
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape" && sidePanel.classList.contains("is-open")) {
            closePanel();
        }
    });

    if (!searchToggle || !searchBar) return; // safety

    function openSearch() {
        searchBar.classList.add("show");
        searchOverlay?.classList.add("show");
        setTimeout(() => searchInput && searchInput.focus(), 150);
    }

    function closeSearch() {
        searchBar.classList.remove("show");
        searchOverlay?.classList.remove("show");
    }

    searchToggle.addEventListener("click", () => {
        const open = searchBar.classList.contains("show");
        if (open) {
            closeSearch();
        } else {
            openSearch();
        }
    });

    searchClose?.addEventListener("click", closeSearch);
    searchOverlay?.addEventListener("click", closeSearch);

    const toggle = document.getElementById("quickBuyToggle");
    const quickbuy = document.querySelector(".quickbuy-list");

    if (!toggle || !quickbuy) return;

    toggle.addEventListener("click", (e) => {
        e.preventDefault();
        e.stopPropagation(); // so the document click below doesn't immediately close it
        quickbuy.classList.toggle("active");
    });

    document.addEventListener("click", (e) => {
        // clicked outside
        if (!quickbuy.contains(e.target) && e.target !== toggle) {
            quickbuy.classList.remove("active");
        }
    });
});
