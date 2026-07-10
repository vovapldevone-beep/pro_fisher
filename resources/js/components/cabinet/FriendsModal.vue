<template>
    <Teleport to="body">
        <Transition name="zoom">
            <div
                v-if="show"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-0 backdrop-blur-sm sm:p-6"
                @click.self="$emit('close')"
            >
                <div class="zoom-panel flex h-full w-full max-w-md flex-col overflow-hidden bg-white shadow-2xl sm:h-[80vh] sm:rounded-2xl">

                    <!-- Header -->
                    <div class="flex flex-shrink-0 items-center justify-between border-b border-slate-100 px-5 py-4">
                        <h2 class="font-bold text-slate-900">{{ t('friends.title') }}</h2>
                        <button
                            type="button"
                            class="flex h-8 w-8 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                            :aria-label="t('modal.cancel')"
                            @click="$emit('close')"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Tabs -->
                    <div class="flex flex-shrink-0 border-b border-slate-100">
                        <button
                            v-for="tab in tabs"
                            :key="tab.key"
                            type="button"
                            class="flex-1 border-b-2 px-4 py-3 text-sm font-medium transition-colors"
                            :class="activeTab === tab.key
                                ? 'border-slate-900 text-slate-900'
                                : 'border-transparent text-slate-500 hover:text-slate-700'"
                            @click="activeTab = tab.key"
                        >
                            {{ tab.label }}
                            <span class="ml-1 text-xs text-slate-400">{{ tab.count }}</span>
                        </button>
                    </div>

                    <!-- List -->
                    <div class="min-h-0 flex-1 overflow-y-auto px-2 py-2">
                        <div v-if="loading" class="py-16 text-center text-sm text-slate-400">
                            {{ t('common.loading') }}
                        </div>

                        <div v-else-if="!currentList.length" class="py-16 text-center text-sm text-slate-400">
                            {{ activeTab === 'following' ? t('friends.emptyFollowing') : t('friends.emptyFollowers') }}
                        </div>

                        <router-link
                            v-for="person in currentList"
                            :key="person.id"
                            :to="`/fishers/${person.id}`"
                            class="flex items-center gap-3 rounded-xl px-3 py-2.5 transition hover:bg-slate-50 active:bg-slate-100"
                            @click="$emit('close')"
                        >
                            <img
                                :src="person.avatar_url || defaultAvatar"
                                :alt="person.name"
                                class="h-11 w-11 shrink-0 rounded-full object-cover"
                            />
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-slate-900">{{ person.name }}</p>
                                <p v-if="person.badge" class="truncate text-xs text-emerald-600">{{ person.badge }}</p>
                            </div>
                            <span
                                v-if="activeTab === 'followers' && person.is_following"
                                class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-500"
                            >
                                {{ t('friends.mutual') }}
                            </span>
                            <svg class="h-4 w-4 shrink-0 text-slate-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6" />
                            </svg>
                        </router-link>
                    </div>

                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { computed, ref, toRef, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { fetchFriends } from '../../api/cabinet';
import { useScrollLock } from '../../composables/useScrollLock';

const { t } = useI18n();

const props = defineProps({
    show: { type: Boolean, default: false },
});

defineEmits(['close']);

useScrollLock(toRef(props, 'show'));

const defaultAvatar = 'https://images.unsplash.com/photo-1504307651254-35680f356dfd?w=200';

const following = ref([]);
const followers = ref([]);
const loading = ref(false);
const loaded = ref(false);
const activeTab = ref('following');

const tabs = computed(() => [
    { key: 'following', label: t('friends.following'), count: following.value.length },
    { key: 'followers', label: t('friends.followers'), count: followers.value.length },
]);

const currentList = computed(() =>
    activeTab.value === 'following' ? following.value : followers.value
);

async function load() {
    loading.value = true;
    try {
        const data = await fetchFriends();
        following.value = data.following;
        followers.value = data.followers;
        loaded.value = true;
    } finally {
        loading.value = false;
    }
}

// Fetch lazily on first open, then reuse — the lists rarely change mid-session
watch(() => props.show, (open) => {
    if (open && !loaded.value) load();
});
</script>

<style scoped>
.zoom-enter-active,
.zoom-leave-active {
    transition: opacity 0.2s ease;
}
.zoom-enter-from,
.zoom-leave-to {
    opacity: 0;
}

.zoom-enter-active .zoom-panel {
    transition:
        transform 0.28s cubic-bezier(0.22, 1.2, 0.36, 1),
        opacity 0.28s ease;
}
.zoom-enter-from .zoom-panel {
    transform: scale(0.92) translateY(10px);
    opacity: 0;
}

.zoom-leave-active .zoom-panel {
    transition:
        transform 0.16s ease-in,
        opacity 0.16s ease-in;
}
.zoom-leave-to .zoom-panel {
    transform: scale(0.96);
    opacity: 0;
}

@media (prefers-reduced-motion: reduce) {
    .zoom-enter-active .zoom-panel,
    .zoom-leave-active .zoom-panel,
    .zoom-enter-from .zoom-panel,
    .zoom-leave-to .zoom-panel {
        transition: opacity 0.15s ease;
        transform: none;
    }
}
</style>
