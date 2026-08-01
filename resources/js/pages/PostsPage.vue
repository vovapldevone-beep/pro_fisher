<template>
    <div class="mx-auto max-w-[1600px] px-4 py-8 sm:px-6">
        <!-- Toolbar -->
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">{{ t('posts.title') }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ t('posts.subtitle') }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <!-- Filter tabs -->
                <template v-if="authStore.isAuthenticated">
                    <!-- "Всі" tab -->
                    <button
                        type="button"
                        class="rounded-full px-4 py-1.5 text-sm font-medium transition"
                        :class="activeFilter === 'all' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                        @click="activeFilter = 'all'; typeFilter = 'all'"
                    >
                        {{ t('posts.all') }}
                    </button>

                    <!-- Type segmented control -->
                    <div class="flex overflow-hidden rounded-full border border-slate-200 bg-slate-100 text-sm font-medium">
                        <button
                            type="button"
                            class="px-4 py-1.5 transition"
                            :class="typeFilter === 'post' ? 'bg-emerald-600 text-white' : 'text-slate-600 hover:bg-slate-200'"
                            @click="typeFilter = typeFilter === 'post' ? 'all' : 'post'; activeFilter = typeFilter === 'all' ? 'all' : 'none'"
                        >
                            {{ t('posts.postsFilter') }}
                        </button>
                        <span class="flex items-center text-slate-300">/</span>
                        <button
                            type="button"
                            class="px-4 py-1.5 transition"
                            :class="typeFilter === 'catch' ? 'bg-emerald-600 text-white' : 'text-slate-600 hover:bg-slate-200'"
                            @click="typeFilter = typeFilter === 'catch' ? 'all' : 'catch'; activeFilter = typeFilter === 'all' ? 'all' : 'none'"
                        >
                            {{ t('posts.catchesFilter') }}
                        </button>
                    </div>

                    <!-- Вподобані / Коментовані -->
                    <button
                        v-for="tab in filterTabs.slice(1)"
                        :key="tab.key"
                        type="button"
                        class="rounded-full px-4 py-1.5 text-sm font-medium transition"
                        :class="activeFilter === tab.key ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                        @click="activeFilter = tab.key"
                    >
                        {{ tab.label }}
                    </button>
                </template>

                <!-- Action button -->
                <button
                    v-if="authStore.isAuthenticated"
                    type="button"
                    class="rounded-lg border border-emerald-500 px-4 py-1.5 text-sm font-medium text-emerald-600 transition hover:bg-emerald-50"
                    @click="showAddPost = true"
                >
                    + {{ t('common.addPublication') }}
                </button>
            </div>
        </div>

        <div v-if="!loaded" class="py-24 text-center text-slate-500">
            {{ t('posts.loading') }}
        </div>
        <div v-else-if="!catches.length" class="py-24 text-center text-slate-500">
            <span v-if="activeFilter === 'liked'">{{ t('posts.emptyLiked') }}</span>
            <span v-else-if="activeFilter === 'commented'">{{ t('posts.emptyCommented') }}</span>
            <span v-else>{{ t('posts.empty') }}</span>
        </div>

        <template v-else>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <!-- relative isolate: lets a fish tuck behind the card (-z-10) without
                     falling behind the whole page -->
                <div
                    v-for="catchItem in catches"
                    :key="catchItem.id"
                    class="relative isolate"
                >
                    <PostCard
                        :catch-item="catchItem"
                        :selected="selectedPost?.id === catchItem.id"
                        @select="selectPost(catchItem)"
                        @like-changed="handleLikeChanged"
                    />
                    <HiddenFish
                        v-for="fish in fishOn(catchItem.id)"
                        :key="fish.id"
                        :anchor="fish.anchor"
                        @caught="catchFish(fish)"
                    />
                </div>
            </div>

            <!-- Infinite scroll sentinel -->
            <div ref="sentinel" class="mt-4 h-4"></div>

            <div v-if="loading" class="py-6 text-center text-sm text-slate-400">
                {{ t('posts.loading') }}
            </div>
        </template>

        <!-- Catch detail modal -->
        <CatchDetailModal
            :show="!!selectedPost"
            :post="selectedPost"
            @close="selectedPost = null"
            @comment-added="handleCommentAdded"
            @like-changed="handleLikeChanged"
            @edit="startEdit"
            @deleted="handleDeleted"
        />

        <EditCatchModal
            :show="!!editingPost"
            :post="editingPost"
            :lakes="lakesStore.lakes"
            @close="editingPost = null"
            @updated="handleUpdated"
        />

        <!-- Modals -->
        <AddCatchModal
            :show="showAddCatch"
            :lakes="lakesStore.lakes"
            :saving="catchesStore.saving"
            :error="catchesStore.error"
            @submit="handleAddCatch"
            @close="closeAddModals"
            @switch="closeAddModals(); showAddPost = true"
        />

        <AddPostModal
            :show="showAddPost"
            :saving="catchesStore.saving"
            :error="catchesStore.error"
            @submit="handleAddPost"
            @close="closeAddModals"
            @switch="closeAddModals(); showAddCatch = true"
        />
    </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { fetchPosts } from '../api/home';
import { useInfiniteScroll } from '../composables/useInfiniteScroll';
import AddCatchModal from '../components/cabinet/AddCatchModal.vue';
import AddPostModal from '../components/cabinet/AddPostModal.vue';
import CatchDetailModal from '../components/posts/CatchDetailModal.vue';
import EditCatchModal from '../components/posts/EditCatchModal.vue';
import PostCard from '../components/posts/PostCard.vue';
import HiddenFish from '../components/fish/HiddenFish.vue';
import { useAuthStore } from '../stores/auth';
import { useCatchesStore } from '../stores/catches';
import { useFishStore } from '../stores/fish';
import { useLakesStore } from '../stores/lakes';

const { t } = useI18n();
const authStore = useAuthStore();
const catchesStore = useCatchesStore();
const fishStore = useFishStore();
const lakesStore = useLakesStore();

const catches = ref([]);
// `loading` guards the request; `loaded` tells the first fetch from an empty result
const loading = ref(false);
const loaded = ref(false);
const selectedPost = ref(null);
const editingPost = ref(null);
const activeFilter = ref('all');
const showAddCatch = ref(false);
const showAddPost = ref(false);

const typeFilter = ref('all');

// `page` holds the *next* page to fetch, so the comparison must be inclusive
const page = ref(1);
const lastPage = ref(1);
const hasMore = computed(() => page.value <= lastPage.value);
const sentinel = ref(null);

// ─── Fish hunt ────────────────────────────────────────────────────────────────
// Fish are dripped onto posts as they load (initial batch + infinite scroll).
// There is no cap on how many are hidden — catching any counts toward 10. Once
// the hunt is complete, generation stops and any leftover fish are removed.
const placements = ref([]);
const decidedPosts = new Set(); // post ids already rolled, so we never re-roll them

const PLACE_CHANCE = 0.3;

// Peek variants tuck behind the card edge (-z-10); text variants sit over the
// card's bottom overlay (z-20). Position is otherwise random per placement.
const FISH_ANCHORS = [
    '-top-3 left-5 -z-10',
    '-top-3 right-6 -z-10',
    '-left-3 top-10 -z-10',
    '-right-3 top-12 -z-10',
    '-bottom-3 right-10 -z-10',
    '-bottom-3 left-8 -z-10',
    'bottom-3 left-3 z-20',
    'bottom-11 right-4 z-20',
];

function placeFishOn(post) {
    placements.value.push({
        id: `fish-${post.id}-${Math.random().toString(36).slice(2, 7)}`,
        postId: post.id,
        anchor: FISH_ANCHORS[Math.floor(Math.random() * FISH_ANCHORS.length)],
    });
}

// Hide fish among a freshly loaded batch of posts. No cap — but nothing new once
// the hunt is done.
function topUpFish(batch) {
    if (! fishStore.loaded || fishStore.completed) return;

    const fresh = batch.filter((p) => ! decidedPosts.has(p.id));
    fresh.forEach((p) => decidedPosts.add(p.id));

    for (const post of fresh) {
        if (Math.random() < PLACE_CHANCE) placeFishOn(post);
    }
}

function resetFish() {
    placements.value = [];
    decidedPosts.clear();
}

function fishOn(postId) {
    return placements.value.filter((f) => f.postId === postId);
}

function catchFish(fish) {
    placements.value = placements.value.filter((f) => f.id !== fish.id);
    fishStore.recordFind();
}

// The 10th fish (caught anywhere — feed or modal) ends the hunt: sweep away any
// leftover fish still hiding in the feed.
watch(() => fishStore.completed, (done) => {
    if (done) placements.value = [];
});

const filterTabs = computed(() => [
    { key: 'all', label: t('posts.all') },
    { key: 'liked', label: t('posts.liked') },
    { key: 'commented', label: t('posts.commented') },
]);

// Both filters are applied server-side, so only matching rows are ever downloaded
function activeParams() {
    return {
        ...(typeFilter.value !== 'all' ? { type: typeFilter.value } : {}),
        ...(['liked', 'commented'].includes(activeFilter.value) ? { filter: activeFilter.value } : {}),
    };
}

// Bumped on every reset; a response from an older request is discarded, so
// switching filters mid-flight cannot paint stale cards.
let requestId = 0;

async function loadPosts(reset = false) {
    if (!reset && loading.value) return;
    if (!reset && !hasMore.value && catches.value.length) return;

    const id = reset ? ++requestId : requestId;

    loading.value = true;
    try {
        const res = await fetchPosts({ page: page.value, ...activeParams() });
        if (id !== requestId) return;

        catches.value = reset ? res.data : [...catches.value, ...res.data];
        lastPage.value = res.meta.last_page;
        page.value = res.meta.current_page + 1;

        // Hide fish among the newly appended posts (initial batch is handled by
        // the caller once fish progress is known)
        if (! reset) topUpFish(res.data);
    } finally {
        if (id === requestId) {
            loading.value = false;
            loaded.value = true;
        }
    }
}

async function reloadFeed() {
    catches.value = [];
    resetFish();
    page.value = 1;
    lastPage.value = 1;
    loaded.value = false;
    await loadPosts(true);
    // The feed is a different set now — re-hide the fish among it
    topUpFish(catches.value);
}

// A filter change means a different result set — start the feed over
watch([activeFilter, typeFilter], reloadFeed);

useInfiniteScroll(sentinel, {
    loading,
    hasMore,
    // Only ever appends; the first page comes from onMounted / reloadFeed
    onLoad: () => catches.value.length && loadPosts(),
});

onMounted(async () => {
    await Promise.all([loadPosts(true), lakesStore.loadLakes()]);
    // Fish placement needs the progress; the initial feed batch is already loaded
    if (!fishStore.loaded) await fishStore.fetchProgress();
    resetFish();
    topUpFish(catches.value);
});

function selectPost(catchItem) {
    selectedPost.value = selectedPost.value?.id === catchItem.id ? null : catchItem;
}

function handleLikeChanged({ id, liked, likes_count }) {
    const post = catches.value.find((c) => c.id === id);
    if (post) {
        post.likes_count = likes_count;
        post.is_liked = liked;
    }
    if (selectedPost.value?.id === id) {
        selectedPost.value = { ...selectedPost.value, likes_count, is_liked: liked };
    }
}

function handleCommentAdded(postId) {
    const post = catches.value.find((c) => c.id === postId);
    if (post) post.is_commented = true;
}

// ─── Owner actions ────────────────────────────────────────────────────────────

function startEdit(post) {
    selectedPost.value = null;
    editingPost.value = post;
}

function handleUpdated(updated) {
    const index = catches.value.findIndex((c) => c.id === updated.id);
    if (index !== -1) catches.value[index] = { ...catches.value[index], ...updated };
}

function handleDeleted(id) {
    catches.value = catches.value.filter((c) => c.id !== id);
}

// Keep the modal open when publishing fails — the message is in
// catchesStore.error, and the user should not have to retype the post.
async function handleAddCatch(formData) {
    try {
        await catchesStore.addCatch(formData);
    } catch {
        return;
    }
    closeAddModals();
    reloadFeed(); // the new record belongs on page 1
}

async function handleAddPost(formData) {
    try {
        await catchesStore.addCatch(formData);
    } catch {
        return;
    }
    closeAddModals();
    reloadFeed();
}

function closeAddModals() {
    showAddCatch.value = false;
    showAddPost.value = false;
    catchesStore.error = ''; // otherwise it greets the user again on the next open
}
</script>

