<template>
    <div ref="mapContainer" class="h-full w-full"></div>
</template>

<script setup>
import { onMounted, onUnmounted, ref, watch } from 'vue';
import L from 'leaflet';

const props = defineProps({
    lakes: {
        type: Array,
        default: () => [],
    },
    highlightedSlug: {
        type: String,
        default: null,
    },
});

const emit = defineEmits(['lake-selected', 'bounds-changed', 'map-clicked']);

const mapContainer = ref(null);
let map = null;
let markersLayer = null;
const markersBySlug = {};
let boundsTimer = null;

const defaultIcon = L.icon({
    iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
    iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
    shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41],
});

function initMap() {
    map = L.map(mapContainer.value, {
        center: [52.0, 19.0],
        zoom: 6,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 18,
    }).addTo(map);

    markersLayer = L.layerGroup().addTo(map);
    updateMarkers();

    map.on('moveend zoomend', () => {
        clearTimeout(boundsTimer);
        boundsTimer = setTimeout(emitBounds, 500);
    });

    map.on('click', () => emit('map-clicked'));

    emitBounds();
}

function emitBounds() {
    if (!map) return;
    const b = map.getBounds();
    emit('bounds-changed', {
        lat_min: b.getSouth(),
        lat_max: b.getNorth(),
        lng_min: b.getWest(),
        lng_max: b.getEast(),
    });
}

function updateMarkers() {
    if (!markersLayer) return;

    markersLayer.clearLayers();
    Object.keys(markersBySlug).forEach((key) => delete markersBySlug[key]);

    props.lakes.forEach((lake) => {
        const marker = L.marker([lake.latitude, lake.longitude], { icon: defaultIcon });
        marker.bindPopup(`<strong>${lake.name}</strong>`);
        marker.on('click', () => emit('lake-selected', lake));
        markersLayer.addLayer(marker);
        markersBySlug[lake.slug] = marker;
    });
}

function flyToLake(lake) {
    if (!map || !lake) return;
    map.flyTo([lake.latitude, lake.longitude], 10, { duration: 0.8 });
}

defineExpose({ flyToLake });

watch(() => props.lakes, updateMarkers, { deep: true });

watch(() => props.highlightedSlug, (slug) => {
    if (slug && markersBySlug[slug]) {
        markersBySlug[slug].openPopup();
    }
});

onMounted(() => {
    initMap();
});

onUnmounted(() => {
    if (map) {
        map.remove();
        map = null;
    }
});
</script>
