import { onUnmounted, watch } from 'vue';

/**
 * Freezes the page behind an open modal.
 *
 * The app scrolls inside <main>, not <body> (App.vue root is 100dvh + overflow-hidden),
 * so that is the element we lock. Padding compensates for the vanishing scrollbar
 * to stop the content behind the overlay from jumping sideways.
 */
export function useScrollLock(isLocked) {
    let el = null;
    let scrollTop = 0;

    function lock() {
        el = document.querySelector('main');
        if (!el || el.style.overflow === 'hidden') return;

        scrollTop = el.scrollTop;
        const scrollbarWidth = el.offsetWidth - el.clientWidth;

        el.style.overflow = 'hidden';
        if (scrollbarWidth > 0) el.style.paddingRight = `${scrollbarWidth}px`;
    }

    function unlock() {
        if (!el) return;

        el.style.overflow = '';
        el.style.paddingRight = '';
        el.scrollTop = scrollTop;
        el = null;
    }

    watch(isLocked, (locked) => (locked ? lock() : unlock()), { immediate: true });

    onUnmounted(unlock);
}
