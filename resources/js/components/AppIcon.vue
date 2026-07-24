<script setup lang="ts">
export type AppIconName =
    | 'brand'
    | 'ai-settings'
    | 'collapse'
    | 'close'
    | 'expand'
    | 'logout'
    | 'marketplace'
    | 'menu'
    | 'moon'
    | 'profile'
    | 'retry'
    | 'sun'
    | 'system'
    | 'tracking'
    | 'roles'
    | 'users';

defineProps<{
    name: AppIconName;
}>();
</script>

<template>
    <svg
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.8"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
        focusable="false"
    >
        <template v-if="name === 'brand'">
            <circle cx="12" cy="12" r="8.5" />
            <circle cx="12" cy="12" r="3.5" />
            <path d="M12 2.5V6M21.5 12H18M12 21.5V18M2.5 12H6" />
        </template>

        <template v-else-if="name === 'ai-settings'">
            <path d="M12 3.5v2M12 18.5v2M3.5 12h2M18.5 12h2" />
            <circle cx="12" cy="12" r="4.5" />
            <path
                d="m5.98 5.98 1.4 1.4M16.62 16.62l1.4 1.4M18.02 5.98l-1.4 1.4M7.38 16.62l-1.4 1.4"
            />
            <circle cx="12" cy="12" r="1.25" />
        </template>

        <template v-else-if="name === 'marketplace'">
            <circle cx="10.5" cy="10.5" r="6.5" />
            <path d="m15.5 15.5 4 4M18 3v4M16 5h4" />
        </template>

        <template v-else-if="name === 'tracking'">
            <rect x="3" y="4" width="5" height="5" rx="1" />
            <rect x="16" y="15" width="5" height="5" rx="1" />
            <path d="M8 6.5h4a4 4 0 0 1 4 4v4.5M12.5 12.5 16 16l3.5-3.5" />
        </template>

        <template v-else-if="name === 'profile'">
            <path d="M6 3h9l3 3v15H6zM15 3v4h4" />
            <circle cx="11.5" cy="11.5" r="2.25" />
            <path d="M8.5 17c.7-1.7 1.7-2.5 3-2.5s2.3.8 3 2.5" />
        </template>

        <template v-else-if="name === 'users'">
            <circle cx="9" cy="8" r="3" />
            <path
                d="M3.5 19c.5-3.2 2.3-5 5.5-5s5 1.8 5.5 5M15 5.5a3 3 0 0 1 0 5.5M15.5 14c2.8.2 4.5 1.8 5 5"
            />
        </template>

        <template v-else-if="name === 'roles'">
            <path d="M4 5h10M4 12h7M4 19h10" />
            <path
                d="m16 11 2-2 2 2v3.5c0 2.2-1.3 4.1-3 4.8-1.7-.7-3-2.6-3-4.8V11l2-2"
            />
        </template>

        <template v-else-if="name === 'sun'">
            <circle cx="12" cy="12" r="3.75" />
            <path
                d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.65 17.65l1.42 1.42M2 12h2M20 12h2M4.93 19.07l1.42-1.42M17.65 6.35l1.42-1.42"
            />
        </template>

        <template v-else-if="name === 'moon'">
            <path d="M20 15.2A8.5 8.5 0 0 1 8.8 4a8.5 8.5 0 1 0 11.2 11.2Z" />
        </template>

        <template v-else-if="name === 'system'">
            <rect x="3" y="4" width="18" height="13" rx="2" />
            <path d="M8 21h8M12 17v4" />
        </template>

        <template v-else-if="name === 'logout'">
            <path d="M10 5H5v14h5M14 8l4 4-4 4M18 12H9" />
        </template>

        <template v-else-if="name === 'close'">
            <path d="m6 6 12 12M18 6 6 18" />
        </template>

        <template v-else-if="name === 'retry'">
            <path d="M20 7v5h-5M4 17v-5h5" />
            <path d="M6.1 8.5A7 7 0 0 1 18.7 10M17.9 15.5A7 7 0 0 1 5.3 14" />
        </template>

        <template v-else-if="name === 'collapse'">
            <path d="m14.5 6-6 6 6 6" />
        </template>

        <template v-else-if="name === 'expand'">
            <path d="m9.5 6 6 6-6 6" />
        </template>

        <template v-else-if="name === 'menu'">
            <path d="M4 7h16M4 12h16M4 17h16" />
        </template>
    </svg>
</template>
