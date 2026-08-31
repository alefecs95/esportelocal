import api from '@/services/api';

const RESOURCE = '/modalidades';

export default {
  getAll(params) {
    return api.get(RESOURCE, { params });
  },

  get(id) {
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
};
