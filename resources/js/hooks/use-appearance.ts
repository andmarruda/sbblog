import { useCallback, useSyncExternalStore } from 'react';

export type Appearance = 'light' | 'dark' | 'system';

const STORAGE_KEY = 'appearance';
const listeners = new Set<() => void>();

const darkQuery = () => window.matchMedia('(prefers-color-scheme: dark)');

function readStored(): Appearance {
    try {
        const value = localStorage.getItem(STORAGE_KEY);

        return value === 'light' || value === 'dark' ? value : 'system';
    } catch {
        return 'system';
    }
}

let current: Appearance =
    typeof window === 'undefined' ? 'system' : readStored();

function isDark(appearance: Appearance) {
    return (
        appearance === 'dark' ||
        (appearance === 'system' && darkQuery().matches)
    );
}

function applyTheme() {
    const dark = isDark(current);

    document.documentElement.classList.toggle('dark', dark);
    document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
}

function setAppearance(appearance: Appearance) {
    current = appearance;

    try {
        localStorage.setItem(STORAGE_KEY, appearance);
    } catch {
        // Storage can be unavailable (private mode); the cookie still works.
    }

    // The server reads the cookie to render the right class before React boots.
    document.cookie = `${STORAGE_KEY}=${appearance};path=/;max-age=31536000;SameSite=Lax`;

    applyTheme();
    listeners.forEach((listener) => listener());
}

function subscribe(listener: () => void) {
    listeners.add(listener);

    return () => listeners.delete(listener);
}

/**
 * Apply the stored theme on boot and follow the OS setting while on "system".
 */
export function initializeTheme() {
    applyTheme();

    darkQuery().addEventListener('change', () => {
        applyTheme();
        listeners.forEach((listener) => listener());
    });
}

export function useAppearance() {
    const appearance = useSyncExternalStore(
        subscribe,
        () => current,
        () => 'system' as Appearance,
    );

    const updateAppearance = useCallback(
        (value: Appearance) => setAppearance(value),
        [],
    );

    return {
        appearance,
        resolved: isDark(appearance) ? 'dark' : 'light',
        updateAppearance,
    } as const;
}
