import api from '@/services/api';

const asForm = (data) => {
  if (data instanceof FormData) {
    return {
      data,
      headers: { 'Content-Type': undefined },
    };
  }
  return { data };
};

export default {
  dashboard() {
    return api.get('/memoria/dashboard');
  },
  opcoes() {
    return api.get('/memoria/opcoes');
  },
  createOpcao(payload) {
    return api.post('/memoria/opcoes', payload);
  },
  locais() {
    return api.get('/memoria/locais');
  },
  createLocal(data) {
    const cfg = asForm(data);
    return api.post('/memoria/locais', cfg.data, { headers: cfg.headers });
  },
  updateLocal(id, data) {
    if (data instanceof FormData) data.append('_method', 'POST');
    const cfg = asForm(data);
    return api.post(`/memoria/locais/${id}`, cfg.data, { headers: cfg.headers });
  },
  deleteLocal(id) {
    return api.delete(`/memoria/locais/${id}`);
  },
  createMomento(localId, data) {
    const cfg = asForm(data);
    return api.post(`/memoria/locais/${localId}/momentos`, cfg.data, { headers: cfg.headers });
  },
  deleteMomento(localId, id) {
    return api.delete(`/memoria/locais/${localId}/momentos/${id}`);
  },
  equipes() {
    return api.get('/memoria/equipes');
  },
  createEquipe(data) {
    const cfg = asForm(data);
    return api.post('/memoria/equipes', cfg.data, { headers: cfg.headers });
  },
  updateEquipe(id, data) {
    const cfg = asForm(data);
    return api.post(`/memoria/equipes/${id}`, cfg.data, { headers: cfg.headers });
  },
  deleteEquipe(id) {
    return api.delete(`/memoria/equipes/${id}`);
  },
  correcoes(params = {}) {
    return api.get('/memoria/correcoes', { params });
  },
  aprovarCorrecao(id) {
    return api.post(`/memoria/correcoes/${id}/aprovar`);
  },
  rejeitarCorrecao(id, payload = {}) {
    return api.post(`/memoria/correcoes/${id}/rejeitar`, payload);
  },
  personalidades(params = {}) {
    return api.get('/memoria/personalidades', { params });
  },
  getPersonalidade(id) {
    return api.get(`/memoria/personalidades/${id}`);
  },
  createPersonalidade(data) {
    const cfg = asForm(data);
    return api.post('/memoria/personalidades', cfg.data, { headers: cfg.headers });
  },
  updatePersonalidade(id, data) {
    const cfg = asForm(data);
    return api.post(`/memoria/personalidades/${id}`, cfg.data, { headers: cfg.headers });
  },
  deletePersonalidade(id) {
    return api.delete(`/memoria/personalidades/${id}`);
  },
  marcos(params = {}) {
    return api.get('/memoria/marcos', { params });
  },
  getMarco(id) {
    return api.get(`/memoria/marcos/${id}`);
  },
  createMarco(data) {
    const cfg = asForm(data);
    return api.post('/memoria/marcos', cfg.data, { headers: cfg.headers });
  },
  updateMarco(id, data) {
    const cfg = asForm(data);
    return api.post(`/memoria/marcos/${id}`, cfg.data, { headers: cfg.headers });
  },
  deleteMarco(id) {
    return api.delete(`/memoria/marcos/${id}`);
  },
  acervo(params = {}) {
    return api.get('/memoria/acervo', { params });
  },
  getAcervo(id) {
    return api.get(`/memoria/acervo/${id}`);
  },
  createAcervo(data) {
    const cfg = asForm(data);
    return api.post('/memoria/acervo', cfg.data, { headers: cfg.headers });
  },
  updateAcervo(id, data) {
    const cfg = asForm(data);
    return api.post(`/memoria/acervo/${id}`, cfg.data, { headers: cfg.headers });
  },
  deleteAcervo(id) {
    return api.delete(`/memoria/acervo/${id}`);
  },
  aprovar(id, payload = {}) {
    return api.post(`/memoria/contribuicoes/${id}/aprovar`, payload);
  },
  rejeitar(id, payload = {}) {
    return api.post(`/memoria/contribuicoes/${id}/rejeitar`, payload);
  },
  pedirComplemento(id, payload = {}) {
    return api.post(`/memoria/contribuicoes/${id}/complemento`, payload);
  },
  lote(payload) {
    return api.post('/memoria/contribuicoes/lote', payload);
  },
  duplicarMarco(id) {
    return api.post(`/memoria/marcos/${id}/duplicar`);
  },
  duplicarPersonalidade(id) {
    return api.post(`/memoria/personalidades/${id}/duplicar`);
  },
  auditoria() {
    return api.get('/memoria/auditoria');
  },
  notificacoes() {
    return api.get('/memoria/notificacoes');
  },
  lerNotificacoes() {
    return api.post('/memoria/notificacoes/ler');
  },
  identificacoes(params = {}) {
    return api.get('/memoria/identificacoes', { params });
  },
  aprovarIdentificacao(id, payload = {}) {
    return api.post(`/memoria/identificacoes/${id}/aprovar`, payload);
  },
  rejeitarIdentificacao(id, payload = {}) {
    return api.post(`/memoria/identificacoes/${id}/rejeitar`, payload);
  },
  recordes() {
    return api.get('/memoria/recordes');
  },
  createRecorde(payload) {
    return api.post('/memoria/recordes', payload);
  },
  updateRecorde(id, payload) {
    return api.post(`/memoria/recordes/${id}`, payload);
  },
  deleteRecorde(id) {
    return api.delete(`/memoria/recordes/${id}`);
  },
};
