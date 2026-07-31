<template>
    <div>
        <div ref="mapEl" class="h-80 w-full overflow-hidden rounded-xl sm:h-[28rem]" />

        <p class="mt-3 text-xs text-slate-500">
            Яскравість — кількість уловів на озері.
            Точками позначені всі озера, зокрема ті, де уловів ще немає.
        </p>
    </div>
</template>

<script setup>
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
    // [{ id, name, slug, lat, lng, catches }]
    points: { type: Array, default: () => [] },
    maxCatches: { type: Number, default: 0 },
});

const mapEl = ref(null);
let map = null;
let heat = null;
let markers = null;

/**
 * Leaflet.heat is from 2015: it patches the global `L` instead of importing
 * Leaflet, so it has to be pulled in dynamically *after* the global is set —
 * a static import would be hoisted above the assignment and blow up.
 */
async function loadHeatPlugin() {
    if (L.heatLayer) return;

    window.L = L;
    await import('leaflet.heat');
}

function draw() {
    if (!map) return;

    heat?.remove();
    markers?.remove();

    if (!props.points.length) return;

    // Fit before the layers go on: leaflet.heat sizes and positions its canvas
    // when it is added, and a view change afterwards leaves the painted area
    // offset from the map with a visible seam.
    map.invalidateSize();
    map.fitBounds(L.latLngBounds(props.points.map(p => [p.lat, p.lng])), {
        padding: [30, 30],
        maxZoom: 10,
        animate: false,
    });

    // Only lakes with catches feed the heat: giving the empty ones a floor
    // weight would paint activity onto water nobody has fished.
    const hot = props.points
        .filter(p => p.catches > 0)
        .map(p => [p.lat, p.lng, p.catches]);

    if (hot.length) {
        heat = L.heatLayer(hot, {
            radius: 28,
            blur: 22,
            minOpacity: 0.4,
            // Normalised against the busiest lake, so the scale stays readable
            // whether the top lake has 3 catches or 3000.
            max: Math.max(props.maxCatches, 1),
            // Spelled out because the default only starts leaving blue at 0.4:
            // while the busiest lake has a handful of catches every point sits
            // below that and the whole map comes out uniformly cold.
            gradient: {
                0.1: '#3b82f6', // blue-500
                0.4: '#22c55e', // green-500
                0.7: '#f59e0b', // amber-500
                1.0: '#ef4444', // red-500
            },
        }).addTo(map);
    }

    // The heat layer alone cannot be pointed at or identified — these give
    // every lake a hit target and a name on hover.
    markers = L.layerGroup(
        props.points.map(p =>
            L.circleMarker([p.lat, p.lng], {
                radius: 4,
                weight: 1,
                color: p.catches > 0 ? '#0f766e' : '#94a3b8',
                fillColor: p.catches > 0 ? '#14b8a6' : '#cbd5e1',
                fillOpacity: 0.9,
            }).bindTooltip(
                `${p.name} — уловів: ${p.catches}`,
                { direction: 'top' }
            )
        )
    ).addTo(map);
}

onMounted(async () => {
    await loadHeatPlugin();

    if (!mapEl.value) return; // unmounted while the plugin was loading

    map = L.map(mapEl.value, { center: [52.0, 19.0], zoom: 6, scrollWheelZoom: false });
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap',
    }).addTo(map);

    draw();
});

watch(() => props.points, draw, { deep: true });

onBeforeUnmount(() => {
    map?.remove();
    map = heat = markers = null;
});
</script>
