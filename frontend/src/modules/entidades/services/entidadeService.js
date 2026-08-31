import api from '@/services/api';

export default {
  getAll(params = {}) {
    return api.get('/entidades', { params });
  },
  
  getById(id) {
    return api.get(`/entidades/${id}`);
  },
  
  create(data) {
    return api.post('/entidades', data);
  },
  
  update(id, data) {
    // Se for FormData, precisamos usar POST com _method=PUT para o Laravel processar arquivos
    if (data instanceof FormData) {
        data.append('_method', 'PUT');
        return api.post(`/entidades/${id}`, data);
    }
    return api.put(`/entidades/${id}`, data);
  },
  
  delete(id) {
    return api.delete(`/entidades/${id}`);
  }
};
