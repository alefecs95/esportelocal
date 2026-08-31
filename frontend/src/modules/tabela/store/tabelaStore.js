import { defineStore } from 'pinia';
import tabelaService from '../services/tabelaService';

export const useTabelaStore = defineStore('tabela', {
  state: () => ({
    campeonato: null,
    fases: [],
    inscricoes: [], // Para corrida
    equipes: [], // Equipes do campeonato para preencher selects
    loading: false,
    error: null,
  }),

  actions: {
    async fetchTabela(campeonatoId) {
      this.loading = true;
      this.error = null;
      try {
        const response = await tabelaService.getTabela(campeonatoId);
        this.campeonato = response.data;
        this.fases = response.data.fases || [];
        this.inscricoes = response.data.inscricoes || []; // Para corrida
        
        // Se a resposta incluir equipes, popula
        if (response.data.equipes) {
            this.equipes = response.data.equipes;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Erro ao carregar tabela.';
      } finally {
        this.loading = false;
      }
    },

    async fetchEquipes(campeonatoId) {
        try {
            // Importar dinamicamente para evitar ciclo ou usar service direto se possível
            // Como tabelaService não tem getEquipes, vamos adicionar lá ou chamar api direto
            // Melhor adicionar no tabelaService para manter padrão
            const response = await tabelaService.getEquipes(campeonatoId);
            this.equipes = response.data;
        } catch (error) {
            console.error('Erro ao carregar equipes:', error);
        }
    },

    async gerarTabela(campeonatoId, formato) {
      this.loading = true;
      this.error = null;
      try {
        await tabelaService.gerarTabela(campeonatoId, formato);
        await this.fetchTabela(campeonatoId); // Recarrega
      } catch (error) {
        this.error = error.response?.data?.error || 'Erro ao gerar tabela.';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async atualizarPlacar(partidaId, placarMandante, placarVisitante) {
      try {
        await tabelaService.atualizarPlacar(partidaId, placarMandante, placarVisitante);
        // Atualizar localmente ou recarregar tudo
        // Recarregar garante consistência (ex: avanço de fase)
        if (this.campeonato) {
            await this.fetchTabela(this.campeonato.id);
        }
      } catch (error) {
        throw error;
      }
    }
  },

  getters: {
    temTabela: (state) => state.fases.length > 0 || state.campeonato?.formato === 'corrida',
    isMataMata: (state) => state.campeonato?.formato === 'mata_mata',
    isGrupos: (state) => state.campeonato?.formato === 'grupos',
    isPontosCorridos: (state) => state.campeonato?.formato === 'pontos_corridos',
    isCorrida: (state) => state.campeonato?.formato === 'corrida',
  }
});
