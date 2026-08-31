# Escopo MVP - Modulos Espacos Esportivos e Escolinhas

## Objetivo

Entregar dois modulos vendaveis em ate 12 semanas, com foco em operacao municipal real e rapida adocao:

- **Modulo 1: Espacos Esportivos** para controlar agenda e uso dos equipamentos esportivos.
- **Modulo 2: Escolinhas/Nucleos** para controlar turmas, matriculas e frequencia.

## Modulo 1 - Espacos Esportivos (MVP)

### Problema que resolve

- Conflito de agenda de ginasios e campos.
- Falta de transparencia para solicitacoes de uso.
- Ausencia de dados para justificar investimentos.

### Inclui no MVP

- Cadastro de espacos:
  - nome, tipo (campo, ginasio, quadra, pista), endereco, capacidade, status.
- Agenda operacional:
  - horarios disponiveis, bloqueios (manutencao/evento), visualizacao por semana.
- Solicitacoes de uso:
  - solicitante interno/externo, data/hora, finalidade, aprovacao/rejeicao.
- Regras basicas de conflito:
  - impedir dupla reserva no mesmo horario.
- Relatorios basicos:
  - taxa de ocupacao por espaco e periodo.
- Portal publico basico:
  - agenda publica somente leitura (opcional por tenant).

### Fora do MVP (Fase 2)

- Integracao com pagamento/taxas.
- Assinatura digital de termo de uso.
- App mobile dedicado.
- SLA avancado por tipo de solicitacao.

### Entidades minimas

- `espacos`
- `espaco_disponibilidades`
- `espaco_bloqueios`
- `espaco_reservas`
- `espaco_solicitacoes`

### Perfis e permissoes minimas

- `ADMIN`: CRUD completo, aprovacao e relatorios.
- `RESPONSAVEL`: cria solicitacao e acompanha status.
- `PUBLICO` (portal): apenas consulta agenda quando habilitado.

### Criterios de aceite do MVP

- Nao permitir reserva com sobreposicao no mesmo espaco.
- Fluxo completo de solicitacao -> aprovacao/rejeicao funcionando.
- Relatorio mensal de ocupacao exportavel (CSV/PDF simples).

## Modulo 2 - Escolinhas/Nucleos (MVP)

### Problema que resolve

- Falta de controle de turmas e vagas.
- Dificuldade de medir frequencia/evasao.
- Pouca visibilidade de impacto social.

### Inclui no MVP

- Cadastro de nucleos e turmas:
  - modalidade, faixa etaria, professor, dias/horarios, local.
- Matriculas:
  - status (ativa, lista de espera, cancelada), data de ingresso.
- Frequencia:
  - chamada por aula, presenca/falta por aluno.
- Painel basico:
  - alunos ativos, ocupacao de vagas, frequencia media por turma.
- Alertas operacionais:
  - baixa frequencia por aluno (regra simples, ex: < 60% no mes).

### Fora do MVP (Fase 2)

- Plano pedagogico detalhado por ciclo.
- Avaliacao fisica integrada.
- Integracao com transporte escolar.
- App do responsavel com notificacoes push.

### Entidades minimas

- `nucleos`
- `turmas`
- `turma_vagas`
- `matriculas`
- `aulas`
- `frequencias`

### Perfis e permissoes minimas

- `ADMIN`: CRUD completo e indicadores.
- `PROFESSOR` (ou usuario operacional): registro de frequencia.
- `RESPONSAVEL`: consulta da situacao do aluno.

### Criterios de aceite do MVP

- Registrar chamada por aula em menos de 2 minutos para 30 alunos.
- Listar alunos com baixa frequencia automaticamente.
- Dashboard com indicadores basicos por periodo e modalidade.

## Requisitos comuns aos dois modulos

- Multi-tenant por prefeitura (obrigatorio).
- Auditoria de acoes administrativas (quem/quando/o que).
- Exportacao CSV e PDF simples.
- Permissao por perfil via middleware/policies.
- Tempo de resposta de listagens principais < 2s (base de referencia inicial).

## Entregaveis por sprint (sugestao)

- Sprint 1-2: modelagem de dados + APIs base + permissoes.
- Sprint 3-4: telas operacionais (admin) + regras de negocio.
- Sprint 5: portal/consulta + relatorios.
- Sprint 6: homologacao com prefeitura piloto + ajustes finais.

## KPIs de sucesso (90 dias)

- 80% dos espacos ativos com agenda cadastrada.
- Reducao de conflitos de reserva em pelo menos 50%.
- 70% das turmas com frequencia registrada semanalmente.
- Relatorio mensal emitido por pelo menos 1 gestor por tenant.
