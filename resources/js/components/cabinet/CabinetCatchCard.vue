<template>
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <img
            v-if="catchItem.photo_url"
            :src="catchItem.photo_url"
            :alt="catchItem.fish_name"
            class="h-28 w-full object-cover"
        />
        <div
            v-else
            class="flex h-28 w-full items-center justify-center bg-slate-100 text-3xl"
        >
            🐟
        </div>
        <div class="p-3">
            <p class="font-semibold text-slate-900">
                {{ catchItem.fish_name }}
                <span v-if="catchItem.weight" class="font-normal text-slate-600">{{ catchItem.weight }} кг</span>
            </p>
            <p class="mt-0.5 text-xs text-slate-400">{{ formatDate(catchItem.caught_at) }}</p>
            <p class="mt-1 text-xs text-slate-500">Озеро {{ catchItem.lake?.name }}</p>
        </div>
    </div>
</template>

<script setup>
defineProps({
    catchItem: {
        type: Object,
        required: true,
    },
});

function formatDate(date) {
    if (!date) return '';
    return new Date(date).toLocaleDateString('uk-UA');
}
</script>
