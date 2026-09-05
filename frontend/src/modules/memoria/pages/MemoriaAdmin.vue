<template>
  <div class="p-4 sm:p-6">
    <div class="mb-6 flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Memória do Esporte</h1>
        <p class="text-gray-500 mt-1">Cadastre fatos, pessoas e fotos. O que estiver publicado aparece no portal da cidade.</p>
      </div>
      <div class="flex flex-wrap gap-2">
        <router-link to="/admin/memoria/marcos/novo" class="bg-blue-600 text-white px-4 py-2.5 rounded-xl font-semibold min-h-11 inline-flex items-center">Novo fato</router-link>
        <router-link to="/admin/memoria/personalidades/nova" class="bg-white border border-gray-200 px-4 py-2.5 rounded-xl font-semibold min-h-11 inline-flex items-center">Nova pessoa</router-link>
        <router-link to="/admin/memoria/acervo/novo" class="bg-white border border-gray-200 px-4 py-2.5 rounded-xl font-semibold min-h-11 inline-flex items-center">Nova mídia</router-link>
      </div>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
      <button
        v-for="card in cards"
        :key="card.tab"
        type="button"
        class="bg-white border rounded-2xl p-4 text-left hover:border-blue-300 transition-colors"
        :class="activeTab === card.tab ? 'border-blue-600 ring-2 ring-blue-100' : 'border-gray-200'"
        @click="activeTab = card.tab"
      >
        <p class="text-xs uppercase tracking-wide text-gray-500">{{ card.label }}</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">{{ card.value }}</p>
        <p v-if="card.hint" class="text-xs text-gray-500 mt-1">{{ card.hint }}</p>
      </button>
    </div>
    <p v-if="stats.orfaos" class="mb-4 text-sm text-amber-800 bg-amber-50 border border-amber-100 rounded-xl px-4 py-3">
      {{ stats.orfaos }} mídia(s) aprovada(s) sem vínculo com fato ou pessoa — aparecem só na galeria.
      <button type="button" class="font-semibold underline ml-1" @click="filtrarOrfaos">Ver órfãs</button>
    </p>
    <p v-if="stats.sem_alt" class="mb-4 text-sm text-slate-700 bg-slate-50 border border-slate-200 rounded-xl px-4 py-3">
      {{ stats.sem_alt }} foto(s) sem texto alternativo — preencha o alt na edição para acessibilidade.
    </p>
    <p v-if="stats.sem_credito" class="mb-4 text-sm text-slate-700 bg-slate-50 border border-slate-200 rounded-xl px-4 py-3">
      {{ stats.sem_credito }} peça(s) publicada(s) sem crédito nem fonte — complete na ficha.
    </p>
    <p v-if="stats.duplicados" class="mb-4 text-sm text-amber-800 bg-amber-50 border border-amber-100 rounded-xl px-4 py-3">
      {{ stats.duplicados }} arquivo(s) com o mesmo conteúdo de outra peça — confira se não é duplicata.
    </p>

    <div class="flex gap-2 overflow-x-auto pb-3 mb-4">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        type="button"
        class="shrink-0 px-4 py-2 rounded-full text-sm font-semibold border min-h-11"
        :class="activeTab === tab.id ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-200'"
        @click="activeTab = tab.id"
      >
        {{ tab.label }}
        <span v-if="tab.id === 'pendentes' && stats.pendentes" class="ml-1 bg-yellow-400 text-blue-900 text-xs px-1.5 py-0.5 rounded-full">{{ stats.pendentes }}</span>
        <span v-if="tab.id === 'identificar' && stats.identificacoes" class="ml-1 bg-yellow-400 text-blue-900 text-xs px-1.5 py-0.5 rounded-full">{{ stats.identificacoes }}</span>
        <span v-if="tab.id === 'correcoes' && stats.correcoes" class="ml-1 bg-yellow-400 text-blue-900 text-xs px-1.5 py-0.5 rounded-full">{{ stats.correcoes }}</span>
      </button>
    </div>

    <div v-if="loading" class="text-center py-12">
      <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600 mx-auto"></div>
    </div>
    <div v-else-if="error" class="bg-red-100 text-red-700 p-4 rounded-xl">{{ error }}</div>

    <div v-else class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
      <div v-if="activeTab === 'marcos'" class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fato histórico</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quando</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">No portal</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="item in marcos" :key="item.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 text-sm font-medium text-gray-900">
                <div class="flex items-center gap-3">
                  <img v-if="item.capa_url" :src="item.capa_url" class="h-12 w-16 rounded-lg object-cover bg-gray-100 shrink-0" alt="" />
                  <div>
                    <p>{{ item.titulo }}</p>
                    <p class="text-xs text-gray-500 font-normal mt-0.5">{{ tipoMarcoLabels[item.tipo] || item.tipo }}</p>
                  </div>
                </div>
              </td>
              <td class="px-4 py-3 text-sm text-gray-600">{{ item.ano || 'Sem ano' }}</td>
              <td class="px-4 py-3 text-sm">
                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold" :class="item.publicado ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'">
                  {{ item.publicado ? 'Publicado' : 'Rascunho' }}
                </span>
                <span v-if="item.destaque" class="ml-1 text-[10px] uppercase bg-yellow-100 text-yellow-800 px-2 py-0.5 rounded-full">Home</span>
              </td>
              <td class="px-4 py-3 text-sm text-right space-x-3 whitespace-nowrap">
                <router-link :to="`/admin/memoria/marcos/${item.id}/editar`" class="text-blue-600 font-medium">Editar</router-link>
                <button class="text-gray-600 font-medium" @click="duplicar('marco', item.id)">Duplicar</button>
                <button class="text-red-600 font-medium" @click="remove('marco', item.id)">Excluir</button>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="marcos.length === 0" class="px-6 py-12 text-center">
          <p class="text-gray-700 font-semibold">A linha do tempo ainda está vazia</p>
          <p class="text-sm text-gray-500 mt-1">Comece por um fato que a cidade inteira conhece.</p>
          <router-link to="/admin/memoria/marcos/novo" class="inline-flex mt-4 bg-blue-600 text-white px-4 py-2 rounded-xl font-semibold">Cadastrar primeiro fato</router-link>
        </div>
      </div>

      <div v-else-if="activeTab === 'pessoas'" class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pessoa</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Atuação</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="item in personalidades" :key="item.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 text-sm font-medium">
                <div class="flex items-center gap-3">
                  <img v-if="item.foto_url" :src="item.foto_url" class="h-10 w-10 rounded-full object-cover bg-gray-100" alt="" />
                  <span>{{ item.nome }}</span>
                </div>
              </td>
              <td class="px-4 py-3 text-sm text-gray-600">{{ papelLabels[item.papel] || item.papel }}</td>
              <td class="px-4 py-3 text-sm text-right space-x-3">
                <router-link :to="`/admin/memoria/personalidades/${item.id}/editar`" class="text-blue-600 font-medium">Editar</router-link>
                <button class="text-gray-600 font-medium" @click="duplicar('pessoa', item.id)">Duplicar</button>
                <button class="text-red-600 font-medium" @click="remove('pessoa', item.id)">Excluir</button>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="personalidades.length === 0" class="px-6 py-12 text-center text-gray-500">
          Nenhuma personalidade ainda.
          <router-link to="/admin/memoria/personalidades/nova" class="block mt-3 text-blue-700 font-semibold">Cadastrar ídolo ou dirigente</router-link>
        </div>
      </div>

      <div v-else-if="activeTab === 'acervo'" class="overflow-x-auto">
        <div v-if="soOrfaos" class="px-4 py-3 text-sm bg-amber-50 border-b text-amber-900 flex justify-between gap-3">
          <span>Mostrando só mídias sem fato nem pessoa.</span>
          <button type="button" class="font-semibold underline" @click="limparFiltroOrfaos">Ver todas</button>
        </div>
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mídia</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Origem</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="item in acervo" :key="item.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 text-sm font-medium">
                <div class="flex items-center gap-3">
                  <img v-if="item.tipo === 'foto' && item.arquivo_url" :src="item.arquivo_url" class="h-12 w-16 rounded-lg object-cover bg-gray-100" :alt="item.alt_text || item.titulo" />
                  <div>
                    <span>{{ item.titulo }}</span>
                    <div class="flex flex-wrap gap-1 mt-1">
                      <span v-if="!item.marco_id && !item.personalidade_id" class="text-[10px] uppercase font-bold tracking-wide bg-amber-100 text-amber-800 px-1.5 py-0.5 rounded">Órfã</span>
                      <span v-if="item.tipo === 'foto' && !item.alt_text" class="text-[10px] uppercase font-bold tracking-wide bg-slate-200 text-slate-700 px-1.5 py-0.5 rounded">Sem alt</span>
                    </div>
                  </div>
                </div>
              </td>
              <td class="px-4 py-3 text-sm">{{ tipoAcervoLabels[item.tipo] || item.tipo }}</td>
              <td class="px-4 py-3 text-sm">{{ originLabels[item.origem] || item.origem }}</td>
              <td class="px-4 py-3 text-sm text-right space-x-3">
                <router-link v-if="item.publicado && item.slug" :to="`/memoria/acervo/${item.slug}`" class="text-slate-600 font-medium" target="_blank">Ver no portal</router-link>
                <router-link :to="`/admin/memoria/acervo/${item.id}/editar`" class="text-blue-600 font-medium">Editar</router-link>
                <button class="text-red-600 font-medium" @click="remove('acervo', item.id)">Excluir</button>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="acervo.length === 0" class="px-6 py-12 text-center text-gray-500">
          {{ soOrfaos ? 'Nenhuma mídia órfã no momento.' : 'O acervo ainda não tem itens publicados.' }}
          <router-link v-if="!soOrfaos" to="/admin/memoria/acervo/novo" class="block mt-3 text-blue-700 font-semibold">Enviar primeira mídia</router-link>
        </div>
      </div>

      <div v-else-if="activeTab === 'pendentes'" class="divide-y">
        <div v-if="pendentes.length" class="p-4 flex flex-wrap gap-2 bg-slate-50 border-b">
          <button type="button" class="px-3 py-2 rounded-xl bg-green-600 text-white text-sm font-semibold" :disabled="!selectedIds.length" @click="lote('aprovar')">Aprovar selecionados ({{ selectedIds.length }})</button>
          <button type="button" class="px-3 py-2 rounded-xl bg-red-50 text-red-700 text-sm font-semibold" :disabled="!selectedIds.length" @click="lote('rejeitar')">Recusar selecionados</button>
        </div>
        <div v-if="pendentes.length === 0" class="px-6 py-12 text-center text-gray-500">
        Nenhuma peça na fila. Envios da cidade e itens pedindo complemento aparecem aqui.
        </div>
        <article v-for="item in pendentes" :key="item.id" class="p-4 sm:p-5 flex flex-col md:flex-row gap-4">
          <label class="md:pt-2">
            <input type="checkbox" class="rounded border-gray-300" :value="item.id" v-model="selectedIds" />
          </label>
          <img v-if="item.tipo === 'foto' && item.arquivo_url" :src="item.arquivo_url" class="w-full md:w-44 h-32 object-cover rounded-xl bg-gray-100" alt="" />
          <div class="flex-1 min-w-0">
            <p class="text-xs font-bold uppercase" :class="item.status === 'complemento' ? 'text-blue-700' : 'text-amber-700'">
              {{ item.status === 'complemento' ? 'Precisa de complemento' : 'Aguardando curadoria' }}
            </p>
            <h3 class="font-semibold text-gray-900 text-lg">{{ item.titulo }}</h3>
            <p class="text-sm text-gray-600 mt-1 whitespace-pre-line">{{ item.descricao || 'Sem descrição da pessoa que enviou.' }}</p>
            <dl class="mt-3 grid sm:grid-cols-2 gap-1 text-xs text-gray-500">
              <div><span class="font-semibold text-gray-700">Quem enviou:</span> {{ item.doador_nome }}</div>
              <div v-if="item.doador_telefone"><span class="font-semibold text-gray-700">WhatsApp/telefone:</span> {{ item.doador_telefone }}</div>
              <div v-if="item.doador_email"><span class="font-semibold text-gray-700">E-mail:</span> {{ item.doador_email }}</div>
              <div v-if="item.ano"><span class="font-semibold text-gray-700">Ano:</span> {{ item.ano }}</div>
              <div v-if="item.pessoas_envolvidas"><span class="font-semibold text-gray-700">Pessoas:</span> {{ item.pessoas_envolvidas }}</div>
              <div v-if="item.fonte"><span class="font-semibold text-gray-700">Fonte:</span> {{ item.fonte }}</div>
            </dl>
            <div v-if="rejectingId === item.id" class="mt-3 space-y-2">
              <textarea v-model="rejectReason" rows="2" class="w-full rounded-xl border-gray-300 text-sm" placeholder="Motivo visível só internamente (opcional)" />
              <div class="flex gap-2">
                <button type="button" class="bg-red-600 text-white px-3 py-2 rounded-xl text-sm font-semibold" @click="confirmReject(item)">Confirmar rejeição</button>
                <button type="button" class="px-3 py-2 rounded-xl text-sm border" @click="rejectingId = null">Cancelar</button>
              </div>
            </div>
            <div v-else class="flex flex-wrap gap-2 mt-4">
              <button class="bg-green-600 text-white px-4 py-2 rounded-xl text-sm font-semibold min-h-11" @click="aprovar(item)">Aprovar e publicar</button>
              <button class="bg-white border px-4 py-2 rounded-xl text-sm font-semibold min-h-11" @click="pedirComplemento(item)">Pedir complemento</button>
              <router-link :to="`/admin/memoria/acervo/${item.id}/editar`" class="px-4 py-2 rounded-xl text-sm font-semibold border min-h-11 inline-flex items-center">Completar ficha</router-link>
              <button class="bg-red-50 text-red-700 px-4 py-2 rounded-xl text-sm font-semibold min-h-11" @click="startReject(item)">Recusar</button>
              <a v-if="item.arquivo_url" :href="item.arquivo_url" target="_blank" class="px-4 py-2 rounded-xl text-sm font-semibold border min-h-11 inline-flex items-center">Abrir arquivo</a>
            </div>
          </div>
        </article>
      </div>

      <div v-else-if="activeTab === 'locais'" class="p-4 sm:p-5 space-y-5">
        <p class="text-sm text-gray-500">Locais aparecem nos filtros da galeria, na página pública do espaço e no mapa. Informe latitude e longitude para o ponto aparecer no mapa.</p>
        <form class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3 items-end bg-gray-50 p-4 rounded-2xl border" @submit.prevent="salvarLocal">
          <MemoriaField label="Nome do espaço" required>
            <input v-model="localForm.nome" required class="w-full rounded-xl border-gray-300 min-h-11" placeholder="Ginásio Municipal" />
          </MemoriaField>
          <MemoriaField label="Slug da URL" hint="Opcional. Gerado a partir do nome.">
            <input v-model="localForm.slug" class="w-full rounded-xl border-gray-300 min-h-11" placeholder="ginasio-municipal" />
          </MemoriaField>
          <MemoriaField label="Tipo">
            <MemoriaChoicePicker
              v-model="localForm.tipo"
              grupo="local_tipo"
              variant="select"
              :defaults="tipoLocalOptions"
              :hidden-ids="['outro']"
              add-label="Adicionar tipo"
              placeholder="Ex.: Campo, piscina, ciclovia..."
            />
          </MemoriaField>
          <MemoriaField label="Endereço ou bairro" hint="Opcional.">
            <input v-model="localForm.endereco" class="w-full rounded-xl border-gray-300 min-h-11" placeholder="Centro, rua..." />
          </MemoriaField>
          <MemoriaField label="Latitude" hint="Para o mapa público.">
            <input v-model="localForm.lat" type="number" step="0.0000001" class="w-full rounded-xl border-gray-300 min-h-11" placeholder="-23.55" />
          </MemoriaField>
          <MemoriaField label="Longitude">
            <input v-model="localForm.lng" type="number" step="0.0000001" class="w-full rounded-xl border-gray-300 min-h-11" placeholder="-46.63" />
          </MemoriaField>
          <MemoriaField label="Foto de capa">
            <input type="file" accept="image/*" class="w-full text-sm" @change="onLocalFile($event, 'foto')" />
          </MemoriaField>
          <MemoriaField label="Foto de antes" hint="Comparativo na página pública.">
            <input type="file" accept="image/*" class="w-full text-sm" @change="onLocalFile($event, 'foto_antes')" />
          </MemoriaField>
          <MemoriaField label="Foto de depois">
            <input type="file" accept="image/*" class="w-full text-sm" @change="onLocalFile($event, 'foto_depois')" />
          </MemoriaField>
          <MemoriaField label="Ano da foto de antes">
            <input v-model="localForm.ano_antes" type="number" min="1900" class="w-full rounded-xl border-gray-300 min-h-11" />
          </MemoriaField>
          <MemoriaField label="Ano da foto de depois">
            <input v-model="localForm.ano_depois" type="number" min="1900" class="w-full rounded-xl border-gray-300 min-h-11" />
          </MemoriaField>
          <div class="flex gap-2">
            <button class="bg-blue-600 text-white px-4 py-2.5 rounded-xl font-semibold min-h-11 flex-1">{{ editingLocalId ? 'Salvar local' : 'Adicionar local' }}</button>
            <button v-if="editingLocalId" type="button" class="px-4 py-2.5 rounded-xl border font-semibold min-h-11" @click="resetLocalForm">Cancelar</button>
          </div>
        </form>
        <MemoriaQrCard
          v-if="localQrPath"
          title="QR no local"
          hint="Imprima e afixe no espaço. Quem apontar a câmera abre esta página da Memória."
          :path="localQrPath"
          :unpublished="localUnpublished"
        />
        <div v-if="editingLocalId" class="bg-white border rounded-2xl p-4 space-y-3">
          <p class="text-sm font-semibold text-gray-800">Momentos deste espaço</p>
          <p class="text-xs text-gray-500">Várias épocas, não só um par antes/depois. A página pública monta o comparativo com o mais antigo e o mais recente.</p>
          <form class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end" @submit.prevent="salvarMomento">
            <MemoriaField label="Ano">
              <input v-model="momentoForm.ano" type="number" min="1900" class="w-full rounded-xl border-gray-300 min-h-11" />
            </MemoriaField>
            <MemoriaField label="Título">
              <input v-model="momentoForm.titulo" class="w-full rounded-xl border-gray-300 min-h-11" placeholder="Inauguração, reforma..." />
            </MemoriaField>
            <MemoriaField label="Foto">
              <input type="file" accept="image/*" class="w-full text-sm" @change="onMomentoFile" />
            </MemoriaField>
            <button class="bg-slate-900 text-white px-4 py-2.5 rounded-xl font-semibold min-h-11">Incluir momento</button>
          </form>
          <ul v-if="momentosDoLocal.length" class="divide-y">
            <li v-for="m in momentosDoLocal" :key="m.id" class="py-2 flex justify-between gap-3 items-center">
              <div class="flex items-center gap-3 min-w-0">
                <img v-if="m.foto_url" :src="m.foto_url" class="h-12 w-16 rounded-lg object-cover" alt="" />
                <p class="text-sm truncate">{{ m.ano || 'Sem ano' }} · {{ m.titulo || 'Momento' }}</p>
              </div>
              <button type="button" class="text-red-600 text-sm font-medium shrink-0" @click="remove('momento', m.id)">Excluir</button>
            </li>
          </ul>
        </div>
        <ul v-if="locais.length" class="divide-y">
          <li v-for="item in locais" :key="item.id" class="py-3 flex justify-between gap-3 items-center">
            <div>
              <p class="font-medium">{{ item.nome }}</p>
              <p class="text-xs text-gray-500">
                {{ tipoLocalLabels[item.tipo] || item.tipo }}
                <span v-if="item.endereco"> · {{ item.endereco }}</span>
                <span v-if="item.lat && item.lng"> · no mapa</span>
              </p>
            </div>
            <div class="flex gap-3 shrink-0">
              <button class="text-blue-600 text-sm font-medium" @click="editarLocal(item)">Editar</button>
              <button class="text-red-600 text-sm font-medium" @click="remove('local', item.id)">Excluir</button>
            </div>
          </li>
        </ul>
        <p v-else class="text-sm text-gray-500">Nenhum local cadastrado ainda.</p>
      </div>

      <div v-else-if="activeTab === 'equipes'" class="p-4 sm:p-5 space-y-5">
        <p class="text-sm text-gray-500">Times históricos do memorial — não confundir com as equipes do campeonato atual.</p>
        <form class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3 items-end bg-gray-50 p-4 rounded-2xl border" @submit.prevent="salvarEquipe">
          <MemoriaField label="Nome" required>
            <input v-model="equipeForm.nome" required class="w-full rounded-xl border-gray-300 min-h-11" placeholder="Comercial Futebol Clube" />
          </MemoriaField>
          <MemoriaField label="Apelido">
            <input v-model="equipeForm.apelido" class="w-full rounded-xl border-gray-300 min-h-11" placeholder="Leão da Vila" />
          </MemoriaField>
          <MemoriaField label="Nome anterior">
            <input v-model="equipeForm.nome_anterior" class="w-full rounded-xl border-gray-300 min-h-11" />
          </MemoriaField>
          <MemoriaField label="Modalidade">
            <select v-model="equipeForm.modalidade_id" class="w-full rounded-xl border-gray-300 min-h-11">
              <option value="">Não se aplica</option>
              <option v-for="m in modalidades" :key="m.id" :value="String(m.id)">{{ m.nome }}</option>
            </select>
          </MemoriaField>
          <MemoriaField label="Ano de início">
            <input v-model="equipeForm.ano_inicio" type="number" min="1900" class="w-full rounded-xl border-gray-300 min-h-11" />
          </MemoriaField>
          <MemoriaField label="Cidade / bairro">
            <input v-model="equipeForm.cidade" class="w-full rounded-xl border-gray-300 min-h-11" />
          </MemoriaField>
          <MemoriaField label="Resumo" class="sm:col-span-2">
            <input v-model="equipeForm.descricao" class="w-full rounded-xl border-gray-300 min-h-11" />
          </MemoriaField>
          <label class="flex items-center gap-2 text-sm font-medium min-h-11">
            <input v-model="equipeForm.publicado" type="checkbox" class="rounded border-gray-300" />
            Publicar no portal
          </label>
          <button class="bg-blue-600 text-white px-4 py-2.5 rounded-xl font-semibold min-h-11">{{ editingEquipeId ? 'Salvar equipe' : 'Adicionar equipe' }}</button>
        </form>
        <MemoriaQrCard
          v-if="equipeQrPath"
          title="QR da equipe"
          hint="Imprima e afixe no clube ou no ginásio. Quem apontar a câmera abre a página histórica desta equipe."
          :path="equipeQrPath"
          :unpublished="editingEquipeId && !equipeForm.publicado"
        />
        <ul v-if="equipes.length" class="divide-y">
          <li v-for="item in equipes" :key="item.id" class="py-3 flex justify-between gap-3 items-center">
            <div>
              <p class="font-medium">{{ item.nome }} <span v-if="item.apelido" class="text-gray-500 font-normal">· {{ item.apelido }}</span></p>
              <p class="text-xs text-gray-500">{{ item.ano_inicio || 'Sem ano' }} · {{ item.modalidade?.nome || 'Várias modalidades' }}</p>
            </div>
            <div class="flex gap-3 shrink-0">
              <button class="text-blue-600 text-sm font-medium" @click="editarEquipe(item)">Editar</button>
              <button class="text-red-600 text-sm font-medium" @click="remove('equipe', item.id)">Excluir</button>
            </div>
          </li>
        </ul>
        <p v-else class="text-sm text-gray-500">Nenhuma equipe histórica cadastrada.</p>
      </div>

      <div v-else-if="activeTab === 'identificar'" class="divide-y">
        <p v-if="!identificacoes.length" class="p-6 text-sm text-gray-500">Nenhuma sugestão da cidade esperando conferência.</p>
        <article v-for="item in identificacoes" :key="item.id" class="p-4 sm:p-5 flex flex-col md:flex-row gap-4">
          <img v-if="item.acervo?.arquivo_url" :src="item.acervo.arquivo_url" class="w-full md:w-40 h-28 object-cover rounded-xl bg-gray-100" alt="" />
          <div class="flex-1 min-w-0">
            <p class="text-xs font-bold uppercase text-amber-700">Sugestão da cidade</p>
            <h3 class="font-semibold text-lg">{{ item.nome_sugerido }}</h3>
            <p class="text-sm text-gray-600">Foto: {{ item.acervo?.titulo }}</p>
            <p v-if="item.comentario" class="text-sm text-gray-500 mt-1">{{ item.comentario }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ item.doador_nome || 'Anônimo' }}</p>
            <select v-model="item._personalidade" class="mt-3 w-full max-w-sm rounded-xl border-gray-300 min-h-11 text-sm">
              <option value="">Não ligar a um perfil do Hall</option>
              <option v-for="p in personalidades" :key="p.id" :value="String(p.id)">{{ p.nome }}</option>
            </select>
            <label class="mt-2 flex items-center gap-2 text-sm">
              <input v-model="item._fechar" type="checkbox" class="rounded border-gray-300" />
              Tirar esta foto da página “Quem é essa pessoa?”
            </label>
            <div class="flex flex-wrap gap-2 mt-3">
              <button class="bg-green-600 text-white px-4 py-2 rounded-xl text-sm font-semibold min-h-11" @click="aprovarIdentificacao(item)">Aprovar nome</button>
              <button class="bg-red-50 text-red-700 px-4 py-2 rounded-xl text-sm font-semibold min-h-11" @click="rejeitarIdentificacao(item)">Recusar</button>
            </div>
          </div>
        </article>
      </div>

      <div v-else-if="activeTab === 'correcoes'" class="divide-y">
        <p v-if="!correcoes.length" class="p-6 text-sm text-gray-500">Nenhuma correção da cidade esperando conferência.</p>
        <article v-for="item in correcoes" :key="item.id" class="p-4 sm:p-5">
          <p class="text-xs font-bold uppercase text-amber-700">{{ item.tipo }}</p>
          <h3 class="font-semibold">{{ item.acervo?.titulo || 'Peça do acervo' }}</h3>
          <p v-if="item.valor" class="text-sm text-gray-700 mt-1">{{ item.valor }}</p>
          <p v-if="item.comentario" class="text-sm text-gray-500 mt-1">{{ item.comentario }}</p>
          <p class="text-xs text-gray-500 mt-1">{{ item.doador_nome || 'Anônimo' }}</p>
          <div class="flex flex-wrap gap-2 mt-3">
            <button class="bg-green-600 text-white px-4 py-2 rounded-xl text-sm font-semibold min-h-11" @click="aprovarCorrecao(item)">Aplicar</button>
            <button class="bg-red-50 text-red-700 px-4 py-2 rounded-xl text-sm font-semibold min-h-11" @click="rejeitarCorrecao(item)">Recusar</button>
          </div>
        </article>
      </div>

      <div v-else-if="activeTab === 'recordes'" class="p-4 sm:p-5 space-y-5">
        <p class="text-sm text-gray-500">Marcas históricas da cidade. A artilharia da temporada entra sozinha a partir dos campeonatos.</p>
        <form class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3 items-end bg-gray-50 p-4 rounded-2xl border" @submit.prevent="salvarRecorde">
          <MemoriaField label="Título" required>
            <input v-model="recordeForm.titulo" required class="w-full rounded-xl border-gray-300 min-h-11" placeholder="Recorde municipal dos 800 m" />
          </MemoriaField>
          <MemoriaField label="Categoria">
            <select v-model="recordeForm.categoria" class="w-full rounded-xl border-gray-300 min-h-11">
              <option v-for="(label, id) in categoriaRecordeLabels" :key="id" :value="id">{{ label }}</option>
            </select>
          </MemoriaField>
          <MemoriaField label="Valor">
            <input v-model="recordeForm.valor" class="w-full rounded-xl border-gray-300 min-h-11" placeholder="2:08.4" />
          </MemoriaField>
          <MemoriaField label="Unidade">
            <input v-model="recordeForm.unidade" class="w-full rounded-xl border-gray-300 min-h-11" placeholder="min, gols, m..." />
          </MemoriaField>
          <MemoriaField label="Ano">
            <input v-model="recordeForm.ano" type="number" min="1900" class="w-full rounded-xl border-gray-300 min-h-11" />
          </MemoriaField>
          <MemoriaField label="Atleta (texto)">
            <input v-model="recordeForm.atleta_nome" class="w-full rounded-xl border-gray-300 min-h-11" />
          </MemoriaField>
          <MemoriaField label="Personalidade do Hall">
            <select v-model="recordeForm.personalidade_id" class="w-full rounded-xl border-gray-300 min-h-11">
              <option value="">Nenhuma</option>
              <option v-for="p in personalidades" :key="p.id" :value="String(p.id)">{{ p.nome }}</option>
            </select>
          </MemoriaField>
          <MemoriaField label="Campeonato atual">
            <select v-model="recordeForm.campeonato_id" class="w-full rounded-xl border-gray-300 min-h-11">
              <option value="">Nenhum</option>
              <option v-for="c in campeonatos" :key="c.id" :value="String(c.id)">{{ c.nome }}</option>
            </select>
          </MemoriaField>
          <MemoriaField label="Fato da timeline">
            <select v-model="recordeForm.marco_id" class="w-full rounded-xl border-gray-300 min-h-11">
              <option value="">Nenhum</option>
              <option v-for="m in marcos" :key="m.id" :value="String(m.id)">{{ m.ano }} · {{ m.titulo }}</option>
            </select>
          </MemoriaField>
          <label class="flex items-center gap-2 text-sm font-medium min-h-11">
            <input v-model="recordeForm.vigente" type="checkbox" class="rounded border-gray-300" />
            Ainda vigente
          </label>
          <button class="bg-blue-600 text-white px-4 py-2.5 rounded-xl font-semibold min-h-11">{{ editingRecordeId ? 'Salvar recorde' : 'Adicionar recorde' }}</button>
        </form>
        <ul v-if="recordes.length" class="divide-y">
          <li v-for="item in recordes" :key="item.id" class="py-3 flex justify-between gap-3 items-center">
            <div>
              <p class="font-medium">{{ item.titulo }}</p>
              <p class="text-xs text-gray-500">{{ item.atleta_nome || item.personalidade?.nome }} · {{ item.valor }} {{ item.unidade }} · {{ item.ano }}</p>
            </div>
            <div class="flex gap-3 shrink-0">
              <button class="text-blue-600 text-sm font-medium" @click="editarRecorde(item)">Editar</button>
              <button class="text-red-600 text-sm font-medium" @click="remove('recorde', item.id)">Excluir</button>
            </div>
          </li>
        </ul>
        <p v-else class="text-sm text-gray-500">Nenhum recorde histórico cadastrado.</p>
      </div>

      <div v-else class="divide-y">
        <p v-if="!auditoria.length" class="p-6 text-sm text-gray-500">Ainda não há alterações registradas nesta curadoria.</p>
        <article v-for="log in auditoria" :key="log.id" class="px-4 py-3 text-sm">
          <p class="font-medium text-gray-900">{{ log.acao }} · {{ log.entidade_tipo }}<span v-if="log.entidade_id"> #{{ log.entidade_id }}</span></p>
          <p v-if="log.resumo" class="text-gray-700 text-sm">{{ log.resumo }}</p>
          <p class="text-gray-500 text-xs mt-0.5">{{ log.user?.name || 'Sistema' }} · {{ formatDate(log.created_at) }}</p>
        </article>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import memoriaService from '../services/memoriaService';
import MemoriaField from '../components/MemoriaField.vue';
import MemoriaQrCard from '../components/MemoriaQrCard.vue';
import MemoriaChoicePicker from '../components/MemoriaChoicePicker.vue';
import { originLabels, papelLabels, tipoAcervoLabels, tipoLocalLabels, tipoMarcoLabels, categoriaRecordeLabels } from '../utils/memoriaLabels';
import campeonatoService from '@/modules/campeonatos/services/campeonatoService';
import modalidadeService from '@/modules/modalidades/services/modalidadeService';

const emptyRecordeForm = () => ({
  titulo: '',
  categoria: 'artilharia',
  valor: '',
  unidade: '',
  ano: '',
  atleta_nome: '',
  personalidade_id: '',
  campeonato_id: '',
  marco_id: '',
  vigente: true,
});

const emptyLocalForm = () => ({
  nome: '',
  slug: '',
  tipo: 'ginasio',
  endereco: '',
  lat: '',
  lng: '',
  ano_antes: '',
  ano_depois: '',
});

const emptyMomentoForm = () => ({
  ano: '',
  titulo: '',
});

const emptyEquipeForm = () => ({
  nome: '',
  apelido: '',
  nome_anterior: '',
  modalidade_id: '',
  ano_inicio: '',
  cidade: '',
  descricao: '',
  publicado: true,
});

const tabs = [
  { id: 'marcos', label: 'Linha do tempo' },
  { id: 'pessoas', label: 'Pessoas' },
  { id: 'acervo', label: 'Acervo' },
  { id: 'pendentes', label: 'Fila de curadoria' },
  { id: 'locais', label: 'Locais' },
  { id: 'equipes', label: 'Equipes' },
  { id: 'identificar', label: 'Quem é?' },
  { id: 'correcoes', label: 'Correções' },
  { id: 'recordes', label: 'Recordes' },
  { id: 'auditoria', label: 'Histórico' },
];

const tipoLocalOptions = [
  { id: 'ginasio', label: 'Ginásio' },
  { id: 'estadio', label: 'Estádio' },
  { id: 'quadra', label: 'Quadra' },
  { id: 'pista', label: 'Pista' },
  { id: 'outro', label: 'Outro' },
];

const activeTab = ref('marcos');
const loading = ref(true);
const error = ref('');
const stats = ref({});
const marcos = ref([]);
const personalidades = ref([]);
const acervo = ref([]);
const pendentes = ref([]);
const locais = ref([]);
const equipes = ref([]);
const equipeForm = ref(emptyEquipeForm());
const editingEquipeId = ref(null);
const correcoes = ref([]);
const modalidades = ref([]);
const localForm = ref(emptyLocalForm());
const localFiles = ref({});
const editingLocalId = ref(null);
const momentoForm = ref(emptyMomentoForm());
const momentoFile = ref(null);
const identificacoes = ref([]);
const recordes = ref([]);
const campeonatos = ref([]);
const recordeForm = ref(emptyRecordeForm());
const editingRecordeId = ref(null);
const localQrPath = computed(() => (
  editingLocalId.value && localForm.value.slug
    ? `/memoria/locais/${localForm.value.slug}`
    : ''
));
const localUnpublished = computed(() => {
  if (!editingLocalId.value) return false;
  const item = locais.value.find((l) => l.id === editingLocalId.value);
  return item?.publicado === false;
});
const equipeQrPath = computed(() => {
  if (!editingEquipeId.value) return '';
  const item = equipes.value.find((e) => e.id === editingEquipeId.value);
  return item?.slug ? `/memoria/equipes/${item.slug}` : '';
});
const rejectingId = ref(null);
const rejectReason = ref('');

const router = useRouter();
const selectedIds = ref([]);
const auditoria = ref([]);
const soOrfaos = ref(false);

const cards = computed(() => [
  { tab: 'marcos', label: 'Fatos', value: stats.value.marcos || 0, hint: `${stats.value.marcos_publicados || 0} no ar · ${stats.value.marcos_rascunho || 0} rascunho` },
  { tab: 'pessoas', label: 'Pessoas', value: stats.value.personalidades || 0, hint: `${stats.value.personalidades_publicadas || 0} no ar · ${stats.value.personalidades_rascunho || 0} rascunho` },
  { tab: 'acervo', label: 'Mídias', value: stats.value.acervo || 0, hint: stats.value.orfaos ? `${stats.value.orfaos} órfã(s)` : '' },
  { tab: 'pendentes', label: 'Fila', value: stats.value.pendentes || 0, hint: stats.value.complemento ? `${stats.value.complemento} pedindo complemento` : '' },
  { tab: 'locais', label: 'Locais', value: stats.value.locais || 0 },
  { tab: 'equipes', label: 'Equipes', value: stats.value.equipes || 0 },
  { tab: 'identificar', label: 'Quem é?', value: stats.value.identificacoes || 0 },
  { tab: 'correcoes', label: 'Correções', value: stats.value.correcoes || 0 },
  { tab: 'recordes', label: 'Recordes', value: stats.value.recordes || 0 },
]);

const momentosDoLocal = computed(() => {
  const local = locais.value.find((l) => l.id === editingLocalId.value);
  return local?.momentos || [];
});

const formatDate = (iso) => {
  if (!iso) return '';
  try {
    return new Date(iso).toLocaleString('pt-BR');
  } catch {
    return iso;
  }
};

const load = async () => {
  loading.value = true;
  error.value = '';
  try {
    const [dash, m, p, a, pend, l, logs, ids, rec, camps, eqs, cors, mods] = await Promise.all([
      memoriaService.dashboard(),
      memoriaService.marcos(),
      memoriaService.personalidades(),
      memoriaService.acervo({ per_page: 30, ...(soOrfaos.value ? { orfaos: 1 } : {}) }),
      memoriaService.acervo({ status: 'fila', per_page: 50 }),
      memoriaService.locais(),
      memoriaService.auditoria(),
      memoriaService.identificacoes(),
      memoriaService.recordes(),
      campeonatoService.getAll({ per_page: 50 }).catch(() => ({ data: [] })),
      memoriaService.equipes().catch(() => ({ data: [] })),
      memoriaService.correcoes().catch(() => ({ data: [] })),
      modalidadeService.getAll().catch(() => ({ data: [] })),
    ]);
    stats.value = dash.data || {};
    marcos.value = m.data || [];
    personalidades.value = p.data || [];
    acervo.value = a.data?.data || a.data || [];
    pendentes.value = pend.data?.data || pend.data || [];
    locais.value = l.data || [];
    auditoria.value = logs.data || [];
    identificacoes.value = (ids.data || []).map((item) => ({
      ...item,
      _personalidade: item.personalidade_id ? String(item.personalidade_id) : '',
      _fechar: false,
    }));
    recordes.value = rec.data || [];
    campeonatos.value = camps.data?.data || camps.data || [];
    equipes.value = eqs.data || [];
    correcoes.value = cors.data || [];
    modalidades.value = mods.data?.data || mods.data || [];
    selectedIds.value = [];
  } catch (e) {
    error.value = 'Não foi possível carregar a Memória.';
  } finally {
    loading.value = false;
  }
};

const filtrarOrfaos = async () => {
  soOrfaos.value = true;
  activeTab.value = 'acervo';
  await load();
};

const limparFiltroOrfaos = async () => {
  soOrfaos.value = false;
  await load();
};

const duplicar = async (tipo, id) => {
  try {
    if (tipo === 'marco') {
      const { data } = await memoriaService.duplicarMarco(id);
      router.push(`/admin/memoria/marcos/${data.id}/editar`);
    } else {
      const { data } = await memoriaService.duplicarPersonalidade(id);
      router.push(`/admin/memoria/personalidades/${data.id}/editar`);
    }
  } catch (e) {
    alert('Não foi possível duplicar.');
  }
};

const lote = async (acao) => {
  if (!selectedIds.value.length) return;
  let motivo = '';
  if (acao === 'rejeitar') {
    motivo = prompt('Motivo da recusa (fica registrado na curadoria):') || '';
  }
  try {
    await memoriaService.lote({ ids: selectedIds.value, acao, motivo_rejeicao: motivo });
    await load();
  } catch (e) {
    alert('Não foi possível aplicar a ação em lote.');
  }
};

const remove = async (tipo, id) => {
  if (!confirm('Excluir este registro? Essa ação não publica nada no portal, só remove do cadastro.')) return;
  try {
    if (tipo === 'marco') await memoriaService.deleteMarco(id);
    if (tipo === 'pessoa') await memoriaService.deletePersonalidade(id);
    if (tipo === 'acervo') await memoriaService.deleteAcervo(id);
    if (tipo === 'local') await memoriaService.deleteLocal(id);
    if (tipo === 'recorde') await memoriaService.deleteRecorde(id);
    if (tipo === 'equipe') await memoriaService.deleteEquipe(id);
    if (tipo === 'momento' && editingLocalId.value) await memoriaService.deleteMomento(editingLocalId.value, id);
    await load();
  } catch (e) {
    alert('Não foi possível excluir.');
  }
};

const aprovar = async (item) => {
  await memoriaService.aprovar(item.id);
  await load();
};

const pedirComplemento = async (item) => {
  const nota = prompt('O que falta nesta ficha? (fica só para a curadoria)', item.motivo_rejeicao || '');
  if (nota === null) return;
  await memoriaService.pedirComplemento(item.id, { motivo_rejeicao: nota });
  await load();
};

const startReject = (item) => {
  rejectingId.value = item.id;
  rejectReason.value = '';
};

const confirmReject = async (item) => {
  await memoriaService.rejeitar(item.id, { motivo_rejeicao: rejectReason.value });
  rejectingId.value = null;
  await load();
};

const salvarLocal = async () => {
  const fd = new FormData();
  fd.append('nome', localForm.value.nome);
  fd.append('slug', localForm.value.slug || '');
  fd.append('tipo', localForm.value.tipo);
  fd.append('endereco', localForm.value.endereco || '');
  if (localForm.value.lat !== '' && localForm.value.lat != null) fd.append('lat', localForm.value.lat);
  if (localForm.value.lng !== '' && localForm.value.lng != null) fd.append('lng', localForm.value.lng);
  if (localForm.value.ano_antes) fd.append('ano_antes', localForm.value.ano_antes);
  if (localForm.value.ano_depois) fd.append('ano_depois', localForm.value.ano_depois);
  fd.append('publicado', '1');
  ['foto', 'foto_antes', 'foto_depois'].forEach((field) => {
    if (localFiles.value[field]) fd.append(field, localFiles.value[field]);
  });
  let savedId = editingLocalId.value;
  if (editingLocalId.value) await memoriaService.updateLocal(editingLocalId.value, fd);
  else {
    const { data } = await memoriaService.createLocal(fd);
    savedId = data.id;
  }
  localFiles.value = {};
  await load();
  const item = locais.value.find((l) => l.id === savedId);
  if (item) editarLocal(item);
};

const onMomentoFile = (event) => {
  momentoFile.value = event.target.files?.[0] || null;
};

const salvarMomento = async () => {
  if (!editingLocalId.value) return;
  const fd = new FormData();
  fd.append('ano', momentoForm.value.ano || '');
  fd.append('titulo', momentoForm.value.titulo || '');
  if (momentoFile.value) fd.append('foto', momentoFile.value);
  await memoriaService.createMomento(editingLocalId.value, fd);
  momentoForm.value = emptyMomentoForm();
  momentoFile.value = null;
  const keep = editingLocalId.value;
  await load();
  const item = locais.value.find((l) => l.id === keep);
  if (item) editarLocal(item);
};

const onLocalFile = (event, field) => {
  localFiles.value[field] = event.target.files?.[0] || null;
};

const editarLocal = (item) => {
  editingLocalId.value = item.id;
  localForm.value = {
    nome: item.nome || '',
    slug: item.slug || '',
    tipo: item.tipo || 'ginasio',
    endereco: item.endereco || '',
    lat: item.lat ?? '',
    lng: item.lng ?? '',
    ano_antes: item.ano_antes || '',
    ano_depois: item.ano_depois || '',
  };
  localFiles.value = {};
  momentoForm.value = emptyMomentoForm();
  momentoFile.value = null;
};

const resetLocalForm = () => {
  editingLocalId.value = null;
  localForm.value = emptyLocalForm();
  localFiles.value = {};
  momentoForm.value = emptyMomentoForm();
  momentoFile.value = null;
};

const aprovarIdentificacao = async (item) => {
  await memoriaService.aprovarIdentificacao(item.id, {
    personalidade_id: item._personalidade || null,
    fechar: item._fechar ? '1' : '0',
  });
  await load();
};

const rejeitarIdentificacao = async (item) => {
  await memoriaService.rejeitarIdentificacao(item.id, {});
  await load();
};

const salvarRecorde = async () => {
  const payload = {
    ...recordeForm.value,
    personalidade_id: recordeForm.value.personalidade_id || null,
    campeonato_id: recordeForm.value.campeonato_id || null,
    marco_id: recordeForm.value.marco_id || null,
  };
  if (editingRecordeId.value) await memoriaService.updateRecorde(editingRecordeId.value, payload);
  else await memoriaService.createRecorde(payload);
  editingRecordeId.value = null;
  recordeForm.value = emptyRecordeForm();
  await load();
};

const salvarEquipe = async () => {
  const payload = {
    ...equipeForm.value,
    modalidade_id: equipeForm.value.modalidade_id || null,
    publicado: equipeForm.value.publicado ? '1' : '0',
  };
  if (editingEquipeId.value) await memoriaService.updateEquipe(editingEquipeId.value, payload);
  else await memoriaService.createEquipe(payload);
  editingEquipeId.value = null;
  equipeForm.value = emptyEquipeForm();
  await load();
};

const editarEquipe = (item) => {
  editingEquipeId.value = item.id;
  equipeForm.value = {
    nome: item.nome || '',
    apelido: item.apelido || '',
    nome_anterior: item.nome_anterior || '',
    modalidade_id: item.modalidade_id ? String(item.modalidade_id) : '',
    ano_inicio: item.ano_inicio || '',
    cidade: item.cidade || '',
    descricao: item.descricao || '',
    publicado: item.publicado !== false,
  };
};

const aprovarCorrecao = async (item) => {
  await memoriaService.aprovarCorrecao(item.id);
  await load();
};

const rejeitarCorrecao = async (item) => {
  await memoriaService.rejeitarCorrecao(item.id, {});
  await load();
};

const editarRecorde = (item) => {
  editingRecordeId.value = item.id;
  recordeForm.value = {
    titulo: item.titulo || '',
    categoria: item.categoria || 'artilharia',
    valor: item.valor || '',
    unidade: item.unidade || '',
    ano: item.ano || '',
    atleta_nome: item.atleta_nome || '',
    personalidade_id: item.personalidade_id ? String(item.personalidade_id) : '',
    campeonato_id: item.campeonato_id ? String(item.campeonato_id) : '',
    marco_id: item.marco_id ? String(item.marco_id) : '',
    vigente: !!item.vigente,
  };
};

onMounted(load);
</script>
