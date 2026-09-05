<template>
  <section class="relative overflow-hidden bg-slate-950 text-white">
    <img
      v-if="image"
      :src="image"
      class="absolute inset-0 w-full h-full object-cover opacity-35"
      :alt="imageAlt"
    />
    <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/80 to-slate-900/50"></div>
    <div class="container mx-auto px-4 relative z-10 py-10 sm:py-14">
      <p class="text-yellow-400 font-bold uppercase tracking-[0.2em] text-xs mb-2">Memória do Esporte</p>
      <h1 class="text-3xl sm:text-4xl font-extrabold leading-tight max-w-3xl">{{ title }}</h1>
      <p v-if="description" class="text-slate-300 mt-3 max-w-2xl text-base sm:text-lg">{{ description }}</p>
      <slot />
    </div>
  </section>
</template>

<script setup>
defineProps({
  title: { type: String, required: true },
  description: { type: String, default: '' },
  image: { type: String, default: '' },
  imageAlt: { type: String, default: '' },
});
</script>
