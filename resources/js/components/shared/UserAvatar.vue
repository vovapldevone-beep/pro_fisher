<template>
    <button
        type="button"
        class="shrink-0 overflow-hidden rounded-full ring-0 transition hover:ring-2 hover:ring-emerald-400 hover:ring-offset-1 focus:outline-none"
        :class="sizeClass"
        :title="user?.name"
        @click.stop="navigate"
    >
        <img
            v-if="user?.avatar_url"
            :src="user.avatar_url"
            :alt="user.name"
            class="h-full w-full object-cover"
        />
        <span
            v-else
            class="flex h-full w-full items-center justify-center bg-emerald-500 font-bold text-white"
            :class="textClass"
        >
            {{ initials }}
        </span>
    </button>
</template>

<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';

const props = defineProps({
    user: { type: Object, default: null },
    size: { type: String, default: 'md' }, // sm | md | lg
});

const router = useRouter();
const authStore = useAuthStore();

const sizeClass = computed(() => ({
    sm: 'h-7 w-7',
    md: 'h-9 w-9',
    lg: 'h-11 w-11',
}[props.size] ?? 'h-9 w-9'));

const textClass = computed(() => ({
    sm: 'text-[10px]',
    md: 'text-xs',
    lg: 'text-sm',
}[props.size] ?? 'text-xs'));

const initials = computed(() => {
    const name = props.user?.name || '?';
    return name.split(' ').map((n) => n[0]).join('').slice(0, 2).toUpperCase();
});

function navigate() {
    if (!props.user?.id) return;
    if (String(props.user.id) === String(authStore.user?.id)) {
        router.push({ name: 'cabinet' });
    } else {
        router.push({ name: 'fisher', params: { id: props.user.id } });
    }
}
</script>
