<template>
    <!-- Desktop sidebar only — mobile nav is rendered in App.vue as a static flex child -->
    <nav
        class="group fixed left-0 top-[65px] z-40 hidden h-[calc(100vh-65px)] w-16 flex-col overflow-hidden bg-[#1a1f2e] shadow-xl transition-[width] duration-200 ease-in-out hover:w-56 md:flex"
    >
        <div class="flex flex-1 flex-col gap-1 py-4">
            <template v-for="item in navItems" :key="item.label">
                <router-link
                    :to="item.to"
                    class="flex items-center gap-4 pl-5 pr-4 py-3 transition-colors duration-150 hover:bg-white/10"
                    :class="isActive(item) ? 'text-emerald-400 bg-white/5' : 'text-white/60 hover:text-white'"
                >
                    <component :is="item.icon" class="h-6 w-6 shrink-0" />
                    <span class="whitespace-nowrap text-sm font-medium opacity-0 transition-opacity duration-150 delay-75 group-hover:opacity-100">
                        {{ item.label }}
                    </span>
                </router-link>
            </template>

            <button
                type="button"
                class="flex cursor-not-allowed items-center gap-4 pl-5 pr-4 py-3 text-white/30"
            >
                <component :is="GearIcon" class="h-6 w-6 shrink-0" />
                <span class="whitespace-nowrap text-sm font-medium opacity-0 transition-opacity duration-150 delay-75 group-hover:opacity-100">
                    {{ t('nav.settings') }}
                </span>
            </button>
        </div>
    </nav>
</template>

<script setup>
import { computed, h } from 'vue';
import { useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '../../stores/auth';

const authStore = useAuthStore();
const route = useRoute();
const { t } = useI18n();

const FishIcon = () =>
    h('svg', { viewBox: '0 0 24 24', fill: 'currentColor', class: 'h-6 w-6 shrink-0' }, [
        h('path', { d: 'M19.5 12c0 0-3-5-7.5-5S4.5 12 4.5 12 7.5 17 12 17s7.5-5 7.5-5zm1 0 3-2.5v5L20.5 12zM14 10.5a2 2 0 1 1-4 0 2 2 0 0 1 4 0z' }),
    ]);

const MapPinIcon = () =>
    h('svg', { viewBox: '0 0 24 24', fill: 'currentColor', class: 'h-6 w-6 shrink-0' }, [
        h('path', { d: 'M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z' }),
    ]);

const UsersIcon = () =>
    h('svg', { viewBox: '0 0 24 24', fill: 'currentColor', class: 'h-6 w-6 shrink-0' }, [
        h('path', { d: 'M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z' }),
    ]);

const GearIcon = () =>
    h('svg', { viewBox: '0 0 24 24', fill: 'currentColor', class: 'h-6 w-6 shrink-0' }, [
        h('path', { d: 'M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.57 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z' }),
    ]);

const SearchIcon = () =>
    h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2', 'stroke-linecap': 'round', 'stroke-linejoin': 'round', class: 'h-6 w-6 shrink-0' }, [
        h('circle', { cx: '11', cy: '11', r: '7' }),
        h('line', { x1: '21', y1: '21', x2: '16.65', y2: '16.65' }),
    ]);

const ShieldIcon = () =>
    h('svg', { viewBox: '0 0 24 24', fill: 'currentColor', class: 'h-6 w-6 shrink-0' }, [
        h('path', { d: 'M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 4l6 2.67V11c0 3.84-2.54 7.42-6 8.93C8.54 18.42 6 14.84 6 11V7.67L12 5z' }),
    ]);

const navItems = computed(() => {
    const items = [
        { to: '/cabinet', label: t('nav.myFishing'), icon: FishIcon,   exact: false },
        { to: '/map',     label: t('nav.lakeMap'),   icon: MapPinIcon, exact: true },
        { to: '/posts',   label: t('nav.posts'),     icon: SearchIcon, exact: true },
        // Спільнота прихована: після логіну "/" редіректить на Пости
        // { to: '/',        label: t('nav.community'), icon: UsersIcon,  exact: true },
    ];
    if (authStore.user?.is_admin) {
        items.push({ to: '/admin', label: 'Адмін', icon: ShieldIcon, exact: false });
    }
    return items;
});

function isActive(item) {
    if (item.exact) return route.path === item.to;
    return route.path === item.to || route.path.startsWith(item.to + '/');
}
</script>
