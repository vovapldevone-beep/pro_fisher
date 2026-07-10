import { onMounted, onUnmounted, ref } from 'vue';

/**
 * Hides an element on scroll down, reveals it on scroll up.
 *
 * Listens on the given scroll container (the app scrolls inside <main>, not <body>).
 * `offset` keeps the element visible near the top so a short page can't hide it,
 * and `threshold` swallows the jitter of momentum scrolling on iOS.
 */
export function useHideOnScroll(containerRef, { threshold = 8, offset = 65 } = {}) {
    const hidden = ref(false);

    let lastY = 0;
    let ticking = false;

    function show() {
        hidden.value = false;
    }

    function evaluate() {
        ticking = false;

        const el = containerRef.value;
        if (!el) return;

        const y = el.scrollTop;
        const delta = y - lastY;

        if (y <= offset) {
            hidden.value = false;
            lastY = y;
            return;
        }

        if (Math.abs(delta) < threshold) return;

        hidden.value = delta > 0;
        lastY = y;
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
        containerRef.value?.removeEventListener('scroll', onScroll);
    });

    return { hidden, show };
}
