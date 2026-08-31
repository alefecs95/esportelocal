# Manual do Usuário - Sistema de Gestão Esportiva (SEMEL)

Bem-vindo ao manual completo do sistema SEMEL. Este documento guia você através de todas as funcionalidades da plataforma, desde a criação de um campeonato até a gestão de partidas em tempo real.

---

## 📋 Índice

1. [Acesso ao Sistema](#1-acesso-ao-sistema)
2. [Gestão de Campeonatos](#2-gestão-de-campeonatos)
3. [Gestão de Equipes e Atletas](#3-gestão-de-equipes-e-atletas)
4. [Configuração de Tabelas e Fases](#4-configuração-de-tabelas-e-fases)
   - [Editor Flexível](#editor-flexível)
   - [Automação de Confrontos](#automação-de-confrontos)
5. [Súmula Eletrônica (Operação de Jogo)](#5-súmula-eletrônica-operação-de-jogo)
6. [Classificação e Avanço de Fases](#6-classificação-e-avanço-de-fases)
7. [Portal do Torcedor](#7-portal-do-torcedor)

---

## 1. Acesso ao Sistema

O sistema possui áreas distintas para administradores e para o público geral.

- **Área Administrativa**: Requer login (E-mail e Senha). Acessível para gestores da SEMEL e organizadores.
- **Portal Público**: Aberto a todos, não requer login. Exibe tabelas, resultados e estatísticas.

---

## 2. Gestão de Campeonatos

O primeiro passo é criar a competição.

1. No menu lateral, clique em **Campeonatos**.
2. Clique no botão **Novo Campeonato**.
3. Preencha os dados básicos:
   - **Nome**: Ex: "Copa Municipal 2026".
   - **Modalidade**: Futebol, Futsal, Vôlei, etc.
   - **Datas**: Início e fim previstos.
4. **Formato**: Escolha a estrutura macro (Mata-mata, Grupos, Pontos Corridos ou Híbrido).
   - *Nota: Você poderá personalizar as fases detalhadamente depois.*

---

## 3. Gestão de Equipes e Atletas

Antes de montar a tabela, é preciso ter os participantes.

1. Vá em **Equipes**.
2. Cadastre as equipes participantes ou importe-as.
3. Dentro de cada equipe, gerencie o **Elenco**:
   - Adicione atletas com Nome, Documento e Posição.
   - **Status de Inscrição**: O sistema permite aprovar ou rejeitar atletas (importante para verificar regularidade).
   - Apenas atletas com status **Aprovado** aparecerão na Súmula Eletrônica.

---

## 4. Configuração de Tabelas e Fases

Esta é a área mais poderosa do sistema. Aqui você define como o campeonato acontece.

Acesse: **Campeonatos** > Selecione o Campeonato > **Tabela de Jogos**.

### Editor Flexível de Tabela
Nesta tela, você desenha o caminho do campeonato.

1. **Adicionar Fases**:
   - Clique em **Nova Fase**.
   - Dê um nome (ex: "Fase de Grupos").
   - Escolha o tipo (Grupos, Mata-mata ou Pontos Corridos).
   
2. **Adicionar Grupos**:
   - Se for uma fase de grupos, clique em **Adicionar Grupo** para criar "Grupo A", "Grupo B", etc.

3. **Criar Confrontos (Jogos)**:
   - Clique em **Adicionar Confronto**.
   - Você verá dois blocos: **Time da Casa** e **Time Visitante**.
   - **Quem joga?**:
     - **Equipe Específica**: Selecione um time da lista (útil para a 1ª fase).
     - **Classificado de Grupo**: Escolha "1º do Grupo A" (útil para Oitavas/Quartas).
     - **Vencedor de Jogo**: Escolha "Vencedor do Jogo 1" (útil para Semifinais/Finais).
   - O sistema preencherá os times automaticamente conforme o campeonato avança.

### Automação de Confrontos
- **Gerar Jogos Automáticos**: Se você configurou grupos, o sistema pode gerar automaticamente os jogos de "todos contra todos" dentro do grupo.
- **Preencher Classificados**: Ao clicar neste botão, o sistema verifica os resultados das fases anteriores e preenche os times nos jogos de mata-mata automaticamente.
- **Salvar Alterações**: Sempre salve suas alterações no editor para que elas se tornem públicas.

---

## 5. Súmula Eletrônica (Operação de Jogo)

Ferramenta para mesários e árbitros registrarem o jogo em tempo real.

1. Na lista de jogos, clique em **Detalhes** (ícone de olho) ou acesse a área de Súmula.
2. **Cronologia**:
   - O sistema mostra o tempo de jogo e o placar.
   - **Registrar Evento**: Clique nos botões rápidos ao lado do nome do jogador:
     - ⚽ **Gol**: Atualiza o placar automaticamente.
     - 🟨 **Cartão Amarelo**.
     - 🟥 **Cartão Vermelho**.
     - 🔄 **Substituição**: Selecione quem sai e quem entra.
3. **Timeline**: Todos os eventos aparecem numa linha do tempo central.
4. **Impressão**: Clique no botão **Imprimir Súmula** para gerar um PDF oficial da partida com todas as assinaturas e ocorrências.

---

## 6. Classificação e Avanço de Fases

Após os jogos terminarem, o sistema processa os resultados.

1. **Atualizar Classificação**:
   - No editor da tabela, na "Fase de Grupos", clique em **Atualizar Classificação**.
   - O sistema calcula Pontos, Vitórias, Saldo de Gols, etc., baseado nos critérios configurados (padrão FIFA ou personalizado).
2. **Resolver Classificados**:
   - Botão mágico **"Resolver Classificados"**.
   - O sistema olha para as fases futuras. Se houver um jogo esperando o "1º do Grupo A" e a fase de grupos já terminou, ele preenche esse time automaticamente no mata-mata.
   - Também resolve "Melhores Terceiros Colocados" se configurado.

---

## 7. Portal do Torcedor

A face pública do seu campeonato.

- Link direto para compartilhamento.
- **Visualização Híbrida**:
  - Mostra as tabelas de classificação (com pontuação detalhada).
  - Mostra o **Chaveamento (Bracket)** visual para fases eliminatórias (Oitavas -> Quartas -> Final).
- Atualização em tempo real: Assim que o mesário lança um gol na súmula, o portal é atualizado.

---

### Dicas Extras

- **Backup**: O sistema salva o histórico de edições da tabela.
- **Testes**: Antes de iniciar o campeonato real, crie um campeonato de "Teste" para treinar seus mesários na Súmula Eletrônica.
