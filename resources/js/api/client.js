import axios from 'axios';

const api = axios.create({
    baseURL: '/api',
    withCredentials: true,
    headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

api.interceptors.request.use((config) => {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (token) {
        config.headers['X-CSRF-TOKEN'] = token;
    }
    return config;
});

// A ban has to take effect on the open session, not at the next login: the
// `blocked` flag is what EnsureNotBlocked sends, and it is what tells this
// apart from the admin-only 403. Imports are dynamic — the auth store and the
// router both import this module.
api.interceptors.response.use(
    (response) => response,
    async (error) => {
        if (error.response?.status === 403 && error.response.data?.blocked) {
            const { useAuthStore } = await import('../stores/auth');
            const { default: router } = await import('../router');

            useAuthStore().user = null;
            if (router.currentRoute.value.name !== 'login') {
                router.replace({ name: 'login', query: { error: 'blocked' } });
            }
        }

        return Promise.reject(error);
    }
);

export async function initCsrf() {
    await axios.get('/sanctum/csrf-cookie', { withCredentials: true });
}

export default api;
