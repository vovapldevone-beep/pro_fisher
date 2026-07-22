<template>
    <ModalDialog
        :show="show"
        :saving="saving"
        :submit-label="t('catch.add')"
        :tabs="[
            { key: 'post', label: t('post.new') },
            { key: 'catch', label: t('catch.new') },
        ]"
        active-tab="catch"
        @tab="$emit('switch')"
        @close="$emit('close')"
        @submit="handleSubmit"
    >
        <!-- Photo -->
        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">{{ t('modal.photo') }}</label>
            <div
                class="relative flex h-48 w-full cursor-pointer items-center justify-center overflow-hidden rounded-xl border-2 border-dashed border-slate-200 bg-slate-50 hover:border-emerald-400"
                @click="$refs.photoInput.click()"
            >
                <img v-if="photoPreview" :src="photoPreview" class="h-full w-full object-cover" />
                <div v-else class="flex flex-col items-center gap-2 text-slate-400">
                    <svg class="h-10 w-10" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5M21 15.75V18a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-2.25M3 9.75h18"/>
                    </svg>
                    <span class="text-sm">{{ t('modal.photoHint') }}</span>
                </div>
            </div>
            <input ref="photoInput" type="file" accept="image/*" class="sr-only" @change="onPhotoChange" />
        </div>

        <!-- Fish name -->
        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">{{ t('catch.fishName') }}</label>
            <input
                v-model="form.fish_name"
                type="text"
                required
                :placeholder="t('catch.fishNamePlaceholder')"
                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-400 focus:ring-1 focus:ring-emerald-400"
            />
        </div>

        <!-- Weight + Date -->
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">{{ t('catch.weight') }}</label>
                <input
                    v-model="form.weight"
                    type="number"
                    step="0.01"
                    min="0"
                    placeholder="0.00"
                    class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-400 focus:ring-1 focus:ring-emerald-400"
                />
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">{{ t('catch.date') }}</label>
                <input
                    v-model="form.caught_at"
                    type="date"
                    required
                    class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-400 focus:ring-1 focus:ring-emerald-400"
                />
            </div>
        </div>

        <!-- Place: a lake from the list, a typed spot, GPS or a point on the map -->
        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">{{ t('catch.place') }}</label>
            <LakeSelect
                v-model="form.lake_id"
                v-model:location="form.location"
                :lakes="lakes"
                :placeholder="t('catch.placePlaceholder')"
                :error="lakeError"
                allow-gps
                allow-custom
                allow-map
                :map-open="showMap"
                :gps-loading="locating"
                @use-gps="handleUseGps"
                @toggle-map="toggleMap"
                @place-selected="handlePlaceSelected"
            />
            <div v-show="showMap" ref="mapEl" class="mt-2 h-52 w-full overflow-hidden rounded-xl border border-slate-200" />
            <p v-if="showMap" class="mt-1 text-xs text-slate-400">{{ t('modal.mapHint') }}</p>
            <p v-if="gpsError" class="mt-1 text-xs text-red-500">{{ gpsError }}</p>
        </div>

        <!-- Notes -->
        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">{{ t('modal.notes') }}</label>
            <textarea
                v-model="form.notes"
                rows="3"
                maxlength="2000"
                :placeholder="t('catch.notesPlaceholder')"
                class="w-full resize-none rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-400 focus:ring-1 focus:ring-emerald-400"
            />
        </div>
    </ModalDialog>
</template>

<script setup>
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { nextTick, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useGeolocation } from '../../composables/useGeolocation';
import LakeSelect from '../shared/LakeSelect.vue';
import ModalDialog from '../shared/ModalDialog.vue';

const { t } = useI18n();

const props = defineProps({
    show: { type: Boolean, default: false },
    lakes: { type: Array, default: () => [] },
    saving: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'submit', 'switch']);

const { locating, error: gpsError, locate, reverseGeocode } = useGeolocation();

function emptyForm() {
    return { fish_name: '', weight: '', caught_at: today(), lake_id: '', notes: '', location: '' };
}

const form = ref(emptyForm());
const photoFile = ref(null);
const photoPreview = ref(null);
const lakeError = ref('');
const showMap = ref(false);
const mapEl = ref(null);
let mapInstance = null;
let marker = null;

function today() {
    return new Date().toISOString().split('T')[0];
}

watch(() => props.show, (val) => {
    if (val) {
        form.value = emptyForm();
        photoFile.value = null;
        photoPreview.value = null;
        lakeError.value = '';
        gpsError.value = '';
        showMap.value = false;
    } else {
        destroyMap();
    }
});

// Either a lake or a location satisfies the requirement, so clear the error on both
watch(() => [form.value.lake_id, form.value.location], ([id, location]) => {
    if (id || location) lakeError.value = '';
});

function onPhotoChange(e) {
    const file = e.target.files[0];
    if (!file) return;
    photoFile.value = file;
    photoPreview.value = URL.createObjectURL(file);
}

// ─── Location: map picker ─────────────────────────────────────────────────────

async function toggleMap() {
    showMap.value = !showMap.value;
    if (showMap.value) {
        await nextTick();
        initMap();
    } else {
        destroyMap();
    }
}

function initMap() {
    if (mapInstance || !mapEl.value) return;
    mapInstance = L.map(mapEl.value).setView([50.4501, 30.5234], 6);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap',
    }).addTo(mapInstance);
    mapInstance.on('click', (e) => {
        const { lat, lng } = e.latlng;
        form.value.lake_id = '';
        form.value.location = `${lat.toFixed(5)}, ${lng.toFixed(5)}`;
        placeMarker(lat, lng);
    });
}

function placeMarker(lat, lng) {
    if (!mapInstance) return;
    if (marker) marker.remove();
    marker = L.marker([lat, lng]).addTo(mapInstance);
    mapInstance.setView([lat, lng], Math.max(mapInstance.getZoom(), 12));
}

function destroyMap() {
    if (mapInstance) {
        mapInstance.remove();
        mapInstance = null;
        marker = null;
    }
}

// A geocoded suggestion carries coordinates, so the open map can follow it
function handlePlaceSelected(place) {
    if (showMap.value) placeMarker(place.lat, place.lon);
}

// ─── Location: device GPS ─────────────────────────────────────────────────────

async function handleUseGps() {
    let coords;
    try {
        coords = await locate();
    } catch {
        return; // gpsError already carries the reason
    }

    const { latitude, longitude } = coords;
    // A lake and a free-form location are mutually exclusive
    form.value.lake_id = '';
    form.value.location = `${latitude}, ${longitude}`;

    const address = await reverseGeocode(latitude, longitude);
    if (address) form.value.location = address;

    if (showMap.value) placeMarker(latitude, longitude);
}

function handleSubmit() {
    // The combobox is not a native control, so the browser cannot enforce `required`
    if (!form.value.lake_id && !form.value.location.trim()) {
        lakeError.value = t('catch.lakeOrLocationRequired');
        return;
    }

    const fd = new FormData();
    fd.append('fish_name', form.value.fish_name);
    fd.append('caught_at', form.value.caught_at);
    if (form.value.lake_id) fd.append('lake_id', form.value.lake_id);
    if (form.value.location.trim()) fd.append('location', form.value.location.trim());
    if (form.value.weight) fd.append('weight', form.value.weight);
    if (form.value.notes) fd.append('notes', form.value.notes);
    if (photoFile.value) fd.append('photo', photoFile.value);
    emit('submit', fd);
}
</script>
