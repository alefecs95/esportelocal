<template>
  <!--
    type: software
    deal: palette teal-on-paper (papel de secretaria, acento verde-azulado), type host, radius soft,
    hero centred, nav nav-centre, footer footer-statement, close close-word, rhythm space,
    buttons solid, mark ring, ground painting
    effect: painterly-landscape no quadro do hero
    moments: marquee-row em #modalidades, word-highlight em #frase
  -->
  <div class="taste-root">
    <header class="nav nav--centre">
      <div class="wrap nav__bar">
        <a class="skip-link" href="#top">Ir para o conteúdo</a>
        <a class="nav__logo" href="#top">
          <img v-if="!logoError" :src="logoSrc" alt="Esporte Local" @error="logoError = true" />
          <template v-else>
            <span class="mark" data-mark="ring" aria-hidden="true"></span>
            Esporte Local
          </template>
        </a>
        <nav class="nav__links" aria-label="Principal">
          <div class="nav__item">
            <a class="nav__link" href="#sumula">Produto
              <svg class="chev" viewBox="0 0 12 12" aria-hidden="true"><path d="M3 4.5l3 3 3-3" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
            <div class="nav__menu">
              <div class="wrap grid">
                <div class="menu-col span-3">
                  <p class="t-label">Secretaria</p>
                  <a href="#sumula">Súmula<span>Gol e cartão no celular</span></a>
                  <a href="#recursos">Cadastro<span>Atleta, documento e equipe</span></a>
                  <a href="#passos">Implantação<span>Portal em 48 horas úteis</span></a>
                </div>
                <div class="menu-col span-3">
                  <p class="t-label">Cidade</p>
                  <a href="#modalidades">Modalidades<span>Vários esportes, um cadastro</span></a>
                  <a href="#perguntas">Perguntas<span>Servidor, documento, limite</span></a>
                  <a href="#contato">Apresentação<span>Pedido para a secretaria</span></a>
                </div>
              </div>
            </div>
          </div>
          <div class="nav__item"><a class="nav__link" href="#recursos">Recursos</a></div>
          <div class="nav__item"><a class="nav__link" href="#passos">Passos</a></div>
          <div class="nav__item"><a class="nav__link" href="#perguntas">Perguntas</a></div>
        </nav>
        <div class="nav__actions">
          <router-link class="nav__link" to="/login">Entrar</router-link>
          <a class="btn btn--primary" href="#contato">Solicitar apresentação</a>
        </div>
        <button class="nav__toggle" type="button" aria-expanded="false" aria-label="Menu">
          <svg width="18" height="18" viewBox="0 0 18 18" aria-hidden="true"><path d="M2 5h14M2 13h14" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
        </button>
      </div>
    </header>

    <main id="top">
      <section class="hero hero--centred" data-enter aria-labelledby="hero-h">
        <div class="wrap">
          <div class="hero-text">
            <div class="hero__c">
              <a class="pill" href="#sumula"><span>Súmula no celular</span><span class="muted">Gol e cartão na hora</span></a>
              <h1 id="hero-h" aria-label="Gestão esportiva para prefeituras">
                <span class="ly-mark" aria-hidden="true"></span>
                Gestão esportiva para prefeituras
              </h1>
              <p class="hero__lead">A secretaria cadastra atletas, monta a tabela e publica o portal da cidade. O portal entra no ar em até 48 horas úteis.</p>
              <div class="btn-row">
                <a class="btn btn--primary" href="#contato">Solicitar apresentação</a>
                <a class="btn btn--secondary" href="#sumula">Ver a súmula</a>
              </div>
            </div>
            <div class="hero__window" data-fx="painterly-landscape" data-fx-seed="7" role="img" aria-label="Lista de partidas municipais com a súmula da partida aberta">
              <div class="ly-piece">
                <div class="ui ui-panel ui-win" data-ui="window" data-open="Ao vivo" data-work="Tabela" data-done="Encerrada">
                  <div class="ui-win__bar">
                    <span class="ui-dots" aria-hidden="true"><i></i><i></i><i></i></span>
                    <span class="ui-win__crumb">Partidas <i>/</i> <b>Hoje</b></span>
                    <span class="ui-spacer"></span>
                    <span class="ui-win__search" aria-hidden="true"><svg class="ui-icon" viewBox="0 0 16 16"><circle cx="7" cy="7" r="4.5"/><path d="m10.5 10.5 3 3"/></svg><span class="ui-win__ph">Buscar</span></span>
                  </div>
                  <div class="ui-win__body">
                    <nav class="ui-win__side" aria-hidden="true">
                      <span class="ui-win__nav is-on">Hoje<em>4</em></span>
                      <span class="ui-win__nav">Tabelas<em>12</em></span>
                      <span class="ui-win__nav">Súmulas<em>3</em></span>
                      <span class="ui-win__nav">Encerradas</span>
                    </nav>
                    <div class="ui-win__main">
                      <div class="ui-win__cols" aria-hidden="true"><span>Jogo</span><span>Modalidade</span><span>Estado</span><span>Hora</span></div>
                      <ul class="ui-win__rows">
                        <li class="ui-win__row"><span class="ui-avatar" data-tone="1">FS</span><span class="ui-win__who">Casa 2 × 1 Visitante</span><span class="ui-win__sub">Futsal sub-17 · ginásio</span><span class="ui-win__st">Ao vivo</span><span class="ui-win__age">19:30</span></li>
                        <li class="ui-win__row"><span class="ui-avatar" data-tone="3">VO</span><span class="ui-win__who">Casa × Visitante</span><span class="ui-win__sub">Vôlei feminino · 2º set</span><span class="ui-win__st">Ao vivo</span><span class="ui-win__age">20:00</span></li>
                        <li class="ui-win__row"><span class="ui-avatar" data-tone="4">FB</span><span class="ui-win__who">Casa × Visitante</span><span class="ui-win__sub">Futebol · oitavas</span><span class="ui-win__st">Tabela</span><span class="ui-win__age">20:40</span></li>
                        <li class="ui-win__row"><span class="ui-avatar" data-tone="2">BA</span><span class="ui-win__who">Casa 71 × 64 Visitante</span><span class="ui-win__sub">Basquete · final</span><span class="ui-win__st is-done">Encerrada</span><span class="ui-win__age">18:00</span></li>
                      </ul>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="section" id="sumula" aria-labelledby="sumula-h">
        <div class="wrap">
          <div class="grid">
            <h2 id="sumula-h" class="span-5">A partida aberta ao lado da tabela</h2>
            <p class="span-6 start-7">A lista mostra o jogo do dia. Ao lado, a súmula guarda o gol, o cartão e a substituição, e o portal recebe o placar.</p>
          </div>
          <div class="ui app fill" role="img" aria-label="Tabela de partidas ao lado da súmula aberta" data-ui-pause-host>
            <div class="app__pane app__pane--list">
              <div class="ui ui-panel ui-win" data-ui="window" data-open="Ao vivo" data-work="Tabela" data-done="Encerrada">
                <div class="ui-win__bar">
                  <span class="ui-dots" aria-hidden="true"><i></i><i></i><i></i></span>
                  <span class="ui-win__crumb">Campeonatos <i>/</i> <b>Municipal</b></span>
                </div>
                <div class="ui-win__body">
                  <nav class="ui-win__side" aria-hidden="true">
                    <span class="ui-win__nav is-on">Jogos<em>9</em></span>
                    <span class="ui-win__nav">Equipes</span>
                    <span class="ui-win__nav">Atletas</span>
                  </nav>
                  <div class="ui-win__main">
                    <div class="ui-win__cols" aria-hidden="true"><span>Jogo</span><span>Chave</span><span>Estado</span></div>
                    <ul class="ui-win__rows">
                      <li class="ui-win__row" v-for="row in partidas" :key="row.id">
                        <span class="ui-avatar" :data-tone="row.tone">{{ row.sigla }}</span>
                        <span class="ui-win__who">{{ row.jogo }}</span>
                        <span class="ui-win__sub">{{ row.chave }}</span>
                        <span class="ui-win__st" :class="row.estadoClasse">{{ row.estado }}</span>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>
            <div class="app__pane app__pane--open" data-ui-loop>
              <div class="ui ui-doc" data-ui="doc">
                <article class="ui-doc__page">
                  <div class="ui-doc__label">Súmula, futsal sub-17</div>
                  <div class="ui-doc__title">Casa 2 × 1 Visitante</div>
                  <div class="ui-doc__text">Gol da casa aos <mark class="ui-doc__mark is-on">12 min</mark>. Gol do visitante aos 26 min. Gol da casa aos 31 min.</div>
                  <div class="ui-doc__text">Cartão amarelo aos 29 min. Substituição aos 30 min, com o atleta que entra já no cadastro.</div>
                  <div class="ui-doc__text">A súmula segue no celular mesmo sem rede e sincroniza quando a quadra volta a ter sinal.</div>
                  <div class="ui-doc__text">O PDF da partida fica na ficha, com o placar que o portal já mostra.</div>
                </article>
                <aside class="ui-doc__note ui-panel is-on" aria-live="polite">
                  <div class="ui-doc__kind"><span class="ui-doc__dot"></span><span>PDF</span></div>
                  <div class="ui-doc__msg">Súmula pronta para arquivar.</div>
                  <div class="ui-doc__where">Partida de hoje, ginásio</div>
                </aside>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="section" id="modalidades" aria-labelledby="modalidades-h">
        <div class="wrap">
          <div class="section-head">
            <h2 id="modalidades-h">Modalidades no mesmo cadastro</h2>
            <p>Futsal, campo, quadra e pista usam o mesmo atleta, a mesma equipe e a mesma súmula.</p>
          </div>
          <div data-moment="marquee-row" data-moment-opts='{"speed": 36}'>
            <ul class="mo-mq__track">
              <li>Futsal</li>
              <li>Futebol</li>
              <li>Vôlei</li>
              <li>Basquete</li>
              <li>Handebol</li>
              <li>Natação</li>
              <li>Atletismo</li>
              <li>Judô</li>
              <li>Corrida</li>
              <li>Tênis de mesa</li>
            </ul>
          </div>
        </div>
      </section>

      <section class="section g2" id="recursos" aria-labelledby="recursos-h">
        <div class="wrap">
          <div class="group">
            <div class="section-head">
              <h2 id="recursos-h">O que a secretaria faz no sistema. <span class="muted">Cadastro, aviso, portal e regra.</span></h2>
            </div>
            <div class="grid">
              <article class="card card--row card--open span-6">
                <h3>Cadastra o atleta</h3>
                <p>O atleta envia RG e comprovante pelo celular. A secretaria aprova antes de ele entrar na súmula.</p>
                <div class="panel">
                  <div class="stage stage--fade-b">
                    <div class="at-rise fit" style="--fit-max: 400px; --ax: max(24px, 50% - 200px); --ay: 56px; --ay-sm: 24px" data-ui-loop>
                      <div class="ui ui-panel ui-checks" data-ui="checklist">
                        <div class="ui-checks__head">
                          <div class="ui-title">Documentos do atleta</div>
                          <span class="ui-spacer"></span>
                          <div class="ui-meta">2 de 4</div>
                        </div>
                        <div class="ui-checks__bar"><i style="--k: 0.5"></i></div>
                        <ul class="ui-checks__list">
                          <li class="ui-check is-done"><span class="ui-check__box"><svg viewBox="0 0 16 16"><path d="m4.5 8.2 2.3 2.3 4.7-5"/></svg></span><span class="ui-check__label">RG fotografado</span></li>
                          <li class="ui-check is-done"><span class="ui-check__box"><svg viewBox="0 0 16 16"><path d="m4.5 8.2 2.3 2.3 4.7-5"/></svg></span><span class="ui-check__label">CPF conferido</span></li>
                          <li class="ui-check"><span class="ui-check__box"><svg viewBox="0 0 16 16"><path d="m4.5 8.2 2.3 2.3 4.7-5"/></svg></span><span class="ui-check__label">Comprovante de residência</span></li>
                          <li class="ui-check"><span class="ui-check__box"><svg viewBox="0 0 16 16"><path d="m4.5 8.2 2.3 2.3 4.7-5"/></svg></span><span class="ui-check__label">Aprovação da secretaria</span></li>
                        </ul>
                      </div>
                    </div>
                  </div>
                </div>
              </article>
              <article class="card card--row card--open span-6">
                <h3>Avisa o gol na hora</h3>
                <p>O árbitro lança o evento na beira da quadra. A tabela e a artilharia do portal mudam em seguida.</p>
                <div class="panel">
                  <div class="stage stage--fade-b">
                    <div class="at-rise fit" style="--fit-max: 400px; --ax: max(24px, 50% - 200px); --ay: calc(100% - 180px); --ay-sm: 24px">
                      <div class="ui ui-notify" data-ui="notify">
                        <ul class="ui-notify__list">
                          <li class="ui-note"><span class="ui-note__text"><span class="ui-note__title">Gol da casa<time>agora</time></span><span class="ui-note__body">Futsal sub-17, minuto 31</span></span></li>
                          <li class="ui-note"><span class="ui-note__text"><span class="ui-note__title">Cartão amarelo<time>2 min</time></span><span class="ui-note__body">Visitante, minuto 29</span></span></li>
                          <li class="ui-note"><span class="ui-note__text"><span class="ui-note__title">Tabela atualizada<time>2 min</time></span><span class="ui-note__body">Placar no portal da cidade</span></span></li>
                        </ul>
                      </div>
                    </div>
                  </div>
                </div>
              </article>
            </div>
          </div>
          <div class="grid">
            <article class="card card--row card--open span-6">
              <h3>Publica a tabela</h3>
              <p>O morador abre o portal da cidade e vê o jogo, o horário e o placar sem pedir na secretaria.</p>
              <div class="panel panel--flush panel--device">
                <div class="stage stage--fade-b">
                  <div class="at-corner" style="--ax: 24px; --ay: 28px">
                    <div class="ui ui-panel ui-win" data-ui="window" data-ui-opts='{"compact":true}' data-open="Hoje" data-work="Semana" data-done="Feito">
                      <div class="ui-win__bar">
                        <span class="ui-dots" aria-hidden="true"><i></i><i></i><i></i></span>
                        <span class="ui-win__crumb">Portal <i>/</i> <b>Jogos</b></span>
                      </div>
                      <div class="ui-win__body">
                        <div class="ui-win__main">
                          <ul class="ui-win__rows">
                            <li class="ui-win__row"><span class="ui-win__who">Futsal sub-17</span><span class="ui-win__sub">Casa × Visitante</span><span class="ui-win__st">Hoje</span><span class="ui-win__age">19:30</span></li>
                            <li class="ui-win__row"><span class="ui-win__who">Vôlei</span><span class="ui-win__sub">Casa × Visitante</span><span class="ui-win__st">Hoje</span><span class="ui-win__age">20:15</span></li>
                            <li class="ui-win__row"><span class="ui-win__who">Futebol</span><span class="ui-win__sub">Oitavas de final</span><span class="ui-win__st">Semana</span><span class="ui-win__age">sáb</span></li>
                          </ul>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </article>
            <article class="card card--row card--open span-6">
              <h3>Trava o atleta suspenso</h3>
              <p>Quem está suspenso ou fora da idade não entra na súmula. A regra fica no cadastro, não numa planilha.</p>
              <div class="panel">
                <div class="stage stage--fade-b">
                  <div class="at-right to-edge" style="--ax: 32px; --ay: 56px">
                    <div class="ui ui-panel ui-toggles" data-ui="toggles">
                      <div class="ui-head">
                        <div class="ui-title">Regras da partida</div>
                        <span class="ui-spacer"></span>
                        <div class="ui-meta">3 de 4 ligadas</div>
                      </div>
                      <ul class="ui-toggles__list">
                        <li class="ui-tg">
                          <span class="ui-tg__text"><span class="ui-tg__label">Bloquear suspenso</span><span class="ui-tg__sub">Cartão acumulado impede a súmula</span></span>
                          <button class="ui-switch is-on" type="button" role="switch" aria-checked="true" aria-label="Bloquear atleta suspenso"><i></i></button>
                        </li>
                        <li class="ui-tg">
                          <span class="ui-tg__text"><span class="ui-tg__label">Checar a idade</span><span class="ui-tg__sub">Categoria sub-17 no documento</span></span>
                          <button class="ui-switch is-on" type="button" role="switch" aria-checked="true" aria-label="Checar a idade da categoria"><i></i></button>
                        </li>
                        <li class="ui-tg">
                          <span class="ui-tg__text"><span class="ui-tg__label">Exigir documento</span><span class="ui-tg__sub">Sem aprovação, sem jogo</span></span>
                          <button class="ui-switch is-on" type="button" role="switch" aria-checked="true" aria-label="Exigir documento aprovado"><i></i></button>
                        </li>
                        <li class="ui-tg">
                          <span class="ui-tg__text"><span class="ui-tg__label">Atleta de outra equipe</span><span class="ui-tg__sub">A secretaria libera caso a caso</span></span>
                          <button class="ui-switch" type="button" role="switch" aria-checked="false" aria-label="Permitir atleta de outra equipe"><i></i></button>
                        </li>
                      </ul>
                    </div>
                  </div>
                </div>
              </div>
            </article>
          </div>
        </div>
      </section>

      <section class="section statement" id="frase" data-moment="word-highlight" aria-labelledby="frase-h">
        <div class="wrap">
          <h2 id="frase-h" class="mo-wh__text">A secretaria deixa a súmula de papel. O árbitro anota o <strong>gol</strong> no celular, o portal da cidade atualiza a tabela e o PDF fica guardado com a partida.</h2>
        </div>
      </section>

      <section class="section" id="passos" aria-labelledby="passos-h">
        <div class="wrap">
          <div class="section-head">
            <h2 id="passos-h">Três passos até a primeira partida</h2>
          </div>
          <div class="grid">
            <article class="card card--row card--open span-4">
              <span class="step-num">1</span>
              <h3>Abrir o portal</h3>
              <p>O portal da cidade e a área da secretaria ficam no ar em até 48 horas úteis.</p>
            </article>
            <article class="card card--row card--open span-4">
              <span class="step-num">2</span>
              <h3>Cadastrar as equipes</h3>
              <p>A secretaria lança modalidades, equipes e atletas. O documento sobe pelo celular.</p>
            </article>
            <article class="card card--row card--open span-4">
              <span class="step-num">3</span>
              <h3>Lançar a súmula</h3>
              <p>O árbitro registra o jogo na quadra. O placar aparece no portal da cidade.</p>
            </article>
          </div>
        </div>
      </section>

      <section class="section" id="perguntas" aria-labelledby="perguntas-h">
        <div class="wrap grid">
          <div class="span-5">
            <div class="section-head">
              <h2 id="perguntas-h">Perguntas</h2>
              <p>Prazo, servidor, documento e limite de atletas.</p>
              <a class="link" href="#contato">Ver o formulário</a>
            </div>
          </div>
          <div class="faq span-6 start-7">
            <div class="faq__item">
              <h3>Quanto tempo leva a implantação?</h3>
              <p>Até 48 horas úteis depois da assinatura, com o portal da cidade e a área da secretaria. O treinamento da equipe vem em seguida.</p>
            </div>
            <div class="faq__item">
              <h3>A prefeitura precisa de servidor?</h3>
              <p>Não. O sistema roda na nuvem. A prefeitura usa navegador e internet. Backup e hospedagem ficam com a operação do Esporte Local.</p>
            </div>
            <div class="faq__item">
              <h3>Como o atleta envia o documento?</h3>
              <p>Com o próprio acesso, ele fotografa RG, CPF e comprovante no celular. A secretaria aprova antes do atleta valer na súmula.</p>
            </div>
            <div class="faq__item">
              <h3>Há limite de atletas?</h3>
              <p>O plano acompanha o tamanho do município. O número entra na apresentação, não numa tabela genérica desta página.</p>
            </div>
            <div class="faq__item">
              <h3>A súmula funciona sem internet?</h3>
              <p>Sim. O árbitro lança o jogo sem rede e o aparelho sincroniza quando o sinal volta, inclusive o PDF da partida.</p>
            </div>
          </div>
        </div>
      </section>

      <section class="section" id="contato" aria-labelledby="contato-h">
        <div class="wrap">
          <div class="section-head">
            <h2 id="contato-h">Pedido de apresentação</h2>
            <p>Cidade, nome, e-mail e telefone. A equipe usa estes dados para retornar.</p>
          </div>
          <form id="pedido" class="form" novalidate data-fix="{n} campos para corrigir." @submit.prevent="submitForm">
            <div class="field">
              <label for="f-cidade">Cidade</label>
              <input id="f-cidade" name="cidade" type="text" autocomplete="organization" v-model="formContact.cidade" required aria-describedby="f-cidade-err" data-error="Escreva o nome da cidade." />
              <p class="field__error" id="f-cidade-err" hidden>Escreva o nome da cidade.</p>
            </div>
            <div class="field">
              <label for="f-nome">Nome</label>
              <input id="f-nome" name="nome" type="text" autocomplete="name" v-model="formContact.nome" required aria-describedby="f-nome-err" data-error="Escreva o seu nome." />
              <p class="field__error" id="f-nome-err" hidden>Escreva o seu nome.</p>
            </div>
            <div class="field">
              <label for="f-email">E-mail</label>
              <input id="f-email" name="email" type="email" autocomplete="email" inputmode="email" v-model="formContact.email" required aria-describedby="f-email-err" data-error="Escreva um e-mail com @." />
              <p class="field__error" id="f-email-err" hidden>Escreva um e-mail com @.</p>
            </div>
            <div class="field">
              <label for="f-telefone">Telefone</label>
              <input id="f-telefone" name="telefone" type="tel" autocomplete="tel" inputmode="tel" v-model="formContact.telefone" required aria-describedby="f-telefone-err" data-error="Escreva um telefone para retorno." />
              <p class="field__error" id="f-telefone-err" hidden>Escreva um telefone para retorno.</p>
            </div>
            <div class="btn-row">
              <button class="btn btn--primary" type="submit" :aria-busy="submitting ? 'true' : 'false'">Solicitar apresentação</button>
            </div>
            <p class="form__status" role="status" data-form-status>{{ statusMsg }}</p>
          </form>
        </div>
      </section>
    </main>

    <footer class="footer closing footer--statement" aria-labelledby="close-h">
      <div class="wrap">
        <div class="closing__cta close--word">
          <div class="section-head--split">
            <div>
              <h2 id="close-h">Peça a apresentação da secretaria</h2>
              <p class="t-lead">O pedido chega à equipe do Esporte Local.</p>
            </div>
            <a class="btn btn--primary" href="#contato">Solicitar apresentação</a>
          </div>
          <p class="close__word" aria-hidden="true">Esporte Local</p>
        </div>
        <div class="closing__links">
          <div class="grid">
            <div class="span-4 stack-4">
              <a class="nav__logo" href="#top">
                <img v-if="!logoError" :src="logoSrc" alt="Esporte Local" />
                <template v-else>
                  <span class="mark" data-mark="ring" aria-hidden="true"></span>
                  Esporte Local
                </template>
              </a>
              <p class="footer__lede">Sistema de campeonatos, atletas e súmula para a secretaria de esportes.</p>
            </div>
            <div class="span-2 footer__col">
              <h3>Produto</h3>
              <ul>
                <li><a href="#sumula">Súmula</a></li>
                <li><a href="#recursos">Recursos</a></li>
                <li><a href="#modalidades">Modalidades</a></li>
              </ul>
            </div>
            <div class="span-2 footer__col">
              <h3>Uso</h3>
              <ul>
                <li><a href="#passos">Passos</a></li>
                <li><a href="#perguntas">Perguntas</a></li>
                <li><router-link to="/login">Entrar</router-link></li>
              </ul>
            </div>
            <div class="span-2 footer__col">
              <h3>Contato</h3>
              <ul>
                <li><a href="#contato">Apresentação</a></li>
                <li><router-link to="/sumula-eletronica">Página da súmula</router-link></li>
              </ul>
            </div>
          </div>
          <div class="footer__legal">
            <span>© {{ year }} Esporte Local</span>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/services/api';

const PAGE_TITLE = 'Esporte Local: gestão esportiva para prefeituras';
const router = useRouter();

const logoSrc = '/logo-sge.png';
const logoError = ref(false);
const submitting = ref(false);
const statusMsg = ref('');
const year = new Date().getFullYear();
const formContact = ref({ cidade: '', nome: '', email: '', telefone: '' });

const partidas = [
  { id: 1, sigla: 'FS', tone: 1, jogo: 'Casa 2 × 1 Visitante', chave: 'Futsal sub-17 · hoje', estado: 'Ao vivo', estadoClasse: '' },
  { id: 2, sigla: 'VO', tone: 3, jogo: 'Casa × Visitante', chave: 'Vôlei · 2º set', estado: 'Ao vivo', estadoClasse: '' },
  { id: 3, sigla: 'FB', tone: 4, jogo: 'Casa × Visitante', chave: 'Futebol · oitavas', estado: 'Tabela', estadoClasse: '' },
  { id: 4, sigla: 'BA', tone: 2, jogo: 'Casa 71 × 64 Visitante', chave: 'Basquete · final', estado: 'Encerrada', estadoClasse: 'is-done' },
  { id: 5, sigla: 'HA', tone: 5, jogo: 'Casa × Visitante', chave: 'Handebol · grupo B', estado: 'Tabela', estadoClasse: '' },
  { id: 6, sigla: 'NA', tone: 1, jogo: '50 m livre', chave: 'Natação · eliminatória', estado: 'Tabela', estadoClasse: '' },
  { id: 7, sigla: 'AT', tone: 3, jogo: '100 m rasos', chave: 'Atletismo · final', estado: 'Encerrada', estadoClasse: 'is-done' },
  { id: 8, sigla: 'JU', tone: 4, jogo: 'Até 60 kg', chave: 'Judô · oitavas', estado: 'Tabela', estadoClasse: '' },
  { id: 9, sigla: 'CO', tone: 2, jogo: '5 km', chave: 'Corrida · largada 7h', estado: 'Tabela', estadoClasse: '' },
];

const STYLE_LINKS = [
  ['preconnect', 'https://fonts.googleapis.com'],
  ['preconnect', 'https://fonts.gstatic.com'],
  ['stylesheet', 'https://fonts.googleapis.com/css2?family=Host+Grotesk:wght@400..700&family=JetBrains+Mono:wght@400..500&display=swap'],
  ['stylesheet', '/taste/system.css'],
  ['stylesheet', '/taste/ui/cards.css'],
  ['stylesheet', '/taste/motion/motion.css'],
  ['stylesheet', '/taste/backgrounds/backgrounds.css'],
  ['stylesheet', '/taste/layout/layout.css'],
  ['stylesheet', '/taste/icons/icon.css'],
  ['stylesheet', '/taste/ui/base.css'],
  ['stylesheet', '/taste/ui/window/fragment.css'],
  ['stylesheet', '/taste/ui/doc/fragment.css'],
  ['stylesheet', '/taste/ui/checklist/fragment.css'],
  ['stylesheet', '/taste/ui/notify/fragment.css'],
  ['stylesheet', '/taste/ui/toggles/fragment.css'],
  ['stylesheet', '/taste/moments/marquee-row/moment.css'],
  ['stylesheet', '/taste/moments/word-highlight/moment.css'],
];

const ROOT_CSS = `:root {
  --p-bg: #f7f9f8; --p-surface: #ebf0ee; --p-ink: #06525c; --p-muted: #2f737d;
  --p-accent: #06525c; --p-accent-ink: #f7f9f8; --p-action: #06525c; --p-action-ink: #f7f9f8;
  --p-inv-bg: #06525c; --p-inv-surface: #205d66; --p-inv-ink: #f7f9f8; --p-inv-muted: #c1d0d2;
  --p-inv-accent: #f7f9f8; --p-inv-accent-ink: #06525c; --p-inv-action: #f7f9f8; --p-inv-action-ink: #06525c;
  --font-display: "Host Grotesk", system-ui, sans-serif;
  --font-text: "Host Grotesk", system-ui, sans-serif;
  --font-mono: "JetBrains Mono", ui-monospace, "SFMono-Regular", Menlo, Consolas, monospace;
  --display-weight: 500;
  --display-tracking: -0.035em;
  --display-tracking-xl: -0.04em;
}`;

const PAGE_CSS = `
.taste-root { background: var(--p-bg); color: var(--p-ink); font-family: var(--font-text); }
.nav.is-open .nav__links { z-index: 40; }
#recursos .panel { --p-h: 336px; }
#passos .panel { --p-h: 0; }
.app.ui { display: grid; grid-template-columns: minmax(0, 5fr) minmax(0, 7fr); margin-top: var(--s-7); background: var(--u-bg); border: 1px solid var(--u-border); border-radius: var(--u-r); box-shadow: var(--u-shadow); }
.app > .app__pane { position: relative; min-width: 0; min-height: 0; overflow: hidden; }
.app > .app__pane + .app__pane { border-left: 1px solid var(--u-line); }
.app > .app__pane > .ui-panel { width: 100%; border: 0; border-radius: 0; box-shadow: none; }
.app > .app__pane::after { content: ""; position: absolute; inset: auto 0 0 0; height: 64px; background: linear-gradient(transparent, var(--u-bg)); pointer-events: none; }
#contato .form { margin-top: var(--s-4); }
@media (min-width: 1024px) and (max-width: 1279px) { .app.ui { grid-template-columns: minmax(0, 4fr) minmax(0, 8fr); } }
@media (max-width: 1023px) {
  .app.ui { grid-template-columns: minmax(0, 1fr); grid-template-rows: auto minmax(0, 1fr); }
  .app > .app__pane--list { max-height: 252px; }
  .app > .app__pane--list::after { display: none; }
  .app .ui-win__side { display: none; }
  .app .ui-win__body { grid-template-columns: minmax(0, 1fr); }
  .app > .app__pane + .app__pane { border-left: 0; border-top: 1px solid var(--u-line); }
}
@media (max-width: 639px) {
  .app.ui { grid-template-rows: minmax(0, 1fr); }
  .app > .app__pane--list { display: none; }
  .app > .app__pane + .app__pane { border-top: 0; }
  #recursos .panel { --p-h: 220px; }
}
`;

const added = [];
let previous = null;

const remember = (node) => {
  added.push(node);
  return node;
};

const setMeta = (attr, key, value) => {
  let el = document.head.querySelector(`meta[${attr}="${key}"]`);
  if (!el) {
    el = remember(document.createElement('meta'));
    el.setAttribute(attr, key);
    el.dataset.taste = '1';
    document.head.appendChild(el);
  }
  el.setAttribute('content', value);
};

const mountTaste = () => {
  const html = document.documentElement;
  previous = {
    lang: html.lang,
    title: document.title,
    attrs: {
      type: html.getAttribute('data-type'),
      radius: html.getAttribute('data-radius'),
      ground: html.getAttribute('data-ground'),
      buttons: html.getAttribute('data-buttons'),
      motion: html.hasAttribute('data-motion'),
    },
  };
  html.lang = 'pt';
  html.dataset.type = 'software';
  html.dataset.radius = 'soft';
  html.dataset.ground = 'painting';
  html.dataset.buttons = 'solid';
  html.setAttribute('data-motion', '');
  html.classList.add('m-enter', 'mo-js');
  window.setTimeout(() => html.classList.add('m-enter-done'), 700);
  window.setTimeout(() => html.classList.add('mo-safe'), 2500);

  document.title = PAGE_TITLE;
  setMeta('name', 'description', 'A secretaria cadastra atletas, monta a tabela e publica o portal da cidade. O portal entra no ar em até 48 horas úteis.');
  setMeta('name', 'site-kind', 'software');
  setMeta('name', 'page-type', 'software');
  setMeta('name', 'theme-color', '#f7f9f8');
  setMeta('property', 'og:title', 'Esporte Local: gestão esportiva para prefeituras');
  setMeta('property', 'og:description', 'Cadastro de atletas, tabela e súmula no celular para a secretaria de esportes.');
  setMeta('property', 'og:type', 'website');

  const style = document.createElement('style');
  style.dataset.taste = '1';
  style.textContent = `${ROOT_CSS}\n${PAGE_CSS}`;

  for (const [rel, href] of STYLE_LINKS) {
    const link = remember(document.createElement('link'));
    link.rel = rel;
    link.href = href;
    link.dataset.taste = '1';
    if (href.includes('gstatic')) link.crossOrigin = 'anonymous';
    document.head.appendChild(link);
  }

  document.head.appendChild(remember(style));

  const bg = remember(document.createElement('script'));
  bg.src = '/taste/backgrounds/backgrounds.js';
  bg.dataset.taste = '1';
  document.head.appendChild(bg);
};

const unmountTaste = () => {
  const html = document.documentElement;
  if (!previous) return;
  html.lang = previous.lang || 'en';
  document.title = previous.title || 'Esporte Local';
  html.classList.remove('m-enter', 'm-enter-done', 'mo-js', 'mo-safe');
  const { attrs } = previous;
  if (attrs.type) html.setAttribute('data-type', attrs.type); else html.removeAttribute('data-type');
  if (attrs.radius) html.setAttribute('data-radius', attrs.radius); else html.removeAttribute('data-radius');
  if (attrs.ground) html.setAttribute('data-ground', attrs.ground); else html.removeAttribute('data-ground');
  if (attrs.buttons) html.setAttribute('data-buttons', attrs.buttons); else html.removeAttribute('data-buttons');
  if (!attrs.motion) html.removeAttribute('data-motion');
  for (const node of added) node.remove();
  added.length = 0;
  previous = null;
};

const submitForm = async () => {
  if (submitting.value) return;
  const form = document.getElementById('pedido');
  const fields = [...form.querySelectorAll('input')];
  const bad = fields.filter((el) => !el.checkValidity());
  fields.forEach((el) => {
    const err = document.getElementById(el.getAttribute('aria-describedby'));
    if (el.checkValidity()) {
      el.removeAttribute('aria-invalid');
      if (err) err.hidden = true;
    } else {
      el.setAttribute('aria-invalid', 'true');
      if (err) err.hidden = false;
    }
  });
  if (bad.length) {
    statusMsg.value = bad.length === 1 ? '1 campo para corrigir.' : `${bad.length} campos para corrigir.`;
    bad[0].focus();
    return;
  }
  submitting.value = true;
  statusMsg.value = '';
  try {
    await api.post('/public/leads', formContact.value);
    formContact.value = { cidade: '', nome: '', email: '', telefone: '' };
    statusMsg.value = 'Pedido de apresentação enviado. A equipe retorna neste contato.';
  } catch (error) {
    console.error(error);
    statusMsg.value = 'O pedido não saiu. Os dados continuam no formulário. Tente de novo.';
  } finally {
    submitting.value = false;
  }
};

const loadModule = (src) => new Promise((resolve, reject) => {
  if (document.querySelector(`script[data-taste-src="${src}"]`)) {
    resolve();
    return;
  }
  const script = document.createElement('script');
  script.type = 'module';
  script.src = src;
  script.dataset.tasteSrc = src;
  script.onload = () => resolve();
  script.onerror = () => reject(new Error(src));
  document.body.appendChild(script);
});

mountTaste();
const stopTitle = router.afterEach(() => {
  document.title = PAGE_TITLE;
});

onMounted(async () => {
  document.title = PAGE_TITLE;
  await loadModule('/taste/system.js');
  await loadModule('/taste/motion/motion.js');
  await loadModule('/taste/layout/layout.js');
});

onUnmounted(() => {
  stopTitle();
  unmountTaste();
});
</script>
