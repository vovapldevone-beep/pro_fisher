<template>
    <div>
        <!-- Hero -->
        <section class="relative -mt-[65px] bg-[#1a1f2e] pt-[65px]">
            <div
                class="absolute inset-0 bg-cover bg-center"
                style="background-image: url('/images/bg.png')"
            ></div>
            <div class="absolute inset-0 bg-gradient-to-r from-[#1a1f2e]/90 via-[#1a1f2e]/70 to-[#1a1f2e]/40"></div>

            <div class="relative mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8 lg:py-28">
                <h1 class="max-w-2xl text-4xl font-bold leading-tight text-white sm:text-5xl lg:text-[3.25rem]">
                    Знайди найкращі місця для риболовлі
                </h1>
                <p class="mt-4 max-w-xl text-lg text-white/80">
                    Карта озер, актуальні улови, спільнота рибалок та все необхідне в одному місці
                </p>

                <form class="mt-8 flex max-w-2xl overflow-hidden rounded-xl bg-white shadow-2xl" @submit.prevent="handleSearch">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Пошук озера, риби або локації..."
                        class="flex-1 px-5 py-4 text-slate-700 placeholder-slate-400 focus:outline-none"
                    />
                    <button
                        type="submit"
                        class="bg-emerald-500 px-8 py-4 font-semibold text-white transition hover:bg-emerald-600"
                    >
                        Пошук
                    </button>
                </form>

                <div class="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4">
                    <div
                        v-for="stat in statsItems"
                        :key="stat.label"
                        class="flex items-center gap-3 rounded-xl bg-white/10 px-4 py-3 backdrop-blur-sm"
                    >
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white/10 text-emerald-400">
                            <component :is="stat.icon" class="h-5 w-5" />
                        </div>
                        <p class="text-sm font-medium text-white">{{ stat.value }} {{ stat.label }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Content -->
        <section class="bg-slate-100 py-12">
            <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-2 lg:px-8">
                <!-- Popular lakes -->
                <div class="rounded-2xl bg-white p-6 shadow-sm">
                    <div class="mb-4 flex items-center justify-between">
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
                <div class="rounded-2xl bg-white p-6 shadow-sm">
                    <div class="mb-4 flex items-center justify-between">
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
import { computed, h, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { fetchHomeStats, fetchPopularLakes, fetchRecentCatches } from '../api/home';
import PopularLakeItem from '../components/home/PopularLakeItem.vue';
import RecentCatchItem from '../components/home/RecentCatchItem.vue';
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

const LakeIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7' }),
]);

const FishIcon = () => h('svg', { fill: 'currentColor', viewBox: '0 0 24 24' }, [
    h('path', { d: 'M12 2C8 6 4 8 4 12c0 3 2 5 4 6 1-2 3-3 5-3s4 1 5 3c2-1 4-3 4-6 0-4-4-6-8-10z' }),
]);

const UserIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' }),
]);

const TrophyIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M5 3h14M9 3v2a3 3 0 003 3h0a3 3 0 003-3V3M5 3v2a5 5 0 005 5h4a5 5 0 005-5V3M7 10v1a5 5 0 005 5h0a5 5 0 005-5v-1M9 21h6' }),
]);

const statsItems = computed(() => [
    { icon: LakeIcon, value: formatNumber(stats.value.lakes_count), label: 'озер у базі' },
    { icon: FishIcon, value: formatNumber(stats.value.catches_count), label: 'уловів додано' },
    { icon: UserIcon, value: formatNumber(stats.value.users_count), label: 'рибалок з нами' },
    { icon: TrophyIcon, value: formatNumber(stats.value.contests_count), label: 'активні конкурси' },
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
