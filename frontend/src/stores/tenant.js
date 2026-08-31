import { defineStore } from 'pinia';
import api from '../services/api';
import { isTenantDomain } from '../utils/tenantHelpers';

const sanitizeMediaValue = (value) => {
    if (typeof value !== 'string') return null;
    const trimmed = value.trim().replace(/^['"`\s]+|['"`\s]+$/g, '');
    return trimmed || null;
};

export const useTenantStore = defineStore('tenant', {
    state: () => ({
        tenant: null,
        loading: false,
        error: null
    }),
    actions: {
        async fetchTenant() {
            this.loading = true;
            try {
                if (!isTenantDomain()) {
                    this.tenant = null;
                    this.error = null;
                    return;
                }
                // Try to get tenant info
                // Note: The backend middleware identifies the tenant via subdomain/header
                // This endpoint just returns the identified tenant info
                const response = await api.get('tenant');
                const tenantPayload = response.data || {};
                this.tenant = {
                    ...tenantPayload,
                    logo_url: sanitizeMediaValue(tenantPayload.logo_url),
                    logo: sanitizeMediaValue(tenantPayload.logo),
                    banner_portal_url: sanitizeMediaValue(tenantPayload.banner_portal_url),
                    fundo_login_url: sanitizeMediaValue(tenantPayload.fundo_login_url),
                };
                if (this.tenant?.slug) localStorage.setItem('tenant_slug', this.tenant.slug);
                if (this.tenant?.nome) localStorage.setItem('tenant_name', this.tenant.nome);
                
                // Apply theme
                if (this.tenant.cor_primaria) {
                    document.documentElement.style.setProperty('--p-primary-color', this.tenant.cor_primaria);
                    // Also set a CSS variable for Tailwind if needed
                    document.documentElement.style.setProperty('--color-primary', this.tenant.cor_primaria);
                }
                
                // Update title
                const tenantName = this.tenant?.nome || localStorage.getItem('tenant_name') || 'SEMEL - Esporte Local';
                document.title = tenantName;
                
            } catch (error) {
                const status = error?.response?.status;
                if (status === 404) {
                    this.tenant = null;
                    this.error = null;
                    return;
                }
                console.error('Failed to load tenant:', error);
                this.error = error;
            } finally {
                this.loading = false;
            }
        }
    },
    getters: {
        currentTenant: (state) => state.tenant,
        primaryColor: (state) => state.tenant?.cor_primaria || '#000000',
        logo: (state) => sanitizeMediaValue(state.tenant?.logo_url) || sanitizeMediaValue(state.tenant?.logo)
    }
});
