<template>
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="font-bold text-slate-900">{{ t('cabinet.achievements') }}</h3>
            <router-link to="/cabinet/achievements" class="text-sm text-blue-600 hover:underline">{{ t('common.viewAll') }}</router-link>
        </div>

        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
            <div
                v-for="achievement in achievements"
                :key="achievement.id"
                class="flex flex-col items-center text-center"
            >
                <div
                    class="flex h-16 w-16 items-center justify-center rounded-full text-2xl"
                    :class="achievement.earned ? 'bg-emerald-50' : 'bg-slate-100 grayscale opacity-50'"
                >
                    {{ iconEmoji(achievement.icon) }}
                </div>
                <p class="mt-2 text-xs font-medium text-slate-700">{{ achievementTitle(achievement) }}</p>
                <p
                    class="mt-0.5 text-xs"
                    :class="achievement.earned ? 'text-emerald-600' : 'text-slate-400'"
                >
                    {{ achievement.earned ? t('cabinet.earned') : t('cabinet.notEarned') }}
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n';

const { t, te } = useI18n();

defineProps({
    achievements: {
        type: Array,
        default: () => [],
    },
});

function achievementTitle(achievement) {
    const key = `achievements.${achievement.id}.title`;
    return te(key) ? t(key) : achievement.title;
}

const icons = { star: '⭐', fish: '🐟', lake: '🏞️', camera: '📷', moon: '🌙' };

function iconEmoji(icon) {
    return icons[icon] || '🏅';
}
</script>
