<template>
  <div class="w-full">
    <div class="text-center mb-10">
      <h2 class="text-2xl lg:text-3xl font-bold text-gray-900 mb-2">Redefinir senha</h2>
      <p class="text-gray-500 text-sm lg:text-base">Crie sua nova senha para acessar a plataforma.</p>
    </div>

    <form @submit.prevent="handleSubmit" class="space-y-6">
      <div v-if="error" class="bg-red-50 text-red-600 p-4 rounded-xl text-sm border border-red-100">
        {{ error }}
      </div>
      <div v-if="success" class="bg-green-50 text-green-700 p-4 rounded-xl text-sm border border-green-100">
        {{ success }}
      </div>

      <div class="space-y-1">
        <label for="password" class="block text-sm font-medium text-gray-700 ml-1">Nova senha</label>
        <input
          id="password"
          v-model="form.password"
          type="password"
          required
          minlength="6"
          class="w-full px-4 py-3.5 border border-gray-200 rounded-xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 outline-none transition-all duration-300 bg-gray-50 focus:bg-white text-gray-800 placeholder-gray-400"
          placeholder="Digite a nova senha"
        />
      </div>

      <div class="space-y-1">
        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 ml-1">Confirmar senha</label>
        <input
          id="password_confirmation"
          v-model="form.password_confirmation"
          type="password"
          required
          minlength="6"
          class="w-full px-4 py-3.5 border border-gray-200 rounded-xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 outline-none transition-all duration-300 bg-gray-50 focus:bg-white text-gray-800 placeholder-gray-400"
          placeholder="Confirme a nova senha"
        />
      </div>

      <button
        type="submit"
        :disabled="loading"
        class="w-full bg-blue-600 hover:bg-blue-700 text-white py-4 rounded-xl font-bold shadow-lg transition-all duration-300 disabled:opacity-70"
      >
        <span v-if="loading">Salvando...</span>
        <span v-else>Redefinir senha</span>
      </button>
    </form>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import authService from '../services/authService';

const route = useRoute();
const router = useRouter();
const loading = ref(false);
const error = ref('');
const success = ref('');

const form = ref({
  password: '',
  password_confirmation: ''
});

const handleSubmit = async () => {
  if (form.value.password !== form.value.password_confirmation) {
    error.value = 'As senhas não conferem.';
    return;
  }

  loading.value = true;
  error.value = '';
  success.value = '';

  try {
    const payload = {
      token: route.params.token,
      password: form.value.password,
      password_confirmation: form.value.password_confirmation
    };
    const { data } = await authService.resetPasswordByToken(payload);
    success.value = data?.message || 'Senha redefinida com sucesso.';
    setTimeout(() => router.push('/login'), 1400);
  } catch (e) {
    if (e.response?.data?.errors) {
      const errors = e.response.data.errors;
      error.value = Object.values(errors)[0][0];
    } else {
      error.value = e.response?.data?.message || 'Não foi possível redefinir a senha.';
    }
  } finally {
    loading.value = false;
  }
};
</script>
