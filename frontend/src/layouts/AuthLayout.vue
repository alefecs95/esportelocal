<template>
  <div class="min-h-dvh flex relative overflow-x-hidden">
    <!-- Mobile Background (Fixed) -->
    <div class="fixed inset-0 z-0 lg:hidden w-full h-full">
        <img 
            :src="tenantStore.fundo_login || 'https://images.unsplash.com/photo-1579952363873-27f3bade9f55?q=80&w=2000&auto=format&fit=crop'" 
            class="w-full h-full object-cover"
            alt="Background"
        />
        <div class="absolute inset-0 bg-gradient-to-b from-blue-900/90 via-blue-800/80 to-blue-900/90"></div>
        
        <!-- Padrão e Decorativos Mobile -->
        <div class="absolute inset-0 opacity-10 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')]"></div>
        <div class="absolute -top-20 -right-20 w-72 h-72 bg-yellow-400 rounded-full mix-blend-overlay filter blur-3xl opacity-20"></div>
        <div class="absolute bottom-0 left-0 w-full h-1/2 bg-gradient-to-t from-black/50 to-transparent"></div>
    </div>

    <!-- Lado Esquerdo - Imagem/Branding (Escondido em mobile) -->
    <div class="hidden lg:flex lg:w-1/2 relative bg-blue-900 items-center justify-center overflow-hidden">
       <!-- Imagem de fundo com overlay -->
       <div class="absolute inset-0 z-0">
         <img 
            :src="tenantStore.fundo_login || 'https://images.unsplash.com/photo-1579952363873-27f3bade9f55?q=80&w=2000&auto=format&fit=crop'" 
            class="w-full h-full object-cover opacity-40 mix-blend-overlay"
            alt="Background"
         />
         <div class="absolute inset-0 bg-gradient-to-br from-blue-900/90 to-blue-800/80"></div>
       </div>

       <!-- Conteúdo Branding -->
       <div class="relative z-10 text-white p-12 max-w-lg text-center animate-fade-in-up">
          <div class="mb-8 flex justify-center">
             <div class="w-28 h-28 bg-white/10 backdrop-blur-md rounded-3xl flex items-center justify-center border border-white/20 shadow-2xl rotate-3 hover:rotate-0 transition-transform duration-200 ease-out-strong">
                <img v-if="tenantStore.logo" :src="tenantStore.logo" class="w-20 h-20 object-contain drop-shadow-lg" alt="Logo" />
                <Trophy v-else :size="56" class="text-yellow-400" />
             </div>
          </div>
          <h1 class="text-4xl md:text-5xl font-extrabold mb-6 tracking-tight drop-shadow-md">
            {{ tenantStore.tenant?.nome || 'Gestão Esportiva' }}
          </h1>
          <p class="text-blue-100 text-lg font-light leading-relaxed max-w-md mx-auto">
             Plataforma oficial para gestão de campeonatos, atletas, equipes e eventos esportivos municipais.
          </p>
       </div>
       
       <!-- Elementos decorativos -->
       <div class="absolute top-0 left-0 w-64 h-64 bg-blue-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob"></div>
       <div class="absolute bottom-0 right-0 w-64 h-64 bg-yellow-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-2000"></div>
    </div>

    <!-- Lado Direito - Formulário -->
    <div class="w-full lg:w-1/2 flex items-center justify-center p-4 sm:p-12 relative z-10 lg:bg-white min-h-dvh pb-[max(1rem,env(safe-area-inset-bottom,0px))]">
      <div class="w-full max-w-md space-y-8 bg-white/95 backdrop-blur-md p-6 sm:p-8 rounded-3xl shadow-2xl border border-white/40 lg:bg-transparent lg:p-0 lg:shadow-none lg:border-none lg:backdrop-blur-none mx-auto">
        <router-view></router-view>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useTenantStore } from '../stores/tenant';
import { Trophy } from 'lucide-vue-next';

const tenantStore = useTenantStore();
</script>

<style scoped>
.animate-fade-in-up {
  animation: fadeInUp 400ms var(--ease-out);
}

@media (prefers-reduced-motion: reduce) {
  .animate-fade-in-up {
    animation: none;
  }
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

</style>
