import api from '../../../services/api';

export default {
    createInscricao(data) {
        return api.post('/inscricoes', data);
    },
    getInscricoesPorCampeonato(campeonatoId, params = {}) {
        return api.get(`/campeonatos/${campeonatoId}/inscricoes`, { params });
    },
    updateInscricao(id, data) {
        return api.put(`/inscricoes/${id}`, data);
    },
    getCamposCadastro(campeonatoId) {
        return api.get(`/public/campeonatos/${campeonatoId}/campos-cadastro`);
    },
    criarInscricaoPublica(campeonatoId, data) {
        return api.post(`/public/campeonatos/${campeonatoId}/inscricoes`, data);
    },
    confirmarContribuicao(id, data) {
        return api.post(`/inscricoes/${id}/confirmar-contribuicao`, data);
    },
    retirarKit(id, data) {
        return api.post(`/inscricoes/${id}/retirar-kit`, data);
    },
    getPodio(campeonatoId) {
        return api.get(`/campeonatos/${campeonatoId}/premiacao`);
    },
    premiar(inscricaoId, data = {}) {
        return api.post(`/inscricoes/${inscricaoId}/premiar`, data);
    },
    marcarChegada(inscricaoId) {
        return api.post(`/inscricoes/${inscricaoId}/marcar-chegada`);
    },
    getMedalhasFinisher(campeonatoId) {
        return api.get(`/campeonatos/${campeonatoId}/medalhas-finisher`);
    },
    getResultadosPublicos(campeonatoId) {
        return api.get(`/public/campeonatos/${campeonatoId}/resultados-corrida`);
    },
    getPdfUrl(campeonatoId, tipo) {
        return api.get(`/campeonatos/${campeonatoId}/pdf-${tipo}`);
    },
};
