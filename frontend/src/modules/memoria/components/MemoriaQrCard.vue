<template>
  <section v-if="pageUrl" class="bg-white border rounded-2xl p-5 print:block">
    <h2 class="font-bold text-lg mb-2">{{ title }}</h2>
    <p class="text-sm text-gray-600 mb-4">{{ hint }}</p>
    <div class="flex flex-col sm:flex-row sm:items-center gap-4">
      <img :src="qrSrc" :alt="alt" class="w-40 h-40 bg-white border rounded-xl" />
      <div class="min-w-0">
        <p class="text-xs text-gray-500 break-all">{{ pageUrl }}</p>
        <button
          type="button"
          class="mt-3 px-4 py-2 rounded-xl border border-gray-300 text-sm font-semibold min-h-11"
          @click="printQr"
        >
          Imprimir QR
        </button>
        <p v-if="unpublished" class="text-xs text-amber-700 mt-2">Publique no portal para o QR abrir a página pública.</p>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  title: { type: String, default: 'QR no espaço' },
  hint: { type: String, default: 'Imprima e afixe no local. Quem apontar a câmera abre esta página.' },
  path: { type: String, default: '' },
  alt: { type: String, default: 'QR code da página pública' },
  unpublished: { type: Boolean, default: false },
});

const pageUrl = computed(() => {
  if (!props.path) return '';
  const path = props.path.startsWith('/') ? props.path : `/${props.path}`;
  return `${window.location.origin}${path}`;
});
const qrSrc = computed(() => (
  pageUrl.value
    ? `https://api.qrserver.com/v1/create-qr-code/?size=240x240&data=${encodeURIComponent(pageUrl.value)}`
    : ''
));
const printQr = () => window.print();
</script>
