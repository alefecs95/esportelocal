import { defineStore } from 'pinia';
import inscricaoService from '../services/inscricaoService';

export const useInscricaoStore = defineStore('inscricao', {
    state: () => ({
        inscricoes: [],
        loading: false,
        error: null,
    }),
    actions: {
        async fetchInscricoes(campeonatoId) {
            this.loading = true;
            this.error = null;
            try {
                const response = await inscricaoService.getInscricoesPorCampeonato(campeonatoId);
                this.inscricoes = response.data.data;
            } catch (error) {
                this.error = error.response?.data?.message || 'Erro ao carregar inscrições';
            } finally {
                this.loading = false;
            }
        },
        async createInscricao(data) {
            this.loading = true;
            this.error = null;
            try {
                const response = await inscricaoService.createInscricao(data);
                return response.data;
            } catch (error) {
                this.error = error.response?.data?.message || 'Erro ao criar inscrição';
                throw error;
            } finally {
                this.loading = false;
            }
        },
        async updateInscricao(id, data) {
            this.loading = true;
            this.error = null;
            try {
                const response = await inscricaoService.updateInscricao(id, data);
                // Atualiza o estado local
                const index = this.inscricoes.findIndex(i => i.id === id);
                if (index !== -1) {
                    this.inscricoes[index] = response.data.data;
                }
                return response.data;
            } catch (error) {
                if (error.response?.data?.errors) {
                    const errorMessages = Object.values(error.response.data.errors).flat().join(' ');
                    this.error = errorMessages;
                } else {
                    this.error = error.response?.data?.message || 'Erro ao atualizar inscrição';
                }
                throw error;
            } finally {
                this.loading = false;
            }
        }
    }
});
