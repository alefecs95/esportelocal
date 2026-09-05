<template>
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <h1 class="text-2xl font-bold text-gray-900">Institucional e Personalização</h1>
        </div>

        <div class="bg-white shadow rounded-lg p-6">
            <form @submit.prevent="save" class="space-y-8">
                
                <!-- Identidade Visual -->
                <div>
                    <h3 class="text-lg font-medium leading-6 text-gray-900 border-b pb-2 mb-4">Identidade Visual</h3>
                    <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                        
                        <!-- Logo -->
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Logo da Plataforma</label>
                            <div class="mt-2 flex items-center space-x-6">
                                <div class="shrink-0">
                                    <img v-if="previewLogo" :src="previewLogo" alt="Logo Preview" class="h-24 w-24 object-contain rounded-md border border-gray-200 bg-gray-50 p-1" />
                                    <div v-else class="h-24 w-24 rounded-md border border-gray-200 bg-gray-50 flex items-center justify-center text-gray-400">
                                        <span class="text-xs">Sem Logo</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block">
                                        <span class="sr-only">Escolher logo</span>
                                        <input type="file" @change="(e) => handleFile(e, 'logo')" accept="image/*" class="block w-full text-sm text-gray-500
                                            file:mr-4 file:py-2 file:px-4
                                            file:rounded-full file:border-0
                                            file:text-sm file:font-semibold
                                            file:bg-blue-50 file:text-blue-700
                                            hover:file:bg-blue-100
                                        "/>
                                    </label>
                                    <p class="mt-1 text-xs text-gray-500">PNG, JPG até 2MB.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Banner Portal -->
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Banner do Portal</label>
                            <div class="mt-2 flex flex-col space-y-2">
                                <div class="shrink-0 w-full h-24 rounded-md border border-gray-200 bg-gray-50 overflow-hidden relative group">
                                    <img v-if="previewBannerPortal" :src="previewBannerPortal" alt="Banner Preview" class="w-full h-full object-cover" />
                                    <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                                        <span class="text-xs">Sem Banner</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block">
                                        <input type="file" @change="(e) => handleFile(e, 'banner_portal')" accept="image/*" class="block w-full text-sm text-gray-500
                                            file:mr-4 file:py-2 file:px-4
                                            file:rounded-full file:border-0
                                            file:text-sm file:font-semibold
                                            file:bg-blue-50 file:text-blue-700
                                            hover:file:bg-blue-100
                                        "/>
                                    </label>
                                    <p class="mt-1 text-xs text-gray-500">Exibido no topo do portal. Max 4MB.</p>
                                </div>
                            </div>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Logo do Rodapé</label>
                            <div class="mt-2 flex items-center space-x-6">
                                <div class="shrink-0">
                                    <img v-if="previewLogoRodape" :src="previewLogoRodape" alt="Logo Rodapé Preview" class="h-24 w-24 object-contain rounded-md border border-gray-200 bg-gray-50 p-1" />
                                    <div v-else class="h-24 w-24 rounded-md border border-gray-200 bg-gray-50 flex items-center justify-center text-gray-400">
                                        <span class="text-xs">Sem Logo</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block">
                                        <span class="sr-only">Escolher logo de rodapé</span>
                                        <input type="file" @change="(e) => handleFile(e, 'logo_rodape')" accept="image/*" class="block w-full text-sm text-gray-500
                                            file:mr-4 file:py-2 file:px-4
                                            file:rounded-full file:border-0
                                            file:text-sm file:font-semibold
                                            file:bg-blue-50 file:text-blue-700
                                            hover:file:bg-blue-100
                                        "/>
                                    </label>
                                    <p class="mt-1 text-xs text-gray-500">Exibido no rodapé do portal. PNG, JPG até 2MB.</p>
                                </div>
                            </div>
                        </div>

                         <!-- Fundo Login -->
                         <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Fundo da Tela de Login</label>
                            <div class="mt-2 flex flex-col space-y-2">
                                <div class="shrink-0 w-full h-24 rounded-md border border-gray-200 bg-gray-50 overflow-hidden relative">
                                    <img v-if="previewFundoLogin" :src="previewFundoLogin" alt="Login BG Preview" class="w-full h-full object-cover" />
                                    <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                                        <span class="text-xs">Sem Fundo</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block">
                                        <input type="file" @change="(e) => handleFile(e, 'fundo_login')" accept="image/*" class="block w-full text-sm text-gray-500
                                            file:mr-4 file:py-2 file:px-4
                                            file:rounded-full file:border-0
                                            file:text-sm file:font-semibold
                                            file:bg-blue-50 file:text-blue-700
                                            hover:file:bg-blue-100
                                        "/>
                                    </label>
                                    <p class="mt-1 text-xs text-gray-500">Imagem de fundo na tela de login. Max 4MB.</p>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Cores e Estilo -->
                <div>
                    <h3 class="text-lg font-medium leading-6 text-gray-900 border-b pb-2 mb-4">Cores e Estilo</h3>
                    <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                        
                        <div class="sm:col-span-2">
                            <label for="cor_primaria" class="block text-sm font-medium text-gray-700">Cor Primária</label>
                            <div class="mt-1 flex rounded-md shadow-sm">
                                <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 sm:text-sm">
                                    <div class="w-6 h-6 rounded border border-gray-200" :style="{ backgroundColor: form.cor_primaria }"></div>
                                </span>
                                <input type="text" v-model="form.cor_primaria" id="cor_primaria" class="flex-1 min-w-0 block w-full px-3 py-2 rounded-none rounded-r-md focus:ring-blue-500 focus:border-blue-500 sm:text-sm border-gray-300">
                                <input type="color" v-model="form.cor_primaria" class="ml-2 h-9 w-9 p-0 border-0 rounded cursor-pointer">
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Cabeçalho, botões principais.</p>
                        </div>

                        <div class="sm:col-span-2">
                            <label for="cor_secundaria" class="block text-sm font-medium text-gray-700">Cor Secundária</label>
                            <div class="mt-1 flex rounded-md shadow-sm">
                                <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 sm:text-sm">
                                    <div class="w-6 h-6 rounded border border-gray-200" :style="{ backgroundColor: form.cor_secundaria }"></div>
                                </span>
                                <input type="text" v-model="form.cor_secundaria" id="cor_secundaria" class="flex-1 min-w-0 block w-full px-3 py-2 rounded-none rounded-r-md focus:ring-blue-500 focus:border-blue-500 sm:text-sm border-gray-300">
                                <input type="color" v-model="form.cor_secundaria" class="ml-2 h-9 w-9 p-0 border-0 rounded cursor-pointer">
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Detalhes, bordas, fundos secundários.</p>
                        </div>

                        <!-- Cores Extras (JSON) -->
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Cor do Menu (Sidebar)</label>
                            <div class="mt-1 flex rounded-md shadow-sm">
                                <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 sm:text-sm">
                                    <div class="w-6 h-6 rounded border border-gray-200" :style="{ backgroundColor: form.configuracao_estilo.sidebar_color }"></div>
                                </span>
                                <input type="text" v-model="form.configuracao_estilo.sidebar_color" class="flex-1 min-w-0 block w-full px-3 py-2 rounded-none rounded-r-md focus:ring-blue-500 focus:border-blue-500 sm:text-sm border-gray-300">
                                <input type="color" v-model="form.configuracao_estilo.sidebar_color" class="ml-2 h-9 w-9 p-0 border-0 rounded cursor-pointer">
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Cor de fundo do menu lateral.</p>
                        </div>

                         <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Texto do Menu</label>
                            <div class="mt-1 flex rounded-md shadow-sm">
                                <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 sm:text-sm">
                                    <div class="w-6 h-6 rounded border border-gray-200" :style="{ backgroundColor: form.configuracao_estilo.sidebar_text_color }"></div>
                                </span>
                                <input type="text" v-model="form.configuracao_estilo.sidebar_text_color" class="flex-1 min-w-0 block w-full px-3 py-2 rounded-none rounded-r-md focus:ring-blue-500 focus:border-blue-500 sm:text-sm border-gray-300">
                                <input type="color" v-model="form.configuracao_estilo.sidebar_text_color" class="ml-2 h-9 w-9 p-0 border-0 rounded cursor-pointer">
                            </div>
                        </div>

                         <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Cor de Links/Destaque</label>
                            <div class="mt-1 flex rounded-md shadow-sm">
                                <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 sm:text-sm">
                                    <div class="w-6 h-6 rounded border border-gray-200" :style="{ backgroundColor: form.configuracao_estilo.link_color }"></div>
                                </span>
                                <input type="text" v-model="form.configuracao_estilo.link_color" class="flex-1 min-w-0 block w-full px-3 py-2 rounded-none rounded-r-md focus:ring-blue-500 focus:border-blue-500 sm:text-sm border-gray-300">
                                <input type="color" v-model="form.configuracao_estilo.link_color" class="ml-2 h-9 w-9 p-0 border-0 rounded cursor-pointer">
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Informações da Instituição -->
                <div>
                    <h3 class="text-lg font-medium leading-6 text-gray-900 border-b pb-2 mb-4">Informações da Organização</h3>
                    <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                        
                        <div class="sm:col-span-4">
                            <label for="nome" class="block text-sm font-medium text-gray-700">Nome da Instituição</label>
                            <div class="mt-1">
                                <input type="text" v-model="form.nome" id="nome" required class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md p-2 border">
                            </div>
                        </div>

                        <div class="sm:col-span-2">
                            <label for="telefone" class="block text-sm font-medium text-gray-700">Telefone</label>
                            <div class="mt-1">
                                <input type="text" v-model="form.telefone" id="telefone" class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md p-2 border" placeholder="(00) 0000-0000">
                            </div>
                        </div>

                        <div class="sm:col-span-3">
                            <label for="email" class="block text-sm font-medium text-gray-700">E-mail de Contato</label>
                            <div class="mt-1">
                                <input type="email" v-model="form.email_contato" id="email" class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md p-2 border">
                            </div>
                        </div>

                        <div class="sm:col-span-6">
                            <label for="endereco" class="block text-sm font-medium text-gray-700">Endereço Completo</label>
                            <div class="mt-1">
                                <input type="text" v-model="form.endereco" id="endereco" class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md p-2 border">
                            </div>
                        </div>

                    </div>
                </div>

                <div>
                    <h3 class="text-lg font-medium leading-6 text-gray-900 border-b pb-2 mb-4">Rodapé do Portal</h3>
                    <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                        <div class="sm:col-span-3">
                            <label class="block text-sm font-medium text-gray-700">Título do Rodapé</label>
                            <div class="mt-1">
                                <input type="text" v-model="form.footer_titulo" class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md p-2 border" placeholder="Secretaria Municipal de Esporte e Lazer">
                            </div>
                        </div>
                        <div class="sm:col-span-3">
                            <label class="block text-sm font-medium text-gray-700">Link de Transparência</label>
                            <div class="mt-1">
                                <input type="url" v-model="form.footer_link_transparencia" class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md p-2 border" placeholder="https://...">
                            </div>
                        </div>
                        <div class="sm:col-span-3">
                            <label class="block text-sm font-medium text-gray-700">Link de Editais</label>
                            <div class="mt-1">
                                <input type="url" v-model="form.footer_link_editais" class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md p-2 border" placeholder="https://...">
                            </div>
                        </div>
                        <div class="sm:col-span-3">
                            <label class="block text-sm font-medium text-gray-700">Link de Contato</label>
                            <div class="mt-1">
                                <input type="url" v-model="form.footer_link_contato" class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md p-2 border" placeholder="https://...">
                            </div>
                        </div>
                        <div class="sm:col-span-3">
                            <label class="block text-sm font-medium text-gray-700">Rede Social - Facebook</label>
                            <div class="mt-1">
                                <input type="url" v-model="form.redes_sociais.facebook" class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md p-2 border" placeholder="https://facebook.com/...">
                            </div>
                        </div>
                        <div class="sm:col-span-3">
                            <label class="block text-sm font-medium text-gray-700">Rede Social - Instagram</label>
                            <div class="mt-1">
                                <input type="url" v-model="form.redes_sociais.instagram" class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md p-2 border" placeholder="https://instagram.com/...">
                            </div>
                        </div>
                        <div class="sm:col-span-3">
                            <label class="block text-sm font-medium text-gray-700">Rede Social - YouTube</label>
                            <div class="mt-1">
                                <input type="url" v-model="form.redes_sociais.youtube" class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md p-2 border" placeholder="https://youtube.com/...">
                            </div>
                        </div>
                        <div class="sm:col-span-3">
                            <label class="block text-sm font-medium text-gray-700">Rede Social - X</label>
                            <div class="mt-1">
                                <input type="url" v-model="form.redes_sociais.x" class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md p-2 border" placeholder="https://x.com/...">
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="text-lg font-medium leading-6 text-gray-900 border-b pb-2 mb-4">Integrações</h3>
                    <div class="border border-gray-200 rounded-xl p-4 bg-gradient-to-br from-white to-gray-50 space-y-5">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                            <div>
                                <h4 class="text-base font-semibold text-gray-900">WhatsApp no servidor</h4>
                                <p class="text-sm text-gray-600">Conecte o número da prefeitura pelo QR Code. A sessão fica no nosso servidor e reconecta sozinha.</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <span
                                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold"
                                    :class="form.integracoes.whatsapp.enabled ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'"
                                >
                                    {{ form.integracoes.whatsapp.enabled ? 'Envios ativos' : 'Envios desligados' }}
                                </span>
                                <button
                                    type="button"
                                    @click="form.integracoes.whatsapp.enabled = !form.integracoes.whatsapp.enabled"
                                    class="relative inline-flex h-7 w-12 items-center rounded-full transition-colors"
                                    :class="form.integracoes.whatsapp.enabled ? 'bg-green-500' : 'bg-gray-300'"
                                >
                                    <span
                                        class="inline-block h-5 w-5 transform rounded-full bg-white transition-transform"
                                        :class="form.integracoes.whatsapp.enabled ? 'translate-x-6' : 'translate-x-1'"
                                    />
                                </button>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold"
                                :class="whatsAppStatusClass"
                            >
                                {{ whatsAppStatusLabel }}
                            </span>
                            <span v-if="form.integracoes.whatsapp.phone" class="text-sm text-gray-700">
                                Número: {{ form.integracoes.whatsapp.phone }}
                            </span>
                        </div>

                        <div v-if="form.integracoes.whatsapp.qr" class="flex flex-col items-start gap-2">
                            <p class="text-sm text-gray-700">Abra o WhatsApp no celular → Aparelhos conectados → Conectar um aparelho e escaneie:</p>
                            <img :src="form.integracoes.whatsapp.qr" alt="QR Code WhatsApp" class="w-56 h-56 rounded-lg border border-gray-200 bg-white p-2">
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <button
                                type="button"
                                @click="connectWhatsApp(false)"
                                :disabled="whatsappBusy"
                                class="inline-flex items-center justify-center px-4 py-2 border border-green-200 rounded-md text-sm font-medium text-green-800 bg-green-50 hover:bg-green-100 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span v-if="connectingWhatsApp">Conectando...</span>
                                <span v-else>Conectar / gerar QR</span>
                            </button>
                            <button
                                type="button"
                                @click="refreshWhatsAppStatus"
                                :disabled="whatsappBusy"
                                class="inline-flex items-center justify-center px-4 py-2 border border-blue-200 rounded-md text-sm font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Atualizar status
                            </button>
                            <button
                                type="button"
                                @click="disconnectWhatsApp"
                                :disabled="whatsappBusy || form.integracoes.whatsapp.status === 'disconnected'"
                                class="inline-flex items-center justify-center px-4 py-2 border border-red-200 rounded-md text-sm font-medium text-red-700 bg-red-50 hover:bg-red-100 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Desconectar
                            </button>
                        </div>
                        <p class="text-xs text-gray-500">Salve as alterações para ligar ou desligar o envio de mensagens. A conexão do aparelho é independente e permanece no servidor.</p>
                    </div>
                </div>

                <div class="pt-5 border-t border-gray-200">
                    <div class="flex justify-end">
                        <button type="button" @click="fetchSettings" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="loading" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50">
                            <span v-if="loading">Salvando...</span>
                            <span v-else>Salvar Alterações</span>
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import api from '@/services/api';
import { useToast } from 'vue-toastification';
import { useTheme } from '@/composables/useTheme';

const toast = useToast();
const { updateTheme } = useTheme();
const loading = ref(false);
const connectingWhatsApp = ref(false);
const whatsappBusy = computed(() => loading.value || connectingWhatsApp.value);
const previewLogo = ref(null);
const previewLogoRodape = ref(null);
const previewBannerPortal = ref(null);
const previewFundoLogin = ref(null);

const form = ref({
    nome: '',
    endereco: '',
    telefone: '',
    email_contato: '',
    cor_primaria: '#000000',
    cor_secundaria: '#ffffff',
    logo: null,
    logo_rodape: null,
    banner_portal: null,
    fundo_login: null,
    footer_titulo: '',
    footer_link_transparencia: '',
    footer_link_editais: '',
    footer_link_contato: '',
    redes_sociais: {
        facebook: '',
        instagram: '',
        youtube: '',
        x: ''
    },
    configuracao_estilo: {
        sidebar_color: '#111827', // gray-900 default
        sidebar_text_color: '#ffffff',
        link_color: '#2563EB' // blue-600 default
    },
    integracoes: {
        whatsapp: {
            enabled: false,
            status: 'disconnected',
            phone: null,
            qr: null
        }
    }
});

const fetchSettings = async () => {
    loading.value = true;
    try {
        const { data } = await api.get('/institucional');
        form.value.nome = data.nome;
        form.value.endereco = data.endereco || '';
        form.value.telefone = data.telefone || '';
        form.value.email_contato = data.email_contato || '';
        form.value.footer_titulo = data.footer_titulo || '';
        form.value.footer_link_transparencia = data.footer_link_transparencia || '';
        form.value.footer_link_editais = data.footer_link_editais || '';
        form.value.footer_link_contato = data.footer_link_contato || '';
        form.value.redes_sociais = {
            facebook: data.redes_sociais?.facebook || '',
            instagram: data.redes_sociais?.instagram || '',
            youtube: data.redes_sociais?.youtube || '',
            x: data.redes_sociais?.x || ''
        };
        form.value.cor_primaria = data.cor_primaria || '#000000';
        form.value.cor_secundaria = data.cor_secundaria || '#ffffff';
        
        // Merge config estilo com defaults
        if (data.configuracao_estilo) {
            form.value.configuracao_estilo = { 
                ...form.value.configuracao_estilo, 
                ...data.configuracao_estilo 
            };
        }

        const whatsApp = data.integracoes?.whatsapp || {};
        applyWhatsAppState(whatsApp, true);

        if (data.logo_url) previewLogo.value = data.logo_url;
        if (data.logo_rodape_url) previewLogoRodape.value = data.logo_rodape_url;
        if (data.banner_portal_url) previewBannerPortal.value = data.banner_portal_url;
        if (data.fundo_login_url) previewFundoLogin.value = data.fundo_login_url;
        updateTheme(data);

    } catch (error) {
        console.error('Erro ao carregar configurações:', error);
        toast.error('Não foi possível carregar as configurações.');
    } finally {
        loading.value = false;
    }
};

const handleFile = (event, field) => {
    const file = event.target.files[0];
    if (file) {
        form.value[field] = file;
        
        const previewUrl = URL.createObjectURL(file);
        if (field === 'logo') previewLogo.value = previewUrl;
        if (field === 'logo_rodape') previewLogoRodape.value = previewUrl;
        if (field === 'banner_portal') previewBannerPortal.value = previewUrl;
        if (field === 'fundo_login') previewFundoLogin.value = previewUrl;
    }
};

const save = async () => {
    loading.value = true;
    try {
        const formData = new FormData();
        formData.append('nome', form.value.nome);
        if (form.value.endereco) formData.append('endereco', form.value.endereco);
        if (form.value.telefone) formData.append('telefone', form.value.telefone);
        if (form.value.email_contato) formData.append('email_contato', form.value.email_contato);
        if (form.value.footer_titulo) formData.append('footer_titulo', form.value.footer_titulo);
        if (form.value.footer_link_transparencia) formData.append('footer_link_transparencia', form.value.footer_link_transparencia);
        if (form.value.footer_link_editais) formData.append('footer_link_editais', form.value.footer_link_editais);
        if (form.value.footer_link_contato) formData.append('footer_link_contato', form.value.footer_link_contato);
        formData.append('cor_primaria', form.value.cor_primaria);
        if (form.value.cor_secundaria) formData.append('cor_secundaria', form.value.cor_secundaria);
        
        // Enviar JSON de estilo
        // Precisamos enviar como string se for multipart/form-data com array/json complexo às vezes,
        // mas o Laravel costuma aceitar se enviarmos os campos indexados ou JSON stringificado se form-data
        // Vamos enviar como JSON stringificado para garantir integridade ou indexado se o backend esperar array
        // O cast 'array' no Laravel espera JSON na coluna do banco, então é melhor enviar um campo 'configuracao_estilo' que seja um array no request.
        // Como FormData é flat, melhor enviar como array indexado: configuracao_estilo[key]
        
        Object.keys(form.value.configuracao_estilo).forEach(key => {
            formData.append(`configuracao_estilo[${key}]`, form.value.configuracao_estilo[key]);
        });

        Object.keys(form.value.redes_sociais).forEach(key => {
            if (form.value.redes_sociais[key]) {
                formData.append(`redes_sociais[${key}]`, form.value.redes_sociais[key]);
            }
        });

        formData.append('integracoes[whatsapp][enabled]', form.value.integracoes.whatsapp.enabled ? '1' : '0');

        if (form.value.logo instanceof File) formData.append('logo', form.value.logo);
        if (form.value.logo_rodape instanceof File) formData.append('logo_rodape', form.value.logo_rodape);
        if (form.value.banner_portal instanceof File) formData.append('banner_portal', form.value.banner_portal);
        if (form.value.fundo_login instanceof File) formData.append('fundo_login', form.value.fundo_login);

        const { data } = await api.post('/institucional', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
        
        toast.success('Configurações salvas com sucesso!');
        if (data?.data) {
            updateTheme(data.data);
        }
        await fetchSettings();
        // Atualizar tema em tempo real
        // O ideal seria usar useTheme aqui para atualizar as variaveis CSS sem reload
        
    } catch (error) {
        console.error('Erro ao salvar:', error);
        if (error.response?.data?.message) {
             toast.error(error.response.data.message);
        } else {
             toast.error('Erro ao salvar as configurações.');
        }
    } finally {
        loading.value = false;
    }
};

const applyWhatsAppState = (whatsApp = {}, syncEnabled = false) => {
    if (syncEnabled && whatsApp.enabled !== undefined) {
        form.value.integracoes.whatsapp.enabled = !!whatsApp.enabled;
    }
    form.value.integracoes.whatsapp.status = whatsApp.status || 'disconnected';
    form.value.integracoes.whatsapp.phone = whatsApp.phone || null;
    form.value.integracoes.whatsapp.qr = whatsApp.qr || null;
    syncWhatsAppPolling();
};

const whatsAppStatusLabel = computed(() => {
    const status = form.value.integracoes.whatsapp.status;
    if (status === 'connected') return 'Conectado';
    if (status === 'qr') return 'Aguardando QR Code';
    if (status === 'connecting' || status === 'reconnecting') return 'Reconectando';
    return 'Desconectado';
});

const whatsAppStatusClass = computed(() => {
    const status = form.value.integracoes.whatsapp.status;
    if (status === 'connected') return 'bg-green-100 text-green-700';
    if (status === 'qr') return 'bg-amber-100 text-amber-800';
    if (status === 'connecting' || status === 'reconnecting') return 'bg-blue-100 text-blue-700';
    return 'bg-gray-100 text-gray-600';
});

let whatsAppPoll = null;

const stopWhatsAppPolling = () => {
    if (whatsAppPoll) {
        clearInterval(whatsAppPoll);
        whatsAppPoll = null;
    }
};

const syncWhatsAppPolling = () => {
    const status = form.value.integracoes.whatsapp.status;
    const shouldPoll = ['qr', 'connecting', 'reconnecting'].includes(status);
    if (shouldPoll && !whatsAppPoll) {
        whatsAppPoll = setInterval(refreshWhatsAppStatus, 2500);
    }
    if (!shouldPoll) {
        stopWhatsAppPolling();
    }
};

const refreshWhatsAppStatus = async () => {
    try {
        const { data } = await api.get('/institucional/integracoes/whatsapp/status');
        applyWhatsAppState(data);
    } catch (error) {
        console.error('Erro ao consultar status do WhatsApp:', error);
    }
};

const connectWhatsApp = async (reset = false) => {
    connectingWhatsApp.value = true;
    try {
        const { data } = await api.post('/institucional/integracoes/whatsapp/conectar', { reset });
        applyWhatsAppState(data);
        toast.success(data.message || 'Conexão iniciada. Escaneie o QR Code se aparecer.');
    } catch (error) {
        const payload = error.response?.data || {};
        applyWhatsAppState(payload);
        toast.error(payload.message || 'Falha ao conectar o WhatsApp.');
    } finally {
        connectingWhatsApp.value = false;
    }
};

const disconnectWhatsApp = async () => {
    connectingWhatsApp.value = true;
    try {
        const { data } = await api.post('/institucional/integracoes/whatsapp/desconectar');
        applyWhatsAppState({
            ...form.value.integracoes.whatsapp,
            status: data.status || 'disconnected',
            phone: null,
            qr: null,
        });
        toast.success(data.message || 'WhatsApp desconectado.');
    } catch (error) {
        const message = error.response?.data?.message || 'Falha ao desconectar o WhatsApp.';
        toast.error(message);
    } finally {
        connectingWhatsApp.value = false;
    }
};

onMounted(() => {
    fetchSettings();
});

onUnmounted(() => {
    stopWhatsAppPolling();
});
</script>
