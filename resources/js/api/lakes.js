import api from './client';

export async function fetchLakes(bounds = null) {
    const params = bounds
        ? { lat_min: bounds.lat_min, lat_max: bounds.lat_max, lng_min: bounds.lng_min, lng_max: bounds.lng_max }
        : {};
    const { data } = await api.get('/lakes', { params });
    return data.data;
}

export async function fetchLake(slug) {
    const { data } = await api.get(`/lakes/${slug}`);
    return data.data;
}
