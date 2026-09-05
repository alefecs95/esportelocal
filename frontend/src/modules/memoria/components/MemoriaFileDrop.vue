<template>
  <div>
    <label v-if="label" class="block text-sm font-semibold text-gray-800 mb-1">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>
    <p v-if="hint" class="text-xs text-gray-500 mb-2">{{ hint }}</p>
    <input
      ref="inputRef"
      type="file"
      class="sr-only"
      :accept="accept"
      :required="required && !previewUrl && !hasExisting"
      @change="onChange"
    />
    <button
      type="button"
      class="w-full rounded-2xl border-2 border-dashed px-4 py-6 text-left transition-colors min-h-[9rem]"
      :class="previewUrl || hasExisting
        ? 'border-blue-200 bg-blue-50/60 hover:border-blue-400'
        : 'border-gray-300 bg-gray-50 hover:border-blue-400 hover:bg-blue-50/40'"
      @click="inputRef?.click()"
    >
      <div v-if="previewUrl && isImage" class="flex items-center gap-4">
        <img :src="previewUrl" alt="Prévia" class="h-24 w-24 sm:h-28 sm:w-36 object-cover rounded-xl bg-white border" />
        <div class="min-w-0">
          <p class="font-semibold text-gray-800 truncate">{{ fileName || 'Arquivo selecionado' }}</p>
          <p class="text-sm text-gray-500 mt-1">Clique para trocar o arquivo</p>
        </div>
      </div>
      <div v-else-if="previewUrl && isVideo" class="flex items-center gap-4">
        <video :src="previewUrl" class="h-24 w-36 rounded-xl bg-black object-cover" muted />
        <div>
          <p class="font-semibold text-gray-800">{{ fileName }}</p>
          <p class="text-sm text-gray-500 mt-1">Clique para trocar o vídeo</p>
        </div>
      </div>
      <div v-else-if="previewUrl && isAudio" class="flex items-center gap-4">
        <audio :src="previewUrl" controls class="flex-1" />
        <p class="text-sm text-gray-500">Clique para trocar o áudio</p>
      </div>
      <div v-else class="flex items-center gap-4">
        <div class="h-14 w-14 rounded-xl bg-white border flex items-center justify-center text-blue-600 shrink-0">
          <Upload :size="22" />
        </div>
        <div>
          <p class="font-semibold text-gray-800">{{ emptyTitle }}</p>
          <p class="text-sm text-gray-500 mt-1">{{ emptySubtitle }}</p>
        </div>
      </div>
    </button>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { Upload } from 'lucide-vue-next';

const props = defineProps({
  label: { type: String, default: 'Arquivo' },
  hint: { type: String, default: '' },
  accept: { type: String, default: 'image/*' },
  required: { type: Boolean, default: false },
  existingUrl: { type: String, default: '' },
  emptyTitle: { type: String, default: 'Clique para enviar' },
  emptySubtitle: { type: String, default: 'JPG, PNG ou WEBP' },
});

const emit = defineEmits(['change']);

const inputRef = ref(null);
const file = ref(null);
const objectUrl = ref('');

const fileName = computed(() => file.value?.name || '');
const hasExisting = computed(() => !!props.existingUrl);
const previewUrl = computed(() => objectUrl.value || props.existingUrl || '');
const isVideo = computed(() => {
  if (file.value) return file.value.type.startsWith('video/');
  return !!previewUrl.value.match(/\.(mp4|webm)($|\?)/i);
});
const isAudio = computed(() => {
  if (file.value) return file.value.type.startsWith('audio/');
  return !!previewUrl.value.match(/\.(mp3|ogg|wav|m4a|weba)($|\?)/i);
});
const isImage = computed(() => {
  if (file.value) return file.value.type.startsWith('image/');
  return !isVideo.value && !isAudio.value && !previewUrl.value.match(/\.pdf($|\?)/i);
});

const revoke = () => {
  if (objectUrl.value) {
    URL.revokeObjectURL(objectUrl.value);
    objectUrl.value = '';
  }
};

const onChange = (event) => {
  const next = event.target.files?.[0] || null;
  file.value = next;
  revoke();
  if (next) objectUrl.value = URL.createObjectURL(next);
  emit('change', next);
};

watch(() => props.existingUrl, () => {
  if (!file.value) revoke();
});

onBeforeUnmount(revoke);
</script>
