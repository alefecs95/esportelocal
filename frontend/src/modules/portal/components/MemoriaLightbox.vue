<template>
  <Teleport to="body">
    <div
      v-if="item"
      class="fixed inset-0 z-[80] bg-black/90 flex flex-col"
      role="dialog"
      aria-modal="true"
      :aria-label="item.titulo"
      @click.self="close"
    >
      <div class="flex items-center justify-between gap-3 px-4 py-3 text-white">
        <p class="font-semibold truncate">{{ item.titulo }}</p>
        <button type="button" class="shrink-0 w-11 h-11 rounded-full bg-white/10 font-bold" aria-label="Fechar" @click="close">✕</button>
      </div>
      <div class="relative flex-1 flex items-center justify-center px-4 min-h-0" @touchstart.passive="onTouchStart" @touchend.passive="onTouchEnd">
        <button
          v-if="hasPrev"
          type="button"
          class="hidden sm:flex absolute left-3 w-11 h-11 rounded-full bg-white/10 text-white items-center justify-center"
          aria-label="Anterior"
          @click="prev"
        >‹</button>
        <img
          v-if="item.tipo === 'foto'"
          :src="item.arquivo_url"
          class="max-h-[70vh] max-w-full object-contain rounded-lg"
          :alt="item.alt_text || item.titulo"
        />
        <video
          v-else-if="item.tipo === 'video'"
          :src="item.arquivo_url"
          controls
          autoplay
          class="max-h-[70vh] max-w-full rounded-lg bg-black"
        ></video>
        <audio v-else-if="item.tipo === 'audio'" :src="item.arquivo_url" controls class="w-full max-w-lg"></audio>
        <div v-else class="bg-white rounded-2xl p-8 text-center max-w-md">
          <p class="font-semibold text-slate-900">{{ item.titulo }}</p>
          <a :href="item.arquivo_url" target="_blank" rel="noopener" class="inline-flex mt-4 bg-slate-900 text-white px-5 py-3 rounded-full font-bold">Abrir documento</a>
        </div>
        <button
          v-if="hasNext"
          type="button"
          class="hidden sm:flex absolute right-3 w-11 h-11 rounded-full bg-white/10 text-white items-center justify-center"
          aria-label="Próximo"
          @click="next"
        >›</button>
      </div>
      <div class="px-4 pb-6 pt-3 text-white text-center space-y-2">
        <p class="text-sm text-white/70">
          {{ item.ano || 'Ano a confirmar' }}
          <span v-if="item.local?.nome"> · {{ item.local.nome }}</span>
        </p>
        <div class="flex flex-wrap justify-center gap-2">
          <router-link
            v-if="item.precisa_identificar"
            to="/memoria/quem-e"
            class="px-4 py-2 rounded-full bg-yellow-400 text-slate-900 text-sm font-bold"
            @click="close"
          >Quem é essa pessoa?</router-link>
          <router-link
            v-if="item.slug || item.id"
            :to="{ name: 'PortalMemoriaAcervo', params: { slug: item.slug || item.id } }"
            class="px-4 py-2 rounded-full bg-white text-slate-900 text-sm font-bold"
            @click="close"
          >Ver história completa</router-link>
          <a :href="item.arquivo_url" target="_blank" rel="noopener" class="px-4 py-2 rounded-full border border-white/30 text-sm font-bold">Baixar / abrir original</a>
        </div>
        <p class="text-xs text-white/40 sm:hidden">Deslize ou use as setas do teclado</p>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, onBeforeUnmount, watch } from 'vue';

const props = defineProps({
  items: { type: Array, default: () => [] },
  index: { type: Number, default: -1 },
});

const emit = defineEmits(['update:index', 'close']);

const item = computed(() => (props.index >= 0 ? props.items[props.index] : null));
const hasPrev = computed(() => props.index > 0);
const hasNext = computed(() => props.index >= 0 && props.index < props.items.length - 1);

const close = () => emit('close');
const prev = () => {
  if (hasPrev.value) emit('update:index', props.index - 1);
};
const next = () => {
  if (hasNext.value) emit('update:index', props.index + 1);
};

const onKey = (event) => {
  if (props.index < 0) return;
  if (event.key === 'Escape') close();
  if (event.key === 'ArrowLeft') prev();
  if (event.key === 'ArrowRight') next();
};

let touchX = 0;
const onTouchStart = (event) => {
  touchX = event.changedTouches[0]?.clientX || 0;
};
const onTouchEnd = (event) => {
  const x = event.changedTouches[0]?.clientX || 0;
  const delta = x - touchX;
  if (delta > 50) prev();
  if (delta < -50) next();
};

watch(
  () => props.index,
  (value, _, onCleanup) => {
    if (value < 0) return;
    document.body.style.overflow = 'hidden';
    window.addEventListener('keydown', onKey);
    onCleanup(() => {
      document.body.style.overflow = '';
      window.removeEventListener('keydown', onKey);
    });
  }
);

onBeforeUnmount(() => {
  document.body.style.overflow = '';
  window.removeEventListener('keydown', onKey);
});
</script>
