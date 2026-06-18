import api from './client';

export async function fetchCatches() {
    const { data } = await api.get('/catches');
    return data.data;
}

export async function createCatch(formData) {
    const { data } = await api.post('/catches', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
    });
    return data.data;
}

export async function updateCatch(id, formData) {
    formData.append('_method', 'PUT');
    const { data } = await api.post(`/catches/${id}`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
    });
    return data.data;
}

export async function deleteCatch(id) {
    await api.delete(`/catches/${id}`);
}

export async function toggleLike(id) {
    const { data } = await api.post(`/catches/${id}/like`);
    return data;
}
