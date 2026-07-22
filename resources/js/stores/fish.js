import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import api from '../api/client';

/**
 * The "find 10 hidden fish" easter egg. Progress is per-user and server-capped;
 * this store mirrors it and lets the header + feed react.
 */
export const useFishStore = defineStore('fish', () => {
    const found = ref(0);
    const total = ref(10);
    const loaded = ref(false);
    // Fires only when the *final* fish is caught this session — not when a
    // fetch loads an already-completed tally. That's what gates the fireworks.
    const justCompleted = ref(false);

    const remaining = computed(() => Math.max(0, total.value - found.value));
    const completed = computed(() => loaded.value && found.value >= total.value);

    async function fetchProgress() {
        try {
            const { data } = await api.get('/fish-hunt');
            found.value = data.found;
            total.value = data.total;
            loaded.value = true;
        } catch {
            // Guest or offline — leave at defaults, the hunt just won't show
        }
    }

    // Optimistic bump keeps the click snappy; the server response is authoritative
    async function recordFind() {
        if (remaining.value === 0) return;
        const wasComplete = found.value >= total.value;
        found.value += 1;
        try {
            const { data } = await api.post('/fish-hunt/find');
            found.value = data.found;
            total.value = data.total;
            if (! wasComplete && found.value >= total.value) {
                justCompleted.value = true;
            }
        } catch {
            found.value = Math.max(0, found.value - 1);
        }
    }

    function acknowledgeCompletion() {
        justCompleted.value = false;
    }

    function reset() {
        found.value = 0;
        loaded.value = false;
        justCompleted.value = false;
    }

    return {
        found, total, remaining, completed, loaded, justCompleted,
        fetchProgress, recordFind, acknowledgeCompletion, reset,
    };
});
