import { defineStore } from 'pinia';
import atletaService from '../services/atletaService';

const resolveTituloErrorMessage = (error) => {
    const status = error?.response?.status;
    const apiMessage = error?.response?.data?.message;

    if (status === 400) {
        return 'Requisição inválida: verifique os parâmetros informados.';
    }
    if (status === 401) {
        return 'Não autenticado: faça login novamente para continuar.';
    }
    if (status === 403) {
        return 'Não autorizado: consulta negada por saldo indisponível ou permissão.';
    }
    if (status === 404) {
        return 'Não encontrado: recurso da consulta eleitoral não localizado.';
    }
    if (status === 408) {
        return 'Tempo esgotado: a consulta demorou além do limite. Tente novamente.';
    }
    if (status === 500) {
        return 'Falha ao realizar consulta: o servidor não conseguiu processar a solicitação.';
    }
    if (status === 503) {
        return 'Consulta em manutenção: o serviço eleitoral está temporariamente indisponível.';
    }

    return apiMessage || 'Erro ao consultar Título';
};

export const useAtletaStore = defineStore('atleta', {
    state: () => ({
        atletas: [],
        atleta: null,
        loading: false,
        error: null,
        successMessage: null,
        pagination: {
            current_page: 1,
            last_page: 1,
            total: 0,
        },
        currentSearch: '',
        currentFilters: {}
    }),

    actions: {
        async fetchAtletas(page = 1, search = '', filters = {}) {
            this.loading = true;
            this.error = null;
            try {
                this.currentSearch = search;
                this.currentFilters = { ...filters };
                const response = await atletaService.getAtletas(page, search, filters);
                this.atletas = response.data.data.data;
                this.pagination = {
                    current_page: response.data.data.current_page,
                    last_page: response.data.data.last_page,
                    total: response.data.data.total,
                };
            } catch (error) {
                this.error = error.response?.data?.message || 'Erro ao carregar atletas';
            } finally {
                this.loading = false;
            }
        },

        async fetchAtleta(id) {
            this.loading = true;
            this.error = null;
            try {
                const response = await atletaService.getAtleta(id);
                this.atleta = response.data.data;
            } catch (error) {
                this.error = error.response?.data?.message || 'Erro ao carregar atleta';
            } finally {
                this.loading = false;
            }
        },

        async createAtleta(data) {
            this.loading = true;
            this.error = null;
            this.successMessage = null;
            try {
                const response = await atletaService.createAtleta(data);
                this.successMessage = response.data.message;
                return true;
            } catch (error) {
                // Capturar erros de validação
                if (error.response && error.response.status === 422) {
                     // Formata os erros de validação em uma string ou objeto
                     // Aqui pegamos o primeiro erro de cada campo para simplificar, ou a mensagem geral
                     const validationErrors = error.response.data.errors;
                     if (validationErrors) {
                         this.error = Object.values(validationErrors).flat().join('\n');
                     } else {
                         this.error = error.response.data.message;
                     }
                } else {
                    this.error = error.response?.data?.message || 'Erro ao criar atleta';
                }
                return false;
            } finally {
                this.loading = false;
            }
        },

        async updateAtleta(id, data) {
            this.loading = true;
            this.error = null;
            try {
                await atletaService.updateAtleta(id, data);
                return true;
            } catch (error) {
                this.error = error.response?.data?.message || 'Erro ao atualizar atleta';
                return false;
            } finally {
                this.loading = false;
            }
        },

        async deleteAtleta(id) {
            this.loading = true;
            this.error = null;
            try {
                await atletaService.deleteAtleta(id);
                await this.fetchAtletas(this.pagination.current_page, this.currentSearch, this.currentFilters);
                return true;
            } catch (error) {
                this.error = error.response?.data?.message || 'Erro ao deletar atleta';
                return false;
            } finally {
                this.loading = false;
            }
        },

        async updateAtletaStatus(id, payload) {
            this.loading = true;
            this.error = null;
            try {
                await atletaService.updateAtletaStatus(id, payload);
                await this.fetchAtletas(this.pagination.current_page, this.currentSearch, this.currentFilters);
                return true;
            } catch (error) {
                this.error = error.response?.data?.message || 'Erro ao atualizar status do atleta';
                return false;
            } finally {
                this.loading = false;
            }
        },

        async consultarCpf(cpf) {
            this.loading = true;
            this.error = null;
            try {
                const response = await atletaService.consultarCpf(cpf);
                return response.data.data;
            } catch (error) {
                this.error = error.response?.data?.message || 'Erro ao consultar CPF';
                return null;
            } finally {
                this.loading = false;
            }
        },

        async consultarTitulo(dados) {
            this.loading = true;
            this.error = null;
            try {
                const response = await atletaService.consultarTitulo(dados);
                return response.data.data;
            } catch (error) {
                this.error = resolveTituloErrorMessage(error);
                return null;
            } finally {
                this.loading = false;
            }
        },

        async revalidarTitulo(id) {
            this.loading = true;
            this.error = null;
            try {
                const response = await atletaService.revalidarTitulo(id);
                await this.fetchAtletas(this.pagination.current_page, this.currentSearch, this.currentFilters);
                return response.data.data;
            } catch (error) {
                this.error = error.response?.data?.message || 'Erro ao revalidar título';
                return null;
            } finally {
                this.loading = false;
            }
        },

        async aprovarDadosEleitoraisManual(id) {
            this.loading = true;
            this.error = null;
            try {
                const response = await atletaService.aprovarDadosEleitoraisManual(id);
                await this.fetchAtletas(this.pagination.current_page, this.currentSearch, this.currentFilters);
                return response.data.data;
            } catch (error) {
                this.error = error.response?.data?.message || 'Erro ao aprovar dados eleitorais manualmente';
                return null;
            } finally {
                this.loading = false;
            }
        },

        async resetPassword(id) {
            this.loading = true;
            this.error = null;
            try {
                const response = await atletaService.resetPassword(id);
                return response.data.data;
            } catch (error) {
                this.error = error.response?.data?.message || 'Erro ao resetar senha';
                return null;
            } finally {
                this.loading = false;
            }
        },

        async gerarUsuario(id) {
            this.loading = true;
            this.error = null;
            try {
                const response = await atletaService.gerarUsuario(id);
                await this.fetchAtletas(this.pagination.current_page, this.currentSearch, this.currentFilters);
                return response.data.data;
            } catch (error) {
                this.error = error.response?.data?.message || 'Erro ao gerar usuário';
                return null;
            } finally {
                this.loading = false;
            }
        }
    }
});
