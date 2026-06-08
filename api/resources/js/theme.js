const STORAGE_KEY = 'careguard-theme';

export function getPreferredTheme() {
    const stored = localStorage.getItem(STORAGE_KEY);
    if (stored === 'dark' || stored === 'light') {
        return stored;
    }
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

export function applyTheme(theme) {
    document.documentElement.classList.toggle('dark', theme === 'dark');
}

export function initThemeStore(Alpine) {
    Alpine.store('theme', {
        dark: getPreferredTheme() === 'dark',

        init() {
            applyTheme(this.dark ? 'dark' : 'light');
        },

        toggle() {
            this.dark = !this.dark;
            const theme = this.dark ? 'dark' : 'light';
            localStorage.setItem(STORAGE_KEY, theme);
            applyTheme(theme);
        },
    });
}

// Run before paint to avoid flash
applyTheme(getPreferredTheme());
