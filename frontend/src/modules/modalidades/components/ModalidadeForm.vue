<template>
  <form @submit.prevent="submit">
    <BaseInput
      id="nome"
      label="Nome"
      v-model="form.nome"
      required
      placeholder="Digite o nome da modalidade"
    />
    
    <div class="mb-4">
      <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
      <select
        v-model="form.tipo"
        class="block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
        required
      >
        <option value="individual">Individual</option>
        <option value="coletivo">Coletivo</option>
      </select>
    </div>

    <div class="mb-4">
      <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
      <textarea
        v-model="form.descricao"
        rows="3"
        class="block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
        placeholder="Descrição da modalidade (opcional)"
      ></textarea>
    </div>

    <!-- Configurações de Regras -->
    <div class="border-t pt-4 mt-4 mb-4">
      <h3 class="text-lg font-medium text-gray-900 mb-4">Configurações de Regras</h3>
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Sistema de Pontuação -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Sistema de Pontuação</label>
          <select
            v-model="form.regras_config.sistema_pontuacao"
            class="block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
          >
            <option value="gols">Gols (Futebol, Futsal, Handebol)</option>
            <option value="sets">Sets (Vôlei, Tênis)</option>
            <option value="pontos">Pontos (Basquete)</option>
            <option value="tempo">Tempo (Corrida, Natação, Ciclismo)</option>
            <option value="eliminatoria">Eliminatória (Lutas, Judô)</option>
            <option value="pontos_suico">Pontos Suíço (Xadrez)</option>
          </select>
        </div>

        <!-- Campos de Categoria -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Campos de Categoria Obrigatórios</label>
          <div class="space-y-2">
            <div class="flex items-center">
              <input type="checkbox" value="sexo" v-model="form.regras_config.campos_categoria" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
              <label class="ml-2 block text-sm text-gray-900">Sexo</label>
            </div>
            <div class="flex items-center">
              <input type="checkbox" value="idade" v-model="form.regras_config.campos_categoria" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
              <label class="ml-2 block text-sm text-gray-900">Idade</label>
            </div>
            <div class="flex items-center">
              <input type="checkbox" value="peso" v-model="form.regras_config.campos_categoria" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
              <label class="ml-2 block text-sm text-gray-900">Peso (Lutas)</label>
            </div>
            <div class="flex items-center">
              <input type="checkbox" value="distancia" v-model="form.regras_config.campos_categoria" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
              <label class="ml-2 block text-sm text-gray-900">Distância (Corridas)</label>
            </div>
             <div class="flex items-center">
              <input type="checkbox" value="faixa" v-model="form.regras_config.campos_categoria" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
              <label class="ml-2 block text-sm text-gray-900">Faixa (Artes Marciais)</label>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
        <!-- Requer Súmula -->
        <div class="flex items-center mb-4">
          <input
            id="requer_sumula"
            type="checkbox"
            v-model="form.regras_config.requer_sumula"
            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
          />
          <label for="requer_sumula" class="ml-2 block text-sm text-gray-900">
            Requer Súmula
          </label>
        </div>

        <!-- Permite Inscrição Avulsa -->
        <div class="flex items-center mb-4">
          <input
            id="permite_inscricao_avulsa"
            type="checkbox"
            v-model="form.regras_config.permite_inscricao_avulsa"
            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
          />
          <label for="permite_inscricao_avulsa" class="ml-2 block text-sm text-gray-900">
            Permite Inscrição Avulsa
          </label>
        </div>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
         <!-- Min Jogadores -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Mín. Jogadores (por time)</label>
          <input
            type="number"
            v-model="form.regras_config.min_jogadores"
            class="block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
          />
        </div>
         <!-- Max Jogadores -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Máx. Jogadores (por time)</label>
          <input
            type="number"
            v-model="form.regras_config.max_jogadores"
            class="block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
          />
        </div>
      </div>
    </div>

    <div class="flex items-center mb-4">
      <input
        id="status"
        type="checkbox"
        v-model="form.status"
        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
      />
      <label for="status" class="ml-2 block text-sm text-gray-900">
        Ativo
      </label>
    </div>

    <div class="flex justify-end space-x-3">
      <BaseButton type="button" variant="secondary" @click="$emit('cancel')">
        Cancelar
      </BaseButton>
      <BaseButton type="submit" variant="primary" :disabled="loading">
        {{ isEdit ? 'Atualizar' : 'Salvar' }}
      </BaseButton>
    </div>
  </form>
</template>

<script setup>
import { reactive, watch } from 'vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseButton from '@/components/ui/BaseButton.vue';

const props = defineProps({
  initialData: {
    type: Object,
    default: () => ({
      nome: '',
      tipo: 'individual',
      descricao: '',
      status: true,
      regras_config: {
        sistema_pontuacao: 'gols',
        campos_categoria: [],
        requer_sumula: false,
        permite_inscricao_avulsa: false,
        min_jogadores: null,
        max_jogadores: null,
      }
    }),
  },
  isEdit: {
    type: Boolean,
    default: false,
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['submit', 'cancel']);

const form = reactive({ 
  ...props.initialData,
  regras_config: props.initialData.regras_config || {
    sistema_pontuacao: 'gols',
    campos_categoria: [],
    requer_sumula: false,
    permite_inscricao_avulsa: false,
    min_jogadores: null,
    max_jogadores: null,
  }
});

watch(() => props.initialData, (newVal) => {
  const config = newVal.regras_config || {
    sistema_pontuacao: 'gols',
    campos_categoria: [],
    requer_sumula: false,
    permite_inscricao_avulsa: false,
    min_jogadores: null,
    max_jogadores: null,
  };
  
  Object.assign(form, {
    ...newVal,
    regras_config: config
  });
}, { deep: true });

const submit = () => {
  emit('submit', { ...form });
};
</script>
