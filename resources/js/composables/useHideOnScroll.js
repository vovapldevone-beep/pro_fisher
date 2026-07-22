import { onMounted, onUnmounted, ref } from 'vue';

/**
 * Hides an element on scroll down, reveals it on scroll up.
 *
 * Listens on the given scroll container (the app scrolls inside <main>, not <body>).
 * `offset` keeps the element visible near the top so a short page can't hide it,
 * and `threshold` swallows the jitter of momentum scrolling on iOS.
 */
/**
 * Slightly larger than the header, so the whole show/hide layout shift fits inside it.
 */
const BOTTOM_DEAD_ZONE = 80;

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
        const maxY = el.scrollHeight - el.clientHeight;

        // iOS rubber-banding reports scrollTop outside [0, maxY]. Reacting to those
        // values makes the header flap, so treat the overscroll region as "no news".
        if (y < 0 || y > maxY) return;

        if (y <= offset) {
            hidden.value = false;
            lastY = y;
            return;
        }

        // Toggling the header resizes the content row, which changes scrollHeight.
        // At the very bottom that would clamp scrollTop, fire another scroll event
        // and oscillate. Freeze the decision in the last stretch instead.
        if (maxY - y <= BOTTOM_DEAD_ZONE) {
            lastY = y;
            return;
        }

        const delta = y - lastY;
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
