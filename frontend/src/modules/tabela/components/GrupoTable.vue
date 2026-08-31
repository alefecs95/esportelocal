<template>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div v-for="(grupo, nomeGrupo) in grupos" :key="nomeGrupo" class="border rounded-lg shadow-sm bg-white overflow-hidden">
      <div class="bg-gray-100 px-4 py-2 border-b font-bold text-gray-700">
        Grupo {{ nomeGrupo.replace('Grupo ', '') }}
      </div>
      
      <!-- Tabela de Classificação do Grupo -->
      <table class="w-full text-sm text-left">
        <thead class="bg-gray-50 text-gray-600 font-medium border-b">
          <tr>
            <th class="px-3 py-2">Time</th>
            <th class="px-2 py-2 text-center" title="Pontos">P</th>
            <th class="px-2 py-2 text-center" title="Vitórias">V</th>
            <th class="px-2 py-2 text-center" title="Empates">E</th>
            <th class="px-2 py-2 text-center" title="Derrotas">D</th>
            <th class="px-2 py-2 text-center" title="Saldo de Gols">SG</th>
          </tr>
        </thead>
        <tbody class="divide-y">
          <tr v-for="(time, index) in calcularClassificacao(grupo)" :key="time.id" class="hover:bg-gray-50">
            <td class="px-3 py-2 font-medium truncate max-w-[150px]">{{ index + 1 }}º {{ time.nome }}</td>
            <td class="px-2 py-2 text-center font-bold">{{ time.pontos }}</td>
            <td class="px-2 py-2 text-center">{{ time.vitorias }}</td>
            <td class="px-2 py-2 text-center">{{ time.empates }}</td>
            <td class="px-2 py-2 text-center">{{ time.derrotas }}</td>
            <td class="px-2 py-2 text-center">{{ time.saldo }}</td>
          </tr>
        </tbody>
      </table>

      <!-- Partidas do Grupo -->
      <div class="border-t p-2 bg-gray-50">
        <h4 class="text-xs font-semibold text-gray-500 uppercase mb-2 px-2">Jogos</h4>
        <div class="space-y-2">
          <PartidaCard 
            v-for="partida in grupo" 
            :key="partida.id" 
            :partida="partida"
            :pode-editar="false"
            class="text-sm"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { computed } from 'vue';
import PartidaCard from './PartidaCard.vue';

export default {
  components: { PartidaCard },
  props: {
    partidas: {
      type: Array,
      required: true
    }
  },
  setup(props) {
    // Agrupar partidas por chave (Grupo A, Grupo B...)
    const grupos = computed(() => {
      if (!props.partidas || !Array.isArray(props.partidas)) return {};
      
      const groups = {};
      props.partidas.forEach(p => {
        if (!p) return;
        const key = p.grupo || p.chave || 'Geral'; // Usar campo grupo se existir
        if (!groups[key]) groups[key] = [];
        groups[key].push(p);
      });
      // Ordenar chaves
      return Object.keys(groups).sort().reduce((acc, key) => {
        acc[key] = groups[key];
        return acc;
      }, {});
    });

    const calcularClassificacao = (partidasGrupo) => {
      const stats = {};

      const initStats = (id, nome) => {
        if (!stats[id]) {
          stats[id] = { 
            id, 
            nome, 
            pontos: 0, 
            vitorias: 0, 
            empates: 0, 
            derrotas: 0, 
            gp: 0, 
            gc: 0, 
            saldo: 0 
          };
        }
      };

      partidasGrupo.forEach(p => {
        if (!p.mandante || !p.visitante) return;
        
        initStats(p.mandante.id, p.mandante.nome);
        initStats(p.visitante.id, p.visitante.nome);

        if (p.status === 'finalizada') {
          const pm = p.placar_mandante;
          const pv = p.placar_visitante;

          stats[p.mandante.id].gp += pm;
          stats[p.mandante.id].gc += pv;
          stats[p.mandante.id].saldo += (pm - pv);

          stats[p.visitante.id].gp += pv;
          stats[p.visitante.id].gc += pm;
          stats[p.visitante.id].saldo += (pv - pm);

          if (pm > pv) {
            stats[p.mandante.id].pontos += 3;
            stats[p.mandante.id].vitorias++;
            stats[p.visitante.id].derrotas++;
          } else if (pv > pm) {
            stats[p.visitante.id].pontos += 3;
            stats[p.visitante.id].vitorias++;
            stats[p.mandante.id].derrotas++;
          } else {
            stats[p.mandante.id].pontos += 1;
            stats[p.mandante.id].empates++;
            stats[p.visitante.id].pontos += 1;
            stats[p.visitante.id].empates++;
          }
        }
      });

      return Object.values(stats).sort((a, b) => {
        if (b.pontos !== a.pontos) return b.pontos - a.pontos;
        if (b.vitorias !== a.vitorias) return b.vitorias - a.vitorias;
        return b.saldo - a.saldo;
      });
    };

    return { grupos, calcularClassificacao };
  }
}
</script>
