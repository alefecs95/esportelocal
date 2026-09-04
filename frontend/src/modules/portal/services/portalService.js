import api from '@/services/api';

export default {
  getHomeData() {
    return api.get('/public/home-data');
  },

  getNoticias(params = {}) {
    return api.get('/public/noticias', { params });
  },

  getNoticia(slug) {
    return api.get(`/public/noticias/${slug}`);
  },

  getComentarios(id) {
    return api.get(`/public/noticias/${id}/comentarios`);
  },

  postComentario(id, data) {
    // Uses authenticated route for posting if logged in, but we can try public endpoint if created
    // Currently backend requires auth for posting in some routes, let's check.
    // We added Route::post('noticias/{id}/comentarios') in auth group.
    return api.post(`/noticias/${id}/comentarios`, data);
  },

  getCampeonatos(params = {}) {
    return api.get('/public/campeonatos', { params });
  },

  getNoticias(params = {}) {
    return api.get('/public/noticias', { params });
  },

  getTabela(campeonatoId) {
    return api.get(`/public/campeonatos/${campeonatoId}/tabela`);
  },

  getArtilharia(campeonatoId) {
    return api.get(`/public/campeonatos/${campeonatoId}/artilharia`);
  },

  getCartoes(campeonatoId) {
    return api.get(`/public/campeonatos/${campeonatoId}/cartoes`);
  },

  getEquipes(params = {}) {
    return api.get('/public/equipes', { params });
  },

  getEquipe(id) {
    return api.get(`/public/equipes/${id}`);
  },

  getAtletas(params = {}) {
    return api.get('/public/atletas', { params });
  },

  getAtleta(id) {
    return api.get(`/public/atletas/${id}`);
  },

  getPartida(partidaId) {
    return api.get(`/public/partidas/${partidaId}`);
  },

  getPartidas(params = {}) {
    return api.get('/public/partidas', { params });
  },

  getMemoria() {
    return api.get('/public/memoria');
  },

  getMemoriaTimeline(params = {}) {
    return api.get('/public/memoria/timeline', { params });
  },

  getMemoriaMarco(slug) {
    return api.get(`/public/memoria/marcos/${slug}`);
  },

  getMemoriaGaleria(params = {}) {
    return api.get('/public/memoria/galeria', { params });
  },

  getMemoriaPersonalidades(params = {}) {
    return api.get('/public/memoria/personalidades', { params });
  },

  getMemoriaPersonalidade(slug) {
    return api.get(`/public/memoria/personalidades/${slug}`);
  },

  getMemoriaLocais() {
    return api.get('/public/memoria/locais');
  },

  enviarMemoriaContribuicao(data) {
    return api.post('/public/memoria/contribuicoes', data, {
      headers: { 'Content-Type': undefined },
    });
  }
};
