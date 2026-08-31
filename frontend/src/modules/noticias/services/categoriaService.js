import api from '@/services/api';

export default {
  getAll() {
    return api.get('/categorias');
  },
  create(data) {
    return api.post('/categorias', data);
  }
};
