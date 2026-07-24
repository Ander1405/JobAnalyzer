<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import AppIcon from '@/components/AppIcon.vue';
import {
    getStoredThemePreference,
    setThemePreference,
    THEME_CHANGE_EVENT,
} from '@/lib/theme';
import type { ThemePreference } from '@/lib/theme';

defineProps<{
    collapsed: boolean;
}>();

const preference = ref<ThemePreference>(getStoredThemePreference());

const options: Array<{ value: ThemePreference; label: string }> = [
    { value: 'light', label: 'Claro' },
    { value: 'dark', label: 'Oscuro' },
    { value: 'system', label: 'Sistema' },
];

const activeLabel = computed(
    () =>
        options.find((option) => option.value === preference.value)?.label ??
        'Sistema',
);
const activeIcon = computed(() => {
    if (preference.value === 'light') {
        return 'sun';
    }

    if (preference.value === 'dark') {
        return 'moon';
    }

    return 'system';
});

onMounted(() => {
    window.addEventListener(THEME_CHANGE_EVENT, syncThemePreference);
});

onBeforeUnmount(() => {
    window.removeEventListener(THEME_CHANGE_EVENT, syncThemePreference);
});

function syncThemePreference(event: Event): void {
    preference.value = (event as CustomEvent<ThemePreference>).detail;
}

function updateTheme(event: Event): void {
    const value = (event.target as HTMLSelectElement).value as ThemePreference;
    preference.value = value;
    setThemePreference(value);
}
</script>

<template>
    <div v-if="!collapsed" class="grid gap-1.5 px-1">
        <label
            for="theme-preference"
            class="px-2 text-xs font-medium text-slate-400"
        >
            Apariencia
        </label>
        <div class="relative">
            <AppIcon
                :name="activeIcon"
                class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-signal-200"
            />
            <select
                id="theme-preference"
                :value="preference"
                class="min-h-11 w-full appearance-none rounded-control border border-slate-700 bg-slate-900 py-2 pr-9 pl-10 text-sm font-semibold text-slate-100 transition-colors outline-none hover:border-slate-500 focus:border-signal-200 focus:ring-2 focus:ring-signal-500/35"
                @change="updateTheme"
            >
                <option
                    v-for="option in options"
                    :key="option.value"
                    :value="option.value"
                >
                    {{ option.label }}
                </option>
            </select>
            <svg
                class="pointer-events-none absolute top-1/2 right-3 h-4 w-4 -translate-y-1/2 text-slate-400"
                viewBox="0 0 20 20"
                fill="none"
                aria-hidden="true"
            >
                <path
                    d="m6 8 4 4 4-4"
                    stroke="currentColor"
                    stroke-width="1.75"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />
            </svg>
        </div>
    </div>

    <label
        v-else
        class="relative mx-auto flex h-11 w-11 cursor-pointer items-center justify-center rounded-control text-slate-300 transition-colors focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-signal-200 hover:bg-slate-800 hover:text-white"
        :title="`Apariencia: ${activeLabel}`"
    >
        <span class="sr-only">Apariencia</span>
        <AppIcon :name="activeIcon" class="h-5 w-5" />
        <select
            :value="preference"
            class="absolute inset-0 cursor-pointer opacity-0"
            aria-label="Seleccionar apariencia"
            @change="updateTheme"
        >
            <option
                v-for="option in options"
                :key="option.value"
                :value="option.value"
            >
                {{ option.label }}
            </option>
        </select>
    </label>
</template>
