<template>
  <div class="min-h-[70vh] bg-slate-50 flex items-center">
    <div class="container mx-auto px-4 py-16 max-w-xl text-center">
      <p class="text-yellow-600 font-bold uppercase tracking-[0.2em] text-xs">Memória do Esporte</p>
      <h1 class="text-3xl font-extrabold text-slate-900 mt-3">{{ title }}</h1>
      <p class="text-slate-600 mt-3 leading-relaxed">{{ description }}</p>
      <div class="mt-8 flex flex-wrap justify-center gap-3">
        <router-link to="/memoria" class="bg-slate-900 text-white px-5 py-3 rounded-full font-bold min-h-11">Ver linha do tempo</router-link>
        <router-link to="/memoria/galeria" class="border border-slate-300 px-5 py-3 rounded-full font-bold min-h-11">Ir à galeria</router-link>
        <router-link to="/memoria/mapa" class="border border-slate-300 px-5 py-3 rounded-full font-bold min-h-11">Mapa da Memória</router-link>
        <router-link to="/memoria/enviar" class="border border-slate-300 px-5 py-3 rounded-full font-bold min-h-11">Enviar uma memória</router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: { type: String, default: 'Ainda não encontramos este conteúdo' },
  description: { type: String, default: 'Pode ter sido removido, ainda não publicado, ou o endereço está incompleto. A história da cidade continua na linha do tempo e na galeria.' },
});
</script>
