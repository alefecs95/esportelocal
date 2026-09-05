<template>
  <nav class="bg-slate-950 border-t border-white/10" aria-label="Seções da Memória">
    <div class="container mx-auto px-2 sm:px-4">
      <ul class="flex gap-1 overflow-x-auto py-2 [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden">
        <li v-for="item in items" :key="item.to">
          <router-link :to="item.to" custom v-slot="{ href, navigate, isActive, isExactActive }">
            <a
              :href="href"
              class="block shrink-0 px-3 sm:px-4 py-2 rounded-full text-sm font-bold min-h-11 inline-flex items-center whitespace-nowrap"
              :class="linkClass(item, isActive, isExactActive)"
              @click="navigate"
            >{{ item.label }}</a>
          </router-link>
        </li>
      </ul>
    </div>
  </nav>
</template>

<script setup>
import { useRoute } from 'vue-router';

const route = useRoute();

const items = [
  { to: '/memoria', label: 'História', exact: true },
  { to: '/memoria/galeria', label: 'Galeria' },
  { to: '/memoria/hall', label: 'Pessoas', aliases: ['/memoria/personalidades'] },
  { to: '/memoria/equipes', label: 'Equipes' },
  { to: '/memoria/mapa', label: 'Cidade', aliases: ['/memoria/locais'] },
  { to: '/memoria/recordes', label: 'Recordes' },
  { to: '/memoria/enviar', label: 'Enviar foto', primary: true },
];

const linkClass = (item, isActive, isExactActive) => {
  const aliasOn = (item.aliases || []).some((prefix) => route.path.startsWith(prefix));
  const on = item.exact ? isExactActive : isActive || aliasOn;
  if (on) return 'bg-white text-slate-900';
  if (item.primary) return 'bg-yellow-400 text-slate-900';
  return 'text-white/80 hover:bg-white/10 hover:text-white';
};
</script>
