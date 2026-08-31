import api from '../../../services/api';

export default {
    getDashboard() {
        return api.get('/athlete/dashboard');
    },
    getProfile() {
        return api.get('/athlete/profile');
    },
    updateProfile(data) {
        return api.put('/athlete/profile', data);
    },
    uploadPhoto(formData) {
        return api.post('/athlete/photo', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
    },
    uploadDocument(formData) {
        return api.post('/athlete/documents', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
    },
    deleteDocument(id) {
        return api.delete(`/athlete/documents/${id}`);
    },
    responderConvite(id, payload) {
        return api.post(`/athlete/convites/${id}/responder`, payload);
    }
};
