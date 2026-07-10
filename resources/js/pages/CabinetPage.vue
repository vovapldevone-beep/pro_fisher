<template>
    <div class="min-h-full bg-slate-50 py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6">

            <div v-if="loading" class="py-24 text-center text-slate-500">{{ t('common.loading') }}</div>

            <template v-else-if="cabinet">

                <!-- Profile card -->
                <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
                    <div class="flex flex-col gap-6 md:flex-row md:items-start">

                        <!-- Left: avatar + info, with the action buttons spanning below them -->
                        <div class="flex min-w-0 flex-1 flex-col gap-4">
                            <div class="flex min-w-0 gap-5">
                                <img
                                    :src="cabinet.profile.avatar_url || defaultAvatar"
                                    :alt="cabinet.profile.name"
                                    class="h-24 w-24 shrink-0 rounded-full object-cover shadow-sm ring-4 ring-white md:h-28 md:w-28"
                                />
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h1 class="text-xl font-bold text-slate-900">{{ cabinet.profile.name }}</h1>
                                        <span
                                            v-if="cabinet.profile.badge"
                                            class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700"
                                        >{{ cabinet.profile.badge }}</span>
                                    </div>
                                    <p class="mt-0.5 text-sm text-slate-400">@{{ handle }}</p>
                                    <p v-if="cabinet.profile.bio" class="mt-2 text-sm leading-relaxed text-slate-600">{{ cabinet.profile.bio }}</p>
                                </div>
                            </div>

                            <!-- Action buttons: profile actions grouped, content creation
                                 as a segmented control. Both wrap instead of overflowing. -->
                            <div class="flex flex-wrap items-center gap-2">
                                <button
                                    type="button"
                                    class="flex-1 whitespace-nowrap rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700 sm:flex-none sm:px-5"
                                    @click="showEditProfile = true"
                                >
                                    {{ t('cabinet.editProfile') }}
                                </button>
                                <button
                                    type="button"
                                    class="flex flex-1 items-center justify-center gap-1.5 whitespace-nowrap rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 sm:flex-none"
                                    @click="showFriends = true"
                                >
                                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                    {{ t('friends.button') }}
                                </button>

                                <div class="flex w-full overflow-hidden rounded-lg border border-slate-300 sm:w-auto">
                                    <button
                                        type="button"
                                        class="flex flex-1 items-center justify-center gap-1.5 whitespace-nowrap border-r border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 sm:flex-none"
                                        @click="showAddCatch = true"
                                    >
                                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                                        </svg>
                                        {{ t('cabinet.newCatch') }}
                                    </button>
                                    <button
                                        type="button"
                                        class="flex flex-1 items-center justify-center gap-1.5 whitespace-nowrap px-4 py-2 text-sm font-medium text-blue-600 transition hover:bg-blue-50 sm:flex-none"
                                        @click="showAddPost = true"
                                    >
                                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                                        </svg>
                                        {{ t('cabinet.newPost') }}
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

                <!-- Content: left (tabs + grid) / right (activity) -->
                <div class="grid gap-6 lg:grid-cols-12">

                    <!-- Left column — min-w-0 lets it shrink past the tab strip's width -->
                    <div class="min-w-0 lg:col-span-8 xl:col-span-9">

                        <!-- Tab bar -->
                        <div class="mb-6 border-b border-slate-200 bg-white">
                            <nav class="tabs-scroll flex overflow-x-auto" aria-label="Tabs">
                                <button
                                    v-for="tab in tabs"
                                    :key="tab.key"
                                    type="button"
                                    class="flex shrink-0 items-center gap-2 whitespace-nowrap border-b-2 px-4 py-3.5 text-sm font-medium transition-colors sm:px-5"
                                    :class="[
                                        activeTab === tab.key
                                            ? 'border-slate-900 text-slate-900'
                                            : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700',
                                        tab.mobileOnly ? 'lg:hidden' : '',
                                    ]"
                                    @click="switchTab(tab.key)"
                                >
                                    <component :is="tab.icon" class="h-4 w-4" />
                                    {{ tab.label }}
                                </button>
                            </nav>
                        </div>

                        <!-- Feed tabs -->
                        <template v-if="isFeedTab">
                            <div v-if="postsLoading && posts.length === 0" class="py-20 text-center text-slate-400">
                                {{ t('fisher.loading') }}
                            </div>

                            <div v-else-if="!postsLoading && posts.length === 0" class="py-20 text-center text-slate-400">
                                {{ t('fisher.empty') }}
                            </div>

                            <div v-else class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-3">
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

                            <div v-if="postsLoading && posts.length > 0" class="py-6 text-center text-sm text-slate-400">
                                {{ t('fisher.loading') }}
                            </div>
                        </template>

                        <!-- Activity tab (mobile only) -->
                        <template v-else-if="activeTab === 'activity'">
                            <div class="space-y-6 lg:hidden">
                                <ActivityFeed :activity="cabinet.activity" />
                                <!-- Дозволи приховано
                                <PermitsCard :permits="cabinet.permits" />
                                -->
                            </div>
                        </template>

                        <!-- Achievements tab -->
                        <template v-else>
                            <div class="mb-4 flex justify-end">
                                <router-link to="/cabinet/achievements" class="text-sm text-blue-600 hover:underline">
                                    {{ t('common.viewAll') }}
                                </router-link>
                            </div>
                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 xl:grid-cols-4">
                                <div
                                    v-for="achievement in cabinet.achievements"
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
                    </div>

                    <!-- Right column: activity (desktop; on mobile it lives in the "Активність" tab) -->
                    <div class="hidden min-w-0 space-y-6 lg:col-span-4 lg:block xl:col-span-3">
                        <ActivityFeed :activity="cabinet.activity" />
                        <!-- Дозволи приховано
                        <PermitsCard :permits="cabinet.permits" />
                        -->
                    </div>
                </div>

            </template>
        </div>

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

        <EditProfileModal
            :show="showEditProfile"
            :profile="cabinet?.profile ?? {}"
            @close="showEditProfile = false"
            @saved="handleProfileSaved"
        />

        <FriendsModal :show="showFriends" @close="showFriends = false" />
    </div>

    <!-- Catch detail sidebar -->
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
</template>

<script setup>
import { computed, h, onMounted, onUnmounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { fetchCabinet } from '../api/cabinet';
import { fetchFisherPosts } from '../api/fishers';
import { useInfiniteScroll } from '../composables/useInfiniteScroll';
import ActivityFeed from '../components/cabinet/ActivityFeed.vue';
import AddCatchModal from '../components/cabinet/AddCatchModal.vue';
import AddPostModal from '../components/cabinet/AddPostModal.vue';
import EditProfileModal from '../components/cabinet/EditProfileModal.vue';
import FriendsModal from '../components/cabinet/FriendsModal.vue';
// import PermitsCard from '../components/cabinet/PermitsCard.vue';
import CatchDetailModal from '../components/posts/CatchDetailModal.vue';
import EditCatchModal from '../components/posts/EditCatchModal.vue';
import PostCard from '../components/posts/PostCard.vue';
import { useAuthStore } from '../stores/auth';
import { useCatchesStore } from '../stores/catches';
import { useLakesStore } from '../stores/lakes';

const { t, te } = useI18n();
const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const catchesStore = useCatchesStore();
const lakesStore = useLakesStore();

const cabinet = ref(null);
const loading = ref(true);
const showAddCatch = ref(false);
const showAddPost = ref(false);
const showEditProfile = ref(false);
const showFriends = ref(false);

const posts = ref([]);
const postsLoading = ref(false);
// `page` holds the *next* page to fetch, so the comparison must be inclusive —
// with `<` the final page would never load.
const page = ref(1);
const lastPage = ref(1);
const hasMore = computed(() => page.value <= lastPage.value);

const activeTab = ref('publications');
const selectedPost = ref(null);
const editingPost = ref(null);
const sentinel = ref(null);

const defaultAvatar = 'https://images.unsplash.com/photo-1504307651254-35680f356dfd?w=200';

const handle = computed(() => {
    if (!cabinet.value?.profile?.name) return '';
    return cabinet.value.profile.name.toLowerCase().replace(/\s+/g, '');
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

const PulseIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', 'stroke-width': '2', viewBox: '0 0 24 24' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M3 12h4l3-8 4 16 3-8h4' }),
]);

// ─── Stats ────────────────────────────────────────────────────────────────────

const statItems = computed(() => {
    if (!cabinet.value?.stats) return [];
    const s = cabinet.value.stats;
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
    // Rendered only below lg — on desktop these cards live in the right column
    { key: 'activity',     label: t('cabinet.activity'),    icon: PulseIcon, mobileOnly: true },
]);

const FEED_TABS = ['publications', 'posts', 'catches'];
const isFeedTab = computed(() => FEED_TABS.includes(activeTab.value));

// ─── Data loading ─────────────────────────────────────────────────────────────

async function loadCabinet() {
    loading.value = true;
    try {
        cabinet.value = await fetchCabinet();
    } finally {
        loading.value = false;
    }
}

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
        const res = await fetchFisherPosts(authStore.user.id, {
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

function resetPosts() {
    posts.value = [];
    page.value = 1;
    lastPage.value = 1;
}

async function switchTab(tab) {
    if (activeTab.value === tab) return;
    activeTab.value = tab;
    if (isFeedTab.value) {
        resetPosts();
        await loadPosts(true);
    }
}

// ─── Infinite scroll ──────────────────────────────────────────────────────────

useInfiniteScroll(sentinel, {
    loading: postsLoading,
    hasMore,
    // Only ever appends — the first page is fetched by onMounted / switchTab
    onLoad: () => posts.value.length && loadPosts(),
});

// ─── Like / detail ────────────────────────────────────────────────────────────

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

// ─── Owner actions ────────────────────────────────────────────────────────────

function startEdit(post) {
    selectedPost.value = null;
    editingPost.value = post;
}

function handleUpdated(updated) {
    const index = posts.value.findIndex((p) => p.id === updated.id);
    if (index !== -1) posts.value[index] = updated;
    loadCabinet(); // stats and the activity feed may have changed
}

function handleDeleted(id) {
    posts.value = posts.value.filter((p) => p.id !== id);
    loadCabinet();
}

// ─── Modals ───────────────────────────────────────────────────────────────────

async function handleAddCatch(formData) {
    await catchesStore.addCatch(formData);
    showAddCatch.value = false;
    resetPosts();
    await Promise.all([loadCabinet(), loadPosts(true)]);
}

async function handleAddPost(formData) {
    await catchesStore.addCatch(formData);
    showAddPost.value = false;
    resetPosts();
    await Promise.all([loadCabinet(), loadPosts(true)]);
}

function handleProfileSaved(updatedUser) {
    if (cabinet.value) {
        cabinet.value.profile.name = updatedUser.name;
        cabinet.value.profile.avatar_url = updatedUser.avatar_url;
    }
    authStore.user.name = updatedUser.name;
    authStore.user.avatar_url = updatedUser.avatar_url;
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

watch(() => route.query.action, (action) => {
    if (!action) return;
    if (action === 'add-catch') showAddCatch.value = true;
    if (action === 'add-post') showAddPost.value = true;
    router.replace({ query: {} });
}, { immediate: true });

// The "Активність" tab only exists below lg — leaving it selected on a widened
// window would blank the left column, so fall back to the first tab.
const desktopQuery = window.matchMedia('(min-width: 1024px)');
function onBreakpointChange(e) {
    if (e.matches && activeTab.value === 'activity') switchTab('publications');
}

onMounted(async () => {
    desktopQuery.addEventListener('change', onBreakpointChange);
    await Promise.all([loadCabinet(), lakesStore.loadLakes()]);
    await loadPosts(true);
});

onUnmounted(() => {
    desktopQuery.removeEventListener('change', onBreakpointChange);
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
