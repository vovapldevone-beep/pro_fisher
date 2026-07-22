<template>
    <div
        class="rounded-2xl border bg-white p-5 shadow-sm transition-opacity"
        :class="achievement.earned ? 'border-emerald-100' : 'border-slate-200 opacity-70'"
    >
        <div class="flex items-start gap-4">
            <!-- Icon -->
            <div
                class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-full text-2xl"
                :class="achievement.earned ? 'bg-emerald-50' : 'bg-slate-100 grayscale'"
            >
                {{ iconEmoji(achievement.icon) }}
            </div>

            <!-- Text -->
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                    <p class="font-semibold text-slate-900">{{ achievementTitle }}</p>
                    <span
                        v-if="achievement.earned"
                        class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700"
                    >
                        {{ t('cabinet.earnedBadge') }}
                    </span>
                </div>
                <p class="mt-0.5 text-xs text-slate-500">{{ achievementDescription }}</p>
            </div>
        </div>

        <!-- Progress bar -->
        <div class="mt-4">
            <div class="mb-1 flex justify-between text-xs text-slate-500">
                <span>{{ achievement.progress }} / {{ achievement.max }}</span>
                <span>{{ progressPercent }}%</span>
            </div>
            <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100">
                <div
                    class="h-full rounded-full transition-all duration-500"
                    :class="achievement.earned ? 'bg-emerald-500' : 'bg-blue-400'"
                    :style="{ width: progressPercent + '%' }"
                />
            </div>
        </div>

        <router-link
            v-if="achievement.id === 'fish_hunt'"
            to="/raffle"
            class="mt-3 inline-block text-xs font-semibold text-emerald-600 hover:underline"
        >
            {{ t('fish.details') }}
        </router-link>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t, te } = useI18n();

const props = defineProps({
    achievement: {
        type: Object,
        required: true,
    },
});

const icons = {
    star: '⭐',
    fish: '🐟',
    lake: '🏞️',
    camera: '📷',
    moon: '🌙',
    trophy: '🏆',
    map: '🗺️',
    people: '👥',
};

function iconEmoji(icon) {
    return icons[icon] || '🏅';
}

const achievementTitle = computed(() => {
    const key = `achievements.${props.achievement.id}.title`;
    return te(key) ? t(key) : props.achievement.title;
});

const achievementDescription = computed(() => {
    const key = `achievements.${props.achievement.id}.description`;
    return te(key) ? t(key) : props.achievement.description;
});

const progressPercent = computed(() => {
    if (!props.achievement.max) return 0;
    return Math.min(100, Math.round((props.achievement.progress / props.achievement.max) * 100));
});
</script>
