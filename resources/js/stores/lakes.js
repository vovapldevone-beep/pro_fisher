import { defineStore } from 'pinia';
import { ref } from 'vue';
import { fetchLakes, fetchLake } from '../api/lakes';

export const useLakesStore = defineStore('lakes', () => {
    const lakes = ref([]);
    const selectedLake = ref(null);
    const loading = ref(false);
    const detailLoading = ref(false);

    async function loadLakes() {
        loading.value = true;
        try {
            lakes.value = await fetchLakes();
        } finally {
            loading.value = false;
        }
    }

    async function loadLake(slug) {
        detailLoading.value = true;
        try {
            selectedLake.value = await fetchLake(slug);
        } finally {
            detailLoading.value = false;
        }
    }

    function clearSelectedLake() {
        selectedLake.value = null;
    }

    return {
        lakes,
        selectedLake,
        loading,
        detailLoading,
        loadLakes,
        loadLake,
        clearSelectedLake,
    };
});
