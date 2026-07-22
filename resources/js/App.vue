<template>
    <div
        class="flex flex-col overflow-hidden overscroll-none bg-slate-50 text-slate-900"
        style="height: 100vh; height: 100dvh"
    >
        <!-- Fixed header (still position:fixed, works fine) -->
        <AppHeader :hidden="headerHidden" />

        <!-- Floating fish counter: takes over on the Posts page when the mobile
             header has slid away, so the tally stays visible while hunting. -->
        <Transition name="fish-badge">
            <div
                v-if="showFloatingFish"
                class="fixed right-2 top-2 z-50 flex items-center gap-1 rounded-full bg-[#1a1f2e]/95 px-2.5 py-1 text-xs font-semibold text-white shadow-lg backdrop-blur-sm md:hidden"
                :class="fishStore.completed ? 'ring-1 ring-emerald-400/60' : ''"
            >
                <span class="text-sm leading-none">🐟</span>
                <span :class="fishStore.completed ? 'text-emerald-300' : ''">
                    {{ fishStore.found }}/{{ fishStore.total }}
                </span>
            </div>
        </Transition>

        <!-- Content row: below the fixed header. On mobile the top padding
             collapses in step with the header sliding away. -->
        <div
            class="flex min-h-0 flex-1 transition-[padding-top] duration-300 ease-out md:pt-[65px]"
            :class="headerHidden ? 'pt-0' : 'pt-[65px]'"
        >
            <AppSidebar v-if="authStore.isAuthenticated" />
            <!-- overscroll-y-none kills the iOS rubber-band: bouncing past the edge
                 reports scrollTop outside its bounds and makes the header flap. -->
            <main
                ref="mainEl"
                class="min-h-0 flex-1 overflow-y-auto overscroll-y-none"
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

        <!-- Full-screen fireworks when the last hidden fish is caught -->
        <FireworksOverlay :show="showFireworks" @done="showFireworks = false" />

        <!-- Raffle promo: shown once per session right after signing in -->
        <RafflePromoModal :show="showRafflePromo" @close="showRafflePromo = false" />
    </div>
</template>

<script setup>
import { computed, h, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import FireworksOverlay from './components/fish/FireworksOverlay.vue';
import RafflePromoModal from './components/fish/RafflePromoModal.vue';
import AppHeader from './components/layout/AppHeader.vue';
import AppSidebar from './components/layout/AppSidebar.vue';
import { useHideOnScroll } from './composables/useHideOnScroll';
import { useAuthStore } from './stores/auth';
import { useFishStore } from './stores/fish';

const authStore = useAuthStore();
const fishStore = useFishStore();
const route = useRoute();
const { t } = useI18n();

const mainEl = ref(null);
const { hidden: headerHidden, show: showHeader } = useHideOnScroll(mainEl);

// A new page starts at the top, so the header belongs on screen.
watch(() => route.fullPath, showHeader);

// Load fish-hunt progress on login, drop it on logout
watch(() => authStore.isAuthenticated, async (signedIn) => {
    if (signedIn) {
        await fishStore.fetchProgress();
        maybeShowRafflePromo();
    } else {
        fishStore.reset();
        // A fresh login in this tab should see the promo again
        sessionStorage.removeItem('raffle_promo_seen');
    }
}, { immediate: true });

// Raffle promo modal: greets the user once per browser session after signing in.
// sessionStorage (not localStorage) so a new visit shows it again, while mere
// reloads inside the same session don't nag. Skipped for users who already
// completed the hunt — there is nothing left to promote to them.
const showRafflePromo = ref(false);

function maybeShowRafflePromo() {
    if (fishStore.completed) return;
    if (sessionStorage.getItem('raffle_promo_seen')) return;
    sessionStorage.setItem('raffle_promo_seen', '1');
    showRafflePromo.value = true;
}

// Floating fish tally — only on the Posts page, only once the mobile header hides
const showFloatingFish = computed(() =>
    headerHidden.value
    && route.name === 'posts'
    && authStore.isAuthenticated
    && fishStore.loaded
);

// Fireworks fire the moment the final fish is caught (not on reload of a done tally)
const showFireworks = ref(false);
watch(() => fishStore.justCompleted, (done) => {
    if (done) {
        showFireworks.value = true;
        fishStore.acknowledgeCompletion();
    }
});

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
        // Стрічка прихована разом зі "Спільнотою" в AppSidebar — "/" веде на Пости
        // { to: '/',        shortLabel: t('nav.communityShort'), icon: UsersIcon,  exact: true },
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

<style scoped>
.fish-badge-enter-active,
.fish-badge-leave-active {
    transition: opacity 0.25s ease, transform 0.25s ease;
}
.fish-badge-enter-from,
.fish-badge-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}
</style>
