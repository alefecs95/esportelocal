import api from '@/services/api';

export default {
    getEquipes(campeonatoId) {
        return api.get(`/campeonatos/${campeonatoId}/equipes`);
    },
    getEquipe(id) {
        return api.get(`/equipes/${id}`);
    },
    createEquipe(equipe) {
        return api.post('/equipes', equipe);
    },
    updateEquipe(id, equipe) {
        return api.put(`/equipes/${id}`, equipe);
    },
    updateStatus(id, status) {
        return api.put(`/equipes/${id}/status`, { status });
    },
    deleteEquipe(id) {
        return api.delete(`/equipes/${id}`);
    },
    addAtleta(equipeId, atletaId, inscricaoDireta = false) {
        return api.post(`/equipes/${equipeId}/adicionar-atleta`, {
            atleta_id: atletaId,
            inscricao_direta: inscricaoDireta,
        });
    },
    updateAtletaStatus(equipeId, atletaId, status) {
        return api.put(`/equipes/${equipeId}/atletas/${atletaId}/status`, { status });
    },
    updateAtletasStatusLote(equipeId, atletaIds, status) {
        return api.put(`/equipes/${equipeId}/atletas/status-lote`, {
            atleta_ids: atletaIds,
            status
        });
    },
    removeAtleta(equipeId, atletaId) {
        return api.delete(`/equipes/${equipeId}/remover-atleta/${atletaId}`);
    }
};
