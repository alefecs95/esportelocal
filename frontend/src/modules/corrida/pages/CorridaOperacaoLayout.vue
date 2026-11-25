<template>
  <div class="container mx-auto px-3 sm:px-4 py-5 max-w-7xl">
    <!-- Cabeçalho mobile-first -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
      <div>
        <button
          type="button"
          @click="$router.push('/admin/competicoes')"
          class="text-xs font-medium text-blue-600 hover:underline mb-1 inline-block"
        >
          ← Voltar para Competições
        </button>
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900">
          Operação da Corrida <span v-if="campeonato" class="text-blue-700">— {{ campeonato.nome }}</span>
        </h1>
      </div>

      <!-- Botões de Exportação PDF -->
      <div class="flex flex-wrap gap-2">
        <button
          type="button"
          @click="abrirPdf('inscritos')"
          class="px-3 py-2 rounded-lg bg-white border border-gray-300 text-xs sm:text-sm font-semibold text-gray-700 hover:bg-gray-50"
        >
          PDF Inscritos
        </button>
        <button
          type="button"
          @click="abrirPdf('retirada-kit')"
          class="px-3 py-2 rounded-lg bg-white border border-gray-300 text-xs sm:text-sm font-semibold text-gray-700 hover:bg-gray-50"
        >
          PDF Retirada Kit
        </button>
        <button
          type="button"
          @click="abrirPdf('largada')"
          class="px-3 py-2 rounded-lg bg-white border border-gray-300 text-xs sm:text-sm font-semibold text-gray-700 hover:bg-gray-50"
        >
          PDF Largada
        </button>
        <button
          type="button"
          @click="abrirPdf('contribuicoes')"
          class="px-3 py-2 rounded-lg bg-white border border-gray-300 text-xs sm:text-sm font-semibold text-gray-700 hover:bg-gray-50"
        >
          PDF Contribuições
        </button>
      </div>
    </div>

    <!-- Abas de operação (grande e fácil de tocar no celular) -->
    <div class="flex gap-2 overflow-x-auto pb-2 mb-5 border-b border-gray-200">
      <router-link
        v-for="tab in tabs"
        :key="tab.name"
        :to="{ name: tab.name, params: { id: campeonatoId } }"
        class="px-4 py-2.5 rounded-lg text-sm font-semibold whitespace-nowrap transition min-h-[44px] flex items-center"
        :class="$route.name === tab.name ? 'bg-blue-600 text-white shadow-sm' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50'"
      >
        {{ tab.label }}
      </router-link>
    </div>

    <div v-if="pdfErro" class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
      {{ pdfErro }}
    </div>

    <router-view :campeonato="campeonato" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import campeonatoService from '@/modules/campeonatos/services/campeonatoService';
import inscricaoService from '@/modules/inscricoes/services/inscricaoService';

const route = useRoute();
const campeonatoId = computed(() => route.params.id);
const campeonato = ref(null);
const pdfErro = ref('');

const tabs = [
  { name: 'CorridaInscritos', label: '1. Inscritos' },
  { name: 'CorridaContribuicoes', label: '2. Contribuições' },
  { name: 'CorridaRetiradaKit', label: '3. Retirada de Kit' },
  { name: 'CorridaPremiacao', label: '4. Premiação' },
  { name: 'CorridaMedalhas', label: '5. Medalha Finisher' },
];

onMounted(async () => {
  try {
    const resp = await campeonatoService.get(campeonatoId.value);
    campeonato.value = resp.data?.data || resp.data;
  } catch (e) {
    console.error('Erro ao carregar competição', e);
  }
});

const abrirPdf = async (tipo) => {
  pdfErro.value = '';
  try {
    const resp = await inscricaoService.getPdfUrl(campeonatoId.value, tipo);
    const url = resp.data?.url;
    if (url) {
      window.open(url, '_blank', 'noopener');
    } else {
      pdfErro.value = 'Não foi possível gerar o link do PDF.';
    }
  } catch (e) {
    pdfErro.value = e.response?.data?.message || 'Erro ao gerar PDF.';
  }
};
</script>
