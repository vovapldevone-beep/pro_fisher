import { defineStore } from 'pinia';
import { ref } from 'vue';
import { fetchCatches, createCatch, updateCatch, deleteCatch } from '../api/catches';

export const useCatchesStore = defineStore('catches', () => {
    const catches = ref([]);
    const loading = ref(false);
    const saving = ref(false);
    // Last publish failure, shown inside the add modal. Without it a rejected
    // request (a rate limit, a 5 MB photo) left the button springing back with
    // nothing on screen.
    const error = ref('');

    async function loadCatches() {
        loading.value = true;
        try {
            catches.value = await fetchCatches();
        } finally {
            loading.value = false;
        }
    }

    async function addCatch(formData) {
        saving.value = true;
        error.value = '';
        try {
            const created = await createCatch(formData);
            catches.value.unshift(created);
            return created;
        } catch (e) {
            error.value = e.response?.data?.message || 'Не вдалося опублікувати. Спробуйте ще раз.';
            throw e;
        } finally {
            saving.value = false;
        }
    }

    async function editCatch(id, formData) {
        saving.value = true;
        try {
            const updated = await updateCatch(id, formData);
            const index = catches.value.findIndex((c) => c.id === id);
            if (index !== -1) {
                catches.value[index] = updated;
            }
            return updated;
        } finally {
            saving.value = false;
        }
    }

    async function removeCatch(id) {
        await deleteCatch(id);
        catches.value = catches.value.filter((c) => c.id !== id);
    }

    return {
        catches,
        loading,
        saving,
        error,
        loadCatches,
        addCatch,
        editCatch,
        removeCatch,
    };
});
