<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            @click.self="$emit('close')"
        >
            <div class="flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
                <!-- Header -->
                <div class="flex flex-shrink-0 items-center justify-between border-b border-slate-100 px-6 py-4">
                    <h2 class="font-semibold text-slate-900">Новий пост</h2>
                    <button type="button" class="flex h-7 w-7 items-center justify-center rounded-full text-slate-400 hover:bg-slate-100" @click="$emit('close')">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form class="flex-1 overflow-y-auto" @submit.prevent="handleSubmit">
                    <div class="space-y-4 px-6 py-5">
                        <!-- Photo -->
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Фото</label>
                            <div
                                class="relative flex h-48 w-full cursor-pointer items-center justify-center overflow-hidden rounded-xl border-2 border-dashed border-slate-200 bg-slate-50 hover:border-emerald-400"
                                @click="$refs.photoInput.click()"
                            >
                                <img v-if="photoPreview" :src="photoPreview" class="h-full w-full object-cover" />
                                <div v-else class="flex flex-col items-center gap-2 text-slate-400">
                                    <svg class="h-10 w-10" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5M21 15.75V18a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-2.25M3 9.75h18"/>
                                    </svg>
                                    <span class="text-sm">Натисни щоб додати фото</span>
                                </div>
                            </div>
                            <input ref="photoInput" type="file" accept="image/*" class="sr-only" @change="onPhotoChange" />
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Опис</label>
                            <textarea
                                v-model="form.notes"
                                rows="3"
                                maxlength="2000"
                                placeholder="Поділись своєю історією..."
                                class="w-full resize-none rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-400 focus:ring-1 focus:ring-emerald-400"
                            />
                        </div>

                        <!-- Location -->
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Розташування</label>
                            <div class="flex gap-2">
                                <input
                                    v-model="form.location"
                                    type="text"
                                    placeholder="Назва місця або координати"
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
                                    {{ showMap ? 'Закрити' : 'Карта' }}
                                </button>
                            </div>

                            <!-- Leaflet map -->
                            <div v-show="showMap" ref="mapEl" class="mt-2 h-52 w-full overflow-hidden rounded-xl border border-slate-200" />
                            <p v-if="showMap" class="mt-1 text-xs text-slate-400">Натисни на карті щоб вибрати місце</p>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex flex-shrink-0 gap-3 border-t border-slate-100 px-6 py-4">
                        <button type="button" class="flex-1 rounded-lg border border-slate-200 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50" @click="$emit('close')">
                            Скасувати
                        </button>
                        <button type="submit" :disabled="saving" class="flex-1 rounded-lg bg-emerald-600 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-60">
                            {{ saving ? 'Публікація...' : 'Опублікувати' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { nextTick, ref, watch } from 'vue';

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
