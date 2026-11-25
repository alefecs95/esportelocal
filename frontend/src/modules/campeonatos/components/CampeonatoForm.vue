<template>
  <form @submit.prevent="submit" class="space-y-6">
    
    <!-- Seção 1: Dados Básicos -->
    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
      <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        Dados Básicos
      </h3>
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Nome -->
        <BaseInput
          id="nome"
          label="Nome da Competição"
          v-model="form.nome"
          required
          placeholder="Ex: Campeonato Municipal de Futebol 2024"
          class="col-span-1 md:col-span-2"
        />

        <!-- Local -->
        <BaseInput
          id="local"
          label="Local do Evento"
          v-model="form.local"
          placeholder="Ex: Estádio Municipal, Ginásio Poliesportivo"
          class="col-span-1 md:col-span-2"
        />
        <div class="col-span-1 md:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-1">Banner da Competição</label>
          <input
            type="file"
            accept="image/*"
            class="block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            @change="handleBannerChange"
          >
          <p class="text-xs text-gray-500 mt-1">Formatos aceitos: JPG, PNG, WEBP. Tamanho máximo: 5MB.</p>
          <img
            v-if="bannerPreview"
            :src="bannerPreview"
            alt="Preview do banner"
            class="mt-3 w-full max-h-52 object-cover rounded border border-gray-200"
          >
        </div>
        
        <!-- Modalidade -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Modalidade</label>
          <select
            v-model="form.modalidade_id"
            class="block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            required
          >
            <option value="" disabled>Selecione uma modalidade</option>
            <option v-for="mod in modalidades" :key="mod.id" :value="mod.id">
              {{ mod.nome }}
            </option>
          </select>
        </div>

        <!-- Tipo de Competição -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Disputa</label>
          <select
            v-model="form.tipo_competicao"
            class="block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            required
          >
            <option value="individual">Individual</option>
            <option value="equipe">Por Equipes</option>
          </select>
        </div>

        <!-- Categoria Principal (Label) -->
        <BaseInput
          id="categoria"
          label="Rótulo da Categoria Principal"
          v-model="form.categoria"
          required
          placeholder="Ex: Série A, Elite, Geral"
          hint="Nome principal para exibição nos cards."
        />

        <!-- Gênero -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Gênero</label>
          <select
            v-model="form.genero"
            class="block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            required
          >
            <option value="masculino">Masculino</option>
            <option value="feminino">Feminino</option>
            <option value="misto">Misto</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Seção 2: Configuração Específica da Modalidade (Dinâmico) -->
    <div v-if="form.modalidade_id" class="bg-blue-50 p-6 rounded-lg shadow-sm border border-blue-100">
      <h3 class="text-lg font-semibold text-blue-800 mb-4 flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        Configuração Técnica: {{ currentModalidade?.nome }}
      </h3>
      
      <!-- Configuração de Equipes -->
      <div v-if="form.tipo_competicao === 'equipe'" class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <BaseInput
          id="min_jogadores"
          label="Mín. Jogadores por Equipe"
          type="number"
          v-model="form.min_jogadores"
          placeholder="Ex: 11"
        />
        <BaseInput
          id="max_jogadores"
          label="Máx. Jogadores por Equipe"
          type="number"
          v-model="form.max_jogadores"
          placeholder="Ex: 22"
        />
      </div>

      <!-- Categorias / Provas Específicas -->
      <div class="border-t border-blue-200 pt-4">
        <div class="flex justify-between items-center mb-4">
          <div>
            <h4 class="text-md font-bold text-blue-900">Categorias / Provas</h4>
            <p class="text-sm text-blue-700">Defina as subdivisões (ex: Sub-20, Peso Pesado, 5km)</p>
          </div>
          <button 
            type="button"
            @click="addCategoria"
            class="bg-blue-600 text-white px-3 py-1.5 rounded hover:bg-blue-700 text-sm flex items-center gap-1 shadow-sm"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Adicionar Categoria
          </button>
        </div>

        <div v-if="form.categorias && form.categorias.length > 0" class="space-y-3">
          <div v-for="(cat, index) in form.categorias" :key="index" class="bg-white p-4 rounded-md shadow-sm border border-blue-100 relative group">
            <button type="button" @click="removeCategoria(index)" class="absolute top-2 right-2 text-gray-400 hover:text-red-500 p-1">
               <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
               </svg>
            </button>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
              <div class="col-span-1 md:col-span-2">
                 <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Nome da Categoria</label>
                 <input v-model="cat.nome" type="text" class="w-full border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Ex: Sub-20">
              </div>
              
              <div>
                 <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Gênero</label>
                 <select v-model="cat.genero" class="w-full border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <option value="M">Masculino</option>
                    <option value="F">Feminino</option>
                    <option value="Misto">Misto</option>
                 </select>
              </div>

              <!-- Campos Condicionais -->
              <div v-if="shouldShowField('distancia')">
                 <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Distância</label>
                 <input v-model="cat.distancia" type="text" class="w-full border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Ex: 5km">
              </div>

              <div v-if="shouldShowField('peso')" class="flex gap-2">
                 <div class="flex-1">
                   <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Peso Min</label>
                   <input v-model="cat.peso_min" type="number" step="0.1" class="w-full border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                 </div>
                 <div class="flex-1">
                   <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Peso Max</label>
                   <input v-model="cat.peso_max" type="number" step="0.1" class="w-full border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                 </div>
              </div>

               <div v-if="shouldShowField('idade')" class="flex gap-2">
                 <div class="flex-1">
                   <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Idade Min</label>
                   <input v-model="cat.idade_min" type="number" class="w-full border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                 </div>
                 <div class="flex-1">
                   <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Idade Max</label>
                   <input v-model="cat.idade_max" type="number" class="w-full border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                 </div>
              </div>

              <div>
                 <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Valor (R$)</label>
                 <input v-model="cat.valor_inscricao" type="number" step="0.01" class="w-full border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Opcional">
              </div>
              
               <div>
                 <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Vagas</label>
                 <input v-model="cat.vagas_limite" type="number" class="w-full border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Ilimitado">
              </div>
            </div>

            <!-- Campos adicionais de Corrida / Inscrição Solidária -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-3 mt-3 pt-3 border-t border-gray-100">
              <div class="flex items-center gap-2">
                <input
                  :id="`cat_competitiva_${index}`"
                  type="checkbox"
                  v-model="cat.competitiva"
                  class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                />
                <label :for="`cat_competitiva_${index}`" class="text-xs font-medium text-gray-700">Competitiva (pódio)</label>
              </div>

              <div class="flex items-center gap-2">
                <input
                  :id="`cat_brinquedo_${index}`"
                  type="checkbox"
                  v-model="cat.aceita_brinquedo"
                  class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                />
                <label :for="`cat_brinquedo_${index}`" class="text-xs font-medium text-gray-700">Aceita brinquedo</label>
              </div>

              <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Alimento (kg)</label>
                <input v-model="cat.contribuicao_alimento_kg" type="number" step="0.5" min="0" class="w-full border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Ex: 4">
              </div>

              <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Largada prevista</label>
                <input v-model="cat.largada_prevista" type="text" class="w-full border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Ex: 06:30">
              </div>

              <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Ordem premiação</label>
                <input v-model="cat.ordem_premiacao" type="number" min="1" class="w-full border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Ex: 1">
              </div>

              <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Premiação</label>
                <input v-model="cat.premiacao_descricao" type="text" class="w-full border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Ex: Troféu + cesta">
              </div>
            </div>
          </div>
        </div>
        <div v-else class="text-center py-6 bg-white rounded border border-dashed border-blue-200 text-blue-400">
          <p>Nenhuma categoria específica adicionada.</p>
          <p class="text-xs mt-1">Clique em "Adicionar Categoria" se precisar de divisões (ex: Sub-17, Veterano).</p>
        </div>
      </div>
    </div>

    <!-- Seção 3: Cronograma e Prazos -->
    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
      <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        Cronograma
      </h3>
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <BaseInput
          id="data_inicio"
          label="Início da Competição"
          type="date"
          v-model="form.data_inicio"
          required
        />
        <BaseInput
          id="data_fim"
          label="Fim da Competição"
          type="date"
          v-model="form.data_fim"
          required
        />
        
        <div class="col-span-1 md:col-span-2 border-t border-gray-100 pt-4 mt-2">
            <h4 class="text-sm font-bold text-gray-700 mb-3">Prazos de Inscrição</h4>
            <div class="flex items-center gap-4 mb-4">
                <input
                  id="permite_inscricao_online"
                  type="checkbox"
                  v-model="form.permite_inscricao_online"
                  class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded"
                />
                <label for="permite_inscricao_online" class="text-sm text-gray-900 font-medium">
                  Habilitar Inscrições Online pelo Portal
                </label>
            </div>
            
            <div v-if="form.permite_inscricao_online" class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-green-50 p-4 rounded-md">
                <BaseInput
                  id="inicio_inscricao"
                  label="Início das Inscrições"
                  type="date"
                  v-model="form.inicio_inscricao"
                />
                <BaseInput
                  id="fim_inscricao"
                  label="Fim das Inscrições"
                  type="date"
                  v-model="form.fim_inscricao"
                />
            </div>
        </div>
      </div>
    </div>

    <!-- Seção 4: Elegibilidade e Restrições (Fixo) -->
    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            Restrições de Domicílio Eleitoral
        </h3>
        
        <p class="text-sm text-gray-500 mb-4">
            Defina abaixo os critérios geográficos para participação. Deixe em branco para permitir participantes de qualquer local.
        </p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-purple-50 p-4 rounded-md border border-purple-100">
          <!-- Cidade Eleitoral -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Cidade Eleitoral</label>
            <select
              v-model="form.cidade_eleitoral"
              class="block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500 sm:text-sm"
              @change="handleCidadeChange"
            >
              <option value="">Todas as Cidades</option>
              <option v-for="cidade in cidadesEleitorais" :key="cidade" :value="cidade">
                {{ cidade }}
              </option>
            </select>
          </div>

          <!-- Bairro Eleitoral -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Bairro Eleitoral</label>
            <select
              v-model="form.bairro_eleitoral"
              class="block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500 sm:text-sm"
              :disabled="!form.cidade_eleitoral"
            >
              <option value="">Todos os Bairros</option>
              <option v-for="bairro in bairrosEleitorais" :key="bairro" :value="bairro">
                {{ bairro }}
              </option>
            </select>
          </div>
          
          <!-- Contador de Elegibilidade -->
          <div class="col-span-1 md:col-span-2 mt-2 pt-2 border-t border-purple-200">
             <div v-if="elegibilidadeCount !== null" :class="[
                'p-3 rounded-md text-center font-medium text-sm',
                elegibilidadeCount > 0 ? 'text-green-700 bg-green-100' : 'text-red-700 bg-red-100'
             ]">
                <span v-if="elegibilidadeCount > 0">
                    <span class="font-bold text-lg">{{ elegibilidadeCount }}</span> atletas encontrados com esses critérios.
                </span>
                <span v-else>
                    Nenhum atleta encontrado nesta região.
                </span>
             </div>
             <div v-else class="text-center text-gray-400 text-xs italic">
                Selecione uma cidade/bairro para simular a quantidade de atletas elegíveis.
             </div>
          </div>
        </div>
    </div>

    <!-- Seção 4b: Cadastro do Participante -->
    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
      <h3 class="text-lg font-semibold text-gray-800 mb-2">Cadastro do participante</h3>
      <p class="text-sm text-gray-500 mb-4">
        Escolha quais campos serão pedidos na inscrição online desta competição. O modo "Completo" mantém o cadastro padrão do sistema.
      </p>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-5">
        <label
          v-for="opcao in modosCadastroOpcoes"
          :key="opcao.value"
          :class="[
            'cursor-pointer rounded-lg border p-4 transition',
            form.modo_cadastro === opcao.value
              ? 'border-blue-600 bg-blue-50 ring-1 ring-blue-600'
              : 'border-gray-200 hover:border-gray-300 bg-white'
          ]"
        >
          <div class="flex items-center gap-2">
            <input
              type="radio"
              name="modo_cadastro"
              :value="opcao.value"
              v-model="form.modo_cadastro"
              class="h-4 w-4 text-blue-600"
            />
            <span class="font-semibold text-sm text-gray-900">{{ opcao.label }}</span>
          </div>
          <p class="text-xs text-gray-600 mt-1">{{ opcao.desc }}</p>
        </label>
      </div>

      <!-- Campos fixos (sempre obrigatórios) -->
      <div class="mb-4 rounded-md bg-gray-50 border border-gray-200 p-3">
        <div class="text-xs font-bold uppercase text-gray-500 mb-2">Sempre obrigatórios (fixos)</div>
        <div class="flex flex-wrap gap-2">
          <span
            v-for="fixo in camposFixosLabels"
            :key="fixo"
            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-200 text-gray-700"
          >
            {{ fixo }} *
          </span>
        </div>
      </div>

      <!-- Tabela de configuração no modo personalizado -->
      <div v-if="form.modo_cadastro === 'personalizado'" class="mb-4 overflow-x-auto border border-gray-200 rounded-md">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Campo</th>
              <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600 uppercase">Mostrar</th>
              <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600 uppercase">Obrigatório</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 bg-white">
            <tr v-for="campo in camposConfiguraveisMeta" :key="campo.key">
              <td class="px-4 py-2 font-medium text-gray-800">{{ campo.label }}</td>
              <td class="px-4 py-2 text-center">
                <input
                  type="checkbox"
                  v-model="camposPersonalizados[campo.key].visivel"
                  @change="onToggleVisivel(campo.key)"
                  class="h-4 w-4 text-blue-600 border-gray-300 rounded"
                />
              </td>
              <td class="px-4 py-2 text-center">
                <input
                  type="checkbox"
                  v-model="camposPersonalizados[campo.key].obrigatorio"
                  :disabled="!camposPersonalizados[campo.key].visivel"
                  class="h-4 w-4 text-blue-600 border-gray-300 rounded disabled:opacity-40"
                />
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Prévia dos campos ativos -->
      <div class="rounded-md bg-blue-50/70 border border-blue-100 p-3">
        <div class="text-xs font-bold uppercase text-blue-800 mb-2">Prévia dos campos ativos neste formulário</div>
        <div class="flex flex-wrap gap-2">
          <span
            v-for="item in previaCamposAtivos"
            :key="item.key"
            :class="[
              'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium',
              item.obrigatorio ? 'bg-blue-600 text-white' : 'bg-white text-blue-800 border border-blue-200'
            ]"
          >
            {{ item.label }}<span v-if="item.obrigatorio" class="ml-0.5">*</span>
          </span>
        </div>
        <p class="text-[11px] text-blue-700 mt-2">
          * Campos marcados com asterisco são obrigatórios. Menores de 18 anos na data do evento informam nome e telefone do responsável.
        </p>
      </div>
    </div>

    <!-- Seção 4c: Inscrição Solidária -->
    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
      <h3 class="text-lg font-semibold text-gray-800 mb-2">Inscrição solidária</h3>
      <p class="text-sm text-gray-500 mb-4">
        Ative para eventos onde a inscrição fica pendente até a entrega de alimento, brinquedo ou taxa, com número de peito gerado já no cadastro (a inscrição só é confirmada na entrega da contribuição).
      </p>

      <div class="space-y-4">
        <div class="flex items-start gap-3">
          <input
            id="inscricao_solidaria"
            type="checkbox"
            v-model="form.inscricao_solidaria"
            class="mt-1 h-4 w-4 text-emerald-600 focus:ring-emerald-500 border-gray-300 rounded"
          />
          <label for="inscricao_solidaria" class="text-sm text-gray-900">
            <span class="font-medium block">Ativar inscrição solidária</span>
            <span class="text-xs text-gray-500">Inscrição nasce pendente, exige escolha de prova/contribuição e gera número de peito ao confirmar recebimento.</span>
          </label>
        </div>

        <div class="flex items-start gap-3">
          <input
            id="exige_termo_menor"
            type="checkbox"
            v-model="form.exige_termo_menor"
            class="mt-1 h-4 w-4 text-emerald-600 focus:ring-emerald-500 border-gray-300 rounded"
          />
          <label for="exige_termo_menor" class="text-sm text-gray-900">
            <span class="font-medium block">Exigir termo de autorização para menores de 18 anos</span>
            <span class="text-xs text-gray-500">Bloqueia a retirada do kit para menores sem termo assinado e nome do responsável.</span>
          </label>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Informações de retirada do kit</label>
          <textarea
            v-model="form.kit_retirada_info"
            rows="3"
            class="block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            placeholder="Ex: Retirada nos dias 17 e 18/12 das 08h às 17h na Secretaria de Esportes. Levar documento com foto."
          ></textarea>
        </div>
      </div>
    </div>

    <!-- Seção 5: Finalização -->
    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
      <h3 class="text-lg font-semibold text-gray-800 mb-4">Outros Detalhes</h3>
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <BaseInput
            id="valor_inscricao"
            label="Taxa de Inscrição (R$)"
            type="number"
            step="0.01"
            v-model="form.valor_inscricao"
            placeholder="0,00"
          />

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Status da Competição</label>
            <select
              v-model="form.status"
              class="block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              required
            >
              <option value="draft">Rascunho</option>
              <option value="inscricoes_abertas">Inscrições Abertas</option>
              <option value="inscricoes_encerradas">Inscrições Encerradas</option>
              <option value="em_andamento">Em Andamento</option>
              <option value="finalizado">Finalizado</option>
            </select>
          </div>

          <BaseInput
            id="rodada_limite_inscricao_atletas"
            label="Rodada limite para inscrição de atleta"
            type="number"
            min="1"
            v-model="form.rodada_limite_inscricao_atletas"
            placeholder="Ex: 2"
          />

          <div class="col-span-1 md:col-span-2 rounded-md border border-amber-200 bg-amber-50 p-4">
            <h4 class="text-sm font-bold text-amber-800 mb-3">Regras Disciplinares</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="md:col-span-3 flex items-center gap-3">
                <input
                  id="suspensao_automatica_ativa"
                  type="checkbox"
                  v-model="form.suspensao_automatica_ativa"
                  class="h-4 w-4 text-amber-600 focus:ring-amber-500 border-gray-300 rounded"
                />
                <label for="suspensao_automatica_ativa" class="text-sm text-amber-900 font-medium">
                  Ativar suspensão automática por cartões nesta competição
                </label>
              </div>

              <BaseInput
                id="cartoes_amarelos_para_suspensao"
                label="Qtde de amarelos para suspensão"
                type="number"
                min="1"
                max="10"
                v-model="form.cartoes_amarelos_para_suspensao"
                :disabled="!form.suspensao_automatica_ativa"
              />

              <div class="flex items-center gap-3 md:col-span-2 mt-6">
                <input
                  id="cartao_vermelho_suspende"
                  type="checkbox"
                  v-model="form.cartao_vermelho_suspende"
                  :disabled="!form.suspensao_automatica_ativa"
                  class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded"
                />
                <label for="cartao_vermelho_suspende" class="text-sm text-gray-800 font-medium">
                  Cartão vermelho gera suspensão automática na próxima partida
                </label>
              </div>
            </div>
          </div>
          
          <div class="col-span-1 md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Regulamento</label>
            <textarea
              v-model="form.regulamento"
              rows="4"
              class="block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              placeholder="Cole aqui o regulamento ou orientações gerais..."
            ></textarea>
          </div>
      </div>
    </div>

    <div class="flex justify-end space-x-4 pt-4 pb-8">
      <BaseButton type="button" variant="secondary" @click="$emit('cancel')" class="px-6">
        Cancelar
      </BaseButton>
      <BaseButton type="submit" variant="primary" :disabled="loading" class="px-8 text-lg">
        {{ isEdit ? 'Salvar Alterações' : 'Criar Competição' }}
      </BaseButton>
    </div>
  </form>
</template>

<script setup>
import { reactive, watch, onMounted, computed, ref } from 'vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import { useModalidadeStore } from '@/modules/modalidades/store/modalidadeStore';
import { useCampeonatoStore } from '../store/campeonatoStore';

const props = defineProps({
  initialData: {
    type: Object,
    default: () => ({
      nome: '',
      modalidade_id: '',
      categoria: '',
      genero: 'misto',
      tipo_competicao: 'equipe',
      data_inicio: '',
      data_fim: '',
      limite_participantes: '',
      valor_inscricao: '',
      regulamento: '',
      status: 'draft',
      idade_min: '',
      idade_max: '',
      cidade_eleitoral: '',
      bairro_eleitoral: '',
      permite_inscricao_online: false,
      inicio_inscricao: '',
      fim_inscricao: '',
      rodada_limite_inscricao_atletas: '',
      suspensao_automatica_ativa: true,
      cartoes_amarelos_para_suspensao: 2,
      cartao_vermelho_suspende: true,
      banner: null,
      banner_url: '',
      categorias: [],
      min_jogadores: '',
      max_jogadores: '',
      modo_cadastro: 'completo',
      campos_cadastro: null,
      inscricao_solidaria: false,
      exige_termo_menor: false,
      kit_retirada_info: '',
    }),
  },
  isEdit: {
    type: Boolean,
    default: false,
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['submit', 'cancel']);
const modalidadeStore = useModalidadeStore();
const campeonatoStore = useCampeonatoStore();

const formatDateForInput = (dateString) => {
    if (!dateString) return '';
    // Pega apenas a parte da data (YYYY-MM-DD) ignorando o tempo
    return String(dateString).substring(0, 10);
};

const processInitialData = (data) => {
    if (!data) return {};
    const processed = { ...data };
    const dateFields = ['data_inicio', 'data_fim', 'inicio_inscricao', 'fim_inscricao'];
    
    dateFields.forEach(field => {
        if (processed[field]) {
            processed[field] = formatDateForInput(processed[field]);
        }
    });
    processed.banner = null;
    
    return processed;
};

const modosCadastroOpcoes = [
  { value: 'completo', label: 'Completo (padrão)', desc: 'Mantém todos os campos tradicionais (CPF, filiação, título de eleitor, endereço, foto).' },
  { value: 'simplificado', label: 'Simplificado', desc: 'Ideal para corridas e eventos abertos: nome, nascimento, sexo, CPF, telefone, e-mail, camiseta e emergência.' },
  { value: 'personalizado', label: 'Personalizado', desc: 'Escolha individualmente quais campos mostrar e quais serão obrigatórios.' },
];

const camposFixosLabels = ['Nome completo', 'Data de nascimento', 'Prova / Categoria', 'Aceites obrigatórios'];

const camposConfiguraveisMeta = [
  { key: 'cpf', label: 'CPF' },
  { key: 'sexo', label: 'Sexo' },
  { key: 'nome_mae', label: 'Nome da mãe' },
  { key: 'nome_pai', label: 'Nome do pai' },
  { key: 'titulo_eleitor', label: 'Título de eleitor / dados eleitorais' },
  { key: 'cep', label: 'CEP' },
  { key: 'endereco', label: 'Endereço (rua, bairro, UF)' },
  { key: 'telefone', label: 'Telefone / WhatsApp' },
  { key: 'email', label: 'E-mail' },
  { key: 'foto', label: 'Foto do participante' },
  { key: 'tamanho_camiseta', label: 'Tamanho da camiseta' },
  { key: 'contato_emergencia', label: 'Contato de emergência' },
];

const PRESET_CAMPOS = {
  completo: {
    cpf: { visivel: true, obrigatorio: true },
    sexo: { visivel: true, obrigatorio: false },
    nome_mae: { visivel: true, obrigatorio: true },
    nome_pai: { visivel: true, obrigatorio: false },
    titulo_eleitor: { visivel: true, obrigatorio: true },
    cep: { visivel: true, obrigatorio: true },
    endereco: { visivel: true, obrigatorio: true },
    telefone: { visivel: true, obrigatorio: false },
    email: { visivel: true, obrigatorio: false },
    foto: { visivel: true, obrigatorio: false },
    tamanho_camiseta: { visivel: false, obrigatorio: false },
    contato_emergencia: { visivel: false, obrigatorio: false },
  },
  simplificado: {
    cpf: { visivel: true, obrigatorio: true },
    sexo: { visivel: true, obrigatorio: true },
    nome_mae: { visivel: false, obrigatorio: false },
    nome_pai: { visivel: false, obrigatorio: false },
    titulo_eleitor: { visivel: false, obrigatorio: false },
    cep: { visivel: false, obrigatorio: false },
    endereco: { visivel: false, obrigatorio: false },
    telefone: { visivel: true, obrigatorio: true },
    email: { visivel: true, obrigatorio: false },
    foto: { visivel: false, obrigatorio: false },
    tamanho_camiseta: { visivel: true, obrigatorio: false },
    contato_emergencia: { visivel: true, obrigatorio: false },
  },
};

const criarCamposPersonalizados = (saved) => {
  const base = PRESET_CAMPOS.completo;
  const result = {};
  camposConfiguraveisMeta.forEach(({ key }) => {
    const item = saved && typeof saved === 'object' && saved[key] ? saved[key] : base[key];
    const obrigatorio = Boolean(item?.obrigatorio);
    result[key] = {
      visivel: obrigatorio ? true : Boolean(item?.visivel),
      obrigatorio,
    };
  });
  return result;
};

const camposPersonalizados = reactive(criarCamposPersonalizados(props.initialData?.campos_cadastro));

const onToggleVisivel = (key) => {
  if (!camposPersonalizados[key].visivel) {
    camposPersonalizados[key].obrigatorio = false;
  }
};

const previaCamposAtivos = computed(() => {
  const mapa = form.modo_cadastro === 'personalizado'
    ? camposPersonalizados
    : (PRESET_CAMPOS[form.modo_cadastro] || PRESET_CAMPOS.completo);

  const fixos = [
    { key: 'nome', label: 'Nome completo', obrigatorio: true },
    { key: 'data_nascimento', label: 'Data de nascimento', obrigatorio: true },
  ];

  const ativos = camposConfiguraveisMeta
    .filter(({ key }) => mapa[key]?.visivel)
    .map(({ key, label }) => ({ key, label, obrigatorio: Boolean(mapa[key]?.obrigatorio) }));

  return [...fixos, ...ativos, { key: 'cidade', label: 'Cidade', obrigatorio: form.modo_cadastro === 'completo' }];
});

const bannerPreview = ref(props.initialData?.banner_url || null);

const normalizarCategorias = (cats) => {
  if (!Array.isArray(cats)) return [];
  return cats.map((cat) => ({
    ...cat,
    competitiva: cat.competitiva === undefined || cat.competitiva === null ? true : Boolean(cat.competitiva),
    aceita_brinquedo: Boolean(cat.aceita_brinquedo),
    contribuicao_alimento_kg: cat.contribuicao_alimento_kg ?? '',
    premiacao_descricao: cat.premiacao_descricao ?? '',
    largada_prevista: cat.largada_prevista ?? '',
    ordem_premiacao: cat.ordem_premiacao ?? '',
  }));
};

const form = reactive({ 
    categorias: [], 
    min_jogadores: '',
    max_jogadores: '',
    modo_cadastro: 'completo',
    campos_cadastro: null,
    inscricao_solidaria: false,
    exige_termo_menor: false,
    kit_retirada_info: '',
    ...processInitialData(props.initialData) 
});

form.categorias = normalizarCategorias(form.categorias);
if (!form.modo_cadastro) form.modo_cadastro = 'completo';
form.inscricao_solidaria = Boolean(form.inscricao_solidaria);
form.exige_termo_menor = Boolean(form.exige_termo_menor);

// Watch para garantir que form se mantenha sincronizado com props
watch(() => props.initialData, (newVal) => {
    if (newVal) {
        Object.assign(form, processInitialData(newVal));
        form.categorias = normalizarCategorias(newVal.categorias);
        form.modo_cadastro = newVal.modo_cadastro || 'completo';
        form.inscricao_solidaria = Boolean(newVal.inscricao_solidaria);
        form.exige_termo_menor = Boolean(newVal.exige_termo_menor);
        form.kit_retirada_info = newVal.kit_retirada_info || '';
        Object.assign(camposPersonalizados, criarCamposPersonalizados(newVal.campos_cadastro));
        bannerPreview.value = newVal.banner_url || null;
        if (newVal.cidade_eleitoral && typeof campeonatoStore.fetchBairrosEleitorais === 'function') {
            campeonatoStore.fetchBairrosEleitorais(newVal.cidade_eleitoral);
        }
    }
    if (!form.categorias) form.categorias = [];
}, { deep: true });

// Watch para preencher dados da modalidade ao selecionar
watch(() => form.modalidade_id, (newVal) => {
    // Se for edição, não faz nada para evitar sobrescrever dados
    if (props.isEdit) return;

    if (!newVal) {
        form.min_jogadores = '';
        form.max_jogadores = '';
        return;
    }
    
    const mod = modalidades.value.find(m => String(m.id) === String(newVal));
    if (mod) {
        // Define tipo automaticamente
        if (mod.tipo === 'coletivo') {
            form.tipo_competicao = 'equipe';
        } else {
            form.tipo_competicao = 'individual';
        }

        // Preenche limites se houver na config
        if (mod.regras_config) {
             form.min_jogadores = mod.regras_config.min_jogadores || '';
             form.max_jogadores = mod.regras_config.max_jogadores || '';
        } else {
             form.min_jogadores = '';
             form.max_jogadores = '';
        }
    }
});

const addCategoria = () => {
  form.categorias.push({
    nome: '',
    genero: 'Misto',
    distancia: '',
    idade_min: '',
    idade_max: '',
    peso_min: '',
    peso_max: '',
    valor_inscricao: '',
    vagas_limite: '',
    competitiva: true,
    contribuicao_alimento_kg: '',
    aceita_brinquedo: false,
    premiacao_descricao: '',
    largada_prevista: '',
    ordem_premiacao: '',
  });
};

const removeCategoria = (index) => {
  form.categorias.splice(index, 1);
};

const modalidades = computed(() => modalidadeStore.modalidades);
const cidadesEleitorais = computed(() => campeonatoStore.cidadesEleitorais || []);
const bairrosEleitorais = computed(() => campeonatoStore.bairrosEleitorais || []);
const elegibilidadeCount = computed(() => campeonatoStore.elegibilidadeCount);

const currentModalidade = computed(() => {
    if (!form.modalidade_id) return null;
    return modalidades.value.find(m => String(m.id) === String(form.modalidade_id));
});

const shouldShowField = (field) => {
    // Se não tiver modalidade selecionada, mostra tudo (ou nada, dependendo da estratégia)
    // Vamos mostrar os campos básicos por padrão.
    if (!currentModalidade.value) return true;
    
    // Se tiver regras_config, usa ela. Senão tenta inferir pelo nome/tipo.
    const config = currentModalidade.value.regras_config;
    
    // Se for um campo de categoria (campos_categoria), verifica no config
    if (['sexo', 'idade', 'peso', 'distancia', 'faixa'].includes(field)) {
         if (config && config.campos_categoria) {
            // Se o campo estiver na lista, retorna true
            return config.campos_categoria.includes(field);
         }
    }
    
    // Fallback logic se não tiver config JSON ou campo não estiver mapeado explicitamente
    const nomeMod = currentModalidade.value.nome ? currentModalidade.value.nome.toLowerCase() : '';
    
    if (field === 'distancia') {
        return nomeMod.includes('corrida') || nomeMod.includes('atletismo') || nomeMod.includes('ciclismo') || nomeMod.includes('natação');
    }
    
    if (field === 'peso') {
        return nomeMod.includes('judô') || nomeMod.includes('judo') || nomeMod.includes('karate') || nomeMod.includes('luta') || nomeMod.includes('boxe');
    }
    
    if (field === 'idade') {
        return true; // Idade geralmente é relevante pra tudo
    }
    
    return true;
};

let simulationTimeout = null;

onMounted(async () => {
  campeonatoStore.elegibilidadeCount = null; // Resetar contador ao montar
  
  if (modalidadeStore.modalidades.length === 0) {
    await modalidadeStore.fetchModalidades({ per_page: 100 });
  }
  
  // Verifica se a função existe antes de chamar
  if (typeof campeonatoStore.fetchCidadesEleitorais === 'function') {
      await campeonatoStore.fetchCidadesEleitorais();
  }
  
  if (form.cidade_eleitoral && typeof campeonatoStore.fetchBairrosEleitorais === 'function') {
      await campeonatoStore.fetchBairrosEleitorais(form.cidade_eleitoral);
  }
  
  // Se for edição, simula logo de cara
  if (props.isEdit) {
      debouncedSimulation();
  }
});

const handleCidadeChange = async () => {
  form.bairro_eleitoral = ''; // Reset bairro
  if (form.cidade_eleitoral) {
      await campeonatoStore.fetchBairrosEleitorais(form.cidade_eleitoral);
  } else {
      campeonatoStore.bairrosEleitorais = []; // Limpa lista se nenhuma cidade
  }
  debouncedSimulation();
};

const debouncedSimulation = () => {
  if (simulationTimeout) clearTimeout(simulationTimeout);
  
  simulationTimeout = setTimeout(() => {
    // Só simula se tiver pelo menos um critério definido e a função existir
    if ((form.idade_min || form.idade_max || form.cidade_eleitoral || form.bairro_eleitoral) && campeonatoStore && typeof campeonatoStore.simularElegibilidade === 'function') {
        campeonatoStore.simularElegibilidade({
            idade_min: form.idade_min,
            idade_max: form.idade_max,
            cidade_eleitoral: form.cidade_eleitoral,
            bairro_eleitoral: form.bairro_eleitoral,
        });
    } else {
        if (campeonatoStore) campeonatoStore.elegibilidadeCount = null;
    }
  }, 500); // 500ms delay
};

// Watch para simular elegibilidade
watch(() => [form.idade_min, form.idade_max, form.cidade_eleitoral, form.bairro_eleitoral], () => {
    debouncedSimulation();
});

const submit = () => {
  const data = {
    ...form,
    modo_cadastro: form.modo_cadastro || 'completo',
    campos_cadastro: form.modo_cadastro === 'personalizado'
      ? JSON.parse(JSON.stringify(camposPersonalizados))
      : null,
    inscricao_solidaria: Boolean(form.inscricao_solidaria),
    exige_termo_menor: Boolean(form.exige_termo_menor),
    kit_retirada_info: form.kit_retirada_info === '' ? null : form.kit_retirada_info,
    categorias: Array.isArray(form.categorias) ? form.categorias.map(cat => ({ ...cat })) : []
  };

  // Clean empty strings to null for numeric fields
  if (data.categorias && Array.isArray(data.categorias)) {
    data.categorias = data.categorias.map(cat => {
      return {
        ...cat,
        idade_min: cat.idade_min === '' ? null : cat.idade_min,
        idade_max: cat.idade_max === '' ? null : cat.idade_max,
        peso_min: cat.peso_min === '' ? null : cat.peso_min,
        peso_max: cat.peso_max === '' ? null : cat.peso_max,
        valor_inscricao: cat.valor_inscricao === '' ? null : cat.valor_inscricao,
        vagas_limite: cat.vagas_limite === '' ? null : cat.vagas_limite,
        distancia: cat.distancia === '' ? null : cat.distancia,
        competitiva: cat.competitiva === undefined ? true : Boolean(cat.competitiva),
        contribuicao_alimento_kg: cat.contribuicao_alimento_kg === '' ? null : cat.contribuicao_alimento_kg,
        aceita_brinquedo: Boolean(cat.aceita_brinquedo),
        premiacao_descricao: cat.premiacao_descricao === '' ? null : cat.premiacao_descricao,
        largada_prevista: cat.largada_prevista === '' ? null : cat.largada_prevista,
        ordem_premiacao: cat.ordem_premiacao === '' ? null : cat.ordem_premiacao,
      };
    });
  }
  
  // Clean main form numeric fields
  ['idade_min', 'idade_max', 'limite_participantes', 'valor_inscricao', 'rodada_limite_inscricao_atletas'].forEach(field => {
      if (data[field] === '') data[field] = null;
  });

  if (!data.suspensao_automatica_ativa) {
    data.cartoes_amarelos_para_suspensao = data.cartoes_amarelos_para_suspensao || 2;
    data.cartao_vermelho_suspende = false;
  }

  if (data.banner instanceof File) {
    const formData = new FormData();
    Object.entries(data).forEach(([key, value]) => {
      if (key === 'categorias') return;
      if (key === 'campos_cadastro') {
        if (value && typeof value === 'object') {
          formData.append(key, JSON.stringify(value));
        }
        return;
      }
      if (value === null || value === undefined || value === '') return;
      if (typeof value === 'boolean') {
        formData.append(key, value ? '1' : '0');
        return;
      }
      formData.append(key, value);
    });

    data.categorias.forEach((cat, index) => {
      Object.entries(cat).forEach(([key, value]) => {
        if (value === null || value === undefined || value === '') return;
        if (typeof value === 'boolean') {
          formData.append(`categorias[${index}][${key}]`, value ? '1' : '0');
          return;
        }
        formData.append(`categorias[${index}][${key}]`, value);
      });
    });

    emit('submit', formData);
    return;
  }

  delete data.banner;
  emit('submit', data);
};

const handleBannerChange = (event) => {
  const arquivo = event.target.files?.[0] || null;
  form.banner = arquivo;
  if (!arquivo) {
    bannerPreview.value = form.banner_url || null;
    return;
  }
  bannerPreview.value = URL.createObjectURL(arquivo);
};
</script>
