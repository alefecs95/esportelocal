<template>
  <div class="w-full">
    <div class="text-center mb-10">
      <h2 class="text-2xl lg:text-3xl font-bold text-gray-900 mb-2">Recuperar senha</h2>
      <p class="text-gray-500 text-sm lg:text-base">
        Informe seu CPF ou email para receber o link no WhatsApp.
      </p>
    </div>

    <form @submit.prevent="handleSubmit" class="space-y-6">
      <div v-if="error" class="bg-red-50 text-red-600 p-4 rounded-xl text-sm border border-red-100">
        {{ error }}
      </div>
      <div v-if="success" class="bg-green-50 text-green-700 p-4 rounded-xl text-sm border border-green-100">
        {{ success }}
      </div>

      <div class="space-y-1">
        <label for="login" class="block text-sm font-medium text-gray-700 ml-1">CPF ou Email</label>
        <input
          id="login"
          v-model="form.login"
          type="text"
          required
          class="w-full px-4 py-3.5 border border-gray-200 rounded-xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 outline-none transition-all duration-300 bg-gray-50 focus:bg-white text-gray-800 placeholder-gray-400"
          placeholder="Digite seu CPF ou email"
        />
      </div>

      <button
        type="submit"
        :disabled="loading"
        class="w-full bg-blue-600 hover:bg-blue-700 text-white py-4 rounded-xl font-bold shadow-lg transition-all duration-300 disabled:opacity-70"
      >
        <span v-if="loading">Enviando...</span>
        <span v-else>Enviar link no WhatsApp</span>
      </button>
    </form>

    <div class="mt-8 pt-6 border-t border-gray-100 text-center text-sm text-gray-500">
      Lembrou da senha?
      <router-link to="/login" class="text-blue-600 font-bold hover:text-blue-800 transition-colors">
        Voltar ao login
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import authService from '../services/authService';

const loading = ref(false);
const error = ref('');
const success = ref('');

const form = ref({
  login: ''
});

const handleSubmit = async () => {
  loading.value = true;
  error.value = '';
  success.value = '';

  try {
    const { data } = await authService.forgotPasswordWhatsApp(form.value);
    success.value = data?.message || 'Se os dados estiverem corretos, enviaremos a recuperação via WhatsApp.';
  } catch (e) {
    if (e.response?.data?.errors) {
      const errors = e.response.data.errors;
      error.value = Object.values(errors)[0][0];
    } else {
      error.value = e.response?.data?.message || 'Não foi possível processar a recuperação agora.';
    }
  } finally {
    loading.value = false;
  }
};
</script>
