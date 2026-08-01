<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            @click.self="$emit('close')"
        >
            <div class="flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
                <!-- Header: either a plain title, or two equal tab columns
                     (e.g. "Новий пост | Новий улов") that switch modal kinds -->
                <div
                    class="flex flex-shrink-0 items-center justify-between border-b border-slate-100"
                    :class="tabs ? 'pr-3' : 'px-6 py-4'"
                >
                    <div v-if="tabs" class="grid flex-1 grid-cols-2">
                        <button
                            v-for="tab in tabs"
                            :key="tab.key"
                            type="button"
                            class="-mb-px border-b-2 px-2 py-4 text-sm font-semibold transition-colors"
                            :class="tab.key === activeTab
                                ? 'border-emerald-500 text-emerald-600'
                                : 'border-transparent text-slate-400 hover:text-slate-600'"
                            @click="tab.key !== activeTab && $emit('tab', tab.key)"
                        >
                            {{ tab.label }}
                        </button>
                    </div>
                    <h2 v-else class="font-semibold text-slate-900">{{ title }}</h2>

                    <button
                        type="button"
                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-slate-400 hover:bg-slate-100"
                        @click="$emit('close')"
                    >
                        <AppIcon name="close" class="h-4 w-4" />
                    </button>
                </div>

                <!-- Body -->
                <form class="flex-1 overflow-y-auto" @submit.prevent="$emit('submit')">
                    <div class="space-y-4 px-6 py-5">
                        <slot />
                    </div>

                    <!-- Submit failure (rate limit, oversized photo, …) -->
                    <p v-if="error" class="mx-6 mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600">
                        {{ error }}
                    </p>

                    <!-- Footer -->
                    <div class="flex flex-shrink-0 gap-3 border-t border-slate-100 px-6 py-4">
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
                            {{ saving ? (savingLabel || t('modal.saving')) : submitLabel }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { toRef } from 'vue';
import { useI18n } from 'vue-i18n';
import { useScrollLock } from '../../composables/useScrollLock';
import AppIcon from '../shared/AppIcon.vue';

const { t } = useI18n();

const props = defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: '' },
    saving: { type: Boolean, default: false },
    submitLabel: { type: String, default: '' },
    savingLabel: { type: String, default: '' },
    error: { type: String, default: '' },
    // Optional header tabs: [{key, label}]. When set, replaces the title.
    tabs: { type: Array, default: null },
    activeTab: { type: String, default: '' },
});

defineEmits(['close', 'submit', 'tab']);

useScrollLock(toRef(props, 'show'));
</script>
