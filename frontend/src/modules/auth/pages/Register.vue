<template>
  <div class="w-full max-w-5xl mx-auto">
    <div class="text-center mb-8">
      <h1 class="text-3xl font-bold text-gray-900 mb-2">Cadastro de Atleta</h1>
      <p class="text-gray-500">Preencha em etapas para concluir o cadastro com segurança</p>
    </div>

    <div class="mb-6 bg-white rounded-2xl border border-slate-200 shadow-sm p-4 md:p-5">
      <div class="flex items-center justify-between mb-3">
        <span class="text-sm font-semibold text-slate-700">Progresso do cadastro</span>
        <span class="text-sm font-bold text-blue-600">{{ progressPercent }}%</span>
      </div>
      <div class="h-2 w-full bg-slate-100 rounded-full overflow-hidden">
        <div class="h-full bg-blue-600 transition-all duration-300" :style="{ width: `${progressPercent}%` }"></div>
      </div>
      <div class="grid grid-cols-2 md:grid-cols-5 gap-2 mt-3 text-xs">
        <button type="button" @click="goToStep(1)" class="px-2 py-1 rounded-md text-left" :class="currentStep === 1 ? 'bg-blue-50 text-blue-700 border border-blue-200' : stepStates.identificacao ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-50 text-slate-500'">1. CPF</button>
        <button type="button" @click="goToStep(2)" class="px-2 py-1 rounded-md text-left" :class="currentStep === 2 ? 'bg-blue-50 text-blue-700 border border-blue-200' : stepStates.pessoais ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-50 text-slate-500'">2. Dados</button>
        <button type="button" @click="goToStep(3)" class="px-2 py-1 rounded-md text-left" :class="currentStep === 3 ? 'bg-blue-50 text-blue-700 border border-blue-200' : stepStates.eleitorais ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-50 text-slate-500'">3. Eleitoral</button>
        <button type="button" @click="goToStep(4)" class="px-2 py-1 rounded-md text-left" :class="currentStep === 4 ? 'bg-blue-50 text-blue-700 border border-blue-200' : stepStates.contato ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-50 text-slate-500'">4. Contato</button>
        <button type="button" @click="goToStep(5)" class="px-2 py-1 rounded-md text-left" :class="currentStep === 5 ? 'bg-blue-50 text-blue-700 border border-blue-200' : form.receive_notifications ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-50 text-slate-500'">5. Finalizar</button>
      </div>
    </div>

    <form @submit.prevent="handleRegister" class="space-y-6 bg-white rounded-2xl border border-slate-200 shadow-xl p-5 md:p-7">
      <div v-if="error" class="bg-red-50 text-red-700 p-4 rounded-xl text-sm flex items-start gap-2 border border-red-100">
        <AlertCircle :size="16" class="mt-0.5 shrink-0" />
        <div>
          <p class="font-medium">{{ error }}</p>
          <p class="text-red-600/90 mt-1">Confira o bloco final do formulário para corrigir rapidamente.</p>
        </div>
      </div>

      <section v-if="currentStep === 1" class="rounded-xl border border-slate-200 bg-slate-50 p-4 md:p-5 space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="text-sm font-semibold text-slate-700">1. Identificação por CPF</h3>
          <span class="text-xs text-slate-500">Etapa 1 de 5</span>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
        <div>
          <label for="cpf" class="block text-sm font-medium text-gray-700 mb-2">CPF</label>
          <div class="relative">
            <input
              id="cpf"
              v-model="form.cpf"
              type="text"
              required
              maxlength="14"
              @input="formatCpfInput"
              :disabled="cpfConsultado || loadingCpf || loading"
              class="w-full pl-10 pr-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors bg-white disabled:bg-slate-100 disabled:text-slate-500"
              placeholder="000.000.000-00"
            />
            <CreditCard :size="20" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
          </div>
        </div>

        <button
          type="button"
          @click="consultarCpf"
          :disabled="loadingCpf || loading || cpfConsultado"
          class="h-[46px] w-full md:w-auto bg-blue-600 text-white px-4 rounded-lg font-semibold hover:bg-blue-700 transition-all flex items-center justify-center gap-2 disabled:opacity-70 shadow-sm"
        >
          <Loader2 v-if="loadingCpf" :size="18" class="animate-spin" />
          <span v-else>Buscar CPF</span>
        </button>

        <button
          v-if="cpfConsultado"
          type="button"
          @click="liberarCpf"
          :disabled="loadingCpf || loading || loadingTitulo"
          class="h-[46px] w-full md:w-auto bg-white text-slate-700 border border-slate-300 px-4 rounded-lg font-semibold hover:bg-slate-50 transition-all disabled:opacity-70"
        >
          Alterar CPF
        </button>

        <div class="text-xs text-gray-500 md:text-right md:ml-auto">
          {{ cpfConsultado ? 'Dados carregados. Complete os campos restantes.' : 'Primeiro consulte o CPF para preencher automaticamente.' }}
        </div>
      </div>
      <div class="pt-2 flex justify-end">
        <button type="button" @click="goToNextStep" class="h-[44px] px-4 rounded-lg bg-blue-600 text-white font-semibold hover:bg-blue-700 transition-all">
          Próxima etapa
        </button>
      </div>
      </section>

      <section v-if="currentStep === 2" class="rounded-xl border border-slate-200 bg-white p-4 md:p-5 space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="text-sm font-semibold text-slate-700">2. Dados pessoais</h3>
          <span class="text-xs text-slate-500">Etapa 2 de 5</span>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label for="nome" class="block text-sm font-medium text-gray-700 mb-2">Nome Completo</label>
          <div class="relative">
            <input
              id="nome"
              v-model="form.nome"
              type="text"
              required
              :disabled="!cpfConsultado || isFieldLocked('nome')"
              class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors disabled:bg-gray-100 disabled:text-gray-500"
              placeholder="Seu nome completo"
            />
            <User :size="20" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
          </div>
        </div>

        <div>
          <label for="data_nascimento" class="block text-sm font-medium text-gray-700 mb-2">Data de Nascimento</label>
          <input
            id="data_nascimento"
            v-model="form.data_nascimento"
            type="date"
            required
            :disabled="!cpfConsultado || isFieldLocked('data_nascimento')"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors disabled:bg-gray-100 disabled:text-gray-500"
          />
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label for="sexo" class="block text-sm font-medium text-gray-700 mb-2">Sexo</label>
          <select
            id="sexo"
            v-model="form.sexo"
            required
            :disabled="!cpfConsultado || isFieldLocked('sexo')"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors disabled:bg-gray-100 disabled:text-gray-500"
          >
            <option value="">Selecione</option>
            <option value="MASCULINO">Masculino</option>
            <option value="FEMININO">Feminino</option>
            <option value="OUTRO">Outro</option>
          </select>
        </div>
      </div>

      <div>
        <label for="nome_mae" class="block text-sm font-medium text-gray-700 mb-2">Nome da Mãe</label>
        <input
          id="nome_mae"
          v-model="form.nome_mae"
          type="text"
          required
          :disabled="!cpfConsultado || isFieldLocked('nome_mae')"
          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors disabled:bg-gray-100 disabled:text-gray-500"
          placeholder="Nome completo da mãe"
        />
      </div>
      <div class="pt-2 flex items-center justify-between">
        <button type="button" @click="goToPreviousStep" class="h-[44px] px-4 rounded-lg bg-white text-slate-700 border border-slate-300 font-semibold hover:bg-slate-50 transition-all">
          Voltar
        </button>
        <button type="button" @click="goToNextStep" class="h-[44px] px-4 rounded-lg bg-blue-600 text-white font-semibold hover:bg-blue-700 transition-all">
          Próxima etapa
        </button>
      </div>
      </section>

      <div v-if="currentStep === 3 && mostrarCamposEleitorais" class="rounded-xl border border-indigo-100 bg-indigo-50/40 p-4 md:p-5">
        <div class="flex items-center justify-between mb-3">
          <h3 class="text-sm font-semibold text-slate-700">3. Dados eleitorais</h3>
          <span class="text-xs text-slate-500">Etapa 3 de 5</span>
        </div>
        <div class="flex items-center gap-3 mb-3">
          <button
            type="button"
            @click="consultarTitulo"
            :disabled="loadingTitulo || !podeBuscarTitulo"
            class="h-[42px] bg-indigo-600 text-white px-4 rounded-lg font-semibold hover:bg-indigo-700 transition-all flex items-center justify-center gap-2 disabled:opacity-60"
          >
            <Loader2 v-if="loadingTitulo" :size="16" class="animate-spin" />
            <span v-else>Buscar dados do título</span>
          </button>
        </div>
        <div v-if="eleitoralPreenchidoApi" class="mb-3 bg-emerald-50 text-emerald-700 p-3 rounded-lg text-sm border border-emerald-100">
          Dados eleitorais validados e preenchidos automaticamente pela API.
        </div>
        <div v-if="tituloAutoFillWarning" class="mb-3 bg-amber-50 text-amber-700 p-3 rounded-lg text-sm">
          {{ tituloAutoFillWarning }}
        </div>
        <div v-if="consultaTituloBloqueadaApi" class="mb-3 bg-orange-50 text-orange-700 p-3 rounded-lg text-sm">
          Consulta eleitoral por API bloqueada para este cadastro. Preencha os dados manualmente e a para análise da Administração.
        </div>
        <div v-if="eleitoralManualLiberado && !eleitoralPreenchidoApi" class="mb-3 bg-slate-100 text-slate-700 p-3 rounded-lg text-sm">
          Não foi possível validar no TSE. Complete os dados eleitorais manualmente para continuar.
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label for="titulo_eleitor" class="block text-sm font-medium text-gray-700 mb-2">Título de Eleitor</label>
            <input
              id="titulo_eleitor"
              v-model="form.titulo_eleitor"
              type="text"
              :readonly="!eleitoralManualLiberado"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg text-slate-700"
              :class="!eleitoralManualLiberado ? 'bg-slate-100 cursor-not-allowed' : 'bg-white'"
              placeholder="Número do título"
            />
          </div>
          <div>
            <label for="zona_eleitoral" class="block text-sm font-medium text-gray-700 mb-2">Zona Eleitoral</label>
            <input
              id="zona_eleitoral"
              v-model="form.zona_eleitoral"
              type="text"
              :readonly="!eleitoralManualLiberado"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg text-slate-700"
              :class="!eleitoralManualLiberado ? 'bg-slate-100 cursor-not-allowed' : 'bg-white'"
              placeholder="Zona"
            />
          </div>
          <div>
            <label for="secao_eleitoral" class="block text-sm font-medium text-gray-700 mb-2">Seção Eleitoral</label>
            <input
              id="secao_eleitoral"
              v-model="form.secao_eleitoral"
              type="text"
              :readonly="!eleitoralManualLiberado"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg text-slate-700"
              :class="!eleitoralManualLiberado ? 'bg-slate-100 cursor-not-allowed' : 'bg-white'"
              placeholder="Seção"
            />
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
          <div>
            <label for="bairro_eleitoral" class="block text-sm font-medium text-gray-700 mb-2">Bairro Eleitoral</label>
            <input
              id="bairro_eleitoral"
              v-model="form.bairro_eleitoral"
              type="text"
              :readonly="!eleitoralManualLiberado"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg text-slate-700"
              :class="!eleitoralManualLiberado ? 'bg-slate-100 cursor-not-allowed' : 'bg-white'"
              placeholder="Bairro"
            />
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
          <div>
            <label for="cidade_eleitoral" class="block text-sm font-medium text-gray-700 mb-2">Cidade Eleitoral</label>
            <input
              id="cidade_eleitoral"
              v-model="form.cidade_eleitoral"
              type="text"
              :readonly="!eleitoralManualLiberado"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg text-slate-700"
              :class="!eleitoralManualLiberado ? 'bg-slate-100 cursor-not-allowed' : 'bg-white'"
              placeholder="Cidade"
            />
          </div>
          <div>
            <label for="uf_eleitoral" class="block text-sm font-medium text-gray-700 mb-2">UF Eleitoral</label>
            <input
              id="uf_eleitoral"
              v-model="form.uf_eleitoral"
              type="text"
              :readonly="!eleitoralManualLiberado"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg text-slate-700"
              :class="!eleitoralManualLiberado ? 'bg-slate-100 cursor-not-allowed' : 'bg-white'"
              placeholder="UF"
            />
          </div>
        </div>
        <div class="pt-4 flex items-center justify-between">
          <button type="button" @click="goToPreviousStep" class="h-[44px] px-4 rounded-lg bg-white text-slate-700 border border-slate-300 font-semibold hover:bg-slate-50 transition-all">
            Voltar
          </button>
          <button type="button" @click="goToNextStep" class="h-[44px] px-4 rounded-lg bg-blue-600 text-white font-semibold hover:bg-blue-700 transition-all">
            Próxima etapa
          </button>
        </div>
      </div>

      <div v-else-if="currentStep === 3 && cpfConsultado" class="rounded-xl border border-blue-100 bg-blue-50/60 p-4 md:p-5 space-y-4">
        <div class="bg-blue-50 text-blue-700 p-3 rounded-lg text-sm">
          Atleta com menos de 16 anos. Dados eleitorais não são obrigatórios.
        </div>
        <div class="pt-2 flex items-center justify-between">
          <button type="button" @click="goToPreviousStep" class="h-[44px] px-4 rounded-lg bg-white text-slate-700 border border-slate-300 font-semibold hover:bg-slate-50 transition-all">
            Voltar
          </button>
          <button type="button" @click="goToNextStep" class="h-[44px] px-4 rounded-lg bg-blue-600 text-white font-semibold hover:bg-blue-700 transition-all">
            Próxima etapa
          </button>
        </div>
      </div>

      <section v-if="currentStep === 4" class="rounded-xl border border-slate-200 bg-white p-4 md:p-5 space-y-4">
      <div class="flex items-center justify-between">
        <h3 class="text-sm font-semibold text-slate-700">4. Endereço e contato</h3>
        <span class="text-xs text-slate-500">Etapa 4 de 5</span>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="md:col-span-1">
          <label for="cep" class="block text-sm font-medium text-gray-700 mb-2">CEP</label>
          <input
            id="cep"
            v-model="form.cep"
            type="text"
            required
            @input="formatCepInput"
            @blur="consultarCep"
            :disabled="!cpfConsultado || isFieldLocked('cep') || loadingCep"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors disabled:bg-gray-100 disabled:text-gray-500"
            placeholder="00000-000"
          />
        </div>

        <div class="md:col-span-3">
          <label for="endereco" class="block text-sm font-medium text-gray-700 mb-2">Endereço</label>
          <input
            id="endereco"
            v-model="form.endereco"
            type="text"
            required
            :disabled="!cpfConsultado || isFieldLocked('endereco')"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors disabled:bg-gray-100 disabled:text-gray-500"
            placeholder="Rua, avenida, etc."
          />
        </div>
      </div>

      <div v-if="loadingCep" class="text-xs text-blue-600">
        Consultando CEP...
      </div>
      <div v-else-if="cepAutoFillWarning" class="text-xs text-amber-700">
        {{ cepAutoFillWarning }}
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label for="bairro" class="block text-sm font-medium text-gray-700 mb-2">Bairro</label>
          <input
            id="bairro"
            v-model="form.bairro"
            type="text"
            required
            :disabled="!cpfConsultado || isFieldLocked('bairro')"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors disabled:bg-gray-100 disabled:text-gray-500"
            placeholder="Seu bairro"
          />
        </div>

        <div>
          <label for="cidade" class="block text-sm font-medium text-gray-700 mb-2">Cidade</label>
          <input
            id="cidade"
            v-model="form.cidade"
            type="text"
            required
            :disabled="!cpfConsultado || isFieldLocked('cidade')"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors disabled:bg-gray-100 disabled:text-gray-500"
            placeholder="Sua cidade"
          />
        </div>

        <div>
          <label for="estado" class="block text-sm font-medium text-gray-700 mb-2">UF</label>
          <input
            id="estado"
            v-model="form.estado"
            type="text"
            maxlength="2"
            required
            :disabled="!cpfConsultado || isFieldLocked('estado')"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors uppercase disabled:bg-gray-100 disabled:text-gray-500"
            placeholder="SP"
          />
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label for="telefone" class="block text-sm font-medium text-gray-700 mb-2">Telefone</label>
          <input
            id="telefone"
            v-model="form.telefone"
            type="text"
            :disabled="!cpfConsultado || isFieldLocked('telefone')"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors disabled:bg-gray-100 disabled:text-gray-500"
            placeholder="(00) 00000-0000"
          />
        </div>

        <div>
          <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
          <div class="relative">
            <input
              id="email"
              v-model="form.email"
              type="email"
              :disabled="!cpfConsultado || isFieldLocked('email')"
              class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors disabled:bg-gray-100 disabled:text-gray-500"
              placeholder="seu@email.com"
            />
            <Mail :size="20" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
          </div>
        </div>
      </div>
      <div class="pt-2 flex items-center justify-between">
        <button type="button" @click="goToPreviousStep" class="h-[44px] px-4 rounded-lg bg-white text-slate-700 border border-slate-300 font-semibold hover:bg-slate-50 transition-all">
          Voltar
        </button>
        <button type="button" @click="goToNextStep" class="h-[44px] px-4 rounded-lg bg-blue-600 text-white font-semibold hover:bg-blue-700 transition-all">
          Próxima etapa
        </button>
      </div>
      </section>

      <section v-if="currentStep === 5" class="rounded-xl border border-slate-200 bg-slate-50 p-4 md:p-5 space-y-4">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
          <p class="text-sm text-slate-700">
            Revise os dados e confirme o recebimento de notificações para finalizar.
          </p>
          <span class="text-xs font-semibold px-2.5 py-1 rounded-full" :class="cpfConsultado ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'">
            {{ cpfConsultado ? 'Pronto para cadastrar' : 'Consulte o CPF antes de enviar' }}
          </span>
        </div>

        <label class="flex items-start gap-3 p-3 rounded-xl border border-slate-200 bg-white">
          <input
            v-model="form.receive_notifications"
            type="checkbox"
            :disabled="!cpfConsultado"
            class="mt-1 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 disabled:opacity-60"
          />
          <span class="text-sm text-gray-700">
            Aceito receber notificações no WhatsApp
          </span>
        </label>

        <div ref="submitFeedbackRef" v-if="error || validationErrors.length" class="bg-red-50 border border-red-100 rounded-xl p-4">
          <div class="flex items-start gap-2 text-red-700">
            <AlertCircle :size="16" class="mt-0.5 shrink-0" />
            <div class="w-full">
              <p class="font-semibold">{{ error || 'Revise os campos destacados.' }}</p>
              <ul v-if="validationErrors.length" class="mt-2 space-y-1 text-sm text-red-700/95">
                <li v-for="item in validationErrors" :key="item">• {{ item }}</li>
              </ul>
            </div>
          </div>
        </div>

        <button
          type="submit"
          :disabled="loading || !cpfConsultado"
          class="w-full bg-blue-600 text-white py-3.5 rounded-xl font-semibold hover:bg-blue-700 focus:ring-4 focus:ring-blue-100 transition-all flex items-center justify-center gap-2 disabled:opacity-70 shadow-sm"
        >
          <Loader2 v-if="loading" :size="20" class="animate-spin" />
          <span v-else>Cadastrar Atleta</span>
        </button>
        <button type="button" @click="goToPreviousStep" class="w-full h-[44px] rounded-lg bg-white text-slate-700 border border-slate-300 font-semibold hover:bg-slate-50 transition-all">
          Voltar
        </button>
      </section>
    </form>

    <div class="mt-8 text-center text-sm text-gray-500">
      Já tem uma conta? <router-link to="/login" class="text-blue-600 font-medium hover:underline">Faça login</router-link>
    </div>

    <div v-if="showSuccessModal" class="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
      <div class="w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-green-100 overflow-hidden">
        <div class="bg-green-50 border-b border-green-100 px-6 py-4">
          <h3 class="text-lg font-bold text-green-800">Cadastro realizado com sucesso</h3>
        </div>
        <div class="p-6 space-y-4">
          <p class="text-sm text-gray-700">{{ successMessage }}</p>
          <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 space-y-2">
            <div class="flex justify-between text-sm">
              <span class="text-gray-500">Login</span>
              <span class="font-semibold text-gray-900">{{ loginCredential }}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-gray-500">Senha inicial</span>
              <span class="font-semibold text-gray-900">{{ initialPassword }}</span>
            </div>
          </div>
          <p class="text-xs text-gray-500">No primeiro acesso será obrigatória a troca de senha.</p>
        </div>
        <div class="px-6 pb-6 flex gap-3 justify-end">
          <button
            type="button"
            @click="closeSuccessModal"
            class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50"
          >
            Fechar
          </button>
          <button
            type="button"
            @click="goToLogin"
            class="px-4 py-2 rounded-lg bg-blue-600 text-white font-semibold hover:bg-blue-700"
          >
            Ir para login
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import authService from '../services/authService';
import { useAtletaStore } from '../../atletas/store/atletaStore';
import { User, Loader2, AlertCircle, Mail, CreditCard } from 'lucide-vue-next';

const router = useRouter();
const atletaStore = useAtletaStore();
const loading = ref(false);
const loadingCpf = ref(false);
const loadingTitulo = ref(false);
const loadingCep = ref(false);
const error = ref('');
const tituloAutoFillWarning = ref('');
const cepAutoFillWarning = ref('');
const success = ref('');
const cpfConsultado = ref(false);
const currentStep = ref(1);
const showSuccessModal = ref(false);
const successMessage = ref('');
const loginCredential = ref('');
const initialPassword = ref('');
const eleitoralPreenchidoApi = ref(false);
const eleitoralManualLiberado = ref(false);
const tituloConsultaTentada = ref(false);
const consultaTituloBloqueadaApi = ref(false);
const validationErrors = ref([]);
const submitFeedbackRef = ref(null);
const lockedFields = ref({
  nome: false,
  data_nascimento: false,
  sexo: false,
  nome_mae: false,
  cep: false,
  endereco: false,
  bairro: false,
  cidade: false,
  estado: false,
  telefone: false,
  email: false
});

const form = ref({
  nome: '',
  cpf: '',
  data_nascimento: '',
  sexo: '',
  nome_mae: '',
  titulo_eleitor: '',
  zona_eleitoral: '',
  secao_eleitoral: '',
  bairro_eleitoral: '',
  cidade_eleitoral: '',
  uf_eleitoral: '',
  titulo_verificado: false,
  cep: '',
  endereco: '',
  bairro: '',
  cidade: '',
  estado: '',
  telefone: '',
  email: '',
  receive_notifications: false
});

const resetForm = () => {
  form.value = {
    nome: '',
    cpf: '',
    data_nascimento: '',
    sexo: '',
    nome_mae: '',
    titulo_eleitor: '',
    zona_eleitoral: '',
    secao_eleitoral: '',
    bairro_eleitoral: '',
    cidade_eleitoral: '',
    uf_eleitoral: '',
    titulo_verificado: false,
    cep: '',
    endereco: '',
    bairro: '',
    cidade: '',
    estado: '',
    telefone: '',
    email: '',
    receive_notifications: false
  };
};

const normalizeDateInput = (value) => {
  if (!value || typeof value !== 'string') return '';
  const raw = value.split(' ')[0];
  if (raw.includes('T')) return raw.split('T')[0];
  if (raw.includes('/')) {
    const parts = raw.split('/');
    if (parts.length === 3) {
      const [dd, mm, yyyy] = parts;
      return `${yyyy}-${mm.padStart(2, '0')}-${dd.padStart(2, '0')}`;
    }
  }
  return raw;
};

const calcularIdade = (dataNascimento) => {
  if (!dataNascimento) return null;
  const hoje = new Date();
  const nascimento = new Date(dataNascimento);
  let idade = hoje.getFullYear() - nascimento.getFullYear();
  const m = hoje.getMonth() - nascimento.getMonth();
  if (m < 0 || (m === 0 && hoje.getDate() < nascimento.getDate())) idade--;
  return Number.isNaN(idade) ? null : idade;
};

const mostrarCamposEleitorais = computed(() => {
  const idade = calcularIdade(form.value.data_nascimento);
  return idade !== null && idade >= 16;
});

const podeBuscarTitulo = computed(() => {
  if (!mostrarCamposEleitorais.value) return false;
  if (consultaTituloBloqueadaApi.value) return false;
  return Boolean(
    form.value.cpf &&
    form.value.data_nascimento &&
    form.value.nome_mae
  );
});

const preenchido = (value) => typeof value === 'string' ? value.trim().length > 0 : Boolean(value);

const stepStates = computed(() => {
  const identificacao = cpfConsultado.value;
  const pessoais = [
    form.value.nome,
    form.value.data_nascimento,
    form.value.sexo,
    form.value.nome_mae
  ].every(preenchido);
  const eleitoraisPreenchidos = [
    form.value.zona_eleitoral,
    form.value.secao_eleitoral,
    form.value.bairro_eleitoral,
    form.value.cidade_eleitoral,
    form.value.uf_eleitoral
  ].every(preenchido);
  const eleitorais = !mostrarCamposEleitorais.value || (tituloConsultaTentada.value && eleitoraisPreenchidos);
  const contato = [
    form.value.cep,
    form.value.endereco,
    form.value.bairro,
    form.value.cidade,
    form.value.estado,
    form.value.telefone,
    form.value.email
  ].every(preenchido) && form.value.receive_notifications;

  return { identificacao, pessoais, eleitorais, contato };
});

const progressPercent = computed(() => {
  const etapasConcluidas = Object.values(stepStates.value).filter(Boolean).length;
  return Math.round((etapasConcluidas / 4) * 100);
});

const goToStep = (step) => {
  if (step < 1 || step > 5) return;
  if (step > currentStep.value) {
    while (currentStep.value < step) {
      const before = currentStep.value;
      goToNextStep();
      if (before === currentStep.value) return;
    }
    return;
  }
  currentStep.value = step;
};

const goToPreviousStep = () => {
  currentStep.value = Math.max(1, currentStep.value - 1);
};

const validateStep = (step) => {
  if (step === 1) {
    if (!cpfConsultado.value) {
      setFormError('Consulte o CPF antes de avançar.');
      return false;
    }
    return true;
  }

  if (step === 2) {
    if (!stepStates.value.pessoais) {
      setFormError('Preencha os dados pessoais obrigatórios antes de avançar.');
      return false;
    }
    return true;
  }

  if (step === 3) {
    if (mostrarCamposEleitorais.value && !tituloConsultaTentada.value) {
      setFormError('Tente validar os dados no TSE antes de avançar.');
      return false;
    }
    const eleitoraisPreenchidos = [
      form.value.zona_eleitoral,
      form.value.secao_eleitoral,
      form.value.bairro_eleitoral,
      form.value.cidade_eleitoral,
      form.value.uf_eleitoral
    ].every(preenchido);
    if (mostrarCamposEleitorais.value && !eleitoraisPreenchidos) {
      setFormError('Preencha zona, seção, município, bairro e UF eleitorais antes de avançar.');
      return false;
    }
    return true;
  }

  if (step === 4) {
    const contatoPreenchido = [
      form.value.cep,
      form.value.endereco,
      form.value.bairro,
      form.value.cidade,
      form.value.estado,
      form.value.telefone,
      form.value.email
    ].every(preenchido);
    if (!contatoPreenchido) {
      setFormError('Preencha endereço e contato antes de avançar.');
      return false;
    }
    return true;
  }

  return true;
};

const goToNextStep = () => {
  if (!validateStep(currentStep.value)) {
    return;
  }
  error.value = '';
  validationErrors.value = [];
  currentStep.value = Math.min(5, currentStep.value + 1);
};

const resetLockedFields = () => {
  Object.keys(lockedFields.value).forEach((field) => {
    lockedFields.value[field] = false;
  });
};

const isFieldLocked = (field) => Boolean(lockedFields.value[field]);

const hasApiValue = (value) => {
  if (typeof value === 'string') return value.trim().length > 0;
  return value !== null && value !== undefined;
};

const applyApiFieldLocks = (dados) => {
  resetLockedFields();
  if (hasApiValue(dados.nome)) lockedFields.value.nome = true;
  if (hasApiValue(dados.data_nascimento)) lockedFields.value.data_nascimento = true;
  if (hasApiValue(dados.sexo)) lockedFields.value.sexo = true;
  if (hasApiValue(dados.nome_mae)) lockedFields.value.nome_mae = true;
  if (hasApiValue(dados.cep)) lockedFields.value.cep = true;
  if (hasApiValue(dados.endereco)) lockedFields.value.endereco = true;
  if (hasApiValue(dados.bairro)) lockedFields.value.bairro = true;
  if (hasApiValue(dados.cidade)) lockedFields.value.cidade = true;
  if (hasApiValue(dados.estado)) lockedFields.value.estado = true;
  if (hasApiValue(dados.telefone)) lockedFields.value.telefone = true;
  if (hasApiValue(dados.email)) lockedFields.value.email = true;
};

const scrollToSubmitFeedback = () => {
  requestAnimationFrame(() => {
    submitFeedbackRef.value?.scrollIntoView({ behavior: 'smooth', block: 'center' });
  });
};

const setFormError = (message, details = []) => {
  error.value = message;
  validationErrors.value = details;
  scrollToSubmitFeedback();
};

const liberarCpf = () => {
  cpfConsultado.value = false;
  eleitoralPreenchidoApi.value = false;
  eleitoralManualLiberado.value = false;
  tituloConsultaTentada.value = false;
  consultaTituloBloqueadaApi.value = false;
  currentStep.value = 1;
  error.value = '';
  validationErrors.value = [];
  tituloAutoFillWarning.value = '';
  cepAutoFillWarning.value = '';
  resetLockedFields();
  resetDadosEleitorais();
};

const formatCpfInput = () => {
  cpfConsultado.value = false;
  eleitoralPreenchidoApi.value = false;
  eleitoralManualLiberado.value = false;
  tituloConsultaTentada.value = false;
  resetLockedFields();
  resetDadosEleitorais();
  const cleaned = form.value.cpf.replace(/\D/g, '').slice(0, 11);
  if (cleaned.length <= 3) {
    form.value.cpf = cleaned;
    return;
  }
  if (cleaned.length <= 6) {
    form.value.cpf = `${cleaned.slice(0, 3)}.${cleaned.slice(3)}`;
    return;
  }
  if (cleaned.length <= 9) {
    form.value.cpf = `${cleaned.slice(0, 3)}.${cleaned.slice(3, 6)}.${cleaned.slice(6)}`;
    return;
  }
  form.value.cpf = `${cleaned.slice(0, 3)}.${cleaned.slice(3, 6)}.${cleaned.slice(6, 9)}-${cleaned.slice(9)}`;
};

const formatCepInput = () => {
  const cleaned = form.value.cep.replace(/\D/g, '').slice(0, 8);
  if (cleaned.length <= 5) {
    form.value.cep = cleaned;
  } else {
    form.value.cep = `${cleaned.slice(0, 5)}-${cleaned.slice(5)}`;
  }
  cepAutoFillWarning.value = '';
};

const consultarCep = async () => {
  if (!cpfConsultado.value || isFieldLocked('cep')) {
    return;
  }

  const cep = form.value.cep.replace(/\D/g, '');
  if (cep.length !== 8) {
    return;
  }

  loadingCep.value = true;
  cepAutoFillWarning.value = '';

  try {
    const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
    const data = await response.json();

    if (data?.erro) {
      cepAutoFillWarning.value = 'CEP não encontrado. Preencha o endereço manualmente.';
      lockedFields.value.endereco = false;
      lockedFields.value.bairro = false;
      lockedFields.value.cidade = false;
      lockedFields.value.estado = false;
      return;
    }

    if (typeof data?.logradouro === 'string' && data.logradouro.trim() !== '') {
      form.value.endereco = data.logradouro;
      lockedFields.value.endereco = true;
    } else {
      lockedFields.value.endereco = false;
    }

    if (typeof data?.bairro === 'string' && data.bairro.trim() !== '') {
      form.value.bairro = data.bairro;
      lockedFields.value.bairro = true;
    } else {
      lockedFields.value.bairro = false;
    }

    if (typeof data?.localidade === 'string' && data.localidade.trim() !== '') {
      form.value.cidade = data.localidade;
      lockedFields.value.cidade = true;
    } else {
      lockedFields.value.cidade = false;
    }

    if (typeof data?.uf === 'string' && data.uf.trim() !== '') {
      form.value.estado = data.uf.toUpperCase();
      lockedFields.value.estado = true;
    } else {
      lockedFields.value.estado = false;
    }
  } catch (e) {
    cepAutoFillWarning.value = 'Não foi possível consultar o CEP no momento. Preencha o endereço manualmente.';
    lockedFields.value.endereco = false;
    lockedFields.value.bairro = false;
    lockedFields.value.cidade = false;
    lockedFields.value.estado = false;
  } finally {
    loadingCep.value = false;
  }
};

const resetDadosEleitorais = () => {
  form.value.titulo_eleitor = '';
  form.value.zona_eleitoral = '';
  form.value.secao_eleitoral = '';
  form.value.bairro_eleitoral = '';
  form.value.cidade_eleitoral = '';
  form.value.uf_eleitoral = '';
  form.value.titulo_verificado = false;
};

const consultarTitulo = async () => {
  if (!mostrarCamposEleitorais.value) return;
  if (consultaTituloBloqueadaApi.value) {
    tituloAutoFillWarning.value = 'Consulta por API bloqueada para este cadastro. Continue com preenchimento manual.';
    return;
  }
  if (!form.value.cpf || !form.value.data_nascimento || !form.value.nome_mae) {
    tituloAutoFillWarning.value = 'Preencha CPF, data de nascimento e nome da mãe antes de buscar dados eleitorais.';
    return;
  }
  loadingTitulo.value = true;
  tituloConsultaTentada.value = true;
  try {
    const dados = await atletaStore.consultarTitulo({
      cpf: form.value.cpf.replace(/\D/g, ''),
      data_nascimento: form.value.data_nascimento,
      nome_mae: form.value.nome_mae
    });
    if (!dados?.retorno) {
      eleitoralPreenchidoApi.value = false;
      eleitoralManualLiberado.value = true;
      consultaTituloBloqueadaApi.value = true;
      tituloAutoFillWarning.value = atletaStore.error || 'Não foi possível preencher os dados eleitorais automaticamente. Complete manualmente.';
      return;
    }
    tituloAutoFillWarning.value = '';
    const retorno = dados?.retorno || {};
    const domicilio = retorno.domicilioEleitoral || {};
    const identificacao = retorno.identificacao || {};
    if (domicilio.zona) form.value.zona_eleitoral = domicilio.zona.toString();
    if (domicilio.secao) form.value.secao_eleitoral = domicilio.secao.toString();
    if (domicilio.bairro) form.value.bairro_eleitoral = domicilio.bairro;
    if (domicilio.municipio) form.value.cidade_eleitoral = domicilio.municipio;
    if (domicilio.uf) form.value.uf_eleitoral = String(domicilio.uf).toUpperCase();
    if (identificacao.inscricao) form.value.titulo_eleitor = String(identificacao.inscricao);
    const eleitoraisPreenchidos = [
      form.value.zona_eleitoral,
      form.value.secao_eleitoral,
      form.value.bairro_eleitoral,
      form.value.cidade_eleitoral,
      form.value.uf_eleitoral
    ].every(preenchido);
    eleitoralPreenchidoApi.value = eleitoraisPreenchidos;
    eleitoralManualLiberado.value = !eleitoraisPreenchidos;
    form.value.titulo_verificado = eleitoraisPreenchidos;
    if (!eleitoraisPreenchidos) {
      consultaTituloBloqueadaApi.value = true;
      tituloAutoFillWarning.value = 'A API não retornou todos os dados eleitorais. Complete manualmente.';
      return;
    }
    currentStep.value = 4;
  } catch (e) {
    eleitoralPreenchidoApi.value = false;
    eleitoralManualLiberado.value = true;
    tituloConsultaTentada.value = true;
    consultaTituloBloqueadaApi.value = true;
    tituloAutoFillWarning.value = atletaStore.error || 'Não foi possível preencher os dados eleitorais automaticamente. Complete manualmente.';
  } finally {
    loadingTitulo.value = false;
  }
};

const consultarCpf = async () => {
  const cpfLimpo = form.value.cpf.replace(/\D/g, '');
  if (cpfLimpo.length !== 11) return;
  loadingCpf.value = true;
  error.value = '';
  validationErrors.value = [];
  tituloAutoFillWarning.value = '';
  cepAutoFillWarning.value = '';
  eleitoralPreenchidoApi.value = false;
  eleitoralManualLiberado.value = false;
  tituloConsultaTentada.value = false;
  consultaTituloBloqueadaApi.value = false;
  resetLockedFields();
  resetDadosEleitorais();
  success.value = '';
  cpfConsultado.value = false;
  try {
    const dados = await atletaStore.consultarCpf(cpfLimpo);
    if (!dados) {
      error.value = atletaStore.error || 'Não foi possível carregar dados desse CPF.';
      return;
    }
    const cpfParaExibir = (dados.cpf || cpfLimpo).replace(/\D/g, '').slice(0, 11);
    form.value.cpf = cpfParaExibir.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
    form.value.nome = dados.nome || form.value.nome;
    form.value.data_nascimento = normalizeDateInput(dados.data_nascimento) || form.value.data_nascimento;
    form.value.sexo = (dados.sexo || form.value.sexo || '').toUpperCase();
    form.value.nome_mae = dados.nome_mae || form.value.nome_mae;
    form.value.titulo_eleitor = dados.titulo_eleitor || form.value.titulo_eleitor;
    form.value.cep = dados.cep || form.value.cep;
    form.value.endereco = dados.endereco || form.value.endereco;
    form.value.bairro = dados.bairro || form.value.bairro;
    form.value.cidade = dados.cidade || form.value.cidade;
    form.value.estado = (dados.estado || form.value.estado || '').toUpperCase();
    form.value.telefone = dados.telefone || form.value.telefone;
    form.value.email = dados.email || form.value.email;
    applyApiFieldLocks(dados);
    cpfConsultado.value = true;
    form.value.titulo_verificado = false;
    currentStep.value = 2;
  } catch (e) {
    if (e.response?.data?.errors) {
      const errors = e.response.data.errors;
      error.value = Object.values(errors)[0][0];
    } else {
      error.value = e.response?.data?.message || 'Erro ao consultar CPF.';
    }
    console.error(e);
  } finally {
    loadingCpf.value = false;
  }
};

const handleRegister = async () => {
  if (!cpfConsultado.value) {
    setFormError('Consulte o CPF antes de enviar o cadastro.');
    return;
  }
  if (mostrarCamposEleitorais.value && !tituloConsultaTentada.value) {
    setFormError('Tente validar os dados eleitorais no TSE antes de enviar.');
    return;
  }
  const eleitoraisPreenchidos = [
    form.value.zona_eleitoral,
    form.value.secao_eleitoral,
    form.value.bairro_eleitoral,
    form.value.cidade_eleitoral,
    form.value.uf_eleitoral
  ].every(preenchido);
  if (mostrarCamposEleitorais.value && !eleitoraisPreenchidos) {
    setFormError('Preencha zona, seção, município, bairro e UF eleitorais antes de enviar.');
    return;
  }
  if (!form.value.receive_notifications) {
    setFormError('Você precisa aceitar o recebimento de notificações no WhatsApp.');
    return;
  }
  loading.value = true;
  error.value = '';
  validationErrors.value = [];
  success.value = '';

  try {
    const payload = {
      ...form.value,
      cpf: form.value.cpf.replace(/\D/g, ''),
      cep: form.value.cep.replace(/\D/g, ''),
      estado: form.value.estado.toUpperCase()
    };
    payload.titulo_verificado = Boolean(eleitoralPreenchidoApi.value);
    payload.validacao_eleitoral_pendente = Boolean(
      mostrarCamposEleitorais.value &&
      tituloConsultaTentada.value &&
      !payload.titulo_verificado
    );

    const { data } = await authService.registerAtleta(payload);
    success.value = data?.message || 'Cadastro realizado com sucesso.';
    successMessage.value = data?.message || 'Cadastro realizado com sucesso.';
    loginCredential.value = payload.cpf;
    initialPassword.value = payload.cpf.slice(-4);
    showSuccessModal.value = true;
    resetForm();
    resetLockedFields();
    eleitoralPreenchidoApi.value = false;
    eleitoralManualLiberado.value = false;
    tituloConsultaTentada.value = false;
    consultaTituloBloqueadaApi.value = false;
    cpfConsultado.value = false;
    currentStep.value = 1;
  } catch (e) {
    if (e.response?.data?.errors) {
      const backendErrors = Object.values(e.response.data.errors).flat().filter(Boolean);
      setFormError(backendErrors[0] || 'Existem campos inválidos no formulário.', backendErrors);
    } else {
      setFormError(e.response?.data?.message || 'Erro ao cadastrar atleta. Tente novamente.');
    }
    console.error(e);
  } finally {
    loading.value = false;
  }
};

const closeSuccessModal = () => {
  showSuccessModal.value = false;
};

const goToLogin = () => {
  showSuccessModal.value = false;
  router.push('/login');
};
</script>
