import api from '@/services/api';

export default {
  getAll(params = {}) {
    return api.get('/noticias', { params });
  },
  get(id) {
    return api.get(`/noticias/${id}`);
  },
  create(data) {
    return api.post('/noticias', data, {
      headers: {
        'Content-Type': undefined
      }
    });
  },
  update(id, data) {
    // Note: Laravel (or PHP in general) has issues with PUT/PATCH and multipart/form-data.
    // The standard workaround is to use POST with _method=PUT.
    if (data instanceof FormData) {
      data.append('_method', 'PUT');
    }
    return api.post(`/noticias/${id}`, data, {
      headers: {
        'Content-Type': undefined
      }
    });
  },
  delete(id) {
    return api.delete(`/noticias/${id}`);
  },
  toggleDestaque(id) {
    return api.patch(`/noticias/${id}/toggle-destaque`);
  },
  getComentariosAdmin(id) {
    return api.get(`/noticias/${id}/comentarios-admin`);
  },
  deleteComentario(id) {
    return api.delete(`/comentarios/${id}`);
  },
  postComentario(id, data) {
    return api.post(`/noticias/${id}/comentarios`, data);
  }
};
