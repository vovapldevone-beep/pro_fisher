<template>
    <div class="grid grid-cols-2 gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:grid-cols-3 lg:grid-cols-6">
        <div v-for="stat in items" :key="stat.label" class="text-center">
            <div class="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                <component :is="stat.icon" class="h-5 w-5" />
            </div>
            <p class="text-lg font-bold text-slate-900">{{ stat.value }}</p>
            <p class="text-xs text-slate-500">{{ stat.label }}</p>
        </div>
    </div>
</template>

<script setup>
import { computed, h } from 'vue';

const props = defineProps({
    stats: {
        type: Object,
        required: true,
    },
});

const HookIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M12 6v6m0 0v6m0-6h6m-6 0H6' }),
]);

const LakeIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7' }),
]);

const FishIcon = () => h('svg', { fill: 'currentColor', viewBox: '0 0 24 24' }, [
    h('path', { d: 'M12 2C8 6 4 8 4 12c0 3 2 5 4 6 1-2 3-3 5-3s4 1 5 3c2-1 4-3 4-6 0-4-4-6-8-10z' }),
]);

const UsersIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' }),
]);

const TrophyIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M5 3h14M9 3v2a3 3 0 003 3h0a3 3 0 003-3V3M5 3v2a5 5 0 005 5h4a5 5 0 005-5V3M7 10v1a5 5 0 005 5h0a5 5 0 005-5v-1M9 21h6' }),
]);

const HeartIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z' }),
]);

const items = computed(() => [
    { icon: HookIcon, value: props.stats.catches_count, label: 'уловів' },
    { icon: LakeIcon, value: props.stats.lakes_visited, label: 'озер відвідано' },
    {
        icon: FishIcon,
        value: props.stats.biggest_fish_weight ? `${props.stats.biggest_fish_weight} кг` : '—',
        label: props.stats.biggest_fish_name ? `найбільша риба ${props.stats.biggest_fish_name}` : 'найбільша риба',
    },
    { icon: UsersIcon, value: props.stats.followers_count, label: 'підписників' },
    { icon: HeartIcon, value: props.stats.total_likes ?? 0, label: 'лайків' },
    { icon: TrophyIcon, value: `#${props.stats.ranking}`, label: 'місце в рейтингу' },
]);
</script>
