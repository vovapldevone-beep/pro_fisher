<template>
    <!-- The offset follows the scroll 1:1, so no transition while it moves —
         a transition here would lag a finger and feel like rubber. It is only
         switched on for the snap at the end of a gesture. -->
    <header
        ref="rootEl"
        class="fixed inset-x-0 top-0 z-50 bg-[#1a1f2e]/95 backdrop-blur-sm"
        :class="settling ? 'transition-transform duration-200 ease-out' : ''"
        :style="headerStyle"
    >
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
            <router-link to="/" class="flex min-w-0 items-center gap-2">
                <!-- :src (not src) so Vite serves it from public/ instead of trying to bundle it -->
                <img
                    :src="'/images/logo.png'"
                    alt="ProFisher"
                    class="h-9 w-9 shrink-0 rounded-lg object-contain sm:h-10 sm:w-10"
                />
                <span class="truncate text-lg font-bold text-white sm:text-xl">ProFisher</span>
            </router-link>

            <nav class="hidden items-center gap-8 md:flex">
                <router-link
                    v-for="link in navLinks"
                    :key="link.to"
                    :to="link.to"
                    class="text-sm text-white/80 transition hover:text-white"
                    active-class="!text-white border-b-2 border-emerald-400 pb-0.5"
                >
                    {{ link.label }}
                </router-link>
            </nav>

            <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                <!-- Fish-hunt counter with hover/tap popover -->
                <div
                    v-if="authStore.isAuthenticated && fishStore.loaded"
                    class="relative shrink-0"
                    @mouseenter="fishTip = true"
                    @mouseleave="fishTip = false"
                >
                    <button
                        type="button"
                        class="flex items-center gap-1 rounded-full bg-white/10 px-2.5 py-1 text-xs font-semibold text-white"
                        :class="fishStore.completed ? 'ring-1 ring-emerald-400/60' : ''"
                        @click="fishTip = !fishTip"
                    >
                        <span class="text-sm leading-none">🐟</span>
                        <span :class="fishStore.completed ? 'text-emerald-300' : ''">
                            {{ fishStore.found }}/{{ fishStore.total }}
                        </span>
                    </button>

                    <Transition name="fish-tip">
                        <div
                            v-if="fishTip"
                            class="absolute right-0 top-full z-50 mt-2 w-64 rounded-xl bg-white p-3 text-left shadow-xl"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <p class="text-xs leading-relaxed text-slate-600">
                                    {{ fishStore.completed ? t('fish.tooltipComplete') : t('fish.tooltipIncomplete') }}
                                </p>
                                <router-link
                                    to="/raffle"
                                    class="shrink-0 text-xs font-semibold text-emerald-600 hover:underline"
                                    @click="fishTip = false"
                                >
                                    {{ t('fish.details') }}
                                </router-link>
                            </div>
                        </div>
                    </Transition>
                </div>

                <button
                    type="button"
                    class="hidden rounded-lg border border-white/30 px-4 py-2 text-sm font-medium text-white transition hover:bg-white/10 sm:inline-block"
                    @click="openAction('add-post')"
                >
                    + {{ t('common.addPublication') }}
                </button>

                <!-- Language switcher: the current language is the trigger,
                     the others drop down under it -->
                <div ref="langRoot" class="relative shrink-0">
                    <button
                        type="button"
                        class="flex items-center gap-1 rounded-lg border border-white/20 px-2 py-1.5 text-xs font-semibold text-white transition hover:bg-white/10 sm:px-2.5"
                        :aria-expanded="langOpen"
                        aria-haspopup="listbox"
                        :aria-label="`Мова: ${currentLocale.label}`"
                        @click="langOpen = !langOpen"
                    >
                        {{ currentLocale.label }}
                        <AppIcon
                            name="chevron-down"
                            class="h-3 w-3 text-white/60 transition-transform"
                            :class="langOpen ? 'rotate-180' : ''"
                        />
                    </button>

                    <Transition name="fish-tip">
                        <ul
                            v-if="langOpen"
                            class="absolute right-0 top-full z-50 mt-1 min-w-full overflow-hidden rounded-lg border border-white/20 bg-[#1a1f2e] shadow-xl"
                            role="listbox"
                        >
                            <li v-for="option in otherLocales" :key="option.code">
                                <button
                                    type="button"
                                    class="w-full px-2 py-1.5 text-xs font-semibold text-white/70 transition hover:bg-white/10 hover:text-white sm:px-2.5"
                                    role="option"
                                    @click="chooseLocale(option.code)"
                                >
                                    {{ option.label }}
                                </button>
                            </li>
                        </ul>
                    </Transition>
                </div>

                <template v-if="authStore.isAuthenticated">
                    <router-link
                        to="/cabinet"
                        class="hidden text-sm text-white/70 hover:text-white sm:inline"
                    >
                        {{ authStore.user?.name }}
                    </router-link>
                    <button
                        type="button"
                        class="shrink-0 whitespace-nowrap rounded-lg border border-white/30 px-3 py-2 text-sm font-medium text-white transition hover:bg-white/10 sm:px-4"
                        @click="handleLogout"
                    >
                        {{ t('header.logout') }}
                    </button>
                </template>
                <template v-else>
                    <router-link
                        to="/login"
                        class="shrink-0 whitespace-nowrap rounded-lg border border-white/30 px-3 py-2 text-sm font-medium text-white transition hover:bg-white/10 sm:px-4"
                    >
                        {{ t('header.login') }}
                    </router-link>
                </template>
            </div>
        </div>
    </header>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '../../stores/auth';
import { useFishStore } from '../../stores/fish';
import { setLocale } from '../../i18n';
import AppIcon from '../shared/AppIcon.vue';

const props = defineProps({
    // True once the header is all the way up — drives the floating fish badge
    // and closes the language menu; ignored from md up
    hidden: { type: Boolean, default: false },
    // How far up the header currently sits, in px (0 = fully visible)
    offset: { type: Number, default: 0 },
    // Set only while the header snaps to one end after a gesture
    settling: { type: Boolean, default: false },
});

// Handed to CSS as a variable rather than applied here, so the "never hides
// from md up" rule can stay a media query instead of a JS breakpoint listener.
const headerStyle = computed(() => ({ '--header-offset': `${props.offset}px` }));

// App.vue measures the header to know how far it has to travel. It cannot use
// $el: the comment above <header> is a second root node in dev builds (the
// compiler only strips comments in production), which makes $el the comment
// and its offsetHeight undefined — the measurement then silently fell back to
// 65px while the real header is 70, leaving a 5px strip on screen.
const rootEl = ref(null);
defineExpose({ rootEl });

const authStore = useAuthStore();
const fishStore = useFishStore();
const router = useRouter();
const { t, locale } = useI18n();

const fishTip = ref(false);

// ─── Language dropdown ────────────────────────────────────────────────────────

const LOCALES = [
    { code: 'uk', label: 'UA' },
    { code: 'pl', label: 'PL' },
];

const langOpen = ref(false);
const langRoot = ref(null);

const currentLocale = computed(
    () => LOCALES.find(l => l.code === locale.value) ?? LOCALES[0]
);
// Only the alternatives drop down — the current one is already the trigger
const otherLocales = computed(() => LOCALES.filter(l => l.code !== currentLocale.value.code));

function chooseLocale(code) {
    setLocale(code);
    langOpen.value = false;
}

function onDocumentPointerDown(e) {
    if (langOpen.value && langRoot.value && !langRoot.value.contains(e.target)) {
        langOpen.value = false;
    }
}

function onDocumentKeydown(e) {
    if (e.key === 'Escape') langOpen.value = false;
}

onMounted(() => {
    document.addEventListener('pointerdown', onDocumentPointerDown);
    document.addEventListener('keydown', onDocumentKeydown);
});
onUnmounted(() => {
    document.removeEventListener('pointerdown', onDocumentPointerDown);
    document.removeEventListener('keydown', onDocumentKeydown);
});

// The header slides out of view on mobile scroll; a menu left hanging there
// would reopen invisible
watch(() => props.hidden, (isHidden) => {
    if (isHidden) langOpen.value = false;
});

const allNavLinks = computed(() => [
    { to: '/map', label: t('nav.lakeMap') },
]);

const navLinks = computed(() =>
    authStore.isAuthenticated ? [] : allNavLinks.value.slice(0, 1)
);

function openAction(action) {
    if (!authStore.isAuthenticated) {
        router.push({ name: 'login', query: { redirect: `/cabinet?action=${action}` } });
        return;
    }
    router.push({ path: '/cabinet', query: { action } });
}

async function handleLogout() {
    await authStore.logout();
    router.push('/');
}
</script>

<style scoped>
header {
    transform: translate3d(0, calc(-1 * var(--header-offset, 0px)), 0);
}

/* Desktop keeps the header pinned — the sidebar and page layout assume the
   65px band at the top is always there. */
@media (min-width: 768px) {
    header {
        transform: none;
    }
}

.fish-tip-enter-active,
.fish-tip-leave-active {
    transition: opacity 0.15s ease, transform 0.15s ease;
}
.fish-tip-enter-from,
.fish-tip-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
</style>
