<template>
    <Teleport to="body">
        <Transition name="zoom">
            <div
                v-if="show"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm sm:p-6"
                @click.self="$emit('close')"
            >
                <div class="zoom-panel relative flex h-full w-full max-w-5xl flex-col overflow-hidden bg-white shadow-2xl sm:h-[88vh] sm:rounded-2xl md:flex-row">

                    <!-- Hidden fish: a 10% chance on each open, if the hunt is unfinished -->
                    <HiddenFish
                        v-if="modalFishAnchor"
                        :anchor="modalFishAnchor"
                        @caught="catchModalFish"
                    />

                    <!-- Owner actions, left of the close button -->
                    <div v-if="isOwner" ref="menuRoot" class="absolute right-14 top-3 z-10">
                        <button
                            type="button"
                            class="flex h-9 w-9 items-center justify-center rounded-full bg-black/45 text-white backdrop-blur-sm transition hover:bg-black/65 md:bg-slate-100/90 md:text-slate-500 md:hover:bg-slate-200 md:hover:text-slate-700"
                            :aria-label="t('post.actions')"
                            @click.stop="menuOpen = !menuOpen"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/>
                            </svg>
                        </button>

                        <div
                            v-if="menuOpen"
                            class="absolute right-0 top-11 w-44 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 shadow-xl"
                        >
                            <button
                                type="button"
                                class="flex w-full items-center gap-2.5 px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50"
                                @click="startEdit"
                            >
                                <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                {{ t('catch.edit') }}
                            </button>
                            <button
                                type="button"
                                class="flex w-full items-center gap-2.5 px-4 py-2.5 text-left text-sm text-red-600 transition hover:bg-red-50 disabled:opacity-50"
                                :disabled="deleting"
                                @click="handleDelete"
                            >
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                {{ deleting ? t('modal.saving') : t('catch.delete') }}
                            </button>
                        </div>
                    </div>

                    <!-- Close button, pinned to the panel corner -->
                    <button
                        type="button"
                        class="absolute right-3 top-3 z-10 flex h-9 w-9 items-center justify-center rounded-full bg-black/45 text-white backdrop-blur-sm transition hover:bg-black/65 md:bg-slate-100/90 md:text-slate-500 md:hover:bg-slate-200 md:hover:text-slate-700"
                        :aria-label="t('modal.cancel')"
                        @click="$emit('close')"
                    >
                        <AppIcon name="close" class="h-4 w-4" />
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
                                <AppIcon name="heart" class="h-4 w-4" fill="currentColor" />
                                {{ t('post.likes', { n: post.likes_count ?? 0 }) }}
                            </span>
                            <span class="flex items-center gap-1 text-slate-400">
                                <AppIcon name="comment" class="h-4 w-4" />
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
                                <AppIcon name="send" class="h-4 w-4" />
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
import { deleteCatch } from '../../api/catches';
import { fetchComments, postComment } from '../../api/comments';
import { useScrollLock } from '../../composables/useScrollLock';
import { useAuthStore } from '../../stores/auth';
import { useFishStore } from '../../stores/fish';
import HiddenFish from '../fish/HiddenFish.vue';
import LocationBadge from '../shared/LocationBadge.vue';
import UserAvatar from '../shared/UserAvatar.vue';
import AppIcon from '../shared/AppIcon.vue';

const { t, locale } = useI18n();
const authStore = useAuthStore();
const fishStore = useFishStore();

const props = defineProps({
    show: { type: Boolean, default: false },
    post: { type: Object, default: null },
});

const emit = defineEmits(['close', 'comment-added', 'edit', 'deleted']);

useScrollLock(toRef(props, 'show'));

// ─── Hidden fish (10% per open) ───────────────────────────────────────────────

const FISH_CHANCE = 0.10;

// Spots that dodge the close button (top-right) and the comment input (bottom)
const FISH_ANCHORS = [
    'top-1/4 left-6 z-30',
    'top-1/2 left-1/4 z-30',
    'top-2/3 left-10 z-30',
    'bottom-28 right-8 z-30',
    'top-1/3 right-1/4 z-30',
    'bottom-1/3 right-10 z-30',
];

const modalFishAnchor = ref(null);

function maybeSpawnFish() {
    modalFishAnchor.value = null;
    if (fishStore.remaining > 0 && Math.random() < FISH_CHANCE) {
        modalFishAnchor.value = FISH_ANCHORS[Math.floor(Math.random() * FISH_ANCHORS.length)];
    }
}

function catchModalFish() {
    modalFishAnchor.value = null;
    fishStore.recordFind();
}

// Roll once each time a post is opened (or swapped without closing)
watch(() => (props.show ? props.post?.id : null), (id) => {
    if (id) maybeSpawnFish();
    else modalFishAnchor.value = null;
});

// ─── Owner actions ────────────────────────────────────────────────────────────

const menuOpen = ref(false);
const menuRoot = ref(null);
const deleting = ref(false);

// `user_id` is always in CatchResource; the `user` relation is not always loaded
const isOwner = computed(() =>
    !!props.post && String(props.post.user_id) === String(authStore.user?.id)
);

function startEdit() {
    menuOpen.value = false;
    emit('edit', props.post);
}

async function handleDelete() {
    if (deleting.value) return;

    const question = props.post.type === 'post' ? t('post.deleteConfirm') : t('catch.deleteConfirm');
    if (!confirm(question)) return;

    deleting.value = true;
    try {
        await deleteCatch(props.post.id);
        menuOpen.value = false;
        emit('deleted', props.post.id);
        emit('close');
    } finally {
        deleting.value = false;
    }
}

function onPointerDown(e) {
    if (menuOpen.value && menuRoot.value && !menuRoot.value.contains(e.target)) {
        menuOpen.value = false;
    }
}

// A reopened modal must never inherit the previous post's open menu
watch(() => props.show, (show) => {
    if (!show) menuOpen.value = false;
});

// ─── Lifecycle ────────────────────────────────────────────────────────────────

function onKeydown(e) {
    if (e.key !== 'Escape' || !props.show) return;
    if (menuOpen.value) menuOpen.value = false;
    else emit('close');
}

onMounted(() => {
    document.addEventListener('keydown', onKeydown);
    document.addEventListener('pointerdown', onPointerDown);
});
onUnmounted(() => {
    document.removeEventListener('keydown', onKeydown);
    document.removeEventListener('pointerdown', onPointerDown);
});

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
