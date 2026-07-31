<template>
    <div ref="root" class="relative">
        <div
            class="flex w-full cursor-text items-center gap-2 rounded-lg border bg-white px-3 py-2 transition"
            :class="isOpen
                ? 'border-emerald-400 ring-1 ring-emerald-400'
                : (error ? 'border-red-300' : 'border-slate-200')"
            @click="openDropdown"
        >
            <input
                ref="inputEl"
                v-model="query"
                type="text"
                autocomplete="off"
                class="min-w-0 flex-1 bg-transparent text-sm outline-none placeholder:text-slate-400"
                :placeholder="displayText || placeholder"
                :class="displayText && !isOpen ? 'placeholder:text-slate-900' : ''"
                @focus="openDropdown"
                @keydown.down.prevent="move(1)"
                @keydown.up.prevent="move(-1)"
                @keydown.enter.prevent="selectHighlighted"
                @keydown.esc.prevent="closeDropdown"
            />

            <button
                v-if="modelValue || location"
                type="button"
                class="shrink-0 text-slate-300 transition hover:text-slate-500"
                :aria-label="t('modal.cancel')"
                @click.stop="clear"
            >
                <AppIcon name="close" class="h-4 w-4" />
            </button>

            <button
                v-if="allowMap"
                type="button"
                class="shrink-0 rounded p-0.5 transition"
                :class="mapOpen ? 'text-emerald-600' : 'text-slate-400 hover:text-slate-600'"
                :aria-label="t('modal.mapOpen')"
                @click.stop="$emit('toggle-map')"
            >
                <AppIcon name="map" class="h-4 w-4" />
            </button>

            <AppIcon name="chevron-down" class="h-4 w-4 shrink-0 text-slate-400 transition-transform" :class="isOpen ? 'rotate-180' : ''" />
        </div>

        <p v-if="error" class="mt-1 text-xs text-red-500">{{ error }}</p>

        <!-- Teleported so the modal's overflow-y-auto can't clip it -->
        <Teleport to="body">
            <ul
                v-if="isOpen"
                ref="dropdownEl"
                class="fixed z-[60] max-h-60 overflow-y-auto rounded-lg border border-slate-200 bg-white py-1 shadow-xl"
                :style="dropdownStyle"
            >
                <!-- GPS shortcut always sits on top, regardless of the search query -->
                <li
                    v-if="allowGps"
                    class="flex cursor-pointer items-center gap-3 border-b border-slate-100 px-3 py-2.5 text-sm transition hover:bg-slate-50"
                    :class="gpsLoading ? 'pointer-events-none opacity-60' : ''"
                    @click="onUseGps"
                >
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                        <svg v-if="!gpsLoading" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="3"/>
                            <circle cx="12" cy="12" r="8"/>
                            <path d="M12 2v3m0 14v3M2 12h3m14 0h3" stroke-linecap="round"/>
                        </svg>
                        <AppIcon name="spinner" class="h-3.5 w-3.5 animate-spin" />
                    </span>
                    <span class="min-w-0">
                        <span class="block font-medium text-slate-800">{{ t('catch.useGps') }}</span>
                        <span class="block text-xs text-slate-400">
                            {{ gpsLoading ? t('catch.gpsLocating') : t('catch.useGpsHint') }}
                        </span>
                    </span>
                </li>

                <template v-for="(option, index) in options" :key="option.key">
                    <!-- Group header, printed before the first option of each group -->
                    <li
                        v-if="option.groupLabel"
                        class="px-3 pb-1 pt-2 text-[11px] font-semibold uppercase tracking-wide text-slate-400"
                    >
                        {{ option.groupLabel }}
                    </li>

                    <li
                        data-option
                        class="flex cursor-pointer items-center gap-2 px-3 py-2.5 text-sm transition"
                        :class="index === highlighted
                            ? 'bg-emerald-50 text-emerald-900'
                            : 'text-slate-700 hover:bg-slate-50'"
                        @mouseenter="highlighted = index"
                        @click="choose(option)"
                    >
                        <AppIcon name="map-pin" v-if="option.type === 'place'" class="h-3.5 w-3.5 shrink-0 text-blue-500" fill="currentColor" />

                        <span class="min-w-0 flex-1 truncate">{{ option.label }}</span>
                        <span v-if="option.hint" class="shrink-0 truncate text-xs text-slate-400">{{ option.hint }}</span>
                    </li>
                </template>

                <!-- Geocoder is still working on the current query -->
                <li v-if="suggestLoading" class="flex items-center gap-2 px-3 py-2.5 text-sm text-slate-400">
                    <AppIcon name="spinner" class="h-3.5 w-3.5 animate-spin" />
                    {{ t('catch.searchingPlaces') }}
                </li>

                <!-- Nothing matched anywhere: keep whatever was typed -->
                <li
                    v-if="allowCustom && trimmedQuery && !suggestLoading && !suggestions.length"
                    class="flex cursor-pointer items-center gap-2 px-3 py-2.5 text-sm transition hover:bg-slate-50"
                    @click="selectCustom"
                >
                    <AppIcon name="map-pin" class="h-3.5 w-3.5 shrink-0 text-blue-500" fill="currentColor" />
                    <span class="min-w-0 truncate">
                        «{{ trimmedQuery }}» — <span class="text-slate-400">{{ t('catch.useAsOwnPlace') }}</span>
                    </span>
                </li>

                <li v-if="!options.length && !suggestLoading && !trimmedQuery" class="px-3 py-2.5 text-sm text-slate-400">
                    {{ t('catch.noLakesFound') }}
                </li>
            </ul>
        </Teleport>
    </div>
</template>

<script setup>
import { computed, nextTick, onUnmounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import AppIcon from '../shared/AppIcon.vue';

const { t } = useI18n();

const props = defineProps({
    modelValue: { type: [Number, String], default: '' },
    // A free-form place, used when the lake is not in the list. Mutually exclusive with modelValue.
    location: { type: String, default: '' },
    lakes: { type: Array, default: () => [] },
    placeholder: { type: String, default: '' },
    error: { type: String, default: '' },
    allowGps: { type: Boolean, default: false },
    allowCustom: { type: Boolean, default: false },
    allowMap: { type: Boolean, default: false },
    mapOpen: { type: Boolean, default: false },
    gpsLoading: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue', 'update:location', 'use-gps', 'toggle-map', 'place-selected']);

const root = ref(null);
const inputEl = ref(null);
const dropdownEl = ref(null);

const isOpen = ref(false);
const query = ref('');
const highlighted = ref(0);
const dropdownStyle = ref({});

const selectedLake = computed(() =>
    props.lakes.find((l) => String(l.id) === String(props.modelValue)) ?? null
);

const trimmedQuery = computed(() => query.value.trim());

const displayText = computed(() => selectedLake.value?.name || props.location || '');

// An empty query lists everything — that is what makes the field browsable, not just searchable
const filtered = computed(() => {
    const q = query.value.trim().toLowerCase();
    if (!q) return props.lakes;
    return props.lakes.filter((l) =>
        l.name.toLowerCase().includes(q) || l.region?.toLowerCase().includes(q)
    );
});

// ─── Map suggestions (Nominatim) ──────────────────────────────────────────────

const suggestions = ref([]);
const suggestLoading = ref(false);

const MIN_QUERY = 3;
const DEBOUNCE_MS = 350;

let debounceTimer = null;
let controller = null;

async function fetchSuggestions(q) {
    // Abort the in-flight request so a slow early response cannot overwrite a newer one
    controller?.abort();
    controller = new AbortController();

    suggestLoading.value = true;
    try {
        const url = `https://nominatim.openstreetmap.org/search?format=json&limit=5&q=${encodeURIComponent(q)}`;
        const res = await fetch(url, {
            headers: { 'Accept-Language': 'uk,pl,en' },
            signal: controller.signal,
        });
        if (!res.ok) throw new Error('request failed');
        const data = await res.json();

        suggestions.value = data.map((p) => ({
            name: p.display_name,
            lat: parseFloat(p.lat),
            lon: parseFloat(p.lon),
        }));
    } catch (e) {
        if (e.name !== 'AbortError') suggestions.value = [];
    } finally {
        // A newer request may already be running; do not clear its spinner
        if (!controller.signal.aborted) suggestLoading.value = false;
    }
}

watch(trimmedQuery, (q) => {
    if (!props.allowCustom) return;

    clearTimeout(debounceTimer);

    if (q.length < MIN_QUERY) {
        controller?.abort();
        suggestions.value = [];
        suggestLoading.value = false;
        return;
    }

    // Nominatim allows roughly one request per second — debounce, do not spam per keystroke
    debounceTimer = setTimeout(() => fetchSuggestions(q), DEBOUNCE_MS);
});

// ─── Combined option list (keyboard navigation runs over this) ─────────────────

const options = computed(() => {
    const list = filtered.value.map((lake, i) => ({
        key: `lake-${lake.id}`,
        type: 'lake',
        lake,
        label: lake.name,
        hint: lake.region || '',
        groupLabel: i === 0 && suggestions.value.length ? t('catch.lakesGroup') : '',
    }));

    suggestions.value.forEach((place, i) => {
        list.push({
            key: `place-${place.lat}-${place.lon}`,
            type: 'place',
            place,
            label: place.name,
            hint: '',
            groupLabel: i === 0 ? t('catch.placesGroup') : '',
        });
    });

    return list;
});

watch(options, () => (highlighted.value = 0));

function updatePosition() {
    if (!root.value) return;

    const rect = root.value.getBoundingClientRect();
    const gap = 4;
    const maxHeight = 240;
    const spaceBelow = window.innerHeight - rect.bottom;

    // Flip upwards when the field sits near the bottom of the viewport
    const openUp = spaceBelow < maxHeight && rect.top > spaceBelow;

    dropdownStyle.value = openUp
        ? {
            left: `${rect.left}px`,
            width: `${rect.width}px`,
            bottom: `${window.innerHeight - rect.top + gap}px`,
            maxHeight: `${Math.min(maxHeight, rect.top - gap)}px`,
        }
        : {
            left: `${rect.left}px`,
            width: `${rect.width}px`,
            top: `${rect.bottom + gap}px`,
            maxHeight: `${Math.min(maxHeight, spaceBelow - gap)}px`,
        };
}

async function openDropdown() {
    if (isOpen.value) return;
    isOpen.value = true;
    query.value = '';
    highlighted.value = 0;

    await nextTick();
    updatePosition();
    inputEl.value?.focus();

    // capture:true so scrolling the modal body (not just the window) repositions us
    window.addEventListener('scroll', updatePosition, true);
    window.addEventListener('resize', updatePosition);
    document.addEventListener('pointerdown', onPointerDown);
}

function closeDropdown() {
    if (!isOpen.value) return;
    isOpen.value = false;
    query.value = '';
    clearTimeout(debounceTimer);
    controller?.abort();
    suggestions.value = [];
    suggestLoading.value = false;
    teardown();
}

function teardown() {
    window.removeEventListener('scroll', updatePosition, true);
    window.removeEventListener('resize', updatePosition);
    document.removeEventListener('pointerdown', onPointerDown);
}

function onPointerDown(e) {
    const inField = root.value?.contains(e.target);
    const inList = dropdownEl.value?.contains(e.target);
    if (!inField && !inList) closeDropdown();
}

function select(lake) {
    emit('update:modelValue', lake.id);
    emit('update:location', '');
    closeDropdown();
    inputEl.value?.blur();
}

function selectCustom() {
    emit('update:location', trimmedQuery.value);
    emit('update:modelValue', '');
    closeDropdown();
    inputEl.value?.blur();
}

function selectPlace(place) {
    emit('update:location', place.name);
    emit('update:modelValue', '');
    emit('place-selected', place);
    closeDropdown();
    inputEl.value?.blur();
}

function choose(option) {
    if (option.type === 'lake') select(option.lake);
    else selectPlace(option.place);
}

// Enter takes the highlighted option, otherwise keeps whatever was typed
function selectHighlighted() {
    const option = options.value[highlighted.value];
    if (option) {
        choose(option);
    } else if (props.allowCustom && trimmedQuery.value) {
        selectCustom();
    }
}

function onUseGps() {
    if (props.gpsLoading) return;
    emit('use-gps');
    closeDropdown();
}

function clear() {
    emit('update:modelValue', '');
    emit('update:location', '');
    query.value = '';
    inputEl.value?.focus();
}

function move(step) {
    if (!isOpen.value) {
        openDropdown();
        return;
    }
    const count = options.value.length;
    if (!count) return;
    highlighted.value = (highlighted.value + step + count) % count;

    nextTick(() => {
        dropdownEl.value
            ?.querySelectorAll('[data-option]')[highlighted.value]
            ?.scrollIntoView({ block: 'nearest' });
    });
}

onUnmounted(() => {
    clearTimeout(debounceTimer);
    controller?.abort();
    teardown();
});
</script>
