<template>
  <div class="space-y-6">
    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r-lg">
      <div class="flex">
        <div class="flex-shrink-0">
          <Info :size="20" class="text-blue-500" />
        </div>
        <div class="ml-3">
          <h3 class="text-sm font-medium text-blue-800">Bem-vindo à Área do Responsável</h3>
          <div class="mt-2 text-sm text-blue-700">
            <p>Aqui você pode gerenciar suas equipes, inscrever-se em campeonatos e adicionar atletas.</p>
          </div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <router-link to="/manager/competicoes" class="group">
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 hover:border-blue-500 hover:shadow-md transition-all h-full">
          <div class="flex items-center gap-4 mb-4">
            <div class="p-3 bg-blue-100 text-blue-600 rounded-lg group-hover:bg-blue-600 group-hover:text-white transition-colors">
              <Trophy :size="24" />
            </div>
            <h3 class="text-lg font-bold text-gray-800">Competições Disponíveis</h3>
          </div>
          <p class="text-gray-500 text-sm">Veja os campeonatos com inscrições abertas e registre sua equipe.</p>
        </div>
      </router-link>

      <router-link to="/manager/minhas-equipes" class="group">
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 hover:border-green-500 hover:shadow-md transition-all h-full">
          <div class="flex items-center gap-4 mb-4">
            <div class="p-3 bg-green-100 text-green-600 rounded-lg group-hover:bg-green-600 group-hover:text-white transition-colors">
              <Shield :size="24" />
            </div>
            <h3 class="text-lg font-bold text-gray-800">Minhas Equipes</h3>
          </div>
          <p class="text-gray-500 text-sm">Gerencie suas equipes, verifique status de aprovação e adicione jogadores.</p>
        </div>
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { Trophy, Shield, Info } from 'lucide-vue-next';
</script>
