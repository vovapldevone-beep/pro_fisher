<template>
    <ModalDialog
        :show="show"
        :title="isCatch ? t('catch.editTitle') : t('post.editTitle')"
        :saving="saving"
        :submit-label="t('common.save')"
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
                    <AppIcon name="image" class="h-10 w-10" />
                    <span class="text-sm">{{ t('modal.photoHint') }}</span>
                </div>
            </div>
            <input ref="photoInput" type="file" accept="image/*" class="sr-only" @change="onPhotoChange" />
        </div>

        <!-- Catch-only fields -->
        <template v-if="isCatch">
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

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">{{ t('catch.place') }}</label>
                <LakeSelect
                    v-model="form.lake_id"
                    v-model:location="form.location"
                    :lakes="lakes"
                    :placeholder="t('catch.placePlaceholder')"
                    :error="placeError"
                    allow-custom
                />
            </div>
        </template>

        <!-- Post-only field -->
        <div v-else>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">{{ t('modal.location') }}</label>
            <input
                v-model="form.location"
                type="text"
                :placeholder="t('modal.locationPlaceholder')"
                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-400 focus:ring-1 focus:ring-emerald-400"
            />
        </div>

        <!-- Notes / description -->
        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">
                {{ isCatch ? t('modal.notes') : t('post.description') }}
            </label>
            <textarea
                v-model="form.notes"
                rows="3"
                maxlength="2000"
                :placeholder="isCatch ? t('catch.notesPlaceholder') : t('post.descriptionPlaceholder')"
                class="w-full resize-none rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-400 focus:ring-1 focus:ring-emerald-400"
            />
        </div>

        <p v-if="error" class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600">{{ error }}</p>
    </ModalDialog>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { updateCatch } from '../../api/catches';
import LakeSelect from '../shared/LakeSelect.vue';
import ModalDialog from '../shared/ModalDialog.vue';
import AppIcon from '../shared/AppIcon.vue';

const { t } = useI18n();

const props = defineProps({
    show: { type: Boolean, default: false },
    post: { type: Object, default: null },
    lakes: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'updated']);

const isCatch = computed(() => (props.post?.type ?? 'catch') === 'catch');

const form = ref({ fish_name: '', weight: '', caught_at: '', lake_id: '', location: '', notes: '' });
const photoFile = ref(null);
const photoPreview = ref(null);
const saving = ref(false);
const error = ref('');
const placeError = ref('');

watch(() => [props.show, props.post?.id], ([show]) => {
    if (!show || !props.post) return;

    const p = props.post;
    form.value = {
        fish_name: p.fish_name ?? '',
        weight: p.weight ?? '',
        caught_at: p.caught_at ?? '',
        lake_id: p.lake_id ?? '',
        location: p.location ?? '',
        notes: p.notes ?? '',
    };
    photoFile.value = null;
    photoPreview.value = p.photo_url ?? null;
    error.value = '';
    placeError.value = '';
}, { immediate: true });

watch(() => [form.value.lake_id, form.value.location], ([id, location]) => {
    if (id || location) placeError.value = '';
});

function onPhotoChange(e) {
    const file = e.target.files[0];
    if (!file) return;
    photoFile.value = file;
    photoPreview.value = URL.createObjectURL(file);
}

async function handleSubmit() {
    if (isCatch.value && !form.value.lake_id && !form.value.location.trim()) {
        placeError.value = t('catch.lakeOrLocationRequired');
        return;
    }

    saving.value = true;
    error.value = '';
    try {
        const fd = new FormData();

        if (isCatch.value) {
            fd.append('fish_name', form.value.fish_name);
            fd.append('caught_at', form.value.caught_at);
            if (form.value.weight !== '' && form.value.weight !== null) fd.append('weight', form.value.weight);
        }

        // Empty strings become null server-side (ConvertEmptyStringsToNull),
        // which is exactly how a lake gets swapped for a free-form location.
        fd.append('lake_id', form.value.lake_id ?? '');
        fd.append('location', form.value.location?.trim() ?? '');
        fd.append('notes', form.value.notes ?? '');
        if (photoFile.value) fd.append('photo', photoFile.value);

        const updated = await updateCatch(props.post.id, fd);
        emit('updated', updated);
        emit('close');
    } catch (e) {
        const errs = e.response?.data?.errors;
        error.value = errs
            ? Object.values(errs).flat().join(' ')
            : (e.response?.data?.message || t('cabinet.saveError'));
    } finally {
        saving.value = false;
    }
}
</script>
