<template>
    <div class="min-h-screen bg-slate-50 text-slate-900">
        <AppHeader />
        <AppSidebar v-if="authStore.isAuthenticated" />
        <main :class="authStore.isAuthenticated ? 'ml-16 pt-[65px]' : 'pt-[65px]'">
            <router-view />
        </main>
    </div>
</template>

<script setup>
import AppHeader from './components/layout/AppHeader.vue';
import AppSidebar from './components/layout/AppSidebar.vue';
import { useAuthStore } from './stores/auth';

const authStore = useAuthStore();
</script>
