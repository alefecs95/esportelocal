<template>
  <div class="space-y-5">
    <div v-if="erro" class="rounded-xl bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
      {{ erro }}
    </div>
    <div v-if="sucesso" class="rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm font-semibold">
      {{ sucesso }}
    </div>

    <div v-if="loading" class="p-8 text-center text-gray-500">Calculando pódios...</div>
    <div v-else-if="grupos.length === 0" class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-500">
      Nenhuma categoria competitiva com tempos registrados ainda.
    </div>

    <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-5">
      <div
        v-for="grupo in grupos"
        :key="grupo.categoria.id"
        class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm"
      >
        <div class="bg-blue-50 border-b border-blue-100 px-4 py-3 flex items-center justify-between gap-2">
          <div>
            <h3 class="font-bold text-blue-900">{{ grupo.categoria.nome }}</h3>
            <p v-if="grupo.categoria.premiacao_descricao" class="text-xs text-blue-700">
              Premiação: {{ grupo.categoria.premiacao_descricao }}
            </p>
          </div>
          <span v-if="grupo.categoria.ordem_premiacao" class="text-xs font-semibold px-2 py-0.5 rounded bg-white text-blue-700 border border-blue-200">
            Ordem #{{ grupo.categoria.ordem_premiacao }}
          </span>
        </div>

        <!-- Aviso de atletas pulados por não acumular premiação -->
        <div v-if="grupo.pulados && grupo.pulados.length" class="bg-amber-50 border-b border-amber-200 px-4 py-2.5 text-xs text-amber-900 space-y-1">
          <div class="font-bold">Atleta(s) pulado(s) pela regra de não acumular premiação:</div>
          <div v-for="p in grupo.pulados" :key="p.inscricao_id">
            • {{ p.atleta_nome }} ({{ p.colocacao_original }}º na prova) — já premiado(a) em <strong>{{ p.categoria_premiada_antes }}</strong>.
          </div>
        </div>

        <div v-if="grupo.colocados.length === 0" class="p-5 text-sm text-gray-500 text-center">
          Nenhum atleta com tempo registrado nesta categoria.
        </div>

        <div v-else class="divide-y divide-gray-100">
          <div
            v-for="colocado in grupo.colocados"
            :key="colocado.inscricao_id"
            class="p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
          >
            <div>
              <div class="flex items-center gap-2">
                <span class="w-7 h-7 rounded-full bg-amber-100 text-amber-800 font-bold text-sm flex items-center justify-center">
                  {{ colocado.colocacao_categoria }}º
                </span>
                <span class="font-bold text-gray-900">{{ colocado.atleta_nome }}</span>
                <span v-if="colocado.numero_peito" class="text-xs text-gray-500">#{{ colocado.numero_peito }}</span>
              </div>
              <div class="text-xs text-gray-600 mt-1 ml-9">
                Tempo: <strong>{{ colocado.tempo_visual || '-' }}</strong>
                <span v-if="colocado.premiacao_descricao"> · {{ colocado.premiacao_descricao }}</span>
              </div>
            </div>

            <div>
              <span v-if="colocado.premiado_em" class="inline-block px-3 py-1.5 rounded-lg bg-emerald-100 text-emerald-800 text-xs font-bold">
                Premiado
              </span>
              <button
                v-else
                type="button"
                :disabled="premiandoId === colocado.inscricao_id"
                @click="registrarPremiacao(colocado)"
                class="w-full sm:w-auto px-4 py-2 rounded-lg bg-amber-500 text-white text-xs font-bold hover:bg-amber-600 disabled:opacity-50 min-h-[40px]"
              >
                {{ premiandoId === colocado.inscricao_id ? 'Salvando...' : 'Registrar premiação' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import inscricaoService from '@/modules/inscricoes/services/inscricaoService';

const route = useRoute();
const grupos = ref([]);
const loading = ref(false);
const erro = ref('');
const sucesso = ref('');
const premiandoId = ref(null);

const carregar = async () => {
  loading.value = true;
  erro.value = '';
  try {
    const resp = await inscricaoService.getPodio(route.params.id);
    grupos.value = resp.data?.data || [];
  } catch (e) {
    erro.value = e.response?.data?.message || 'Erro ao carregar pódio.';
  } finally {
    loading.value = false;
  }
};

const registrarPremiacao = async (colocado) => {
  premiandoId.value = colocado.inscricao_id;
  erro.value = '';
  sucesso.value = '';
  try {
    await inscricaoService.premiar(colocado.inscricao_id, {
      premiacao_descricao: colocado.premiacao_descricao,
    });
    sucesso.value = `Premiação registrada para ${colocado.atleta_nome}!`;
    await carregar();
  } catch (e) {
    erro.value = e.response?.data?.message || 'Erro ao registrar premiação.';
  } finally {
    premiandoId.value = null;
  }
};

onMounted(carregar);
</script>
