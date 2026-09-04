export function shareBackendBaseUrl() {
  const envApiUrl = import.meta.env.VITE_API_URL;
  if (envApiUrl && /^https?:\/\//i.test(envApiUrl)) {
    return envApiUrl.replace(/\/api\/v1\/?$/i, '');
  }
  return `${window.location.origin}/backend/public`;
}

export function memoriaShareUrl(tipo, slug) {
  return `${shareBackendBaseUrl()}/share/memoria/${tipo}/${slug}`;
}
