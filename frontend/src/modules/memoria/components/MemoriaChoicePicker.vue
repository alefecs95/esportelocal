<template>
  <div>
    <div v-if="variant === 'cards'" :class="gridClass">
      <button
        v-for="option in visibleOptions"
        :key="option.id"
        type="button"
        class="rounded-xl border px-3 py-3 text-left min-h-11"
        :class="modelValue === option.id ? 'border-blue-600 bg-blue-50 ring-2 ring-blue-100' : 'border-gray-200 hover:border-gray-300'"
        @click="$emit('update:modelValue', option.id)"
      >
        <span class="block text-sm font-semibold text-gray-900">{{ option.label }}</span>
        <span v-if="option.hint" class="block text-xs text-gray-500 mt-0.5">{{ option.hint }}</span>
      </button>
      <button
        v-if="!adding"
        type="button"
        class="rounded-xl border border-dashed border-blue-300 bg-blue-50/40 px-3 py-3 text-left min-h-11 hover:border-blue-500"
        @click="startAdd"
      >
        <span class="block text-sm font-semibold text-blue-800">+ {{ addLabel }}</span>
        <span class="block text-xs text-blue-700/80 mt-0.5">Fica disponível da próxima vez</span>
      </button>
    </div>

    <div v-else class="space-y-2">
      <select
        class="w-full rounded-xl border-gray-300 min-h-11"
        :value="modelValue"
        @change="$emit('update:modelValue', $event.target.value)"
      >
        <option v-for="option in visibleOptions" :key="option.id" :value="option.id">{{ option.label }}</option>
      </select>
      <button
        v-if="!adding"
        type="button"
        class="text-sm font-semibold text-blue-700 hover:text-blue-900"
        @click="startAdd"
      >
        + {{ addLabel }}
      </button>
    </div>

    <div v-if="adding" class="mt-3 flex flex-col sm:flex-row gap-2 p-3 rounded-2xl bg-slate-50 border border-slate-200">
      <input
        ref="draftInput"
        v-model="draft"
        maxlength="50"
        class="flex-1 rounded-xl border-gray-300 min-h-11"
        :placeholder="placeholder"
        @keydown.enter.prevent="confirmAdd"
      />
      <div class="flex gap-2">
        <button type="button" class="px-4 py-2 rounded-xl bg-blue-600 text-white text-sm font-semibold min-h-11" :disabled="saving" @click="confirmAdd">
          {{ saving ? 'Incluindo...' : 'Incluir' }}
        </button>
        <button type="button" class="px-4 py-2 rounded-xl border border-gray-300 text-sm font-medium min-h-11" @click="cancelAdd">Cancelar</button>
      </div>
    </div>
    <p v-if="addError" class="text-xs text-red-600 mt-1">{{ addError }}</p>
  </div>
</template>

<script setup>
import { computed, nextTick, onMounted, ref } from 'vue';
import memoriaService from '../services/memoriaService';

const props = defineProps({
  modelValue: { type: String, default: '' },
  grupo: { type: String, required: true },
  defaults: { type: Array, default: () => [] },
  hiddenIds: { type: Array, default: () => [] },
  variant: { type: String, default: 'cards' },
  addLabel: { type: String, default: 'Adicionar' },
  placeholder: { type: String, default: 'Nome da nova opção' },
  gridClass: { type: String, default: 'grid grid-cols-2 sm:grid-cols-3 gap-2' },
});

const emit = defineEmits(['update:modelValue']);

const extras = ref([]);
const adding = ref(false);
const draft = ref('');
const saving = ref(false);
const addError = ref('');
const draftInput = ref(null);

const visibleOptions = computed(() => {
  const hidden = new Set(props.hiddenIds);
  const base = props.defaults.filter((opt) => !hidden.has(opt.id) || opt.id === props.modelValue);
  const known = new Set(base.map((opt) => opt.id));
  const extraCards = extras.value
    .filter((valor) => valor && !known.has(valor))
    .map((valor) => ({ id: valor, label: valor }));
  return [...base, ...extraCards];
});

const load = async () => {
  try {
    const { data } = await memoriaService.opcoes();
    extras.value = data?.[props.grupo] || [];
  } catch {
    extras.value = [];
  }
};

const startAdd = async () => {
  adding.value = true;
  draft.value = '';
  addError.value = '';
  await nextTick();
  draftInput.value?.focus();
};

const cancelAdd = () => {
  adding.value = false;
  draft.value = '';
  addError.value = '';
};

const matchDefault = (label) => {
  const needle = label.trim().toLowerCase();
  return props.defaults.find((opt) => opt.id === needle || String(opt.label).toLowerCase() === needle);
};

const confirmAdd = async () => {
  const label = draft.value.trim();
  if (!label) {
    addError.value = 'Escreva o nome da opção.';
    return;
  }
  const existing = matchDefault(label);
  if (existing) {
    emit('update:modelValue', existing.id);
    cancelAdd();
    return;
  }
  saving.value = true;
  addError.value = '';
  try {
    const { data } = await memoriaService.createOpcao({ grupo: props.grupo, valor: label });
    const valor = data.valor || label;
    if (!extras.value.includes(valor)) extras.value = [...extras.value, valor];
    emit('update:modelValue', valor);
    cancelAdd();
  } catch (e) {
    addError.value = e.response?.data?.message || 'Não foi possível incluir esta opção.';
  } finally {
    saving.value = false;
  }
};

onMounted(load);
</script>
