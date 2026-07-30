import api from './client';

export async function fetchFisher(id) {
    const { data } = await api.get(`/fishers/${id}`);
    return data;
}

export async function fetchFisherPosts(id, params = {}) {
    const { data } = await api.get(`/fishers/${id}/posts`, { params });
    return data;
}

export async function searchUsers(params = {}) {
    const { data } = await api.get('/users/search', { params });
    return data;
}

export async function followFisher(id) {
    const { data } = await api.post(`/fishers/${id}/follow`);
    return data;
}

export async function unfollowFisher(id) {
    const { data } = await api.delete(`/fishers/${id}/follow`);
    return data;
}
