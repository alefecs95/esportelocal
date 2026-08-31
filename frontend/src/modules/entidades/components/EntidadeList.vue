<template>
  <div class="overflow-x-auto rounded-lg shadow border border-gray-100">
    <table class="w-full bg-white text-sm text-left text-gray-500">
      <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b border-gray-100">
        <tr>
          <th scope="col" class="px-6 py-3 font-semibold">Nome</th>
          <th scope="col" class="px-6 py-3 font-semibold">Sigla</th>
          <th scope="col" class="px-6 py-3 font-semibold">Responsável</th>
          <th scope="col" class="px-6 py-3 font-semibold">Contato</th>
          <th scope="col" class="px-6 py-3 font-semibold">Sede</th>
          <th scope="col" class="px-6 py-3 font-semibold text-right">Ações</th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="entidades.length === 0">
          <td colspan="6" class="px-6 py-8 text-center text-gray-400">
            Nenhum clube cadastrado.
          </td>
        </tr>
        <tr 
          v-for="entidade in entidades" 
          :key="entidade.id" 
          class="bg-white border-b hover:bg-gray-50 transition-colors group"
        >
          <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-xs font-bold text-gray-500 uppercase overflow-hidden border border-gray-200">
                <img v-if="entidade.logo_url" :src="entidade.logo_url" :alt="entidade.nome" class="w-full h-full object-cover">
                <span v-else>{{ entidade.sigla || entidade.nome.substring(0,2) }}</span>
            </div>
            {{ entidade.nome }}
          </th>
          <td class="px-6 py-4">{{ entidade.sigla || '-' }}</td>
          <td class="px-6 py-4">{{ entidade.responsavel || '-' }}</td>
          <td class="px-6 py-4">{{ entidade.contato || '-' }}</td>
          <td class="px-6 py-4">
             <span v-if="entidade.cidade || entidade.bairro">
               {{ entidade.cidade }}<span v-if="entidade.cidade && entidade.bairro"> - </span>{{ entidade.bairro }}
             </span>
             <span v-else class="text-gray-300">-</span>
          </td>
          <td class="px-6 py-4 text-right flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
            <button 
              @click="$emit('edit', entidade)" 
              class="font-medium text-blue-600 hover:underline p-1"
              title="Editar"
            >
              ✏️
            </button>
            <button 
              @click="$emit('delete', entidade.id)" 
              class="font-medium text-red-600 hover:underline p-1"
              title="Excluir"
            >
              🗑
            </button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
defineProps({
  entidades: {
    type: Array,
    required: true,
    default: () => []
  }
});

defineEmits(['edit', 'delete']);
</script>
