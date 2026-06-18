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

        <!-- Bottom gradient + info -->
        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/75 via-black/30 to-transparent p-3 pt-10">
            <div class="flex items-baseline justify-between gap-1">
                <p class="truncate font-semibold text-white">{{ catchItem.fish_name }}</p>
                <p v-if="catchItem.weight" class="shrink-0 text-sm text-white/90">{{ catchItem.weight }} кг</p>
            </div>
            <div class="mt-1 flex items-center justify-between text-xs text-white/70">
                <span v-if="catchItem.lake" class="flex items-center gap-1 truncate">
                    <svg class="h-3 w-3 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" />
                    </svg>
                    {{ catchItem.lake.name }}
                </span>
                <!-- Like button -->
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
            </div>
        </div>

        <!-- User avatar (top-right) -->
        <button
            v-if="catchItem.user"
            type="button"
            class="absolute right-2 top-2 h-9 w-9 overflow-hidden rounded-full border-2 border-white shadow-lg transition hover:scale-110"
            :title="catchItem.user.name"
            @click.stop="goToFisher"
        >
            <img
                v-if="catchItem.user.avatar_url"
                :src="catchItem.user.avatar_url"
                :alt="catchItem.user.name"
                class="h-full w-full object-cover"
            />
            <div
                v-else
                class="flex h-full w-full items-center justify-center bg-emerald-500 text-xs font-bold text-white"
            >
                {{ initials }}
            </div>
        </button>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { toggleLike } from '../../api/catches';

const props = defineProps({
    catchItem: { type: Object, required: true },
    selected: { type: Boolean, default: false },
});

const emit = defineEmits(['select', 'like-changed']);

const router = useRouter();
const authStore = useAuthStore();

const liked = ref(props.catchItem.is_liked ?? false);
const likesCount = ref(props.catchItem.likes_count ?? 0);

const initials = computed(() => {
    const name = props.catchItem.user?.name || '?';
    return name.split(' ').map((n) => n[0]).join('').slice(0, 2).toUpperCase();
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

function goToFisher() {
    if (!props.catchItem.user?.id) return;
    if (String(props.catchItem.user.id) === String(authStore.user?.id)) {
        router.push({ name: 'cabinet' });
    } else {
        router.push({ name: 'fisher', params: { id: props.catchItem.user.id } });
    }
}

function formatDate(date) {
    if (!date) return '';
    return new Date(date).toLocaleDateString('uk-UA');
}
</script>
