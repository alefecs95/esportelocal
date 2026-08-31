import api from '../../../services/api';

export default {
    createInscricao(data) {
        return api.post('/inscricoes', data);
    },
    getInscricoesPorCampeonato(campeonatoId) {
        return api.get(`/campeonatos/${campeonatoId}/inscricoes`);
    },
    updateInscricao(id, data) {
        return api.put(`/inscricoes/${id}`, data);
    }
};
