import api from './client';

export async function fetchHomeStats() {
    const { data } = await api.get('/home/stats');
    return data;
}

export async function fetchPopularLakes() {
    const { data } = await api.get('/home/popular-lakes');
    return data.data;
}

export async function fetchRecentCatches() {
    const { data } = await api.get('/home/recent-catches');
    return data.data;
}
