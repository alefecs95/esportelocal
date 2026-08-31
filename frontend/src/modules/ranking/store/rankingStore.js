import { defineStore } from 'pinia';
import { getRankingGeral, getRankingPorModalidade, getTop10, recalcularRanking, getTemporadaAtiva } from '../services/rankingService';

export const useRankingStore = defineStore('ranking', {
    state: () => ({
        rankingGeral: [],
        rankingModalidade: [],
        top10: [],
        temporadaAtiva: null,
        loading: false,
        recalculating: false,
        error: null,
    }),
    actions: {
        async fetchTemporadaAtiva() {
            try {
                const response = await getTemporadaAtiva();
                this.temporadaAtiva = response.data;
                return response.data;
            } catch (error) {
                console.error('Erro ao buscar temporada ativa:', error);
                return null;
            }
        },

        async fetchRankingGeral(temporadaId, params = {}) {
            this.loading = true;
            this.error = null;
            try {
                const response = await getRankingGeral(temporadaId, params);
                this.rankingGeral = response.data;
            } catch (error) {
                this.error = error;
                console.error('Erro ao buscar ranking geral:', error);
            } finally {
                this.loading = false;
            }
        },

        async fetchRankingPorModalidade(temporadaId, modalidadeId, params = {}) {
            this.loading = true;
            this.error = null;
            try {
                const response = await getRankingPorModalidade(temporadaId, modalidadeId, params);
                this.rankingModalidade = response.data;
            } catch (error) {
                this.error = error;
                console.error('Erro ao buscar ranking por modalidade:', error);
            } finally {
                this.loading = false;
            }
        },

        async fetchTop10(temporadaId, params = {}) {
            this.loading = true;
            this.error = null;
            try {
                const response = await getTop10(temporadaId, params);
                this.top10 = response.data;
            } catch (error) {
                this.error = error;
                console.error('Erro ao buscar top 10:', error);
            } finally {
                this.loading = false;
            }
        },

        async recalcularRanking() {
            this.recalculating = true;
            this.error = null;
            try {
                await recalcularRanking();
                // Após recalcular, recarrega os dados se tivermos uma temporada ativa
                if (this.temporadaAtiva) {
                    // Limpar caches locais se necessário ou recarregar view atual
                    // Como não sabemos em qual view o usuário está, o componente deve reagir
                }
                return true;
            } catch (error) {
                this.error = error;
                console.error('Erro ao recalcular ranking:', error);
                throw error;
            } finally {
                this.recalculating = false;
            }
        }
    }
});
