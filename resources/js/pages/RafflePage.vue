<template>
    <div class="min-h-full bg-slate-50 py-8">
        <div class="mx-auto max-w-2xl px-4 sm:px-6">
            <button
                type="button"
                class="mb-6 flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-emerald-700"
                @click="$router.back()"
            >
                <AppIcon name="chevron-left" class="h-4 w-4" />
                {{ t('common.viewAll') }}
            </button>

            <RaffleCard />
        </div>
    </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n';
import RaffleCard from '../components/fish/RaffleCard.vue';
import AppIcon from '../components/shared/AppIcon.vue';

const { t } = useI18n();
</script>
