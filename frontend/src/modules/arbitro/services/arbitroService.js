import api from '@/services/api';

export default {
  getMinhasPartidas() {
    return api.get('/arbitro/minhas-partidas');
  },

  atualizarPlacar(partidaId, placarMandante, placarVisitante, finalizar = false) {
    return api.put(`/arbitro/partidas/${partidaId}/placar`, {
      placar_mandante: placarMandante,
      placar_visitante: placarVisitante,
      finalizar
    });
  },

  getEscalacao(partidaId) {
    return api.get(`/arbitro/partidas/${partidaId}/escalacao`);
  },

  salvarEscalacao(partidaId, payload) {
    return api.put(`/arbitro/partidas/${partidaId}/escalacao`, payload);
  }
};
