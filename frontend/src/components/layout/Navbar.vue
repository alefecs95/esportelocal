<template>
  <header class="bg-white shadow h-16 flex items-center justify-between px-3 sm:px-6 fixed top-0 left-0 lg:left-64 right-0 z-20">
    <div class="flex items-center gap-2 min-w-0">
      <button @click="emit('toggle-sidebar')" class="lg:hidden p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100">
        <Menu :size="20" />
      </button>
      <div class="text-base sm:text-xl font-semibold text-gray-800 truncate">
        {{ title }}
      </div>
    </div>
    <div class="flex items-center gap-2 sm:gap-4">
      <div class="relative">
        <button
          type="button"
          class="relative p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100"
          title="Notificações da Memória"
          @click="toggleBell"
        >
          <Bell :size="20" />
          <span
            v-if="unread"
            class="absolute -top-0.5 -right-0.5 min-w-[1.1rem] h-4 px-1 rounded-full bg-red-600 text-white text-[10px] font-bold flex items-center justify-center"
          >
            {{ unread > 9 ? '9+' : unread }}
          </span>
        </button>
        <div
          v-if="bellOpen"
          class="absolute right-0 mt-2 w-80 max-w-[90vw] bg-white border border-gray-200 rounded-xl shadow-lg overflow-hidden"
        >
          <div class="px-3 py-2 border-b flex items-center justify-between">
            <p class="text-sm font-semibold">Memória do Esporte</p>
            <button v-if="unread" type="button" class="text-xs font-semibold text-blue-700" @click="marcarLidas">Marcar lidas</button>
          </div>
          <p v-if="!notices.length" class="px-3 py-6 text-sm text-gray-500 text-center">Nenhum envio recente da comunidade.</p>
          <ul v-else class="max-h-72 overflow-y-auto divide-y">
            <li v-for="item in notices" :key="item.id">
              <router-link :to="item.link || '/admin/memoria'" class="block px-3 py-2.5 hover:bg-gray-50" :class="item.lida ? 'opacity-70' : ''" @click="bellOpen = false">
                <p class="text-sm font-medium text-gray-900">{{ item.titulo }}</p>
                <p class="text-xs text-gray-500 mt-0.5">{{ item.mensagem }}</p>
              </router-link>
            </li>
          </ul>
        </div>
      </div>
      <div class="relative flex items-center gap-2 sm:gap-4">
        <button class="flex items-center space-x-2 text-gray-700 hover:text-gray-900 focus:outline-none">
          <img
            class="h-8 w-8 rounded-full object-cover"
            src="https://ui-avatars.com/api/?name=Admin+User&background=random"
            alt="User avatar"
          />
          <span class="font-medium hidden sm:inline">Admin</span>
        </button>
        
        <button 
          @click="handleLogout" 
          class="flex items-center text-red-500 hover:text-red-700 transition-colors"
          title="Sair do sistema"
        >
          <LogOut :size="20" />
          <span class="ml-2 text-sm font-medium hidden sm:inline">Sair</span>
        </button>
      </div>
    </div>
  </header>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { Bell, LogOut, Menu } from 'lucide-vue-next';
import authService from '../../modules/auth/services/authService';
import memoriaService from '../../modules/memoria/services/memoriaService';

const emit = defineEmits(['toggle-sidebar']);
const route = useRoute();
const router = useRouter();
const title = computed(() => route.meta.title || 'Dashboard');
const bellOpen = ref(false);
const unread = ref(0);
const notices = ref([]);
let pollId = null;

const loadNotices = async () => {
  try {
    const { data } = await memoriaService.notificacoes();
    unread.value = data.nao_lidas || 0;
    notices.value = data.items || [];
  } catch {
    unread.value = 0;
    notices.value = [];
  }
};

const toggleBell = async () => {
  bellOpen.value = !bellOpen.value;
  if (bellOpen.value) await loadNotices();
};

const marcarLidas = async () => {
  try {
    await memoriaService.lerNotificacoes();
    await loadNotices();
  } catch {
    /* ignore */
  }
};

const handleLogout = async () => {
  try {
    await authService.logout();
  } catch (error) {
    console.error('Erro ao fazer logout:', error);
  } finally {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    router.push('/login');
  }
};

onMounted(() => {
  loadNotices();
  pollId = setInterval(loadNotices, 60000);
});
onUnmounted(() => {
  if (pollId) clearInterval(pollId);
});
</script>
