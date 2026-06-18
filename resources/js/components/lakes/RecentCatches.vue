<template>
    <div>
        <h3 class="mb-3 text-lg font-semibold text-slate-900">Ostatnie połowy</h3>
        <div v-if="!catches?.length" class="rounded-lg bg-slate-50 p-4 text-sm text-slate-500">
            Brak zarejestrowanych połowów na tym jeziorze.
        </div>
        <div v-else class="space-y-3">
            <div
                v-for="catchItem in catches"
                :key="catchItem.id"
                class="flex items-center gap-3 rounded-lg border border-slate-200 p-3"
            >
                <img
                    v-if="catchItem.photo_url"
                    :src="catchItem.photo_url"
                    :alt="catchItem.fish_name"
                    class="h-14 w-14 rounded-lg object-cover"
                />
                <div
                    v-else
                    class="flex h-14 w-14 items-center justify-center rounded-lg bg-emerald-50 text-2xl"
                >
                    🐟
                </div>
                <div class="min-w-0 flex-1">
                    <p class="font-medium text-slate-900">{{ catchItem.fish_name }}</p>
                    <p class="text-sm text-slate-500">
                        {{ catchItem.user?.name }}
                        <span v-if="catchItem.weight"> · {{ catchItem.weight }} kg</span>
                    </p>
                    <p class="text-xs text-slate-400">{{ formatDate(catchItem.caught_at) }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    catches: {
        type: Array,
        default: () => [],
    },
});

function formatDate(date) {
    if (!date) return '';
    return new Date(date).toLocaleDateString('pl-PL');
}
</script>
