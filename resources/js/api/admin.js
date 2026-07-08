import api from './client';

export const fetchAdminStats = () => api.get('/admin/stats').then(r => r.data);

export const fetchAdminUsers = (page = 1) => api.get('/admin/users', { params: { page } }).then(r => r.data);
export const blockUser = (id) => api.post(`/admin/users/${id}/block`).then(r => r.data);
export const unblockUser = (id) => api.post(`/admin/users/${id}/unblock`).then(r => r.data);

export const fetchAdminCatches = (page = 1) => api.get('/admin/catches', { params: { page } }).then(r => r.data);
export const adminDeleteCatch = (id) => api.delete(`/admin/catches/${id}`).then(r => r.data);

export const fetchAdminLakes = (page = 1) => api.get('/admin/lakes', { params: { page } }).then(r => r.data);
export const fetchAdminLake = (id) => api.get(`/admin/lakes/${id}`).then(r => r.data);
export const createLake = (formData) => api.post('/admin/lakes', formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
}).then(r => r.data);
// POST (not PUT) — PHP does not parse multipart bodies on PUT requests
export const updateLake = (id, formData) => api.post(`/admin/lakes/${id}`, formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
}).then(r => r.data);
export const deleteLake = (id) => api.delete(`/admin/lakes/${id}`).then(r => r.data);
