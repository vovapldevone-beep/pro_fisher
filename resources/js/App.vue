<template>
    <div
        class="flex flex-col overflow-hidden overscroll-none bg-slate-50 text-slate-900"
        style="height: 100vh; height: 100dvh"
    >
        <!-- Fixed header (still position:fixed, works fine) -->
        <AppHeader ref="headerRef" :hidden="headerHidden" :offset="headerOffset" :settling="headerSettling" />

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

        <!-- Content row. The header's space is padding *inside* <main>, not on
             this row: that way sliding the header away changes no element's
             height, so the content never jumps and scrollHeight stays stable
             while the header follows the scroll. -->
        <div class="flex min-h-0 flex-1">
            <AppSidebar v-if="authStore.isAuthenticated" />
            <!-- overscroll-y-none kills the iOS rubber-band: bouncing past the edge
                 reports scrollTop outside its bounds and makes the header flap. -->
            <main
                ref="mainEl"
                class="min-h-0 flex-1 overflow-y-auto overscroll-y-none pt-[65px]"
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
            <template v-for="item in mobileNavItems" :key="item.to">
                <router-link
                    :to="item.to"
                    class="flex flex-1 flex-col items-center justify-center gap-0.5 py-2.5 transition-colors duration-150"
                    :class="isMobileActive(item) ? 'text-emerald-400' : 'text-white/50 hover:text-white/80'"
                >
                    <AppIcon :name="item.icon" class="h-5 w-5 shrink-0" />
                    <span class="text-[10px] font-medium leading-tight">{{ item.shortLabel }}</span>
                </router-link>

                <!-- Prominent "add publication" FAB, raised above the bar right
                     after the map tab. The thick menu-coloured border makes it
                     read as cut into the bar as it protrudes upward. -->
                <div v-if="item.to === '/map'" class="flex flex-1 items-center justify-center">
                    <button
                        type="button"
                        :aria-label="t('common.addPublication')"
                        class="flex h-14 w-14 items-center justify-center rounded-full border-2 border-[#1a1f2e] text-white/60 shadow-lg transition hover:text-white/90 active:scale-95"
                        style="background-color: oklch(0.24 0.03 269.9)"
                        @click="openAddPublication"
                    >
                        <AppIcon name="plus" class="h-7 w-7" />
                    </button>
                </div>
            </template>
        </nav>

        <!-- Full-screen fireworks when the last hidden fish is caught -->
        <FireworksOverlay :show="showFireworks" @done="showFireworks = false" />

        <!-- Raffle promo: shown once per session right after signing in -->
        <RafflePromoModal :show="showRafflePromo" @close="showRafflePromo = false" />
    </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import FireworksOverlay from './components/fish/FireworksOverlay.vue';
import RafflePromoModal from './components/fish/RafflePromoModal.vue';
import AppHeader from './components/layout/AppHeader.vue';
import AppSidebar from './components/layout/AppSidebar.vue';
import AppIcon from './components/shared/AppIcon.vue';
import { useHideOnScroll } from './composables/useHideOnScroll';
import { useAuthStore } from './stores/auth';
import { useFishStore } from './stores/fish';

const authStore = useAuthStore();
const fishStore = useFishStore();
const route = useRoute();
const router = useRouter();
const { t } = useI18n();

// FAB: opens the tabbed add-publication modal. CabinetPage watches
// ?action=add-post and opens AddPostModal (with the Пост|Улов tabs).
function openAddPublication() {
    router.push({ path: '/cabinet', query: { action: 'add-post' } });
}

const mainEl = ref(null);
const headerRef = ref(null);

// How far the header has to travel to clear the screen. Measured, not the 65px
// the layout reserves for it: the mobile header is actually 70px tall, and the
// difference used to be left hanging at the top edge.
const headerHeight = ref(65);

// AppHeader exposes the element itself — $el is unreliable there (see the note
// in AppHeader.vue), and a wrong measurement leaves part of the header on screen.
function headerEl() {
    return headerRef.value?.rootEl ?? null;
}

function measureHeader() {
    const h = headerEl()?.offsetHeight;
    if (h) headerHeight.value = h;
}

const {
    hidden: headerHidden,
    offset: headerOffset,
    settling: headerSettling,
    show: showHeader,
} = useHideOnScroll(mainEl, { height: headerHeight });

// Watched rather than measured once: the header grows and shrinks after mount
// (the fish counter appears on login, the name row wraps on a narrow screen),
// and a stale height is exactly what leaves a strip behind.
let headerObserver = null;

onMounted(() => {
    measureHeader();
    const el = headerEl();
    if (el && window.ResizeObserver) {
        headerObserver = new ResizeObserver(measureHeader);
        headerObserver.observe(el);
    } else {
        window.addEventListener('resize', measureHeader);
    }
});
onUnmounted(() => {
    headerObserver?.disconnect();
    window.removeEventListener('resize', measureHeader);
});

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

const mobileNavItems = computed(() => {
    const items = [
        { to: '/cabinet', shortLabel: t('nav.myFishingShort'), icon: 'fish',    exact: false },
        { to: '/map',     shortLabel: t('nav.lakeMapShort'),   icon: 'map-pin', exact: true },
        { to: '/posts',   shortLabel: t('nav.postsShort'),     icon: 'search',  exact: true },
        // Стрічка прихована разом зі "Спільнотою" в AppSidebar — "/" веде на Пости
        // { to: '/',        shortLabel: t('nav.communityShort'), icon: 'users',   exact: true },
    ];
    if (authStore.user?.is_admin) {
        items.push({ to: '/admin', shortLabel: 'Адмін', icon: 'shield', exact: false });
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
