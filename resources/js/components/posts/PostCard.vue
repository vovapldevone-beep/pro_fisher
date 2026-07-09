<template>
    <div
        class="group relative aspect-square overflow-hidden rounded-xl bg-slate-100 transition cursor-pointer"
        :class="selected ? 'ring-2 ring-emerald-500 shadow-lg' : 'hover:shadow-lg'"
        @click="$emit('select')"
    >
        <!-- Photo -->
        <img
            v-if="catchItem.photo_url"
            :src="catchItem.photo_url"
            :alt="catchItem.fish_name"
            class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
        />
        <div
            v-else
            class="flex h-full w-full items-center justify-center text-6xl"
        >
            🐟
        </div>

        <!-- Location badge -->
        <div v-if="locationBadge" class="absolute left-2 top-2">
            <LocationBadge :label="locationBadge.label" :url="locationBadge.url" />
        </div>

        <!-- Bottom gradient + info -->
        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/75 via-black/30 to-transparent p-3 pt-10">
            <div class="flex items-baseline justify-between gap-1">
                <p class="truncate font-semibold text-white">{{ title }}</p>
                <p v-if="catchItem.weight" class="shrink-0 text-sm text-white/90">{{ catchItem.weight }} кг</p>
            </div>
            <div class="mt-1 flex items-center justify-between text-xs text-white/70">
                <span v-if="displayDate" class="text-white/60">{{ displayDate }}</span>
                <!-- Like + Comment buttons -->
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        class="flex items-center gap-1 transition"
                        :class="liked ? 'text-red-400' : 'text-white/70 hover:text-red-400'"
                        @click.stop="handleLike"
                    >
                        <svg class="h-4 w-4" :fill="liked ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                        <span>{{ likesCount }}</span>
                    </button>
                    <button
                        type="button"
                        class="flex items-center gap-1 text-white/70 transition hover:text-white"
                        @click.stop="$emit('select')"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                        <span>{{ catchItem.comments_count ?? 0 }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- User avatar (top-right) -->
        <div v-if="catchItem.user" class="absolute right-2 top-2">
            <UserAvatar :user="catchItem.user" size="md" />
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { toggleLike } from '../../api/catches';
import LocationBadge from '../shared/LocationBadge.vue';
import UserAvatar from '../shared/UserAvatar.vue';

const props = defineProps({
    catchItem: { type: Object, required: true },
    selected: { type: Boolean, default: false },
});

const emit = defineEmits(['select', 'like-changed']);

const router = useRouter();
const authStore = useAuthStore();


const liked = ref(props.catchItem.is_liked ?? false);
const likesCount = ref(props.catchItem.likes_count ?? 0);

const title = computed(() => {
    const full = props.catchItem.fish_name || props.catchItem.notes || 'Пост';
    const words = full.trim().split(/\s+/);
    return words.length > 4 ? words.slice(0, 4).join(' ') + '...' : full;
});

const displayDate = computed(() => {
    const raw = props.catchItem.caught_at || props.catchItem.created_at;
    if (!raw) return null;
    return new Date(raw).toLocaleDateString('uk-UA');
});

const locationBadge = computed(() => {
    const c = props.catchItem;
    if (c.location) {
        return {
            label: 'Локація: ' + c.location,
            url: `https://www.google.com/maps/search/${encodeURIComponent(c.location)}`,
        };
    }
    if (c.lake?.latitude && c.lake?.longitude) {
        return {
            label: 'Озеро: ' + c.lake.name,
            url: `https://www.google.com/maps?q=${c.lake.latitude},${c.lake.longitude}`,
        };
    }
    return null;
});

async function handleLike() {
    if (!authStore.isAuthenticated) {
        router.push({ name: 'login' });
        return;
    }
    liked.value = !liked.value;
    likesCount.value += liked.value ? 1 : -1;
    try {
        const result = await toggleLike(props.catchItem.id);
        liked.value = result.liked;
        likesCount.value = result.likes_count;
        emit('like-changed', { id: props.catchItem.id, liked: result.liked, likes_count: result.likes_count });
    } catch {
        liked.value = !liked.value;
        likesCount.value += liked.value ? 1 : -1;
    }
}

</script>
