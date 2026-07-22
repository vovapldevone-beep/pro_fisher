<template>
    <Teleport to="body">
        <Transition name="raffle-zoom">
            <!-- z-[90]: above the header/detail modals (z-50), below the fireworks (z-[100]) -->
            <div
                v-if="show"
                class="fixed inset-0 z-[90] flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm"
                @click.self="$emit('close')"
            >
                <div class="raffle-panel relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl">
                    <button
                        type="button"
                        class="absolute right-3 top-3 z-10 flex h-9 w-9 items-center justify-center rounded-full bg-black/25 text-white backdrop-blur-sm transition hover:bg-black/45"
                        :aria-label="t('modal.cancel')"
                        @click="$emit('close')"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <RaffleCard />
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { toRef } from 'vue';
import { useI18n } from 'vue-i18n';
import { useScrollLock } from '../../composables/useScrollLock';
import RaffleCard from './RaffleCard.vue';

const { t } = useI18n();

const props = defineProps({
    show: { type: Boolean, default: false },
});

defineEmits(['close']);

useScrollLock(toRef(props, 'show'));
</script>

<style scoped>
.raffle-zoom-enter-active,
.raffle-zoom-leave-active {
    transition: opacity 0.22s ease;
}
.raffle-zoom-enter-from,
.raffle-zoom-leave-to {
    opacity: 0;
}

.raffle-zoom-enter-active .raffle-panel {
    transition:
        transform 0.32s cubic-bezier(0.22, 1.2, 0.36, 1),
        opacity 0.32s ease;
}
.raffle-zoom-enter-from .raffle-panel {
    transform: scale(0.92) translateY(10px);
    opacity: 0;
}

.raffle-zoom-leave-active .raffle-panel {
    transition:
        transform 0.16s ease-in,
        opacity 0.16s ease-in;
}
.raffle-zoom-leave-to .raffle-panel {
    transform: scale(0.96);
    opacity: 0;
}

@media (prefers-reduced-motion: reduce) {
    .raffle-zoom-enter-active .raffle-panel,
    .raffle-zoom-leave-active .raffle-panel,
    .raffle-zoom-enter-from .raffle-panel,
    .raffle-zoom-leave-to .raffle-panel {
        transition: opacity 0.15s ease;
        transform: none;
    }
}
</style>
