<template>
    <div>
        <!-- Hero. The negative offset lets it run under the fixed header, but only
             from md up: on mobile the header slides away on scroll and the content
             row's top padding collapses with it. -->
        <section class="relative overflow-hidden bg-[#1a1f2e] md:-mt-[65px] md:pt-[65px]">
            <div
                class="absolute inset-0 bg-cover bg-center"
                style="background-image: url('/images/bg.png')"
            ></div>
            <div class="absolute inset-0 bg-gradient-to-b from-[#1a1f2e]/80 to-[#1a1f2e]/60 sm:bg-gradient-to-r sm:from-[#1a1f2e]/90 sm:via-[#1a1f2e]/70 sm:to-[#1a1f2e]/40"></div>

            <div class="relative mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-20 lg:px-8 lg:py-28">
                <h1 class="max-w-2xl text-3xl font-bold leading-tight text-white sm:text-5xl lg:text-[3.25rem]">
                    Знайди найкращі місця для риболовлі
                </h1>
                <p class="mt-3 max-w-xl text-base text-white/80 sm:mt-4 sm:text-lg">
                    Карта озер, актуальні улови, спільнота рибалок та все необхідне в одному місці
                </p>

                <!-- Stacks on mobile: an input and a button cannot share 320px -->
                <form
                    class="mt-6 flex max-w-2xl flex-col gap-2 sm:mt-8 sm:flex-row sm:gap-0 sm:overflow-hidden sm:rounded-xl sm:bg-white sm:shadow-2xl"
                    @submit.prevent="handleSearch"
                >
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Пошук озера, риби або локації..."
                        class="w-full min-w-0 rounded-xl px-4 py-3 text-slate-700 placeholder-slate-400 shadow-2xl focus:outline-none sm:flex-1 sm:rounded-none sm:px-5 sm:py-4 sm:shadow-none"
                    />
                    <button
                        type="submit"
                        class="shrink-0 rounded-xl bg-emerald-500 px-6 py-3 font-semibold text-white transition hover:bg-emerald-600 sm:rounded-none sm:px-8 sm:py-4"
                    >
                        Пошук
                    </button>
                </form>

                <div class="mt-6 grid grid-cols-2 gap-2 sm:mt-8 sm:grid-cols-4 sm:gap-4">
                    <div
                        v-for="stat in statsItems"
                        :key="stat.label"
                        class="flex min-w-0 items-center gap-2 rounded-xl bg-white/10 px-3 py-2.5 backdrop-blur-sm sm:gap-3 sm:px-4 sm:py-3"
                    >
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white/10 text-emerald-400 sm:h-10 sm:w-10">
                            <AppIcon :name="stat.icon" class="h-4 w-4 sm:h-5 sm:w-5" />
                        </div>
                        <p class="min-w-0 text-xs font-medium leading-tight text-white sm:text-sm">
                            <span class="font-bold">{{ stat.value }}</span>
                            <span class="block sm:inline"> {{ stat.label }}</span>
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Content -->
        <section class="bg-slate-100 py-8 sm:py-12">
            <!-- Raffle: guests only. Signed-in users are redirected off this
                 page anyway, and for them the card lives on /raffle with their
                 own progress bar. -->
            <div
                v-if="!authStore.isAuthenticated"
                class="mx-auto mb-4 max-w-3xl px-4 sm:mb-6 sm:px-6 lg:px-8"
            >
                <RaffleCard :show-progress="false" signup-step />
            </div>

            <div class="mx-auto grid max-w-7xl gap-4 px-4 sm:gap-6 sm:px-6 lg:grid-cols-2 lg:px-8">
                <!-- Popular lakes -->
                <div class="min-w-0 rounded-2xl bg-white p-4 shadow-sm sm:p-6">
                    <div class="mb-4 flex items-center justify-between gap-2">
                        <h2 class="text-xl font-bold text-slate-900">Популярні водойми</h2>
                        <router-link to="/map" class="text-sm font-medium text-blue-600 hover:underline">
                            Переглянути всі
                        </router-link>
                    </div>
                    <div v-if="loading" class="py-8 text-center text-slate-500">Завантаження...</div>
                    <div v-else class="divide-y divide-slate-100">
                        <PopularLakeItem
                            v-for="lake in popularLakes"
                            :key="lake.id"
                            :lake="lake"
                        />
                    </div>
                </div>

                <!-- Recent catches -->
                <div class="min-w-0 rounded-2xl bg-white p-4 shadow-sm sm:p-6">
                    <div class="mb-4 flex items-center justify-between gap-2">
                        <h2 class="text-xl font-bold text-slate-900">Останні улови</h2>
                        <router-link
                            :to="authStore.isAuthenticated ? '/cabinet' : '/login'"
                            class="text-sm font-medium text-blue-600 hover:underline"
                        >
                            Переглянути всі
                        </router-link>
                    </div>
                    <div v-if="loading" class="py-8 text-center text-slate-500">Завантаження...</div>
                    <div v-else class="divide-y divide-slate-100">
                        <RecentCatchItem
                            v-for="catchItem in recentCatches"
                            :key="catchItem.id"
                            :catch-item="catchItem"
                        />
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { fetchHomeStats, fetchPopularLakes, fetchRecentCatches } from '../api/home';
import RaffleCard from '../components/fish/RaffleCard.vue';
import PopularLakeItem from '../components/home/PopularLakeItem.vue';
import RecentCatchItem from '../components/home/RecentCatchItem.vue';
import AppIcon from '../components/shared/AppIcon.vue';
import { useAuthStore } from '../stores/auth';

const router = useRouter();
const authStore = useAuthStore();

const searchQuery = ref('');
const loading = ref(true);
const stats = ref({ lakes_count: 0, catches_count: 0, users_count: 0, contests_count: 0 });
const popularLakes = ref([]);
const recentCatches = ref([]);

function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
}

const statsItems = computed(() => [
    { icon: 'lake', value: formatNumber(stats.value.lakes_count), label: 'озер у базі' },
    { icon: 'fish', value: formatNumber(stats.value.catches_count), label: 'уловів додано' },
    { icon: 'user', value: formatNumber(stats.value.users_count), label: 'рибалок з нами' },
    { icon: 'trophy', value: formatNumber(stats.value.contests_count), label: 'активні конкурси' },
]);

function handleSearch() {
    router.push({ path: '/map', query: searchQuery.value ? { q: searchQuery.value } : {} });
}

onMounted(async () => {
    try {
        const [statsData, lakes, catches] = await Promise.all([
            fetchHomeStats(),
            fetchPopularLakes(),
            fetchRecentCatches(),
        ]);
        stats.value = statsData;
        popularLakes.value = lakes;
        recentCatches.value = catches;
    } finally {
        loading.value = false;
    }
});
</script>
