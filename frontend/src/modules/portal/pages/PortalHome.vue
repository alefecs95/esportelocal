<template>
  <div class="bg-white min-h-screen font-sans">
    <!-- Hero Section (Carrossel de Destaques) -->
    <div class="relative h-[500px] lg:h-[650px] overflow-hidden group">
      <!-- Se não houver destaques carregados ainda ou vazio, mostra padrão -->
      <div v-if="!destaqueSlider || destaqueSlider.length === 0" class="absolute inset-0">
        <div class="absolute inset-0 bg-gradient-to-r from-primary-900 via-primary-900/60 to-transparent z-10"></div>
        <img 
          :src="resolveMediaUrl(theme.banner_portal_url)"
          @error="onImageError"
          alt="Esportes" 
          class="w-full h-full object-cover object-center"
        />
        <div class="container mx-auto px-4 h-full flex flex-col justify-center relative z-20 text-white">
            <div class="max-w-3xl animate-fade-in-up">
            <h1 class="text-5xl lg:text-7xl font-extrabold mb-6 leading-tight tracking-tight drop-shadow-lg">
                {{ theme.nome || 'Esporte em Movimento' }}
            </h1>
            <p class="text-blue-50 text-xl lg:text-2xl mb-10 leading-relaxed font-light drop-shadow-md max-w-2xl">
                Acompanhe as principais notícias, campeonatos e eventos esportivos da nossa cidade em um só lugar.
            </p>
            <div class="flex flex-wrap gap-4">
                <button class="bg-yellow-400 hover:bg-yellow-300 text-blue-900 px-8 py-4 rounded-full font-bold transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-1 text-lg flex items-center gap-2">
                  <span>Saiba Mais</span>
                  <ArrowRight :size="20" />
                </button>
            </div>
            </div>
        </div>
      </div>

      <!-- Slider Dinâmico -->
      <div v-else class="absolute inset-0 w-full h-full">
        <transition-group name="fade" tag="div" class="relative w-full h-full">
            <div v-for="(slide, index) in destaqueSlider" :key="slide.id" v-show="currentSlide === index" class="absolute inset-0 w-full h-full">
                <!-- Imagem de Fundo -->
                <div class="absolute inset-0">
                    <img 
                        :src="resolveMediaUrl(slide.imagem_capa_url)"
                        @error="onImageError"
                        :alt="slide.titulo" 
                        class="w-full h-full object-cover object-center transition-transform duration-[10000ms] ease-linear transform scale-100"
                        :class="{ 'scale-110': currentSlide === index }"
                    />
                    <!-- Overlay Gradiente -->
                    <div class="absolute inset-0 bg-gradient-to-t from-blue-900/90 via-blue-900/40 to-black/30"></div>
                </div>
                
                <!-- Conteúdo -->
                <div class="container mx-auto px-4 h-full flex flex-col justify-end pb-24 lg:pb-32 relative z-20 text-white pointer-events-none">
                    <div class="max-w-4xl pointer-events-auto animate-slide-up">
                        <span v-if="slide.categoria" class="inline-flex items-center bg-yellow-400 text-blue-900 text-xs font-bold px-3 py-1 rounded-full uppercase mb-6 tracking-wider shadow-sm">
                            {{ slide.categoria.nome }}
                        </span>
                        <h1 class="text-4xl lg:text-6xl font-extrabold mb-6 leading-tight tracking-tight drop-shadow-xl line-clamp-3">
                            {{ slide.titulo }}
                        </h1>
                        <p class="text-gray-100 text-lg lg:text-xl mb-8 leading-relaxed font-light line-clamp-2 max-w-3xl drop-shadow-lg hidden md:block">
                            {{ getExcerpt(slide.conteudo) }}
                        </p>
                        <div class="flex flex-wrap gap-4">
                            <router-link 
                                :to="{ name: 'PortalNoticiaDetalhe', params: { slug: slide.slug || slide.id } }"
                                class="bg-white/10 backdrop-blur-md hover:bg-white/20 text-white border border-white/30 px-8 py-3 rounded-full font-bold transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-1 text-lg inline-flex items-center gap-2"
                            >
                                <span>Ler Notícia</span>
                                <ArrowRight :size="18" />
                            </router-link>
                        </div>
                    </div>
                </div>
            </div>
        </transition-group>

        <!-- Controles do Slider -->
        <div class="absolute bottom-10 right-10 z-30 flex justify-center gap-3">
            <button 
                v-for="(_, idx) in destaqueSlider" 
                :key="idx" 
                @click="setSlide(idx)"
                class="w-3 h-3 rounded-full transition-all duration-300 shadow-sm cursor-pointer border border-white/50"
                :class="currentSlide === idx ? 'bg-yellow-400 w-10 border-yellow-400' : 'bg-transparent hover:bg-white'"
            ></button>
        </div>
      </div>
    </div>

    <!-- Seção de Notícias -->
    <section class="py-16 bg-gray-50">
      <div class="container mx-auto px-4">
        <div class="flex flex-col md:flex-row justify-between items-end mb-10 gap-4">
          <div>
             <h2 class="text-3xl font-bold text-gray-900 mb-2 flex items-center gap-2">
               <span class="w-2 h-8 bg-blue-600 rounded-full"></span>
               Últimas Notícias
             </h2>
             <p class="text-gray-500 text-lg">Fique por dentro do que acontece no esporte municipal</p>
          </div>
          <router-link to="/noticias" class="text-blue-600 font-bold hover:text-blue-800 flex items-center gap-1 transition-colors group">
            Ver todas as notícias <ArrowRight :size="16" class="group-hover:translate-x-1 transition-transform" />
          </router-link>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
          <!-- Destaque Principal (Esquerda - Maior) -->
          <div class="lg:col-span-7 xl:col-span-8">
            <div v-if="loading" class="h-[500px] bg-gray-200 animate-pulse rounded-2xl"></div>
            <div v-else-if="destaqueNoticia" class="relative h-[500px] rounded-2xl overflow-hidden shadow-xl group cursor-pointer transition-all hover:shadow-2xl">
              <img :src="resolveMediaUrl(destaqueNoticia.imagem_capa_url)" @error="onImageError" class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" />
              <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent"></div>
              
              <div class="absolute bottom-0 left-0 p-5 md:p-8 text-white w-full">
                 <div class="flex items-center gap-3 mb-4">
                   <span v-if="destaqueNoticia.categoria" class="bg-blue-600 text-white text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wide">
                     {{ destaqueNoticia.categoria.nome }}
                   </span>
                   <span class="flex items-center text-sm text-gray-300 font-medium">
                     <Calendar :size="14" class="mr-1"/> {{ formatDate(destaqueNoticia.created_at) }}
                   </span>
                 </div>
                 <h3 class="text-2xl md:text-4xl font-bold mb-4 leading-tight group-hover:text-blue-200 transition-colors">
                   <router-link :to="{ name: 'PortalNoticiaDetalhe', params: { slug: destaqueNoticia.slug || destaqueNoticia.id } }">
                     {{ destaqueNoticia.titulo }}
                   </router-link>
                 </h3>
                 <p class="text-gray-300 line-clamp-2 text-base md:text-lg font-light mb-6 max-w-2xl">
                   {{ getExcerpt(destaqueNoticia.conteudo) }}
                 </p>
                 <router-link :to="{ name: 'PortalNoticiaDetalhe', params: { slug: destaqueNoticia.slug || destaqueNoticia.id } }" class="inline-flex items-center text-white font-bold border-b-2 border-yellow-400 pb-1 hover:text-yellow-400 transition-colors">
                   Ler Matéria Completa <ArrowRight :size="16" class="ml-2" />
                 </router-link>
              </div>
            </div>
            <div v-else class="text-center py-20 bg-white rounded-2xl shadow-sm border border-gray-100 text-gray-500">
               Nenhuma notícia recente encontrada.
            </div>
          </div>
          
          <!-- Lista Lateral (Direita) -->
          <div class="lg:col-span-5 xl:col-span-4 flex flex-col gap-6">
            <div v-if="loading" class="space-y-4">
               <div v-for="i in 3" :key="i" class="h-32 bg-gray-200 animate-pulse rounded-xl"></div>
            </div>
            <div v-else class="flex flex-col gap-6 h-full">
              <div v-for="noticia in listaNoticias" :key="noticia.id" class="flex-1 bg-white p-5 rounded-2xl shadow-sm border border-gray-100 hover:shadow-lg transition-all group hover:-translate-y-1 flex flex-col justify-between">
                 <div>
                   <div class="flex justify-between items-start mb-3">
                     <span v-if="noticia.categoria" class="text-xs font-bold text-blue-600 bg-blue-50 px-2 py-1 rounded uppercase tracking-wide">
                       {{ noticia.categoria.nome }}
                     </span>
                     <span class="text-xs text-gray-400 flex items-center gap-1">
                        {{ formatDate(noticia.created_at) }}
                     </span>
                   </div>
                   <h4 class="font-bold text-gray-800 text-lg mb-3 leading-snug group-hover:text-blue-700 transition-colors line-clamp-3">
                     <router-link :to="{ name: 'PortalNoticiaDetalhe', params: { slug: noticia.slug || noticia.id } }">{{ noticia.titulo }}</router-link>
                   </h4>
                 </div>
                 <router-link :to="{ name: 'PortalNoticiaDetalhe', params: { slug: noticia.slug || noticia.id } }" class="text-sm font-bold text-gray-500 group-hover:text-blue-600 flex items-center gap-1 mt-auto pt-4 border-t border-gray-50">
                    Ler mais <ArrowRight :size="14" />
                 </router-link>
              </div>
              
              <!-- Card 'Ver Mais' se a lista for curta -->
              <router-link to="/noticias" class="bg-blue-50 p-6 rounded-2xl border-2 border-dashed border-blue-200 text-center hover:bg-blue-100 hover:border-blue-300 transition-colors group flex items-center justify-center gap-2 text-blue-700 font-bold" v-if="listaNoticias.length > 0">
                Ver Arquivo de Notícias <ArrowRight :size="18" class="group-hover:translate-x-1 transition-transform" />
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Stats Section (Faixa Azul - Melhorada) -->
    <section class="bg-gradient-to-r from-blue-900 to-blue-800 py-20 text-white relative overflow-hidden">
      <!-- Background Pattern -->
      <div class="absolute inset-0 opacity-10 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')]"></div>
      
      <!-- Círculos decorativos de fundo -->
      <div class="absolute top-0 left-0 w-64 h-64 bg-blue-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob"></div>
      <div class="absolute top-0 right-0 w-64 h-64 bg-yellow-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-2000"></div>
      <div class="absolute -bottom-8 left-20 w-64 h-64 bg-green-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-4000"></div>

      <div class="container mx-auto px-4 relative z-10">
        <div class="text-center mb-16">
          <h2 class="text-4xl lg:text-5xl font-bold mb-4 tracking-tight">Impacto no Esporte</h2>
          <div class="w-24 h-1 bg-yellow-400 mx-auto rounded-full mb-6"></div>
          <p class="text-blue-100 text-xl max-w-2xl mx-auto font-light">
            Números que refletem nosso compromisso com o desenvolvimento esportivo e bem-estar da comunidade.
          </p>
        </div>
        
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-8">
          <!-- Card 1 -->
          <div class="group relative p-8 bg-white/5 rounded-2xl backdrop-blur-md border border-white/10 hover:bg-white/10 transition-all duration-300 hover:-translate-y-2 hover:shadow-2xl flex flex-col items-center">
            <div class="mb-4 p-4 bg-yellow-400/20 rounded-full text-yellow-400 group-hover:bg-yellow-400 group-hover:text-blue-900 transition-colors duration-300">
                <Trophy :size="32" />
            </div>
            <div class="text-5xl lg:text-6xl font-extrabold text-white mb-2 tracking-tighter group-hover:scale-110 transition-transform duration-300">
                +{{ stats.campeonatos_realizados || 0 }}
            </div>
            <div class="text-sm lg:text-base text-blue-200 font-medium uppercase tracking-widest text-center">Competições</div>
          </div>

          <!-- Card 2 -->
          <div class="group relative p-8 bg-white/5 rounded-2xl backdrop-blur-md border border-white/10 hover:bg-white/10 transition-all duration-300 hover:-translate-y-2 hover:shadow-2xl flex flex-col items-center">
            <div class="mb-4 p-4 bg-green-400/20 rounded-full text-green-400 group-hover:bg-green-400 group-hover:text-blue-900 transition-colors duration-300">
                <Users :size="32" />
            </div>
            <div class="text-5xl lg:text-6xl font-extrabold text-white mb-2 tracking-tighter group-hover:scale-110 transition-transform duration-300">
                +{{ stats.atletas_cadastrados || 0 }}
            </div>
            <div class="text-sm lg:text-base text-blue-200 font-medium uppercase tracking-widest text-center">Atletas</div>
          </div>

          <!-- Card 3 -->
          <div class="group relative p-8 bg-white/5 rounded-2xl backdrop-blur-md border border-white/10 hover:bg-white/10 transition-all duration-300 hover:-translate-y-2 hover:shadow-2xl flex flex-col items-center">
             <div class="mb-4 p-4 bg-blue-400/20 rounded-full text-blue-400 group-hover:bg-blue-400 group-hover:text-blue-900 transition-colors duration-300">
                <Shield :size="32" />
            </div>
            <div class="text-5xl lg:text-6xl font-extrabold text-white mb-2 tracking-tighter group-hover:scale-110 transition-transform duration-300">
                {{ stats.equipes_ativas || 0 }}
            </div>
            <div class="text-sm lg:text-base text-blue-200 font-medium uppercase tracking-widest text-center">Equipes</div>
          </div>

          <!-- Card 4 -->
          <div class="group relative p-8 bg-white/5 rounded-2xl backdrop-blur-md border border-white/10 hover:bg-white/10 transition-all duration-300 hover:-translate-y-2 hover:shadow-2xl flex flex-col items-center">
             <div class="mb-4 p-4 bg-red-400/20 rounded-full text-red-400 group-hover:bg-red-400 group-hover:text-blue-900 transition-colors duration-300">
                <Dumbbell :size="32" />
            </div>
            <div class="text-5xl lg:text-6xl font-extrabold text-white mb-2 tracking-tighter group-hover:scale-110 transition-transform duration-300">
                {{ stats.modalidades || 0 }}
            </div>
            <div class="text-sm lg:text-base text-blue-200 font-medium uppercase tracking-widest text-center">Modalidades</div>
          </div>
        </div>
      </div>
    </section>

    <!-- Próximos Eventos -->
    <section class="py-16 bg-white" id="eventos">
      <div class="container mx-auto px-4">
         <div class="flex flex-col md:flex-row md:justify-between md:items-end items-center text-center md:text-left mb-10 gap-4">
            <div class="flex flex-col items-center md:items-start">
              <h2 class="text-3xl font-bold text-gray-900 mb-2 flex items-center gap-2">
                <span class="w-2 h-8 bg-green-500 rounded-full"></span>
                Calendário Esportivo
              </h2>
              <p class="text-gray-500 text-lg">Próximos jogos e competições oficiais</p>
            </div>
            <router-link to="/campeonatos" class="bg-white border border-gray-200 text-gray-700 px-6 py-3 rounded-xl font-bold hover:bg-gray-50 hover:border-gray-300 transition-all shadow-sm inline-flex items-center gap-2">
              <Calendar :size="18" /> Ver Agenda Completa
            </router-link>
         </div>
         
         <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div v-for="i in 4" :key="i" class="h-64 bg-gray-100 animate-pulse rounded-2xl"></div>
         </div>
         
         <div v-else-if="proximosEventos.length === 0" class="text-center py-16 bg-gray-50 rounded-2xl border border-gray-100 text-gray-500">
            <div class="mb-4 bg-gray-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto text-gray-400">
              <Calendar :size="32" />
            </div>
            <h3 class="text-lg font-bold text-gray-700 mb-1">Nenhum evento agendado</h3>
            <p>Acompanhe em breve as novas datas.</p>
         </div>

         <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
           <div v-for="evento in proximosEventos.slice(0, 4)" :key="evento.id" class="bg-white rounded-2xl shadow-sm hover:shadow-xl transition-all group border border-gray-100 overflow-hidden flex flex-col h-full hover:-translate-y-1">
             <div class="p-4 border-b border-gray-100 bg-gray-50/70">
               <div class="flex items-center justify-between gap-2">
                 <span class="text-[10px] font-bold text-blue-700 bg-blue-100 px-2 py-1 rounded uppercase tracking-wide truncate max-w-[65%]">
                   {{ evento.titulo || evento.campeonato }}
                 </span>
                 <span class="text-xs font-bold text-gray-700 bg-white px-2.5 py-1 rounded-md border border-gray-200 shrink-0">
                   {{ evento.hora }}
                 </span>
               </div>
             </div>

             <div class="p-5 flex-1 flex flex-col">
               <!-- Layout para Partida -->
               <div v-if="evento.tipo === 'partida'" class="flex items-center justify-between mb-4">
                  <div class="flex-1 text-center">
                    <div class="mb-1 flex justify-center">
                      <img
                        v-if="evento.mandante_logo_url"
                        :src="evento.mandante_logo_url"
                        :alt="evento.mandante_nome"
                        class="w-8 h-8 rounded-full object-contain bg-white border border-gray-200"
                      />
                      <div
                        v-else
                        class="w-8 h-8 rounded-full bg-gray-100 border border-gray-200 flex items-center justify-center text-[10px] font-bold text-gray-500 uppercase"
                      >
                        {{ getIniciais(evento.mandante_nome) }}
                      </div>
                    </div>
                    <div class="text-sm font-bold text-gray-800 truncate" :title="evento.mandante_nome">{{ evento.mandante_nome }}</div>
                  </div>
                  <div class="px-2 text-[11px] font-black text-gray-300">VS</div>
                  <div class="flex-1 text-center">
                    <div class="mb-1 flex justify-center">
                      <img
                        v-if="evento.visitante_logo_url"
                        :src="evento.visitante_logo_url"
                        :alt="evento.visitante_nome"
                        class="w-8 h-8 rounded-full object-contain bg-white border border-gray-200"
                      />
                      <div
                        v-else
                        class="w-8 h-8 rounded-full bg-gray-100 border border-gray-200 flex items-center justify-center text-[10px] font-bold text-gray-500 uppercase"
                      >
                        {{ getIniciais(evento.visitante_nome) }}
                      </div>
                    </div>
                    <div class="text-sm font-bold text-gray-800 truncate" :title="evento.visitante_nome">{{ evento.visitante_nome }}</div>
                  </div>
               </div>

               <!-- Layout para Evento Único (Corrida/Festival) -->
               <div v-else class="mb-4">
                  <h3 class="font-bold text-gray-800 text-lg leading-tight mb-1 line-clamp-2">{{ evento.titulo }}</h3>
                  <p class="text-sm text-gray-500">{{ evento.subtitulo }}</p>
               </div>
               
               <div class="mt-auto space-y-3 pt-4 border-t border-gray-50">
                   <div class="flex items-center text-sm text-gray-500 gap-2">
                     <Calendar :size="16" class="text-blue-500"/> <span class="font-medium">{{ evento.data }}</span>
                   </div>
                   <div class="flex items-center text-sm text-gray-500 gap-2">
                     <MapPin :size="16" class="text-red-500"/> <span class="truncate">{{ evento.local }}</span>
                   </div>
               </div>
             </div>
             
             <!-- Link diferente dependendo do tipo -->
             <router-link 
                v-if="evento.tipo === 'partida'"
                :to="{ name: 'PortalPartida', params: { id: evento.id.replace('p_', '') } }" 
                class="bg-gray-50 hover:bg-blue-600 hover:text-white text-center py-3 text-sm font-bold text-gray-600 transition-colors border-t border-gray-100"
             >
               Ver Detalhes
             </router-link>
             <button 
                v-else
                class="bg-gray-50 hover:bg-green-600 hover:text-white text-center py-3 text-sm font-bold text-gray-600 transition-colors border-t border-gray-100"
             >
               Inscrever-se / Detalhes
             </button>
           </div>
         </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useTheme } from '@/composables/useTheme';
import portalService from '../services/portalService';
import * as rankingService from '../../ranking/services/rankingService';
import { 
  Trophy, 
  Users, 
  Calendar, 
  ArrowRight, 
  Shield,
  Dumbbell,
  MapPin
} from 'lucide-vue-next';

const { theme } = useTheme();
const stats = ref({});
const proximosEventos = ref([]);
const ultimasNoticias = ref([]);
const destaqueNoticia = ref(null);
const listaNoticias = ref([]);
const destaqueSlider = ref([]);
const currentSlide = ref(0);
let slideInterval = null;
const loading = ref(true);

// Ranking logic
const rankingTab = ref('atleta');
const rankingAtletas = ref([]);
const rankingEquipes = ref([]);
const temporadaAtiva = ref(null);
const loadingRanking = ref(false);
const rankingEnabled = false;

const resolveMediaUrl = (value) => {
  if (!value || typeof value !== 'string') return null;
  const trimmed = value.trim();
  if (!trimmed) return null;

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

const onImageError = (event) => {
  const target = event?.target;
  if (!target) return;
  target.style.display = 'none';
};

const setSlide = (index) => {
    currentSlide.value = index;
    resetSlideInterval();
};

const nextSlide = () => {
    if (destaqueSlider.value.length > 0) {
        currentSlide.value = (currentSlide.value + 1) % destaqueSlider.value.length;
    }
};

const resetSlideInterval = () => {
    if (slideInterval) clearInterval(slideInterval);
    slideInterval = setInterval(nextSlide, 8000);
};

const extractEquipeNome = (value) => {
  if (!value) return 'A definir';
  if (typeof value === 'object') {
    return value.nome || value.name || 'A definir';
  }
  if (typeof value === 'string') {
    const trimmed = value.trim();
    if (!trimmed) return 'A definir';
    if (trimmed.startsWith('{') || trimmed.startsWith('[')) {
      try {
        const parsed = JSON.parse(trimmed);
        if (parsed && typeof parsed === 'object') {
          return parsed.nome || parsed.name || 'A definir';
        }
      } catch {
      }
    }
    return trimmed;
  }
  return 'A definir';
};

const extractEquipeLogo = (value) => {
  if (!value) return null;
  if (typeof value === 'object') {
    return value.logo_url || value.logo || null;
  }
  if (typeof value === 'string') {
    const trimmed = value.trim();
    if (!trimmed) return null;
    if (trimmed.startsWith('{') || trimmed.startsWith('[')) {
      try {
        const parsed = JSON.parse(trimmed);
        if (parsed && typeof parsed === 'object') {
          return parsed.logo_url || parsed.logo || null;
        }
      } catch {
      }
    }
  }
  return null;
};

const getIniciais = (nome) => {
  if (!nome) return '?';
  const partes = String(nome).trim().split(/\s+/).filter(Boolean);
  if (partes.length === 0) return '?';
  if (partes.length === 1) return partes[0].slice(0, 2).toUpperCase();
  return (partes[0][0] + partes[1][0]).toUpperCase();
};

const loadData = async () => {
  loading.value = true;
  try {
    const { data } = await portalService.getHomeData();
    stats.value = data.stats;
    proximosEventos.value = (data.proximos_eventos || []).map((evento) => ({
      ...evento,
      mandante_nome: extractEquipeNome(evento.mandante),
      visitante_nome: extractEquipeNome(evento.visitante),
      mandante_logo_url: extractEquipeLogo(evento.mandante),
      visitante_logo_url: extractEquipeLogo(evento.visitante),
    }));
    
    // Load recent news (e.g. latest 5)
    // 1. Get featured news for Slider (limit 5)
    const featuredNews = await portalService.getNoticias({ per_page: 5, destaque: true });
    
    if (featuredNews.data.data && featuredNews.data.data.length > 0) {
        destaqueSlider.value = featuredNews.data.data;
        resetSlideInterval();
    }
    
    // 2. Get latest news for "Notícias em Destaque" (below hero) and side list
    // We fetch all latest news regardless of featured status to keep timeline fresh
    const newsData = await portalService.getNoticias({ per_page: 5 });
    const allNews = newsData.data.data || [];
    
    if (allNews.length > 0) {
        // The big card below Hero
        destaqueNoticia.value = allNews[0];
        // The side list
        listaNoticias.value = allNews.slice(1, 4);
    }
    
    // Combine for legacy usage if needed
    ultimasNoticias.value = allNews;
  } catch (e) {
    console.error('Erro ao carregar dados da home', e);
  } finally {
    loading.value = false;
  }
};

const loadRanking = async () => {
  if (!rankingEnabled) {
    loadingRanking.value = false;
    return;
  }
  loadingRanking.value = true;
  try {
    // Verificar se o serviço de ranking existe e tem os métodos necessários
    if (!rankingService || typeof rankingService.getTemporadaAtiva !== 'function') {
        console.warn('Serviço de ranking não disponível ou incompleto.');
        return;
    }

    // 1. Get active season
    try {
        const { data: temporada } = await rankingService.getTemporadaAtiva();
        temporadaAtiva.value = temporada;

        if (temporada && temporada.id) {
            // 2. Get Top 10 Athletes
            // const { data: atletas } = await rankingService.getTop10(temporada.id, { type: 'atleta' });
            // rankingAtletas.value = atletas || [];

            // 3. Get Top 10 Teams
            // const { data: equipes } = await rankingService.getTop10(temporada.id, { type: 'equipe' });
            // rankingEquipes.value = equipes || [];
        }
    } catch (e) {
        if (!(e.response && e.response.status === 404)) {
            console.error('Erro ao carregar dados do ranking:', e);
        }
    }
  } catch (e) {
    console.error('Erro geral no ranking:', e);
  } finally {
    loadingRanking.value = false;
  }
};

const formatDate = (d) => {
  if (!d) return '';
  try {
    return new Date(d).toLocaleDateString('pt-BR');
  } catch {
    return '';
  }
};

const getExcerpt = (content) => {
  if (!content) return '';
  
  // Se for string JSON
  try {
    const data = typeof content === 'string' ? JSON.parse(content) : content;
    
    // Tentar pegar o primeiro bloco de texto
    if (data.blocks && Array.isArray(data.blocks)) {
      const paragraph = data.blocks.find(b => b.type === 'paragraph');
      if (paragraph && paragraph.data && paragraph.data.text) {
        // Remover tags HTML se houver (Editor.js pode ter formatting inline)
        const div = document.createElement('div');
        div.innerHTML = paragraph.data.text;
        return (div.textContent || div.innerText || '').trim();
      }
    }
  } catch (e) {
    // Se falhar o parse, assume que é texto legado
  }

  // Fallback para texto simples (legado)
  const div = document.createElement('div');
  div.innerHTML = content || '';
  return (div.textContent || div.innerText || '').trim();
};

onMounted(() => {
  loadData();
  if (rankingEnabled) {
    loadRanking();
  }
});
</script>
