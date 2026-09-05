<template>
  <div class="min-h-screen bg-slate-50">
    <MemoriaPageHero
      title="Livro de recordes"
      description="Marcas históricas da Memória e a artilharia dos campeonatos que a cidade disputa hoje."
    />
    <section class="container mx-auto px-4 py-8 space-y-10">
      <div v-if="loading" class="h-40 bg-slate-200 rounded-2xl animate-pulse"></div>
      <div v-else>
        <section>
          <h2 class="text-xl font-bold mb-4">Temporada atual</h2>
          <div v-if="temporada.length === 0" class="bg-white border rounded-2xl p-6 text-gray-500">
            Ainda não há gols registrados nos campeonatos em andamento ou encerrados.
          </div>
          <div v-else class="grid md:grid-cols-2 gap-3">
            <router-link
              v-for="item in temporada"
              :key="item.campeonato_id"
              :to="`/campeonatos/${item.campeonato_id}`"
              class="bg-white border rounded-2xl p-4 hover:border-blue-500"
            >
              <p class="text-xs font-bold uppercase text-blue-700">{{ item.modalidade || 'Campeonato' }} · {{ item.status === 'em_andamento' ? 'Em andamento' : 'Encerrado' }}</p>
              <p class="font-semibold mt-1">{{ item.titulo }}</p>
              <p class="text-sm text-gray-600 mt-1">{{ item.atleta_nome }} · {{ item.valor }} {{ item.unidade }}<span v-if="item.equipe_nome"> · {{ item.equipe_nome }}</span></p>
            </router-link>
          </div>
        </section>

        <section class="mt-10">
          <h2 class="text-xl font-bold mb-4">Recordes históricos</h2>
          <div v-if="historico.length === 0" class="bg-white border rounded-2xl p-6 text-gray-500">
            A secretaria ainda não cadastrou marcas históricas. Elas aparecem aqui quando forem publicadas.
          </div>
          <div v-else class="grid md:grid-cols-2 gap-3">
            <article v-for="item in historico" :key="item.id" class="bg-white border rounded-2xl p-4">
              <p class="text-xs font-bold uppercase text-amber-700">
                {{ categoriaRecordeLabels[item.categoria] || item.categoria }}
                <span v-if="item.ano"> · {{ item.ano }}</span>
                <span v-if="item.vigente"> · vigente</span>
              </p>
              <h3 class="font-semibold mt-1">{{ item.titulo }}</h3>
              <p class="text-sm text-gray-700 mt-1">
                <template v-if="item.personalidade">
                  <router-link :to="{ name: 'PortalMemoriaPersonalidade', params: { slug: item.personalidade.slug || item.personalidade.id } }" class="font-semibold text-blue-700">{{ item.personalidade.nome }}</router-link>
                </template>
                <template v-else>{{ item.atleta_nome }}</template>
                <span v-if="item.valor"> · {{ item.valor }}{{ item.unidade ? ' ' + item.unidade : '' }}</span>
              </p>
              <p v-if="item.descricao" class="text-sm text-gray-500 mt-2">{{ item.descricao }}</p>
              <p v-if="item.campeonato" class="text-xs text-gray-400 mt-2">Ligado a {{ item.campeonato.nome }}</p>
              <router-link
                v-if="item.marco"
                :to="{ name: 'PortalMemoriaMarco', params: { slug: item.marco.slug || item.marco.id } }"
                class="inline-block mt-2 text-sm font-bold text-blue-700"
              >Ver o fato</router-link>
            </article>
          </div>
        </section>
      </div>
    </section>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import portalService from '../services/portalService';
import { categoriaRecordeLabels } from '@/modules/memoria/utils/memoriaLabels';
import MemoriaPageHero from '../components/MemoriaPageHero.vue';

const loading = ref(true);
const historico = ref([]);
const temporada = ref([]);

onMounted(async () => {
  try {
    const { data } = await portalService.getMemoriaRecordes();
    historico.value = data.historico || [];
    temporada.value = data.temporada || [];
  } finally {
    loading.value = false;
  }
});
</script>
