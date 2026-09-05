<template>
  <div class="space-y-6">
    <PageHeader title="Ranking" description="Classificação geral e por modalidade da temporada.">
      <template #actions>
        <select v-model="selectedTemporada" @change="fetchCurrent" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
          <option v-for="temp in temporadas" :key="temp.id" :value="temp.id">
            {{ temp.nome }}
          </option>
        </select>
        <button
          v-if="tab === 'geral' && isTemporadaAtiva"
          type="button"
          @click="handleRecalculate"
          :disabled="recalculating"
          class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50"
        >
          {{ recalculating ? 'Recalculando...' : 'Recalcular ranking' }}
        </button>
      </template>
    </PageHeader>

    <div class="flex gap-2 border-b border-gray-200">
      <button
        type="button"
        class="px-4 py-2 text-sm font-semibold border-b-2"
        :class="tab === 'geral' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-500'"
        @click="setTab('geral')"
      >
        Geral
      </button>
      <button
        type="button"
        class="px-4 py-2 text-sm font-semibold border-b-2"
        :class="tab === 'modalidade' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-500'"
        @click="setTab('modalidade')"
      >
        Por modalidade
      </button>
    </div>

    <div class="flex flex-wrap gap-4 items-center">
      <select v-if="tab === 'modalidade'" v-model="selectedModalidade" @change="fetchCurrent" class="rounded-md border-gray-300 shadow-sm sm:text-sm min-w-[200px]">
        <option :value="null" disabled>Selecione uma modalidade</option>
        <option v-for="mod in modalidades" :key="mod.id" :value="mod.id">{{ mod.nome }}</option>
      </select>
      <div class="flex rounded-md shadow-sm">
        <button
          type="button"
          @click="setType('atleta')"
          :class="type === 'atleta' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'"
          class="relative inline-flex items-center px-4 py-2 rounded-l-md border border-gray-300 text-sm font-medium"
        >
          Atletas
        </button>
        <button
          type="button"
          @click="setType('equipe')"
          :class="type === 'equipe' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'"
          class="relative -ml-px inline-flex items-center px-4 py-2 rounded-r-md border border-gray-300 text-sm font-medium"
        >
          Equipes
        </button>
      </div>
    </div>

    <template v-if="tab === 'geral'">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <Top10Card :rankingData="top10" :type="type" :temporadaLabel="temporadaLabel" />
      </div>
      <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Classificação completa</h2>
        <RankingTable :rankingData="rankingGeral" :type="type" />
      </div>
    </template>

    <template v-else>
      <div v-if="selectedModalidade" class="bg-white shadow rounded-lg p-6">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Classificação — {{ modalidadeLabel }}</h2>
        <RankingTable :rankingData="rankingModalidade" :type="type" />
      </div>
      <EmptyState v-else title="Selecione uma modalidade" description="Escolha a modalidade para ver o ranking da temporada." />
    </template>

    <ConfirmDialog
      :is-open="confirm.isOpen.value"
      :title="confirm.title.value"
      :message="confirm.message.value"
      @confirm="confirm.confirm"
      @cancel="confirm.cancel"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useToast } from 'vue-toastification';
import { useRankingStore } from '../store/rankingStore';
import { useTemporadaStore } from '@/modules/temporadas/store/temporadaStore';
import { useModalidadeStore } from '@/modules/modalidades/store/modalidadeStore';
import RankingTable from '../components/RankingTable.vue';
import Top10Card from '../components/Top10Card.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue';
import { useConfirmDialog } from '@/composables/useConfirmDialog';

const toast = useToast();
const confirm = useConfirmDialog();
const route = useRoute();
const router = useRouter();
const rankingStore = useRankingStore();
const temporadaStore = useTemporadaStore();
const modalidadeStore = useModalidadeStore();

const { rankingGeral, rankingModalidade, top10, recalculating } = storeToRefs(rankingStore);
const { temporadas, currentTemporada } = storeToRefs(temporadaStore);
const { modalidades } = storeToRefs(modalidadeStore);

const selectedTemporada = ref(null);
const selectedModalidade = ref(null);
const type = ref('atleta');
const tab = ref(route.query.tab === 'modalidade' ? 'modalidade' : 'geral');

const temporadaLabel = computed(() => temporadas.value.find((t) => t.id === selectedTemporada.value)?.nome || '');
const modalidadeLabel = computed(() => modalidades.value.find((m) => m.id === selectedModalidade.value)?.nome || '');
const isTemporadaAtiva = computed(() => temporadas.value.find((t) => t.id === selectedTemporada.value)?.status === 'ativa');

const fetchCurrent = () => {
  if (!selectedTemporada.value) return;
  if (tab.value === 'geral') {
    rankingStore.fetchRankingGeral(selectedTemporada.value, { type: type.value });
    rankingStore.fetchTop10(selectedTemporada.value, { type: type.value });
    return;
  }
  if (!selectedModalidade.value) return;
  rankingStore.fetchRankingPorModalidade(selectedTemporada.value, selectedModalidade.value, { type: type.value });
};

const setTab = (next) => {
  tab.value = next;
  router.replace({ query: { ...route.query, tab: next === 'modalidade' ? 'modalidade' : undefined } });
  fetchCurrent();
};

const setType = (newType) => {
  type.value = newType;
  fetchCurrent();
};

const handleRecalculate = async () => {
  const ok = await confirm.ask(
    'Recalcular o ranking da temporada ativa atualiza todos os dados com base nos campeonatos finalizados.',
    'Recalcular ranking'
  );
  if (!ok) return;
  try {
    await rankingStore.recalcularRanking();
    toast.success('Ranking recalculado com sucesso.');
    fetchCurrent();
  } catch {
    toast.error('Não foi possível recalcular o ranking.');
  }
};

watch(() => route.query.tab, (value) => {
  const next = value === 'modalidade' ? 'modalidade' : 'geral';
  if (next !== tab.value) {
    tab.value = next;
    fetchCurrent();
  }
});

onMounted(async () => {
  await Promise.all([temporadaStore.fetchTemporadas(), modalidadeStore.fetchModalidades()]);
  if (currentTemporada.value) {
    selectedTemporada.value = currentTemporada.value.id;
  } else if (temporadas.value.length > 0) {
    selectedTemporada.value = temporadas.value[0].id;
  }
  fetchCurrent();
});
</script>
