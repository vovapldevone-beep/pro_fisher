<template>
    <Teleport to="body">
        <Transition name="zoom">
            <div
                v-if="show"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm sm:p-6"
                @click.self="$emit('close')"
            >
                <div class="zoom-panel relative flex h-full w-full max-w-5xl flex-col overflow-hidden bg-white shadow-2xl sm:h-[88vh] sm:rounded-2xl md:flex-row">

                    <!-- Close button, pinned to the panel corner -->
                    <button
                        type="button"
                        class="absolute right-3 top-3 z-10 flex h-9 w-9 items-center justify-center rounded-full bg-black/45 text-white backdrop-blur-sm transition hover:bg-black/65 md:bg-slate-100/90 md:text-slate-500 md:hover:bg-slate-200 md:hover:text-slate-700"
                        :aria-label="t('modal.cancel')"
                        @click="$emit('close')"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <!-- Photo (left side on desktop). object-contain: never crop the shot,
                         letterbox it against the dark backing instead. -->
                    <div class="flex shrink-0 items-center justify-center bg-slate-900 md:h-full md:w-3/5">
                        <img
                            v-if="post.photo_url"
                            :src="post.photo_url"
                            :alt="post.fish_name"
                            class="max-h-[40vh] w-full object-contain sm:max-h-[50vh] md:h-full md:max-h-full"
                        />
                        <div v-else class="flex h-56 w-full items-center justify-center text-6xl sm:h-72 md:h-full">🐟</div>
                    </div>

                    <!-- Details column -->
                    <div class="flex min-h-0 flex-1 flex-col">

                    <!-- Post info. On desktop the close button sits in this column's
                         top-right corner, so the content starts below it. -->
                    <div class="flex-shrink-0 border-b border-slate-100 px-4 py-3 md:pt-14">
                        <!-- Author -->
                        <div v-if="post.user" class="mb-3 flex items-center gap-2">
                            <UserAvatar :user="post.user" size="sm" />
                            <span class="text-sm font-medium text-slate-700">{{ post.user.name }}</span>
                        </div>

                        <!-- Fish + weight -->
                        <div class="flex items-baseline justify-between">
                            <h3 class="text-lg font-bold text-slate-900">{{ post.fish_name }}</h3>
                            <span v-if="post.weight" class="text-slate-600">{{ post.weight }} кг</span>
                        </div>

                        <!-- Location + date -->
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <LocationBadge v-if="locationBadge" :label="locationBadge.label" :url="locationBadge.url" />
                            <span v-if="post.caught_at || post.created_at" class="text-xs text-slate-400">
                                {{ formatDate(post.caught_at || post.created_at) }}
                            </span>
                        </div>

                        <!-- Likes + comments count -->
                        <div class="mt-2 flex items-center gap-4 text-sm">
                            <span class="flex items-center gap-1 text-red-400">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                </svg>
                                {{ t('post.likes', { n: post.likes_count ?? 0 }) }}
                            </span>
                            <span class="flex items-center gap-1 text-slate-400">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                </svg>
                                {{ t('post.comments', { n: comments.length }) }}
                            </span>
                        </div>

                        <!-- Notes -->
                        <p v-if="post.notes" class="mt-2 text-sm italic text-slate-600">{{ post.notes }}</p>
                    </div>

                    <!-- Comments list -->
                    <div ref="commentsEl" class="flex-1 space-y-3 overflow-y-auto px-4 py-3">
                        <div v-if="loadingComments" class="py-6 text-center text-sm text-slate-400">
                            {{ t('common.loading') }}
                        </div>
                        <div v-else-if="!comments.length" class="py-6 text-center text-sm text-slate-400">
                            {{ t('post.noComments') }}
                        </div>
                        <div v-for="comment in comments" :key="comment.id" class="flex gap-2">
                            <UserAvatar :user="comment.user" size="sm" />
                            <div class="flex-1">
                                <div
                                    class="rounded-xl px-3 py-2"
                                    :class="String(comment.user?.id) === String(authStore.user?.id)
                                        ? 'bg-emerald-500/15'
                                        : 'bg-slate-50'"
                                >
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
                                :placeholder="t('post.commentPlaceholder')"
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

                    </div><!-- /details column -->
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, toRef, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { fetchComments, postComment } from '../../api/comments';
import { useScrollLock } from '../../composables/useScrollLock';
import { useAuthStore } from '../../stores/auth';
import LocationBadge from '../shared/LocationBadge.vue';
import UserAvatar from '../shared/UserAvatar.vue';

const { t, locale } = useI18n();
const authStore = useAuthStore();

const props = defineProps({
    show: { type: Boolean, default: false },
    post: { type: Object, default: null },
});

const emit = defineEmits(['close', 'comment-added']);

useScrollLock(toRef(props, 'show'));

function onKeydown(e) {
    if (e.key === 'Escape' && props.show) emit('close');
}

onMounted(() => document.addEventListener('keydown', onKeydown));
onUnmounted(() => document.removeEventListener('keydown', onKeydown));

const comments = ref([]);
const loadingComments = ref(false);
const newComment = ref('');
const submitting = ref(false);
const commentsEl = ref(null);

const dateLocale = { uk: 'uk-UA', pl: 'pl-PL' };

function formatDate(date) {
    if (!date) return '';
    return new Date(date).toLocaleDateString(dateLocale[locale.value] || 'uk-UA');
}

function timeAgo(dateStr) {
    const diff = Date.now() - new Date(dateStr).getTime();
    const mins = Math.floor(diff / 60000);
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);
    if (mins < 1) return t('time.justNow');
    if (mins < 60) return t('time.minutesAgo', { mins });
    if (hours < 24) return t('time.hoursAgo', { hours });
    return t('time.daysAgo', { days });
}

const locationBadge = computed(() => {
    const p = props.post;
    if (!p) return null;
    if (p.location) {
        return {
            label: t('post.locationLabel', { name: p.location }),
            url: `https://www.google.com/maps/search/${encodeURIComponent(p.location)}`,
        };
    }
    if (p.lake?.latitude && p.lake?.longitude) {
        return {
            label: t('post.lakeLabel', { name: p.lake.name }),
            url: `https://www.google.com/maps?q=${p.lake.latitude},${p.lake.longitude}`,
        };
    }
    return null;
});

async function loadComments() {
    if (!props.post) return;
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
        emit('comment-added', props.post.id);
        await nextTick();
        commentsEl.value?.scrollTo({ top: commentsEl.value.scrollHeight, behavior: 'smooth' });
    } finally {
        submitting.value = false;
    }
}

watch(() => props.post?.id, (id) => {
    if (id) loadComments();
    else comments.value = [];
}, { immediate: true });
</script>

<style scoped>
/* Backdrop fades; the panel zooms up underneath it. */
.zoom-enter-active,
.zoom-leave-active {
    transition: opacity 0.22s ease;
}
.zoom-enter-from,
.zoom-leave-to {
    opacity: 0;
}

/* Overshoot on the way in makes the zoom feel springy rather than mechanical. */
.zoom-enter-active .zoom-panel {
    transition:
        transform 0.32s cubic-bezier(0.22, 1.2, 0.36, 1),
        opacity 0.32s ease;
}
.zoom-enter-from .zoom-panel {
    transform: scale(0.9) translateY(12px);
    opacity: 0;
}

/* Leaving is quicker and linear — a slow exit feels sluggish. */
.zoom-leave-active .zoom-panel {
    transition:
        transform 0.18s ease-in,
        opacity 0.18s ease-in;
}
.zoom-leave-to .zoom-panel {
    transform: scale(0.95);
    opacity: 0;
}

@media (prefers-reduced-motion: reduce) {
    .zoom-enter-active .zoom-panel,
    .zoom-leave-active .zoom-panel {
        transition: opacity 0.15s ease;
        transform: none;
    }
    .zoom-enter-from .zoom-panel,
    .zoom-leave-to .zoom-panel {
        transform: none;
    }
}
</style>
