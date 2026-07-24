import { ref, watch } from 'vue';
import type { Ref } from 'vue';

/**
 * Ref backed by localStorage. Falls back to `defaultValue` when the key is
 * missing or storage is unavailable (private browsing, quota, SSR) — a
 * broken persistence layer should never crash the UI.
 */
export function usePersistedRef<T>(key: string, defaultValue: T): Ref<T> {
    let initial = defaultValue;

    try {
        const stored = localStorage.getItem(key);

        if (stored !== null) {
            initial = JSON.parse(stored) as T;
        }
    } catch {
        // Corrupt or inaccessible storage — keep the default.
    }

    const state = ref(initial) as Ref<T>;

    watch(
        state,
        (value) => {
            try {
                localStorage.setItem(key, JSON.stringify(value));
            } catch {
                // Storage full/unavailable — persistence is best-effort.
            }
        },
        { deep: true },
    );

    return state;
}
