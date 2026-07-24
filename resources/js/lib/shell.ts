import { inject } from 'vue';
import type { InjectionKey, Ref } from 'vue';
import type { AppIconName } from '@/components/AppIcon.vue';

export type ShellLoadStatus = 'loading' | 'ready' | 'error';

export type ShellBadges = {
    marketplace: number;
    tracking: number;
};

export type AiSettingSummary = {
    provider: string;
    model: string | null;
};

export type ShellNavigationItem = {
    name: 'marketplace' | 'tracking' | 'profile' | 'ai-settings';
    label: string;
    path: string;
    icon: AppIconName;
    badge?: keyof ShellBadges;
};

export type ShellNavigationSection = {
    label: string;
    items: readonly ShellNavigationItem[];
};

export type ShellContext = {
    badges: Readonly<Ref<ShellBadges>>;
    badgesStatus: Readonly<Ref<ShellLoadStatus>>;
    aiSetting: Readonly<Ref<AiSettingSummary | null>>;
    aiSettingStatus: Readonly<Ref<ShellLoadStatus>>;
    refreshBadges: () => Promise<void>;
    refreshAiSetting: () => Promise<void>;
};

export const SHELL_NAVIGATION: readonly ShellNavigationSection[] = [
    {
        label: 'Descubrir',
        items: [
            {
                name: 'marketplace',
                label: 'Marketplace',
                path: '/marketplace',
                icon: 'marketplace',
                badge: 'marketplace',
            },
            {
                name: 'tracking',
                label: 'Mis vacantes',
                path: '/tracking',
                icon: 'tracking',
                badge: 'tracking',
            },
        ],
    },
    {
        label: 'Ajustar',
        items: [
            {
                name: 'profile',
                label: 'Mi perfil',
                path: '/profile',
                icon: 'profile',
            },
            {
                name: 'ai-settings',
                label: 'Configuración IA',
                path: '/profile/ai-settings',
                icon: 'ai-settings',
            },
        ],
    },
] as const;

export const shellContextKey: InjectionKey<ShellContext> =
    Symbol('shell-context');

export function useShellContext(): ShellContext {
    const context = inject(shellContextKey);

    if (!context) {
        throw new Error('Shell context is not available.');
    }

    return context;
}

export function isShellItemActive(
    item: ShellNavigationItem,
    path: string,
): boolean {
    if (item.name === 'ai-settings') {
        return path.startsWith('/profile/ai-settings');
    }

    if (item.name === 'profile') {
        return path === '/profile' || path.startsWith('/profile/design-system');
    }

    return path === item.path || path.startsWith(`${item.path}/`);
}

export function getShellSectionTitle(path: string): string {
    for (const section of SHELL_NAVIGATION) {
        const item = section.items.find((candidate) =>
            isShellItemActive(candidate, path),
        );

        if (item) {
            return item.label;
        }
    }

    return 'JobHunter';
}

export function getAiProviderLabel(
    provider: string | null | undefined,
): string {
    return (
        {
            claude_cli: 'Claude CLI',
            gemini: 'Gemini',
            openrouter: 'OpenRouter',
        }[provider ?? ''] ?? 'Proveedor desconocido'
    );
}

export function formatNavigationBadge(value: number): string {
    return value > 99 ? '99+' : String(Math.max(0, value));
}
