<template>
  <div class="bg-gray-50 min-h-screen pb-12">
    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center min-h-[60vh]">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="container mx-auto px-4 py-12">
      <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-6 rounded-lg shadow-sm max-w-2xl mx-auto">
        <div class="flex items-center gap-3 mb-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
          <h3 class="font-bold text-lg">Não foi possível carregar a notícia</h3>
        </div>
        <p class="mb-4">{{ error }}</p>
        <router-link :to="{ name: 'PortalNoticias' }" class="inline-flex items-center text-red-700 hover:text-red-800 font-medium hover:underline">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          Voltar para lista de notícias
        </router-link>
      </div>
    </div>

    <div v-else-if="noticia" class="animate-fade-in">
      <!-- Hero / Header Section -->
      <header class="bg-white border-b border-gray-200">
        <div class="container mx-auto px-4 py-8 md:py-12 max-w-5xl">
          <!-- Breadcrumb / Category -->
          <div class="flex flex-wrap items-center gap-2 text-sm text-gray-500 mb-6">
            <router-link :to="{ name: 'PortalNoticias' }" class="hover:text-blue-600 transition-colors">Notícias</router-link>
            <span>/</span>
            <span v-if="noticia.categoria" class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full text-xs font-semibold uppercase tracking-wide">
                {{ noticia.categoria.nome }}
            </span>
            <span v-if="noticia.categoria">/</span>
            <span class="text-gray-900 font-medium truncate max-w-[200px]">{{ noticia.titulo }}</span>
          </div>

          <!-- Title -->
          <h1 class="headline-balance text-[1.45rem] sm:text-[1.9rem] lg:text-[2rem] font-bold text-gray-900 leading-[1.22] mb-5 max-w-3xl mx-auto text-center">
            {{ noticia.titulo }}
          </h1>

          <!-- Meta Info (Stats) -->
          <div class="flex flex-wrap items-start gap-5 text-gray-500 border-t border-gray-100 pt-5">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-sm">
                {{ getInitials(noticia.autor?.name) }}
              </div>
              <div class="flex flex-col">
                <span class="text-xs uppercase tracking-wider text-gray-400 font-semibold">Autor</span>
                <span class="font-medium text-gray-900">{{ noticia.autor?.name || 'Redação' }}</span>
              </div>
            </div>
            
            <div class="hidden md:block w-px h-10 bg-gray-200"></div>

            <div class="flex flex-col">
              <span class="text-xs uppercase tracking-wider text-gray-400 font-semibold">Publicado em</span>
              <span class="font-medium text-gray-900 flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                {{ formatDate(noticia.publicado_em || noticia.created_at) }}
                <span v-if="isUpdated(noticia)" class="ml-1 text-xs text-gray-400 italic">(Atualizado)</span>
              </span>
            </div>

            <div class="hidden md:block w-px h-10 bg-gray-200"></div>

            <!-- Reading Time & Views -->
            <div class="flex items-center gap-4">
                <div class="flex flex-col" title="Tempo de leitura estimado">
                    <span class="text-xs uppercase tracking-wider text-gray-400 font-semibold">Leitura</span>
                    <span class="font-medium text-gray-900 flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ noticia.reading_time }} min
                    </span>
                </div>
                
                <div class="flex flex-col" title="Visualizações">
                    <span class="text-xs uppercase tracking-wider text-gray-400 font-semibold">Visto</span>
                    <span class="font-medium text-gray-900 flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        {{ noticia.visualizacoes }}
                    </span>
                </div>
            </div>
          </div>
        </div>
      </header>

      <!-- Cover Image -->
      <div v-if="noticia.imagem_capa_url" class="w-full bg-gray-100">
        <div class="container mx-auto px-4 max-w-5xl -mt-0 md:-mt-0">
          <div class="relative w-full aspect-video md:aspect-[21/9] overflow-hidden rounded-none md:rounded-b-2xl shadow-sm group">
            <img 
              :src="noticia.imagem_capa_url" 
              class="w-full h-full object-cover transform transition-transform duration-700 group-hover:scale-105" 
              alt="Capa da notícia"
              @error="handleImageError"
            />
          </div>
        </div>
      </div>

      <!-- Main Content -->
      <main class="container mx-auto px-4 py-12 max-w-4xl">
        <article class="bg-white p-6 md:p-10 rounded-2xl shadow-sm border border-gray-100">
          <NewsRenderer 
            :content="noticia.conteudo" 
            :carousel-urls="noticia.carrossel_urls"
            :gallery-urls="noticia.galeria_urls"
            @open-lightbox="openLightbox"
          />

           <!-- Share Buttons -->
           <div class="mt-8 pt-8 border-t border-gray-100">
              <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">Compartilhar</h3>
              <div class="flex gap-2">
                  <a :href="`https://www.facebook.com/sharer/sharer.php?u=${sharePageUrl}`" target="_blank" class="bg-blue-600 text-white p-2 rounded-full hover:bg-blue-700 transition-colors" title="Facebook">
                      <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                  </a>
                  <a :href="`https://twitter.com/intent/tweet?text=${encodeURIComponent(noticia.titulo)}&url=${sharePageUrl}`" target="_blank" class="bg-sky-500 text-white p-2 rounded-full hover:bg-sky-600 transition-colors" title="Twitter">
                      <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>
                  </a>
                  <a :href="`https://api.whatsapp.com/send?text=${encodeURIComponent(whatsappShareText)}`" target="_blank" class="bg-green-500 text-white p-2 rounded-full hover:bg-green-600 transition-colors" title="WhatsApp">
                      <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                  </a>
                  <button @click="copyLink" class="bg-gray-500 text-white p-2 rounded-full hover:bg-gray-600 transition-colors" title="Copiar Link">
                      <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                  </button>
              </div>
           </div>
        </article>

        <!-- Gallery Section (Fallback) -->
        <section v-if="!hasBlocksInContent && (noticia.carrossel_urls?.length || noticia.galeria_urls?.length)" class="mt-12 space-y-12">
            <!-- (Existing Gallery Code) -->
            <div v-if="noticia.carrossel_urls?.length && !hasCarouselBlock" class="bg-white p-6 md:p-8 rounded-2xl shadow-sm border border-gray-100">
                <h3 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Galeria de Destaques
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div v-for="(img, idx) in noticia.carrossel_urls" :key="`carrossel-${idx}`" class="group relative aspect-video overflow-hidden rounded-xl bg-gray-100 cursor-zoom-in" @click="openLightbox(img)">
                        <img :src="img" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" loading="lazy" />
                         <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors flex items-center justify-center opacity-0 group-hover:opacity-100">
                            <span class="bg-white/90 text-gray-900 px-3 py-1 rounded-full text-xs font-bold shadow-sm backdrop-blur-sm">Ver imagem</span>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="noticia.galeria_urls?.length && !hasGalleryBlock" class="bg-white p-6 md:p-8 rounded-2xl shadow-sm border border-gray-100">
                <h3 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    Mais Fotos
                </h3>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                    <div v-for="(img, idx) in noticia.galeria_urls" :key="`galeria-${idx}`" class="group relative aspect-square overflow-hidden rounded-lg bg-gray-100 cursor-zoom-in" @click="openLightbox(img)">
                        <img :src="img" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" loading="lazy" />
                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors"></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Comments Section -->
        <section class="mt-16 bg-white p-6 md:p-10 rounded-2xl shadow-sm border border-gray-100">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Comentários</h2>
            
            <div v-if="!isAuthenticated" class="mb-8 p-4 rounded-xl border border-amber-200 bg-amber-50">
                <p class="text-sm text-amber-900 font-medium">
                    Para comentar nesta notícia, você precisa estar logado.
                </p>
                <button
                    @click="abrirModalLogin"
                    class="mt-3 inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 transition-colors"
                >
                    Fazer login para comentar
                </button>
            </div>

            <form v-else @submit.prevent="postComment" class="mb-8">
                <div class="mb-3 flex items-center justify-between rounded-lg border border-green-200 bg-green-50 px-3 py-2">
                    <span class="text-xs font-semibold text-green-800">
                        Logado como: {{ usuarioLogadoNome || 'Usuário autenticado' }}
                    </span>
                    <button type="button" @click="logoutComentario" class="text-xs font-semibold text-green-700 hover:text-green-900">
                        Trocar conta
                    </button>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Seu Comentário</label>
                    <textarea 
                        v-model="commentForm.conteudo" 
                        rows="3" 
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Deixe sua opinião..."
                        required
                    ></textarea>
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" :disabled="commentSubmitting" class="bg-blue-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-blue-700 transition-colors disabled:opacity-50">
                        {{ commentSubmitting ? 'Enviando...' : 'Enviar Comentário' }}
                    </button>
                </div>
            </form>

            <!-- Comments List -->
            <div v-if="comments.length" class="space-y-6">
                <div v-for="comment in comments" :key="comment.id" class="border-b border-gray-100 pb-6 last:border-0">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 font-bold text-xs">
                            {{ getInitials(comment.user?.name || comment.nome) }}
                        </div>
                        <div>
                            <span class="font-bold text-gray-900 block">{{ comment.user?.name || comment.nome }}</span>
                            <span class="text-xs text-gray-500">{{ formatDate(comment.created_at) }}</span>
                        </div>
                    </div>
                    <p class="text-gray-700 leading-relaxed">{{ comment.conteudo }}</p>
                </div>
            </div>
            <div v-else class="text-center py-8 text-gray-500 bg-gray-50 rounded-lg">
                Seja o primeiro a comentar!
            </div>
        </section>

        <!-- Related News -->
        <section v-if="noticia.related && noticia.related.length" class="mt-16">
            <div class="flex items-center justify-between mb-8">
                <h2 class="text-2xl font-bold text-gray-900">Notícias Relacionadas</h2>
                <router-link :to="{ name: 'PortalNoticias' }" class="text-blue-600 hover:text-blue-800 font-medium text-sm">Ver todas</router-link>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <router-link 
                    v-for="item in noticia.related" 
                    :key="item.id" 
                    :to="{ name: 'PortalNoticiaDetalhe', params: { slug: item.slug || item.id } }"
                    class="group bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow"
                >
                    <div class="aspect-video bg-gray-100 relative overflow-hidden">
                        <img v-if="item.imagem_capa_url" :src="item.imagem_capa_url" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" />
                        <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                    </div>
                    <div class="p-5">
                        <div class="text-xs text-gray-500 mb-2">{{ formatDate(item.publicado_em) }}</div>
                        <h3 class="font-bold text-gray-900 mb-2 line-clamp-2 group-hover:text-blue-600 transition-colors">{{ item.titulo }}</h3>
                    </div>
                </router-link>
            </div>
        </section>

        <!-- Navigation Footer -->
        <div class="mt-12 pt-8 border-t border-gray-200">
          <router-link 
            :to="{ name: 'PortalNoticias' }" 
            class="group inline-flex items-center gap-2 text-gray-600 hover:text-blue-600 font-medium transition-colors p-2 -ml-2 rounded-lg hover:bg-blue-50"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transform transition-transform group-hover:-translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Voltar para todas as notícias
          </router-link>
        </div>
      </main>

      <!-- Simple Lightbox -->
      <div v-if="lightboxOpen" class="fixed inset-0 z-50 bg-black/90 backdrop-blur-sm flex items-center justify-center p-4 animate-fade-in" @click="closeLightbox">
        <button class="absolute top-4 right-4 text-white/70 hover:text-white p-2" @click="closeLightbox">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
        <img :src="lightboxImage" class="max-w-full max-h-[90vh] object-contain rounded-lg shadow-2xl" @click.stop />
      </div>

      <div v-if="showLoginModal" class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4" @click.self="fecharModalLogin">
        <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl border border-gray-100 p-6">
          <h3 class="text-xl font-bold text-gray-900 mb-1">Entrar para comentar</h3>
          <p class="text-sm text-gray-500 mb-5">Faça login para liberar o envio de comentários.</p>

          <div v-if="loginError" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
            {{ loginError }}
          </div>

          <form @submit.prevent="fazerLoginNoModal" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">CPF ou Email</label>
              <input v-model="loginForm.login" type="text" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Senha</label>
              <input v-model="loginForm.password" type="password" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required />
            </div>
            <div class="flex items-center justify-end gap-2 pt-2">
              <button type="button" @click="fecharModalLogin" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
                Cancelar
              </button>
              <button type="submit" :disabled="loginLoading" class="px-4 py-2 rounded-lg bg-blue-600 text-white font-semibold hover:bg-blue-700 disabled:opacity-50">
                {{ loginLoading ? 'Entrando...' : 'Entrar' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch, reactive } from 'vue';
import { useRoute } from 'vue-router';
import portalService from '../services/portalService';
import NewsRenderer from '@/components/NewsRenderer.vue';
import { useTenantStore } from '@/stores/tenant';
import authService from '@/modules/auth/services/authService';

const route = useRoute();
const tenantStore = useTenantStore();
const noticia = ref(null);
const loading = ref(true);
const error = ref('');
const comments = ref([]);
const commentForm = reactive({ nome: '', email: '', conteudo: '' });
const commentSubmitting = ref(false);
const isAuthenticated = ref(false);
const showLoginModal = ref(false);
const loginLoading = ref(false);
const loginError = ref('');
const usuarioLogadoNome = ref('');
const loginForm = reactive({
  login: '',
  password: '',
});

// Lightbox state
const lightboxOpen = ref(false);
const lightboxImage = ref('');
const currentUrl = computed(() => window.location.href);
const shareBackendBaseUrl = computed(() => {
  const envApiUrl = import.meta.env.VITE_API_URL;
  if (envApiUrl && /^https?:\/\//i.test(envApiUrl)) {
    return envApiUrl.replace(/\/api\/v1\/?$/i, '');
  }
  return `${window.location.origin}/backend/public`;
});
const sharePageUrl = computed(() => {
  const slug = noticia.value?.slug || route.params.slug || noticia.value?.id;
  return `${shareBackendBaseUrl.value}/share/noticias/${slug}`;
});
const tenantName = computed(() => tenantStore.currentTenant?.nome || localStorage.getItem('tenant_name') || 'SEMEL - Esporte Local');
const whatsappShareText = computed(() => {
  if (!noticia.value?.titulo) return sharePageUrl.value;
  return `${tenantName.value} | ${noticia.value.titulo} ${sharePageUrl.value}`;
});

const openLightbox = (url) => {
  lightboxImage.value = url;
  lightboxOpen.value = true;
  document.body.style.overflow = 'hidden';
};

const closeLightbox = () => {
  lightboxOpen.value = false;
  lightboxImage.value = '';
  document.body.style.overflow = '';
};

const formatDate = (d) => {
  if (!d) return '';
  return new Date(d).toLocaleDateString('pt-BR', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const isUpdated = (n) => {
    if (!n.created_at || !n.updated_at) return false;
    const created = new Date(n.created_at);
    const updated = new Date(n.updated_at);
    // If updated more than 1 hour after creation
    return (updated - created) > 3600000; 
};

const getInitials = (name) => {
  if (!name) return 'A';
  return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
};

const handleImageError = (e) => {
  if (!e?.target) return;
  e.target.style.display = 'none';
};

const copyLink = () => {
    navigator.clipboard.writeText(window.location.href);
    alert('Link copiado!');
};

const atualizarEstadoAuth = () => {
  const token = localStorage.getItem('token');
  const userRaw = localStorage.getItem('user');
  isAuthenticated.value = Boolean(token && userRaw);
  if (userRaw) {
    try {
      const user = JSON.parse(userRaw);
      usuarioLogadoNome.value = user?.name || '';
    } catch {
      usuarioLogadoNome.value = '';
    }
  } else {
    usuarioLogadoNome.value = '';
  }
};

const logoutComentario = () => {
  localStorage.removeItem('token');
  localStorage.removeItem('user');
  atualizarEstadoAuth();
  abrirModalLogin();
};

const abrirModalLogin = () => {
  loginError.value = '';
  showLoginModal.value = true;
};

const fecharModalLogin = () => {
  showLoginModal.value = false;
  loginError.value = '';
};

const fazerLoginNoModal = async () => {
  loginLoading.value = true;
  loginError.value = '';
  try {
    const { data } = await authService.login({
      login: loginForm.login,
      password: loginForm.password,
    });
    localStorage.setItem('token', data.access_token);
    localStorage.setItem('user', JSON.stringify(data.user));
    atualizarEstadoAuth();
    showLoginModal.value = false;
  } catch (e) {
    if (e.response?.status === 401) {
      loginError.value = 'Credenciais inválidas. Verifique CPF/Email e senha.';
    } else if (e.response?.status === 403) {
      loginError.value = 'Conta inativa. Entre em contato com o administrador.';
    } else {
      loginError.value = 'Não foi possível fazer login agora.';
    }
  } finally {
    loginLoading.value = false;
  }
};

const loadComments = async (id) => {
    try {
        const { data } = await portalService.getComentarios(id);
        comments.value = data.data || [];
    } catch (e) {
        console.error('Erro ao carregar comentários', e);
    }
};

const postComment = async () => {
    if (!isAuthenticated.value) {
        abrirModalLogin();
        return;
    }
    commentSubmitting.value = true;
    try {
        const { data } = await portalService.postComentario(noticia.value.id, { conteudo: commentForm.conteudo });
        comments.value.unshift(data); // Add new comment to top
        commentForm.conteudo = '';
        alert('Comentário enviado com sucesso!');
    } catch (e) {
        if (e.response?.status === 401) {
            abrirModalLogin();
            alert('Você precisa estar logado para comentar.');
        } else {
            alert('Erro ao enviar comentário.');
        }
    } finally {
        commentSubmitting.value = false;
    }
};

const load = async () => {
  loading.value = true;
  error.value = '';
  try {
    const noticiaIdentifier = route.params.slug || route.params.id;
    const { data } = await portalService.getNoticia(noticiaIdentifier);
    noticia.value = data;
    
    // SEO / Meta Tags (Basic implementation, ideally use useHead)
    document.title = `${data.titulo} | ${tenantName.value}`;
    // In a real SSR app, these would be server-side. SPA only updates client-side.
    
    loadComments(data.id);
  } catch (e) {
    error.value = 'Não foi possível carregar a notícia.';
    console.error(e);
  } finally {
    loading.value = false;
  }
};

const getBlocks = (content) => {
    if (!content) return [];
    try {
        return typeof content === 'string' ? JSON.parse(content).blocks : content.blocks;
    } catch {
        return [];
    }
};

const hasCarouselBlock = computed(() => {
    if (!noticia.value) return false;
    const blocks = getBlocks(noticia.value.conteudo);
    return blocks && blocks.some(b => b.type === 'carousel');
});

const hasGalleryBlock = computed(() => {
    if (!noticia.value) return false;
    const blocks = getBlocks(noticia.value.conteudo);
    return blocks && blocks.some(b => b.type === 'gallery');
});

const hasBlocksInContent = computed(() => {
    return hasCarouselBlock.value && hasGalleryBlock.value;
});

watch(() => route.params.slug || route.params.id, () => {
    load();
    window.scrollTo(0, 0);
});

onMounted(load);
onMounted(atualizarEstadoAuth);
</script>

<style scoped>
.animate-fade-in {
  animation: fadeIn 0.3s ease-in-out;
}

.headline-balance {
  text-wrap: balance;
  word-break: normal;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
