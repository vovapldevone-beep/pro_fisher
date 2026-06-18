import { defineStore } from 'pinia';
import { ref } from 'vue';
import { fetchCatches, createCatch, updateCatch, deleteCatch } from '../api/catches';

export const useCatchesStore = defineStore('catches', () => {
    const catches = ref([]);
    const loading = ref(false);
    const saving = ref(false);

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
        try {
            const created = await createCatch(formData);
            catches.value.unshift(created);
            return created;
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
        loadCatches,
        addCatch,
        editCatch,
        removeCatch,
    };
});
