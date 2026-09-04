<template>
  <div class="min-h-screen bg-gray-50 flex flex-col font-sans">
    <!-- Navbar Público - Apenas visível no Portal do Tenant -->
    <header v-if="isTenant" class="bg-primary-600 text-white shadow-md sticky top-0 z-50 border-b border-primary-700 pt-[env(safe-area-inset-top)]">
      <div class="container mx-auto px-4 py-3 flex justify-between items-center gap-2 min-w-0">
        <!-- Logo Area -->
        <router-link to="/" class="flex items-center gap-2 sm:gap-3 group min-w-0 flex-1 xl:flex-none">
            <div class="relative w-10 h-10 shrink-0 flex items-center justify-center bg-white/10 rounded-lg group-hover:bg-white/20 transition-colors">
                <img :src="logoSrc" @error="logoLoadFailed = true" class="h-8 w-8 object-contain" alt="Logo" />
            </div>
            <div class="min-w-0 leading-tight">
                <span class="block truncate font-black text-sm sm:text-base md:text-xl tracking-tight uppercase group-hover:text-yellow-400 transition-colors">{{ theme.nome || 'PORTAL MUNICIPAL' }}</span>
            </div>
        </router-link>

        <!-- Menu Desktop (mesmo breakpoint do botão hambúrguer) -->
        <nav class="hidden xl:flex items-center gap-1 bg-primary-700/30 p-1 rounded-full backdrop-blur-sm border border-white/10 shrink-0">
            <router-link to="/" class="px-4 py-2 rounded-full text-sm font-bold transition-all hover:bg-white/10 hover:text-yellow-400" active-class="bg-white text-primary-700 shadow-sm hover:bg-white hover:text-primary-700" exact-active-class="bg-white text-primary-700 shadow-sm">Início</router-link>
            <router-link to="/noticias" class="px-4 py-2 rounded-full text-sm font-bold transition-all hover:bg-white/10 hover:text-yellow-400" active-class="bg-white text-primary-700 shadow-sm hover:bg-white hover:text-primary-700">Notícias</router-link>
            <router-link to="/memoria" class="px-4 py-2 rounded-full text-sm font-bold transition-all hover:bg-white/10 hover:text-yellow-400" active-class="bg-white text-primary-700 shadow-sm hover:bg-white hover:text-primary-700">Memória</router-link>
            <router-link to="/campeonatos" class="px-4 py-2 rounded-full text-sm font-bold transition-all hover:bg-white/10 hover:text-yellow-400" active-class="bg-white text-primary-700 shadow-sm hover:bg-white hover:text-primary-700">Eventos</router-link>
            <router-link to="/partidas" class="px-4 py-2 rounded-full text-sm font-bold transition-all hover:bg-white/10 hover:text-yellow-400" active-class="bg-white text-primary-700 shadow-sm hover:bg-white hover:text-primary-700">Partidas</router-link>
            <router-link to="/equipes" class="px-4 py-2 rounded-full text-sm font-bold transition-all hover:bg-white/10 hover:text-yellow-400" active-class="bg-white text-primary-700 shadow-sm hover:bg-white hover:text-primary-700">Equipes</router-link>
            <router-link to="/atletas" class="px-4 py-2 rounded-full text-sm font-bold transition-all hover:bg-white/10 hover:text-yellow-400" active-class="bg-white text-primary-700 shadow-sm hover:bg-white hover:text-primary-700">Atletas</router-link>
            <router-link to="/diretoria" class="px-4 py-2 rounded-full text-sm font-bold transition-all hover:bg-white/10 hover:text-yellow-400" active-class="bg-white text-primary-700 shadow-sm hover:bg-white hover:text-primary-700">Diretoria</router-link>
        </nav>
        
        <!-- Actions Area -->
        <div class="flex items-center gap-2 sm:gap-3 shrink-0">
            <!-- Login Button (Desktop) -->
            <router-link to="/login" class="hidden xl:flex items-center gap-2 bg-yellow-400 hover:bg-yellow-300 text-primary-900 px-5 py-2.5 rounded-full font-black text-sm transition-all shadow-lg hover:shadow-xl hover:-translate-y-0.5 transform">
                <User :size="18" stroke-width="2.5" />
                <span>ENTRAR</span>
            </router-link>
            
            <!-- Mobile Menu Toggle -->
            <button @click="mobileMenuOpen = !mobileMenuOpen" class="xl:hidden w-10 h-10 flex items-center justify-center rounded-full hover:bg-white/10 transition-colors text-white relative z-50" :aria-expanded="mobileMenuOpen" aria-label="Abrir menu">
                <X v-if="mobileMenuOpen" :size="24" />
                <Menu v-else :size="24" />
            </button>
        </div>
      </div>

      <!-- Mobile Menu Overlay -->
      <transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 -translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 -translate-y-2"
      >
        <div v-if="mobileMenuOpen" class="absolute top-full left-0 w-full bg-white border-b border-gray-100 shadow-xl xl:hidden text-gray-800 py-4 px-4 flex flex-col gap-2 z-40 max-h-[min(80vh,calc(100dvh-4rem))] overflow-y-auto">
            <router-link to="/" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold hover:bg-gray-50 transition-colors" active-class="bg-blue-50 text-blue-600" @click="mobileMenuOpen = false">
                <Home :size="20" /> Início
            </router-link>
            <router-link to="/noticias" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold hover:bg-gray-50 transition-colors" active-class="bg-blue-50 text-blue-600" @click="mobileMenuOpen = false">
                <Newspaper :size="20" /> Notícias
            </router-link>
            <router-link to="/memoria" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold hover:bg-gray-50 transition-colors" active-class="bg-blue-50 text-blue-600" @click="mobileMenuOpen = false">
                <Landmark :size="20" /> Memória
            </router-link>
            <router-link to="/campeonatos" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold hover:bg-gray-50 transition-colors" active-class="bg-blue-50 text-blue-600" @click="mobileMenuOpen = false">
                <Trophy :size="20" /> Eventos
            </router-link>
            <router-link to="/partidas" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold hover:bg-gray-50 transition-colors" active-class="bg-blue-50 text-blue-600" @click="mobileMenuOpen = false">
                <Calendar :size="20" /> Partidas
            </router-link>
            <router-link to="/equipes" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold hover:bg-gray-50 transition-colors" active-class="bg-blue-50 text-blue-600" @click="mobileMenuOpen = false">
                <Shield :size="20" /> Equipes
            </router-link>
            <router-link to="/atletas" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold hover:bg-gray-50 transition-colors" active-class="bg-blue-50 text-blue-600" @click="mobileMenuOpen = false">
                <Users :size="20" /> Atletas
            </router-link>
            <router-link to="/diretoria" class="flex items-center gap-3 px-4 py-3 rounded-xl font-bold hover:bg-gray-50 transition-colors" active-class="bg-blue-50 text-blue-600" @click="mobileMenuOpen = false">
                <Briefcase :size="20" /> Diretoria
            </router-link>
            
            <div class="border-t border-gray-100 my-2 pt-2">
                <router-link to="/login" class="flex items-center justify-center gap-2 bg-primary-600 text-white px-4 py-3 rounded-xl font-bold shadow-sm active:scale-95 transition-transform" @click="mobileMenuOpen = false">
                    <User :size="18" /> Acessar Área Restrita
                </router-link>
            </div>
        </div>
      </transition>
    </header>

    <!-- Conteúdo -->
    <main class="flex-1" :class="{ 'bg-white': !isTenant }">
      <router-view></router-view>
    </main>

    <!-- Footer Público - Apenas visível no Portal do Tenant -->
    <footer v-if="isTenant" class="bg-white text-gray-600 py-12 border-t border-gray-200">
      <div class="container mx-auto px-4 flex flex-col items-center gap-6 text-center">
        <div class="text-center">
            <img v-if="logoRodapeSrc" :src="logoRodapeSrc" class="h-14 object-contain mx-auto mb-3" alt="Logo do rodapé" />
            <h4 class="font-bold text-gray-800 text-lg mb-2">{{ theme.footer_titulo || 'Secretaria Municipal de Esporte e Lazer' }}</h4>
            <div v-if="theme.endereco || theme.telefone" class="text-sm text-gray-500 mb-4 space-y-1">
                <p v-if="theme.endereco">{{ theme.endereco }}</p>
                <div class="flex flex-col sm:flex-row gap-1 sm:gap-4 justify-center break-words px-2">
                    <p v-if="theme.telefone">Tel: {{ theme.telefone }}</p>
                    <p v-if="theme.email_contato" class="break-all">Email: {{ theme.email_contato }}</p>
                </div>
            </div>
            <div class="flex flex-wrap gap-x-4 gap-y-2 text-sm font-medium text-gray-500 justify-center px-2">
                <a v-if="theme.footer_link_transparencia" :href="theme.footer_link_transparencia" target="_blank" rel="noopener noreferrer" class="hover:text-primary-600">Transparência</a>
                <a v-if="theme.footer_link_editais" :href="theme.footer_link_editais" target="_blank" rel="noopener noreferrer" class="hover:text-primary-600">Editais</a>
                <a v-if="theme.footer_link_contato" :href="theme.footer_link_contato" target="_blank" rel="noopener noreferrer" class="hover:text-primary-600">Contato</a>
            </div>
        </div>
        
        <div v-if="hasSocialLinks" class="flex gap-4">
            <a v-if="theme.redes_sociais?.facebook" :href="theme.redes_sociais.facebook" target="_blank" rel="noopener noreferrer" class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-primary-600 hover:bg-blue-200 transition-colors">
                <Facebook :size="16" />
            </a>
            <a v-if="theme.redes_sociais?.x" :href="theme.redes_sociais.x" target="_blank" rel="noopener noreferrer" class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-primary-600 hover:bg-blue-200 transition-colors">
                <Twitter :size="16" />
            </a>
            <a v-if="theme.redes_sociais?.instagram" :href="theme.redes_sociais.instagram" target="_blank" rel="noopener noreferrer" class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-primary-600 hover:bg-blue-200 transition-colors">
                <Instagram :size="16" />
            </a>
            <a v-if="theme.redes_sociais?.youtube" :href="theme.redes_sociais.youtube" target="_blank" rel="noopener noreferrer" class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-primary-600 hover:bg-blue-200 transition-colors">
                <Youtube :size="16" />
            </a>
        </div>
      </div>
      <div class="container mx-auto px-4 mt-8 pt-8 border-t border-gray-100 text-center text-xs text-gray-400 space-y-1">
        &copy; 2026 {{ theme.nome || 'SEMEL' }}. Todos os direitos reservados.
        <div>
          Plataforma por <a :href="platformOrigin" target="_blank" rel="noopener noreferrer" class="font-semibold text-gray-500 hover:text-primary-600">ESPORTE LOCAL</a>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { Facebook, Twitter, Instagram, Youtube, Menu, User, X, Home, Newspaper, Trophy, Calendar, Shield, Users, Briefcase, Landmark } from 'lucide-vue-next';
import { useTheme } from '@/composables/useTheme';
import { ref, onMounted, computed, watch } from 'vue';
import { isTenantDomain } from '@/utils/tenantHelpers';
import { getMainOrigin } from '@/utils/domainHelpers';

const { theme } = useTheme();
const mobileMenuOpen = ref(false);
const isTenant = ref(true);
const logoLoadFailed = ref(false);
const fallbackLogo = 'https://cdn-icons-png.flaticon.com/512/9386/9386916.png';
const platformOrigin = getMainOrigin();

const resolveLogoUrl = (value) => {
    if (!value || typeof value !== 'string') {
        return null;
    }

    const trimmed = value.trim();
    if (!trimmed) {
        return null;
    }

    if (/^https?:\/\//i.test(trimmed) || trimmed.startsWith('data:')) {
        return trimmed;
    }

    if (trimmed.startsWith('/backend/public/storage/')) {
        return trimmed;
    }

    if (trimmed.startsWith('/storage/')) {
        return `/backend/public${trimmed}`;
    }

    if (trimmed.startsWith('/')) {
        return trimmed;
    }

    return `/${trimmed}`;
};

const logoSrc = computed(() => {
    if (logoLoadFailed.value) {
        return fallbackLogo;
    }

    return resolveLogoUrl(theme.value?.logo_url)
        || resolveLogoUrl(theme.value?.logo)
        || fallbackLogo;
});

const logoRodapeSrc = computed(() => {
    return resolveLogoUrl(theme.value?.logo_rodape_url)
        || resolveLogoUrl(theme.value?.logo_rodape)
        || resolveLogoUrl(theme.value?.logo_url)
        || resolveLogoUrl(theme.value?.logo)
        || null;
});

const hasSocialLinks = computed(() => {
    const redes = theme.value?.redes_sociais || {};
    return Boolean(redes.facebook || redes.instagram || redes.youtube || redes.x);
});

watch(
    () => [theme.value?.logo_url, theme.value?.logo],
    () => {
        logoLoadFailed.value = false;
    }
);

onMounted(() => {
    isTenant.value = isTenantDomain();
});
</script>
