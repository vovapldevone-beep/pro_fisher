<template>
    <div class="min-h-full bg-slate-50 py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6">

            <div v-if="loading" class="py-24 text-center text-slate-500">{{ t('common.loading') }}</div>

            <template v-else-if="cabinet">

                <!-- Profile card -->
                <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-col gap-6 md:flex-row md:items-start">

                        <!-- Left: avatar + info + buttons -->
                        <div class="flex min-w-0 flex-1 gap-5">
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

                                <!-- Action buttons -->
                                <div class="mt-4 flex flex-wrap items-center gap-2">
                                    <button
                                        type="button"
                                        class="rounded-lg bg-slate-900 px-5 py-2 text-sm font-semibold text-white transition hover:bg-slate-700"
                                        @click="showEditProfile = true"
                                    >
                                        {{ t('cabinet.editProfile') }}
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                                        @click="showAddCatch = true"
                                    >
                                        {{ t('header.addCatch') }}
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-lg border border-blue-400 px-4 py-2 text-sm font-medium text-blue-600 transition hover:bg-blue-50"
                                        @click="showAddPost = true"
                                    >
                                        {{ t('header.addPost') }}
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

                    <!-- Left column -->
                    <div class="lg:col-span-8 xl:col-span-9">

                        <!-- Tab bar -->
                        <div class="mb-6 border-b border-slate-200 bg-white">
                            <nav class="flex overflow-x-auto" aria-label="Tabs">
                                <button
                                    v-for="tab in tabs"
                                    :key="tab.key"
                                    type="button"
                                    class="flex shrink-0 items-center gap-2 border-b-2 px-5 py-3.5 text-sm font-medium transition-colors"
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

                        <!-- Feed tabs -->
                        <template v-if="activeTab !== 'achievements'">
                            <div v-if="postsLoading && posts.length === 0" class="py-20 text-center text-slate-400">
                                {{ t('fisher.loading') }}
                            </div>

                            <div v-else-if="!postsLoading && posts.length === 0" class="py-20 text-center text-slate-400">
                                {{ t('fisher.empty') }}
                            </div>

                            <div v-else class="grid grid-cols-2 gap-4 lg:grid-cols-3 xl:grid-cols-4">
                                <div
                                    v-for="post in posts"
                                    :key="post.id"
                                    class="cursor-pointer overflow-hidden rounded-xl border border-slate-100 bg-white shadow-sm transition hover:shadow-md"
                                    @click="openPost(post)"
                                >
                                    <!-- Photo -->
                                    <div class="relative bg-slate-100" style="aspect-ratio: 4/3">
                                        <img
                                            v-if="post.photo_url"
                                            :src="post.photo_url"
                                            :alt="post.fish_name || post.notes"
                                            class="h-full w-full object-cover"
                                        />
                                        <div v-else class="flex h-full w-full items-center justify-center text-4xl">🐟</div>

                                        <div
                                            v-if="post.photo_url"
                                            class="absolute right-2 top-2 flex items-center justify-center rounded-md bg-black/50 p-1"
                                        >
                                            <svg class="h-3.5 w-3.5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17 10.5V7c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h12c.55 0 1-.45 1-1v-3.5l4 4v-11l-4 4z"/>
                                            </svg>
                                        </div>
                                    </div>

                                    <!-- Card info -->
                                    <div class="p-3">
                                        <div class="mb-2 flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                <!-- Likes -->
                                                <button
                                                    type="button"
                                                    class="flex items-center gap-1.5 text-sm transition"
                                                    :class="post.is_liked ? 'text-red-500' : 'text-slate-400 hover:text-red-400'"
                                                    @click.stop="handleLike(post)"
                                                >
                                                    <svg class="h-4 w-4" :fill="post.is_liked ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                                    </svg>
                                                    <span class="font-medium text-slate-600">{{ post.likes_count }}</span>
                                                </button>
                                                <!-- Comments -->
                                                <button
                                                    type="button"
                                                    class="flex items-center gap-1.5 text-sm text-slate-400 transition hover:text-slate-600"
                                                    @click.stop="openPost(post)"
                                                >
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                                    </svg>
                                                    <span class="font-medium text-slate-600">{{ post.comments_count }}</span>
                                                </button>
                                            </div>
                                        </div>
                                        <p class="line-clamp-1 text-sm font-semibold text-slate-900">
                                            {{ post.fish_name || post.notes || 'Публікація' }}
                                        </p>
                                        <p class="mt-0.5 line-clamp-1 text-xs text-slate-400">
                                            {{ formatPostDate(post) }}{{ post.lake?.name ? ' · ' + post.lake.name : '' }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Infinite scroll sentinel -->
                            <div ref="sentinel" class="mt-4 h-4"></div>

                            <div v-if="postsLoading && posts.length > 0" class="py-6 text-center text-sm text-slate-400">
                                {{ t('fisher.loading') }}
                            </div>
                        </template>

                        <!-- Achievements tab -->
                        <template v-else>
                            <div class="mb-4 flex justify-end">
                                <router-link to="/cabinet/achievements" class="text-sm text-blue-600 hover:underline">
                                    {{ t('common.viewAll') }}
                                </router-link>
                            </div>
                            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-4">
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

                    <!-- Right column: activity + permits -->
                    <div class="space-y-6 lg:col-span-4 xl:col-span-3">
                        <ActivityFeed :activity="cabinet.activity" />
                        <PermitsCard :permits="cabinet.permits" />
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
import { computed, h, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { fetchCabinet } from '../api/cabinet';
import { fetchFisherPosts } from '../api/fishers';
import { toggleLike } from '../api/catches';
import ActivityFeed from '../components/cabinet/ActivityFeed.vue';
import AddCatchModal from '../components/cabinet/AddCatchModal.vue';
import AddPostModal from '../components/cabinet/AddPostModal.vue';
import EditProfileModal from '../components/cabinet/EditProfileModal.vue';
import PermitsCard from '../components/cabinet/PermitsCard.vue';
import CatchDetailModal from '../components/posts/CatchDetailModal.vue';
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

const posts = ref([]);
const postsLoading = ref(false);
const page = ref(1);
const lastPage = ref(1);
const hasMore = computed(() => page.value < lastPage.value);

const activeTab = ref('publications');
const selectedPost = ref(null);
const sentinel = ref(null);
let observer = null;

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
]);

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
    if (tab !== 'achievements') {
        resetPosts();
        await loadPosts(true);
    }
}

// ─── Infinite scroll ──────────────────────────────────────────────────────────

function setupObserver() {
    if (!sentinel.value) return;
    observer = new IntersectionObserver(
        (entries) => {
            if (entries[0].isIntersecting && !postsLoading.value && hasMore.value) {
                loadPosts();
            }
        },
        { threshold: 0.1 },
    );
    observer.observe(sentinel.value);
}

// ─── Like / detail ────────────────────────────────────────────────────────────

async function handleLike(post) {
    post.is_liked = !post.is_liked;
    post.likes_count += post.is_liked ? 1 : -1;
    try {
        const result = await toggleLike(post.id);
        post.is_liked = result.liked;
        post.likes_count = result.likes_count;
    } catch {
        post.is_liked = !post.is_liked;
        post.likes_count += post.is_liked ? 1 : -1;
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

function formatPostDate(post) {
    const raw = post.caught_at || post.created_at;
    if (!raw) return '';
    return new Date(raw).toLocaleDateString('uk-UA', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

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

onMounted(async () => {
    await Promise.all([loadCabinet(), lakesStore.loadLakes()]);
    await loadPosts(true);
    await nextTick();
    setupObserver();
});

onUnmounted(() => {
    if (observer) observer.disconnect();
});
</script>
