import api from '@/services/api';

export default {
    getAvailableChampionships() {
        return api.get('/manager/campeonatos');
    },
    getMyTeams() {
        return api.get('/manager/equipes');
    },
    registerTeam(data) {
        return api.post('/equipes', data);
    },
    getTeamDetails(id) {
        return api.get(`/equipes/${id}`);
    },
    addAthlete(teamId, athleteId) {
        return api.post(`/equipes/${teamId}/adicionar-atleta`, { atleta_id: athleteId });
    },
    removeAthlete(teamId, athleteId) {
        return api.delete(`/equipes/${teamId}/remover-atleta/${athleteId}`);
    },
    searchAthleteByCpf(cpf) {
        return api.get(`/atletas/consulta-cpf/${cpf}`);
    },
    updateTeamStatus(teamId, status) {
        return api.put(`/equipes/${teamId}/status`, { status });
    }
};
