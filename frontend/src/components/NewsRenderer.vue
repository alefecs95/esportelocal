<template>
  <div class="news-renderer prose max-w-none">
    <div v-for="block in blocks" :key="block.id" class="mb-4">
      <!-- Paragraph -->
      <p v-if="block.type === 'paragraph'" v-html="block.data.text" class="text-gray-800 leading-relaxed"></p>

      <!-- Header -->
      <h1 v-else-if="block.type === 'header' && block.data.level === 1" v-html="block.data.text" class="text-3xl font-bold mt-8 mb-4"></h1>
      <h2 v-else-if="block.type === 'header' && block.data.level === 2" v-html="block.data.text" class="text-2xl font-bold mt-6 mb-3 text-gray-800"></h2>
      <h3 v-else-if="block.type === 'header' && block.data.level === 3" v-html="block.data.text" class="text-xl font-bold mt-5 mb-2 text-gray-800"></h3>
      <h4 v-else-if="block.type === 'header' && block.data.level === 4" v-html="block.data.text" class="text-lg font-bold mt-4 mb-2 text-gray-800"></h4>

      <!-- List -->
      <ul v-else-if="block.type === 'list' && block.data.style === 'unordered'" class="list-disc pl-5 space-y-1">
        <li v-for="(item, i) in block.data.items" :key="i" v-html="typeof item === 'string' ? item : (item.content || item.text || item)"></li>
      </ul>
      <ol v-else-if="block.type === 'list' && block.data.style === 'ordered'" class="list-decimal pl-5 space-y-1">
        <li v-for="(item, i) in block.data.items" :key="i" v-html="typeof item === 'string' ? item : (item.content || item.text || item)"></li>
      </ol>

      <!-- Quote -->
      <blockquote v-else-if="block.type === 'quote'" class="border-l-4 border-blue-500 pl-4 italic bg-gray-50 py-2 pr-4 rounded my-4">
        <p v-html="block.data.text" class="mb-1"></p>
        <footer v-if="block.data.caption" class="text-sm text-gray-500 font-medium">— {{ block.data.caption }}</footer>
      </blockquote>

      <!-- Image -->
      <figure v-else-if="block.type === 'image'" class="my-6">
        <div class="relative overflow-hidden rounded-lg shadow-sm" :class="{'bg-gray-100': block.data.withBackground, 'border border-gray-200': block.data.withBorder, 'w-full': block.data.stretched}">
            <img :src="block.data.file.url" :alt="block.data.caption || 'Imagem da notícia'" class="w-full h-auto object-cover" />
        </div>
        <figcaption v-if="block.data.caption" class="text-center text-sm text-gray-500 mt-2">{{ block.data.caption }}</figcaption>
      </figure>

      <!-- Embed (YouTube, etc) -->
      <div v-else-if="block.type === 'embed'" class="my-6 aspect-w-16 aspect-h-9">
        <iframe :src="block.data.embed" class="w-full h-[400px] rounded-lg shadow-sm" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>
        <div v-if="block.data.caption" class="text-center text-sm text-gray-500 mt-2">{{ block.data.caption }}</div>
      </div>

      <!-- Table -->
      <div v-else-if="block.type === 'table'" class="overflow-x-auto my-6">
        <table class="min-w-full border-collapse border border-gray-200">
          <tbody>
            <tr v-for="(row, rIndex) in block.data.content" :key="rIndex" :class="{'bg-gray-50': rIndex === 0 && block.data.withHeadings}">
              <td v-for="(cell, cIndex) in row" :key="cIndex" v-html="cell" class="border border-gray-200 px-4 py-2 text-sm" :class="{'font-bold': rIndex === 0 && block.data.withHeadings}"></td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Warning -->
      <div v-else-if="block.type === 'warning'" class="bg-yellow-50 border-l-4 border-yellow-400 p-4 my-4 rounded-r">
        <div class="flex">
          <div class="flex-shrink-0">
            <!-- Icon placeholder if needed -->
          </div>
          <div class="ml-3">
            <h3 v-if="block.data.title" class="text-sm font-medium text-yellow-800" v-html="block.data.title"></h3>
            <div class="mt-2 text-sm text-yellow-700" v-html="block.data.message"></div>
          </div>
        </div>
      </div>

      <!-- Delimiter -->
      <div v-else-if="block.type === 'delimiter'" class="flex justify-center items-center my-8 text-2xl tracking-widest text-gray-300">
        ***
      </div>

      <!-- Carousel Block (Slider) -->
      <div v-else-if="block.type === 'carousel'" class="my-8">
        <div v-if="getBlockMediaItems(block).length" class="space-y-3">
            <div class="relative rounded-xl overflow-hidden border border-gray-200 bg-black h-[360px]">
              <img
                v-if="!isVideoMedia(getActiveCarouselMedia(block))"
                :src="getActiveCarouselMedia(block)?.url"
                class="w-full h-full object-contain cursor-zoom-in"
                @click="$emit('open-lightbox', getActiveCarouselMedia(block)?.url)"
              />
              <video
                v-else
                :src="getActiveCarouselMedia(block)?.url"
                controls
                class="w-full h-full object-contain"
              ></video>

              <button
                v-if="getBlockMediaItems(block).length > 1"
                @click="prevCarousel(block)"
                class="absolute left-2 top-1/2 -translate-y-1/2 bg-white/90 hover:bg-white text-gray-800 rounded-full w-9 h-9 flex items-center justify-center"
              >
                ‹
              </button>
              <button
                v-if="getBlockMediaItems(block).length > 1"
                @click="nextCarousel(block)"
                class="absolute right-2 top-1/2 -translate-y-1/2 bg-white/90 hover:bg-white text-gray-800 rounded-full w-9 h-9 flex items-center justify-center"
              >
                ›
              </button>
            </div>

            <div v-if="getBlockMediaItems(block).length > 1" class="flex gap-2 overflow-x-auto py-1">
              <button
                v-for="(media, idx) in getBlockMediaItems(block)"
                :key="`carousel-thumb-${idx}`"
                @click="setCarouselIndex(block, idx)"
                class="w-16 h-12 rounded border overflow-hidden flex-shrink-0"
                :class="getCarouselIndex(block) === idx ? 'border-blue-600' : 'border-gray-200'"
              >
                <img v-if="!isVideoMedia(media)" :src="media.url" class="w-full h-full object-cover" />
                <video v-else :src="media.url" class="w-full h-full object-cover"></video>
              </button>
            </div>
        </div>
        <div v-else class="text-center p-4 bg-gray-100 rounded text-gray-500 text-sm">
            [Carrossel sem mídias]
        </div>
      </div>

      <!-- Gallery Block -->
      <div v-else-if="block.type === 'gallery'" class="my-8">
        <div v-if="getBlockMediaItems(block).length" class="bg-gray-50 p-4 md:p-6 rounded-2xl shadow-sm border border-gray-100">
            <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
              </svg>
              Galeria de Mídias
            </h3>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
              <div 
                v-for="(media, idx) in getBlockMediaItems(block)" 
                :key="`gallery-${idx}`" 
                class="group relative aspect-square overflow-hidden rounded-lg bg-gray-200 cursor-zoom-in"
                @click="!isVideoMedia(media) && $emit('open-lightbox', media.url)"
              >
                <img v-if="!isVideoMedia(media)" :src="media.url" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" loading="lazy" />
                <video v-else :src="media.url" controls class="w-full h-full object-cover"></video>
                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors"></div>
              </div>
            </div>
        </div>
        <div v-else class="text-center p-4 bg-gray-100 rounded text-gray-500 text-sm">
            [Galeria sem mídias]
        </div>
      </div>

      <!-- Fallback for unknown blocks -->
      <div v-else class="text-red-500 text-xs hidden">
        Bloco não suportado: {{ block.type }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
  content: {
    type: [Object, String],
    default: () => ({ blocks: [] })
  },
  carouselUrls: {
    type: Array,
    default: () => []
  },
  galleryUrls: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['open-lightbox']);
const carouselIndexes = ref({});

const isVideoMedia = (media) => {
  const type = (media?.type || '').toLowerCase();
  const mime = (media?.mime || '').toLowerCase();
  if (type === 'video') return true;
  if (mime.startsWith('video/')) return true;
  const url = (media?.url || '').toLowerCase();
  return url.endsWith('.mp4') || url.endsWith('.webm') || url.endsWith('.ogg');
};

const getBlockMediaItems = (block) => {
  const data = block?.data || {};
  const candidates = [
    data.items,
    data.media,
    data.medias,
    data.files,
    data.images,
  ];
  for (const candidate of candidates) {
    if (Array.isArray(candidate) && candidate.length) {
      return candidate.map((entry) => {
        if (typeof entry === 'string') {
          return { url: entry, type: 'image' };
        }
        return entry;
      });
    }
  }
  if (block?.type === 'carousel' && props.carouselUrls?.length) {
    return props.carouselUrls.map((url) => ({ url, type: 'image' }));
  }
  if (block?.type === 'gallery' && props.galleryUrls?.length) {
    return props.galleryUrls.map((url) => ({ url, type: 'image' }));
  }
  return [];
};

const getCarouselKey = (block) => String(block?.id || block?.type || 'carousel');

const getCarouselIndex = (block) => {
  const items = getBlockMediaItems(block);
  if (!items.length) return 0;
  const key = getCarouselKey(block);
  const current = Number.isInteger(carouselIndexes.value[key]) ? carouselIndexes.value[key] : 0;
  if (current >= items.length) return 0;
  return current;
};

const setCarouselIndex = (block, index) => {
  const items = getBlockMediaItems(block);
  if (!items.length) return;
  const total = items.length;
  const next = ((index % total) + total) % total;
  const key = getCarouselKey(block);
  carouselIndexes.value = { ...carouselIndexes.value, [key]: next };
};

const prevCarousel = (block) => {
  setCarouselIndex(block, getCarouselIndex(block) - 1);
};

const nextCarousel = (block) => {
  setCarouselIndex(block, getCarouselIndex(block) + 1);
};

const getActiveCarouselMedia = (block) => {
  const items = getBlockMediaItems(block);
  if (!items.length) return null;
  return items[getCarouselIndex(block)];
};


const blocks = computed(() => {
  if (!props.content) return [];
  
  // Se for string JSON
  if (typeof props.content === 'string') {
    try {
      const parsed = JSON.parse(props.content);
      return parsed.blocks || [];
    } catch (e) {
      // Se não for JSON válido, talvez seja texto legado simples
      // Retorna como um bloco de parágrafo único
      return [{
        id: 'legacy',
        type: 'paragraph',
        data: { text: props.content }
      }];
    }
  }
  
  // Se já for objeto
  return props.content.blocks || [];
});
</script>

<style scoped>
/* Adicionais se necessário, mas Tailwind cobre a maioria */
</style>
