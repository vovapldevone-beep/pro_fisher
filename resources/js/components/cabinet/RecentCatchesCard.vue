<template>
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="font-bold text-slate-900">Останні улови</h3>
            <router-link to="/cabinet/catches" class="text-sm text-blue-600 hover:underline">
                Переглянути всі
            </router-link>
        </div>

        <div v-if="!catches?.length" class="text-sm text-slate-500">
            Ще немає уловів
        </div>
        <div v-else class="grid grid-cols-2 gap-3">
            <CabinetCatchCard
                v-for="catchItem in catches"
                :key="catchItem.id"
                :catch-item="catchItem"
            />
        </div>

        <button
            v-if="showAddButton"
            type="button"
            class="mt-4 w-full rounded-lg bg-emerald-600 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700"
            @click="$emit('add-catch')"
        >
            + Додати улов
        </button>
    </div>
</template>

<script setup>
import CabinetCatchCard from './CabinetCatchCard.vue';

defineProps({
    catches: {
        type: Array,
        default: () => [],
    },
    showAddButton: {
        type: Boolean,
        default: true,
    },
});

defineEmits(['add-catch']);
</script>
