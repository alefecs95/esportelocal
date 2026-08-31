import api from '@/services/api';

const RESOURCE = '/campeonatos';

export default {
  getAll(params) {
    return api.get(RESOURCE, { params });
  },

  get(id) {
    return api.get(`${RESOURCE}/${id}`);
  },

  create(data) {
    if (data instanceof FormData) {
      return api.post(RESOURCE, data);
    }
    return api.post(RESOURCE, data);
  },

  update(id, data) {
    if (data instanceof FormData) {
      data.append('_method', 'PUT');
      return api.post(`${RESOURCE}/${id}`, data);
    }
    return api.put(`${RESOURCE}/${id}`, data);
  },

  delete(id) {
    return api.delete(`${RESOURCE}/${id}`);
  },

  getCidadesEleitorais() {
    return api.get('/filtros/cidades-eleitorais');
  },

  getBairrosEleitorais(cidade) {
    return api.get('/filtros/bairros-eleitorais', { params: { cidade } });
  },

  simularElegiveis(data) {
    return api.post('/competicoes/simular-elegibilidade', data);
  },

  getFormatos() {
    return api.get('/formatos-campeonato');
  },

  getEstrutura(id) {
    return api.get(`/competicoes/${id}/estrutura`);
  },

  getArbitros() {
    return api.get('/users/arbitros');
  },

  saveEstrutura(id, data) {
    return api.put(`/competicoes/${id}/estrutura`, data);
  },

  sugerirConfrontos(id, data = {}) {
    return api.post(`/competicoes/${id}/confrontos/sugerir`, data);
  },

  resolverClassificados(id) {
    return api.post(`/competicoes/${id}/resolver-classificados`);
  },

  getCriteriosClassificacao() {
    return api.get('/fases/regras-classificacao/criterios');
  },

  getRegrasClassificacao(faseId) {
    return api.get(`/fases/${faseId}/regras-classificacao`);
  },

  saveRegrasClassificacao(faseId, data) {
    return api.put(`/fases/${faseId}/regras-classificacao`, data);
  },

  getClassificacaoFase(faseId) {
    return api.get(`/fases/${faseId}/classificacao`);
  },
};
