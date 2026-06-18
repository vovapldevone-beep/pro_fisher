<template>
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="font-bold text-slate-900">Мої дозволи</h3>
            <button type="button" class="text-sm text-blue-600 hover:underline">Переглянути всі</button>
        </div>

        <div v-if="!permits.length" class="text-sm text-slate-500">Немає активних дозволів</div>

        <div v-else class="space-y-4">
            <div
                v-for="permit in permits"
                :key="permit.id"
                class="rounded-xl border border-slate-100 p-4"
            >
                <div class="flex items-center justify-between">
                    <p class="font-semibold text-slate-900">Озеро {{ permit.lake_name }}</p>
                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700">
                        Активний
                    </span>
                </div>
                <p class="mt-1 text-sm text-slate-500">{{ permit.duration_label }}</p>
                <p class="text-sm text-slate-500">Діє до {{ permit.expires_at }}</p>
                <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
                    <div
                        class="h-full rounded-full bg-emerald-500"
                        :style="{ width: `${progress(permit)}%` }"
                    ></div>
                </div>
                <p class="mt-1 text-right text-xs text-slate-400">
                    Залишилось {{ permit.days_left }} днів
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    permits: {
        type: Array,
        default: () => [],
    },
});

function progress(permit) {
    if (!permit.days_total) return 0;
    return Math.max(0, Math.min(100, (permit.days_left / permit.days_total) * 100));
}
</script>
