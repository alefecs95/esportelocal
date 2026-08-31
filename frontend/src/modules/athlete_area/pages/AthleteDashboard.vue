<template>
  <div v-if="loading" class="flex justify-center items-center h-[calc(100vh-200px)]">
    <div class="flex flex-col items-center gap-2">
      <Loader2 class="animate-spin text-blue-600" :size="40" />
      <span class="text-gray-500 font-medium">Carregando suas informações...</span>
    </div>
  </div>

  <div v-else class="space-y-6">
    <!-- Welcome Section -->
    <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-blue-600">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Olá, {{ athlete?.nome || user?.name }}!</h1>
          <p class="text-gray-600 mt-1">Bem-vindo à sua área do atleta. Aqui você pode acompanhar seus jogos e resultados.</p>
        </div>
        <div class="hidden sm:block">
          <Trophy :size="48" class="text-blue-100" />
        </div>
      </div>
    </div>

    <div v-if="convites.length > 0" class="bg-white rounded-xl shadow-sm overflow-hidden border border-amber-200">
      <div class="bg-amber-50 px-6 py-4 border-b border-amber-100 flex items-center justify-between">
        <h3 class="font-semibold text-amber-900 flex items-center gap-2">
          <ClipboardList :size="20" class="text-amber-600" />
          Convites pendentes
        </h3>
        <span class="text-xs font-semibold bg-amber-100 text-amber-800 px-2 py-1 rounded-full">
          {{ convites.length }} pendente(s)
        </span>
      </div>
      <div class="divide-y divide-amber-100">
        <div v-for="convite in convites" :key="convite.inscricao_id" class="p-4 md:p-5 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
          <div>
            <p class="font-semibold text-gray-900">{{ convite.equipe_nome }}</p>
            <p class="text-sm text-gray-600">{{ convite.campeonato_nome }}</p>
            <p class="text-xs text-gray-500 mt-1">Convite enviado em {{ convite.data_convite }}</p>
          </div>
          <div class="flex items-center gap-2">
            <button
              @click="recusarConvite(convite)"
              class="px-3 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm font-medium"
            >
              Recusar
            </button>
            <button
              @click="abrirModalTermos(convite)"
              class="px-3 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 text-sm font-semibold"
            >
              Aceitar convite
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Profile Card -->
      <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex items-center justify-between">
          <h3 class="font-semibold text-gray-800 flex items-center gap-2">
            <User :size="20" class="text-blue-600" />
            Meus Dados
          </h3>
        </div>
        <div class="p-6 space-y-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold">
              {{ (athlete?.nome || user?.name || 'A').charAt(0).toUpperCase() }}
            </div>
            <div>
              <p class="text-sm text-gray-500">Nome Completo</p>
              <p class="font-medium text-gray-900">{{ athlete?.nome || user?.name }}</p>
            </div>
          </div>
          
          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-sm text-gray-500">CPF</p>
              <p class="font-medium text-gray-900">{{ athlete?.cpf || user?.cpf }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500">Nascimento</p>
              <p class="font-medium text-gray-900">{{ formatDate(athlete?.data_nascimento) || 'Não informado' }}</p>
            </div>
          </div>

          <div>
            <p class="text-sm text-gray-500">Email</p>
            <p class="font-medium text-gray-900">{{ athlete?.email || user?.email || 'Não informado' }}</p>
          </div>
          
          <div class="pt-2 flex flex-col gap-2">
             <router-link to="/athlete/profile" class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center gap-1">
                Editar Perfil / Documentos &rarr;
             </router-link>
             
             <button @click="showIDCard = true" class="text-green-600 hover:text-green-800 text-sm font-medium flex items-center gap-1">
                <CreditCard :size="16" />
                Minha Carteirinha
             </button>
          </div>
        </div>
      </div>

      <!-- Teams Card -->
      <div class="bg-white rounded-xl shadow-sm overflow-hidden lg:col-span-2">
        <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex items-center justify-between">
          <h3 class="font-semibold text-gray-800 flex items-center gap-2">
            <Users :size="20" class="text-green-600" />
            Minhas Equipes / Competições
          </h3>
          <span class="text-xs font-medium bg-green-100 text-green-700 px-2 py-1 rounded-full">
            {{ teams.length }} Ativa(s)
          </span>
        </div>
        
        <div v-if="teams.length === 0" class="p-8 text-center text-gray-500">
          <p>Você ainda não está vinculado a nenhuma equipe ou competição.</p>
        </div>

        <div v-else class="divide-y divide-gray-100">
          <div v-for="team in teams" :key="team.id" class="p-4 hover:bg-gray-50 transition-colors flex items-center justify-between">
            <div class="flex items-center gap-4">
              <div class="w-10 h-10 rounded-lg bg-green-50 flex items-center justify-center">
                <Shield :size="20" class="text-green-600" />
              </div>
              <div>
                <h4 class="font-medium text-gray-900">{{ team.nome }}</h4>
                <p class="text-sm text-gray-500">{{ team.campeonato?.nome || 'Competição sem nome' }}</p>
              </div>
            </div>
            <div class="text-right">
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                {{ team.campeonato?.modalidade?.nome || 'Geral' }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Upcoming Matches -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
      <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h3 class="font-semibold text-gray-800 flex items-center gap-2">
          <Calendar :size="20" class="text-purple-600" />
          Próximos Jogos
        </h3>
      </div>
      
      <div v-if="nextGames.length === 0" class="p-8 text-center text-gray-500">
        <p>Nenhum jogo agendado para os próximos dias.</p>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data/Hora</th>
              <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Confronto</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Local</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Campeonato</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="game in nextGames" :key="game.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ formatDateTime(game.data_jogo) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center justify-center gap-3">
                  <span class="font-medium text-gray-900">{{ game.mandante }}</span>
                  <span class="text-gray-400 text-xs font-bold">VS</span>
                  <span class="font-medium text-gray-900">{{ game.visitante }}</span>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ game.local || 'A definir' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ game.campeonato }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Recent Results -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
      <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h3 class="font-semibold text-gray-800 flex items-center gap-2">
          <ClipboardList :size="20" class="text-orange-600" />
          Últimos Resultados
        </h3>
      </div>
      
      <div v-if="results.length === 0" class="p-8 text-center text-gray-500">
        <p>Nenhum resultado recente encontrado.</p>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
              <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Placar</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Campeonato</th>
              <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="game in results" :key="game.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ formatDateTime(game.data_jogo) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center justify-center gap-3">
                  <span class="font-medium text-gray-900 w-1/3 text-right">{{ game.mandante }}</span>
                  <div class="bg-gray-100 px-3 py-1 rounded font-bold text-gray-800 whitespace-nowrap">
                    {{ game.placar_mandante }} x {{ game.placar_visitante }}
                  </div>
                  <span class="font-medium text-gray-900 w-1/3 text-left">{{ game.visitante }}</span>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ game.campeonato }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-center">
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                  Finalizado
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ID Card Modal -->
    <div v-if="showTermosModal" class="fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4" @click.self="fecharModalTermos">
      <div class="bg-white rounded-2xl p-5 max-w-2xl w-full relative flex flex-col gap-4">
        <button @click="fecharModalTermos" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
          <X :size="22" />
        </button>
        <h3 class="text-lg font-bold text-gray-900">Termos para aceitar convite da equipe</h3>
        <p class="text-sm text-gray-600">
          Leia os termos abaixo até o final. Após rolar todo o conteúdo, confirme o aceite para participar da equipe.
        </p>

        <div ref="termosScrollRef" @scroll="onTermosScroll" class="h-72 overflow-y-auto rounded-lg border border-gray-200 p-4 space-y-4 text-sm text-gray-700">
          <section>
            <h4 class="font-semibold text-gray-900 mb-1">Termo de uso de imagem e voz</h4>
            <p>
              Autorizo o uso da minha imagem, voz e nome em fotos, vídeos, transmissões e materiais promocionais
              relacionados ao campeonato {{ conviteSelecionado?.campeonato_nome || '' }}, sem ônus para a organização.
              Esta autorização é concedida para divulgação institucional, esportiva e jornalística.
            </p>
          </section>
          <section>
            <h4 class="font-semibold text-gray-900 mb-1">Regulamento da competição</h4>
            <p v-if="conviteSelecionado?.regulamento" class="whitespace-pre-line">
              {{ conviteSelecionado.regulamento }}
            </p>
            <p v-else>
              Declaro que li e estou de acordo com o regulamento oficial da competição, incluindo regras técnicas,
              disciplinares e administrativas aplicáveis ao atleta e à equipe.
            </p>
          </section>
        </div>

        <div class="rounded-lg bg-blue-50 border border-blue-100 p-3 text-xs text-blue-700">
          Role até o fim para liberar o aceite.
        </div>

        <label class="inline-flex items-start gap-2 text-sm text-gray-700">
          <input
            v-model="termosAceitos"
            type="checkbox"
            :disabled="!termosScrollCompleto"
            class="mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500 disabled:opacity-50"
          />
          <span>Li e aceito o termo de imagem e o regulamento da competição.</span>
        </label>

        <div class="flex justify-end gap-2">
          <button @click="fecharModalTermos" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
            Cancelar
          </button>
          <button
            @click="confirmarAceiteConvite"
            :disabled="respondendoConvite || !termosScrollCompleto || !termosAceitos"
            class="px-4 py-2 rounded-lg bg-emerald-600 text-white font-semibold hover:bg-emerald-700 disabled:opacity-50"
          >
            {{ respondendoConvite ? 'Confirmando...' : 'Confirmar participação' }}
          </button>
        </div>
      </div>
    </div>

    <!-- ID Card Modal -->
    <div v-if="showIDCard" class="fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4" @click.self="showIDCard = false">
      <div class="bg-white rounded-2xl p-6 max-w-sm w-full relative flex flex-col items-center gap-6">
        <button @click="showIDCard = false" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
          <X :size="24" />
        </button>
        
        <h3 class="text-xl font-bold text-gray-800">Carteirinha do Atleta</h3>
        
        <div class="transform scale-90 sm:scale-100 transition-transform">
          <AthleteIDCard :athlete="athlete" />
        </div>

        <button 
          @click="downloadCard" 
          :disabled="downloading"
          class="w-full bg-blue-600 text-white py-2.5 rounded-lg font-medium hover:bg-blue-700 flex items-center justify-center gap-2 disabled:opacity-50 transition-all"
        >
          <Loader2 v-if="downloading" :size="20" class="animate-spin" />
          <Download v-else :size="20" />
          {{ downloading ? 'Gerando Imagem...' : 'Baixar Carteirinha (PNG)' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { User, Calendar, Trophy, Loader2, Users, Shield, ClipboardList, CreditCard, X, Printer, Download } from 'lucide-vue-next';
import athleteService from '../services/athleteService';
import AthleteIDCard from '../components/AthleteIDCard.vue';
import html2canvas from 'html2canvas';

const loading = ref(true);
const downloading = ref(false);
const athlete = ref(null);
const teams = ref([]);
const nextGames = ref([]);
const results = ref([]);
const convites = ref([]);
const showIDCard = ref(false);
const showTermosModal = ref(false);
const conviteSelecionado = ref(null);
const termosAceitos = ref(false);
const termosScrollCompleto = ref(false);
const termosScrollRef = ref(null);
const respondendoConvite = ref(false);

const user = computed(() => {
  const userStr = localStorage.getItem('user');
  return userStr ? JSON.parse(userStr) : null;
});

const downloadCard = async () => {
  const cardElement = document.getElementById('athlete-card');
  if (!cardElement) return;

  downloading.value = true;
  
  // Clone element to remove transforms/scale issues
  const clone = cardElement.cloneNode(true);
  
  // Create a hidden container for the clone
  const container = document.createElement('div');
  container.style.position = 'absolute';
  container.style.top = '-9999px';
  container.style.left = '-9999px';
  container.style.zIndex = '-1';
  container.appendChild(clone);
  document.body.appendChild(container);

  try {
    // Wait for images to load in clone
    const images = clone.querySelectorAll('img');
    await Promise.all(Array.from(images).map(img => {
        if (img.complete) return Promise.resolve();
        return new Promise(resolve => {
            img.onload = resolve;
            img.onerror = resolve;
        });
    }));

    // Wait a bit for fonts and layout
    await new Promise(resolve => setTimeout(resolve, 500));

    const canvas = await html2canvas(clone, {
      scale: 3, // High quality
      useCORS: true, // Allow external images
      backgroundColor: '#ffffff', // Force white background
      logging: false,
      allowTaint: true,
      foreignObjectRendering: false // Disable foreignObject which causes issues with fonts sometimes
    });

    const image = canvas.toDataURL("image/png");
    const link = document.createElement('a');
    link.href = image;
    link.download = `carteirinha-${athlete.value?.nome?.replace(/\s+/g, '-').toLowerCase() || 'atleta'}.png`;
    link.click();
  } catch (error) {
    console.error('Erro ao gerar imagem:', error);
    alert('Erro ao baixar carteirinha. Tente novamente.');
  } finally {
    // Clean up
    document.body.removeChild(container);
    downloading.value = false;
  }
};

const formatDate = (dateString) => {
  if (!dateString) return null;
  // Parse date string (YYYY-MM-DD) manually to avoid timezone issues
  // Assuming dateString comes as "YYYY-MM-DD" or similar
  if (dateString.includes('T')) {
      dateString = dateString.split('T')[0];
  }
  
  const [year, month, day] = dateString.split('-');
  return `${day}/${month}/${year}`;
};

const formatDateTime = (dateString) => {
  if (!dateString) return 'Data a definir';
  return new Date(dateString).toLocaleString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const fetchDashboardData = async () => {
  try {
    loading.value = true;
    const { data } = await athleteService.getDashboard();
    
    athlete.value = data.atleta;
    teams.value = data.equipes || [];
    nextGames.value = data.proximos_jogos || [];
    results.value = data.ultimos_resultados || [];
    convites.value = data.convites || [];
    
  } catch (error) {
    console.error('Erro ao carregar dashboard do atleta:', error);
  } finally {
    loading.value = false;
  }
};

const abrirModalTermos = (convite) => {
  conviteSelecionado.value = convite;
  showTermosModal.value = true;
  termosAceitos.value = false;
  termosScrollCompleto.value = false;
};

const fecharModalTermos = () => {
  showTermosModal.value = false;
  conviteSelecionado.value = null;
  termosAceitos.value = false;
  termosScrollCompleto.value = false;
};

const onTermosScroll = () => {
  const el = termosScrollRef.value;
  if (!el) return;
  const noFinal = el.scrollTop + el.clientHeight >= el.scrollHeight - 4;
  if (noFinal) {
    termosScrollCompleto.value = true;
  }
};

const confirmarAceiteConvite = async () => {
  if (!conviteSelecionado.value) return;
  respondendoConvite.value = true;
  try {
    await athleteService.responderConvite(conviteSelecionado.value.inscricao_id, {
      aceitar: true,
      equipe_id: conviteSelecionado.value.equipe_id,
      aceitou_termos_imagem: true,
      aceitou_regulamento: true,
    });
    alert('Convite aceito com sucesso!');
    fecharModalTermos();
    await fetchDashboardData();
  } catch (error) {
    alert(error.response?.data?.message || 'Não foi possível aceitar o convite.');
  } finally {
    respondendoConvite.value = false;
  }
};

const recusarConvite = async (convite) => {
  if (!confirm('Deseja realmente recusar este convite?')) return;
  try {
    await athleteService.responderConvite(convite.inscricao_id, {
      aceitar: false,
      equipe_id: convite.equipe_id,
    });
    alert('Convite recusado.');
    await fetchDashboardData();
  } catch (error) {
    alert(error.response?.data?.message || 'Não foi possível recusar o convite.');
  }
};

onMounted(() => {
  fetchDashboardData();
});
</script>
