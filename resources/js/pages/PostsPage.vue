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

                <!-- Action buttons -->
                <template v-if="authStore.isAuthenticated">
                    <button
                        type="button"
                        class="rounded-lg border border-slate-300 px-4 py-1.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                        @click="showAddCatch = true"
                    >
                        {{ t('posts.addCatch') }}
                    </button>
                    <button
                        type="button"
                        class="rounded-lg border border-blue-400 px-4 py-1.5 text-sm font-medium text-blue-600 transition hover:bg-blue-50"
                        @click="showAddPost = true"
                    >
                        {{ t('posts.addPost') }}
                    </button>
                </template>
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
                <PostCard
                    v-for="catchItem in catches"
                    :key="catchItem.id"
                    :catch-item="catchItem"
                    :selected="selectedPost?.id === catchItem.id"
                    @select="selectPost(catchItem)"
                    @like-changed="handleLikeChanged"
                />
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
            @submit="handleAddCatch"
            @close="showAddCatch = false"
        />

        <AddPostModal
            :show="showAddPost"
            :saving="catchesStore.saving"
            @submit="handleAddPost"
            @close="showAddPost = false"
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
import { useAuthStore } from '../stores/auth';
import { useCatchesStore } from '../stores/catches';
import { useLakesStore } from '../stores/lakes';

const { t } = useI18n();
const authStore = useAuthStore();
const catchesStore = useCatchesStore();
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
    } finally {
        if (id === requestId) {
            loading.value = false;
            loaded.value = true;
        }
    }
}

function reloadFeed() {
    catches.value = [];
    page.value = 1;
    lastPage.value = 1;
    loaded.value = false;
    loadPosts(true);
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

async function handleAddCatch(formData) {
    await catchesStore.addCatch(formData);
    showAddCatch.value = false;
    reloadFeed(); // the new record belongs on page 1
}

async function handleAddPost(formData) {
    await catchesStore.addCatch(formData);
    showAddPost.value = false;
    reloadFeed();
}
</script>

