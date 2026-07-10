<template>
    <div class="min-h-full bg-slate-50 py-6">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">

            <!-- Loading skeleton -->
            <div v-if="loading" class="py-24 text-center text-slate-500">{{ t('fisher.loading') }}</div>

            <template v-else-if="fisher">

                <!-- Profile card -->
                <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-col gap-6 md:flex-row md:items-start">

                        <!-- Left: avatar + info + buttons -->
                        <div class="flex min-w-0 flex-1 gap-5">
                            <img
                                :src="fisher.profile.avatar_url || defaultAvatar"
                                :alt="fisher.profile.name"
                                class="h-24 w-24 shrink-0 rounded-full object-cover shadow-sm ring-4 ring-white md:h-28 md:w-28"
                            />
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h1 class="text-xl font-bold text-slate-900">{{ fisher.profile.name }}</h1>
                                    <span
                                        v-if="fisher.profile.badge"
                                        class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700"
                                    >{{ fisher.profile.badge }}</span>
                                </div>
                                <p class="mt-0.5 text-sm text-slate-400">@{{ handle }}</p>
                                <p v-if="fisher.profile.bio" class="mt-2 text-sm leading-relaxed text-slate-600">{{ fisher.profile.bio }}</p>

                                <!-- Action buttons -->
                                <div class="mt-4 flex items-center gap-2">
                                    <button
                                        type="button"
                                        class="rounded-lg px-5 py-2 text-sm font-semibold transition"
                                        :class="isFollowing
                                            ? 'border border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100'
                                            : 'bg-slate-900 text-white hover:bg-slate-700'"
                                        @click="handleToggleFollow"
                                    >
                                        {{ isFollowing ? t('fisher.unfollow') : t('fisher.follow') }}
                                    </button>
                                    <button
                                        type="button"
                                        class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Divider (desktop) -->
                        <div class="hidden shrink-0 border-l border-slate-100 md:block"></div>

                        <!-- Stats (desktop: right column) -->
                        <div class="hidden shrink-0 md:flex md:items-start md:gap-6 lg:gap-8">
                            <div v-for="stat in statItems" :key="stat.key" class="flex flex-col items-center text-center">
                                <div class="mb-2 flex h-9 w-9 items-center justify-center rounded-full border border-emerald-200 bg-emerald-50 text-emerald-600">
                                    <component :is="stat.icon" class="h-4 w-4" />
                                </div>
                                <p class="text-base font-bold text-slate-900">{{ stat.value }}</p>
                                <p v-if="stat.sub" class="text-[11px] text-slate-400">{{ stat.sub }}</p>
                                <p class="text-[11px] text-slate-500">{{ stat.label }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Stats (mobile: row below) -->
                    <div class="mt-5 grid grid-cols-3 gap-3 border-t border-slate-100 pt-4 md:hidden">
                        <div v-for="stat in statItems" :key="stat.key" class="flex flex-col items-center text-center">
                            <div class="mb-1.5 flex h-8 w-8 items-center justify-center rounded-full border border-emerald-200 bg-emerald-50 text-emerald-600">
                                <component :is="stat.icon" class="h-3.5 w-3.5" />
                            </div>
                            <p class="text-sm font-bold text-slate-900">{{ stat.value }}</p>
                            <p v-if="stat.sub" class="text-[10px] text-slate-400">{{ stat.sub }}</p>
                            <p class="text-[10px] text-slate-500">{{ stat.label }}</p>
                        </div>
                    </div>
                </div>

                <!-- Tab bar -->
                <div class="mb-6 border-b border-slate-200 bg-white">
                    <nav class="tabs-scroll flex overflow-x-auto" aria-label="Tabs">
                        <button
                            v-for="tab in tabs"
                            :key="tab.key"
                            type="button"
                            class="flex shrink-0 items-center gap-2 whitespace-nowrap border-b-2 px-4 py-3.5 text-sm font-medium transition-colors sm:px-5"
                            :class="activeTab === tab.key
                                ? 'border-slate-900 text-slate-900'
                                : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                            @click="switchTab(tab.key)"
                        >
                            <component :is="tab.icon" class="h-4 w-4" />
                            {{ tab.label }}
                        </button>
                    </nav>
                </div>

                <!-- Content: feed tabs -->
                <template v-if="activeTab !== 'achievements'">
                    <!-- First load spinner -->
                    <div v-if="postsLoading && posts.length === 0" class="py-20 text-center text-slate-400">
                        {{ t('fisher.loading') }}
                    </div>

                    <!-- Empty state -->
                    <div v-else-if="!postsLoading && posts.length === 0" class="py-20 text-center text-slate-400">
                        {{ t('fisher.empty') }}
                    </div>

                    <!-- Grid -->
                    <div v-else class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                        <PostCard
                            v-for="post in posts"
                            :key="post.id"
                            :catch-item="post"
                            :selected="selectedPost?.id === post.id"
                            @select="openPost(post)"
                            @like-changed="handleLikeChanged"
                        />
                    </div>

                    <!-- Infinite scroll sentinel -->
                    <div ref="sentinel" class="mt-4 h-4"></div>

                    <!-- Loading more -->
                    <div v-if="postsLoading && posts.length > 0" class="py-6 text-center text-sm text-slate-400">
                        {{ t('fisher.loading') }}
                    </div>
                </template>

                <!-- Achievements tab -->
                <template v-else>
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                        <div
                            v-for="achievement in fisher.achievements"
                            :key="achievement.id"
                            class="flex flex-col items-center rounded-2xl border border-slate-100 bg-white p-5 text-center shadow-sm"
                            :class="achievement.earned ? '' : 'opacity-50'"
                        >
                            <div
                                class="mb-3 flex h-14 w-14 items-center justify-center rounded-full text-2xl"
                                :class="achievement.earned ? 'bg-emerald-50' : 'bg-slate-100 grayscale'"
                            >
                                {{ achievementEmoji(achievement.icon) }}
                            </div>
                            <p class="text-sm font-semibold text-slate-800">{{ achievementLabel(achievement) }}</p>
                            <p
                                class="mt-1 text-xs font-medium"
                                :class="achievement.earned ? 'text-emerald-600' : 'text-slate-400'"
                            >
                                {{ achievement.earned ? t('cabinet.earned') : t('cabinet.notEarned') }}
                            </p>
                        </div>
                    </div>
                </template>

            </template>
        </div>
    </div>

    <!-- Catch detail sidebar -->
    <CatchDetailModal
        :show="!!selectedPost"
        :post="selectedPost"
        @close="selectedPost = null"
        @comment-added="handleCommentAdded"
    />
</template>

<script setup>
import { computed, h, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { useHead } from '@unhead/vue';
import { fetchFisher, fetchFisherPosts, followFisher, unfollowFisher } from '../api/fishers';
import { useInfiniteScroll } from '../composables/useInfiniteScroll';
import { useAuthStore } from '../stores/auth';
import CatchDetailModal from '../components/posts/CatchDetailModal.vue';
import PostCard from '../components/posts/PostCard.vue';

const { t, te } = useI18n();
const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();

const fisher = ref(null);
const loading = ref(true);
const isFollowing = ref(false);

const posts = ref([]);
const postsLoading = ref(false);
// `page` holds the *next* page to fetch, so the comparison must be inclusive —
// with `<` the final page would never load.
const page = ref(1);
const lastPage = ref(1);
const hasMore = computed(() => page.value <= lastPage.value);

const activeTab = ref('publications');
const selectedPost = ref(null);
const sentinel = ref(null);

const defaultAvatar = 'https://images.unsplash.com/photo-1504307651254-35680f356dfd?w=200';

const handle = computed(() => {
    if (!fisher.value?.profile?.name) return '';
    return fisher.value.profile.name.toLowerCase().replace(/\s+/g, '');
});

// ─── Icons ───────────────────────────────────────────────────────────────────

const HookIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', 'stroke-width': '2', viewBox: '0 0 24 24' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M12 3v10m0 0a3 3 0 103 3M12 13a3 3 0 10-3 3' }),
]);

const LakeIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', 'stroke-width': '2', viewBox: '0 0 24 24' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M3 12h18M3 6l9-3 9 3M3 18l9 3 9-3' }),
]);

const FishIcon = () => h('svg', { fill: 'currentColor', viewBox: '0 0 24 24' }, [
    h('path', { d: 'M19.5 12c0 0-3-5-7.5-5S4.5 12 4.5 12 7.5 17 12 17s7.5-5 7.5-5zm1 0 3-2.5v5L20.5 12zM14 10.5a2 2 0 1 1-4 0 2 2 0 0 1 4 0z' }),
]);

const UsersIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', 'stroke-width': '2', viewBox: '0 0 24 24' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' }),
]);

const HeartIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', 'stroke-width': '2', viewBox: '0 0 24 24' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z' }),
]);

const TrophyIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', 'stroke-width': '2', viewBox: '0 0 24 24' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M5 3h14M9 3v2a3 3 0 003 3h0a3 3 0 003-3V3M5 3v2a5 5 0 005 5h4a5 5 0 005-5V3M7 10v1a5 5 0 005 5h0a5 5 0 005-5v-1M9 21h6' }),
]);

const GridIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', 'stroke-width': '2', viewBox: '0 0 24 24' }, [
    h('rect', { x: '3', y: '3', width: '7', height: '7' }),
    h('rect', { x: '14', y: '3', width: '7', height: '7' }),
    h('rect', { x: '14', y: '14', width: '7', height: '7' }),
    h('rect', { x: '3', y: '14', width: '7', height: '7' }),
]);

const DocIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', 'stroke-width': '2', viewBox: '0 0 24 24' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' }),
]);

// ─── Stats ────────────────────────────────────────────────────────────────────

const statItems = computed(() => {
    if (!fisher.value?.stats) return [];
    const s = fisher.value.stats;
    return [
        { key: 'catches', icon: HookIcon, value: s.catches_count, label: t('stats.catches') },
        { key: 'lakes', icon: LakeIcon, value: s.lakes_visited, label: t('stats.lakesVisited') },
        {
            key: 'fish',
            icon: FishIcon,
            value: s.biggest_fish_weight ? `${s.biggest_fish_weight} ${t('stats.kg')}` : '—',
            sub: s.biggest_fish_name || null,
            label: t('stats.biggestFish'),
        },
        { key: 'followers', icon: UsersIcon, value: s.followers_count, label: t('stats.followers') },
        { key: 'likes', icon: HeartIcon, value: s.total_likes ?? 0, label: t('stats.likes') },
        { key: 'ranking', icon: TrophyIcon, value: `#${s.ranking}`, label: t('stats.ranking') },
    ];
});

// ─── Tabs ─────────────────────────────────────────────────────────────────────

const tabs = computed(() => [
    { key: 'publications', label: t('fisher.publications'), icon: GridIcon },
    { key: 'posts',        label: t('fisher.posts'),        icon: DocIcon },
    { key: 'catches',      label: t('fisher.catches'),      icon: FishIcon },
    { key: 'achievements', label: t('fisher.achievements'), icon: TrophyIcon },
]);

// ─── Data loading ─────────────────────────────────────────────────────────────

function tabToType(tab) {
    if (tab === 'posts') return 'post';
    if (tab === 'catches') return 'catch';
    return '';
}

async function loadPosts(reset = false) {
    if (postsLoading.value) return;
    if (!reset && !hasMore.value && posts.value.length > 0) return;
    postsLoading.value = true;
    try {
        const type = tabToType(activeTab.value);
        const res = await fetchFisherPosts(route.params.id, {
            page: page.value,
            ...(type ? { type } : {}),
        });
        if (reset) {
            posts.value = res.data;
        } else {
            posts.value.push(...res.data);
        }
        lastPage.value = res.meta.last_page;
        page.value = res.meta.current_page + 1;
    } finally {
        postsLoading.value = false;
    }
}

async function switchTab(tab) {
    if (activeTab.value === tab) return;
    activeTab.value = tab;
    if (tab !== 'achievements') {
        posts.value = [];
        page.value = 1;
        lastPage.value = 1;
        await loadPosts(true);
    }
}

// ─── Infinite scroll ──────────────────────────────────────────────────────────

useInfiniteScroll(sentinel, {
    loading: postsLoading,
    hasMore,
    // Only ever appends — the first page is fetched by load() / switchTab
    onLoad: () => posts.value.length && loadPosts(),
});

// ─── Like ─────────────────────────────────────────────────────────────────────

function handleLikeChanged({ id, liked, likes_count }) {
    const post = posts.value.find((p) => p.id === id);
    if (post) {
        post.is_liked = liked;
        post.likes_count = likes_count;
    }
    if (selectedPost.value?.id === id) {
        selectedPost.value = { ...selectedPost.value, is_liked: liked, likes_count };
    }
}

// ─── Follow ───────────────────────────────────────────────────────────────────

async function handleToggleFollow() {
    if (!authStore.isAuthenticated) {
        router.push({ name: 'login' });
        return;
    }
    const id = route.params.id;
    if (isFollowing.value) {
        await unfollowFisher(id);
        isFollowing.value = false;
        if (fisher.value?.stats) fisher.value.stats.followers_count--;
    } else {
        await followFisher(id);
        isFollowing.value = true;
        if (fisher.value?.stats) fisher.value.stats.followers_count++;
    }
}

// ─── Detail modal ─────────────────────────────────────────────────────────────

function openPost(post) {
    selectedPost.value = post;
}

function handleCommentAdded(postId) {
    const p = posts.value.find((c) => c.id === postId);
    if (p) {
        p.is_commented = true;
        p.comments_count = (p.comments_count ?? 0) + 1;
    }
}

// ─── Helpers ─────────────────────────────────────────────────────────────────

const achievementIcons = { star: '⭐', fish: '🐟', lake: '🏞️', camera: '📷', moon: '🌙' };
function achievementEmoji(icon) {
    return achievementIcons[icon] || '🏅';
}
function achievementLabel(achievement) {
    const key = `achievements.${achievement.id}.title`;
    return te(key) ? t(key) : achievement.title;
}

// ─── Lifecycle ────────────────────────────────────────────────────────────────

useHead({
    title: computed(() =>
        fisher.value?.profile?.name
            ? `${fisher.value.profile.name} — рибалка | Pro Fisher`
            : 'Pro Fisher'
    ),
});

async function load(id) {
    if (String(id) === String(authStore.user?.id)) {
        router.replace({ name: 'cabinet' });
        return;
    }
    loading.value = true;
    try {
        fisher.value = await fetchFisher(id);
        isFollowing.value = fisher.value.is_following;
    } finally {
        loading.value = false;
    }
    await loadPosts(true);
}

onMounted(() => load(route.params.id));

watch(() => route.params.id, (id) => {
    if (id) {
        posts.value = [];
        page.value = 1;
        lastPage.value = 1;
        activeTab.value = 'publications';
        load(id);
    }
});
</script>

<style scoped>
/* Horizontal tab strip scrolls on narrow screens without showing a scrollbar */
.tabs-scroll {
    scrollbar-width: none;
    -ms-overflow-style: none;
    -webkit-overflow-scrolling: touch;
}
.tabs-scroll::-webkit-scrollbar {
    display: none;
}
</style>
