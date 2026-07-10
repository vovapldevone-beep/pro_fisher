<template>
    <a
        :href="url"
        target="_blank"
        rel="noopener"
        class="inline-flex max-w-full items-center gap-1 rounded-full bg-blue-600/50 px-2 py-1 text-[11px] font-medium text-white/80 shadow backdrop-blur-sm hover:bg-blue-600/70 sm:px-2.5 sm:text-xs"
        @click.stop
    >
        <svg class="h-3 w-3 shrink-0" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
        </svg>
        <span class="min-w-0 truncate">{{ label }}</span>
    </a>
</template>

<script setup>
defineProps({
    label: { type: String, required: true },
    url: { type: String, required: true },
});
</script>
