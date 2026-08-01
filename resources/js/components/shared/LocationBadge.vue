<template>
    <a
        :href="url"
        target="_blank"
        rel="noopener"
        class="inline-flex max-w-full items-center gap-1 rounded-full bg-blue-600/50 px-2 py-1 text-[11px] font-medium text-white/80 shadow backdrop-blur-sm hover:bg-blue-600/70 sm:px-2.5 sm:text-xs"
        @click.stop
    >
        <AppIcon name="map-pin" class="h-3 w-3 shrink-0" fill="currentColor" />
        <span class="min-w-0 truncate">{{ label }}</span>
    </a>
</template>

<script setup>
import AppIcon from '../shared/AppIcon.vue';
defineProps({
    label: { type: String, required: true },
    url: { type: String, required: true },
});
</script>
