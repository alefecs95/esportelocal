<template>
  <div class="bg-white min-h-screen font-sans overflow-x-clip">
    <!-- Hero Section (Carrossel de Destaques) -->
    <div
      class="relative h-[min(70svh,28rem)] sm:h-[min(68svh,36rem)] lg:h-[650px] overflow-hidden group"
      @touchstart.passive="onTouchStart"
      @touchend.passive="onTouchEnd"
    >
      <!-- Se não houver destaques carregados ainda ou vazio, mostra padrão -->
      <div v-if="!destaqueSlider || destaqueSlider.length === 0" class="absolute inset-0">
        <div class="absolute inset-0 bg-gradient-to-t from-primary-900 via-primary-900/55 to-black/20 sm:bg-gradient-to-r sm:from-primary-900 sm:via-primary-900/60 sm:to-transparent z-10"></div>
        <img
          :src="resolveMediaUrl(theme.banner_portal_url)"
          @error="onImageError"
          alt="Esportes"
          class="w-full h-full object-cover object-center"
        />
        <div class="container mx-auto px-4 h-full flex flex-col justify-end sm:justify-center pb-10 sm:pb-16 relative z-20 text-white">
            <div class="max-w-3xl animate-fade-in-up">
            <h1 class="text-[1.75rem] leading-tight sm:text-5xl lg:text-7xl font-extrabold mb-3 sm:mb-6 tracking-tight drop-shadow-lg">
                {{ theme.nome || 'Esporte em Movimento' }}
            </h1>
            <p class="text-blue-50 text-sm sm:text-xl lg:text-2xl mb-5 sm:mb-10 leading-relaxed font-light drop-shadow-md max-w-2xl line-clamp-3">
                Acompanhe as principais notícias, campeonatos e eventos esportivos da nossa cidade em um só lugar.
            </p>
            <div class="flex flex-wrap gap-3">
                <a href="#noticias" class="bg-yellow-400 hover:bg-yellow-300 text-blue-900 px-5 py-3 sm:px-8 sm:py-4 rounded-full font-bold transition-all shadow-lg hover:shadow-xl sm:hover:-translate-y-1 text-sm sm:text-lg inline-flex items-center gap-2 min-h-11">
                  <span>Ver notícias</span>
                  <ArrowRight :size="18" />
                </a>
            </div>
            </div>
        </div>
      </div>

      <!-- Slider Dinâmico -->
      <div v-else class="absolute inset-0 w-full h-full">
        <transition-group name="fade" tag="div" class="relative w-full h-full">
            <div v-for="(slide, index) in destaqueSlider" :key="slide.id" v-show="currentSlide === index" class="absolute inset-0 w-full h-full">
                <div class="absolute inset-0">
                    <img
                        :src="resolveMediaUrl(slide.imagem_capa_url)"
                        @error="onImageError"
                        :alt="slide.titulo"
                        class="w-full h-full object-cover object-center transition-transform duration-[10000ms] ease-linear transform scale-100"
                        :class="{ 'scale-110': currentSlide === index }"
                    />
                    <div class="absolute inset-0 bg-gradient-to-t from-blue-900/95 via-blue-900/45 to-black/25"></div>
                </div>

                <div class="container mx-auto px-4 h-full flex flex-col justify-end pb-16 sm:pb-24 lg:pb-32 relative z-20 text-white pointer-events-none">
                    <div class="max-w-4xl pointer-events-auto animate-slide-up">
                        <span v-if="slide.categoria" class="inline-flex items-center bg-yellow-400 text-blue-900 text-[10px] sm:text-xs font-bold px-2.5 py-1 rounded-full uppercase mb-3 sm:mb-6 tracking-wider shadow-sm">
                            {{ slide.categoria.nome }}
                        </span>
                        <h1 class="text-[1.6rem] leading-tight sm:text-4xl lg:text-6xl font-extrabold mb-3 sm:mb-6 tracking-tight drop-shadow-xl line-clamp-3">
                            {{ slide.titulo }}
                        </h1>
                        <p class="text-gray-100 text-sm sm:text-lg lg:text-xl mb-5 sm:mb-8 leading-relaxed font-light line-clamp-2 max-w-3xl drop-shadow-lg hidden sm:block">
                            {{ getExcerpt(slide.conteudo) }}
                        </p>
                        <div class="flex flex-wrap gap-3">
                            <router-link
                                :to="{ name: 'PortalNoticiaDetalhe', params: { slug: slide.slug || slide.id } }"
                                class="bg-white/10 backdrop-blur-md hover:bg-white/20 text-white border border-white/30 px-5 py-2.5 sm:px-8 sm:py-3 rounded-full font-bold transition-all shadow-lg text-sm sm:text-lg inline-flex items-center gap-2 min-h-11"
                            >
                                <span>Ler notícia</span>
                                <ArrowRight :size="18" />
                            </router-link>
                        </div>
                    </div>
                </div>
            </div>
        </transition-group>

        <button
          v-if="destaqueSlider.length > 1"
          type="button"
          class="hidden sm:flex absolute left-3 lg:left-6 top-1/2 -translate-y-1/2 z-30 w-11 h-11 items-center justify-center rounded-full bg-black/30 hover:bg-black/50 text-white border border-white/20 backdrop-blur-sm"
          aria-label="Slide anterior"
          @click="prevSlide"
        >
          <ChevronLeft :size="22" />
        </button>
        <button
          v-if="destaqueSlider.length > 1"
          type="button"
          class="hidden sm:flex absolute right-3 lg:right-6 top-1/2 -translate-y-1/2 z-30 w-11 h-11 items-center justify-center rounded-full bg-black/30 hover:bg-black/50 text-white border border-white/20 backdrop-blur-sm"
          aria-label="Próximo slide"
          @click="goNextSlide"
        >
          <ChevronRight :size="22" />
        </button>

        <div class="absolute bottom-4 left-1/2 -translate-x-1/2 z-30 flex justify-center gap-2 px-4">
            <button
                v-for="(_, idx) in destaqueSlider"
                :key="idx"
                type="button"
                @click="setSlide(idx)"
                class="h-2.5 rounded-full transition-all duration-300 shadow-sm cursor-pointer border border-white/50 min-w-[1.5rem]"
                :class="currentSlide === idx ? 'bg-yellow-400 w-8 border-yellow-400' : 'bg-transparent w-2.5 hover:bg-white'"
                :aria-label="`Ir para destaque ${idx + 1}`"
            ></button>
        </div>
      </div>
    </div>

    <section class="bg-slate-950 text-white">
      <div class="container mx-auto px-4 py-10 sm:py-14">
        <p class="text-yellow-400 font-bold uppercase tracking-[0.2em] text-xs mb-2">Memória do Esporte</p>
        <h2 class="text-2xl sm:text-4xl font-extrabold max-w-3xl leading-tight">
          {{ memoriaDestaque?.titulo || 'A história do esporte da cidade, em um só lugar.' }}
        </h2>
        <p class="mt-3 text-slate-300 max-w-2xl">
          {{ memoriaDestaque?.resumo || 'Conquistas, ídolos e fotos que a cidade não pode esquecer.' }}
        </p>
        <div class="mt-6 flex flex-wrap gap-3">
          <router-link to="/memoria" class="bg-yellow-400 text-slate-900 px-5 py-3 rounded-full font-bold">Ver a linha do tempo</router-link>
          <router-link to="/memoria/enviar" class="border border-white/30 px-5 py-3 rounded-full font-bold hover:bg-white/10">Envie sua foto</router-link>
        </div>
      </div>
    </section>

    <!-- Seção de Notícias -->
    <section id="noticias" class="py-10 sm:py-16 bg-gray-50 scroll-mt-20">
      <div class="container mx-auto px-4">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end mb-8 sm:mb-10 gap-3">
          <div class="min-w-0">
             <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-1 sm:mb-2 flex items-center gap-2">
               <span class="w-2 h-7 sm:h-8 bg-blue-600 rounded-full shrink-0"></span>
               Últimas Notícias
             </h2>
             <p class="text-gray-500 text-base sm:text-lg">Fique por dentro do que acontece no esporte municipal</p>
          </div>
          <router-link to="/noticias" class="text-blue-600 font-bold hover:text-blue-800 inline-flex items-center gap-1 transition-colors group text-sm sm:text-base min-h-11">
            Ver todas <ArrowRight :size="16" class="group-hover:translate-x-1 transition-transform" />
          </router-link>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 lg:gap-8">
          <div class="lg:col-span-7 xl:col-span-8 min-w-0">
            <div v-if="loading" class="h-[16rem] sm:h-[24rem] lg:h-[500px] bg-gray-200 animate-pulse rounded-2xl"></div>
            <router-link
              v-else-if="destaqueNoticia"
              :to="{ name: 'PortalNoticiaDetalhe', params: { slug: destaqueNoticia.slug || destaqueNoticia.id } }"
              class="relative block min-h-[16rem] sm:min-h-[24rem] lg:h-[500px] rounded-2xl overflow-hidden shadow-xl group cursor-pointer transition-all hover:shadow-2xl"
            >
              <img :src="resolveMediaUrl(destaqueNoticia.imagem_capa_url)" @error="onImageError" class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" alt="" />
              <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent"></div>

              <div class="relative z-10 flex flex-col justify-end min-h-[16rem] sm:min-h-[24rem] lg:h-[500px] p-4 sm:p-6 md:p-8 text-white">
                 <div class="flex flex-wrap items-center gap-2 sm:gap-3 mb-3 sm:mb-4">
                   <span v-if="destaqueNoticia.categoria" class="bg-blue-600 text-white text-[10px] sm:text-xs font-bold px-2.5 py-1 rounded-full uppercase tracking-wide">
                     {{ destaqueNoticia.categoria.nome }}
                   </span>
                   <span class="flex items-center text-xs sm:text-sm text-gray-300 font-medium">
                     <Calendar :size="14" class="mr-1 shrink-0"/> {{ formatDate(destaqueNoticia.created_at) }}
                   </span>
                 </div>
                 <h3 class="text-xl sm:text-2xl md:text-4xl font-bold mb-2 sm:mb-4 leading-tight group-hover:text-blue-200 transition-colors line-clamp-3">
                   {{ destaqueNoticia.titulo }}
                 </h3>
                 <p class="text-gray-300 line-clamp-2 text-sm sm:text-base md:text-lg font-light mb-4 sm:mb-6 max-w-2xl hidden sm:block">
                   {{ getExcerpt(destaqueNoticia.conteudo) }}
                 </p>
                 <span class="inline-flex items-center text-white font-bold border-b-2 border-yellow-400 pb-1 group-hover:text-yellow-400 transition-colors text-sm sm:text-base w-fit">
                   Ler matéria completa <ArrowRight :size="16" class="ml-2" />
                 </span>
              </div>
            </router-link>
            <div v-else class="text-center py-16 sm:py-20 bg-white rounded-2xl shadow-sm border border-gray-100 text-gray-500 px-4">
               Nenhuma notícia recente encontrada.
            </div>
          </div>

          <div class="lg:col-span-5 xl:col-span-4 flex flex-col gap-4 sm:gap-6 min-w-0">
            <div v-if="loading" class="space-y-4">
               <div v-for="i in 3" :key="i" class="h-28 bg-gray-200 animate-pulse rounded-xl"></div>
            </div>
            <div v-else class="flex flex-col gap-4 sm:gap-6 h-full">
              <router-link
                v-for="noticia in listaNoticias"
                :key="noticia.id"
                :to="{ name: 'PortalNoticiaDetalhe', params: { slug: noticia.slug || noticia.id } }"
                class="flex-1 bg-white p-4 sm:p-5 rounded-2xl shadow-sm border border-gray-100 hover:shadow-lg transition-all group sm:hover:-translate-y-1 flex gap-3 sm:flex-col sm:justify-between min-w-0"
              >
                 <img
                   v-if="resolveMediaUrl(noticia.imagem_capa_url)"
                   :src="resolveMediaUrl(noticia.imagem_capa_url)"
                   @error="onImageError"
                   alt=""
                   class="w-20 h-20 sm:hidden rounded-xl object-cover shrink-0 bg-gray-100"
                 />
                 <div class="min-w-0 flex-1 flex flex-col">
                   <div class="flex justify-between items-start mb-2 sm:mb-3 gap-2">
                     <span v-if="noticia.categoria" class="text-[10px] sm:text-xs font-bold text-blue-600 bg-blue-50 px-2 py-1 rounded uppercase tracking-wide truncate">
                       {{ noticia.categoria.nome }}
                     </span>
                     <span class="text-[11px] sm:text-xs text-gray-400 shrink-0">
                        {{ formatDate(noticia.created_at) }}
                     </span>
                   </div>
                   <h4 class="font-bold text-gray-800 text-sm sm:text-lg mb-0 sm:mb-3 leading-snug group-hover:text-blue-700 transition-colors line-clamp-3">
                     {{ noticia.titulo }}
                   </h4>
                   <span class="hidden sm:flex text-sm font-bold text-gray-500 group-hover:text-blue-600 items-center gap-1 mt-auto pt-4 border-t border-gray-50">
                      Ler mais <ArrowRight :size="14" />
                   </span>
                 </div>
              </router-link>

              <router-link to="/noticias" class="bg-blue-50 p-4 sm:p-6 rounded-2xl border-2 border-dashed border-blue-200 text-center hover:bg-blue-100 hover:border-blue-300 transition-colors group flex items-center justify-center gap-2 text-blue-700 font-bold min-h-12" v-if="listaNoticias.length > 0">
                Ver arquivo de notícias <ArrowRight :size="18" class="group-hover:translate-x-1 transition-transform" />
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Stats Section -->
    <section class="bg-gradient-to-r from-blue-900 to-blue-800 py-12 sm:py-20 text-white relative overflow-hidden">
      <div class="absolute inset-0 opacity-10 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')]"></div>
      <div class="absolute top-0 left-0 w-64 h-64 bg-blue-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob"></div>
      <div class="absolute top-0 right-0 w-64 h-64 bg-yellow-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-2000"></div>
      <div class="absolute -bottom-8 left-20 w-64 h-64 bg-green-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-4000"></div>

      <div class="container mx-auto px-4 relative z-10">
        <div class="text-center mb-8 sm:mb-16">
          <h2 class="text-2xl sm:text-4xl lg:text-5xl font-bold mb-3 sm:mb-4 tracking-tight">Impacto no Esporte</h2>
          <div class="w-16 sm:w-24 h-1 bg-yellow-400 mx-auto rounded-full mb-4 sm:mb-6"></div>
          <p class="text-blue-100 text-sm sm:text-xl max-w-2xl mx-auto font-light px-1">
            Números que refletem nosso compromisso com o desenvolvimento esportivo e bem-estar da comunidade.
          </p>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-8">
          <div class="group relative p-3 sm:p-8 bg-white/5 rounded-2xl backdrop-blur-md border border-white/10 hover:bg-white/10 transition-all duration-300 sm:hover:-translate-y-2 hover:shadow-2xl flex flex-col items-center text-center min-w-0">
            <div class="mb-2 sm:mb-4 p-2.5 sm:p-4 bg-yellow-400/20 rounded-full text-yellow-400 group-hover:bg-yellow-400 group-hover:text-blue-900 transition-colors duration-300">
                <Trophy :size="28" class="w-6 h-6 sm:w-8 sm:h-8" />
            </div>
            <div class="text-2xl sm:text-5xl lg:text-6xl font-extrabold text-white mb-1 sm:mb-2 tracking-tight tabular-nums break-all">
                +{{ stats.campeonatos_realizados || 0 }}
            </div>
            <div class="text-[10px] sm:text-sm lg:text-base text-blue-200 font-medium uppercase tracking-wide sm:tracking-widest">Competições</div>
          </div>

          <div class="group relative p-3 sm:p-8 bg-white/5 rounded-2xl backdrop-blur-md border border-white/10 hover:bg-white/10 transition-all duration-300 sm:hover:-translate-y-2 hover:shadow-2xl flex flex-col items-center text-center min-w-0">
            <div class="mb-2 sm:mb-4 p-2.5 sm:p-4 bg-green-400/20 rounded-full text-green-400 group-hover:bg-green-400 group-hover:text-blue-900 transition-colors duration-300">
                <Users :size="28" class="w-6 h-6 sm:w-8 sm:h-8" />
            </div>
            <div class="text-2xl sm:text-5xl lg:text-6xl font-extrabold text-white mb-1 sm:mb-2 tracking-tight tabular-nums break-all">
                +{{ stats.atletas_cadastrados || 0 }}
            </div>
            <div class="text-[10px] sm:text-sm lg:text-base text-blue-200 font-medium uppercase tracking-wide sm:tracking-widest">Atletas</div>
          </div>

          <div class="group relative p-3 sm:p-8 bg-white/5 rounded-2xl backdrop-blur-md border border-white/10 hover:bg-white/10 transition-all duration-300 sm:hover:-translate-y-2 hover:shadow-2xl flex flex-col items-center text-center min-w-0">
             <div class="mb-2 sm:mb-4 p-2.5 sm:p-4 bg-blue-400/20 rounded-full text-blue-400 group-hover:bg-blue-400 group-hover:text-blue-900 transition-colors duration-300">
                <Shield :size="28" class="w-6 h-6 sm:w-8 sm:h-8" />
            </div>
            <div class="text-2xl sm:text-5xl lg:text-6xl font-extrabold text-white mb-1 sm:mb-2 tracking-tight tabular-nums break-all">
                {{ stats.equipes_ativas || 0 }}
            </div>
            <div class="text-[10px] sm:text-sm lg:text-base text-blue-200 font-medium uppercase tracking-wide sm:tracking-widest">Equipes</div>
          </div>

          <div class="group relative p-3 sm:p-8 bg-white/5 rounded-2xl backdrop-blur-md border border-white/10 hover:bg-white/10 transition-all duration-300 sm:hover:-translate-y-2 hover:shadow-2xl flex flex-col items-center text-center min-w-0">
             <div class="mb-2 sm:mb-4 p-2.5 sm:p-4 bg-red-400/20 rounded-full text-red-400 group-hover:bg-red-400 group-hover:text-blue-900 transition-colors duration-300">
                <Dumbbell :size="28" class="w-6 h-6 sm:w-8 sm:h-8" />
            </div>
            <div class="text-2xl sm:text-5xl lg:text-6xl font-extrabold text-white mb-1 sm:mb-2 tracking-tight tabular-nums break-all">
                {{ stats.modalidades || 0 }}
            </div>
            <div class="text-[10px] sm:text-sm lg:text-base text-blue-200 font-medium uppercase tracking-wide sm:tracking-widest">Modalidades</div>
          </div>
        </div>
      </div>
    </section>

    <!-- Próximos Eventos -->
    <section class="py-10 sm:py-16 bg-white" id="eventos">
      <div class="container mx-auto px-4">
         <div class="flex flex-col sm:flex-row sm:justify-between sm:items-end items-stretch mb-8 sm:mb-10 gap-4">
            <div class="min-w-0">
              <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-1 sm:mb-2 flex items-center gap-2">
                <span class="w-2 h-7 sm:h-8 bg-green-500 rounded-full shrink-0"></span>
                Calendário Esportivo
              </h2>
              <p class="text-gray-500 text-base sm:text-lg">Próximos jogos e competições oficiais</p>
            </div>
            <router-link to="/campeonatos" class="bg-white border border-gray-200 text-gray-700 px-5 py-3 rounded-xl font-bold hover:bg-gray-50 hover:border-gray-300 transition-all shadow-sm inline-flex items-center justify-center gap-2 min-h-11 w-full sm:w-auto">
              <Calendar :size="18" /> Ver agenda
            </router-link>
         </div>

         <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
            <div v-for="i in 4" :key="i" class="h-56 bg-gray-100 animate-pulse rounded-2xl"></div>
         </div>

         <div v-else-if="proximosEventos.length === 0" class="text-center py-12 sm:py-16 bg-gray-50 rounded-2xl border border-gray-100 text-gray-500 px-4">
            <div class="mb-4 bg-gray-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto text-gray-400">
              <Calendar :size="32" />
            </div>
            <h3 class="text-lg font-bold text-gray-700 mb-1">Nenhum evento agendado</h3>
            <p>Acompanhe em breve as novas datas.</p>
         </div>

         <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
           <div v-for="evento in proximosEventos.slice(0, 4)" :key="evento.id" class="bg-white rounded-2xl shadow-sm hover:shadow-xl transition-all group border border-gray-100 overflow-hidden flex flex-col h-full sm:hover:-translate-y-1 min-w-0">
             <div class="p-3 sm:p-4 border-b border-gray-100 bg-gray-50/70">
               <div class="flex items-center justify-between gap-2 min-w-0">
                 <span class="text-[10px] font-bold text-blue-700 bg-blue-100 px-2 py-1 rounded uppercase tracking-wide truncate min-w-0">
                   {{ evento.titulo || evento.campeonato }}
                 </span>
                 <span class="text-xs font-bold text-gray-700 bg-white px-2.5 py-1 rounded-md border border-gray-200 shrink-0">
                   {{ evento.hora }}
                 </span>
               </div>
             </div>

             <div class="p-4 sm:p-5 flex-1 flex flex-col">
               <div v-if="evento.tipo === 'partida'" class="flex items-start justify-between mb-4 gap-1">
                  <div class="flex-1 text-center min-w-0">
                    <div class="mb-1 flex justify-center">
                      <img
                        v-if="evento.mandante_logo_url"
                        :src="evento.mandante_logo_url"
                        :alt="evento.mandante_nome"
                        class="w-9 h-9 rounded-full object-contain bg-white border border-gray-200"
                      />
                      <div
                        v-else
                        class="w-9 h-9 rounded-full bg-gray-100 border border-gray-200 flex items-center justify-center text-[10px] font-bold text-gray-500 uppercase"
                      >
                        {{ getIniciais(evento.mandante_nome) }}
                      </div>
                    </div>
                    <div class="text-sm font-bold text-gray-800 leading-tight line-clamp-2" :title="evento.mandante_nome">{{ evento.mandante_nome }}</div>
                  </div>
                  <div class="px-1 pt-2 text-[11px] font-black text-gray-300 shrink-0">VS</div>
                  <div class="flex-1 text-center min-w-0">
                    <div class="mb-1 flex justify-center">
                      <img
                        v-if="evento.visitante_logo_url"
                        :src="evento.visitante_logo_url"
                        :alt="evento.visitante_nome"
                        class="w-9 h-9 rounded-full object-contain bg-white border border-gray-200"
                      />
                      <div
                        v-else
                        class="w-9 h-9 rounded-full bg-gray-100 border border-gray-200 flex items-center justify-center text-[10px] font-bold text-gray-500 uppercase"
                      >
                        {{ getIniciais(evento.visitante_nome) }}
                      </div>
                    </div>
                    <div class="text-sm font-bold text-gray-800 leading-tight line-clamp-2" :title="evento.visitante_nome">{{ evento.visitante_nome }}</div>
                  </div>
               </div>

               <div v-else class="mb-4">
                  <h3 class="font-bold text-gray-800 text-lg leading-tight mb-1 line-clamp-2">{{ evento.titulo }}</h3>
                  <p class="text-sm text-gray-500">{{ evento.subtitulo }}</p>
               </div>

               <div class="mt-auto space-y-3 pt-4 border-t border-gray-50">
                   <div class="flex items-center text-sm text-gray-500 gap-2 min-w-0">
                     <Calendar :size="16" class="text-blue-500 shrink-0"/> <span class="font-medium">{{ evento.data }}</span>
                   </div>
                   <div class="flex items-center text-sm text-gray-500 gap-2 min-w-0">
                     <MapPin :size="16" class="text-red-500 shrink-0"/> <span class="truncate">{{ evento.local }}</span>
                   </div>
               </div>
             </div>

             <router-link
                v-if="evento.tipo === 'partida'"
                :to="{ name: 'PortalPartida', params: { id: evento.id.replace('p_', '') } }"
                class="bg-gray-50 hover:bg-blue-600 hover:text-white text-center py-3 text-sm font-bold text-gray-600 transition-colors border-t border-gray-100 min-h-11 flex items-center justify-center"
             >
               Ver detalhes
             </router-link>
             <router-link
                v-else
                to="/campeonatos"
                class="bg-gray-50 hover:bg-green-600 hover:text-white text-center py-3 text-sm font-bold text-gray-600 transition-colors border-t border-gray-100 min-h-11 flex items-center justify-center"
             >
               Ver eventos
             </router-link>
           </div>
         </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
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
  MapPin,
  ChevronLeft,
  ChevronRight,
} from 'lucide-vue-next';

const { theme } = useTheme();
const stats = ref({});
const proximosEventos = ref([]);
const ultimasNoticias = ref([]);
const destaqueNoticia = ref(null);
const memoriaDestaque = ref(null);
const listaNoticias = ref([]);
const destaqueSlider = ref([]);
const currentSlide = ref(0);
let slideInterval = null;
const loading = ref(true);
const touchStartX = ref(0);

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

const prevSlide = () => {
    if (destaqueSlider.value.length > 0) {
        currentSlide.value = (currentSlide.value - 1 + destaqueSlider.value.length) % destaqueSlider.value.length;
        resetSlideInterval();
    }
};

const goNextSlide = () => {
    nextSlide();
    resetSlideInterval();
};

const onTouchStart = (event) => {
    touchStartX.value = event.changedTouches?.[0]?.screenX || 0;
};

const onTouchEnd = (event) => {
    if (!destaqueSlider.value.length) return;
    const endX = event.changedTouches?.[0]?.screenX || 0;
    const delta = endX - touchStartX.value;
    if (Math.abs(delta) < 40) return;
    if (delta < 0) goNextSlide();
    else prevSlide();
};

const resetSlideInterval = () => {
    if (slideInterval) clearInterval(slideInterval);
    if (destaqueSlider.value.length > 1) {
        slideInterval = setInterval(nextSlide, 8000);
    }
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

    const featuredNews = await portalService.getNoticias({ per_page: 5, destaque: true });

    if (featuredNews.data.data && featuredNews.data.data.length > 0) {
        destaqueSlider.value = featuredNews.data.data;
        resetSlideInterval();
    }

    const newsData = await portalService.getNoticias({ per_page: 5 });
    const allNews = newsData.data.data || [];

    if (allNews.length > 0) {
        destaqueNoticia.value = allNews[0];
        listaNoticias.value = allNews.slice(1, 4);
    }

    ultimasNoticias.value = allNews;

    try {
      const memoria = await portalService.getMemoria();
      memoriaDestaque.value = memoria.data?.neste_dia || (memoria.data?.destaques || [])[0] || null;
    } catch {
      memoriaDestaque.value = null;
    }
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
    if (!rankingService || typeof rankingService.getTemporadaAtiva !== 'function') {
        console.warn('Serviço de ranking não disponível ou incompleto.');
        return;
    }

    try {
        const { data: temporada } = await rankingService.getTemporadaAtiva();
        temporadaAtiva.value = temporada;
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

  try {
    const data = typeof content === 'string' ? JSON.parse(content) : content;

    if (data.blocks && Array.isArray(data.blocks)) {
      const paragraph = data.blocks.find(b => b.type === 'paragraph');
      if (paragraph && paragraph.data && paragraph.data.text) {
        const div = document.createElement('div');
        div.innerHTML = paragraph.data.text;
        return (div.textContent || div.innerText || '').trim();
      }
    }
  } catch (e) {
  }

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

onUnmounted(() => {
  if (slideInterval) clearInterval(slideInterval);
});
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 400ms var(--ease-out);
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.animate-fade-in-up {
  animation: fadeInUp 450ms var(--ease-out) both;
}
.animate-slide-up {
  animation: fadeInUp 400ms var(--ease-out) both;
}

@media (prefers-reduced-motion: reduce) {
  .animate-fade-in-up,
  .animate-slide-up {
    animation: none;
  }
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(12px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
