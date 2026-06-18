<template>
    <div class="flex min-h-[calc(100vh-57px)] items-center justify-center px-4 py-12">
        <div class="w-full max-w-md rounded-2xl bg-white p-8 shadow-lg">
            <h1 class="mb-6 text-2xl font-bold text-slate-900">Zaloguj się</h1>
            <form class="space-y-4" @submit.prevent="handleSubmit">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                    <input
                        v-model="form.email"
                        type="email"
                        required
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Hasło</label>
                    <input
                        v-model="form.password"
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
                    {{ authStore.loading ? 'Logowanie...' : 'Zaloguj' }}
                </button>
            </form>
            <p class="mt-4 text-center text-sm text-slate-600">
                Nie masz konta?
                <router-link to="/register" class="font-medium text-emerald-700 hover:underline">
                    Zarejestruj się
                </router-link>
            </p>
        </div>
    </div>
</template>

<script setup>
import { reactive } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const authStore = useAuthStore();
const router = useRouter();
const route = useRoute();

const form = reactive({
    email: '',
    password: '',
});

function formatError(error) {
    if (typeof error === 'string') return error;
    return Object.values(error).flat().join(' ');
}

async function handleSubmit() {
    const success = await authStore.login(form);
    if (success) {
        router.push(route.query.redirect || '/');
    }
}
</script>
