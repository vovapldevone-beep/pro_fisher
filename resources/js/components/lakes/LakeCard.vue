<template>
    <div class="flex h-full flex-col">
        <div class="flex items-center justify-between border-b border-slate-200 p-4">
            <h2 class="text-xl font-bold text-slate-900">{{ lake?.name }}</h2>
            <button
                type="button"
                class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                @click="$emit('close')"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div v-if="loading" class="flex flex-1 items-center justify-center p-8">
            <p class="text-slate-500">{{ t('common.loading') }}</p>
        </div>

        <div v-else-if="lake" class="flex-1 overflow-y-auto p-4">
            <div v-if="lake.photos?.length" class="mb-4 flex gap-2 overflow-x-auto pb-2">
                <img
                    v-for="photo in lake.photos"
                    :key="photo.id"
                    :src="photo.url"
                    :alt="lake.name"
                    class="h-40 w-56 shrink-0 rounded-xl object-cover"
                />
            </div>

            <div class="mb-4 space-y-2">
                <p v-if="lake.region" class="text-sm text-emerald-700">{{ lake.region }}</p>
                <a
                    v-if="lake.address"
                    :href="mapsUrl"
                    target="_blank"
                    rel="noopener"
                    class="group inline-flex items-start gap-1.5 text-sm text-slate-500 transition hover:text-blue-600"
                >
                    <svg class="mt-0.5 h-4 w-4 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                    </svg>
                    <span class="group-hover:underline">{{ lake.address }}</span>
                </a>
                <p v-if="lake.price" class="text-lg font-semibold text-slate-900">
                    {{ lake.price }} {{ t('map.perDay') }}
                </p>
                <p v-if="lake.description" class="text-sm leading-relaxed text-slate-600">
                    {{ lake.description }}
                </p>
            </div>

            <RecentCatches :catches="lake.recent_catches" />

            <button
                type="button"
                class="mt-4 flex w-full items-center justify-center rounded-lg bg-emerald-600 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700"
                @click="handleMore"
            >
                {{ t('map.more') }}
            </button>
        </div>
    </div>

    <!-- Login modal -->
    <Teleport to="body">
        <div v-if="showLoginModal" class="fixed inset-0 z-[2000] flex items-center justify-center bg-black/50 p-4" @click.self="showLoginModal = false">
            <div class="w-full max-w-md rounded-2xl bg-white p-8 shadow-xl">
                <div class="mb-6 flex items-center justify-between">
                    <h2 class="text-xl font-bold text-slate-900">{{ t('auth.loginToContinue') }}</h2>
                    <button
                        type="button"
                        class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                        @click="showLoginModal = false"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form class="space-y-4" @submit.prevent="handleModalLogin">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                        <input
                            v-model="loginForm.email"
                            type="email"
                            required
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">{{ t('auth.password') }}</label>
                        <input
                            v-model="loginForm.password"
                            type="password"
                            required
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                        />
                    </div>
                    <p v-if="authStore.error" class="text-sm text-red-600">
                        {{ formatError(authStore.error) }}
                    </p>
                    <button
                        type="submit"
                        :disabled="authStore.loading"
                        class="w-full rounded-lg bg-emerald-600 py-2.5 font-medium text-white hover:bg-emerald-700 disabled:opacity-50"
                    >
                        {{ authStore.loading ? t('auth.logging') : t('header.login') }}
                    </button>
                </form>

                <p class="mt-4 text-center text-sm text-slate-600">
                    {{ t('auth.noAccount') }}
                    <router-link
                        :to="{ name: 'register' }"
                        class="font-medium text-emerald-700 hover:underline"
                        @click="showLoginModal = false"
                    >
                        {{ t('auth.register') }}
                    </router-link>
                </p>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '../../stores/auth';
import RecentCatches from './RecentCatches.vue';

const { t } = useI18n();

const props = defineProps({
    lake: {
        type: Object,
        default: null,
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['close']);

// Coordinates drop a pin exactly on the lake; the address text is only a fallback
// because Google resolves it to whatever it thinks matches.
const mapsUrl = computed(() => {
    const l = props.lake;
    if (!l) return '';
    if (l.latitude && l.longitude) {
        return `https://www.google.com/maps?q=${l.latitude},${l.longitude}`;
    }
    return `https://www.google.com/maps/search/${encodeURIComponent(l.address)}`;
});

const authStore = useAuthStore();
const router = useRouter();
const showLoginModal = ref(false);
const loginForm = reactive({ email: '', password: '' });

function handleMore() {
    if (authStore.isAuthenticated) {
        router.push({ name: 'lake-detail', params: { slug: props.lake.slug } });
    } else {
        authStore.error = null;
        showLoginModal.value = true;
    }
}

async function handleModalLogin() {
    const success = await authStore.login(loginForm);
    if (success) {
        showLoginModal.value = false;
        router.push({ name: 'lake-detail', params: { slug: props.lake.slug } });
    }
}

function formatError(error) {
    if (typeof error === 'string') return error;
    return Object.values(error).flat().join(' ');
}
</script>
