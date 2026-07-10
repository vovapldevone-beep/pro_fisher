import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const routes = [
    {
        path: '/',
        name: 'home',
        component: () => import('../pages/HomePage.vue'),
    },
    {
        path: '/map',
        name: 'map',
        component: () => import('../pages/MapPage.vue'),
    },
    {
        path: '/lakes/:slug',
        name: 'lake-detail',
        component: () => import('../pages/LakeDetailPage.vue'),
    },
    {
        path: '/login',
        name: 'login',
        component: () => import('../pages/LoginPage.vue'),
        meta: { guest: true },
    },
    {
        path: '/register',
        name: 'register',
        component: () => import('../pages/RegisterPage.vue'),
        meta: { guest: true },
    },
    {
        path: '/posts',
        name: 'posts',
        component: () => import('../pages/PostsPage.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/fishers/:id',
        name: 'fisher',
        component: () => import('../pages/FisherPage.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/cabinet',
        name: 'cabinet',
        component: () => import('../pages/CabinetPage.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/cabinet/catches',
        name: 'cabinet-catches',
        component: () => import('../pages/CabinetCatchesPage.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/cabinet/achievements',
        name: 'cabinet-achievements',
        component: () => import('../pages/AchievementsPage.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/admin',
        name: 'admin',
        component: () => import('../pages/admin/AdminPage.vue'),
        meta: { requiresAdmin: true },
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

let authReady = false;

router.beforeEach(async (to) => {
    const authStore = useAuthStore();

    if (!authReady) {
        await authStore.fetchUser();
        authReady = true;
    }

    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        return { name: 'login', query: { redirect: to.fullPath } };
    }

    if (to.meta.requiresAdmin) {
        if (!authStore.isAuthenticated) return { name: 'login' };
        if (!authStore.user?.is_admin) return { name: 'posts' };
    }

    if (to.meta.guest && authStore.isAuthenticated) {
        return { name: 'posts' };
    }

    // Once signed in, "/" is no longer the landing page — Пости is
    if (to.name === 'home' && authStore.isAuthenticated) {
        return { name: 'posts' };
    }
});

export default router;
