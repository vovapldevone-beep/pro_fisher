<template>
    <div class="mx-auto max-w-[1600px] px-4 py-8 sm:px-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-900">Пости</h1>
            <p class="mt-1 text-sm text-slate-500">Улови рибалок спільноти</p>
        </div>

        <div v-if="loading" class="py-24 text-center text-slate-500">Завантаження...</div>
        <div v-else-if="!catches.length" class="py-24 text-center text-slate-500">Ще немає уловів</div>

        <div v-else class="grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
            <PostCard
                v-for="catchItem in catches"
                :key="catchItem.id"
                :catch-item="catchItem"
                :selected="selectedPost?.id === catchItem.id"
                @select="selectPost(catchItem)"
                @like-changed="handleLikeChanged"
            />
        </div>

        <!-- Sidebar overlay -->
        <Transition name="slide">
            <div
                v-if="selectedPost"
                class="fixed right-4 z-50 w-96"
                style="top: 65px; height: calc(100vh - 65px - 1rem)"
            >
                <PostCommentSidebar
                    :post="selectedPost"
                    @close="selectedPost = null"
                />
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import api from '../api/client';
import PostCard from '../components/posts/PostCard.vue';
import PostCommentSidebar from '../components/posts/PostCommentSidebar.vue';

const catches = ref([]);
const loading = ref(true);
const selectedPost = ref(null);

onMounted(async () => {
    try {
        const { data } = await api.get('/posts');
        catches.value = data.data;
    } finally {
        loading.value = false;
    }
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
