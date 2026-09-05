<template>
  <div class="max-w-lg mx-auto mt-16 bg-white border border-gray-100 rounded-2xl p-8 text-center">
    <p class="text-sm font-semibold uppercase tracking-wide text-amber-700">Acesso restrito</p>
    <h1 class="mt-2 text-2xl font-bold text-gray-900">Você não tem permissão para esta tela</h1>
    <p class="mt-3 text-sm text-gray-500">
      Esta área é da secretaria. Se precisar desse acesso, peça a um administrador da prefeitura.
    </p>
    <router-link
      :to="home"
      class="mt-6 inline-flex items-center justify-center min-h-11 px-4 py-2 rounded-lg bg-blue-600 text-white font-semibold hover:bg-blue-700"
    >
      Voltar para o meu painel
    </router-link>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const user = JSON.parse(localStorage.getItem('user') || '{}');
const home = computed(() => {
  if (user.perfil === 'ARBITRO') return '/admin/arbitro/minhas-partidas';
  if (user.perfil === 'REDATOR') return '/admin/noticias';
  if (user.perfil === 'RESPONSAVEL' || user.is_manager) return '/manager';
  if (user.perfil === 'ATLETA') return '/athlete/dashboard';
  return '/admin/dashboard';
});
</script>
