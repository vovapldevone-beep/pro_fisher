import { onUnmounted, ref, unref, watch } from 'vue';

/**
 * Loads the next page when a sentinel element scrolls into view.
 *
 * Two things a naive `new IntersectionObserver(...)` in `onMounted` gets wrong:
 *
 *  1. The sentinel is re-created every time its `v-if` branch remounts (tab
 *     switches). Observing it once leaves the observer watching a detached node.
 *     Watching the ref re-attaches the observer to whatever node is current.
 *
 *  2. The observer only fires on a *change* of intersection. If a freshly loaded
 *     page is too short to push the sentinel off screen, it stays intersecting
 *     and nothing more ever loads. Keeping `isIntersecting` as state and reacting
 *     to `loading` falling back to false continues the chain.
 */
export function useInfiniteScroll(sentinelRef, { loading, hasMore, onLoad, rootMargin = '300px', root = null }) {
    const isIntersecting = ref(false);
    let observer = null;

    function disconnect() {
        observer?.disconnect();
        observer = null;
    }

    watch(sentinelRef, (el) => {
        disconnect();
        isIntersecting.value = false;
        if (!el) return;

        observer = new IntersectionObserver(
            ([entry]) => (isIntersecting.value = entry.isIntersecting),
            // The app scrolls inside <main>, not the window. `root` overrides that
            // for lists that scroll in their own box, e.g. inside a modal.
            { root: unref(root) ?? document.querySelector('main'), rootMargin, threshold: 0 },
        );
        observer.observe(el);
    }, { immediate: true, flush: 'post' });

    watch([isIntersecting, loading, hasMore], () => {
        if (isIntersecting.value && !loading.value && hasMore.value) onLoad();
    });

    onUnmounted(disconnect);

    return { isIntersecting };
}
