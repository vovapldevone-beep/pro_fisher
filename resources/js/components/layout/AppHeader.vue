<template>
    <header
        class="fixed inset-x-0 top-0 z-50 bg-[#1a1f2e]/95 backdrop-blur-sm transition-transform duration-300 ease-out"
        :class="hidden ? '-translate-y-full md:translate-y-0' : ''"
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

                <button type="button" class="hidden p-2 text-white/70 hover:text-white sm:block" aria-label="Пошук">
                    <AppIcon name="search" class="h-5 w-5" />
                </button>

                <button
                    type="button"
                    class="hidden rounded-lg border border-white/30 px-4 py-2 text-sm font-medium text-white transition hover:bg-white/10 sm:inline-block"
                    @click="openAction('add-post')"
                >
                    + {{ t('common.addPublication') }}
                </button>

                <!-- Language switcher -->
                <div class="flex shrink-0 items-center overflow-hidden rounded-lg border border-white/20 text-xs font-semibold">
                    <button
                        type="button"
                        class="px-2 py-1.5 transition sm:px-2.5"
                        :class="locale === 'uk' ? 'bg-white/20 text-white' : 'text-white/50 hover:text-white'"
                        @click="setLocale('uk')"
                    >
                        UA
                    </button>
                    <span class="text-white/20">|</span>
                    <button
                        type="button"
                        class="px-2 py-1.5 transition sm:px-2.5"
                        :class="locale === 'pl' ? 'bg-white/20 text-white' : 'text-white/50 hover:text-white'"
                        @click="setLocale('pl')"
                    >
                        PL
                    </button>
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
import { computed, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '../../stores/auth';
import { useFishStore } from '../../stores/fish';
import { setLocale } from '../../i18n';
import AppIcon from '../shared/AppIcon.vue';

defineProps({
    // Slides the header out of view on mobile; ignored from md up
    hidden: { type: Boolean, default: false },
});

const authStore = useAuthStore();
const fishStore = useFishStore();
const router = useRouter();
const { t, locale } = useI18n();

const fishTip = ref(false);

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
