<template>
  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold" :class="toneClass">
    {{ label }}
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  status: {
    type: [String, Boolean],
    default: '',
  },
});

const MAP = {
  rascunho: { label: 'Rascunho', class: 'bg-gray-100 text-gray-700' },
  draft: { label: 'Rascunho', class: 'bg-gray-100 text-gray-700' },
  publicado: { label: 'Publicado', class: 'bg-green-100 text-green-800' },
  pendente: { label: 'Pendente', class: 'bg-amber-100 text-amber-800' },
  aprovada: { label: 'Aprovada', class: 'bg-green-100 text-green-800' },
  aprovado: { label: 'Aprovado', class: 'bg-green-100 text-green-800' },
  confirmada: { label: 'Confirmada', class: 'bg-green-100 text-green-800' },
  rejeitada: { label: 'Rejeitada', class: 'bg-red-100 text-red-800' },
  rejeitado: { label: 'Rejeitado', class: 'bg-red-100 text-red-800' },
  cancelada: { label: 'Cancelada', class: 'bg-gray-100 text-gray-700' },
  em_andamento: { label: 'Em andamento', class: 'bg-blue-100 text-blue-800' },
  inscricoes_abertas: { label: 'Inscrições abertas', class: 'bg-yellow-100 text-yellow-800' },
  inscricoes_encerradas: { label: 'Inscrições encerradas', class: 'bg-orange-100 text-orange-800' },
  finalizado: { label: 'Finalizado', class: 'bg-slate-100 text-slate-700' },
  ativo: { label: 'Ativo', class: 'bg-green-100 text-green-800' },
  inativo: { label: 'Inativo', class: 'bg-red-100 text-red-700' },
  pago: { label: 'Pago', class: 'bg-green-100 text-green-800' },
  nao_pago: { label: 'Não pago', class: 'bg-red-100 text-red-800' },
  isento: { label: 'Isento', class: 'bg-blue-100 text-blue-800' },
};

const resolved = computed(() => {
  if (props.status === true || props.status === 'true') return MAP.publicado;
  if (props.status === false || props.status === 'false') return MAP.rascunho;
  const key = String(props.status || '').toLowerCase();
  return MAP[key] || { label: props.status || '-', class: 'bg-gray-100 text-gray-700' };
});

const label = computed(() => resolved.value.label);
const toneClass = computed(() => resolved.value.class);
</script>
