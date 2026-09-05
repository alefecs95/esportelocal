import { defineStore } from 'pinia';
import temporadaService from '../services/temporadaService';

export const useTemporadaStore = defineStore('temporada', {
  state: () => ({
    temporadas: [],
    currentTemporada: null,
    loading: false,
    error: null,
    pagination: {
      current_page: 1,
      last_page: 1,
      total: 0,
    },
  }),

  actions: {
    async fetchTemporadas() {
      this.loading = true;
      this.error = null;
      try {
        const response = await temporadaService.getAll({ per_page: 100 });
        const payload = response.data;
        this.temporadas = Array.isArray(payload) ? payload : (payload?.data || []);
        this.pagination = {
          current_page: payload?.current_page || 1,
          last_page: payload?.last_page || 1,
          total: payload?.total || this.temporadas.length,
        };
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
