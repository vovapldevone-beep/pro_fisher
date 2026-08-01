<template>
    <svg
        :viewBox="viewBox"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
    >
        <path v-for="(d, i) in paths" :key="i" :d="d" />
    </svg>
</template>

<script setup>
import { computed } from 'vue';
import { FALLBACK_ICON, icons } from '../../icons';

const props = defineProps({
    // Often comes straight from the API (`achievement.icon`), so an unknown
    // value must degrade to something drawable rather than render an empty box.
    name: { type: String, required: true },
    viewBox: { type: String, default: '0 0 24 24' },
});

const paths = computed(() => [].concat(icons[props.name] ?? icons[FALLBACK_ICON]));
</script>
