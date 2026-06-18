import api from './client';

export async function fetchComments(catchId) {
    const { data } = await api.get(`/catches/${catchId}/comments`);
    return data;
}

export async function postComment(catchId, body) {
    const { data } = await api.post(`/catches/${catchId}/comments`, { body });
    return data;
}
