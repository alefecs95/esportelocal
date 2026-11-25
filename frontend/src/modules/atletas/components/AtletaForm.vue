<template>
  <div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-blue-600">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
        </svg>
        {{ isEditing ? 'Editar Atleta' : 'Novo Atleta' }}
      </h2>
    </div>

    <!-- Progress Bar -->
    <div class="w-full bg-gray-200 rounded-full h-2.5 mb-6">
      <div class="bg-blue-600 h-2.5 rounded-full transition-all duration-300" :style="{ width: progressPercentage + '%' }"></div>
    </div>
    
    <!-- Steps Indicator -->
    <div class="flex justify-between mb-8 relative">
        <div class="absolute top-1/2 left-0 w-full h-0.5 bg-gray-200 -z-10 transform -translate-y-1/2"></div>
        <div 
            v-for="(step, index) in steps" 
            :key="index"
            class="flex flex-col items-center cursor-pointer"
            @click="canNavigateTo(index + 1) && (currentStep = index + 1)"
        >
            <div 
                class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold border-2 transition-colors duration-300 bg-white"
                :class="currentStep >= index + 1 ? 'border-blue-600 text-blue-600' : 'border-gray-300 text-gray-400'"
            >
                {{ index + 1 }}
            </div>
            <span 
                class="text-xs mt-1 font-medium transition-colors duration-300"
                :class="currentStep >= index + 1 ? 'text-blue-600' : 'text-gray-400'"
            >
                {{ step.label }}
            </span>
        </div>
    </div>

    <form @submit.prevent="submitForm" class="space-y-6">
      
      <!-- STEP 1: DADOS PESSOAIS -->
      <div v-show="currentStep === 1" class="bg-white p-6 rounded-lg shadow-md border-t-4 border-blue-500 animate-fade-in">
        <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-blue-500">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Zm6-10.125a1.875 1.875 0 1 1-3.75 0 1.875 1.875 0 0 1 3.75 0Zm1.294 6.336a6.721 6.721 0 0 1-3.17.789 6.721 6.721 0 0 1-3.168-.789 3.376 3.376 0 0 1 6.338 0Z" />
          </svg>
          DADOS PESSOAIS
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- CPF -->
          <div>
            <label class="block text-sm font-medium text-gray-700">CPF</label>
            <div class="flex">
              <input 
                :value="form.cpf"
                type="text" 
                class="mt-1 block w-full border border-gray-300 rounded-l-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500" 
                placeholder="000.000.000-00" 
                required
                :disabled="isLocked.cpf"
                @input="form.cpf = formatCpf($event.target.value)"
              />
              <button 
                type="button" 
                @click="consultarCpf" 
                class="mt-1 px-3 py-2 bg-blue-500 text-white rounded-r-md hover:bg-blue-600 disabled:opacity-50"
                :disabled="loadingCpf || isLocked.cpf"
              >
                <svg v-if="!loadingCpf" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <span v-else class="animate-spin h-5 w-5 border-2 border-white border-t-transparent rounded-full block"></span>
              </button>
            </div>
            <p v-if="errors.cpf" class="text-red-500 text-xs mt-1">{{ errors.cpf }}</p>
          </div>

          <!-- Nome -->
          <div>
            <label class="block text-sm font-medium text-gray-700">Nome Completo</label>
            <input 
              v-model="form.nome" 
              type="text" 
              class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 bg-gray-50 disabled:bg-gray-200 disabled:text-gray-500" 
              required 
              :disabled="isLocked.nome"
            />
          </div>

          <!-- Data Nascimento -->
          <div>
            <label class="block text-sm font-medium text-gray-700">Data de Nascimento</label>
            <input 
              v-model="form.data_nascimento" 
              type="date" 
              class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 bg-gray-50 disabled:bg-gray-200 disabled:text-gray-500" 
              required
              :disabled="isLocked.data_nascimento"
            />
          </div>

          <!-- Sexo -->
          <div>
            <label class="block text-sm font-medium text-gray-700">Sexo</label>
            <select 
              v-model="form.sexo" 
              class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 bg-gray-50 disabled:bg-gray-200 disabled:text-gray-500" 
              :disabled="isLocked.sexo"
            >
              <option value="">Selecione</option>
              <option value="MASCULINO">Masculino</option>
              <option value="FEMININO">Feminino</option>
            </select>
          </div>

          <!-- Nome Mãe -->
          <div>
            <label class="block text-sm font-medium text-gray-700">Nome da Mãe</label>
            <input 
              v-model="form.nome_mae" 
              type="text" 
              class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 bg-gray-50 disabled:bg-gray-200 disabled:text-gray-500" 
              required 
              :disabled="isLocked.nome_mae"
            />
          </div>

          <!-- Nome Pai -->
          <div>
            <label class="block text-sm font-medium text-gray-700">Nome do Pai</label>
            <input v-model="form.nome_pai" type="text" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" />
          </div>

          <!-- Telefone -->
          <div>
            <label class="block text-sm font-medium text-gray-700">Telefone</label>
            <input v-model="form.telefone" type="text" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" />
          </div>

          <div class="md:col-span-2">
            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
              <input
                v-model="form.receive_notifications"
                type="checkbox"
                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
              />
              Receber notificações no WhatsApp
            </label>
          </div>
          
          <!-- Email REMOVIDO conforme solicitado -->
        </div>
        
        <div v-if="podeExibirOpcaoTituloMenor" class="mt-6 border-t pt-4">
            <h4 class="text-md font-medium text-gray-600 mb-3">Título Eleitoral (Opcional)</h4>
            <p class="text-xs text-gray-500 mb-3">
              Atleta completa 16 anos até outubro. Informe se já possui título para liberar busca e preenchimento dos dados eleitorais.
            </p>
            <div class="flex items-center gap-4">
              <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                <input v-model="possuiTituloMenorAteOutubro" :value="true" type="radio" class="border-gray-300 text-blue-600 focus:ring-blue-500" />
                Sim, possui título
              </label>
              <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                <input v-model="possuiTituloMenorAteOutubro" :value="false" type="radio" class="border-gray-300 text-blue-600 focus:ring-blue-500" />
                Não possui
              </label>
            </div>
        </div>

        <!-- Subseção Eleitoral -->
        <div v-if="deveExibirDadosEleitorais" class="mt-6 border-t pt-4">
            <h4 class="text-md font-medium text-gray-600 mb-3 flex items-center gap-2">
               <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-gray-500">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
               </svg>
               Dados Eleitorais
            </h4>
            <div class="flex flex-wrap items-center gap-3 mb-3">
              <button
                type="button"
                @click="consultarTitulo"
                class="px-3 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 disabled:opacity-50"
                :disabled="loadingTitulo || !podeBuscarTitulo"
              >
                {{ loadingTitulo ? 'Buscando...' : 'Buscar dados eleitorais' }}
              </button>
              <span class="text-xs text-gray-500">Preencha nome, CPF, data de nascimento e nome da mãe.</span>
            </div>
            <div v-if="tituloConsultaAviso" class="mb-3 bg-amber-50 text-amber-700 p-3 rounded-lg text-sm">
              {{ tituloConsultaAviso }}
            </div>
            <div v-if="electoralManualLiberado && !tituloVerificadoApi" class="mb-3 bg-slate-100 text-slate-700 p-3 rounded-lg text-sm">
              Validação TSE não concluída. Complete manualmente zona, seção, município, bairro e UF.
            </div>
            <div class="mb-3 flex flex-wrap items-center gap-2">
              <button
                type="button"
                @click="marcarTituloComoVerificado"
                class="px-3 py-2 bg-emerald-600 text-white rounded-md hover:bg-emerald-700 text-sm font-semibold"
              >
                Marcar título como verificado
              </button>
              <span v-if="form.titulo_verificado" class="text-xs text-emerald-700 bg-emerald-100 px-2 py-1 rounded">
                Título marcado como verificado manualmente
              </span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Título Eleitor -->
                <div>
                  <label class="block text-sm font-medium text-gray-700">Título de Eleitor</label>
                  <div class="flex">
                    <input 
                      v-model="form.titulo_eleitor" 
                      type="text" 
                      class="mt-1 block w-full border border-gray-300 rounded-l-md shadow-sm p-2" 
                      :disabled="!canEditElectoral"
                    />
                    <button 
                      type="button" 
                      @click="consultarTitulo" 
                      class="mt-1 px-3 py-2 bg-blue-500 text-white rounded-r-md hover:bg-blue-600 disabled:opacity-50"
                      :disabled="loadingTitulo || !podeBuscarTitulo"
                      title="Consultar Zona e Seção"
                    >
                      <svg v-if="!loadingTitulo" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                         <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                      </svg>
                      <span v-else class="animate-spin h-5 w-5 border-2 border-white border-t-transparent rounded-full block"></span>
                    </button>
                  </div>
                </div>

                <!-- Zona Eleitoral -->
                <div>
                  <label class="block text-sm font-medium text-gray-700">Zona Eleitoral</label>
                  <input v-model="form.zona_eleitoral" type="text" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" :disabled="!canEditElectoral" />
                </div>

                <!-- Seção Eleitoral -->
                <div>
                  <label class="block text-sm font-medium text-gray-700">Seção Eleitoral</label>
                  <input v-model="form.secao_eleitoral" type="text" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" :disabled="!canEditElectoral" />
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
               <div>
                   <label class="block text-sm font-medium text-gray-700">Bairro</label>
                   <input v-model="form.bairro_eleitoral" type="text" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" :disabled="!canEditElectoral" />
               </div>
               <div>
                   <label class="block text-sm font-medium text-gray-700">Município</label>
                   <input v-model="form.cidade_eleitoral" type="text" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" :disabled="!canEditElectoral" />
               </div>
               <div>
                   <label class="block text-sm font-medium text-gray-700">UF</label>
                   <input v-model="form.uf_eleitoral" type="text" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" :disabled="!canEditElectoral" />
               </div>
            </div>
        </div>
      </div>

      <!-- STEP 2: ENDEREÇO -->
      <div v-show="currentStep === 2" class="bg-white p-6 rounded-lg shadow-md border-t-4 border-green-500 animate-fade-in">
        <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-green-500">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
            </svg>
            ENDEREÇO
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- CEP -->
            <div>
              <label class="block text-sm font-medium text-gray-700">CEP</label>
              <div class="flex">
                <input 
                  v-model="form.cep" 
                  type="text" 
                  class="mt-1 block w-full border border-gray-300 rounded-l-md shadow-sm p-2 focus:ring-green-500 focus:border-green-500" 
                  @blur="consultarCep"
                  placeholder="00000-000"
                  required 
                  :disabled="isLocked.cep"
                />
                <button 
                  type="button" 
                  @click="consultarCep"
                  class="mt-1 px-3 py-2 bg-green-500 text-white rounded-r-md hover:bg-green-600 disabled:opacity-50"
                  :disabled="loadingCep || isLocked.cep"
                >
                  <svg v-if="!loadingCep" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                  </svg>
                  <span v-else class="animate-spin h-5 w-5 border-2 border-white border-t-transparent rounded-full block"></span>
                </button>
              </div>
            </div>

            <!-- Estado -->
            <div>
              <label class="block text-sm font-medium text-gray-700">Estado (UF)</label>
              <input v-model="form.estado" type="text" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 bg-gray-50 disabled:bg-gray-200 disabled:text-gray-500" maxlength="2" required :disabled="isLocked.estado" />
            </div>

            <!-- Cidade -->
            <div>
              <label class="block text-sm font-medium text-gray-700">Cidade</label>
              <input v-model="form.cidade" type="text" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 bg-gray-50 disabled:bg-gray-200 disabled:text-gray-500" required :disabled="isLocked.cidade" />
            </div>

            <!-- Bairro -->
            <div>
              <label class="block text-sm font-medium text-gray-700">Bairro</label>
              <input v-model="form.bairro" type="text" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 bg-gray-50 disabled:bg-gray-200 disabled:text-gray-500" required :disabled="isLocked.bairro" />
            </div>

            <!-- Endereço -->
            <div class="col-span-2">
              <label class="block text-sm font-medium text-gray-700">Endereço (Rua, Av, etc)</label>
              <input v-model="form.endereco" type="text" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 bg-gray-50 disabled:bg-gray-200 disabled:text-gray-500" required :disabled="isLocked.endereco" />
            </div>

            <!-- Número e Complemento -->
            <div>
              <label class="block text-sm font-medium text-gray-700">Número</label>
              <input v-model="form.numero" type="text" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Complemento</label>
              <input v-model="form.complemento" type="text" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" />
            </div>
        </div>
      </div>

      <!-- STEP 3: DOCUMENTOS (UPLOADS) -->
      <div v-show="currentStep === 3" class="bg-white p-6 rounded-lg shadow-md border-t-4 border-purple-500 animate-fade-in">
        <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-purple-500">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
            </svg>
            UPLOADS
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-end bg-gray-50 p-4 rounded-md border border-dashed border-gray-300">
            <div>
                <label class="block text-sm font-medium text-gray-700">Nome do Arquivo</label>
                <input v-model="uploadForm.nome" type="text" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" placeholder="Ex: RG Frente, Comprovante..." />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Arquivo</label>
                <input type="file" @change="handleFileChange" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100" ref="fileInput" />
            </div>
            <div class="md:col-span-2 flex justify-end">
                <button type="button" @click="addArquivo" class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Adicionar Arquivo
                </button>
            </div>
        </div>

        <!-- Lista de Arquivos -->
        <div v-if="arquivos.length > 0" class="mt-4">
            <h4 class="text-sm font-medium text-gray-700 mb-2">Arquivos Adicionados:</h4>
            <ul class="space-y-2">
                <li v-for="(arquivo, index) in arquivos" :key="index" class="flex justify-between items-center bg-gray-100 p-2 rounded-md">
                    <div class="flex items-center gap-2">
                         <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-gray-500">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                        </svg>
                        <span class="font-medium text-sm">{{ arquivo.nome }}</span>
                        <span class="text-xs text-gray-500">({{ arquivo.file.name }})</span>
                    </div>
                    <button type="button" @click="removeArquivo(index)" class="text-red-500 hover:text-red-700">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                           <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                        </svg>
                    </button>
                </li>
            </ul>
        </div>
      </div>

      <!-- Navigation Buttons -->
      <div class="mt-6 flex justify-between">
        <button 
            type="button" 
            v-if="currentStep > 1" 
            @click="prevStep"
            class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 flex items-center gap-2"
        >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
            </svg>
            Anterior
        </button>
        <div v-else></div> <!-- Spacer -->

        <div class="flex space-x-3">
            <router-link to="/admin/atletas" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 flex items-center gap-2">
                Cancelar
            </router-link>
            
            <button 
                type="button" 
                v-if="currentStep < 3" 
                @click="nextStep"
                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 flex items-center gap-2"
            >
                Próximo
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                </svg>
            </button>

            <button 
                type="submit" 
                v-if="currentStep === 3" 
                class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 flex items-center gap-2" 
                :disabled="loading"
            >
                <svg v-if="!loading" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                   <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
                <span v-else class="animate-spin h-5 w-5 border-2 border-white border-t-transparent rounded-full block"></span>
                {{ loading ? 'Salvando...' : 'Salvar' }}
            </button>
        </div>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed, watch } from 'vue';
import { useAtletaStore } from '../store/atletaStore';
import { useRouter, useRoute } from 'vue-router';
import { formatCpf } from '@/utils/formatCpf';

const props = defineProps({
  isEditing: {
    type: Boolean,
    default: false
  }
});

const store = useAtletaStore();
const router = useRouter();
const route = useRoute();

const loading = ref(false);
const loadingCpf = ref(false);
const loadingTitulo = ref(false);
const errors = reactive({});
const loadingCep = ref(false);
const tituloConsultaAviso = ref('');
const tituloConsultaTentada = ref(false);
const eleitoralManualLiberado = ref(false);
const tituloVerificadoApi = ref(false);
const possuiTituloMenorAteOutubro = ref(false);



const arquivos = reactive([]);
const uploadForm = reactive({ nome: '', file: null });
const fileInput = ref(null);

const form = reactive({
  nome: '',
  cpf: '',
  data_nascimento: '',
  sexo: '', 
  nome_mae: '',
  nome_pai: '',
  titulo_eleitor: '',
  zona_eleitoral: '',
  secao_eleitoral: '',
  bairro_eleitoral: '', 
  cidade_eleitoral: '', 
  uf_eleitoral: '', 
  titulo_verificado: false,
  validacao_eleitoral_pendente: false,
  cep: '',
  endereco: '',
  numero: '',
  complemento: '',
  bairro: '',
  cidade: '',
  estado: '',
  telefone: '',
  receive_notifications: true,
  // email removido
  // modalidades removido
});

const isLocked = reactive({
  nome: false,
  cpf: false,
  data_nascimento: false,
  sexo: false, 
  nome_mae: false,
  titulo_eleitor: false,
  zona_eleitoral: false,
  secao_eleitoral: false,
  bairro_eleitoral: false, 
  cidade_eleitoral: false, 
  uf_eleitoral: false, 
  cep: false,
  endereco: false,
  bairro: false,
  cidade: false,
  estado: false,
});

// Wizard Logic
const currentStep = ref(1);
const steps = [
    { label: 'Dados Pessoais' },
    { label: 'Endereço' },
    { label: 'Documentos' }
];

const progressPercentage = computed(() => {
    return (currentStep.value / steps.length) * 100;
});

const isMenorDe16 = computed(() => {
    if (!form.data_nascimento) return false;
    const hoje = new Date();
    const nascimento = new Date(form.data_nascimento);
    let idade = hoje.getFullYear() - nascimento.getFullYear();
    const m = hoje.getMonth() - nascimento.getMonth();
    if (m < 0 || (m === 0 && hoje.getDate() < nascimento.getDate())) {
        idade--;
    }
    return idade < 16;
});

const completa16AteOutubro = computed(() => {
    if (!form.data_nascimento) return false;
    const nascimento = new Date(form.data_nascimento);
    if (Number.isNaN(nascimento.getTime())) return false;
    const hoje = new Date();
    const anoAtual = hoje.getFullYear();
    const dataLimite = new Date(anoAtual, 9, 31, 23, 59, 59);
    const aniversario16 = new Date(nascimento.getFullYear() + 16, nascimento.getMonth(), nascimento.getDate(), 0, 0, 0);
    return aniversario16 >= hoje && aniversario16 <= dataLimite;
});

const podeExibirOpcaoTituloMenor = computed(() => isMenorDe16.value && completa16AteOutubro.value);
const deveExibirDadosEleitorais = computed(() => !isMenorDe16.value || (podeExibirOpcaoTituloMenor.value && possuiTituloMenorAteOutubro.value));

const podeBuscarTitulo = computed(() => {
    if (!deveExibirDadosEleitorais.value) return false;
    return Boolean(form.cpf && form.data_nascimento && form.nome_mae);
});

const canEditElectoral = computed(() => eleitoralManualLiberado.value);

const nextStep = () => {
    // Simple validation before moving
    if (currentStep.value === 1) {
        // Validação básica comum
        if (!form.nome || !form.cpf || !form.data_nascimento || !form.nome_mae || !form.sexo) {
             alert('Por favor, preencha os campos obrigatórios:\n- CPF\n- Nome\n- Data de Nascimento\n- Sexo\n- Nome da Mãe');
             return;
        }

        // Validação de dados eleitorais obrigatória para maiores de 16
        if (!isMenorDe16.value && !tituloConsultaTentada.value) {
            alert('Antes de avançar, tente validar os dados eleitorais no TSE.');
            return;
        }

        if (!isMenorDe16.value && (!form.zona_eleitoral || !form.secao_eleitoral || !form.bairro_eleitoral || !form.cidade_eleitoral || !form.uf_eleitoral)) {
            alert('Preencha zona, seção, município, bairro e UF eleitorais.');
            return;
        }

        // Para menor que completa 16 até outubro e marcou que possui título,
        // libera busca/preenchimento, mas não obriga completar tudo.
    }
    if (currentStep.value === 2) {
        if (!form.cep || !form.endereco || !form.cidade || !form.estado || !form.bairro) {
            alert('Por favor, preencha todos os campos do endereço.');
            return;
        }
    }
    if (currentStep.value < steps.length) {
        currentStep.value++;
    }
};

const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
    }
};

const canNavigateTo = (step) => {
    // Prevent skipping steps without validation logic if needed, 
    // but allowing going back is usually fine.
    // For simplicity, allow user to click steps if they've already passed them or is current
    // return step <= currentStep.value; 
    
    // Or allow flexible navigation
    return true; 
};

const handleFileChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        uploadForm.file = file;
        if (!uploadForm.nome) {
            uploadForm.nome = file.name;
        }
    }
};

const addArquivo = () => {
    if (uploadForm.file && uploadForm.nome) {
        arquivos.push({
            file: uploadForm.file,
            nome: uploadForm.nome
        });
        // Limpar form de upload
        uploadForm.nome = '';
        uploadForm.file = null;
        if (fileInput.value) {
            fileInput.value.value = '';
        }
    } else {
        alert('Selecione um arquivo e informe um nome.');
    }
};

const removeArquivo = (index) => {
    arquivos.splice(index, 1);
};

// Função para consultar CEP via ViaCEP
const consultarCep = async () => {
  const cep = form.cep.replace(/\D/g, '');
  
  if (cep.length !== 8) {
    return;
  }

  loadingCep.value = true;
  try {
    const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
    const data = await response.json();

    if (!data.erro) {
      if (data.logradouro) {
          form.endereco = data.logradouro;
          isLocked.endereco = true;
      } else {
          isLocked.endereco = false;
      }

      if (data.bairro) {
          form.bairro = data.bairro;
          isLocked.bairro = true;
      } else {
          isLocked.bairro = false;
      }

      if (data.localidade) {
          form.cidade = data.localidade;
          isLocked.cidade = true;
      } else {
          isLocked.cidade = false;
      }

      if (data.uf) {
          form.estado = data.uf;
          isLocked.estado = true;
      } else {
          isLocked.estado = false;
      }
    } else {
      alert('CEP não encontrado.');
      // Desbloquear se não encontrar
      isLocked.endereco = false;
      isLocked.bairro = false;
      isLocked.cidade = false;
      isLocked.estado = false;
    }
  } catch (error) {
    console.error('Erro ao consultar CEP:', error);
    alert('Erro ao consultar CEP.');
  } finally {
    loadingCep.value = false;
  }
};

const normalizeDateInput = (value) => {
  if (!value) return '';
  const raw = String(value).trim();
  const dateOnly = raw.includes('T') ? raw.split('T')[0] : raw.split(' ')[0];
  if (/^\d{4}-\d{2}-\d{2}$/.test(dateOnly)) return dateOnly;
  if (/^\d{2}\/\d{2}\/\d{4}$/.test(dateOnly)) {
    const [dd, mm, yyyy] = dateOnly.split('/');
    return `${yyyy}-${mm}-${dd}`;
  }
  if (/^\d{2}-\d{2}-\d{4}$/.test(dateOnly)) {
    const [dd, mm, yyyy] = dateOnly.split('-');
    return `${yyyy}-${mm}-${dd}`;
  }
  const parsed = new Date(raw);
  if (!Number.isNaN(parsed.getTime())) {
    const year = parsed.getFullYear();
    const month = String(parsed.getMonth() + 1).padStart(2, '0');
    const day = String(parsed.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
  }
  return '';
};

onMounted(async () => {
  if (props.isEditing) {
    const id = route.params.id;
    loading.value = true;
    try {
        await store.fetchAtleta(id);
        const atleta = store.atleta?.data ?? store.atleta;

        if (store.error && !atleta) {
            alert(store.error);
            return;
        }

        if (atleta) {
            Object.keys(form).forEach(key => {
                if (atleta[key] !== undefined && atleta[key] !== null) {
                    if (key === 'data_nascimento' && atleta[key]) {
                        form[key] = normalizeDateInput(atleta[key]);
                    } else {
                        form[key] = atleta[key];
                    }
                }
            });

            tituloVerificadoApi.value = Boolean(form.titulo_verificado);
            eleitoralManualLiberado.value = !tituloVerificadoApi.value;
            tituloConsultaTentada.value = Boolean(form.zona_eleitoral || form.secao_eleitoral || form.bairro_eleitoral || form.cidade_eleitoral || form.uf_eleitoral);
            possuiTituloMenorAteOutubro.value = Boolean(form.titulo_eleitor || form.zona_eleitoral || form.secao_eleitoral || form.bairro_eleitoral || form.cidade_eleitoral || form.uf_eleitoral);

            // Populate existing files list (visual only, can't edit existing files easily yet)
            if (atleta.arquivos && atleta.arquivos.length > 0) {
                // We can't put them in 'arquivos' reactive array because that's for NEW uploads
                // But we could display them separately or add a property to show existing files
                // For now, let's just log or handle if needed.
                // Maybe add to a separate list 'arquivosExistentes'
            }
        }
    } catch (error) {
        console.error('Erro ao carregar atleta:', error);
        alert('Erro ao carregar dados do atleta.');
    } finally {
        loading.value = false;
    }
  }
});

const consultarCpf = async () => {
  const cpfLimpo = String(form.cpf || '').replace(/\D/g, '');
  if (cpfLimpo.length !== 11) {
    tituloConsultaAviso.value = 'Informe um CPF válido para consultar.';
    return;
  }

  loadingCpf.value = true;
  tituloConsultaAviso.value = '';
  try {
    const dados = await store.consultarCpf(cpfLimpo);
    if (!dados) {
      tituloConsultaAviso.value = store.error || 'Não foi possível consultar o CPF.';
      return;
    }
    if (dados.nome) { form.nome = dados.nome; isLocked.nome = true; }
    if (dados.data_nascimento) {
      const dataNormalizada = normalizeDateInput(dados.data_nascimento);
      form.data_nascimento = dataNormalizada || '';
      isLocked.data_nascimento = Boolean(dataNormalizada);
    }
    if (dados.sexo) { form.sexo = dados.sexo; isLocked.sexo = true; }
    if (dados.nome_mae) { form.nome_mae = dados.nome_mae; isLocked.nome_mae = true; }
    tituloConsultaTentada.value = false;
    eleitoralManualLiberado.value = false;
    tituloVerificadoApi.value = false;
    form.titulo_verificado = false;
  } finally {
    loadingCpf.value = false;
  }
};

const consultarTitulo = async () => {
  if (!deveExibirDadosEleitorais.value) return;
  if (!form.cpf || !form.data_nascimento || !form.nome_mae) {
      tituloConsultaAviso.value = 'Preencha CPF, data de nascimento e nome da mãe antes da busca eleitoral.';
      return;
  }

  loadingTitulo.value = true;
  tituloConsultaTentada.value = true;
  tituloConsultaAviso.value = '';
  try {
    const dados = await store.consultarTitulo({
        cpf: String(form.cpf || '').replace(/\D/g, ''),
        data_nascimento: form.data_nascimento,
        nome_mae: form.nome_mae
    });

    if (dados && dados.retorno) {
      const domicilio = dados.retorno.domicilioEleitoral || {};
      const identificacao = dados.retorno.identificacao || {};

      if (domicilio.zona) { 
          form.zona_eleitoral = domicilio.zona.toString(); 
          isLocked.zona_eleitoral = true; 
      }
      if (domicilio.secao) { 
          form.secao_eleitoral = domicilio.secao.toString(); 
          isLocked.secao_eleitoral = true; 
      }
      if (domicilio.bairro) { 
          form.bairro_eleitoral = domicilio.bairro; 
          isLocked.bairro_eleitoral = true; 
      }
      if (domicilio.municipio) { 
          form.cidade_eleitoral = domicilio.municipio; 
          isLocked.cidade_eleitoral = true; 
      }
      if (domicilio.uf) { 
          form.uf_eleitoral = domicilio.uf; 
          isLocked.uf_eleitoral = true; 
      }

      if (identificacao.inscricao) { 
          form.titulo_eleitor = identificacao.inscricao; 
          isLocked.titulo_eleitor = true; 
      }
      const completo = Boolean(form.zona_eleitoral && form.secao_eleitoral && form.bairro_eleitoral && form.cidade_eleitoral && form.uf_eleitoral);
      tituloVerificadoApi.value = completo;
      form.titulo_verificado = completo;
      eleitoralManualLiberado.value = !completo;
      if (!completo) {
        tituloConsultaAviso.value = 'A API não retornou todos os dados eleitorais. Complete manualmente.';
      }
    } else {
      eleitoralManualLiberado.value = true;
      tituloVerificadoApi.value = false;
      form.titulo_verificado = false;
      tituloConsultaAviso.value = store.error || 'Não foi possível preencher os dados eleitorais automaticamente.';
    }
  } finally {
    loadingTitulo.value = false;
  }
};

const limparDadosEleitorais = () => {
  form.titulo_eleitor = '';
  form.zona_eleitoral = '';
  form.secao_eleitoral = '';
  form.bairro_eleitoral = '';
  form.cidade_eleitoral = '';
  form.uf_eleitoral = '';
  form.titulo_verificado = false;
  tituloConsultaTentada.value = false;
  tituloConsultaAviso.value = '';
  tituloVerificadoApi.value = false;
  eleitoralManualLiberado.value = false;
  isLocked.titulo_eleitor = false;
  isLocked.zona_eleitoral = false;
  isLocked.secao_eleitoral = false;
  isLocked.bairro_eleitoral = false;
  isLocked.cidade_eleitoral = false;
  isLocked.uf_eleitoral = false;
};

watch(possuiTituloMenorAteOutubro, (possui) => {
  if (!possui && podeExibirOpcaoTituloMenor.value) {
    limparDadosEleitorais();
  }
});

watch([isMenorDe16, completa16AteOutubro], ([menor, completa]) => {
  if (!menor || !completa) {
    possuiTituloMenorAteOutubro.value = false;
  }
});

const submitForm = async () => {
  loading.value = true;
  errors.value = {}; 
  
  // Criar FormData para envio de arquivos
  const formData = new FormData();
  
  // Adicionar campos do formulário
  const tituloVerificadoFinal = Boolean(tituloVerificadoApi.value || form.titulo_verificado);
  form.titulo_verificado = tituloVerificadoFinal;
  form.validacao_eleitoral_pendente = Boolean(deveExibirDadosEleitorais.value && tituloConsultaTentada.value && !tituloVerificadoFinal);
  Object.keys(form).forEach(key => {
      if (form[key] !== null && form[key] !== undefined) {
          if (typeof form[key] === 'boolean') {
              formData.append(key, form[key] ? 1 : 0);
          } else {
              formData.append(key, form[key]);
          }
      }
  });

  // Adicionar arquivos
  arquivos.forEach((arquivo, index) => {
      formData.append(`arquivos[${index}]`, arquivo.file);
      formData.append(`nomes_arquivos[${index}]`, arquivo.nome);
  });
  
  // Se for edição, o Laravel precisa do _method PUT para processar FormData corretamente
  if (props.isEditing) {
      formData.append('_method', 'PUT');
  }

  let success = false;
  if (props.isEditing) {
    success = await store.updateAtleta(route.params.id, formData);
  } else {
    success = await store.createAtleta(formData);
  }

  if (success) {
    if (store.successMessage) {
        alert(store.successMessage);
    } else {
        alert('Atleta salvo com sucesso!');
    }
    router.push('/admin/atletas');
  } else {
      alert('Erro ao salvar: ' + store.error);
  }
  loading.value = false;
};

const marcarTituloComoVerificado = () => {
  form.titulo_verificado = true;
  form.validacao_eleitoral_pendente = false;
  tituloConsultaAviso.value = '';
};
</script>

<style scoped>
.animate-fade-in {
    animation: fadeIn 180ms var(--ease-out);
}

@media (prefers-reduced-motion: reduce) {
    .animate-fade-in {
        animation: fadeIn 180ms ease;
    }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
</style>
