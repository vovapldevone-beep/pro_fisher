<template>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <!-- Hero -->
        <div class="bg-gradient-to-br from-emerald-500 to-emerald-700 px-6 py-8 text-center text-white sm:px-8">
            <div class="text-5xl">🎣</div>
            <h2 class="mt-3 text-2xl font-bold">{{ t('raffle.title') }}</h2>
            <p class="mx-auto mt-2 max-w-md text-sm text-white/90">{{ t('raffle.subtitle') }}</p>
        </div>

        <div class="space-y-6 p-6 sm:p-8">
            <!-- Status -->
            <div
                class="rounded-xl border p-4"
                :class="fishStore.completed
                    ? 'border-emerald-200 bg-emerald-50'
                    : 'border-slate-200 bg-slate-50'"
            >
                <template v-if="fishStore.completed">
                    <p class="flex items-center gap-2 font-semibold text-emerald-700">
                        <span class="text-lg">✅</span>
                        {{ t('raffle.participating') }}
                    </p>
                </template>
                <template v-else>
                    <div class="mb-2 flex items-center justify-between text-sm">
                        <span class="font-medium text-slate-700">{{ t('raffle.progressLabel') }}</span>
                        <span class="font-semibold text-slate-900">{{ fishStore.found }} / {{ fishStore.total }} 🐟</span>
                    </div>
                    <div class="h-2 w-full overflow-hidden rounded-full bg-slate-200">
                        <div
                            class="h-full rounded-full bg-emerald-500 transition-all duration-500"
                            :style="{ width: progressPercent + '%' }"
                        />
                    </div>
                </template>
            </div>

            <!-- How to participate -->
            <div>
                <h3 class="mb-3 font-bold text-slate-900">{{ t('raffle.howTitle') }}</h3>
                <ol class="space-y-2">
                    <li v-for="(step, i) in steps" :key="i" class="flex gap-3 text-sm text-slate-600">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-700">
                            {{ i + 1 }}
                        </span>
                        <span class="pt-0.5">{{ step }}</span>
                    </li>
                </ol>
            </div>

            <!-- Prize -->
            <div class="rounded-xl bg-slate-50 p-4">
                <h3 class="mb-1 font-bold text-slate-900">{{ t('raffle.prizeTitle') }}</h3>
                <p class="text-sm text-slate-600">{{ t('raffle.prize') }}</p>
            </div>

            <p class="text-xs leading-relaxed text-slate-400">{{ t('raffle.terms') }}</p>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { useFishStore } from '../../stores/fish';

const { t } = useI18n();
const fishStore = useFishStore();

const steps = computed(() => [t('raffle.step1'), t('raffle.step2'), t('raffle.step3')]);

const progressPercent = computed(() =>
    fishStore.total ? Math.round((fishStore.found / fishStore.total) * 100) : 0
);

onMounted(() => {
    if (!fishStore.loaded) fishStore.fetchProgress();
});
</script>
