<template>
    <!-- pointer-events-none: celebratory, never blocks the UI underneath -->
    <canvas
        v-if="show"
        ref="canvas"
        class="pointer-events-none fixed inset-0 z-[100]"
    ></canvas>
</template>

<script setup>
import { nextTick, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
});

const emit = defineEmits(['done']);

const canvas = ref(null);
const COLORS = ['#f43f5e', '#f59e0b', '#10b981', '#3b82f6', '#a855f7', '#ec4899', '#eab308'];
const DURATION = 4500;
const LAUNCH_UNTIL = 3000;

let ctx = null;
let raf = 0;
let particles = [];
let rockets = [];
let startTime = 0;
let w = 0;
let h = 0;

function resize() {
    if (!canvas.value) return;
    w = canvas.value.width = window.innerWidth;
    h = canvas.value.height = window.innerHeight;
}

function launchRocket() {
    rockets.push({
        x: w * (0.15 + Math.random() * 0.7),
        y: h,
        targetY: h * (0.2 + Math.random() * 0.35),
        vy: -(8 + Math.random() * 4),
    });
}

function burst(x, y) {
    const color = COLORS[Math.floor(Math.random() * COLORS.length)];
    const count = 45 + Math.floor(Math.random() * 30);
    for (let i = 0; i < count; i++) {
        const angle = (Math.PI * 2 * i) / count + Math.random() * 0.3;
        const speed = 2 + Math.random() * 4;
        particles.push({
            x, y,
            vx: Math.cos(angle) * speed,
            vy: Math.sin(angle) * speed,
            life: 1,
            decay: 0.008 + Math.random() * 0.012,
            size: 2 + Math.random() * 2,
            color,
        });
    }
}

function tick(now) {
    const elapsed = now - startTime;
    ctx.clearRect(0, 0, w, h);
    ctx.globalCompositeOperation = 'lighter';

    for (let i = rockets.length - 1; i >= 0; i--) {
        const r = rockets[i];
        r.y += r.vy;
        ctx.globalAlpha = 1;
        ctx.fillStyle = '#ffffff';
        ctx.beginPath();
        ctx.arc(r.x, r.y, 2.5, 0, Math.PI * 2);
        ctx.fill();
        if (r.y <= r.targetY) {
            burst(r.x, r.y);
            rockets.splice(i, 1);
        }
    }

    for (let i = particles.length - 1; i >= 0; i--) {
        const p = particles[i];
        p.x += p.vx;
        p.y += p.vy;
        p.vy += 0.05; // gravity
        p.vx *= 0.99;
        p.vy *= 0.99;
        p.life -= p.decay;
        if (p.life <= 0) {
            particles.splice(i, 1);
            continue;
        }
        ctx.globalAlpha = Math.max(0, p.life);
        ctx.fillStyle = p.color;
        ctx.beginPath();
        ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
        ctx.fill();
    }

    ctx.globalAlpha = 1;
    ctx.globalCompositeOperation = 'source-over';

    if (elapsed < LAUNCH_UNTIL && Math.random() < 0.09) launchRocket();

    if (elapsed < DURATION || particles.length || rockets.length) {
        raf = requestAnimationFrame(tick);
    } else {
        stop();
        emit('done');
    }
}

function start() {
    // Respect reduced-motion: skip the animation entirely
    if (window.matchMedia?.('(prefers-reduced-motion: reduce)').matches) {
        emit('done');
        return;
    }

    nextTick(() => {
        if (!canvas.value) return;
        resize();
        ctx = canvas.value.getContext('2d');
        particles = [];
        rockets = [];
        startTime = performance.now();
        for (let i = 0; i < 4; i++) setTimeout(launchRocket, i * 150);
        window.addEventListener('resize', resize);
        raf = requestAnimationFrame(tick);
    });
}

function stop() {
    cancelAnimationFrame(raf);
    window.removeEventListener('resize', resize);
}

watch(() => props.show, (v) => (v ? start() : stop()));
onUnmounted(stop);
</script>
