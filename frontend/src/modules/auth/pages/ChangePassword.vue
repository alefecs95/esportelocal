<template>
  <div class="w-full">
    <div class="text-center mb-10">
      <img v-if="tenantStore.logo" :src="tenantStore.logo" class="h-20 mx-auto mb-4 object-contain" alt="Logo" />
      <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ tenantStore.tenant?.nome || 'Alterar Senha' }}</h1>
      <p class="text-gray-500">Você precisa criar uma nova senha para continuar</p>
    </div>

    <form @submit.prevent="handleSubmit" class="space-y-6">
      <div v-if="error" class="bg-red-50 text-red-600 p-4 rounded-lg text-sm flex items-center gap-2">
        <AlertCircle :size="16" />
        {{ error }}
      </div>
      
      <div v-if="success" class="bg-green-50 text-green-600 p-4 rounded-lg text-sm flex items-center gap-2">
        <CheckCircle :size="16" />
        {{ success }}
      </div>

      <div>
        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Nova Senha</label>
        <div class="relative">
          <input
            id="password"
            v-model="form.password"
            :type="showPassword ? 'text' : 'password'"
            required
            class="w-full pl-10 pr-12 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition-colors"
            placeholder="Crie uma nova senha"
          />
          <Lock :size="20" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
          <button 
            type="button"
            @click="showPassword = !showPassword"
            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
          >
            <Eye v-if="!showPassword" :size="20" />
            <EyeOff v-else :size="20" />
          </button>
        </div>
      </div>

      <div>
        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">Confirmar Nova Senha</label>
        <div class="relative">
          <input
            id="password_confirmation"
            v-model="form.password_confirmation"
            :type="showPassword ? 'text' : 'password'"
            required
            class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition-colors"
            placeholder="Repita a nova senha"
          />
          <Lock :size="20" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
        </div>
      </div>

      <button
        type="submit"
        :disabled="loading"
        class="w-full bg-primary-600 text-white py-3 rounded-lg font-semibold hover:bg-primary-700 focus:ring-4 focus:ring-primary-100 transition-all flex items-center justify-center gap-2"
        :style="{ backgroundColor: tenantStore.primaryColor }"
      >
        <Loader2 v-if="loading" :size="20" class="animate-spin" />
        <span v-else>Alterar Senha</span>
      </button>
    </form>
    
    <div class="mt-8 text-center text-sm text-gray-500">
      <button @click="handleLogout" class="text-primary-600 font-medium hover:underline">Sair e tentar depois</button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import authService from '../services/authService';
import { useTenantStore } from '../../../stores/tenant';
import { Lock, Eye, EyeOff, Loader2, AlertCircle, CheckCircle } from 'lucide-vue-next';

const router = useRouter();
const tenantStore = useTenantStore();
const loading = ref(false);
const error = ref('');
const success = ref('');
const showPassword = ref(false);

const form = ref({
  password: '',
  password_confirmation: ''
});

const handleSubmit = async () => {
  if (form.value.password !== form.value.password_confirmation) {
    error.value = 'As senhas não conferem.';
    return;
  }

  if (form.value.password.length < 6) {
    error.value = 'A senha deve ter pelo menos 6 caracteres.';
    return;
  }

  loading.value = true;
  error.value = '';
  success.value = '';
  
  try {
    await authService.updatePassword(form.value);
    success.value = 'Senha alterada com sucesso! Redirecionando...';
    
    // Update local user data
    const user = JSON.parse(localStorage.getItem('user') || '{}');
    user.forcar_troca_senha = false;
    localStorage.setItem('user', JSON.stringify(user));
    
    setTimeout(() => {
        // Redirect based on profile
        if (user.perfil === 'SUPER_ADMIN') {
          router.push('/super-admin/dashboard');
        } else if (user.perfil === 'ADMIN') {
          router.push('/admin/dashboard');
        } else if (user.is_manager || user.perfil === 'RESPONSAVEL') {
          router.push('/manager');
        } else if (user.perfil === 'ATLETA') {
          router.push('/athlete/dashboard');
        } else if (user.perfil === 'ARBITRO') {
          router.push('/admin/arbitro/minhas-partidas');
        } else {
          router.push('/admin/dashboard');
        }
    }, 1500);

  } catch (e) {
    if (e.response?.data?.errors) {
      const errors = e.response.data.errors;
      error.value = Object.values(errors)[0][0];
    } else {
      error.value = 'Erro ao alterar senha. Tente novamente.';
    }
    console.error(e);
  } finally {
    loading.value = false;
  }
};

const handleLogout = async () => {
    try {
        await authService.logout();
    } catch (e) {
        console.error(e);
    } finally {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        router.push('/login');
    }
};
</script>
