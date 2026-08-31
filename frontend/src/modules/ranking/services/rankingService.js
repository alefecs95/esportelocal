import api from '@/services/api';

export const getRankingGeral = (temporadaId, params = {}) => {
    return api.get(`/ranking/${temporadaId}`, { params });
};

export const getRankingPorModalidade = (temporadaId, modalidadeId, params = {}) => {
    return api.get(`/ranking/${temporadaId}/modalidade/${modalidadeId}`, { params });
};

export const getTop10 = (temporadaId, params = {}) => {
    return api.get(`/ranking/${temporadaId}/top10`, { params });
};

export const recalcularRanking = () => {
    return api.post('/ranking/recalcular');
};

export const getTemporadaAtiva = async () => {
    try {
        const { data } = await api.get('/temporadas');
        const temporadas = Array.isArray(data) ? data : [];
        const ativa = temporadas.find((temporada) => String(temporada?.status || '').toLowerCase() === 'ativa');

        if (ativa) {
            return { data: ativa };
        }
    } catch (error) {
    }

    return api.get('/ranking/temporada-ativa');
};
