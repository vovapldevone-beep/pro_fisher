<template>
    <button
        type="button"
        class="pointer-events-auto absolute cursor-pointer select-none text-xl leading-none"
        :class="[anchor, caught ? 'fish-caught' : 'fish-idle']"
        :style="caught ? caughtStyle : null"
        aria-label="🐟"
        @click.stop="onClick"
    >
        🐟
    </button>
</template>

<script setup>
import { ref } from 'vue';

defineProps({
    // Tailwind position + z-index classes deciding where the fish hides
    anchor: { type: String, default: '' },
});

const emit = defineEmits(['caught']);

const caught = ref(false);

// Inline z-index beats the anchor's `-z-10` class, so a fish that was tucked
// behind a card pops fully in front while the catch animation plays.
const caughtStyle = { zIndex: 60 };

function onClick() {
    if (caught.value) return;
    caught.value = true;
    // Let the pop animation play before the parent unmounts us
    setTimeout(() => emit('caught'), 380);
}
</script>

<style scoped>
/* A faint idle wiggle makes it findable without shouting for attention */
.fish-idle {
    opacity: 0.8;
    animation: fish-wiggle 3s ease-in-out infinite;
    transition: transform 0.15s ease, opacity 0.15s ease;
}
.fish-idle:hover {
    opacity: 1;
    transform: scale(1.3) rotate(0deg);
}
.fish-caught {
    animation: fish-catch 0.38s ease-out forwards;
}

@keyframes fish-wiggle {
    0%, 100% { transform: rotate(-7deg); }
    50% { transform: rotate(7deg); }
}
@keyframes fish-catch {
    0% { transform: scale(1) rotate(0deg); }
    40% { transform: scale(1.6) rotate(18deg); }
    100% { transform: scale(0) translateY(-18px); opacity: 0; }
}

@media (prefers-reduced-motion: reduce) {
    .fish-idle { animation: none; }
}
</style>
