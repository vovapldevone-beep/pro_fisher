<template>
    <div class="min-h-full bg-slate-50 py-8">
        <div class="mx-auto max-w-2xl px-4 sm:px-6">
            <button
                type="button"
                class="mb-6 flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-emerald-700"
                @click="$router.back()"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
                {{ t('common.viewAll') }}
            </button>

            <RaffleCard />
        </div>
    </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n';
import RaffleCard from '../components/fish/RaffleCard.vue';

const { t } = useI18n();
</script>
