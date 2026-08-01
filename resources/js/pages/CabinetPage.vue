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
                                    <AppIcon name="users" class="h-4 w-4 shrink-0" />
                                    {{ t('friends.button') }}
                                </button>

                                <button
                                    type="button"
                                    class="flex w-full items-center justify-center gap-1.5 whitespace-nowrap rounded-lg border border-emerald-500 px-4 py-2 text-sm font-medium text-emerald-600 transition hover:bg-emerald-50 sm:w-auto"
                                    @click="showAddPost = true"
                                >
                                    <AppIcon name="plus" class="h-4 w-4 shrink-0" />
                                    {{ t('common.addPublication') }}
                                </button>
                            </div>
                        </div>

                        <!-- Divider (desktop) -->
                        <div class="hidden shrink-0 border-l border-slate-100 md:block"></div>

                        <!-- Stats (desktop: right column) -->
                        <div class="hidden shrink-0 md:flex md:items-start md:gap-6 lg:gap-8">
                            <div v-for="stat in statItems" :key="stat.key" class="flex flex-col items-center text-center">
                                <div class="mb-2 flex h-9 w-9 items-center justify-center rounded-full border border-emerald-200 bg-emerald-50 text-emerald-600">
                                    <AppIcon :name="stat.icon" class="h-4 w-4" />
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
                                <AppIcon :name="stat.icon" class="h-3.5 w-3.5" />
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
                                    <AppIcon :name="tab.icon" class="h-4 w-4" />
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
                                    v-for="achievement in achievementsList"
                                    :key="achievement.id"
                                    class="flex flex-col items-center rounded-2xl border border-slate-100 bg-white p-5 text-center shadow-sm"
                                    :class="achievement.earned ? '' : 'opacity-50'"
                                >
                                    <div
                                        class="mb-3 flex h-14 w-14 items-center justify-center rounded-full"
                                        :class="achievement.earned ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-400'"
                                    >
                                        <AppIcon :name="achievement.icon" class="h-7 w-7" />
                                    </div>
                                    <p class="text-sm font-semibold text-slate-800">{{ achievementLabel(achievement) }}</p>
                                    <p
                                        class="mt-1 text-xs font-medium"
                                        :class="achievement.earned ? 'text-emerald-600' : 'text-slate-400'"
                                    >
                                        {{ achievement.earned ? t('cabinet.earned') : t('cabinet.notEarned') }}
                                    </p>
                                    <router-link
                                        v-if="achievement.id === 'fish_hunt'"
                                        to="/raffle"
                                        class="mt-2 text-xs font-semibold text-emerald-600 hover:underline"
                                    >
                                        {{ t('fish.details') }}
                                    </router-link>
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
            :error="catchesStore.error"
            @submit="handleAddCatch"
            @close="closeAddModals"
            @switch="closeAddModals(); showAddPost = true"
        />

        <AddPostModal
            :show="showAddPost"
            :saving="catchesStore.saving"
            :error="catchesStore.error"
            @submit="handleAddPost"
            @close="closeAddModals"
            @switch="closeAddModals(); showAddCatch = true"
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
        @like-changed="handleLikeChanged"
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
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
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
import AppIcon from '../components/shared/AppIcon.vue';
import { useAuthStore } from '../stores/auth';
import { useCatchesStore } from '../stores/catches';
import { useFishStore } from '../stores/fish';
import { useLakesStore } from '../stores/lakes';

const { t, te } = useI18n();
const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const catchesStore = useCatchesStore();
const fishStore = useFishStore();
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

// The stored, unique handle. Accounts created before it existed fall back to the
// old derived form until the backfill migration has run.
const handle = computed(() => {
    const profile = cabinet.value?.profile;
    if (!profile?.name) return '';

    return profile.username || profile.name.toLowerCase().replace(/\s+/g, '');
});

// ─── Icons ───────────────────────────────────────────────────────────────────

// ─── Stats ────────────────────────────────────────────────────────────────────

const statItems = computed(() => {
    if (!cabinet.value?.stats) return [];
    const s = cabinet.value.stats;
    return [
        { key: 'catches', icon: 'hook', value: s.catches_count, label: t('stats.catches') },
        { key: 'lakes', icon: 'lake', value: s.lakes_visited, label: t('stats.lakesVisited') },
        {
            key: 'fish',
            icon: 'fish',
            value: s.biggest_fish_weight ? `${s.biggest_fish_weight} ${t('stats.kg')}` : '—',
            sub: s.biggest_fish_name || null,
            label: t('stats.biggestFish'),
        },
        { key: 'followers', icon: 'users', value: s.followers_count, label: t('stats.followers') },
        { key: 'likes', icon: 'heart', value: s.total_likes ?? 0, label: t('stats.likes') },
        // No ranking chip — nothing computes it yet, so it only ever showed "#0"
    ];
});

// ─── Tabs ─────────────────────────────────────────────────────────────────────

const tabs = computed(() => [
    { key: 'publications', label: t('fisher.publications'), icon: 'grid' },
    { key: 'posts',        label: t('fisher.posts'),        icon: 'doc' },
    { key: 'catches',      label: t('fisher.catches'),      icon: 'fish' },
    { key: 'achievements', label: t('fisher.achievements'), icon: 'trophy' },
    // Rendered only below lg — on desktop these cards live in the right column
    { key: 'activity',     label: t('cabinet.activity'),    icon: 'pulse', mobileOnly: true },
]);

const FEED_TABS = ['publications', 'posts', 'catches'];
const isFeedTab = computed(() => FEED_TABS.includes(activeTab.value));

// The fish hunt is stateful (from the store), not computed from stats like the
// API achievements — merge it into the displayed list.
const achievementsList = computed(() => {
    const list = cabinet.value?.achievements ? [...cabinet.value.achievements] : [];
    list.push({
        id: 'fish_hunt',
        icon: 'fish',
        title: 'Рибалка-шукач',
        earned: fishStore.loaded && fishStore.found >= fishStore.total,
    });
    return list;
});

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

// Keep the modal open when publishing fails — the message is in
// catchesStore.error, and the user should not have to retype the post.
async function handleAddCatch(formData) {
    try {
        await catchesStore.addCatch(formData);
    } catch {
        return;
    }
    closeAddModals();
    resetPosts();
    await Promise.all([loadCabinet(), loadPosts(true)]);
}

async function handleAddPost(formData) {
    try {
        await catchesStore.addCatch(formData);
    } catch {
        return;
    }
    closeAddModals();
    resetPosts();
    await Promise.all([loadCabinet(), loadPosts(true)]);
}

function closeAddModals() {
    showAddCatch.value = false;
    showAddPost.value = false;
    catchesStore.error = ''; // otherwise it greets the user again on the next open
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
    if (!fishStore.loaded) fishStore.fetchProgress();
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
