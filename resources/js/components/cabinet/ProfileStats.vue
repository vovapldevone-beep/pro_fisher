<template>
    <div class="grid grid-cols-2 gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:grid-cols-3 lg:grid-cols-5">
        <div v-for="stat in items" :key="stat.label" class="text-center">
            <div class="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                <AppIcon :name="stat.icon" class="h-5 w-5" />
            </div>
            <p class="text-lg font-bold text-slate-900">{{ stat.value }}</p>
            <p class="text-xs text-slate-500">{{ stat.label }}</p>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import AppIcon from '../shared/AppIcon.vue';

const { t } = useI18n();

const props = defineProps({
    stats: {
        type: Object,
        required: true,
    },
});

const items = computed(() => [
    { icon: 'hook', value: props.stats.catches_count, label: t('stats.catches') },
    { icon: 'lake', value: props.stats.lakes_visited, label: t('stats.lakesVisited') },
    {
        icon: 'fish',
        value: props.stats.biggest_fish_weight ? `${props.stats.biggest_fish_weight} ${t('stats.kg')}` : '—',
        label: props.stats.biggest_fish_name
            ? `${t('stats.biggestFish')} ${props.stats.biggest_fish_name}`
            : t('stats.biggestFish'),
    },
    { icon: 'users', value: props.stats.followers_count, label: t('stats.followers') },
    { icon: 'heart', value: props.stats.total_likes ?? 0, label: t('stats.likes') },
    // No ranking chip — nothing computes it yet, so it only ever showed "#0"
]);
</script>
