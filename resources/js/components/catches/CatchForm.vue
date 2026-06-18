<template>
    <form class="space-y-4 rounded-2xl bg-white p-6 shadow-lg" @submit.prevent="handleSubmit">
        <h2 class="text-xl font-bold text-slate-900">
            {{ editingCatch ? 'Edytuj połów' : 'Dodaj połów' }}
        </h2>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Nazwa ryby</label>
            <input
                v-model="form.fish_name"
                type="text"
                required
                class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
            />
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Waga (kg)</label>
            <input
                v-model="form.weight"
                type="number"
                step="0.01"
                min="0"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
            />
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Data połowu</label>
            <input
                v-model="form.caught_at"
                type="date"
                required
                class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
            />
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Jezioro</label>
            <select
                v-model="form.lake_id"
                required
                class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
            >
                <option value="" disabled>Wybierz jezioro</option>
                <option v-for="lake in lakes" :key="lake.id" :value="lake.id">
                    {{ lake.name }}
                </option>
            </select>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Zdjęcie</label>
            <input
                type="file"
                accept="image/*"
                class="w-full text-sm text-slate-600"
                @change="handlePhotoChange"
            />
            <img
                v-if="photoPreview"
                :src="photoPreview"
                alt="Podgląd"
                class="mt-2 h-24 w-24 rounded-lg object-cover"
            />
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Notatki</label>
            <textarea
                v-model="form.notes"
                rows="3"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
            ></textarea>
        </div>

        <div class="flex gap-3">
            <button
                type="submit"
                :disabled="saving"
                class="flex-1 rounded-lg bg-emerald-600 py-2.5 font-medium text-white hover:bg-emerald-700 disabled:opacity-50"
            >
                {{ saving ? 'Zapisywanie...' : (editingCatch ? 'Zapisz' : 'Dodaj połów') }}
            </button>
            <button
                v-if="editingCatch"
                type="button"
                class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50"
                @click="$emit('cancel')"
            >
                Anuluj
            </button>
        </div>
    </form>
</template>

<script setup>
import { reactive, ref, watch } from 'vue';

const props = defineProps({
    lakes: {
        type: Array,
        default: () => [],
    },
    editingCatch: {
        type: Object,
        default: null,
    },
    saving: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['submit', 'cancel']);

const form = reactive({
    fish_name: '',
    weight: '',
    caught_at: new Date().toISOString().split('T')[0],
    lake_id: '',
    notes: '',
});

const photoFile = ref(null);
const photoPreview = ref(null);

watch(
    () => props.editingCatch,
    (catchItem) => {
        if (catchItem) {
            form.fish_name = catchItem.fish_name;
            form.weight = catchItem.weight || '';
            form.caught_at = catchItem.caught_at;
            form.lake_id = catchItem.lake?.id || '';
            form.notes = catchItem.notes || '';
            photoPreview.value = catchItem.photo_url;
            photoFile.value = null;
        } else {
            resetForm();
        }
    },
    { immediate: true }
);

function resetForm() {
    form.fish_name = '';
    form.weight = '';
    form.caught_at = new Date().toISOString().split('T')[0];
    form.lake_id = '';
    form.notes = '';
    photoFile.value = null;
    photoPreview.value = null;
}

function handlePhotoChange(event) {
    const file = event.target.files[0];
    if (file) {
        photoFile.value = file;
        photoPreview.value = URL.createObjectURL(file);
    }
}

function handleSubmit() {
    const formData = new FormData();
    formData.append('fish_name', form.fish_name);
    if (form.weight) formData.append('weight', form.weight);
    formData.append('caught_at', form.caught_at);
    formData.append('lake_id', form.lake_id);
    if (form.notes) formData.append('notes', form.notes);
    if (photoFile.value) formData.append('photo', photoFile.value);

    emit('submit', formData);
}
</script>
