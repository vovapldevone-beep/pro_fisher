<template>
    <div class="flex h-full flex-col bg-white">
        <div class="border-b border-slate-200 p-4">
            <h2 class="text-lg font-bold text-slate-900">Усі озера</h2>
            <p class="mt-0.5 text-sm text-slate-500">{{ filteredLakes.length }} водойм</p>
            <div class="relative mt-3">
                <svg
                    class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input
                    v-model="search"
                    type="text"
                    placeholder="Пошук озера або регіону..."
                    class="w-full rounded-lg border border-slate-200 py-2 pl-9 pr-3 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                />
            </div>
        </div>

        <div v-if="loading" class="flex flex-1 items-center justify-center p-6">
            <p class="text-sm text-slate-500">Завантаження...</p>
        </div>

        <div v-else-if="!filteredLakes.length" class="flex flex-1 items-center justify-center p-6">
            <p class="text-center text-sm text-slate-500">Озер не знайдено</p>
        </div>

        <div v-else class="flex-1 space-y-1 overflow-y-auto p-2">
            <LakeListItem
                v-for="lake in filteredLakes"
                :key="lake.id"
                :lake="lake"
                :selected="selectedSlug === lake.slug"
                @select="$emit('lake-selected', $event)"
            />
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import LakeListItem from './LakeListItem.vue';

const props = defineProps({
    lakes: {
        type: Array,
        default: () => [],
    },
    loading: {
        type: Boolean,
        default: false,
    },
    selectedSlug: {
        type: String,
        default: null,
    },
    initialSearch: {
        type: String,
        default: '',
    },
});

defineEmits(['lake-selected']);

const search = ref(props.initialSearch);

const filteredLakes = computed(() => {
    const query = search.value.trim().toLowerCase();
    if (!query) return props.lakes;

    return props.lakes.filter((lake) =>
        lake.name.toLowerCase().includes(query)
        || lake.region?.toLowerCase().includes(query)
    );
});
</script>
