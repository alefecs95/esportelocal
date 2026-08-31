import { defineStore } from 'pinia';
import campeonatoService from '../services/campeonatoService';

export const useCampeonatoStore = defineStore('campeonato', {
  state: () => ({
    campeonatos: [],
    currentCampeonato: null,
    loading: false,
    error: null,
    pagination: {
      current_page: 1,
      last_page: 1,
      total: 0,
      per_page: 10,
    },
    cidadesEleitorais: [],
    bairrosEleitorais: [],
    elegibilidadeCount: null,
  }),

  actions: {
    async fetchCidadesEleitorais() {
      try {
        const response = await campeonatoService.getCidadesEleitorais();
        this.cidadesEleitorais = response.data;
      } catch (error) {
        console.error('Erro ao buscar cidades eleitorais:', error);
      }
    },

    async fetchBairrosEleitorais(cidade) {
      try {
        const response = await campeonatoService.getBairrosEleitorais(cidade);
        this.bairrosEleitorais = response.data;
      } catch (error) {
        console.error('Erro ao buscar bairros eleitorais:', error);
      }
    },

    async simularElegibilidade(criteria) {
      try {
        const response = await campeonatoService.simularElegiveis(criteria);
        this.elegibilidadeCount = response.data.data.total_elegiveis;
      } catch (error) {
        console.error('Erro ao simular elegibilidade:', error);
        this.elegibilidadeCount = null;
      }
    },

    async fetchCampeonatos(params = {}) {
      this.loading = true;
      this.error = null;
      try {
        const response = await campeonatoService.getAll(params);
        this.campeonatos = response.data.data.data;
        this.pagination = {
            current_page: response.data.data.current_page,
            last_page: response.data.data.last_page,
            total: response.data.data.total,
            per_page: response.data.data.per_page,
        };
      } catch (error) {
        this.error = 'Erro ao carregar campeonatos.';
        console.error(error);
      } finally {
        this.loading = false;
      }
    },

    async fetchCampeonato(id) {
      this.loading = true;
      this.error = null;
      try {
        const response = await campeonatoService.get(id);
        this.currentCampeonato = response.data.data;
      } catch (error) {
        this.error = 'Erro ao carregar campeonato.';
        console.error(error);
      } finally {
        this.loading = false;
      }
    },

    async createCampeonato(data) {
      this.loading = true;
      this.error = null;
      try {
        await campeonatoService.create(data);
        await this.fetchCampeonatos();
      } catch (error) {
        this.error = 'Erro ao criar campeonato.';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async updateCampeonato(id, data) {
      this.loading = true;
      this.error = null;
      try {
        await campeonatoService.update(id, data);
        await this.fetchCampeonatos();
      } catch (error) {
        this.error = error.response?.data?.message || 'Erro ao atualizar campeonato.';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async deleteCampeonato(id) {
      this.loading = true;
      this.error = null;
      try {
        await campeonatoService.delete(id);
        await this.fetchCampeonatos();
      } catch (error) {
        this.error = 'Erro ao excluir campeonato.';
        throw error;
      } finally {
        this.loading = false;
      }
    },
  },
});
