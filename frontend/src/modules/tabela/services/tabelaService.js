import api from '@/services/api';

export default {
  gerarTabela(campeonatoId, formato) {
    return api.post(`/campeonatos/${campeonatoId}/gerar-tabela`, { formato });
  },

  getTabela(campeonatoId) {
    return api.get(`/campeonatos/${campeonatoId}/tabela`);
  },

  getEquipes(campeonatoId) {
    return api.get(`/campeonatos/${campeonatoId}/equipes`);
  },

  atualizarPlacar(partidaId, placarMandante, placarVisitante) {
    return api.put(`/partidas/${partidaId}/placar`, {
      placar_mandante: placarMandante,
      placar_visitante: placarVisitante
    });
  },

  salvarResultadosCorrida(campeonatoId, resultados) {
    return api.post(`/campeonatos/${campeonatoId}/resultados-corrida`, { resultados });
  }
};
