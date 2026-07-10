import { ref } from 'vue';

const PERMISSION_ERRORS = {
    1: 'Доступ до геолокації відхилено. Дозвольте його в налаштуваннях браузера.',
    2: 'Не вдалося визначити місцезнаходження.',
    3: 'Час очікування вичерпано. Спробуйте ще раз.',
};

/**
 * Device GPS + OpenStreetMap Nominatim reverse geocoding.
 *
 * Browsers expose `navigator.geolocation` only in a secure context — HTTPS,
 * or localhost. Over plain HTTP it silently never resolves, so we check upfront.
 */
export function useGeolocation() {
    const locating = ref(false);
    const error = ref('');

    function locate() {
        error.value = '';

        if (!navigator.geolocation) {
            error.value = 'Геолокація не підтримується цим браузером.';
            return Promise.reject(new Error('unsupported'));
        }
        if (!window.isSecureContext) {
            error.value = 'Геолокація доступна лише через HTTPS.';
            return Promise.reject(new Error('insecure'));
        }

        locating.value = true;

        return new Promise((resolve, reject) => {
            navigator.geolocation.getCurrentPosition(
                ({ coords }) => {
                    locating.value = false;
                    resolve({
                        latitude: parseFloat(coords.latitude.toFixed(7)),
                        longitude: parseFloat(coords.longitude.toFixed(7)),
                    });
                },
                (err) => {
                    locating.value = false;
                    error.value = PERMISSION_ERRORS[err.code] || 'Помилка визначення місцезнаходження.';
                    reject(err);
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 },
            );
        });
    }

    // Best-effort: on failure the caller still has the coordinates
    async function reverseGeocode(lat, lng) {
        try {
            const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`;
            const res = await fetch(url, { headers: { 'Accept-Language': 'uk,pl,en' } });
            if (!res.ok) return null;
            const data = await res.json();
            return data.display_name || null;
        } catch {
            return null;
        }
    }

    return { locating, error, locate, reverseGeocode };
}
