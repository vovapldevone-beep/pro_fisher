<template>
    <div class="relative flex h-full overflow-hidden">
        <!-- Map (left, fills remaining space) -->
        <div class="relative min-w-0 flex-1">
            <button
                type="button"
                class="absolute right-4 top-4 z-[1000] rounded-lg bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-md hover:bg-slate-50 md:hidden"
                @click="showList = !showList"
            >
                {{ showList ? t('map.showMap') : t('map.showList') }}
            </button>

            <LakeMap
                ref="lakeMapRef"
                :lakes="lakesStore.lakes"
                :highlighted-slug="lakesStore.selectedLake?.slug"
                @lake-selected="handleLakeSelected"
                @bounds-changed="handleBoundsChanged"
                @map-clicked="onMapClicked"
            />
        </div>

        <!-- Lake list sidebar (right side) -->
        <aside
            class="z-[1000] flex w-full max-w-sm shrink-0 flex-col border-l border-slate-200 bg-white shadow-lg transition-transform md:relative"
            :class="[
                showList ? 'absolute inset-y-0 right-0 md:static' : 'absolute right-0 translate-x-full md:translate-x-0 md:flex',
                showCard ? 'hidden md:flex' : '',
            ]"
        >
            <button
                type="button"
                class="absolute right-3 top-3 z-10 flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200 hover:text-slate-700 md:hidden"
                @click="showList = false"
            >
                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M18 6 6 18M6 6l12 12"/>
                </svg>
            </button>
            <LakeList
                :lakes="lakesStore.lakes"
                :loading="lakesStore.loading"
                :selected-slug="lakesStore.selectedLake?.slug"
                :initial-search="initialSearch"
                @lake-selected="handleLakeSelected"
            />
        </aside>

        <!-- Lake detail card (absolute overlay on the right, above the list) -->
        <div
            v-if="showCard"
            class="absolute inset-y-0 right-0 z-[1001] flex w-full max-w-md flex-col border-l border-slate-200 bg-white shadow-2xl"
        >
            <LakeCard
                :lake="lakesStore.selectedLake"
                :loading="lakesStore.detailLoading"
                @close="closeCard"
            />
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
import { useRoute, useRouter } from 'vue-router';
import LakeMap from '../components/map/LakeMap.vue';
import LakeCard from '../components/lakes/LakeCard.vue';
import LakeList from '../components/lakes/LakeList.vue';
import { useLakesStore } from '../stores/lakes';

const lakesStore = useLakesStore();
const route = useRoute();
const router = useRouter();
const showCard = ref(false);
const showList = ref(false);
const lakeMapRef = ref(null);

const initialSearch = computed(() => route.query.q?.toString() || '');

async function handleBoundsChanged(bounds) {
    await lakesStore.loadLakes(bounds);
}

async function handleLakeSelected(lake) {
    showCard.value = true;
    showList.value = false;
    await lakesStore.loadLake(lake.slug);
    router.replace({ query: { ...route.query, lake: lake.slug } });
    lakeMapRef.value?.flyToLake(lake);
}

function onMapClicked() {
    if (showList.value) showList.value = false;
}

function closeCard() {
    showCard.value = false;
    lakesStore.clearSelectedLake();
    const { lake, ...rest } = route.query;
    router.replace({ query: rest });
}

onMounted(async () => {
    const lakeSlug = route.query.lake;
    if (lakeSlug) {
        showCard.value = true;
        await lakesStore.loadLake(lakeSlug);
        const lake = lakesStore.lakes.find((l) => l.slug === lakeSlug);
        if (lake) {
            lakeMapRef.value?.flyToLake(lake);
        }
    }
});

watch(() => route.query.lake, async (slug) => {
    if (slug && slug !== lakesStore.selectedLake?.slug) {
        showCard.value = true;
        await lakesStore.loadLake(slug);
    } else if (!slug) {
        showCard.value = false;
        lakesStore.clearSelectedLake();
    }
});
</script>
