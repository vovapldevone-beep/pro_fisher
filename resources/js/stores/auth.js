import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api, { initCsrf } from '../api/client';

export const useAuthStore = defineStore('auth', () => {
    const user = ref(null);
    const loading = ref(false);
    const error = ref(null);

    const isAuthenticated = computed(() => !!user.value);

    async function fetchUser() {
        try {
            const { data } = await api.get('/user');
            user.value = data.user;
        } catch {
            user.value = null;
        }
    }

    async function register(form) {
        loading.value = true;
        error.value = null;
        try {
            await initCsrf();
            const { data } = await api.post('/register', form);
            user.value = data.user;
            return true;
        } catch (e) {
            error.value = e.response?.data?.message || e.response?.data?.errors || 'Błąd rejestracji';
            return false;
        } finally {
            loading.value = false;
        }
    }

    async function login(form) {
        loading.value = true;
        error.value = null;
        try {
            await initCsrf();
            const { data } = await api.post('/login', form);
            user.value = data.user;
            return true;
        } catch (e) {
            error.value = e.response?.data?.message || 'Nieprawidłowe dane logowania';
            return false;
        } finally {
            loading.value = false;
        }
    }

    async function logout() {
        try {
            await api.post('/logout');
        } finally {
            user.value = null;
        }
    }

    return {
        user,
        loading,
        error,
        isAuthenticated,
        fetchUser,
        register,
        login,
        logout,
    };
});
