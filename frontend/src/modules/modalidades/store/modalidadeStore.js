import { defineStore } from 'pinia';
import modalidadeService from '../services/modalidadeService';

export const useModalidadeStore = defineStore('modalidade', {
  state: () => ({
    modalidades: [],
    currentModalidade: null,
    loading: false,
    error: null,
    pagination: {
      current_page: 1,
      last_page: 1,
      total: 0,
      per_page: 10,
    },
  }),

  actions: {
    async fetchModalidades(params = {}) {
      this.loading = true;
      this.error = null;
      try {
        const response = await modalidadeService.getAll(params);
        this.modalidades = response.data.data;
        this.pagination = {
            current_page: response.data.current_page,
            last_page: response.data.last_page,
            total: response.data.total,
            per_page: response.data.per_page,
        };
      } catch (error) {
        this.error = 'Erro ao carregar modalidades.';
        console.error(error);
      } finally {
        this.loading = false;
      }
    },

    async fetchModalidade(id) {
      this.loading = true;
      this.error = null;
      try {
        const response = await modalidadeService.get(id);
        this.currentModalidade = response.data;
      } catch (error) {
        this.error = 'Erro ao carregar modalidade.';
        console.error(error);
      } finally {
        this.loading = false;
      }
    },

    async createModalidade(data) {
      this.loading = true;
      this.error = null;
      try {
        await modalidadeService.create(data);
        await this.fetchModalidades();
      } catch (error) {
        this.error = 'Erro ao criar modalidade.';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async updateModalidade(id, data) {
      this.loading = true;
      this.error = null;
      try {
        await modalidadeService.update(id, data);
        await this.fetchModalidades();
      } catch (error) {
        this.error = 'Erro ao atualizar modalidade.';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async deleteModalidade(id) {
      this.loading = true;
      this.error = null;
      try {
        await modalidadeService.delete(id);
        await this.fetchModalidades();
      } catch (error) {
        this.error = 'Erro ao excluir modalidade.';
        throw error;
      } finally {
        this.loading = false;
      }
    },
  },
});
