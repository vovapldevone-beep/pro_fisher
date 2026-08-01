<template>
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-start gap-4">
            <img
                :src="profile.avatar_url || defaultAvatar"
                :alt="profile.name"
                class="h-20 w-20 shrink-0 rounded-full object-cover"
            />
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-xl font-bold text-slate-900">{{ profile.name }}</h2>
                    <span
                        v-if="profile.badge"
                        class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-700"
                    >
                        {{ profile.badge }}
                    </span>
                </div>
                <p v-if="profile.location" class="mt-1 flex items-center gap-1 text-sm text-slate-500">
                    <AppIcon name="map-pin" class="h-4 w-4" />
                    {{ profile.location }}
                </p>
                <p v-if="profile.bio" class="mt-2 text-sm text-slate-600">{{ profile.bio }}</p>
            </div>
        </div>

        <button
            type="button"
            class="mt-4 w-full rounded-lg py-2.5 text-sm font-semibold transition"
            :class="isFollowing
                ? 'border border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100'
                : 'bg-emerald-600 text-white hover:bg-emerald-700'"
            @click="$emit('toggle-follow')"
        >
            {{ isFollowing ? 'Відписатись' : '+ Підписатись' }}
        </button>
    </div>
</template>

<script setup>
import AppIcon from '../shared/AppIcon.vue';
defineProps({
    profile: {
        type: Object,
        required: true,
    },
    isFollowing: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['toggle-follow']);

const defaultAvatar = 'https://images.unsplash.com/photo-1504307651254-35680f356dfd?w=200';
</script>
