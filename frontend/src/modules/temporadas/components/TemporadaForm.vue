<template>
  <form @submit.prevent="submit">
    <div class="mb-4">
      <label class="block text-gray-700 text-sm font-bold mb-2" for="nome">
        Nome da Temporada
      </label>
      <input 
        v-model="form.nome"
        id="nome" 
        type="text" 
        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
        :class="{ 'border-red-500': errors?.nome }"
        placeholder="Ex: Temporada 2026"
        required
      >
      <p v-if="errors?.nome" class="text-red-500 text-xs italic mt-1">{{ errors.nome[0] }}</p>
    </div>
    <div class="mb-6">
      <label class="block text-gray-700 text-sm font-bold mb-2" for="ano">
        Ano
      </label>
      <input 
        v-model="form.ano"
        id="ano" 
        type="number" 
        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
        :class="{ 'border-red-500': errors?.ano }"
        placeholder="2026"
        required
      >
      <p v-if="errors?.ano" class="text-red-500 text-xs italic mt-1">{{ errors.ano[0] }}</p>
    </div>
    <div class="flex items-center justify-between">
      <button 
        type="submit" 
        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
        :disabled="loading"
      >
        {{ loading ? 'Salvando...' : 'Salvar' }}
      </button>
      <button 
        type="button" 
        @click="$emit('cancel')"
        class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
      >
        Cancelar
      </button>
    </div>
  </form>
</template>

<script setup>
import { reactive, watch } from 'vue';

const props = defineProps({
  loading: Boolean,
  initialData: Object,
  errors: Object
});

const emit = defineEmits(['submit', 'cancel']);

const form = reactive({
  nome: '',
  ano: new Date().getFullYear() + 1
});

watch(() => props.initialData, (newVal) => {
  if (newVal) {
    form.nome = newVal.nome;
    form.ano = newVal.ano;
  }
}, { immediate: true });

const submit = () => {
  emit('submit', { ...form });
};
</script>
