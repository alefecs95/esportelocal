import api from '@/services/api';

export default {
    login(credentials) {
        return api.post('/login', credentials);
    },
    register(userData) {
        return api.post('/register', userData);
    },
    registerAtleta(atletaData) {
        return api.post('/atletas', atletaData);
    },
    consultarCpf(cpf) {
        return api.get(`/atletas/consulta-cpf/${cpf}`);
    },
    consultarTitulo(payload) {
        return api.post('/atletas/consulta-titulo', payload);
    },
    logout() {
        return api.post('/logout');
    },
    me() {
        return api.get('/me');
    },
    updatePassword(data) {
        return api.post('/update-password', data);
    },
    forgotPasswordWhatsApp(data) {
        return api.post('/password/forgot-whatsapp', data);
    },
    resetPasswordByToken(data) {
        return api.post('/password/reset-by-token', data);
    }
};
