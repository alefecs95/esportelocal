import { defineStore } from 'pinia';
import temporadaService from '../services/temporadaService';

export const useTemporadaStore = defineStore('temporada', {
  state: () => ({
    temporadas: [],
    currentTemporada: null,
    loading: false,
    error: null
  }),

  actions: {
    async fetchTemporadas() {
      this.loading = true;
      this.error = null;
      try {
        const response = await temporadaService.getAll();
        this.temporadas = response.data;
      } catch (error) {
        this.error = 'Erro ao carregar temporadas.';
        console.error(error);
      } finally {
        this.loading = false;
      }
    },

    async fetchTemporada(id) {
      this.loading = true;
      this.error = null;
      try {
        const response = await temporadaService.getById(id);
        this.currentTemporada = response.data;
        return response.data;
      } catch (error) {
        this.error = 'Erro ao carregar temporada.';
        console.error(error);
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async createTemporada(data) {
      this.loading = true;
      this.error = null;
      try {
        await temporadaService.create(data);
        await this.fetchTemporadas();
      } catch (error) {
        this.error = error.response?.data?.message || 'Erro ao criar temporada.';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async ativarTemporada(id) {
      this.loading = true;
      this.error = null;
      try {
        await temporadaService.ativar(id);
        await this.fetchTemporadas();
      } catch (error) {
        this.error = error.response?.data?.message || 'Erro ao ativar temporada.';
        throw error;
      } finally {
        this.loading = false;
      }
    },
    
    async deleteTemporada(id) {
        this.loading = true;
        this.error = null;
        try {
            await temporadaService.delete(id);
            await this.fetchTemporadas();
        } catch (error) {
            this.error = error.response?.data?.message || 'Erro ao excluir temporada.';
            throw error;
        } finally {
            this.loading = false;
        }
    }
  },

  getters: {
    activeTemporada: (state) => state.temporadas.find(t => t.status === 'ativa'),
  }
});
