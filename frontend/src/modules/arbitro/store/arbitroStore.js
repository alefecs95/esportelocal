import { defineStore } from 'pinia';
import arbitroService from '../services/arbitroService';

export const useArbitroStore = defineStore('arbitro', {
  state: () => ({
    partidas: [],
    loading: false,
    error: null,
  }),

  actions: {
    async fetchMinhasPartidas() {
      this.loading = true;
      this.error = null;
      try {
        const response = await arbitroService.getMinhasPartidas();
        this.partidas = response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Erro ao carregar partidas.';
      } finally {
        this.loading = false;
      }
    },

    async atualizarPlacar(partidaId, placarMandante, placarVisitante, finalizar = false) {
      try {
        await arbitroService.atualizarPlacar(partidaId, placarMandante, placarVisitante, finalizar);
        // Atualizar lista
        await this.fetchMinhasPartidas();
      } catch (error) {
        throw error;
      }
    }
  },
});
