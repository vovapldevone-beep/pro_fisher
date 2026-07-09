import { createApp } from 'vue';
import { createPinia } from 'pinia';
import { createUnhead, headSymbol } from '@unhead/vue';
import { i18n } from './i18n';
import App from './App.vue';
import router from './router';
import { useAuthStore } from './stores/auth';

const app = createApp(App);
const pinia = createPinia();
const head = createUnhead();

app.use(pinia);
app.use(router);
app.use(i18n);
app.use({
    install(a) {
        a.config.globalProperties.$unhead = head;
        a.config.globalProperties.$head = head;
        a.provide(headSymbol, head);
    },
});

const authStore = useAuthStore();
authStore.fetchUser().finally(() => {
    app.mount('#app');
});
