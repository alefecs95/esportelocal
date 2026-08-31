import api from '@/services/api';

export default {
    getPartida(id) {
        return api.get(`/partidas/${id}`);
    },

    getEventos(partidaId) {
        return api.get(`/partidas/${partidaId}/eventos`);
    },

    addEvento(dados) {
        return api.post('/eventos-partida', dados);
    },

    deleteEvento(id) {
        return api.delete(`/eventos-partida/${id}`);
    }
};
