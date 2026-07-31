<template>
    <div class="relative h-64 sm:h-80">
        <canvas ref="canvasEl" />
        <div
            v-if="loading"
            class="absolute inset-0 flex items-center justify-center bg-white/70 text-sm text-slate-400"
        >
            Завантаження...
        </div>
    </div>
</template>

<script setup>
import {
    CategoryScale,
    Chart,
    Filler,
    Legend,
    LinearScale,
    LineController,
    LineElement,
    PointElement,
    Tooltip,
} from 'chart.js';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

// Registered piecemeal rather than via `chart.js/auto`: the auto bundle pulls
// in every controller (bar, pie, radar, scatter…) and roughly doubles the size.
Chart.register(
    LineController, LineElement, PointElement,
    LinearScale, CategoryScale, Tooltip, Legend, Filler,
);

const props = defineProps({
    labels: { type: Array, default: () => [] },
    series: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
});

/** Series colour by key, so a line keeps its colour across metric switches. */
const COLORS = {
    users: '#0f172a',   // slate-900, matches the "Користувачів" card
    posts: '#2563eb',   // blue-600,  matches "Постів"
    catches: '#059669', // emerald-600, matches "Уловів"
};

const canvasEl = ref(null);
let chart = null;

function datasets() {
    return props.series.map(s => {
        const color = COLORS[s.key] ?? '#64748b';

        return {
            label: s.label,
            data: s.data,
            borderColor: color,
            backgroundColor: `${color}1a`, // same hue at 10% for the area fill
            pointBackgroundColor: color,
            pointRadius: props.labels.length > 40 ? 0 : 3,
            pointHoverRadius: 5,
            borderWidth: 2,
            // Monotone, not a plain tension: a smoothed spline overshoots
            // between two zeroes and dips the curve below the axis, and a
            // negative number of posts is not a thing.
            cubicInterpolationMode: 'monotone',
            fill: true,
        };
    });
}

function render() {
    if (!canvasEl.value) return;

    if (chart) {
        chart.data.labels = props.labels;
        chart.data.datasets = datasets();
        chart.update();

        return;
    }

    chart = new Chart(canvasEl.value, {
        type: 'line',
        data: { labels: props.labels, datasets: datasets() },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                // One series needs no key; two do
                legend: {
                    display: props.series.length > 1,
                    position: 'bottom',
                    labels: { usePointStyle: true, boxWidth: 8, padding: 16 },
                },
                tooltip: {
                    backgroundColor: '#0f172a',
                    padding: 10,
                    displayColors: true,
                    usePointStyle: true,
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: '#94a3b8', maxRotation: 0, autoSkipPadding: 16 },
                },
                y: {
                    beginAtZero: true,
                    grid: { color: '#f1f5f9' },
                    border: { display: false },
                    // Counts are whole records — a "2.5 posts" gridline is noise
                    ticks: { color: '#94a3b8', precision: 0 },
                },
            },
        },
    });
}

onMounted(render);
watch(() => [props.labels, props.series], render, { deep: true });

// Chart.js keeps listeners on the canvas and a global registry entry; without
// this the instance leaks every time the dashboard tab is left and re-entered.
onBeforeUnmount(() => {
    chart?.destroy();
    chart = null;
});
</script>
