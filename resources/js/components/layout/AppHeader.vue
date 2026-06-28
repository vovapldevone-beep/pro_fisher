<template>
    <header class="fixed inset-x-0 top-0 z-50 bg-[#1a1f2e]/95 backdrop-blur-sm">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
            <router-link to="/" class="flex items-center gap-2">
                <svg class="h-8 w-8 text-emerald-400" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C8 6 4 8 4 12c0 3 2 5 4 6 1-2 3-3 5-3s4 1 5 3c2-1 4-3 4-6 0-4-4-6-8-10zm0 14c-1.5 0-3 .5-4 1.5.5-2 2-3.5 4-3.5s3.5 1.5 4 3.5c-1-1-2.5-1.5-4-1.5z"/>
                </svg>
                <span class="text-xl font-bold text-white">FishHub</span>
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

            <div class="flex items-center gap-3">
                <button type="button" class="hidden p-2 text-white/70 hover:text-white sm:block" aria-label="Пошук">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </button>

                <button
                    type="button"
                    class="hidden rounded-lg border border-white/30 px-4 py-2 text-sm font-medium text-white transition hover:bg-white/10 sm:inline-block"
                    @click="openAction('add-catch')"
                >
                    + Улов
                </button>
                <button
                    type="button"
                    class="hidden rounded-lg border border-blue-400/60 px-4 py-2 text-sm font-medium text-blue-300 transition hover:bg-blue-400/10 sm:inline-block"
                    @click="openAction('add-post')"
                >
                    + Пост
                </button>

                <template v-if="authStore.isAuthenticated">
                    <router-link
                        to="/cabinet"
                        class="hidden text-sm text-white/70 hover:text-white sm:inline"
                    >
                        {{ authStore.user?.name }}
                    </router-link>
                    <button
                        type="button"
                        class="rounded-lg border border-white/30 px-4 py-2 text-sm font-medium text-white transition hover:bg-white/10"
                        @click="handleLogout"
                    >
                        Вийти
                    </button>
                </template>
                <template v-else>
                    <router-link
                        to="/login"
                        class="rounded-lg border border-white/30 px-4 py-2 text-sm font-medium text-white transition hover:bg-white/10"
                    >
                        Увійти
                    </router-link>
                </template>
            </div>
        </div>
    </header>
</template>

<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';

const authStore = useAuthStore();
const router = useRouter();

const allNavLinks = [
    { to: '/map', label: 'Карта озер' },
    { to: '/', label: 'Спільнота' },
    { to: '/', label: 'Конкурси' },
    { to: '/', label: 'Магазини' },
    { to: '/', label: 'Блог' },
];

const navLinks = computed(() =>
    authStore.isAuthenticated ? [] : allNavLinks.slice(0, 1)
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
