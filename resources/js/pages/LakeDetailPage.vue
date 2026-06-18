<template>
    <div class="min-h-screen bg-slate-50">
        <!-- Page header -->
        <div class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-7xl items-center gap-4 px-4 py-3 sm:px-6">
                <router-link
                    to="/map"
                    class="flex shrink-0 items-center gap-2 text-sm font-medium text-slate-600 hover:text-emerald-700"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Назад до карти
                </router-link>
                <div class="relative mx-auto hidden max-w-md flex-1 sm:block">
                    <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input
                        type="text"
                        placeholder="Пошук озер..."
                        class="w-full rounded-lg border border-slate-200 py-2 pl-9 pr-3 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                        @keydown.enter="goToMap"
                    />
                </div>
            </div>
        </div>

        <div v-if="loading" class="flex items-center justify-center py-24">
            <p class="text-slate-500">Завантаження...</p>
        </div>

        <div v-else-if="lake" class="mx-auto max-w-7xl px-4 py-6 sm:px-6">
            <!-- Top section -->
            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Gallery -->
                <div>
                    <img
                        :src="mainPhoto"
                        :alt="lake.name"
                        class="h-64 w-full rounded-2xl object-cover lg:h-72"
                    />
                    <div class="mt-3 flex gap-2 overflow-x-auto">
                        <button
                            v-for="(photo, index) in visiblePhotos"
                            :key="photo.id"
                            type="button"
                            class="relative shrink-0 overflow-hidden rounded-lg"
                            @click="activePhotoIndex = index"
                        >
                            <img :src="photo.url" :alt="lake.name" class="h-16 w-24 object-cover" />
                            <div
                                v-if="index === 4 && extraPhotosCount > 0"
                                class="absolute inset-0 flex items-center justify-center bg-black/50 text-sm font-medium text-white"
                            >
                                +{{ extraPhotosCount }}
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Info -->
                <div>
                    <h1 class="text-3xl font-bold text-slate-900">Озеро {{ lake.name }}</h1>
                    <div class="mt-2 flex items-center gap-2">
                        <svg class="h-5 w-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                        <span class="font-semibold text-slate-900">{{ lake.rating }}</span>
                        <span class="text-slate-500">({{ lake.reviews_count }} відгуків)</span>
                    </div>

                    <ul class="mt-6 space-y-3 text-sm text-slate-600">
                        <li v-if="lake.region" class="flex items-start gap-3">
                            <svg class="mt-0.5 h-5 w-5 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            </svg>
                            {{ lake.region }}, Польща
                        </li>
                        <li v-if="lake.permit_required" class="flex items-start gap-3">
                            <svg class="mt-0.5 h-5 w-5 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            Дозвіл потрібен
                        </li>
                        <li v-if="lake.fish_species" class="flex items-start gap-3">
                            <span class="mt-0.5 shrink-0 text-lg">🐟</span>
                            {{ lake.fish_species }}
                        </li>
                        <li v-if="lake.price" class="flex items-start gap-3">
                            <svg class="mt-0.5 h-5 w-5 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                            {{ lake.price }} zł / день
                        </li>
                        <li v-if="lake.area_ha" class="flex items-start gap-3">
                            <svg class="mt-0.5 h-5 w-5 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                            </svg>
                            Площа: {{ lake.area_ha }} га
                        </li>
                        <li v-if="lake.max_depth_m" class="flex items-start gap-3">
                            <svg class="mt-0.5 h-5 w-5 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                            </svg>
                            Макс. глибина: {{ lake.max_depth_m }} м
                        </li>
                    </ul>
                </div>

                <!-- Sidebar widgets -->
                <div class="space-y-4">
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="font-bold text-slate-900">Купити дозвіл онлайн</h3>
                        <div class="mt-4 space-y-2">
                            <label
                                v-for="option in lake.permit_options"
                                :key="option.days"
                                class="flex cursor-pointer items-center justify-between rounded-lg border px-4 py-3 transition"
                                :class="selectedPermit === option.days ? 'border-emerald-500 bg-emerald-50' : 'border-slate-200 hover:border-slate-300'"
                            >
                                <div class="flex items-center gap-3">
                                    <input v-model="selectedPermit" type="radio" :value="option.days" class="text-emerald-600" />
                                    <span class="text-sm font-medium text-slate-700">{{ option.label }}</span>
                                </div>
                                <span class="text-sm font-semibold text-slate-900">{{ option.price }} zł</span>
                            </label>
                        </div>
                        <button type="button" class="mt-4 w-full rounded-lg bg-emerald-600 py-3 font-semibold text-white hover:bg-emerald-700">
                            Купити дозвіл
                        </button>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="font-bold text-slate-900">Контакти</h3>
                        <ul class="mt-4 space-y-3 text-sm text-slate-600">
                            <li v-if="lake.admin_name" class="flex items-center gap-3">
                                <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                {{ lake.admin_name }}
                            </li>
                            <li v-if="lake.admin_phone" class="flex items-center gap-3">
                                <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                {{ lake.admin_phone }}
                            </li>
                            <li v-if="lake.admin_website" class="flex items-center gap-3">
                                <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9" />
                                </svg>
                                <a :href="`https://${lake.admin_website}`" target="_blank" class="text-emerald-700 hover:underline">
                                    {{ lake.admin_website }}
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="mt-8 border-b border-slate-200">
                <nav class="flex gap-6 overflow-x-auto">
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        type="button"
                        class="shrink-0 border-b-2 pb-3 text-sm font-medium transition"
                        :class="activeTab === tab.id ? 'border-emerald-500 text-emerald-700' : 'border-transparent text-slate-500 hover:text-slate-700'"
                        @click="activeTab = tab.id"
                    >
                        {{ tab.label }}
                        <span v-if="tab.badge" class="ml-1 text-slate-400">{{ tab.badge }}</span>
                    </button>
                </nav>
            </div>

            <!-- Tab content -->
            <div class="mt-6">
                <div v-if="activeTab === 'overview'" class="grid gap-6 lg:grid-cols-3">
                    <!-- Catch stats -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="font-bold text-slate-900">Статистика уловів за 30 днів</h3>
                        <div v-if="!lake.catch_stats?.length" class="mt-4 text-sm text-slate-500">Немає даних</div>
                        <div v-else class="mt-4 space-y-3">
                            <div v-for="stat in lake.catch_stats" :key="stat.fish_name">
                                <div class="mb-1 flex justify-between text-sm">
                                    <span class="text-slate-700">{{ stat.fish_name }}</span>
                                    <span class="font-medium text-slate-900">{{ stat.count }}</span>
                                </div>
                                <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                                    <div
                                        class="h-full rounded-full bg-emerald-500"
                                        :style="{ width: `${(stat.count / maxCatchStat) * 100}%` }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent catches -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="font-bold text-slate-900">Останні улови</h3>
                            <button type="button" class="text-sm text-blue-600 hover:underline" @click="activeTab = 'catches'">
                                Переглянути всі
                            </button>
                        </div>
                        <div class="space-y-3">
                            <div
                                v-for="catchItem in lake.recent_catches?.slice(0, 4)"
                                :key="catchItem.id"
                                class="flex items-center gap-3"
                            >
                                <div class="w-24 shrink-0">
                                    <p class="text-sm font-medium text-slate-900">{{ catchItem.user?.name }}</p>
                                    <p class="text-xs text-slate-400">{{ timeAgo(catchItem.created_at) }}</p>
                                </div>
                                <img
                                    v-if="catchItem.photo_url"
                                    :src="catchItem.photo_url"
                                    class="h-12 w-16 shrink-0 rounded-lg object-cover"
                                />
                                <div v-else class="flex h-12 w-16 shrink-0 items-center justify-center rounded-lg bg-slate-100">🐟</div>
                                <div class="min-w-0 flex-1">
                                    <p class="font-semibold text-slate-900">{{ catchItem.fish_name }}</p>
                                    <p v-if="catchItem.weight" class="text-sm text-slate-500">{{ catchItem.weight }} кг</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Reviews -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="font-bold text-slate-900">Відгуки</h3>
                            <button type="button" class="text-sm text-blue-600 hover:underline" @click="activeTab = 'reviews'">
                                Переглянути всі
                            </button>
                        </div>
                        <div class="space-y-4">
                            <div v-for="review in lake.reviews?.slice(0, 3)" :key="review.id">
                                <div class="flex items-center gap-2">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 text-xs font-semibold text-emerald-700">
                                        {{ review.author_name.slice(0, 2).toUpperCase() }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-slate-900">{{ review.author_name }}</p>
                                        <p class="text-xs text-slate-400">{{ formatDate(review.created_at) }}</p>
                                    </div>
                                </div>
                                <div class="mt-1 flex gap-0.5">
                                    <svg
                                        v-for="star in 5"
                                        :key="star"
                                        class="h-4 w-4"
                                        :class="star <= review.rating ? 'text-amber-400' : 'text-slate-200'"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                </div>
                                <p class="mt-2 text-sm text-slate-600">{{ review.comment }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else-if="activeTab === 'catches'" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="mb-4 font-bold text-slate-900">Усі улови</h3>
                    <div class="space-y-3">
                        <div
                            v-for="catchItem in lake.recent_catches"
                            :key="catchItem.id"
                            class="flex items-center gap-4 rounded-xl border border-slate-100 p-4"
                        >
                            <div class="w-28">
                                <p class="font-medium text-slate-900">{{ catchItem.user?.name }}</p>
                                <p class="text-xs text-slate-400">{{ timeAgo(catchItem.created_at) }}</p>
                            </div>
                            <img v-if="catchItem.photo_url" :src="catchItem.photo_url" class="h-14 w-20 rounded-lg object-cover" />
                            <div v-else class="flex h-14 w-20 items-center justify-center rounded-lg bg-slate-100 text-xl">🐟</div>
                            <div>
                                <p class="font-semibold text-slate-900">{{ catchItem.fish_name }}</p>
                                <p v-if="catchItem.weight" class="text-sm text-slate-500">{{ catchItem.weight }} кг</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else-if="activeTab === 'reviews'" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="mb-4 font-bold text-slate-900">Відгуки ({{ lake.reviews_count }})</h3>
                    <div class="space-y-6">
                        <div v-for="review in lake.reviews" :key="review.id" class="border-b border-slate-100 pb-4 last:border-0">
                            <div class="flex items-center gap-2">
                                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-100 text-xs font-semibold text-emerald-700">
                                    {{ review.author_name.slice(0, 2).toUpperCase() }}
                                </div>
                                <div>
                                    <p class="font-medium text-slate-900">{{ review.author_name }}</p>
                                    <p class="text-xs text-slate-400">{{ formatDate(review.created_at) }}</p>
                                </div>
                            </div>
                            <div class="mt-2 flex gap-0.5">
                                <svg
                                    v-for="star in 5"
                                    :key="star"
                                    class="h-4 w-4"
                                    :class="star <= review.rating ? 'text-amber-400' : 'text-slate-200'"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                            </div>
                            <p class="mt-2 text-slate-600">{{ review.comment }}</p>
                        </div>
                    </div>
                </div>

                <div v-else-if="activeTab === 'rules'" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="mb-4 font-bold text-slate-900">Правила риболовлі</h3>
                    <p class="whitespace-pre-line text-slate-600">{{ lake.rules || 'Правила не вказані.' }}</p>
                </div>

                <div v-else-if="activeTab === 'directions'" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="mb-4 font-bold text-slate-900">Як дістатися</h3>
                    <p v-if="lake.address" class="text-slate-600">{{ lake.address }}</p>
                    <p v-if="lake.region" class="mt-2 text-slate-600">{{ lake.region }}, Польща</p>
                    <a
                        :href="`https://www.google.com/maps?q=${lake.latitude},${lake.longitude}`"
                        target="_blank"
                        class="mt-4 inline-flex items-center gap-2 text-emerald-700 hover:underline"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        </svg>
                        Відкрити в Google Maps
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { fetchLake } from '../api/lakes';

const route = useRoute();
const router = useRouter();

const lake = ref(null);
const loading = ref(true);
const activeTab = ref('overview');
const activePhotoIndex = ref(0);
const selectedPermit = ref(1);

const tabs = computed(() => [
    { id: 'overview', label: 'Огляд' },
    { id: 'catches', label: 'Улови' },
    { id: 'reviews', label: 'Відгуки', badge: lake.value?.reviews_count },
    { id: 'rules', label: 'Правила' },
    { id: 'directions', label: 'Як дістатися' },
]);

const mainPhoto = computed(() => {
    if (!lake.value?.photos?.length) return 'https://images.unsplash.com/photo-1439066615861-d1af74d74000?w=800';
    return lake.value.photos[activePhotoIndex.value]?.url || lake.value.photos[0].url;
});

const visiblePhotos = computed(() => lake.value?.photos?.slice(0, 5) || []);
const extraPhotosCount = computed(() => Math.max(0, (lake.value?.photos?.length || 0) - 5));

const maxCatchStat = computed(() => {
    if (!lake.value?.catch_stats?.length) return 1;
    return Math.max(...lake.value.catch_stats.map((s) => s.count));
});

function timeAgo(dateStr) {
    const date = new Date(dateStr);
    const diff = Date.now() - date.getTime();
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);
    if (hours < 1) return 'щойно';
    if (hours < 24) return `${hours} год. тому`;
    return `${days} дн. тому`;
}

function formatDate(dateStr) {
    return new Date(dateStr).toLocaleDateString('uk-UA');
}

function goToMap() {
    router.push('/map');
}

onMounted(async () => {
    try {
        lake.value = await fetchLake(route.params.slug);
    } finally {
        loading.value = false;
    }
});
</script>
