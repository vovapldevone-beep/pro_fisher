<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            @click.self="$emit('close')"
        >
            <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto">
                <CatchForm
                    :lakes="lakes"
                    :saving="saving"
                    @submit="$emit('submit', $event)"
                    @cancel="$emit('close')"
                />
                <button
                    type="button"
                    class="mt-2 w-full rounded-lg bg-white py-2 text-sm text-slate-600 hover:bg-slate-50"
                    @click="$emit('close')"
                >
                    Закрити
                </button>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import CatchForm from '../catches/CatchForm.vue';

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    lakes: {
        type: Array,
        default: () => [],
    },
    saving: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['submit', 'close']);
</script>
