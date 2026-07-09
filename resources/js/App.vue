<template>
    <div
        class="flex flex-col overflow-hidden bg-slate-50 text-slate-900"
        style="height: 100vh; height: 100dvh"
    >
        <!-- Fixed header (still position:fixed, works fine) -->
        <AppHeader />

        <!-- Content row: below the fixed header -->
        <div class="flex min-h-0 flex-1 pt-[65px]">
            <AppSidebar v-if="authStore.isAuthenticated" />
            <main
                class="min-h-0 flex-1 overflow-y-auto"
                :class="authStore.isAuthenticated ? 'md:ml-16' : ''"
            >
                <router-view />
            </main>
        </div>

        <!-- Mobile bottom nav: static flex child — never scrolls, always at bottom -->
        <nav
            v-if="authStore.isAuthenticated"
            class="flex shrink-0 bg-[#1a1f2e] shadow-[0_-1px_0_rgba(255,255,255,0.08)] md:hidden"
            style="padding-bottom: env(safe-area-inset-bottom)"
        >
            <router-link
                v-for="item in mobileNavItems"
                :key="item.to"
                :to="item.to"
                class="flex flex-1 flex-col items-center justify-center gap-0.5 py-2.5 transition-colors duration-150"
                :class="isMobileActive(item) ? 'text-emerald-400' : 'text-white/50 hover:text-white/80'"
            >
                <component :is="item.icon" class="h-5 w-5 shrink-0" />
                <span class="text-[10px] font-medium leading-tight">{{ item.shortLabel }}</span>
            </router-link>
        </nav>
    </div>
</template>

<script setup>
import { computed, h } from 'vue';
import { useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import AppHeader from './components/layout/AppHeader.vue';
import AppSidebar from './components/layout/AppSidebar.vue';
import { useAuthStore } from './stores/auth';

const authStore = useAuthStore();
const route = useRoute();
const { t } = useI18n();

const FishIcon   = () => h('svg', { viewBox: '0 0 24 24', fill: 'currentColor' }, [h('path', { d: 'M19.5 12c0 0-3-5-7.5-5S4.5 12 4.5 12 7.5 17 12 17s7.5-5 7.5-5zm1 0 3-2.5v5L20.5 12zM14 10.5a2 2 0 1 1-4 0 2 2 0 0 1 4 0z' })]);
const MapPinIcon = () => h('svg', { viewBox: '0 0 24 24', fill: 'currentColor' }, [h('path', { d: 'M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z' })]);
const SearchIcon = () => h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2', 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }, [h('circle', { cx: '11', cy: '11', r: '7' }), h('line', { x1: '21', y1: '21', x2: '16.65', y2: '16.65' })]);
const UsersIcon  = () => h('svg', { viewBox: '0 0 24 24', fill: 'currentColor' }, [h('path', { d: 'M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z' })]);
const ShieldIcon = () => h('svg', { viewBox: '0 0 24 24', fill: 'currentColor' }, [h('path', { d: 'M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 4l6 2.67V11c0 3.84-2.54 7.42-6 8.93C8.54 18.42 6 14.84 6 11V7.67L12 5z' })]);

const mobileNavItems = computed(() => {
    const items = [
        { to: '/cabinet', shortLabel: t('nav.myFishingShort'), icon: FishIcon,   exact: false },
        { to: '/map',     shortLabel: t('nav.lakeMapShort'),   icon: MapPinIcon, exact: true },
        { to: '/posts',   shortLabel: t('nav.postsShort'),     icon: SearchIcon, exact: true },
        { to: '/',        shortLabel: t('nav.communityShort'), icon: UsersIcon,  exact: true },
    ];
    if (authStore.user?.is_admin) {
        items.push({ to: '/admin', shortLabel: 'Адмін', icon: ShieldIcon, exact: false });
    }
    return items;
});

function isMobileActive(item) {
    if (item.exact) return route.path === item.to;
    return route.path === item.to || route.path.startsWith(item.to + '/');
}
</script>
