<template>
  <div class="space-y-6">
    <div v-if="erro" class="rounded-xl bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
      {{ erro }}
    </div>
    <div v-if="sucesso" class="rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm font-semibold">
      {{ sucesso }}
    </div>

    <!-- Busca rápida e marcação manual de chegada (Kids/Teens ou sem cronometragem) -->
    <div class="bg-white rounded-xl border border-gray-200 p-4 space-y-3">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <div>
          <h3 class="font-bold text-gray-900">Marcar chegada manual (Kids / Teens)</h3>
          <p class="text-xs text-gray-500">Somente participantes que retiraram o kit podem receber medalha finisher.</p>
        </div>
        <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 self-start">
          {{ medalhas.length }} medalha(s) finisher
        </span>
      </div>

      <input
        v-model="search"
        type="text"
        placeholder="Buscar por nome, CPF ou nº de peito..."
        class="w-full px-4 py-3 border border-gray-300 rounded-xl text-base"
        @input="debouncedLoad"
      />
    </div>

    <div v-if="loading" class="p-8 text-center text-gray-500">Carregando...</div>
    <div v-else class="space-y-3">
      <div
        v-for="insc in inscritosComKit"
        :key="insc.id"
        class="bg-white rounded-xl border border-gray-200 p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
      >
        <div>
          <div class="flex items-center gap-2 flex-wrap">
            <span v-if="insc.numero_peito" class="px-2.5 py-0.5 rounded-full bg-blue-600 text-white text-xs font-bold">
              Peito #{{ formatNumeroPeito(insc.numero_peito) }}
            </span>
            <span class="font-bold text-gray-900">{{ insc.atleta?.nome }}</span>
          </div>
          <div class="text-xs text-gray-600 mt-1">
            {{ insc.categoria?.nome || 'Geral' }}
            <span v-if="insc.tempo_visual"> · Tempo: {{ insc.tempo_visual }}</span>
          </div>
        </div>

        <div>
          <span v-if="insc.finalizou_em" class="inline-block px-3 py-1.5 rounded-lg bg-emerald-100 text-emerald-800 text-xs font-bold">
            Finisher (Medalha liberada)
          </span>
          <button
            v-else
            type="button"
            :disabled="marcandoId === insc.id"
            @click="marcarChegada(insc)"
            class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 disabled:opacity-50 min-h-[44px]"
          >
            {{ marcandoId === insc.id ? 'Registrando...' : 'Marcar chegada' }}
          </button>
        </div>
      </div>

      <div v-if="inscritosComKit.length === 0" class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-500 text-sm">
        Nenhum participante com kit retirado encontrado.
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { formatNumeroPeito } from '@/utils/formatNumeroPeito';
import { useRoute } from 'vue-router';
import inscricaoService from '@/modules/inscricoes/services/inscricaoService';

const route = useRoute();
const inscritosComKit = ref([]);
const medalhas = ref([]);
const loading = ref(false);
const search = ref('');
const erro = ref('');
const sucesso = ref('');
const marcandoId = ref(null);
let timer = null;

const carregar = async () => {
  loading.value = true;
  erro.value = '';
  try {
    const [respKit, respMedalhas] = await Promise.all([
      inscricaoService.getInscricoesPorCampeonato(route.params.id, {
        kit_retirado: 1,
        ...(search.value ? { search: search.value } : {}),
      }),
      inscricaoService.getMedalhasFinisher(route.params.id),
    ]);
    inscritosComKit.value = respKit.data?.data || [];
    medalhas.value = respMedalhas.data?.data || [];
  } catch (e) {
    erro.value = e.response?.data?.message || 'Erro ao carregar lista de medalha finisher.';
  } finally {
    loading.value = false;
  }
};

const debouncedLoad = () => {
  clearTimeout(timer);
  timer = setTimeout(carregar, 300);
};

const marcarChegada = async (insc) => {
  marcandoId.value = insc.id;
  erro.value = '';
  sucesso.value = '';
  try {
    await inscricaoService.marcarChegada(insc.id);
    sucesso.value = `Chegada registrada para ${insc.atleta?.nome}!`;
    await carregar();
  } catch (e) {
    erro.value = e.response?.data?.message || 'Erro ao registrar chegada.';
  } finally {
    marcandoId.value = null;
  }
};

onMounted(carregar);
</script>
