<template>
    <ModalDialog
        :show="show"
        :title="t('post.new')"
        :saving="saving"
        :submit-label="t('post.publish')"
        :saving-label="t('post.publishing')"
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

        <!-- Description -->
        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">{{ t('post.description') }}</label>
            <textarea
                v-model="form.notes"
                rows="3"
                maxlength="2000"
                :placeholder="t('post.descriptionPlaceholder')"
                class="w-full resize-none rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-400 focus:ring-1 focus:ring-emerald-400"
            />
        </div>

        <!-- Location -->
        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">{{ t('modal.location') }}</label>
            <div class="flex gap-2">
                <input
                    v-model="form.location"
                    type="text"
                    :placeholder="t('modal.locationPlaceholder')"
                    class="flex-1 rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-400 focus:ring-1 focus:ring-emerald-400"
                />
                <button
                    type="button"
                    class="flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-600 hover:bg-slate-50"
                    @click="toggleMap"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                    </svg>
                    {{ showMap ? t('modal.mapClose') : t('modal.mapOpen') }}
                </button>
            </div>
            <div v-show="showMap" ref="mapEl" class="mt-2 h-52 w-full overflow-hidden rounded-xl border border-slate-200" />
            <p v-if="showMap" class="mt-1 text-xs text-slate-400">{{ t('modal.mapHint') }}</p>
        </div>
    </ModalDialog>
</template>

<script setup>
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { nextTick, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import ModalDialog from '../shared/ModalDialog.vue';

const { t } = useI18n();

const props = defineProps({
    show: { type: Boolean, default: false },
    saving: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'submit']);

const form = ref({ notes: '', location: '' });
const photoFile = ref(null);
const photoPreview = ref(null);
const showMap = ref(false);
const mapEl = ref(null);
let mapInstance = null;
let marker = null;

watch(() => props.show, (val) => {
    if (val) {
        form.value = { notes: '', location: '' };
        photoFile.value = null;
        photoPreview.value = null;
        showMap.value = false;
    } else {
        destroyMap();
    }
});

function onPhotoChange(e) {
    const file = e.target.files[0];
    if (!file) return;
    photoFile.value = file;
    photoPreview.value = URL.createObjectURL(file);
}

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
        form.value.location = `${lat.toFixed(5)}, ${lng.toFixed(5)}`;
        if (marker) marker.remove();
        marker = L.marker([lat, lng]).addTo(mapInstance);
    });
}

function destroyMap() {
    if (mapInstance) {
        mapInstance.remove();
        mapInstance = null;
        marker = null;
    }
}

function handleSubmit() {
    const fd = new FormData();
    fd.append('type', 'post');
    if (form.value.notes) fd.append('notes', form.value.notes);
    if (form.value.location) fd.append('location', form.value.location);
    if (photoFile.value) fd.append('photo', photoFile.value);
    emit('submit', fd);
}
</script>
