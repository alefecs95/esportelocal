import { defineStore } from 'pinia';
import equipeService from '../services/equipeService';

export const useEquipeStore = defineStore('equipe', {
    state: () => ({
        equipes: [],
        currentEquipe: null,
        loading: false,
        error: null
    }),
    actions: {
        async fetchEquipes(campeonatoId) {
            this.loading = true;
            this.equipes = []; // Limpar lista anterior para evitar "ghosting" visual
            try {
                const response = await equipeService.getEquipes(campeonatoId);
                this.equipes = response.data;
            } catch (error) {
                this.error = error.message;
            } finally {
                this.loading = false;
            }
        },
        async fetchEquipe(id) {
            this.loading = true;
            try {
                const response = await equipeService.getEquipe(id);
                this.currentEquipe = response.data;
            } catch (error) {
                this.error = error.message;
            } finally {
                this.loading = false;
            }
        },
        async createEquipe(equipe) {
            this.loading = true;
            try {
                const response = await equipeService.createEquipe(equipe);
                this.equipes.push(response.data);
                return response.data;
            } catch (error) {
                this.error = error.message;
                throw error;
            } finally {
                this.loading = false;
            }
        },
        async updateEquipe(id, equipe) {
            this.loading = true;
            try {
                const response = await equipeService.updateEquipe(id, equipe);
                const index = this.equipes.findIndex(e => e.id === id);
                if (index !== -1) {
                    this.equipes[index] = response.data;
                }
                return response.data;
            } catch (error) {
                this.error = error.message;
                throw error;
            } finally {
                this.loading = false;
            }
        },
        async updateStatus(id, status) {
            this.loading = true;
            try {
                const response = await equipeService.updateStatus(id, status);
                const index = this.equipes.findIndex(e => e.id === id);
                if (index !== -1) {
                    this.equipes[index] = response.data;
                }
                return response.data;
            } catch (error) {
                this.error = error.message;
                throw error;
            } finally {
                this.loading = false;
            }
        },
        async deleteEquipe(id) {
            this.loading = true;
            try {
                await equipeService.deleteEquipe(id);
                this.equipes = this.equipes.filter(e => e.id !== id);
            } catch (error) {
                this.error = error.message;
                throw error;
            } finally {
                this.loading = false;
            }
        },
        async addAtleta(equipeId, atletaId, inscricaoDireta = false) {
            try {
                const response = await equipeService.addAtleta(equipeId, atletaId, inscricaoDireta);
                await this.fetchEquipe(equipeId); // Refresh team data
                return response?.data;
            } catch (error) {
                throw error;
            }
        },
        async removeAtleta(equipeId, atletaId) {
            try {
                await equipeService.removeAtleta(equipeId, atletaId);
                await this.fetchEquipe(equipeId); // Refresh team data
            } catch (error) {
                throw error;
            }
        },
        async updateAtletaStatus(equipeId, atletaId, status) {
            try {
                await equipeService.updateAtletaStatus(equipeId, atletaId, status);
                await this.fetchEquipe(equipeId);
            } catch (error) {
                throw error;
            }
        },
        async updateAtletasStatusLote(equipeId, atletaIds, status) {
            try {
                await equipeService.updateAtletasStatusLote(equipeId, atletaIds, status);
                await this.fetchEquipe(equipeId);
            } catch (error) {
                throw error;
            }
        }
    }
});
