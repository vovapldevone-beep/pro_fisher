<template>
    <button
        type="button"
        class="flex w-full items-center gap-3 rounded-xl p-3 text-left transition"
        :class="selected ? 'bg-emerald-50 ring-1 ring-emerald-200' : 'hover:bg-slate-50'"
        @click="$emit('select', lake)"
    >
        <img
            v-if="lake.thumbnail_url"
            :src="lake.thumbnail_url"
            :alt="lake.name"
            class="h-14 w-20 shrink-0 rounded-lg object-cover"
        />
        <div
            v-else
            class="flex h-14 w-20 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-xl"
        >
            🏞️
        </div>
        <div class="min-w-0 flex-1">
            <p class="truncate font-semibold text-slate-900">{{ lake.name }}</p>
            <p v-if="lake.region" class="truncate text-sm text-slate-500">{{ lake.region }}</p>
            <p v-if="lake.price" class="mt-0.5 text-sm font-medium text-emerald-700">
                {{ lake.price }} PLN / день
            </p>
        </div>
        <div v-if="lake.rating" class="flex shrink-0 items-center gap-1">
            <AppIcon name="star" class="h-4 w-4 text-amber-400" fill="currentColor" />
            <span class="text-sm font-medium text-slate-600">{{ lake.rating }}</span>
        </div>
    </button>
</template>

<script setup>
import AppIcon from '../shared/AppIcon.vue';
defineProps({
    lake: {
        type: Object,
        required: true,
    },
    selected: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['select']);
</script>
