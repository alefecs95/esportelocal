<template>
  <div class="min-h-screen bg-slate-50">
    <MemoriaPageHero
      title="Mapa da Memória"
      description="Ginásios, estádios e fatos da linha do tempo. Toque no ponto para abrir a página."
    />
    <section class="container mx-auto px-4 py-6">
      <div v-if="loading" class="h-[60vh] bg-slate-200 rounded-2xl animate-pulse"></div>
      <div v-else-if="locais.length === 0" class="py-16 text-center text-gray-500 bg-white rounded-2xl border">
        <p class="font-semibold text-gray-800">Ainda não há locais com coordenadas no mapa.</p>
        <p class="mt-1">A curadoria precisa informar latitude e longitude no cadastro do espaço.</p>
        <router-link to="/memoria" class="inline-flex mt-4 bg-slate-900 text-white px-5 py-3 rounded-full font-bold">Voltar à linha do tempo</router-link>
      </div>
      <div v-else>
        <div id="memoria-mapa" class="h-[60vh] rounded-2xl overflow-hidden border z-0"></div>
        <ul class="mt-6 grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
          <li v-for="l in locais" :key="l.id">
            <router-link
              :to="{ name: 'PortalMemoriaLocal', params: { slug: l.slug || l.id } }"
              class="block bg-white border rounded-2xl p-4 hover:border-blue-500 min-h-11"
            >
              <p class="font-semibold">{{ l.nome }}</p>
              <p class="text-xs text-gray-500 mt-1">{{ l.endereco || tipoLocalLabels[l.tipo] || l.tipo }}</p>
            </router-link>
          </li>
        </ul>
      </div>
    </section>
  </div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import portalService from '../services/portalService';
import { tipoLocalLabels } from '@/modules/memoria/utils/memoriaLabels';
import MemoriaPageHero from '../components/MemoriaPageHero.vue';

const loading = ref(true);
const locais = ref([]);
let map = null;

const loadLeaflet = () => new Promise((resolve, reject) => {
  if (window.L) {
    resolve(window.L);
    return;
  }
  if (!document.getElementById('leaflet-css')) {
    const css = document.createElement('link');
    css.id = 'leaflet-css';
    css.rel = 'stylesheet';
    css.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
    document.head.appendChild(css);
  }
  const script = document.createElement('script');
  script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
  script.onload = () => resolve(window.L);
  script.onerror = reject;
  document.head.appendChild(script);
});

onMounted(async () => {
  try {
    const { data } = await portalService.getMemoriaMapa();
    locais.value = data.locais || (Array.isArray(data) ? data : []);
    const marcos = data.marcos || [];
    if (!locais.value.length) return;
    const L = await loadLeaflet();
    L.Icon.Default.mergeOptions({
      iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
      iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
      shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
    });
    const first = locais.value[0];
    map = L.map('memoria-mapa').setView([first.lat, first.lng], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap',
    }).addTo(map);
    const bounds = [];
    locais.value.forEach((l) => {
      const marker = L.marker([l.lat, l.lng]).addTo(map);
      marker.bindPopup(`<a href="/memoria/locais/${l.slug || l.id}">${l.nome}</a>`);
      bounds.push([l.lat, l.lng]);
    });
    marcos.forEach((m) => {
      if (!m.local?.lat || !m.local?.lng) return;
      const marker = L.circleMarker([m.local.lat, m.local.lng], { radius: 7, color: '#ca8a04', fillColor: '#facc15', fillOpacity: 0.9 }).addTo(map);
      marker.bindPopup(`<a href="/memoria/marcos/${m.slug || m.id}">${m.ano || ''} · ${m.titulo}</a>`);
    });
    if (bounds.length > 1) map.fitBounds(bounds, { padding: [32, 32] });
  } finally {
    loading.value = false;
  }
});

onBeforeUnmount(() => {
  map?.remove();
});
</script>
