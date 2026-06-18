<template>
    <div class="mx-auto max-w-[1600px] px-4 py-8 sm:px-6">
        <!-- Toolbar -->
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Пости</h1>
                <p class="mt-1 text-sm text-slate-500">Улови рибалок спільноти</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <!-- Filter tabs (auth only) -->
                <template v-if="authStore.isAuthenticated">
                    <button
                        v-for="tab in filterTabs"
                        :key="tab.key"
                        type="button"
                        class="rounded-full px-4 py-1.5 text-sm font-medium transition"
                        :class="activeFilter === tab.key
                            ? 'bg-emerald-600 text-white'
                            : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
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
                        + Улов
                    </button>
                    <button
                        type="button"
                        class="rounded-lg border border-blue-400 px-4 py-1.5 text-sm font-medium text-blue-600 transition hover:bg-blue-50"
                        @click="showAddPost = true"
                    >
                        + Пост
                    </button>
                </template>
            </div>
        </div>

        <div v-if="loading" class="py-24 text-center text-slate-500">Завантаження...</div>
        <div v-else-if="!filteredCatches.length" class="py-24 text-center text-slate-500">
            <span v-if="activeFilter === 'liked'">Ви ще не вподобали жодного посту</span>
            <span v-else-if="activeFilter === 'commented'">Ви ще не коментували жодного посту</span>
            <span v-else>Ще немає постів</span>
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

        <!-- Comment sidebar overlay -->
        <Transition name="slide">
            <div
                v-if="selectedPost"
                class="fixed right-4 z-50 w-96"
                style="top: 65px; height: calc(100vh - 65px - 1rem)"
            >
                <PostCommentSidebar
                    :post="selectedPost"
                    @close="selectedPost = null"
                    @comment-added="handleCommentAdded"
                />
            </div>
        </Transition>

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
import api from '../api/client';
import AddCatchModal from '../components/cabinet/AddCatchModal.vue';
import AddPostModal from '../components/cabinet/AddPostModal.vue';
import PostCard from '../components/posts/PostCard.vue';
import PostCommentSidebar from '../components/posts/PostCommentSidebar.vue';
import { useAuthStore } from '../stores/auth';
import { useCatchesStore } from '../stores/catches';
import { useLakesStore } from '../stores/lakes';

const authStore = useAuthStore();
const catchesStore = useCatchesStore();
const lakesStore = useLakesStore();

const catches = ref([]);
const loading = ref(true);
const selectedPost = ref(null);
const activeFilter = ref('all');
const showAddCatch = ref(false);
const showAddPost = ref(false);

const filterTabs = [
    { key: 'all', label: 'Всі' },
    { key: 'liked', label: '♥ Вподобані' },
    { key: 'commented', label: '💬 Коментовані' },
];

const filteredCatches = computed(() => {
    if (activeFilter.value === 'liked') return catches.value.filter((c) => c.is_liked);
    if (activeFilter.value === 'commented') return catches.value.filter((c) => c.is_commented);
    return catches.value;
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

<style scoped>
.slide-enter-active,
.slide-leave-active {
    transition: transform 0.25s ease, opacity 0.25s ease;
}
.slide-enter-from,
.slide-leave-to {
    transform: translateX(1.5rem);
    opacity: 0;
}
</style>
