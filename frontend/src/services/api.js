import axios from 'axios';
import {
    getMainOrigin,
    isLocalHost,
    isMainDomainHost,
    isTenantSubdomainHost,
} from '@/utils/domainHelpers';

const host = window.location.hostname;
const envApiUrl = import.meta.env.VITE_API_URL;

const defaultRelativeApiBase = '/backend/public/api/v1';

const resolveAbsoluteApiBase = () => `${getMainOrigin()}${defaultRelativeApiBase}`;

const baseURL = (() => {
    if (!envApiUrl) {
        return isTenantSubdomainHost(host)
            ? resolveAbsoluteApiBase()
            : defaultRelativeApiBase;
    }

    if (/^https?:\/\//i.test(envApiUrl)) {
        return envApiUrl.replace(/\/+$/, '');
    }

    const normalizedPath = envApiUrl.startsWith('/') ? envApiUrl : `/${envApiUrl}`;

    if (!isLocalHost(host) && normalizedPath.startsWith('/')) {
        return `${getMainOrigin()}${normalizedPath.replace(/\/+$/, '')}`;
    }

    return normalizedPath.replace(/\/+$/, '');
})();

const apiBaseCandidates = (() => {
    if (envApiUrl) {
        return [baseURL];
    }

    const mainOriginBase = resolveAbsoluteApiBase();

    const candidates = isTenantSubdomainHost(host)
        ? [
            mainOriginBase,
            `${getMainOrigin()}/backend/api/v1`,
            `${getMainOrigin()}/api/v1`,
        ]
        : [
            defaultRelativeApiBase,
            '/backend/api/v1',
            '/api/v1',
            mainOriginBase,
            `${getMainOrigin()}/backend/api/v1`,
            `${getMainOrigin()}/api/v1`,
        ];

    return Array.from(new Set(candidates));
})();

const api = axios.create({
    baseURL,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
});

const sanitizeSlug = (value) => {
    if (typeof value !== 'string') return null;
    const trimmed = value.trim();
    if (!trimmed || trimmed === 'null' || trimmed === 'undefined') return null;
    return trimmed;
};

const getTenantDomain = () => {
    const currentHost = window.location.hostname;
    if (!currentHost) return null;
    if (isLocalHost(currentHost)) return null;
    if (isMainDomainHost(currentHost)) return null;
    return currentHost.toLowerCase();
};

const getTenantSlug = () => {
    const currentHost = window.location.hostname;
    const envTenantSlug = sanitizeSlug(import.meta.env.VITE_TENANT_SLUG);
    const savedTenantSlug = sanitizeSlug(localStorage.getItem('tenant_slug'));
    const defaultLocalSlug = 'padrao';

    if (isMainDomainHost(currentHost)) {
        return null;
    }

    if (isLocalHost(currentHost)) {
        return envTenantSlug || savedTenantSlug || defaultLocalSlug;
    }

    const parts = currentHost.split('.');

    if (parts.length >= 2 && parts[parts.length - 1] === 'localhost') {
        return sanitizeSlug(parts[0]);
    }

    if (parts.length >= 3) {
        return sanitizeSlug(parts[0]);
    }

    return envTenantSlug || null;
};

api.interceptors.request.use(config => {
    const token = localStorage.getItem('token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }

    if (config.data instanceof FormData) {
        delete config.headers['Content-Type'];
    }

    const tenantSlug = getTenantSlug();
    if (tenantSlug && !config._skipTenantSlug) {
        config.headers['X-Tenant-Slug'] = tenantSlug;
    }

    const tenantDomain = getTenantDomain();
    if (tenantDomain && !config._skipTenantDomain) {
        config.headers['X-Tenant-Domain'] = tenantDomain;
    }

    return config;
}, error => Promise.reject(error));

api.interceptors.response.use(
    response => response,
    async error => {
        const originalRequest = error.config;
        const errorMessage = error.response?.data?.message;
        const statusCode = error.response?.status;
        const method = (originalRequest?.method || 'get').toLowerCase();

        if (statusCode === 404 && method === 'get' && originalRequest && !envApiUrl) {
            const currentBase = originalRequest.baseURL || baseURL;
            const currentIndex = Math.max(0, apiBaseCandidates.indexOf(currentBase));
            const nextIndex = currentIndex + 1;

            if (nextIndex < apiBaseCandidates.length && !originalRequest._baseFallbackRetry) {
                originalRequest._baseFallbackRetry = true;
                originalRequest.baseURL = apiBaseCandidates[nextIndex];
                return api.request(originalRequest);
            }
        }

        const isTenantResolutionError =
            errorMessage === 'Tenant not found.' ||
            errorMessage === 'Tenant not identified or invalid.' ||
            errorMessage === 'Nenhum tenant identificado.';

        if ((error.response?.status === 404 || error.response?.status === 400) && isTenantResolutionError && originalRequest && !originalRequest._tenantRetry) {
            localStorage.removeItem('tenant_slug');
            originalRequest._tenantRetry = true;
            originalRequest._skipTenantSlug = true;
            if (originalRequest.headers) {
                delete originalRequest.headers['X-Tenant-Slug'];
            }
            return api.request(originalRequest);
        }

        if (error.response && error.response.status === 401) {
            if (window.location.pathname !== '/login' && window.location.pathname !== '/login/register') {
                localStorage.removeItem('token');
                localStorage.removeItem('user');
                window.location.href = '/login';
            }
        }
        return Promise.reject(error);
    }
);

export default api;
