<template>
    <router-link
        :to="`/map?lake=${lake.slug}`"
        class="flex items-center gap-3 rounded-xl p-2 transition hover:bg-slate-50 sm:gap-4 sm:p-3"
    >
        <img
            v-if="lake.thumbnail_url"
            :src="lake.thumbnail_url"
            :alt="lake.name"
            class="h-14 w-20 shrink-0 rounded-lg object-cover sm:h-16 sm:w-24"
        />
        <div
            v-else
            class="flex h-14 w-20 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-2xl sm:h-16 sm:w-24"
        >
            🏞️
        </div>
        <div class="min-w-0 flex-1">
            <p class="truncate font-semibold text-slate-900">Озеро {{ lake.name }}</p>
            <p class="truncate text-sm text-slate-500">{{ lake.region }}</p>
        </div>
        <div class="flex shrink-0 flex-col items-end">
            <div class="flex items-center gap-1">
                <AppIcon name="star" class="h-4 w-4 text-amber-400" fill="currentColor" />
                <span class="text-sm font-medium text-slate-700">{{ lake.rating }}</span>
            </div>
            <!-- Imported scores stay labelled: they are not our users' ratings -->
            <span
                v-if="lake.rating_source === 'google'"
                class="whitespace-nowrap text-[10px] leading-tight text-slate-400"
            >
                оцінка Google
            </span>
        </div>
    </router-link>
</template>

<script setup>
import AppIcon from '../shared/AppIcon.vue';
defineProps({
    lake: {
        type: Object,
        required: true,
    },
});
</script>
