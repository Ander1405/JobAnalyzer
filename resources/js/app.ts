import { createInertiaApp, router as inertiaRouter } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { initializeTheme } from '@/lib/theme';
import router from '@/router';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

initializeTheme();

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    progress: {
        color: 'var(--jh-primary)',
    },
    resolve: (name) => {
        const pages = import.meta.glob<{ default: DefineComponent }>(
            './pages/**/*.vue',
        );

        return resolvePageComponent(`./pages/${name}.vue`, pages).then(
            (module) => module.default,
        );
    },
    setup({ el, App, props, plugin }) {
        inertiaRouter.on('navigate', (event) => {
            if (event.detail.page.component !== 'App/Shell') {
                return;
            }

            const targetUrl = new URL(
                event.detail.page.url,
                window.location.origin,
            );
            const targetPath = `${targetUrl.pathname}${targetUrl.search}${targetUrl.hash}`;

            if (router.currentRoute.value.fullPath !== targetPath) {
                void router.replace(targetPath);
            }
        });

        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(router)
            .mount(el);
    },
});
