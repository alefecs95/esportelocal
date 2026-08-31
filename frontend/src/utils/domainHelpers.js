const LOCAL_HOSTS = ['localhost', '127.0.0.1'];

/**
 * Domínio principal da plataforma (sem subdomínio de tenant).
 * Prioridade: VITE_MAIN_DOMAIN > detecção automática pelo hostname.
 */
export function getMainDomain() {
    const envDomain = (import.meta.env.VITE_MAIN_DOMAIN || '').trim().replace(/^www\./, '');
    if (envDomain) {
        return envDomain;
    }

    const host = window.location.hostname;

    if (LOCAL_HOSTS.includes(host) || host.endsWith('.localhost') || /^\d{1,3}(?:\.\d{1,3}){3}$/.test(host)) {
        return host;
    }

    const parts = host.split('.');

    if (parts[0] === 'www' && parts.length >= 2) {
        return parts.slice(1).join('.');
    }

    // tenant.example.com ou tenant.example.com.br
    if (parts.length >= 3) {
        return parts.slice(1).join('.');
    }

    return host;
}

export function getMainDomainHosts() {
    const main = getMainDomain();
    return [...new Set([main, `www.${main}`, ...LOCAL_HOSTS, '127.0.0.1'])];
}

export function isMainDomainHost(host = window.location.hostname) {
    const main = getMainDomain();
    return host === main || host === `www.${main}` || LOCAL_HOSTS.includes(host);
}

export function isTenantSubdomainHost(host = window.location.hostname) {
    if (LOCAL_HOSTS.includes(host) || host.endsWith('.localhost') || /^\d{1,3}(?:\.\d{1,3}){3}$/.test(host)) {
        return false;
    }

    if (isMainDomainHost(host)) {
        return false;
    }

    const main = getMainDomain();
    return host.endsWith(`.${main}`);
}

export function isLocalHost(host = window.location.hostname) {
    return LOCAL_HOSTS.includes(host) || host.endsWith('.localhost') || /^\d{1,3}(?:\.\d{1,3}){3}$/.test(host);
}

export function getMainOrigin() {
    const host = window.location.hostname;

    if (isLocalHost(host)) {
        return `${window.location.protocol}//${window.location.host}`;
    }

    const protocol = window.location.protocol;
    const main = getMainDomain();
    const port = window.location.port;
    const defaultPort = protocol === 'https:' ? '443' : '80';
    const portSuffix = port && port !== defaultPort ? `:${port}` : '';

    return `${protocol}//${main}${portSuffix}`;
}

export function buildTenantSubdomainUrl(slug, { port = null } = {}) {
    const normalizedSlug = String(slug || '').trim();
    if (!normalizedSlug) {
        return '#';
    }

    const host = window.location.hostname;

    if (isLocalHost(host)) {
        const devPort = port || import.meta.env.VITE_DEV_PORT || '5173';
        return `http://${normalizedSlug}.localhost:${devPort}`;
    }

    const protocol = window.location.protocol;
    const main = getMainDomain();
    const currentPort = window.location.port;
    const defaultPort = protocol === 'https:' ? '443' : '80';
    const portSuffix = currentPort && currentPort !== defaultPort ? `:${currentPort}` : '';

    return `${protocol}//${normalizedSlug}.${main}${portSuffix}`;
}
