export type ThemePreference = 'light' | 'dark' | 'system';

export const THEME_CHANGE_EVENT = 'jobhunter:theme-change';

const STORAGE_KEY = 'theme:preference';
const THEME_COLORS = {
    light: '#f2f5fb',
    dark: '#08101f',
} as const;

export function resolveTheme(
    preference: ThemePreference,
    prefersDark: boolean,
): 'light' | 'dark' {
    if (preference === 'system') {
        return prefersDark ? 'dark' : 'light';
    }

    return preference;
}

export function getStoredThemePreference(): ThemePreference {
    let storedPreference: string | null = null;

    try {
        storedPreference = window.localStorage.getItem(STORAGE_KEY);
    } catch {
        return 'system';
    }

    if (storedPreference === 'light' || storedPreference === 'dark') {
        return storedPreference;
    }

    return 'system';
}

export function applyThemePreference(preference: ThemePreference): void {
    const prefersDark = window.matchMedia(
        '(prefers-color-scheme: dark)',
    ).matches;
    const theme = resolveTheme(preference, prefersDark);

    document.documentElement.classList.toggle('dark', theme === 'dark');
    document.documentElement.dataset.theme = theme;
    document
        .querySelector<HTMLMetaElement>('meta[name="theme-color"]')
        ?.setAttribute('content', THEME_COLORS[theme]);
}

export function setThemePreference(preference: ThemePreference): void {
    try {
        if (preference === 'system') {
            window.localStorage.removeItem(STORAGE_KEY);
        } else {
            window.localStorage.setItem(STORAGE_KEY, preference);
        }
    } catch {
        // El tema sigue aplicándose aunque el navegador bloquee el almacenamiento.
    }

    applyThemePreference(preference);
    window.dispatchEvent(
        new CustomEvent<ThemePreference>(THEME_CHANGE_EVENT, {
            detail: preference,
        }),
    );
}

export function initializeTheme(): () => void {
    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    const applyStoredPreference = () => {
        applyThemePreference(getStoredThemePreference());
    };

    applyStoredPreference();
    mediaQuery.addEventListener('change', applyStoredPreference);

    return () =>
        mediaQuery.removeEventListener('change', applyStoredPreference);
}
