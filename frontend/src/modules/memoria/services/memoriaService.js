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
};
