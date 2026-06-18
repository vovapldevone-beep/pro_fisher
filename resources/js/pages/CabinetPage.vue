<template>
    <div class="bg-slate-50 py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div v-if="loading" class="py-24 text-center text-slate-500">Завантаження...</div>

            <template v-else-if="cabinet">
                <div class="grid gap-6 lg:grid-cols-12">
                    <div class="lg:col-span-4">
                        <ProfileCard :profile="cabinet.profile" @edit="showEditProfile = true" />
                    </div>
                    <div class="lg:col-span-5">
                        <ProfileStats :stats="cabinet.stats" />
                    </div>
                    <div class="lg:col-span-3">
                        <PermitsCard :permits="cabinet.permits" />
                    </div>
                </div>

                <div class="mt-6 grid gap-6 lg:grid-cols-12">
                    <div class="lg:col-span-4">
                        <AchievementsCard :achievements="cabinet.achievements" />
                    </div>
                    <div class="lg:col-span-4">
                        <RecentCatchesCard
                            :catches="cabinet.recent_catches"
                            @add-catch="showAddCatch = true"
                            @add-post="showAddPost = true"
                        />
                    </div>
                    <div class="lg:col-span-4">
                        <ActivityFeed :activity="cabinet.activity" />
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
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { fetchCabinet } from '../api/cabinet';
import AchievementsCard from '../components/cabinet/AchievementsCard.vue';
import ActivityFeed from '../components/cabinet/ActivityFeed.vue';
import AddCatchModal from '../components/cabinet/AddCatchModal.vue';
import AddPostModal from '../components/cabinet/AddPostModal.vue';
import EditProfileModal from '../components/cabinet/EditProfileModal.vue';
import PermitsCard from '../components/cabinet/PermitsCard.vue';
import ProfileCard from '../components/cabinet/ProfileCard.vue';
import ProfileStats from '../components/cabinet/ProfileStats.vue';
import RecentCatchesCard from '../components/cabinet/RecentCatchesCard.vue';
import { useAuthStore } from '../stores/auth';
import { useCatchesStore } from '../stores/catches';
import { useLakesStore } from '../stores/lakes';

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

async function loadCabinet() {
    loading.value = true;
    try {
        cabinet.value = await fetchCabinet();
        cabinet.value.recent_catches = cabinet.value.recent_catches?.data ?? cabinet.value.recent_catches;
    } finally {
        loading.value = false;
    }
}

async function handleAddCatch(formData) {
    await catchesStore.addCatch(formData);
    showAddCatch.value = false;
    await loadCabinet();
}

async function handleAddPost(formData) {
    await catchesStore.addCatch(formData);
    showAddPost.value = false;
    await loadCabinet();
}

function handleProfileSaved(updatedUser) {
    if (cabinet.value) {
        cabinet.value.profile.name = updatedUser.name;
        cabinet.value.profile.avatar_url = updatedUser.avatar_url;
    }
    authStore.user.name = updatedUser.name;
    authStore.user.avatar_url = updatedUser.avatar_url;
}

watch(() => route.query.action, (action) => {
    if (!action) return;
    if (action === 'add-catch') showAddCatch.value = true;
    if (action === 'add-post') showAddPost.value = true;
    router.replace({ query: {} });
}, { immediate: true });

onMounted(async () => {
    await Promise.all([loadCabinet(), lakesStore.loadLakes()]);
});
</script>
