import { createRouter, createWebHistory } from 'vue-router';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/', redirect: '/marketplace' },
        {
            path: '/marketplace',
            name: 'marketplace',
            component: () => import('@/views/Marketplace/MarketplaceView.vue'),
        },
        {
            path: '/marketplace/:id',
            name: 'marketplace.job',
            component: () => import('@/views/Marketplace/JobDetailView.vue'),
            props: true,
        },
        {
            path: '/tracking',
            name: 'tracking',
            component: () => import('@/views/Tracking/TrackingView.vue'),
        },
        {
            path: '/tracking/:id',
            name: 'tracking.detail',
            component: () =>
                import('@/views/Tracking/TrackedJobDetailView.vue'),
            props: true,
        },
        {
            path: '/profile/design-system',
            name: 'design-system',
            component: () => import('@/views/Profile/DesignSystemView.vue'),
        },
        {
            path: '/profile',
            name: 'profile',
            component: () => import('@/views/Profile/ProfileView.vue'),
        },
    ],
});

export default router;
