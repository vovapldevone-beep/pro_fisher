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

                    <!-- Search — above the tabs, searches everyone, not just friends -->
                    <div class="flex-shrink-0 px-4 py-3">
                        <div class="relative">
                            <svg
                                class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                                fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 11a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z" />
                            </svg>
                            <input
                                v-model="query"
                                type="search"
                                autocomplete="off"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-9 pr-9 text-sm text-slate-900 placeholder-slate-400 outline-none transition focus:border-slate-300 focus:bg-white"
                                :placeholder="t('friends.searchPlaceholder')"
                            />
                            <button
                                v-if="query"
                                type="button"
                                class="absolute right-2 top-1/2 flex h-6 w-6 -translate-y-1/2 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                                :aria-label="t('modal.cancel')"
                                @click="query = ''"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Tabs — hidden while searching, the results are global -->
                    <div v-if="!isSearching" class="flex flex-shrink-0 border-b border-slate-100">
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
                    <div v-else class="flex-shrink-0 border-b border-slate-100 px-5 pb-3 text-xs font-medium uppercase tracking-wide text-slate-400">
                        {{ t('friends.searchResults') }}
                    </div>

                    <!-- List -->
                    <div ref="listEl" class="min-h-0 flex-1 overflow-y-auto px-2 py-2">
                        <div v-if="current.loading && !current.items.length" class="py-16 text-center text-sm text-slate-400">
                            {{ t('common.loading') }}
                        </div>

                        <div v-else-if="!current.items.length" class="py-16 text-center text-sm text-slate-400">
                            {{ emptyMessage }}
                        </div>

                        <router-link
                            v-for="person in current.items"
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
                                <p v-if="person.username" class="truncate text-xs text-slate-400">@{{ person.username }}</p>
                                <p v-else-if="person.badge" class="truncate text-xs text-emerald-600">{{ person.badge }}</p>
                            </div>
                            <span
                                v-if="showsMutualTag(person)"
                                class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-500"
                            >
                                {{ t('friends.mutual') }}
                            </span>

                            <!-- Search results only: subscribe without leaving the modal.
                                 The row is a link, so the click must not navigate. -->
                            <button
                                v-if="isSearching && !person.is_following"
                                type="button"
                                class="shrink-0 rounded-full bg-emerald-600 px-3 py-1 text-xs font-semibold text-white transition hover:bg-emerald-700 disabled:opacity-50"
                                :disabled="pending.has(person.id)"
                                @click.prevent.stop="follow(person)"
                            >
                                {{ t('friends.follow') }}
                            </button>
                            <span
                                v-else-if="isSearching"
                                class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-500"
                            >
                                {{ t('friends.followed') }}
                            </span>

                            <svg class="h-4 w-4 shrink-0 text-slate-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6" />
                            </svg>
                        </router-link>

                        <!-- Infinite-scroll sentinel: pulls the next page into view -->
                        <div v-if="current.items.length && hasMore" ref="sentinelEl" class="py-4 text-center text-xs text-slate-400">
                            <span v-if="current.loading">{{ t('common.loading') }}</span>
                        </div>
                    </div>

                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { computed, reactive, ref, toRef, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { fetchFriends } from '../../api/cabinet';
import { followFisher, searchUsers } from '../../api/fishers';
import { useInfiniteScroll } from '../../composables/useInfiniteScroll';
import { useScrollLock } from '../../composables/useScrollLock';

const { t } = useI18n();

const props = defineProps({
    show: { type: Boolean, default: false },
});

defineEmits(['close']);

useScrollLock(toRef(props, 'show'));

const defaultAvatar = 'https://images.unsplash.com/photo-1504307651254-35680f356dfd?w=200';

/** Shortest query worth a round-trip — must match the server's own floor. */
const MIN_QUERY = 2;

const listEl = ref(null);
const sentinelEl = ref(null);
const activeTab = ref('following');
const query = ref('');
const counts = reactive({ following: 0, followers: 0 });

// One list per mode. `page` holds the *next* page to fetch, so `hasMore` below
// compares it inclusively against lastPage — see useInfiniteScroll.
const makeList = () => ({ items: [], page: 1, lastPage: 1, loading: false, loaded: false });
const lists = reactive({
    following: makeList(),
    followers: makeList(),
    search: makeList(),
});

const isSearching = computed(() => query.value.trim().length >= MIN_QUERY);
const mode = computed(() => (isSearching.value ? 'search' : activeTab.value));
const current = computed(() => lists[mode.value]);
const hasMore = computed(() => current.value.page <= current.value.lastPage);
const loading = computed(() => current.value.loading);

const tabs = computed(() => [
    { key: 'following', label: t('friends.following'), count: counts.following },
    { key: 'followers', label: t('friends.followers'), count: counts.followers },
]);

const emptyMessage = computed(() => {
    if (isSearching.value) return t('friends.searchEmpty');

    return activeTab.value === 'following'
        ? t('friends.emptyFollowing')
        : t('friends.emptyFollowers');
});

// Only meaningful among your followers, where `is_following` means the two of
// you follow each other. In search results it just means "you follow them",
// which the button/label pair below already says.
function showsMutualTag(person) {
    return mode.value === 'followers' && person.is_following;
}

const pending = reactive(new Set());

async function follow(person) {
    if (pending.has(person.id)) return;
    pending.add(person.id);

    // Optimistic: the row settles immediately, and a failure puts it back
    person.is_following = true;
    counts.following += 1;

    try {
        await followFisher(person.id);
        // The "following" tab is now stale — drop it so reopening refetches
        Object.assign(lists.following, makeList());
    } catch {
        person.is_following = false;
        counts.following -= 1;
    } finally {
        pending.delete(person.id);
    }
}

async function loadPage() {
    const key = mode.value;
    const list = lists[key];
    const term = query.value.trim();

    if (list.loading) return;
    list.loading = true;

    try {
        const data = key === 'search'
            ? await searchUsers({ q: term, page: list.page })
            : await fetchFriends({ tab: key, page: list.page });

        // The query moved on (or the tab did) while this request was in flight —
        // its rows belong to a list nobody is looking at any more.
        if (mode.value !== key || (key === 'search' && term !== query.value.trim())) return;

        list.items.push(...data.data);
        list.lastPage = data.meta.last_page;
        list.page = data.meta.current_page + 1;
        list.loaded = true;

        if (data.counts) Object.assign(counts, data.counts);
    } finally {
        list.loading = false;
    }
}

function resetSearch() {
    Object.assign(lists.search, makeList());
}

useInfiniteScroll(sentinelEl, { loading, hasMore, onLoad: loadPage, root: listEl });

// Debounced so typing a handle does not fire a request per keystroke
let searchTimer = null;
watch(query, () => {
    clearTimeout(searchTimer);
    resetSearch();

    if (!isSearching.value) return;

    searchTimer = setTimeout(() => {
        if (props.show && isSearching.value) loadPage();
    }, 350);
});

// Fetch a tab lazily the first time it is shown, then reuse what is already in
// hand — following/follower lists rarely change mid-session.
watch([() => props.show, mode], ([open]) => {
    if (!open) return;
    if (mode.value === 'search') return; // driven by the debounce above
    if (!current.value.loaded && !current.value.loading) loadPage();
}, { immediate: true });
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
