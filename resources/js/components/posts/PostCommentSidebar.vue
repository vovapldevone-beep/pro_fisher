<template>
    <div class="flex h-full w-full flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl">
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <span class="font-semibold text-slate-800">Деталі улову</span>
            <button
                type="button"
                class="flex h-7 w-7 items-center justify-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                @click="$emit('close')"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Post photo -->
        <div class="relative flex-shrink-0">
            <img
                v-if="post.photo_url"
                :src="post.photo_url"
                :alt="post.fish_name"
                class="h-56 w-full object-cover"
            />
            <div v-else class="flex h-56 w-full items-center justify-center bg-slate-100 text-6xl">🐟</div>
        </div>

        <!-- Post info -->
        <div class="flex-shrink-0 border-b border-slate-100 px-4 py-3">
            <!-- Author -->
            <div v-if="post.user" class="mb-3 flex items-center gap-2">
                <div class="h-7 w-7 overflow-hidden rounded-full bg-emerald-500">
                    <img v-if="post.user.avatar_url" :src="post.user.avatar_url" class="h-full w-full object-cover" />
                    <span v-else class="flex h-full w-full items-center justify-center text-[10px] font-bold text-white">
                        {{ post.user.name?.slice(0,2).toUpperCase() }}
                    </span>
                </div>
                <span class="text-sm font-medium text-slate-700">{{ post.user.name }}</span>
            </div>

            <!-- Fish + weight -->
            <div class="flex items-baseline justify-between">
                <h3 class="text-lg font-bold text-slate-900">{{ post.fish_name }}</h3>
                <span v-if="post.weight" class="text-slate-600">{{ post.weight }} кг</span>
            </div>

            <!-- Lake + date -->
            <div class="mt-1 flex items-center justify-between text-sm text-slate-500">
                <span v-if="post.lake" class="flex items-center gap-1">
                    <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                    </svg>
                    {{ post.lake.name }}
                </span>
                <span>{{ formatDate(post.caught_at) }}</span>
            </div>

            <!-- Likes + comments -->
            <div class="mt-2 flex items-center gap-4 text-sm">
                <span class="flex items-center gap-1 text-red-400">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                    {{ post.likes_count ?? 0 }} лайків
                </span>
                <span class="flex items-center gap-1 text-slate-400">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                    {{ comments.length }} коментарів
                </span>
            </div>

            <!-- Notes -->
            <p v-if="post.notes" class="mt-2 text-sm text-slate-600 italic">{{ post.notes }}</p>
        </div>

        <!-- Comments list -->
        <div ref="commentsEl" class="flex-1 overflow-y-auto px-4 py-3 space-y-3">
            <div v-if="loadingComments" class="py-6 text-center text-sm text-slate-400">Завантаження...</div>
            <div v-else-if="!comments.length" class="py-6 text-center text-sm text-slate-400">Ще немає коментарів</div>
            <div v-for="comment in comments" :key="comment.id" class="flex gap-2">
                <div class="h-7 w-7 flex-shrink-0 overflow-hidden rounded-full bg-emerald-500">
                    <img v-if="comment.user.avatar_url" :src="comment.user.avatar_url" class="h-full w-full object-cover" />
                    <span v-else class="flex h-full w-full items-center justify-center text-[10px] font-bold text-white">
                        {{ comment.user.name?.slice(0,2).toUpperCase() }}
                    </span>
                </div>
                <div class="flex-1">
                    <div class="rounded-xl bg-slate-50 px-3 py-2">
                        <span class="text-xs font-semibold text-slate-700">{{ comment.user.name }}</span>
                        <p class="mt-0.5 text-sm text-slate-700">{{ comment.body }}</p>
                    </div>
                    <p class="mt-0.5 pl-3 text-[11px] text-slate-400">{{ timeAgo(comment.created_at) }}</p>
                </div>
            </div>
        </div>

        <!-- Comment input -->
        <div class="flex-shrink-0 border-t border-slate-100 px-4 py-3">
            <div class="flex gap-2">
                <input
                    v-model="newComment"
                    type="text"
                    placeholder="Написати коментар..."
                    maxlength="500"
                    class="flex-1 rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm outline-none focus:border-emerald-400 focus:ring-1 focus:ring-emerald-400"
                    @keydown.enter.prevent="submitComment"
                />
                <button
                    type="button"
                    :disabled="!newComment.trim() || submitting"
                    class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-emerald-600 text-white transition hover:bg-emerald-700 disabled:opacity-40"
                    @click="submitComment"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { nextTick, ref, watch } from 'vue';
import { fetchComments, postComment } from '../../api/comments';

const props = defineProps({
    post: { type: Object, required: true },
});

defineEmits(['close']);

const comments = ref([]);
const loadingComments = ref(true);
const newComment = ref('');
const submitting = ref(false);
const commentsEl = ref(null);

async function loadComments() {
    loadingComments.value = true;
    try {
        comments.value = await fetchComments(props.post.id);
    } finally {
        loadingComments.value = false;
    }
}

async function submitComment() {
    const body = newComment.value.trim();
    if (!body || submitting.value) return;
    submitting.value = true;
    try {
        const comment = await postComment(props.post.id, body);
        comments.value.push(comment);
        newComment.value = '';
        await nextTick();
        commentsEl.value?.scrollTo({ top: commentsEl.value.scrollHeight, behavior: 'smooth' });
    } finally {
        submitting.value = false;
    }
}

function formatDate(date) {
    if (!date) return '';
    return new Date(date).toLocaleDateString('uk-UA');
}

function timeAgo(dateStr) {
    const diff = Date.now() - new Date(dateStr).getTime();
    const mins = Math.floor(diff / 60000);
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);
    if (mins < 1) return 'щойно';
    if (mins < 60) return `${mins} хв тому`;
    if (hours < 24) return `${hours} год тому`;
    return `${days} дн. тому`;
}

watch(() => props.post.id, loadComments, { immediate: true });
</script>
