<template>
    <div class="min-h-screen bg-slate-50 py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8 flex items-center gap-4">
                <button
                    type="button"
                    class="flex h-9 w-9 items-center justify-center rounded-full bg-white shadow-sm hover:bg-slate-100"
                    @click="$router.back()"
                >
                    <AppIcon name="chevron-left" class="h-5 w-5 text-slate-600" />
                </button>
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">{{ t('cabinet.achievements') }}</h1>
                    <p v-if="!loading" class="mt-0.5 text-sm text-slate-500">
                        {{ t('cabinet.earnedCount', { earned: earnedCount, total: totalCount }) }}
                    </p>
                </div>
                <div
                    v-if="!loading"
                    class="ml-auto flex h-14 w-14 flex-col items-center justify-center rounded-full bg-emerald-100"
                >
                    <span class="text-lg font-bold text-emerald-700">{{ earnedCount }}</span>
                    <span class="text-[10px] text-emerald-600">{{ t('cabinet.earnedOf', { total: totalCount }) }}</span>
                </div>
            </div>

            <!-- Loading -->
            <div v-if="loading" class="py-24 text-center text-slate-400">{{ t('common.loading') }}</div>

            <template v-else>
                <!-- Earned -->
                <section v-if="earned.length" class="mb-8">
                    <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-emerald-600">
                        {{ t('cabinet.earnedSection', { count: earned.length }) }}
                    </h2>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <AchievementCard
                            v-for="a in earned"
                            :key="a.id"
                            :achievement="a"
                        />
                    </div>
                </section>

                <!-- Not earned -->
                <section v-if="notEarned.length">
                    <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-400">
                        {{ t('cabinet.inProgressSection', { count: notEarned.length }) }}
                    </h2>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <AchievementCard
                            v-for="a in notEarned"
                            :key="a.id"
                            :achievement="a"
                        />
                    </div>
                </section>
            </template>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { fetchAchievements } from '../api/cabinet';
import AchievementCard from '../components/cabinet/AchievementCard.vue';
import { useFishStore } from '../stores/fish';
import AppIcon from '../components/shared/AppIcon.vue';

const { t } = useI18n();
const fishStore = useFishStore();

const loading = ref(true);
const apiAchievements = ref([]);

// The fish hunt is stateful (stored in user_achievements) rather than computed
// from stats, so it's merged in from the store instead of the achievements API.
const fishAchievement = computed(() => ({
    id: 'fish_hunt',
    icon: 'fish',
    title: 'Рибалка-шукач',
    description: 'Знайди 10 захованих рибок',
    progress: fishStore.found,
    max: fishStore.total,
    earned: fishStore.loaded && fishStore.found >= fishStore.total,
}));

const achievements = computed(() => [...apiAchievements.value, fishAchievement.value]);
const earned = computed(() => achievements.value.filter((a) => a.earned));
const notEarned = computed(() => achievements.value.filter((a) => !a.earned));
const earnedCount = computed(() => earned.value.length);
const totalCount = computed(() => achievements.value.length);

onMounted(async () => {
    try {
        const data = await fetchAchievements();
        apiAchievements.value = data.achievements;
        if (!fishStore.loaded) await fishStore.fetchProgress();
    } finally {
        loading.value = false;
    }
});
</script>
