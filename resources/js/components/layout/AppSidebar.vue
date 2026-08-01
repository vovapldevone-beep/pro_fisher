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
                    <AppIcon :name="item.icon" class="h-6 w-6 shrink-0" />
                    <span class="whitespace-nowrap text-sm font-medium opacity-0 transition-opacity duration-150 delay-75 group-hover:opacity-100">
                        {{ item.label }}
                    </span>
                </router-link>
            </template>

            <button
                type="button"
                class="flex cursor-not-allowed items-center gap-4 pl-5 pr-4 py-3 text-white/30"
            >
                <AppIcon name="gear" class="h-6 w-6 shrink-0" />
                <span class="whitespace-nowrap text-sm font-medium opacity-0 transition-opacity duration-150 delay-75 group-hover:opacity-100">
                    {{ t('nav.settings') }}
                </span>
            </button>
        </div>
    </nav>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import AppIcon from '../shared/AppIcon.vue';
import { useAuthStore } from '../../stores/auth';

const authStore = useAuthStore();
const route = useRoute();
const { t } = useI18n();

const navItems = computed(() => {
    const items = [
        { to: '/cabinet', label: t('nav.myFishing'), icon: 'fish',   exact: false },
        { to: '/map',     label: t('nav.lakeMap'),   icon: 'map-pin', exact: true },
        { to: '/posts',   label: t('nav.posts'),     icon: 'search', exact: true },
        // Спільнота прихована: після логіну "/" редіректить на Пости
        // { to: '/',        label: t('nav.community'), icon: 'users',  exact: true },
    ];
    if (authStore.user?.is_admin) {
        items.push({ to: '/admin', label: 'Адмін', icon: 'shield', exact: false });
    }
    return items;
});

function isActive(item) {
    if (item.exact) return route.path === item.to;
    return route.path === item.to || route.path.startsWith(item.to + '/');
}
</script>
