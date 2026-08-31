import api from '@/services/api';

const RESOURCE = '/temporadas';

export default {
  getAll() {
    return api.get(RESOURCE);
  },

  getById(id) {
    return api.get(`${RESOURCE}/${id}`);
  },

  create(data) {
    return api.post(RESOURCE, data);
  },

  update(id, data) {
    return api.put(`${RESOURCE}/${id}`, data);
  },

  delete(id) {
    return api.delete(`${RESOURCE}/${id}`);
  },

  ativar(id) {
    return api.put(`${RESOURCE}/${id}/ativar`);
  }
};
