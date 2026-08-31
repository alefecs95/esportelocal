<template>
    <div class="overflow-x-auto rounded-lg shadow border border-gray-100">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b border-gray-100">
                <tr>
                    <th scope="col" class="px-6 py-3 font-semibold">Equipe</th>
                    <th scope="col" class="px-6 py-3 font-semibold">Clube/Entidade</th>
                    <th scope="col" class="px-6 py-3 font-semibold">Responsável</th>
                    <th scope="col" class="px-6 py-3 font-semibold">Contato</th>
                    <th scope="col" class="px-6 py-3 font-semibold text-center">Atletas</th>
                    <th scope="col" class="px-6 py-3 font-semibold text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
                <tr v-if="equipes.length === 0">
                    <td colspan="6" class="px-6 py-8 text-center text-gray-400">
                        Nenhuma equipe cadastrada neste campeonato.
                    </td>
                </tr>
                <tr 
                    v-for="equipe in equipes" 
                    :key="equipe.id" 
                    class="bg-white border-b hover:bg-gray-50 transition-colors group"
                >
                    <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                        {{ equipe.nome }}
                    </th>
                    <td class="px-6 py-4">
                        <span v-if="equipe.entidade" class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-blue-100 text-blue-800 flex items-center justify-center text-xs font-bold">
                                {{ equipe.entidade.sigla || equipe.entidade.nome.substring(0,2).toUpperCase() }}
                            </span>
                            {{ equipe.entidade.nome }}
                        </span>
                        <span v-else class="text-gray-400 italic text-xs">Não vinculado</span>
                    </td>
                    <td class="px-6 py-4">{{ equipe.responsavel || '-' }}</td>
                    <td class="px-6 py-4">{{ equipe.contato || '-' }}</td>
                    <td class="px-6 py-4 text-center">
                        <div class="flex flex-col items-center gap-1">
                            <span class="bg-blue-100 text-blue-800 text-xs font-bold px-2.5 py-0.5 rounded-full">
                                {{ equipe.atletas_count }}
                                <span v-if="campeonato?.max_atletas_equipe"> / {{ campeonato.max_atletas_equipe }}</span>
                            </span>
                            <span v-if="isCompleta(equipe)" class="text-green-600 text-xs font-semibold flex items-center gap-1">
                                ✓ Completa
                            </span>
                            <span v-else class="text-yellow-600 text-xs font-semibold flex items-center gap-1">
                                ⚠ Incompleta
                            </span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-right flex justify-end gap-2">
                        <button 
                            @click="$emit('manage', equipe)" 
                            class="text-blue-600 hover:underline font-medium text-sm"
                            title="Gerenciar Atletas"
                        >
                            Gerenciar
                        </button>
                        <button 
                            @click="$emit('delete', equipe.id)" 
                            class="text-red-600 hover:underline font-medium text-sm ml-2"
                            title="Excluir Equipe"
                        >
                            Excluir
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
const props = defineProps({
    equipes: {
        type: Array,
        default: () => []
    },
    campeonato: {
        type: Object,
        default: null
    }
});

defineEmits(['edit', 'delete', 'manage']);

const isCompleta = (equipe) => {
    if (!props.campeonato) return false;
    const min = props.campeonato.min_atletas_equipe;
    if (min) return equipe.atletas_count >= min;
    // Se não tem mínimo definido, considera incompleta se vazia, ou completa se tem algum?
    // Vamos assumir que se não tem minimo, > 0 já é algo.
    return equipe.atletas_count > 0;
};
</script>
