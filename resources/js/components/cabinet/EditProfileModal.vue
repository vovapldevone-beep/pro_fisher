<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            @click.self="$emit('close')"
        >
            <div class="w-full max-w-md rounded-2xl bg-white shadow-xl">
                <!-- Header -->
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <h2 class="font-semibold text-slate-900">{{ t('cabinet.editProfile') }}</h2>
                    <button
                        type="button"
                        class="flex h-7 w-7 items-center justify-center rounded-full text-slate-400 hover:bg-slate-100"
                        @click="$emit('close')"
                    >
                        <AppIcon name="close" class="h-4 w-4" />
                    </button>
                </div>

                <form class="px-6 py-5 space-y-5" @submit.prevent="handleSubmit">
                    <!-- Avatar -->
                    <div class="flex flex-col items-center gap-3">
                        <div class="relative">
                            <img
                                :src="avatarPreview || profile.avatar_url || defaultAvatar"
                                :alt="t('cabinet.editProfile')"
                                class="h-24 w-24 rounded-full object-cover border-2 border-slate-200"
                            />
                            <label
                                class="absolute bottom-0 right-0 flex h-7 w-7 cursor-pointer items-center justify-center rounded-full bg-emerald-600 text-white shadow hover:bg-emerald-700"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <input
                                    ref="fileInput"
                                    type="file"
                                    accept="image/*"
                                    class="sr-only"
                                    @change="onFileChange"
                                />
                            </label>
                        </div>
                        <p class="text-xs text-slate-400">{{ t('cabinet.avatarHint') }}</p>
                    </div>

                    <!-- Name -->
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">{{ t('cabinet.name') }}</label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            maxlength="255"
                            class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-400 focus:ring-1 focus:ring-emerald-400"
                        />
                    </div>

                    <!-- Error -->
                    <p v-if="error" class="text-sm text-red-500">{{ error }}</p>

                    <!-- Buttons -->
                    <div class="flex gap-3 pt-1">
                        <button
                            type="button"
                            class="flex-1 rounded-lg border border-slate-200 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50"
                            @click="$emit('close')"
                        >
                            {{ t('modal.cancel') }}
                        </button>
                        <button
                            type="submit"
                            :disabled="saving"
                            class="flex-1 rounded-lg bg-emerald-600 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-60"
                        >
                            {{ saving ? t('modal.saving') : t('common.save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { updateProfile } from '../../api/cabinet';
import AppIcon from '../shared/AppIcon.vue';

const { t } = useI18n();

const props = defineProps({
    show: { type: Boolean, default: false },
    profile: { type: Object, required: true },
});

const emit = defineEmits(['close', 'saved']);

const defaultAvatar = 'https://images.unsplash.com/photo-1504307651254-35680f356dfd?w=200';

const form = ref({ name: '' });
const avatarPreview = ref(null);
const avatarFile = ref(null);
const saving = ref(false);
const error = ref(null);

watch(() => props.show, (val) => {
    if (val) {
        form.value.name = props.profile.name ?? '';
        avatarPreview.value = null;
        avatarFile.value = null;
        error.value = null;
    }
});

function onFileChange(e) {
    const file = e.target.files[0];
    if (!file) return;
    avatarFile.value = file;
    avatarPreview.value = URL.createObjectURL(file);
}

async function handleSubmit() {
    if (!form.value.name.trim()) return;
    saving.value = true;
    error.value = null;
    try {
        const fd = new FormData();
        fd.append('name', form.value.name.trim());
        if (avatarFile.value) fd.append('avatar', avatarFile.value);
        const result = await updateProfile(fd);
        emit('saved', result.user);
        emit('close');
    } catch (e) {
        error.value = e.response?.data?.message ?? t('cabinet.saveError');
    } finally {
        saving.value = false;
    }
}
</script>
