<template>
    <ModalDialog
        :show="show"
        :title="t('catch.new')"
        :saving="saving"
        :submit-label="t('catch.add')"
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

        <!-- Lake -->
        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">{{ t('catch.lake') }}</label>
            <select
                v-model="form.lake_id"
                required
                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-400 focus:ring-1 focus:ring-emerald-400"
            >
                <option value="" disabled>{{ t('catch.lakePlaceholder') }}</option>
                <option v-for="lake in lakes" :key="lake.id" :value="lake.id">
                    {{ lake.name }}
                </option>
            </select>
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
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import ModalDialog from '../shared/ModalDialog.vue';

const { t } = useI18n();

const props = defineProps({
    show: { type: Boolean, default: false },
    lakes: { type: Array, default: () => [] },
    saving: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'submit']);

const form = ref({ fish_name: '', weight: '', caught_at: today(), lake_id: '', notes: '' });
const photoFile = ref(null);
const photoPreview = ref(null);

function today() {
    return new Date().toISOString().split('T')[0];
}

watch(() => props.show, (val) => {
    if (val) {
        form.value = { fish_name: '', weight: '', caught_at: today(), lake_id: '', notes: '' };
        photoFile.value = null;
        photoPreview.value = null;
    }
});

function onPhotoChange(e) {
    const file = e.target.files[0];
    if (!file) return;
    photoFile.value = file;
    photoPreview.value = URL.createObjectURL(file);
}

function handleSubmit() {
    const fd = new FormData();
    fd.append('fish_name', form.value.fish_name);
    fd.append('caught_at', form.value.caught_at);
    fd.append('lake_id', form.value.lake_id);
    if (form.value.weight) fd.append('weight', form.value.weight);
    if (form.value.notes) fd.append('notes', form.value.notes);
    if (photoFile.value) fd.append('photo', photoFile.value);
    emit('submit', fd);
}
</script>
