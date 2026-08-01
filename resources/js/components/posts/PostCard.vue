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
        <div
            v-if="locationBadge"
            class="absolute left-2 top-2 min-w-0"
            :class="catchItem.user ? 'right-12' : 'right-2'"
        >
            <LocationBadge :label="locationBadge.label" :url="locationBadge.url" />
        </div>

        <!-- Bottom gradient + info -->
        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/75 via-black/30 to-transparent p-2.5 pt-10 sm:p-3">
            <div class="flex items-baseline justify-between gap-1">
                <p class="min-w-0 truncate text-sm font-semibold text-white sm:text-base">{{ title }}</p>
                <p v-if="catchItem.weight" class="shrink-0 text-xs text-white/90 sm:text-sm">{{ catchItem.weight }} кг</p>
            </div>
            <div class="mt-1 flex flex-wrap items-center justify-between gap-x-2 gap-y-0.5 text-[11px] text-white/70 sm:text-xs">
                <span v-if="displayDate" class="text-white/60">{{ displayDate }}</span>
                <!-- Like + Comment buttons -->
                <div class="flex items-center gap-2.5 sm:gap-3">
                    <button
                        type="button"
                        class="flex items-center gap-1 transition"
                        :class="liked ? 'text-red-400' : 'text-white/70 hover:text-red-400'"
                        @click.stop="handleLike"
                    >
                        <AppIcon name="heart" class="h-3.5 w-3.5 sm:h-4 sm:w-4" :fill="liked ? 'currentColor' : 'none'" />
                        <span>{{ likesCount }}</span>
                    </button>
                    <button
                        type="button"
                        class="flex items-center gap-1 text-white/70 transition hover:text-white"
                        @click.stop="$emit('select')"
                    >
                        <AppIcon name="comment" class="h-3.5 w-3.5 sm:h-4 sm:w-4" />
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
import { computed } from 'vue';
import { useLike } from '../../composables/useLike';
import LocationBadge from '../shared/LocationBadge.vue';
import UserAvatar from '../shared/UserAvatar.vue';
import AppIcon from '../shared/AppIcon.vue';
import { shortPlace } from '../../utils/place';

const props = defineProps({
    catchItem: { type: Object, required: true },
    selected: { type: Boolean, default: false },
});

const emit = defineEmits(['select', 'like-changed']);

const { liked, likesCount, toggle: handleLike } = useLike(
    () => props.catchItem,
    (change) => emit('like-changed', change)
);

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
            label: 'Локація: ' + shortPlace(c.location),
            url: `https://www.google.com/maps/search/${encodeURIComponent(c.location)}`,
        };
    }
    if (c.lake?.latitude && c.lake?.longitude) {
        return {
            label: 'Озеро: ' + shortPlace(c.lake.name),
            url: `https://www.google.com/maps?q=${c.lake.latitude},${c.lake.longitude}`,
        };
    }
    return null;
});

</script>
