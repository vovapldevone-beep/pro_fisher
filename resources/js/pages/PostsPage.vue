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

        <div v-if="loading" class="py-24 text-center text-slate-500">{{ t('posts.loading') }}</div>
        <div v-else-if="!filteredCatches.length" class="py-24 text-center text-slate-500">
            <span v-if="activeFilter === 'liked'">{{ t('posts.emptyLiked') }}</span>
            <span v-else-if="activeFilter === 'commented'">{{ t('posts.emptyCommented') }}</span>
            <span v-else>{{ t('posts.empty') }}</span>
        </div>

        <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <PostCard
                v-for="catchItem in filteredCatches"
                :key="catchItem.id"
                :catch-item="catchItem"
                :selected="selectedPost?.id === catchItem.id"
                @select="selectPost(catchItem)"
                @like-changed="handleLikeChanged"
            />
        </div>

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
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '../api/client';
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
const loading = ref(true);
const selectedPost = ref(null);
const editingPost = ref(null);
const activeFilter = ref('all');
const showAddCatch = ref(false);
const showAddPost = ref(false);

const typeFilter = ref('all');

const filterTabs = computed(() => [
    { key: 'all', label: t('posts.all') },
    { key: 'liked', label: t('posts.liked') },
    { key: 'commented', label: t('posts.commented') },
]);

const filteredCatches = computed(() => {
    let list = catches.value;
    if (typeFilter.value === 'post') list = list.filter((c) => c.type === 'post');
    if (typeFilter.value === 'catch') list = list.filter((c) => c.type === 'catch' || !c.type);
    if (activeFilter.value === 'liked') return list.filter((c) => c.is_liked);
    if (activeFilter.value === 'commented') return list.filter((c) => c.is_commented);
    return list;
});

async function loadPosts() {
    loading.value = true;
    try {
        const { data } = await api.get('/posts');
        catches.value = data.data;
    } finally {
        loading.value = false;
    }
}

onMounted(async () => {
    await Promise.all([loadPosts(), lakesStore.loadLakes()]);
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
    await loadPosts();
}

async function handleAddPost(formData) {
    await catchesStore.addCatch(formData);
    showAddPost.value = false;
    await loadPosts();
}
</script>

