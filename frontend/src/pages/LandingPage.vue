<template>
  <div class="min-h-screen bg-slate-50 font-sans text-slate-900 selection:bg-blue-200 selection:text-blue-900 overflow-x-hidden">
    <!-- Navbar -->
    <header class="fixed top-0 w-full z-50 transition-all duration-300" :class="{ 'bg-white/90 backdrop-blur-xl border-b border-slate-200/50 shadow-sm py-2': scrolled, 'bg-transparent py-4': !scrolled }">
      <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
          <div class="flex items-center gap-3 group cursor-pointer" @click="scrollToTop">
            <img
              v-if="!logoError"
              :src="logoSrc"
              alt="Logo SGE Esporte Local"
              class="h-12 w-auto object-contain"
              @error="onLogoError"
            />
            <span v-else class="text-2xl font-black tracking-tight text-slate-900">ESPORTE<span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600"> LOCAL</span></span>
          </div>
          
          <nav class="hidden md:flex items-center gap-8">
            <a href="#funcionalidades" class="text-sm font-semibold text-slate-600 hover:text-blue-600 transition-colors">Funcionalidades</a>
            <a href="#beneficios" class="text-sm font-semibold text-slate-600 hover:text-blue-600 transition-colors">Benefícios</a>
            <a href="#depoimentos" class="text-sm font-semibold text-slate-600 hover:text-blue-600 transition-colors">Casos de Sucesso</a>
            <div class="h-6 w-px bg-slate-200"></div>
            <router-link to="/login" class="text-sm font-bold text-slate-700 hover:text-blue-600 transition-colors">Login</router-link>
            <a href="#contato" class="bg-slate-900 text-white px-6 py-2.5 rounded-full text-sm font-bold hover:bg-slate-800 transition-all shadow-lg shadow-slate-900/20 hover:shadow-slate-900/40 hover:-translate-y-0.5">Agendar Demo</a>
          </nav>

          <button class="md:hidden p-2 text-slate-600 hover:text-slate-900 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>
        </div>
      </div>
    </header>

    <!-- Hero Section -->
    <section class="relative pt-32 pb-20 md:pt-48 md:pb-32 overflow-hidden">
      <!-- Elementos de Background -->
      <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-[0.03] mix-blend-multiply"></div>
      <div class="absolute top-0 right-0 -mr-20 -mt-20 w-[800px] h-[800px] bg-gradient-to-b from-blue-100/50 to-indigo-100/50 rounded-full blur-3xl -z-10 animate-pulse" style="animation-duration: 8s;"></div>
      <div class="absolute bottom-0 left-0 -ml-40 -mb-40 w-[600px] h-[600px] bg-gradient-to-t from-purple-100/40 to-pink-100/40 rounded-full blur-3xl -z-10"></div>
      
      <div class="container mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white text-blue-700 text-sm font-bold mb-8 border border-blue-100 shadow-sm animate-fade-in-up">
          <span class="relative flex h-2.5 w-2.5">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-blue-500"></span>
          </span>
          A Revolução do Esporte Municipal
        </div>
        
        <h1 class="text-5xl md:text-7xl lg:text-8xl font-black tracking-tight text-slate-900 mb-8 leading-[1.1] animate-fade-in-up" style="animation-delay: 0.1s;">
          {{ settings.hero_title.split(' para a ')[0] }} para a<br>
          <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 relative inline-block">
            {{ settings.hero_title.split(' para a ')[1] || 'Gestão Esportiva' }}
            <!-- Decorative swoosh under text -->
            <svg class="absolute w-full h-4 -bottom-2 left-0 text-indigo-400 opacity-60" viewBox="0 0 100 10" preserveAspectRatio="none">
              <path d="M0 5 Q 50 10 100 0" stroke="currentColor" stroke-width="2" fill="none" />
            </svg>
          </span>
        </h1>
        
        <p class="text-xl md:text-2xl text-slate-600 max-w-3xl mx-auto mb-12 leading-relaxed font-medium animate-fade-in-up" style="animation-delay: 0.2s;">
          {{ settings.hero_subtitle }}
        </p>
        
        <div class="flex flex-col sm:flex-row items-center justify-center gap-5 animate-fade-in-up" style="animation-delay: 0.3s;">
          <a href="#contato" class="w-full sm:w-auto px-8 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-2xl font-bold text-lg hover:from-blue-700 hover:to-indigo-700 transition-all shadow-xl shadow-blue-600/30 hover:shadow-blue-600/50 hover:-translate-y-1 flex items-center justify-center gap-2">
            Quero uma Demonstração
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
          </a>
          <a href="#como-funciona" class="w-full sm:w-auto px-8 py-4 bg-white text-slate-700 border-2 border-slate-200 rounded-2xl font-bold text-lg hover:bg-slate-50 hover:border-slate-300 transition-all flex items-center justify-center gap-2 group">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-400 group-hover:text-blue-600 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Ver como funciona
          </a>
        </div>

        <!-- Dashboard Mockup -->
        <div class="mt-20 relative mx-auto max-w-5xl animate-fade-in-up perspective-1000" style="animation-delay: 0.4s;">
          <div class="absolute -inset-1 bg-gradient-to-r from-blue-500 to-purple-500 rounded-[2.5rem] blur-xl opacity-30 animate-pulse" style="animation-duration: 4s;"></div>
          <div class="relative rounded-3xl bg-slate-900 p-2 shadow-2xl ring-1 ring-white/10 transform-gpu rotate-x-2 hover:rotate-x-0 transition-transform duration-700 ease-out">
            <!-- Mac Window Controls -->
            <div class="flex items-center gap-2 px-4 py-3 border-b border-white/10 bg-slate-800/50 rounded-t-2xl">
              <div class="w-3 h-3 rounded-full bg-red-500"></div>
              <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
              <div class="w-3 h-3 rounded-full bg-green-500"></div>
              <div class="ml-4 text-xs text-slate-400 font-mono opacity-50">{{ adminPreviewHost }}</div>
            </div>
            <div class="rounded-b-2xl bg-slate-950 p-4 md:p-6">
              <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-2xl bg-slate-900 border border-slate-800 p-4">
                  <p class="text-xs uppercase tracking-wider text-slate-400">Atletas Ativos</p>
                  <p class="mt-2 text-2xl font-black text-white">{{ metricasDestaque.atletas }}</p>
                  <p class="text-xs text-emerald-400 mt-1">+22% nesta temporada</p>
                </div>
                <div class="rounded-2xl bg-slate-900 border border-slate-800 p-4">
                  <p class="text-xs uppercase tracking-wider text-slate-400">Campeonatos</p>
                  <p class="mt-2 text-2xl font-black text-white">{{ metricasDestaque.campeonatos }}</p>
                  <p class="text-xs text-blue-400 mt-1">Gestão centralizada</p>
                </div>
                <div class="rounded-2xl bg-slate-900 border border-slate-800 p-4">
                  <p class="text-xs uppercase tracking-wider text-slate-400">Partidas</p>
                  <p class="mt-2 text-2xl font-black text-white">{{ metricasDestaque.partidas }}</p>
                  <p class="text-xs text-indigo-400 mt-1">Sincronização ao vivo</p>
                </div>
              </div>
              <div class="mt-4 grid gap-4 lg:grid-cols-[2fr,1fr]">
                <div class="rounded-2xl bg-slate-900 border border-slate-800 p-4">
                  <div class="flex items-center justify-between mb-3">
                    <p class="text-sm font-bold text-white">Próximos Jogos</p>
                    <span class="text-xs text-slate-400">Hoje</span>
                  </div>
                  <div class="space-y-2">
                    <div
                      v-for="item in dashboardMiniCards"
                      :key="item.id"
                      class="flex items-center justify-between rounded-xl bg-slate-950 border border-slate-800 px-3 py-2"
                    >
                      <div>
                        <p class="text-xs text-slate-500">{{ item.categoria }}</p>
                        <p class="text-sm font-bold text-slate-200">{{ item.confronto }}</p>
                      </div>
                      <p class="text-xs text-blue-300 font-bold">{{ item.horario }}</p>
                    </div>
                  </div>
                </div>
                <div class="rounded-2xl bg-slate-900 border border-slate-800 p-4">
                  <p class="text-sm font-bold text-white mb-3">Top Modalidades</p>
                  <div class="space-y-3">
                    <div class="flex items-center justify-between">
                      <span class="text-xs text-slate-300">Futsal</span>
                      <span class="text-xs font-bold text-white">41%</span>
                    </div>
                    <div class="h-1.5 rounded-full bg-slate-800 overflow-hidden"><div class="h-full w-[41%] bg-blue-500"></div></div>
                    <div class="flex items-center justify-between">
                      <span class="text-xs text-slate-300">Vôlei</span>
                      <span class="text-xs font-bold text-white">29%</span>
                    </div>
                    <div class="h-1.5 rounded-full bg-slate-800 overflow-hidden"><div class="h-full w-[29%] bg-indigo-500"></div></div>
                    <div class="flex items-center justify-between">
                      <span class="text-xs text-slate-300">Basquete</span>
                      <span class="text-xs font-bold text-white">18%</span>
                    </div>
                    <div class="h-1.5 rounded-full bg-slate-800 overflow-hidden"><div class="h-full w-[18%] bg-purple-500"></div></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Logo Cloud -->
    <section class="py-12 border-y border-slate-200/60 bg-white">
      <div class="container mx-auto px-4">
        <p class="text-center text-sm font-bold text-slate-400 uppercase tracking-widest mb-8">Confiado por prefeituras inovadoras em todo o Brasil</p>
        <div class="flex flex-wrap justify-center items-stretch gap-4 md:gap-6">
          <div
            v-for="prefeitura in prefeiturasParceiras"
            :key="prefeitura.id || prefeitura.nome"
            class="w-[150px] h-20 border border-slate-200 rounded-2xl bg-slate-50/60 px-3 py-2 flex items-center justify-center hover:shadow-md transition-all"
            :title="prefeitura.nome"
          >
            <img
              v-if="prefeitura.logo_url"
              :src="prefeitura.logo_url"
              :alt="`Logo da ${prefeitura.nome}`"
              class="max-h-10 w-auto object-contain opacity-80 hover:opacity-100 transition-opacity"
              loading="lazy"
            />
            <div v-else class="w-10 h-10 rounded-xl bg-slate-900 text-white flex items-center justify-center text-xs font-black">
              {{ getPrefeituraSigla(prefeitura.nome) }}
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Problema vs Solução (Benefícios) -->
    <section id="beneficios" class="py-24 bg-slate-50 relative">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-6xl">
            <div class="text-center mb-16">
                <h2 class="text-blue-600 font-bold tracking-wide uppercase text-sm mb-3">O antes e o depois</h2>
                <h3 class="text-3xl md:text-5xl font-black text-slate-900 mb-6">{{ settings.section_benefits_title }}</h3>
                <p class="text-xl text-slate-600 max-w-2xl mx-auto">{{ settings.section_benefits_subtitle }}</p>
            </div>

            <div class="grid md:grid-cols-2 gap-8 items-center">
                <!-- Problema -->
                <div class="bg-white border border-red-100 p-8 md:p-10 rounded-3xl shadow-sm relative overflow-hidden group">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-red-50 rounded-full blur-3xl -mr-10 -mt-10 transition-transform group-hover:scale-150"></div>
                    <div class="flex items-center gap-4 mb-8 text-red-600 relative z-10">
                        <div class="w-12 h-12 bg-red-100 rounded-2xl flex items-center justify-center">
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                          </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-slate-900">O Passado</h3>
                    </div>
                    <ul class="space-y-5 relative z-10">
                        <li class="flex items-start gap-3 text-slate-600">
                            <div class="mt-1 bg-red-100 text-red-500 rounded-full p-1"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></div>
                            <span class="leading-relaxed">Fichas de inscrição impressas que somem, molham ou chegam rasgadas.</span>
                        </li>
                        <li class="flex items-start gap-3 text-slate-600">
                            <div class="mt-1 bg-red-100 text-red-500 rounded-full p-1"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></div>
                            <span class="leading-relaxed">Dias inteiros perdidos montando chaveamentos e tabelas no Excel.</span>
                        </li>
                        <li class="flex items-start gap-3 text-slate-600">
                            <div class="mt-1 bg-red-100 text-red-500 rounded-full p-1"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></div>
                            <span class="leading-relaxed">Atletas irregulares ou suspensos jogando por falta de conferência rápida.</span>
                        </li>
                        <li class="flex items-start gap-3 text-slate-600">
                            <div class="mt-1 bg-red-100 text-red-500 rounded-full p-1"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></div>
                            <span class="leading-relaxed">Telefone não para de tocar com equipes perguntando os horários dos jogos.</span>
                        </li>
                    </ul>
                </div>

                <!-- Solução -->
                <div class="bg-gradient-to-br from-slate-900 to-slate-800 text-white p-8 md:p-10 rounded-3xl shadow-2xl shadow-slate-900/30 transform md:-translate-y-4 relative overflow-hidden group">
                    <!-- Glow effect -->
                    <div class="absolute -top-24 -right-24 w-48 h-48 bg-blue-500 rounded-full blur-3xl opacity-20 group-hover:opacity-40 transition-opacity duration-500"></div>
                    
                    <div class="flex items-center gap-4 mb-8 relative z-10">
                        <div class="w-12 h-12 bg-blue-500/20 text-blue-400 rounded-2xl flex items-center justify-center border border-blue-500/30">
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                          </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-white">Com SEMEL.DIGITAL</h3>
                    </div>
                    <ul class="space-y-5 relative z-10">
                        <li class="flex items-start gap-3 text-slate-300 font-medium">
                            <div class="mt-1 bg-blue-500/20 text-blue-400 rounded-full p-1 border border-blue-500/30"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg></div>
                            <span class="leading-relaxed">Inscrições 100% online feitas pelos próprios dirigentes das equipes.</span>
                        </li>
                        <li class="flex items-start gap-3 text-slate-300 font-medium">
                            <div class="mt-1 bg-blue-500/20 text-blue-400 rounded-full p-1 border border-blue-500/30"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg></div>
                            <span class="leading-relaxed">Tabelas e chaveamentos gerados automaticamente com 1 clique.</span>
                        </li>
                        <li class="flex items-start gap-3 text-slate-300 font-medium">
                            <div class="mt-1 bg-blue-500/20 text-blue-400 rounded-full p-1 border border-blue-500/30"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg></div>
                            <span class="leading-relaxed">Súmula eletrônica com validação e bloqueio de suspensões na hora.</span>
                        </li>
                        <li class="flex items-start gap-3 text-slate-300 font-medium">
                            <div class="mt-1 bg-blue-500/20 text-blue-400 rounded-full p-1 border border-blue-500/30"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg></div>
                            <span class="leading-relaxed">Portal público 24h para a população acompanhar resultados ao vivo.</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Como Funciona (Passo a Passo) -->
    <section id="como-funciona" class="py-24 bg-white border-t border-slate-100">
      <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-16">
          <h2 class="text-blue-600 font-bold tracking-wide uppercase text-sm mb-3">Simples e Direto</h2>
          <h3 class="text-3xl md:text-5xl font-black text-slate-900 mb-6">{{ settings.section_how_it_works_title }}</h3>
        </div>

        <div class="grid md:grid-cols-3 gap-8 relative">
          <!-- Conector entre os passos (visível apenas em telas grandes) -->
          <div class="hidden md:block absolute top-12 left-[15%] right-[15%] h-0.5 bg-gradient-to-r from-blue-100 via-indigo-200 to-purple-100 -z-10"></div>

          <!-- Passo 1 -->
          <div class="text-center relative">
            <div class="w-24 h-24 mx-auto bg-white border-4 border-blue-50 text-blue-600 rounded-full flex items-center justify-center text-3xl font-black shadow-xl shadow-blue-100 mb-6 relative z-10">
              1
            </div>
            <h4 class="text-xl font-bold text-slate-900 mb-3">Configure seu Município</h4>
            <p class="text-slate-600">Personalize o portal com as cores, brasão e banners da sua prefeitura. Crie o primeiro campeonato (ex: Municipal de Futsal).</p>
          </div>

          <!-- Passo 2 -->
          <div class="text-center relative">
            <div class="w-24 h-24 mx-auto bg-white border-4 border-indigo-50 text-indigo-600 rounded-full flex items-center justify-center text-3xl font-black shadow-xl shadow-indigo-100 mb-6 relative z-10">
              2
            </div>
            <h4 class="text-xl font-bold text-slate-900 mb-3">Libere as Inscrições</h4>
            <p class="text-slate-600">Divulgue o link para os times. Eles mesmos cadastram os atletas pelo celular, enviando fotos e documentos, tirando o peso da sua equipe.</p>
          </div>

          <!-- Passo 3 -->
          <div class="text-center relative">
            <div class="w-24 h-24 mx-auto bg-white border-4 border-purple-50 text-purple-600 rounded-full flex items-center justify-center text-3xl font-black shadow-xl shadow-purple-100 mb-6 relative z-10">
              3
            </div>
            <h4 class="text-xl font-bold text-slate-900 mb-3">Gere a Tabela e Jogue</h4>
            <p class="text-slate-600">Feche as inscrições, aperte um botão para sortear as chaves e use o App de Súmula na quadra para atualizar o placar ao vivo no portal.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section id="funcionalidades" class="py-24 bg-slate-50 border-t border-slate-200/50">
      <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-20">
          <h2 class="text-blue-600 font-bold tracking-wide uppercase text-sm mb-3">Tudo em um só lugar</h2>
          <h3 class="text-3xl md:text-5xl font-black text-slate-900 mb-6">{{ settings.section_features_title }}</h3>
        </div>

        <div class="grid md:grid-cols-3 gap-8">
          <!-- Feature 1 -->
          <div class="bg-white p-8 rounded-3xl border border-slate-100 shadow-sm hover:shadow-xl hover:shadow-blue-900/5 transition-all duration-300 group">
            <div class="w-16 h-16 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-blue-600 group-hover:text-white transition-all duration-300 group-hover:-translate-y-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
              </svg>
            </div>
            <h4 class="text-xl font-bold text-slate-900 mb-3">Inscrições Descomplicadas</h4>
            <p class="text-slate-600 leading-relaxed">
              Gestores montam seus times online. O sistema valida idade, vínculo e bloqueia atletas suspensos automaticamente.
            </p>
          </div>

          <!-- Feature 2 -->
          <div class="bg-white p-8 rounded-3xl border border-slate-100 shadow-sm hover:shadow-xl hover:shadow-indigo-900/5 transition-all duration-300 group">
            <div class="w-16 h-16 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-indigo-600 group-hover:text-white transition-all duration-300 group-hover:-translate-y-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M3 14h18m-9-4v8m-7-6h14a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2v-8a2 2 0 012-2z" />
              </svg>
            </div>
            <h4 class="text-xl font-bold text-slate-900 mb-3">Motor de Chaveamento</h4>
            <p class="text-slate-600 leading-relaxed">
              Crie chaves de mata-mata ou fase de grupos com critérios de desempate customizáveis e sorteio automático.
            </p>
          </div>

          <!-- Feature 3 -->
          <div class="bg-white p-8 rounded-3xl border border-slate-100 shadow-sm hover:shadow-xl hover:shadow-purple-900/5 transition-all duration-300 group">
            <div class="w-16 h-16 bg-purple-50 text-purple-600 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-purple-600 group-hover:text-white transition-all duration-300 group-hover:-translate-y-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
              </svg>
            </div>
            <h4 class="text-xl font-bold text-slate-900 mb-3">Portal do Município</h4>
            <p class="text-slate-600 leading-relaxed">
              Um site com a identidade visual da sua cidade para publicar notícias, calendário de jogos e resultados ao vivo.
            </p>
          </div>
          
          <!-- Feature 4 -->
          <div class="bg-white p-8 rounded-3xl border border-slate-100 shadow-sm hover:shadow-xl hover:shadow-emerald-900/5 transition-all duration-300 group">
            <div class="w-16 h-16 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-emerald-600 group-hover:text-white transition-all duration-300 group-hover:-translate-y-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
            </div>
            <h4 class="text-xl font-bold text-slate-900 mb-3">Estatísticas e Ranking</h4>
            <p class="text-slate-600 leading-relaxed">
              Ranking municipal atualizado automaticamente, artilharia, cartões e melhores defesas em tempo real.
            </p>
          </div>

          <!-- Feature 5 -->
          <div class="bg-white p-8 rounded-3xl border border-slate-100 shadow-sm hover:shadow-xl hover:shadow-orange-900/5 transition-all duration-300 group">
            <div class="w-16 h-16 bg-orange-50 text-orange-600 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-orange-600 group-hover:text-white transition-all duration-300 group-hover:-translate-y-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
              </svg>
            </div>
            <h4 class="text-xl font-bold text-slate-900 mb-3">Notificações por WhatsApp</h4>
            <p class="text-slate-600 leading-relaxed">
              Atletas e dirigentes recebem avisos de aprovação, alteração de horário de jogos e punições direto no celular.
            </p>
          </div>

          <!-- Feature 6 -->
          <div class="bg-white p-8 rounded-3xl border border-slate-100 shadow-sm hover:shadow-xl hover:shadow-cyan-900/5 transition-all duration-300 group">
            <div class="w-16 h-16 bg-cyan-50 text-cyan-600 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-cyan-600 group-hover:text-white transition-all duration-300 group-hover:-translate-y-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4" />
              </svg>
            </div>
            <h4 class="text-xl font-bold text-slate-900 mb-3">Multimodalidades</h4>
            <p class="text-slate-600 leading-relaxed">
              Não só futebol. Gerencie vôlei, basquete, judô, corridas, xadrez e esportes individuais com regras específicas.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Interface Showcase / Súmula -->
    <section class="py-24 bg-slate-900 text-white overflow-hidden relative">
        <div class="absolute top-0 left-0 w-full h-px bg-gradient-to-r from-transparent via-blue-500/50 to-transparent"></div>
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="flex flex-col lg:flex-row items-center gap-16">
                <div class="lg:w-1/2">
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-blue-500/20 text-blue-400 text-xs font-bold mb-6 border border-blue-500/30 uppercase tracking-wider">
                      Exclusivo
                    </div>
                    <h2 class="text-3xl md:text-5xl font-black mb-6 leading-tight" v-html="settings.section_sumula_title"></h2>
                    <p class="text-slate-300 text-lg mb-8 leading-relaxed">
                        {{ settings.section_sumula_text }}
                    </p>
                    <ul class="space-y-4 mb-10">
                        <li class="flex items-center gap-3 bg-slate-800/50 p-3 rounded-xl border border-slate-700/50 hover:bg-slate-800 transition-colors">
                            <div class="w-8 h-8 rounded-full bg-blue-500/20 flex items-center justify-center text-blue-400 shrink-0"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg></div>
                            <span class="font-medium text-slate-200">Funciona off-line e sincroniza depois</span>
                        </li>
                        <li class="flex items-center gap-3 bg-slate-800/50 p-3 rounded-xl border border-slate-700/50 hover:bg-slate-800 transition-colors">
                            <div class="w-8 h-8 rounded-full bg-blue-500/20 flex items-center justify-center text-blue-400 shrink-0"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg></div>
                            <span class="font-medium text-slate-200">Gera PDF assinado digitalmente</span>
                        </li>
                        <li class="flex items-center gap-3 bg-slate-800/50 p-3 rounded-xl border border-slate-700/50 hover:bg-slate-800 transition-colors">
                            <div class="w-8 h-8 rounded-full bg-blue-500/20 flex items-center justify-center text-blue-400 shrink-0"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg></div>
                            <span class="font-medium text-slate-200">Cronômetro e timeline de eventos integrados</span>
                        </li>
                    </ul>
                    <router-link to="/sumula-eletronica" class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-500 text-white rounded-xl font-bold transition-all shadow-lg shadow-blue-600/20 hover:shadow-blue-600/40 hover:-translate-y-0.5">
                        Saber mais sobre a Súmula <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                    </router-link>
                </div>
                <div class="lg:w-1/2 relative">
                    <div class="absolute inset-0 bg-blue-500 rounded-full blur-[120px] opacity-30 animate-pulse"></div>
                    <div class="relative max-w-[320px] mx-auto group perspective-1000">
                      <div class="absolute inset-0 bg-gradient-to-t from-slate-900/80 to-transparent rounded-[2.5rem] z-20 pointer-events-none"></div>
                      <div class="relative z-10 rounded-[2.5rem] shadow-2xl border-[8px] border-slate-800 transform-gpu rotate-y-minus-10 rotate-x-5 group-hover:rotate-y-0 group-hover:rotate-x-0 transition-transform duration-700 ease-out overflow-hidden bg-slate-950">
                        <div class="h-8 bg-slate-900 flex items-center justify-center text-[10px] tracking-[0.25em] text-slate-400 font-bold">APP SÚMULA</div>
                        <div class="p-4 space-y-3">
                          <div class="rounded-xl bg-slate-900 border border-slate-800 p-3">
                            <p class="text-[10px] text-slate-500 uppercase tracking-widest">Partida em andamento</p>
                            <p class="text-sm font-black mt-1 text-white">Bela Vista 2 x 1 União FC</p>
                            <p class="text-[11px] text-blue-300 mt-1">2º tempo • 31'</p>
                          </div>
                          <div class="rounded-xl bg-slate-900 border border-slate-800 p-3">
                            <div class="flex items-center justify-between text-[11px] text-slate-300 mb-2">
                              <span>Eventos recentes</span>
                              <span class="text-emerald-300 font-bold">sincronizado</span>
                            </div>
                            <div
                              v-for="evento in sumulaDemoEventos"
                              :key="evento.id"
                              class="flex items-center justify-between py-1.5 border-b border-slate-800 last:border-0"
                            >
                              <div>
                                <p class="text-[11px] text-white font-semibold">{{ evento.titulo }}</p>
                                <p class="text-[10px] text-slate-500">{{ evento.jogador }}</p>
                              </div>
                              <span class="text-[10px] text-blue-300 font-bold">{{ evento.minuto }}</span>
                            </div>
                          </div>
                          <div class="grid grid-cols-2 gap-2">
                            <div class="rounded-lg bg-emerald-500/15 border border-emerald-500/30 p-2">
                              <p class="text-[10px] text-emerald-300">Gols</p>
                              <p class="text-sm font-black text-white">3</p>
                            </div>
                            <div class="rounded-lg bg-amber-500/15 border border-amber-500/30 p-2">
                              <p class="text-[10px] text-amber-300">Cartões</p>
                              <p class="text-sm font-black text-white">5</p>
                            </div>
                          </div>
                          <button class="w-full rounded-lg bg-blue-600 py-2 text-xs font-black text-white">Finalizar e Gerar PDF</button>
                        </div>
                      </div>
                      <!-- Decorative floating elements -->
                      <div class="absolute -right-10 top-20 bg-slate-800 p-4 rounded-2xl shadow-xl border border-slate-700 z-30 animate-bounce" style="animation-duration: 3s;">
                        <div class="flex items-center gap-3">
                          <div class="w-8 h-8 rounded-full bg-green-500/20 flex items-center justify-center text-green-400 text-xs font-bold">GO</div>
                          <div>
                            <div class="text-xs text-slate-400">Gol confirmado</div>
                            <div class="font-bold text-sm">Equipe A - 12'</div>
                          </div>
                        </div>
                      </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-16 bg-blue-600 text-white border-y border-blue-700 relative overflow-hidden">
      <!-- Background pattern -->
      <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 32px 32px;"></div>
      <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center mb-8">
          <p class="text-blue-200 font-bold uppercase tracking-widest text-xs">Resultados em números</p>
          <h3 class="text-3xl md:text-4xl font-black mt-2">Números que comprovam o impacto</h3>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6 text-center">
          <div class="px-4">
            <div class="rounded-2xl border border-blue-500/40 bg-blue-700/40 p-4 h-full">
              <div class="text-4xl md:text-5xl font-black mb-2 tracking-tight drop-shadow-md">{{ metricasDestaque.prefeituras }}</div>
              <div class="text-blue-200 font-bold text-sm uppercase tracking-wider">Prefeituras Ativas</div>
            </div>
          </div>
          <div class="px-4">
            <div class="rounded-2xl border border-blue-500/40 bg-blue-700/40 p-4 h-full">
              <div class="text-4xl md:text-5xl font-black mb-2 tracking-tight drop-shadow-md">{{ metricasDestaque.atletas }}</div>
              <div class="text-blue-200 font-bold text-sm uppercase tracking-wider">Atletas na Base</div>
            </div>
          </div>
          <div class="px-4">
            <div class="rounded-2xl border border-blue-500/40 bg-blue-700/40 p-4 h-full">
              <div class="text-4xl md:text-5xl font-black mb-2 tracking-tight drop-shadow-md">{{ metricasDestaque.partidas }}</div>
              <div class="text-blue-200 font-bold text-sm uppercase tracking-wider">Partidas Processadas</div>
            </div>
          </div>
          <div class="px-4">
            <div class="rounded-2xl border border-blue-500/40 bg-blue-700/40 p-4 h-full">
              <div class="text-4xl md:text-5xl font-black mb-2 tracking-tight drop-shadow-md">{{ settings.stats_papel }}</div>
              <div class="text-blue-200 font-bold text-sm uppercase tracking-wider">Papel Gasto</div>
            </div>
          </div>
        </div>
        <div class="mt-6 flex flex-wrap items-center justify-center gap-3 text-xs md:text-sm">
          <span class="rounded-full border border-blue-400/50 bg-blue-700/40 px-3 py-1.5">+ Transparência para a população</span>
          <span class="rounded-full border border-blue-400/50 bg-blue-700/40 px-3 py-1.5">+ Velocidade na organização</span>
          <span class="rounded-full border border-blue-400/50 bg-blue-700/40 px-3 py-1.5">+ Segurança jurídica para o município</span>
        </div>
      </div>
    </section>

    <!-- Depoimentos (Casos de Sucesso) -->
    <section id="depoimentos" class="py-24 bg-slate-50">
      <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-16">
          <h2 class="text-blue-600 font-bold tracking-wide uppercase text-sm mb-3">Casos de Sucesso</h2>
          <h3 class="text-3xl md:text-5xl font-black text-slate-900 mb-6">O que dizem os Secretários</h3>
        </div>

        <div class="grid md:grid-cols-3 gap-8">
          <!-- Card 1 -->
          <div class="bg-white p-8 rounded-3xl border border-slate-200 shadow-sm relative">
            <div class="text-yellow-400 flex gap-1 mb-6">
              <svg v-for="i in 5" :key="i" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
            </div>
            <p class="text-slate-700 text-lg mb-8 italic">"A organização do Campeonato Municipal de Futsal mudou da água pro vinho. Antes, tínhamos filas na secretaria e pilhas de papel. Agora, as equipes fazem tudo pelo celular."</p>
            <div class="flex items-center gap-4">
              <div class="w-12 h-12 rounded-full bg-slate-200 overflow-hidden">
                <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Felix" alt="Avatar" class="w-full h-full object-cover">
              </div>
              <div>
                <h4 class="font-bold text-slate-900">Carlos Silva</h4>
                <p class="text-sm text-slate-500">Sec. de Esportes - Interior/SP</p>
              </div>
            </div>
          </div>

          <!-- Card 2 -->
          <div class="bg-white p-8 rounded-3xl border border-slate-200 shadow-sm relative md:-translate-y-4">
            <div class="text-yellow-400 flex gap-1 mb-6">
              <svg v-for="i in 5" :key="i" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
            </div>
            <p class="text-slate-700 text-lg mb-8 italic">"A súmula eletrônica é fantástica. Acabou a discussão sobre quem fez gol ou quem tomou cartão. Tudo é atualizado na hora no Portal da Prefeitura e a população adora acompanhar."</p>
            <div class="flex items-center gap-4">
              <div class="w-12 h-12 rounded-full bg-slate-200 overflow-hidden">
                <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Maria" alt="Avatar" class="w-full h-full object-cover">
              </div>
              <div>
                <h4 class="font-bold text-slate-900">Mariana Costa</h4>
                <p class="text-sm text-slate-500">Diretora de Esportes - Sul/MG</p>
              </div>
            </div>
          </div>

          <!-- Card 3 -->
          <div class="bg-white p-8 rounded-3xl border border-slate-200 shadow-sm relative">
            <div class="text-yellow-400 flex gap-1 mb-6">
              <svg v-for="i in 5" :key="i" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
            </div>
            <p class="text-slate-700 text-lg mb-8 italic">"Saber que o sistema cruza os dados e não deixa um jogador suspenso ou fora da idade entrar em quadra nos dá muita segurança jurídica e evita recursos dos times."</p>
            <div class="flex items-center gap-4">
              <div class="w-12 h-12 rounded-full bg-slate-200 overflow-hidden">
                <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Roberto" alt="Avatar" class="w-full h-full object-cover">
              </div>
              <div>
                <h4 class="font-bold text-slate-900">Roberto Almeida</h4>
                <p class="text-sm text-slate-500">Sec. de Esportes - Norte/PR</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- FAQ Section -->
    <section class="py-24 bg-white border-t border-slate-100">
      <div class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-4xl">
        <div class="text-center mb-16">
          <h2 class="text-3xl md:text-4xl font-black text-slate-900 mb-4">Perguntas Frequentes</h2>
          <p class="text-lg text-slate-600">Tire suas dúvidas sobre a implantação do SEMEL.DIGITAL</p>
        </div>

        <div class="space-y-6">
          <div class="bg-slate-50 p-6 rounded-2xl border border-slate-100">
            <h4 class="text-xl font-bold text-slate-900 mb-2">Quanto tempo leva para implantar?</h4>
            <p class="text-slate-600">A implantação é muito rápida. Após a assinatura, entregamos o portal do município personalizado e a área administrativa em até 48 horas úteis. O treinamento com a equipe da secretaria é feito em seguida.</p>
          </div>
          
          <div class="bg-slate-50 p-6 rounded-2xl border border-slate-100">
            <h4 class="text-xl font-bold text-slate-900 mb-2">A prefeitura precisa de servidores ou infraestrutura própria?</h4>
            <p class="text-slate-600">Não. O SEMEL.DIGITAL é 100% na nuvem (Cloud). Nós cuidamos de toda a infraestrutura, backups diários e segurança dos dados. Você só precisa de internet e um navegador.</p>
          </div>

          <div class="bg-slate-50 p-6 rounded-2xl border border-slate-100">
            <h4 class="text-xl font-bold text-slate-900 mb-2">Como os atletas enviam seus documentos?</h4>
            <p class="text-slate-600">Cada atleta ou dirigente tem um login próprio. Eles mesmos fotografam o RG/CPF e comprovantes pelo celular e fazem o upload direto no sistema, sujeito à aprovação da secretaria.</p>
          </div>

          <div class="bg-slate-50 p-6 rounded-2xl border border-slate-100">
            <h4 class="text-xl font-bold text-slate-900 mb-2">Existe limite de campeonatos ou atletas?</h4>
            <p class="text-slate-600">Oferecemos planos flexíveis adequados ao tamanho do seu município. Desde cidades pequenas até grandes capitais, a plataforma escala conforme a sua necessidade sem travar.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Final -->
    <section id="contato" class="py-32 bg-slate-50 relative overflow-hidden border-t border-slate-200">
      <!-- Decorative background -->
      <div class="absolute top-0 right-0 w-1/2 h-full bg-blue-100 rounded-l-full blur-3xl opacity-50 -z-10"></div>
      <div class="absolute bottom-0 left-0 w-1/2 h-full bg-indigo-100 rounded-r-full blur-3xl opacity-50 -z-10"></div>
      
      <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="bg-white rounded-[2.5rem] shadow-2xl shadow-slate-200/50 p-8 md:p-16 text-center relative border border-slate-100 max-w-5xl mx-auto overflow-hidden">
          <!-- Inner decorative ring -->
          <div class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full h-full bg-gradient-to-b from-blue-50/50 to-transparent rounded-full pointer-events-none"></div>

          <div class="absolute -top-12 left-1/2 -translate-x-1/2 bg-gradient-to-br from-blue-600 to-indigo-600 w-24 h-24 rounded-3xl flex items-center justify-center shadow-xl shadow-blue-600/30 rotate-12 group hover:rotate-0 transition-transform duration-300">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-white -rotate-12 group-hover:rotate-0 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
          </div>
          
          <h2 class="text-3xl md:text-5xl font-black text-slate-900 mb-6 mt-12">Sua Secretaria no século 21</h2>
          <p class="text-slate-600 text-lg md:text-xl mb-12 max-w-2xl mx-auto font-medium">
            Não perca mais tempo com processos manuais. Preencha o formulário abaixo e nossa equipe entrará em contato para uma apresentação exclusiva.
          </p>
          
          <form class="max-w-xl mx-auto space-y-5" @submit.prevent="submitForm">
              <div class="flex flex-col sm:flex-row gap-5">
                <div class="flex-1 relative">
                  <input type="text" v-model="formContact.cidade" placeholder="Nome da Prefeitura/Cidade" class="w-full px-6 py-4 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all placeholder:text-slate-400" required />
                </div>
                <div class="flex-1 relative">
                  <input type="text" v-model="formContact.nome" placeholder="Seu Nome" class="w-full px-6 py-4 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all placeholder:text-slate-400" required />
                </div>
              </div>
              <div class="flex flex-col sm:flex-row gap-5">
                <div class="flex-1 relative">
                  <input type="email" v-model="formContact.email" placeholder="E-mail profissional" class="w-full px-6 py-4 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all placeholder:text-slate-400" required />
                </div>
                <div class="flex-1 relative">
                  <input type="tel" v-model="formContact.telefone" placeholder="Telefone/WhatsApp" class="w-full px-6 py-4 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all placeholder:text-slate-400" required />
                </div>
              </div>
              <button type="submit" :disabled="submitting" class="w-full py-4 mt-6 bg-gradient-to-r from-slate-900 to-slate-800 text-white rounded-xl font-bold text-lg hover:from-blue-600 hover:to-indigo-600 transition-all duration-300 shadow-xl shadow-slate-900/20 hover:shadow-blue-600/30 hover:-translate-y-1 flex items-center justify-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed">
                {{ submitting ? 'Enviando...' : 'Solicitar Apresentação' }}
                <svg v-if="!submitting" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
              </button>
              <p class="text-xs text-slate-400 mt-4 font-medium">Ao enviar, você concorda com nossos termos de uso e política de privacidade. Sem compromisso financeiro.</p>
          </form>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer class="bg-slate-900 text-slate-400 py-16 border-t border-slate-800">
      <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
          <div class="col-span-1 md:col-span-2">
            <div class="mb-6">
              <img
                v-if="!logoError"
                :src="logoSrc"
                alt="Logo SGE Esporte Local"
                class="h-16 w-auto object-contain brightness-110"
                @error="onLogoError"
              />
              <span v-else class="text-xl font-black text-white tracking-tight">ESPORTE<span class="text-blue-500"> LOCAL</span></span>
            </div>
            <p class="text-sm leading-relaxed max-w-sm mb-6">
              {{ settings.footer_about_text }}
            </p>
            <div class="flex gap-4">
              <a v-if="settings.social_instagram" :href="settings.social_instagram" target="_blank" class="w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center hover:bg-blue-600 hover:text-white transition-colors">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
              </a>
              <a v-if="settings.social_facebook" :href="settings.social_facebook" target="_blank" class="w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center hover:bg-blue-600 hover:text-white transition-colors">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/></svg>
              </a>
            </div>
          </div>
          
          <div>
            <h4 class="text-white font-bold mb-4 uppercase text-sm tracking-wider">Produto</h4>
            <ul class="space-y-3 text-sm">
              <li><a href="#funcionalidades" class="hover:text-blue-400 transition-colors">Funcionalidades</a></li>
              <li><a href="#como-funciona" class="hover:text-blue-400 transition-colors">Como Funciona</a></li>
              <li><a href="#" class="hover:text-blue-400 transition-colors">Portal do Município</a></li>
              <li><a href="#" class="hover:text-blue-400 transition-colors">Súmula Eletrônica</a></li>
            </ul>
          </div>
          
          <div>
            <h4 class="text-white font-bold mb-4 uppercase text-sm tracking-wider">Empresa</h4>
            <ul class="space-y-3 text-sm">
              <li><a href="#depoimentos" class="hover:text-blue-400 transition-colors">Casos de Sucesso</a></li>
              <li><a href="#contato" class="hover:text-blue-400 transition-colors">Contato</a></li>
              <li v-if="settings.contact_email"><a :href="'mailto:' + settings.contact_email" class="hover:text-blue-400 transition-colors">{{ settings.contact_email }}</a></li>
              <li v-if="settings.contact_phone"><a :href="'https://wa.me/55' + settings.contact_phone.replace(/\D/g, '')" target="_blank" class="hover:text-blue-400 transition-colors">{{ settings.contact_phone }}</a></li>
            </ul>
          </div>
        </div>
        
        <div class="pt-8 border-t border-slate-800 flex flex-col md:flex-row justify-between items-center gap-4 text-xs">
          <p>&copy; {{ new Date().getFullYear() }} SEMEL.DIGITAL Tecnologia. Todos os direitos reservados.</p>
          <p class="flex items-center gap-1">Feito com <span class="text-red-500">♥</span> para o esporte brasileiro.</p>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import api from '@/services/api';
import { getMainDomain } from '@/utils/domainHelpers';

const adminPreviewHost = computed(() => `admin.${getMainDomain()}`);

const scrolled = ref(false);
const submitting = ref(false);
const logoError = ref(false);
const logoSrc = '/logo-sge.png';
const formContact = ref({
  cidade: '',
  nome: '',
  email: '',
  telefone: ''
});

const settings = ref({
  hero_title: 'O sistema definitivo para a Gestão Esportiva',
  hero_subtitle: 'Digitalize sua Secretaria de Esportes. Elimine o papel, automatize tabelas de campeonatos e conecte a população com um portal exclusivo da sua cidade.',
  video_url: '',
  stats_cidades: '40+',
  stats_atletas: '150k',
  stats_jogos: '12k',
  stats_papel: 'Zero',
  section_benefits_title: 'A evolução da sua Secretaria',
  section_benefits_subtitle: 'Chega de perder noites organizando chaves e recebendo fichas de papel rasgadas. Eleve o nível do esporte na sua cidade.',
  section_how_it_works_title: 'Como transformar sua Secretaria em 3 passos',
  section_features_title: 'Recursos pensados para o esporte público',
  section_sumula_title: 'Aposente o papel com a Súmula Eletrônica',
  section_sumula_text: 'Árbitros e mesários preenchem tudo pelo celular ou tablet na beira da quadra. Gols, cartões e substituições são registrados em tempo real, atualizando automaticamente artilharia e tabelas no portal.',
  footer_about_text: 'Ajudando municípios brasileiros a transformar a gestão esportiva através da tecnologia, promovendo transparência e inclusão social.',
  contact_email: '',
  contact_phone: '',
  social_instagram: '',
  social_facebook: '',
  prefeituras_parceiras: [],
  metricas_ao_vivo: {
    prefeituras_ativas: 0,
    atletas_cadastrados: 0,
    campeonatos_geridos: 0,
    partidas_processadas: 0,
    equipes_ativas: 0
  }
});

const dashboardMiniCards = [
  { id: 1, categoria: 'Futsal Sub-17', confronto: 'Bela Vista x Novo Horizonte', horario: '19:30' },
  { id: 2, categoria: 'Futebol de Campo', confronto: 'União FC x Atlético Norte', horario: '20:40' },
  { id: 3, categoria: 'Vôlei Feminino', confronto: 'Equipe Azul x Equipe Central', horario: '21:15' }
];

const sumulaDemoEventos = [
  { id: 1, titulo: 'Gol', jogador: 'Pedro Santos (Bela Vista)', minuto: "26'" },
  { id: 2, titulo: 'Cartão Amarelo', jogador: 'João Silva (União FC)', minuto: "29'" },
  { id: 3, titulo: 'Substituição', jogador: 'Lucas entra / Carlos sai', minuto: "30'" }
];

const formatCompactNumber = (value) => {
  const numericValue = Number(value);
  if (!Number.isFinite(numericValue) || numericValue <= 0) return String(value || '0');
  if (numericValue >= 1000000) return `${(numericValue / 1000000).toFixed(1).replace('.0', '')}M+`;
  if (numericValue >= 1000) return `${(numericValue / 1000).toFixed(1).replace('.0', '')}k+`;
  return `${numericValue}+`;
};

const prefeiturasParceiras = computed(() => {
  if (Array.isArray(settings.value.prefeituras_parceiras) && settings.value.prefeituras_parceiras.length) {
    return settings.value.prefeituras_parceiras;
  }
  return [
    { id: 'p1', nome: 'Prefeitura Bela Vista', logo_url: null },
    { id: 'p2', nome: 'Prefeitura Novo Horizonte', logo_url: null },
    { id: 'p3', nome: 'Prefeitura União da Serra', logo_url: null },
    { id: 'p4', nome: 'Prefeitura Vale Verde', logo_url: null },
    { id: 'p5', nome: 'Prefeitura Santa Luzia', logo_url: null }
  ];
});

const metricasDestaque = computed(() => {
  const metricas = settings.value.metricas_ao_vivo || {};
  return {
    prefeituras: metricas.prefeituras_ativas ? formatCompactNumber(metricas.prefeituras_ativas) : settings.value.stats_cidades,
    atletas: metricas.atletas_cadastrados ? formatCompactNumber(metricas.atletas_cadastrados) : settings.value.stats_atletas,
    campeonatos: metricas.campeonatos_geridos ? formatCompactNumber(metricas.campeonatos_geridos) : settings.value.stats_jogos,
    partidas: metricas.partidas_processadas ? formatCompactNumber(metricas.partidas_processadas) : settings.value.stats_jogos
  };
});

const getPrefeituraSigla = (nome) => {
  if (!nome) return 'PM';
  return nome
    .replace('Prefeitura', '')
    .trim()
    .split(/\s+/)
    .slice(0, 2)
    .map((parte) => parte[0]?.toUpperCase() || '')
    .join('') || 'PM';
};

const loadSettings = async () => {
  try {
    const { data } = await api.get('/public/landing-page');
    if (data) {
      settings.value = data;
    }
  } catch (error) {
    console.error('Erro ao carregar configurações da Landing Page:', error);
  }
};

const handleScroll = () => {
  scrolled.value = window.scrollY > 20;
};

const scrollToTop = () => {
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const onLogoError = () => {
  logoError.value = true;
};

const submitForm = async () => {
  if (submitting.value) return;
  
  submitting.value = true;
  try {
    await api.post('/public/leads', formContact.value);
    alert('Formulário enviado com sucesso! Nossa equipe entrará em contato em breve.');
    formContact.value = { cidade: '', nome: '', email: '', telefone: '' };
  } catch (error) {
    console.error('Erro ao enviar formulário:', error);
    alert('Erro ao enviar formulário. Tente novamente mais tarde.');
  } finally {
    submitting.value = false;
  }
};

onMounted(() => {
  loadSettings();
  window.addEventListener('scroll', handleScroll);
});

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll);
});
</script>

<style scoped>
@keyframes fade-in-up {
  0% {
    opacity: 0;
    transform: translateY(20px);
  }
  100% {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fade-in-up {
  animation: fade-in-up 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
  opacity: 0;
}

.perspective-1000 {
  perspective: 1000px;
}

.rotate-x-2 {
  transform: rotateX(2deg);
}

.rotate-y-minus-10 {
  transform: rotateY(-10deg);
}

.rotate-x-5 {
  transform: rotateX(5deg);
}

html {
  scroll-behavior: smooth;
}
</style>
