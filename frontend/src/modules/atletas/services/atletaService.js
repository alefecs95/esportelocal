import api from '../../../services/api';

export default {
    async getAtletas(page = 1, search = '', filters = {}) {
        return api.get('/atletas', {
            params: {
                page,
                search: search || undefined,
                acesso_ativo: filters.acesso_ativo ?? undefined,
                pode_participar_competicoes: filters.pode_participar_competicoes ?? undefined,
                cidade: filters.cidade || undefined,
                local_votacao: filters.local_votacao || undefined,
                idade_min: filters.idade_min || undefined,
                idade_max: filters.idade_max || undefined
            }
        });
    },

    async getAtleta(id) {
        return api.get(`/atletas/${id}`);
    },

    async createAtleta(data) {
        return api.post('/atletas', data);
    },

    async updateAtleta(id, data) {
        if (data instanceof FormData) {
            return api.post(`/atletas/${id}`, data);
        }
        return api.put(`/atletas/${id}`, data);
    },

    async deleteAtleta(id) {
        return api.delete(`/atletas/${id}`);
    },

    async updateAtletaStatus(id, payload) {
        return api.put(`/atletas/${id}/status`, payload);
    },

    async downloadFichaAtleta(id) {
        return api.get(`/atletas/${id}/pdf-ficha`, {
            responseType: 'blob'
        });
    },

    async consultarCpf(cpf) {
        return api.get(`/atletas/consulta-cpf/${cpf}`);
    },

    async consultarTitulo(dados) {
        return api.post(`/atletas/consulta-titulo`, dados);
    },

    async revalidarTitulo(id) {
        return api.post(`/atletas/${id}/revalidar-titulo`);
    },

    async aprovarDadosEleitoraisManual(id) {
        return api.post(`/atletas/${id}/aprovar-dados-eleitorais-manual`);
    },

    async resetPassword(id) {
        return api.post(`/atletas/${id}/reset-password`);
    },

    async gerarUsuario(id) {
        return api.post(`/atletas/${id}/gerar-usuario`);
    }
};
