import { computed, onMounted, onUnmounted, ref, unref } from 'vue';

/**
 * Slides an element out of view as the page scrolls down and back in as it
 * scrolls up — following the finger rather than flipping between two states.
 *
 * It used to be a boolean: one scroll gesture past a threshold and the header
 * jumped the whole way up in a 300 ms transition, which reads as a flinch on a
 * phone. Now `offset` tracks the scroll delta, so the header is exactly as far
 * up as you have scrolled and counts as hidden the moment its bottom edge meets
 * the top of the screen.
 *
 * Listens on the given scroll container (the app scrolls inside <main>, not
 * <body>). This only works without layout jitter because the space for the
 * header lives *inside* the scroll container — sliding it away no longer
 * changes any element's height, so scrollHeight stays put.
 *
 * @param containerRef  the scrolling element
 * @param height        travel distance before it is fully hidden — a number or
 *                      a ref, since the real header height is only known once
 *                      it has rendered (and 5 px of it stayed on screen while
 *                      this was hardcoded to the reserved 65)
 * @param snapDelay     ms of stillness after which a half-open element settles
 */
export function useHideOnScroll(containerRef, { height = 65, snapDelay = 140 } = {}) {
    const offset = ref(0);
    const settling = ref(false);

    const travel = () => unref(height) || 1;
    const hidden = computed(() => offset.value >= travel() - 1);

    let lastY = 0;
    let ticking = false;
    let snapTimer = null;

    const clamp = (v) => Math.min(travel(), Math.max(0, v));

    function show() {
        clearTimeout(snapTimer);
        settling.value = false;
        offset.value = 0;
        lastY = containerRef.value?.scrollTop ?? 0;
    }

    /** Leaving the header half-way up looks like a bug, so it settles either way. */
    function scheduleSnap() {
        clearTimeout(snapTimer);

        if (offset.value === 0 || offset.value === travel()) return;

        snapTimer = setTimeout(() => {
            settling.value = true;
            offset.value = offset.value > travel() / 2 ? travel() : 0;
            setTimeout(() => { settling.value = false; }, 220);
        }, snapDelay);
    }

    function evaluate() {
        ticking = false;

        const el = containerRef.value;
        if (!el) return;

        const y = el.scrollTop;
        const maxY = el.scrollHeight - el.clientHeight;

        // iOS rubber-banding reports scrollTop outside [0, maxY]. Reacting to
        // those values makes the header flap, so treat that region as no news.
        if (y < 0 || y > maxY) return;

        const delta = y - lastY;
        lastY = y;

        settling.value = false;

        // The scroll position near the top is a *cap*, not an assignment. It
        // guarantees the header is whole again at scrollTop 0 with no drift —
        // but assigning it would push an already-revealed header back down: a
        // flick to the top revealed it at y=300, then y=60 shoved it 60px up
        // again and it slid in a second time.
        offset.value = Math.min(clamp(offset.value + delta), y);

        scheduleSnap();
    }

    function onScroll() {
        if (ticking) return;
        ticking = true;
        requestAnimationFrame(evaluate);
    }

    onMounted(() => {
        containerRef.value?.addEventListener('scroll', onScroll, { passive: true });
    });

    onUnmounted(() => {
        clearTimeout(snapTimer);
        containerRef.value?.removeEventListener('scroll', onScroll);
    });

    return { offset, hidden, settling, show };
}
