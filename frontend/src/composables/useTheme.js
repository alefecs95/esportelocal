import { ref } from 'vue';
import api from '@/services/api';
import { isTenantDomain } from '@/utils/tenantHelpers';

const theme = ref({
  nome: 'SEMEL',
  cor_primaria: '#2563EB', // blue-600 default
  cor_secundaria: '#ffffff',
  logo_url: null,
  logo_rodape_url: null,
  banner_portal_url: null,
  fundo_login_url: null,
  footer_titulo: '',
  footer_link_transparencia: '',
  footer_link_editais: '',
  footer_link_contato: '',
  redes_sociais: {},
  configuracao_estilo: {}
});

export function useTheme() {
  
  const updateTheme = (newTheme) => {
    if (!newTheme) return;
    
    theme.value = { ...theme.value, ...newTheme };
    
    const root = document.documentElement;
    
    if (newTheme.cor_primaria) {
      root.style.setProperty('--color-primary', newTheme.cor_primaria);
    }
    
    if (newTheme.cor_secundaria) {
      root.style.setProperty('--color-secondary', newTheme.cor_secundaria);
    }

    // Configurações de estilo extras
    if (newTheme.configuracao_estilo) {
        if (newTheme.configuracao_estilo.sidebar_color) {
            root.style.setProperty('--color-sidebar-bg', newTheme.configuracao_estilo.sidebar_color);
        }
        if (newTheme.configuracao_estilo.sidebar_text_color) {
            root.style.setProperty('--color-sidebar-text', newTheme.configuracao_estilo.sidebar_text_color);
        }
        if (newTheme.configuracao_estilo.link_color) {
            root.style.setProperty('--color-link', newTheme.configuracao_estilo.link_color);
        }
    }

    if (newTheme.nome) {
      document.title = newTheme.nome;
    }

    if (newTheme.logo_url) {
      let link = document.querySelector("link[rel~='icon']");
      if (!link) {
        link = document.createElement('link');
        link.rel = 'icon';
        document.head.appendChild(link);
      }
      link.href = newTheme.logo_url;
    }
  };

  const loadTheme = async () => {
    try {
      if (!isTenantDomain()) {
        return;
      }
      // Tenta pegar do tenant público (funciona para todos se tiver slug/dominio configurado no api.js)
      const { data } = await api.get('/tenant');
      updateTheme(data);
    } catch (error) {
      console.warn('Não foi possível carregar tema do tenant (pode ser ambiente local sem subdomínio):', error);
    }
  };

  return {
    theme,
    updateTheme,
    loadTheme
  };
}
