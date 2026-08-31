import { getMainDomainHosts, isLocalHost } from '@/utils/domainHelpers';

/**
 * Verifica se o hostname atual é um subdomínio de tenant (não o domínio principal).
 */
export function isTenantDomain() {
    const hostname = window.location.hostname;
    const mainDomains = getMainDomainHosts();

    if (mainDomains.includes(hostname)) {
        return false;
    }

    if (!hostname || isLocalHost(hostname)) {
        return false;
    }

    if (hostname.endsWith('.localhost')) {
        return true;
    }

    const parts = hostname.split('.');

    if (parts.length >= 3 && parts[0] !== 'www') {
        return true;
    }

    if (parts.length >= 2) {
        return true;
    }

    return false;
}
