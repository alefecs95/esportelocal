<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <h1 class="text-2xl font-bold text-gray-900">Configuração da Landing Page</h1>
    </div>

    <BaseCard>
      <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Informações Principais</h2>
        
        <form @submit.prevent="save" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <BaseInput 
              id="hero_title" 
              v-model="form.hero_title" 
              label="Título Principal (Hero)" 
              placeholder="Ex: O sistema definitivo..."
            />
            <BaseInput 
              id="hero_subtitle" 
              v-model="form.hero_subtitle" 
              label="Subtítulo Principal" 
              placeholder="Ex: Digitalize sua Secretaria..."
            />
          </div>

          <div class="pt-4 border-t border-gray-200">
            <h3 class="text-md font-medium text-gray-800 mb-4">Estatísticas (Faixa Azul)</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
              <BaseInput id="stat1" v-model="form.stats.cidades" label="Cidades Ativas" />
              <BaseInput id="stat2" v-model="form.stats.atletas" label="Atletas Base" />
              <BaseInput id="stat3" v-model="form.stats.jogos" label="Jogos Geridos" />
              <BaseInput id="stat4" v-model="form.stats.papel" label="Papel Gasto" />
            </div>
          </div>

          <div class="pt-4 border-t border-gray-200">
            <h3 class="text-md font-medium text-gray-800 mb-4">Títulos das Seções</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <BaseInput id="sec1" v-model="form.section_benefits_title" label="Título: Benefícios" placeholder="A evolução da sua Secretaria" />
              <BaseInput id="sec2" v-model="form.section_how_it_works_title" label="Título: Como Funciona" placeholder="Como transformar sua Secretaria em 3 passos" />
              <BaseInput id="sec3" v-model="form.section_features_title" label="Título: Funcionalidades" placeholder="Recursos pensados para o esporte público" />
              <BaseInput id="sec4" v-model="form.section_sumula_title" label="Título: Súmula" placeholder="Aposente o papel com a Súmula Eletrônica" />
            </div>
          </div>

          <div class="pt-4 border-t border-gray-200">
            <h3 class="text-md font-medium text-gray-800 mb-4">Textos Descritivos</h3>
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Subtítulo: Benefícios</label>
                <textarea v-model="form.section_benefits_subtitle" rows="2" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"></textarea>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Texto: Súmula Eletrônica</label>
                <textarea v-model="form.section_sumula_text" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"></textarea>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Rodapé: Sobre a Empresa</label>
                <textarea v-model="form.footer_about_text" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"></textarea>
              </div>
            </div>
          </div>

          <div class="pt-4 border-t border-gray-200">
            <h3 class="text-md font-medium text-gray-800 mb-4">Redes Sociais e Contato</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <BaseInput id="cont_email" v-model="form.contact_email" label="E-mail Público" :placeholder="contactEmailPlaceholder" />
              <BaseInput id="cont_phone" v-model="form.contact_phone" label="Telefone/WhatsApp" placeholder="(00) 00000-0000" />
              <BaseInput id="soc_insta" v-model="form.social_instagram" label="Link Instagram" placeholder="https://instagram.com/..." />
              <BaseInput id="soc_fb" v-model="form.social_facebook" label="Link Facebook" placeholder="https://facebook.com/..." />
            </div>
          </div>

          <div class="flex justify-end pt-4 border-t border-gray-200">
            <BaseButton type="submit" variant="primary" :disabled="saving">
              <Save :size="20" class="mr-2" />
              {{ saving ? 'Salvando...' : 'Salvar Alterações' }}
            </BaseButton>
          </div>
        </form>
      </div>
    </BaseCard>

    <div class="bg-blue-50 border border-blue-100 p-4 rounded-xl flex gap-4">
      <div class="bg-blue-100 p-2 rounded-lg text-blue-600 h-fit">
        <Info :size="24" />
      </div>
      <div>
        <h4 class="font-bold text-blue-900">Configurações Dinâmicas</h4>
        <p class="text-blue-800 text-sm mt-1">
          Nesta tela você gerencia de forma dinâmica os textos e estatísticas da Landing Page SaaS principal ({{ mainDomainLabel }}). 
          <br><br>
          As alterações feitas aqui refletirão imediatamente na página inicial do sistema.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { Save, Info } from 'lucide-vue-next';
import BaseCard from '../../../components/ui/BaseCard.vue';
import BaseInput from '../../../components/ui/BaseInput.vue';
import BaseButton from '../../../components/ui/BaseButton.vue';
import api from '../../../services/api';
import { getMainDomain } from '@/utils/domainHelpers';

const mainDomainLabel = computed(() => getMainDomain());
const contactEmailPlaceholder = computed(() => `contato@${getMainDomain()}`);

const saving = ref(false);

const form = ref({
  hero_title: '',
  hero_subtitle: '',
  video_url: '',
  stats: {
    cidades: '',
    atletas: '',
    jogos: '',
    papel: ''
  },
  section_benefits_title: '',
  section_benefits_subtitle: '',
  section_how_it_works_title: '',
  section_how_it_works_subtitle: '',
  section_features_title: '',
  section_sumula_title: '',
  section_sumula_text: '',
  footer_about_text: '',
  contact_email: '',
  contact_phone: '',
  social_instagram: '',
  social_facebook: ''
});

const loadSettings = async () => {
  try {
    const { data } = await api.get('/admin/landing-page');
    form.value.hero_title = data.hero_title;
    form.value.hero_subtitle = data.hero_subtitle;
    form.value.video_url = data.video_url || '';
    form.value.stats.cidades = data.stats_cidades;
    form.value.stats.atletas = data.stats_atletas;
    form.value.stats.jogos = data.stats_jogos;
    form.value.stats.papel = data.stats_papel;
    
    form.value.section_benefits_title = data.section_benefits_title || 'A evolução da sua Secretaria';
    form.value.section_benefits_subtitle = data.section_benefits_subtitle || 'Chega de perder noites organizando chaves e recebendo fichas de papel rasgadas. Eleve o nível do esporte na sua cidade.';
    form.value.section_how_it_works_title = data.section_how_it_works_title || 'Como transformar sua Secretaria em 3 passos';
    form.value.section_how_it_works_subtitle = data.section_how_it_works_subtitle || '';
    form.value.section_features_title = data.section_features_title || 'Recursos pensados para o esporte público';
    form.value.section_sumula_title = data.section_sumula_title || 'Aposente o papel com a Súmula Eletrônica';
    form.value.section_sumula_text = data.section_sumula_text || 'Árbitros e mesários preenchem tudo pelo celular ou tablet na beira da quadra. Gols, cartões e substituições são registrados em tempo real, atualizando automaticamente artilharia e tabelas no portal.';
    form.value.footer_about_text = data.footer_about_text || 'Ajudando municípios brasileiros a transformar a gestão esportiva através da tecnologia, promovendo transparência e inclusão social.';
    form.value.contact_email = data.contact_email || '';
    form.value.contact_phone = data.contact_phone || '';
    form.value.social_instagram = data.social_instagram || '';
    form.value.social_facebook = data.social_facebook || '';

  } catch (error) {
    console.error('Erro ao carregar configurações:', error);
  }
};

const save = async () => {
  saving.value = true;
  try {
    await api.put('/admin/landing-page', {
      hero_title: form.value.hero_title,
      hero_subtitle: form.value.hero_subtitle,
      video_url: form.value.video_url,
      stats_cidades: form.value.stats.cidades,
      stats_atletas: form.value.stats.atletas,
      stats_jogos: form.value.stats.jogos,
      stats_papel: form.value.stats.papel,
      section_benefits_title: form.value.section_benefits_title,
      section_benefits_subtitle: form.value.section_benefits_subtitle,
      section_how_it_works_title: form.value.section_how_it_works_title,
      section_how_it_works_subtitle: form.value.section_how_it_works_subtitle,
      section_features_title: form.value.section_features_title,
      section_sumula_title: form.value.section_sumula_title,
      section_sumula_text: form.value.section_sumula_text,
      footer_about_text: form.value.footer_about_text,
      contact_email: form.value.contact_email,
      contact_phone: form.value.contact_phone,
      social_instagram: form.value.social_instagram,
      social_facebook: form.value.social_facebook,
    });
    alert('Configurações da Landing Page salvas com sucesso!');
  } catch (error) {
    console.error('Erro ao salvar configurações:', error);
    alert('Erro ao salvar configurações. Verifique os dados.');
  } finally {
    saving.value = false;
  }
};

onMounted(() => {
  loadSettings();
});
</script>
