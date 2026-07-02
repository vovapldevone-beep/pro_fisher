<template>
    <div>
        <h2 class="mb-4 text-xl font-bold text-slate-900">{{ t('catch.myCatches') }}</h2>

        <div v-if="loading" class="text-slate-500">{{ t('common.loading') }}</div>

        <div v-else-if="!catches.length" class="rounded-lg bg-slate-50 p-6 text-center text-slate-500">
            {{ t('catch.noCatches') }}
        </div>

        <div v-else class="space-y-3">
            <div
                v-for="catchItem in catches"
                :key="catchItem.id"
                class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
            >
                <img
                    v-if="catchItem.photo_url"
                    :src="catchItem.photo_url"
                    :alt="catchItem.fish_name"
                    class="h-16 w-16 rounded-lg object-cover"
                />
                <div
                    v-else
                    class="flex h-16 w-16 items-center justify-center rounded-lg bg-emerald-50 text-3xl"
                >
                    🐟
                </div>
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-slate-900">{{ catchItem.fish_name }}</p>
                    <p class="text-sm text-slate-500">
                        {{ catchItem.lake?.name }}
                        <span v-if="catchItem.weight"> · {{ catchItem.weight }} kg</span>
                    </p>
                    <p class="text-xs text-slate-400">{{ formatDate(catchItem.caught_at) }}</p>
                </div>
                <div class="flex gap-2">
                    <button
                        type="button"
                        class="rounded-lg px-3 py-1.5 text-sm font-medium text-emerald-700 hover:bg-emerald-50"
                        @click="$emit('edit', catchItem)"
                    >
                        {{ t('catch.edit') }}
                    </button>
                    <button
                        type="button"
                        class="rounded-lg px-3 py-1.5 text-sm font-medium text-red-600 hover:bg-red-50"
                        @click="$emit('delete', catchItem)"
                    >
                        {{ t('catch.delete') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n';

const { t, locale } = useI18n();

defineProps({
    catches: {
        type: Array,
        default: () => [],
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['edit', 'delete']);

const dateLocale = { uk: 'uk-UA', pl: 'pl-PL' };

function formatDate(date) {
    if (!date) return '';
    return new Date(date).toLocaleDateString(dateLocale[locale.value] || 'uk-UA');
}
</script>
