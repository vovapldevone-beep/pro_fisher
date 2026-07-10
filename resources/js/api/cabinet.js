import api from './client';

export async function fetchCabinet() {
    const { data } = await api.get('/cabinet');
    return data;
}

export async function fetchAchievements() {
    const { data } = await api.get('/cabinet/achievements');
    return data;
}

export async function fetchFriends() {
    const { data } = await api.get('/cabinet/friends');
    return data;
}

export async function updateProfile(formData) {
    const { data } = await api.post('/user/profile', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
    });
    return data;
}
