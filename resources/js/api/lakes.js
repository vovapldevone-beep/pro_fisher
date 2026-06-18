import api from './client';

export async function fetchLakes() {
    const { data } = await api.get('/lakes');
    return data.data;
}

export async function fetchLake(slug) {
    const { data } = await api.get(`/lakes/${slug}`);
    return data.data;
}
