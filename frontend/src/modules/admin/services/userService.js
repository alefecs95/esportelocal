import api from '@/services/api';

export default {
    searchResponsavel(query) {
        return api.get('/users/responsaveis', { params: { q: query } });
    }
};
