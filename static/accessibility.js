(() => {
    const STORAGE_KEY = "high_readability_mode";
    const toggleId = "readability-toggle";

    function setMode(enabled) {
        document.body.classList.toggle("readability-mode", enabled);
        const btn = document.getElementById(toggleId);
        if (!btn) return;
        btn.textContent = enabled ? "High Readability: On" : "High Readability: Off";
        btn.setAttribute("aria-pressed", enabled ? "true" : "false");
    }

    function loadInitialMode() {
        const saved = localStorage.getItem(STORAGE_KEY);
        return saved === "true";
    }

    document.addEventListener("DOMContentLoaded", () => {
        const initial = loadInitialMode();
        setMode(initial);

        const btn = document.getElementById(toggleId);
        if (!btn) return;

        btn.addEventListener("click", () => {
            const enabled = !document.body.classList.contains("readability-mode");
            setMode(enabled);
            localStorage.setItem(STORAGE_KEY, String(enabled));
        });
    });
})();
