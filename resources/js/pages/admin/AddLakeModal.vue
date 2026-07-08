<template>
    <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-4 pt-10">
        <div class="w-full max-w-3xl rounded-2xl bg-white shadow-2xl">

            <!-- Header -->
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <h2 class="text-lg font-semibold text-slate-900">{{ isEdit ? 'Редагувати озеро' : 'Додати озеро' }}</h2>
                <button type="button" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600" @click="$emit('close')">
                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Body -->
            <div class="space-y-5 p-6">

                <!-- Name -->
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Назва <span class="text-red-500">*</span></label>
                    <input v-model="form.name" type="text" placeholder="Озеро Вишнівське"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100" />
                </div>

                <!-- Photos -->
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">
                        Фотографії
                        <span class="ml-1 font-normal text-slate-400">— до 10 фото, перше стане головним</span>
                    </label>

                    <!-- Previews -->
                    <div v-if="existingPhotos.length || photos.length" class="mb-3 grid grid-cols-4 gap-2 sm:grid-cols-5">
                        <!-- Existing photos (edit mode) -->
                        <div
                            v-for="p in existingPhotos"
                            :key="'existing-' + p.id"
                            class="group relative aspect-square"
                        >
                            <img :src="p.url" class="h-full w-full rounded-xl object-cover" />
                            <span v-if="p.is_primary"
                                class="absolute left-1.5 top-1.5 rounded-md bg-emerald-500 px-1.5 py-0.5 text-[10px] font-semibold text-white shadow">
                                Головна
                            </span>
                            <button
                                type="button"
                                class="absolute right-1 top-1 flex h-6 w-6 items-center justify-center rounded-full bg-black/50 text-white opacity-0 transition group-hover:opacity-100"
                                @click="removeExistingPhoto(p)"
                            >
                                <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <path d="M18 6 6 18M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <!-- New photos -->
                        <div
                            v-for="(p, idx) in photos"
                            :key="p.id"
                            class="group relative aspect-square"
                        >
                            <img :src="p.preview" class="h-full w-full rounded-xl object-cover" />
                            <!-- Primary badge (only when no existing photos remain) -->
                            <span v-if="idx === 0 && !existingPhotos.length"
                                class="absolute left-1.5 top-1.5 rounded-md bg-emerald-500 px-1.5 py-0.5 text-[10px] font-semibold text-white shadow">
                                Головна
                            </span>
                            <!-- Remove button -->
                            <button
                                type="button"
                                class="absolute right-1 top-1 flex h-6 w-6 items-center justify-center rounded-full bg-black/50 text-white opacity-0 transition group-hover:opacity-100"
                                @click="removePhoto(idx)"
                            >
                                <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <path d="M18 6 6 18M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <!-- Add more button (if < 10) -->
                        <button
                            v-if="totalPhotoCount < 10"
                            type="button"
                            class="flex aspect-square items-center justify-center rounded-xl border-2 border-dashed border-slate-200 text-slate-400 transition hover:border-emerald-400 hover:text-emerald-500"
                            @click="fileInput.click()"
                        >
                            <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                        </button>
                    </div>

                    <!-- Upload zone (empty state) -->
                    <div
                        v-if="!photos.length && !existingPhotos.length"
                        class="flex flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-slate-200 py-8 transition"
                        :class="dragging ? 'border-emerald-400 bg-emerald-50' : 'hover:border-slate-300'"
                        @dragover.prevent="dragging = true"
                        @dragleave="dragging = false"
                        @drop.prevent="onDrop"
                        @click="fileInput.click()"
                    >
                        <svg viewBox="0 0 24 24" class="h-9 w-9 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="17 8 12 3 7 8"/>
                            <line x1="12" y1="3" x2="12" y2="15"/>
                        </svg>
                        <p class="text-sm text-slate-500">Перетягніть фото або <span class="cursor-pointer font-medium text-emerald-600 hover:underline">оберіть файли</span></p>
                        <p class="text-xs text-slate-400">PNG, JPG, WEBP — до 5 МБ кожне</p>
                    </div>

                    <input
                        ref="fileInput"
                        type="file"
                        accept="image/*"
                        multiple
                        class="hidden"
                        @change="onFilesSelected"
                    />
                </div>

                <!-- Map -->
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">
                        Координати <span class="text-red-500">*</span>
                        <span class="ml-2 font-normal text-slate-400">— клікніть на карту або введіть вручну</span>
                    </label>
                    <div ref="mapEl" class="h-64 w-full overflow-hidden rounded-xl border border-slate-200"></div>
                    <div class="mt-2 grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1 block text-xs text-slate-500">Широта</label>
                            <input v-model.number="form.latitude" type="number" step="0.0000001" placeholder="52.1234567"
                                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100"
                                @change="moveMarkerFromInputs" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs text-slate-500">Довгота</label>
                            <input v-model.number="form.longitude" type="number" step="0.0000001" placeholder="19.1234567"
                                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100"
                                @change="moveMarkerFromInputs" />
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Опис</label>
                    <textarea v-model="form.description" rows="3" placeholder="Короткий опис озера..."
                        class="w-full resize-none rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100"></textarea>
                </div>

                <!-- Region + Address -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Регіон</label>
                        <input v-model="form.region" type="text" placeholder="Мазовецьке воєводство"
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Адреса</label>
                        <input v-model="form.address" type="text" placeholder="вул. Рибацька 1"
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100" />
                    </div>
                </div>

                <!-- Fish + Area + Depth -->
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Риба</label>
                        <input v-model="form.fish_species" type="text" placeholder="Короп, Лящ, Щука"
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Площа (га)</label>
                        <input v-model.number="form.area_ha" type="number" min="0" placeholder="120"
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Глибина (м)</label>
                        <input v-model.number="form.max_depth_m" type="number" min="0" placeholder="12"
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100" />
                    </div>
                </div>

                <!-- Price + Permit -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Ціна (PLN/добу)</label>
                        <input v-model.number="form.price" type="number" min="0" step="0.01" placeholder="25.00"
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100" />
                    </div>
                    <div class="flex items-end pb-2">
                        <label class="flex cursor-pointer items-center gap-3">
                            <div class="relative">
                                <input v-model="form.permit_required" type="checkbox" class="sr-only peer" />
                                <div class="h-6 w-11 rounded-full bg-slate-200 peer-checked:bg-emerald-500 transition-colors"></div>
                                <div class="absolute left-1 top-1 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-5"></div>
                            </div>
                            <span class="text-sm font-medium text-slate-700">Потрібен дозвіл</span>
                        </label>
                    </div>
                </div>

                <!-- Admin info -->
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Ім'я адміна</label>
                        <input v-model="form.admin_name" type="text" placeholder="Іван Іваненко"
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Телефон</label>
                        <input v-model="form.admin_phone" type="text" placeholder="+48 123 456 789"
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Сайт</label>
                        <input v-model="form.admin_website" type="url" placeholder="https://..."
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100" />
                    </div>
                </div>

                <!-- Rules -->
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Правила</label>
                    <textarea v-model="form.rules" rows="3" placeholder="Правила риболовлі на озері..."
                        class="w-full resize-none rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100"></textarea>
                </div>

                <!-- Error -->
                <p v-if="error" class="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-600">{{ error }}</p>

            </div>

            <!-- Footer -->
            <div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4">
                <button type="button"
                    class="rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 transition"
                    @click="$emit('close')">
                    Скасувати
                </button>
                <button type="button"
                    class="rounded-xl bg-emerald-500 px-5 py-2.5 text-sm font-medium text-white hover:bg-emerald-600 disabled:opacity-50 transition"
                    :disabled="saving"
                    @click="save">
                    {{ saving ? 'Збереження...' : (isEdit ? 'Зберегти зміни' : 'Зберегти озеро') }}
                </button>
            </div>

        </div>
    </div>
</template>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, reactive, ref, watch } from 'vue';
import L from 'leaflet';
import { createLake, fetchAdminLake, updateLake } from '../../api/admin';

const props = defineProps({
    lake: { type: Object, default: null },
});

const emit = defineEmits(['close', 'saved']);

const isEdit = computed(() => !!props.lake);

const mapEl = ref(null);
const fileInput = ref(null);
const saving = ref(false);
const error = ref('');
const dragging = ref(false);

// Each entry: { id, file, preview }
const photos = ref([]);
let photoIdCounter = 0;

// Edit mode: { id, url, is_primary } from server
const existingPhotos = ref([]);
const deletedPhotoIds = ref([]);

const totalPhotoCount = computed(() => existingPhotos.value.length + photos.value.length);

const form = reactive({
    name: '',
    description: '',
    latitude: null,
    longitude: null,
    region: '',
    address: '',
    fish_species: '',
    area_ha: null,
    max_depth_m: null,
    price: null,
    permit_required: true,
    admin_name: '',
    admin_phone: '',
    admin_website: '',
    rules: '',
});

let map = null;
let marker = null;

const markerIcon = L.icon({
    iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
    iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
    shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41],
});

onMounted(async () => {
    await nextTick();
    map = L.map(mapEl.value, { center: [52.0, 19.0], zoom: 6 });
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap',
        maxZoom: 18,
    }).addTo(map);

    map.on('click', (e) => {
        const { lat, lng } = e.latlng;
        form.latitude = parseFloat(lat.toFixed(7));
        form.longitude = parseFloat(lng.toFixed(7));
        placeMarker(lat, lng);
    });

    if (isEdit.value) {
        await loadLake();
    } else {
        restoreDraft();
    }
});

// ── Draft persistence (create mode only) ─────────────────────────────────────
const DRAFT_KEY = 'admin_lake_draft';

function restoreDraft() {
    try {
        const raw = localStorage.getItem(DRAFT_KEY);
        if (!raw) return;
        const draft = JSON.parse(raw);
        Object.keys(form).forEach(key => {
            if (key in draft) form[key] = draft[key];
        });
        if (form.latitude && form.longitude) {
            placeMarker(form.latitude, form.longitude);
        }
    } catch {
        localStorage.removeItem(DRAFT_KEY);
    }
}

watch(form, () => {
    if (isEdit.value) return;
    localStorage.setItem(DRAFT_KEY, JSON.stringify(form));
}, { deep: true });

async function loadLake() {
    try {
        const { lake } = await fetchAdminLake(props.lake.id);
        form.name = lake.name || '';
        form.description = lake.description || '';
        form.latitude = lake.latitude !== null ? Number(lake.latitude) : null;
        form.longitude = lake.longitude !== null ? Number(lake.longitude) : null;
        form.region = lake.region || '';
        form.address = lake.address || '';
        form.fish_species = lake.fish_species || '';
        form.area_ha = lake.area_ha;
        form.max_depth_m = lake.max_depth_m;
        form.price = lake.price !== null ? Number(lake.price) : null;
        form.permit_required = !!lake.permit_required;
        form.admin_name = lake.admin_name || '';
        form.admin_phone = lake.admin_phone || '';
        form.admin_website = lake.admin_website || '';
        form.rules = lake.rules || '';
        existingPhotos.value = lake.photos || [];

        if (form.latitude && form.longitude) {
            placeMarker(form.latitude, form.longitude);
        }
    } catch {
        error.value = 'Не вдалося завантажити дані озера.';
    }
}

onUnmounted(() => {
    photos.value.forEach(p => URL.revokeObjectURL(p.preview));
    map?.remove();
    map = null;
});

function placeMarker(lat, lng) {
    if (marker) {
        marker.setLatLng([lat, lng]);
    } else {
        marker = L.marker([lat, lng], { icon: markerIcon }).addTo(map);
    }
    map.setView([lat, lng], Math.max(map.getZoom(), 10));
}

function moveMarkerFromInputs() {
    const lat = Number(form.latitude);
    const lng = Number(form.longitude);
    if (lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180 && lat !== 0 && lng !== 0) {
        placeMarker(lat, lng);
    }
}

function removeExistingPhoto(photo) {
    deletedPhotoIds.value.push(photo.id);
    existingPhotos.value = existingPhotos.value.filter(p => p.id !== photo.id);
}

function addFiles(files) {
    const remaining = 10 - totalPhotoCount.value;
    Array.from(files).slice(0, remaining).forEach(file => {
        if (!file.type.startsWith('image/')) return;
        photos.value.push({
            id: ++photoIdCounter,
            file,
            preview: URL.createObjectURL(file),
        });
    });
}

function onFilesSelected(e) {
    addFiles(e.target.files);
    e.target.value = '';
}

function onDrop(e) {
    dragging.value = false;
    addFiles(e.dataTransfer.files);
}

function removePhoto(idx) {
    URL.revokeObjectURL(photos.value[idx].preview);
    photos.value.splice(idx, 1);
}

async function save() {
    error.value = '';

    if (!form.name.trim()) { error.value = 'Введіть назву озера.'; return; }
    if (!form.latitude || !form.longitude) { error.value = 'Виберіть координати на карті.'; return; }

    saving.value = true;
    try {
        const fd = new FormData();
        fd.append('name', form.name.trim());
        fd.append('latitude', form.latitude);
        fd.append('longitude', form.longitude);
        fd.append('permit_required', form.permit_required ? '1' : '0');

        const optional = ['description', 'price', 'region', 'address', 'fish_species',
                          'area_ha', 'max_depth_m', 'admin_name', 'admin_phone', 'admin_website', 'rules'];
        optional.forEach(key => {
            if (form[key] !== null && form[key] !== '') fd.append(key, form[key]);
        });

        photos.value.forEach(p => fd.append('photos[]', p.file));

        let res;
        if (isEdit.value) {
            deletedPhotoIds.value.forEach(id => fd.append('deleted_photo_ids[]', id));
            res = await updateLake(props.lake.id, fd);
        } else {
            res = await createLake(fd);
            localStorage.removeItem(DRAFT_KEY);
        }
        emit('saved', res.lake, isEdit.value);
    } catch (e) {
        const errs = e.response?.data?.errors;
        if (errs) {
            error.value = Object.values(errs).flat().join(' ');
        } else {
            error.value = e.response?.data?.message || 'Помилка при збереженні.';
        }
    } finally {
        saving.value = false;
    }
}
</script>
