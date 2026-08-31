<template>
  <div class="w-full">
    <div class="text-center mb-10">
      <!-- Logo visível apenas em mobile/tablet -->
      <div class="lg:hidden flex justify-center mb-6">
          <img v-if="tenantStore.logo" :src="tenantStore.logo" class="h-20 w-auto object-contain" alt="Logo" />
          <div v-else class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center text-blue-600">
             <Trophy :size="32" />
          </div>
      </div>
      
      <h2 class="text-2xl lg:text-3xl font-bold text-gray-900 mb-2">Bem-vindo de volta!</h2>
      <p class="text-gray-500 text-sm lg:text-base">Digite suas credenciais para acessar sua conta.</p>
    </div>

    <form @submit.prevent="handleLogin" class="space-y-6">
      <div v-if="error" class="bg-red-50 text-red-600 p-4 rounded-xl text-sm flex items-center gap-3 border border-red-100 animate-pulse">
        <AlertCircle :size="20" class="shrink-0" />
        {{ error }}
      </div>

      <div class="space-y-1">
        <label for="login" class="block text-sm font-medium text-gray-700 ml-1">CPF ou Email</label>
        <div class="relative group">
          <input
            id="login"
            v-model="form.login"
            type="text"
            required
            class="w-full pl-11 pr-4 py-3.5 border border-gray-200 rounded-xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 outline-none transition-all duration-300 bg-gray-50 focus:bg-white text-gray-800 placeholder-gray-400 group-hover:border-gray-300"
            placeholder="Digite seu CPF ou email"
          />
          <User :size="20" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 group-hover:text-blue-500 transition-colors" />
        </div>
      </div>

      <div class="space-y-1">
        <label for="password" class="block text-sm font-medium text-gray-700 ml-1">Senha</label>
        <div class="relative group">
          <input
            id="password"
            v-model="form.password"
            :type="showPassword ? 'text' : 'password'"
            required
            class="w-full pl-11 pr-12 py-3.5 border border-gray-200 rounded-xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 outline-none transition-all duration-300 bg-gray-50 focus:bg-white text-gray-800 placeholder-gray-400 group-hover:border-gray-300"
            placeholder="Sua senha"
          />
          <Lock :size="20" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 group-hover:text-blue-500 transition-colors" />
          <button 
            type="button"
            @click="showPassword = !showPassword"
            class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-blue-600 transition-colors p-1"
            tabindex="-1"
          >
            <Eye v-if="!showPassword" :size="20" />
            <EyeOff v-else :size="20" />
          </button>
        </div>
      </div>

      <div class="flex items-center justify-between text-sm">
        <label class="flex items-center gap-2 cursor-pointer group">
          <input type="checkbox" class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500 cursor-pointer">
          <span class="text-gray-500 group-hover:text-gray-700 transition-colors">Lembrar-me</span>
        </label>
        <router-link
          to="/login/forgot-password"
          class="text-blue-600 hover:text-blue-700 font-semibold hover:underline"
        >
          Esqueceu a senha?
        </router-link>
      </div>

      <button
        type="submit"
        :disabled="loading"
        class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white py-4 rounded-xl font-bold shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all duration-300 flex items-center justify-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed disabled:transform-none"
        :style="tenantStore.primaryColor ? { background: tenantStore.primaryColor } : {}"
      >
        <Loader2 v-if="loading" :size="22" class="animate-spin" />
        <span v-else class="text-base">Entrar na Plataforma</span>
      </button>
    </form>
    
    <div class="mt-8 pt-6 border-t border-gray-100 text-center text-sm text-gray-500">
      Ainda não é atleta cadastrado? <router-link to="/login/register" class="text-blue-600 font-bold hover:text-blue-800 transition-colors">Cadastre-se aqui</router-link>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import authService from '../services/authService';
import { useTenantStore } from '../../../stores/tenant';
import { User, Lock, Eye, EyeOff, Loader2, AlertCircle, Trophy } from 'lucide-vue-next';

const router = useRouter();
const tenantStore = useTenantStore();
const loading = ref(false);
const error = ref('');
const showPassword = ref(false);

const form = ref({
  login: '',
  password: ''
});

const handleLogin = async () => {
  loading.value = true;
  error.value = '';
  
  try {
    const { data } = await authService.login(form.value);
    
    // Save token
    localStorage.setItem('token', data.access_token);
    localStorage.setItem('user', JSON.stringify(data.user));
    
    // Check force password change
    if (data.user.forcar_troca_senha) {
      router.push({ name: 'ChangePassword' });
      return;
    }

    // Redirect based on profile/capabilities
    if (data.user.perfil === 'SUPER_ADMIN') {
      router.push('/super-admin/dashboard');
    } else if (data.user.perfil === 'ADMIN') {
      router.push('/admin/dashboard');
    } else if (data.user.perfil === 'REDATOR') {
      router.push('/admin/noticias');
    } else if (data.user.is_manager || data.user.perfil === 'RESPONSAVEL') {
      router.push('/manager');
    } else if (data.user.perfil === 'ATLETA') {
      router.push('/athlete/dashboard');
    } else if (data.user.perfil === 'ARBITRO') {
      router.push('/admin/arbitro/minhas-partidas');
    } else {
      router.push('/admin/dashboard');
    }
  } catch (e) {
    if (e.response?.status === 401) {
      error.value = 'Credenciais inválidas. Verifique seu login e senha.';
    } else if (e.response?.status === 403) {
      error.value = 'Conta inativa. Entre em contato com o administrador.';
    } else {
      error.value = 'Erro ao conectar com o servidor. Tente novamente.';
    }
    console.error(e);
  } finally {
    loading.value = false;
  }
};
</script>
