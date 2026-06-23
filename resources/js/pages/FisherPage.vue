<template>
    <div class="bg-slate-50 py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div v-if="loading" class="py-24 text-center text-slate-500">Завантаження...</div>

            <template v-else-if="fisher">
                <!-- Top row -->
                <div class="grid gap-6 lg:grid-cols-12">
                    <div class="lg:col-span-4">
                        <FisherProfileCard
                            :profile="fisher.profile"
                            :is-following="isFollowing"
                            @toggle-follow="handleToggleFollow"
                        />
                    </div>
                    <div class="lg:col-span-8">
                        <ProfileStats :stats="fisher.stats" />
                    </div>
                </div>

                <!-- Bottom row -->
                <div class="mt-6 grid gap-6 lg:grid-cols-12">
                    <div class="lg:col-span-4">
                        <AchievementsCard :achievements="fisher.achievements" />
                    </div>
                    <div class="lg:col-span-8">
                        <RecentCatchesCard
                            :catches="recentCatches"
                            :show-add-button="false"
                        />
                    </div>
                </div>
            </template>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useHead } from '@unhead/vue';
import { fetchFisher, followFisher, unfollowFisher } from '../api/fishers';
import { useAuthStore } from '../stores/auth';
import AchievementsCard from '../components/cabinet/AchievementsCard.vue';
import FisherProfileCard from '../components/cabinet/FisherProfileCard.vue';
import ProfileStats from '../components/cabinet/ProfileStats.vue';
import RecentCatchesCard from '../components/cabinet/RecentCatchesCard.vue';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const fisher = ref(null);
const loading = ref(true);
const isFollowing = ref(false);

useHead({
    title: computed(() =>
        fisher.value?.profile?.name
            ? `${fisher.value.profile.name} — рибалка | Pro Fisher`
            : 'Pro Fisher'
    ),
    meta: computed(() => {
        if (!fisher.value?.profile) return [];
        const stats = fisher.value.stats;
        const description = `${fisher.value.profile.name} на Pro Fisher. Уловів: ${stats?.catches_count ?? 0}, підписників: ${stats?.followers_count ?? 0}.`;
        return [
            { name: 'description', content: description },
            { property: 'og:title', content: `${fisher.value.profile.name} | Pro Fisher` },
            { property: 'og:description', content: description },
        ];
    }),
});

const recentCatches = computed(() =>
    fisher.value?.recent_catches?.data ?? fisher.value?.recent_catches ?? []
);

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
}

async function handleToggleFollow() {
    const id = route.params.id;
    if (isFollowing.value) {
        await unfollowFisher(id);
        isFollowing.value = false;
        fisher.value.stats.followers_count--;
    } else {
        await followFisher(id);
        isFollowing.value = true;
        fisher.value.stats.followers_count++;
    }
}

onMounted(() => load(route.params.id));

watch(() => route.params.id, (id) => {
    if (id) load(id);
});
</script>
