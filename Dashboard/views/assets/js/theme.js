(() => {
    const storageKey = 'dashboard-theme';
    const root = document.documentElement;
    const systemTheme = window.matchMedia('(prefers-color-scheme: dark)');
    let preference = null;

    try {
        const saved = window.localStorage.getItem(storageKey);
        if (saved === 'light' || saved === 'dark') preference = saved;
    } catch (_) {
        // The toggle still works when browser storage is unavailable.
    }

    function applyTheme() {
        const theme = preference || (systemTheme.matches ? 'dark' : 'light');
        root.dataset.theme = theme;
        document.querySelectorAll('[data-theme-toggle]').forEach(toggle => {
            toggle.checked = theme === 'dark';
            const label = theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode';
            toggle.setAttribute('aria-label', label);
            toggle.title = label;
            const icon = toggle.querySelector('[data-theme-icon]');
            if (icon) icon.className = 'fa-solid ' + (theme === 'dark' ? 'fa-sun' : 'fa-moon');
        });
    }

    // Apply before the stylesheet loads to avoid flashing the wrong theme.
    applyTheme();

    document.addEventListener('DOMContentLoaded', () => {
        applyTheme();
        document.querySelectorAll('[data-theme-toggle]').forEach(toggle => {
            const isButton = toggle.tagName === 'BUTTON';
            toggle.addEventListener(isButton ? 'click' : 'change', () => {
                preference = isButton
                    ? (root.dataset.theme === 'dark' ? 'light' : 'dark')
                    : (toggle.checked ? 'dark' : 'light');
                applyTheme();
                try {
                    window.localStorage.setItem(storageKey, preference);
                } catch (_) {
                    // Keep the selected theme for this page even without storage.
                }
            });
        });
    });

    systemTheme.addEventListener('change', () => {
        if (!preference) applyTheme();
    });
})();
