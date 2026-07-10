<template>
    <div class="flex items-center gap-3 rounded-xl p-2 transition hover:bg-slate-50 sm:gap-4 sm:p-3">
        <!-- Below sm only the avatar survives: a 320px row cannot hold the name too -->
        <div class="flex shrink-0 items-center gap-2 sm:w-28">
            <UserAvatar :user="catchItem.user" size="md" />
            <div class="hidden min-w-0 sm:block">
                <p class="truncate text-sm font-medium text-slate-900">{{ catchItem.user?.name }}</p>
                <p class="text-xs text-slate-400">{{ timeAgo }}</p>
            </div>
        </div>

        <img
            v-if="catchItem.photo_url"
            :src="catchItem.photo_url"
            :alt="catchItem.fish_name"
            class="h-12 w-16 shrink-0 rounded-lg object-cover sm:h-14 sm:w-20"
        />
        <div
            v-else
            class="flex h-12 w-16 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-xl sm:h-14 sm:w-20"
        >
            🐟
        </div>

        <div class="min-w-0 flex-1">
            <div class="flex items-baseline justify-between gap-2">
                <p class="min-w-0 truncate font-semibold text-slate-900">{{ catchItem.fish_name }}</p>
                <p v-if="catchItem.weight" class="shrink-0 text-sm font-medium text-slate-600">
                    {{ catchItem.weight }} кг
                </p>
            </div>
            <p class="mt-0.5 flex items-center gap-1 text-sm text-slate-500">
                <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span class="truncate">Озеро {{ catchItem.lake?.name }}</span>
            </p>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import UserAvatar from '../shared/UserAvatar.vue';

const props = defineProps({
    catchItem: {
        type: Object,
        required: true,
    },
});

const timeAgo = computed(() => {
    const date = new Date(props.catchItem.created_at || props.catchItem.caught_at);
    const diff = Date.now() - date.getTime();
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);

    if (hours < 1) return 'щойно';
    if (hours < 24) return `${hours} год. тому`;
    if (days < 7) return `${days} дн. тому`;
    return date.toLocaleDateString('uk-UA');
});
</script>
