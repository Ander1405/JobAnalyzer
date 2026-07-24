<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { useRoute } from 'vue-router';
import AppIcon from '@/components/AppIcon.vue';
import type { AppIconName } from '@/components/AppIcon.vue';
import ThemeSwitcher from '@/components/ThemeSwitcher.vue';
import { cn } from '@/lib/utils';

const props = defineProps<{
    collapsed: boolean;
    badges: { marketplace: number; tracking: number };
}>();

const emit = defineEmits<{
    'toggle-collapsed': [];
    navigate: [];
}>();

const route = useRoute();
const page = usePage();

function logout() {
    router.post('/logout');
}

const items: ReadonlyArray<{
    name: 'marketplace' | 'tracking' | 'profile';
    label: string;
    icon: AppIconName;
}> = [
    { name: 'marketplace', label: 'Marketplace', icon: 'marketplace' },
    { name: 'tracking', label: 'Mis vacantes', icon: 'tracking' },
    { name: 'profile', label: 'Mi perfil', icon: 'profile' },
] as const;

function badgeFor(name: (typeof items)[number]['name']): number {
    if (name === 'marketplace') {
        return props.badges.marketplace;
    }

    if (name === 'tracking') {
        return props.badges.tracking;
    }

    return 0;
}

function isActive(name: (typeof items)[number]['name']): boolean {
    return route.path === `/${name}` || route.path.startsWith(`/${name}/`);
}
</script>

<template>
    <nav
        class="flex h-full flex-col gap-1 p-3 text-slate-300"
        aria-label="Navegación principal"
    >
        <div
            class="mb-7 flex items-center justify-between px-2"
            :class="collapsed && 'justify-center'"
        >
            <span
                v-if="!collapsed"
                class="flex items-center gap-2 text-lg font-black tracking-[-0.06em] text-white"
            >
                <span
                    class="grid h-7 w-7 place-items-center rounded-control bg-signal-500 text-white shadow-action"
                >
                    <AppIcon name="brand" class="h-4 w-4" />
                </span>
                JobHunter
            </span>
            <button
                type="button"
                class="hidden h-11 w-11 items-center justify-center rounded-control text-slate-400 hover:bg-slate-800 hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-signal-200 lg:flex"
                :aria-label="
                    collapsed ? 'Expandir sidebar' : 'Colapsar sidebar'
                "
                @click="emit('toggle-collapsed')"
            >
                <AppIcon
                    :name="collapsed ? 'expand' : 'collapse'"
                    class="h-5 w-5"
                />
            </button>
        </div>

        <router-link
            v-for="item in items"
            :key="item.name"
            :to="`/${item.name}`"
            :class="
                cn(
                    'relative flex min-h-11 items-center gap-3 rounded-control px-3 py-2.5 text-sm font-semibold transition-[color,background-color,box-shadow] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-signal-200',
                    isActive(item.name)
                        ? 'bg-signal-500 text-white shadow-[0_8px_18px_rgba(23,73,233,0.25)]'
                        : 'text-slate-300 hover:bg-slate-800 hover:text-white',
                    collapsed && 'justify-center px-0',
                )
            "
            :aria-label="collapsed ? item.label : undefined"
            :title="collapsed ? item.label : undefined"
            @click="emit('navigate')"
        >
            <AppIcon
                :name="item.icon"
                class="h-5 w-5 shrink-0"
                :class="isActive(item.name) ? 'text-white' : 'text-signal-200'"
            />
            <span v-if="!collapsed" class="flex-1">{{ item.label }}</span>
            <span
                v-if="!collapsed && badgeFor(item.name) > 0"
                class="rounded-full bg-signal-200 px-1.5 py-0.5 text-xs font-bold text-signal-950"
            >
                {{ badgeFor(item.name) }}
            </span>
            <span
                v-else-if="collapsed && badgeFor(item.name) > 0"
                class="absolute top-1.5 right-1.5 h-2 w-2 rounded-full bg-signal-200 ring-2 ring-signal-950"
                aria-hidden="true"
            />
        </router-link>

        <div class="mt-auto flex flex-col gap-2 border-t border-slate-800 pt-3">
            <ThemeSwitcher :collapsed="collapsed" />
            <p v-if="!collapsed" class="truncate px-2 text-xs text-slate-400">
                {{ page.props.auth.user.email }}
            </p>
            <button
                type="button"
                class="flex min-h-11 items-center gap-3 rounded-control px-3 py-2.5 text-sm font-semibold text-slate-300 hover:bg-slate-800 hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-signal-200"
                :class="collapsed && 'justify-center'"
                title="Cerrar sesión"
                @click="logout"
            >
                <AppIcon name="logout" class="h-5 w-5 shrink-0" />
                <span v-if="!collapsed">Cerrar sesión</span>
            </button>
        </div>
    </nav>
</template>
