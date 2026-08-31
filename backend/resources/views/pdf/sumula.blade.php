<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        @page { margin: 10mm 10mm 18mm 10mm; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #0f172a; margin: 0; }
        .page { border: 1px solid #dbe2ea; padding: 0; page-break-after: always; }
        .page.last { page-break-after: auto; }
        .header { background: #0f172a; color: #fff; padding: 9px 12px; }
        .title { margin: 0; font-size: 14px; font-weight: 700; text-transform: uppercase; }
        .subtitle { margin: 2px 0 0; font-size: 9px; color: #cbd5e1; }
        .meta { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .meta td { border: 1px solid #e2e8f0; padding: 5px 6px; background: #f8fafc; vertical-align: top; }
        .label { font-size: 8px; color: #64748b; text-transform: uppercase; font-weight: 700; display: block; }
        .value { font-size: 10px; font-weight: 700; color: #0f172a; }
        .score { width: 150px; margin: 7px auto; text-align: center; border: 2px solid #1e293b; padding: 5px 0; font-size: 22px; font-weight: 800; }
        .section-title { margin: 6px 8px 4px; background: #e2e8f0; border: 1px solid #cbd5e1; padding: 4px 6px; font-weight: 800; text-transform: uppercase; font-size: 10px; }
        .team-name { margin: 0 8px 6px; border: 1px solid #dbe2ea; padding: 5px 6px; font-size: 11px; font-weight: 700; }
        .team-name.mandante { color: #1d4ed8; }
        .team-name.visitante { color: #dc2626; }
        .players { width: calc(100% - 16px); margin: 0 8px; border-collapse: collapse; table-layout: fixed; font-size: 9px; }
        .players th, .players td { border: 1px solid #e2e8f0; padding: 3px 4px; }
        .players th { background: #f8fafc; text-transform: uppercase; font-size: 8px; color: #334155; }
        .num { text-align: center; }
        .events { width: calc(100% - 16px); margin: 6px 8px 0; border-collapse: collapse; table-layout: fixed; }
        .events td { width: 50%; vertical-align: top; padding: 0 3px; }
        .event-box { border: 1px solid #dbe2ea; min-height: 72px; }
        .event-title { background: #f1f5f9; border-bottom: 1px solid #e2e8f0; padding: 4px 6px; font-size: 9px; font-weight: 800; text-transform: uppercase; }
        .event-list { padding: 4px 6px; font-size: 9px; line-height: 1.4; }
        .event-item { margin-bottom: 2px; }
        .empty { color: #94a3b8; }
        .legend { text-align: center; font-size: 8px; color: #64748b; margin-top: 2px; margin-bottom: 4px; }
        .obs { border: 1px solid #dbe2ea; margin: 8px 8px 0; min-height: 42px; padding: 6px; }
        .obs-title { font-weight: 800; text-transform: uppercase; font-size: 9px; color: #334155; margin-bottom: 4px; }
        .signatures { width: calc(100% - 16px); margin: 8px 8px 10px; border-collapse: collapse; table-layout: fixed; }
        .signatures td { width: 33.33%; text-align: center; padding: 0 5px; vertical-align: bottom; }
        .sign-line { border-top: 1px solid #334155; padding-top: 4px; font-size: 9px; }
        .sign-img { max-height: 38px; margin: 0 auto 2px; display: block; }
    </style>
</head>
<body>
    @php
        $formatCpf = function ($cpf) {
            $digits = preg_replace('/\D/', '', (string) $cpf);
            if (strlen($digits) !== 11) return $cpf ?: '-';
            return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $digits);
        };
        $stats = $estatisticas_atleta ?? [];
        $sumarioMandante = $sumario_eventos[(int) $partida->mandante_id] ?? ['gols' => [], 'ca' => [], 'cv' => [], 'subs' => []];
        $sumarioVisitante = $sumario_eventos[(int) $partida->visitante_id] ?? ['gols' => [], 'ca' => [], 'cv' => [], 'subs' => []];
        $mandanteAtletas = $partida->mandante_equipe?->atletas ?? collect();
        $visitanteAtletas = $partida->visitante_equipe?->atletas ?? collect();
        $renderList = function ($items) {
            if (empty($items)) {
                return '<span class="empty">Sem registros</span>';
            }
            return collect($items)->map(fn ($item) => '<div class="event-item">• ' . e($item) . '</div>')->implode('');
        };
    @endphp
    <div class="page">
        <div class="header">
            <p class="title">Súmula Oficial de Partida</p>
            <p class="subtitle">{{ $campeonato->nome }} • {{ $partida->fase->nome }}</p>
        </div>

        <table class="meta">
            <tr>
                <td><span class="label">Data/Hora</span><span class="value">{{ $data_jogo }}</span></td>
                <td><span class="label">Local</span><span class="value">{{ $partida->local ?: 'A definir' }}</span></td>
                <td><span class="label">Rodada</span><span class="value">{{ $partida->rodada ? $partida->rodada . 'ª' : '-' }}</span></td>
                <td><span class="label">Árbitro</span><span class="value">{{ $partida->arbitro?->name ?: 'Não designado' }}</span></td>
            </tr>
        </table>

        <div class="score">{{ $partida->placar_mandante ?? 0 }} X {{ $partida->placar_visitante ?? 0 }}</div>
        <div class="legend">Situação: TIT = Titular, RES = Reserva • Cap: CAP = Capitão</div>
        <div class="section-title">Elenco - Mandante</div>
        <div class="team-name mandante">{{ $partida->mandante_equipe?->nome ?: 'Mandante' }}</div>

        <table class="players">
            <thead>
                <tr>
                    <th style="width:6%">Nº</th>
                    <th style="width:8%">Sit</th>
                    <th style="width:8%">Cap</th>
                    <th style="width:36%">Atleta</th>
                    <th style="width:24%">CPF</th>
                    <th style="width:6%">Gol</th>
                    <th style="width:6%">CA</th>
                    <th style="width:6%">CV</th>
                </tr>
            </thead>
            <tbody>
                @if($mandanteAtletas->count())
                    @foreach($mandanteAtletas as $index => $atleta)
                        @php $atletaStats = $stats[(int) $atleta->id] ?? ['gols' => 0, 'ca' => 0, 'cv' => 0]; @endphp
                        <tr>
                            <td class="num">{{ $index + 1 }}</td>
                            <td class="num">{{ $atleta->escala_tipo ?? '' }}</td>
                            <td class="num">{{ !empty($atleta->escala_capitao) ? 'CAP' : '' }}</td>
                            <td>{{ Str::limit($atleta->nome, 44) }}</td>
                            <td>{{ $formatCpf($atleta->cpf) }}</td>
                            <td class="num">{{ $atletaStats['gols'] ?: '' }}</td>
                            <td class="num">{{ $atletaStats['ca'] ?: '' }}</td>
                            <td class="num">{{ $atletaStats['cv'] ?: '' }}</td>
                        </tr>
                    @endforeach
                @else
                    <tr><td colspan="8" class="empty" style="text-align:center;">Sem atletas cadastrados</td></tr>
                @endif
            </tbody>
        </table>

        <table class="events">
            <tr>
                <td>
                    <div class="event-box">
                        <div class="event-title">Gols Mandante</div>
                        <div class="event-list">{!! $renderList($sumarioMandante['gols']) !!}</div>
                    </div>
                </td>
                <td>
                    <div class="event-box">
                        <div class="event-title">Cartões Mandante</div>
                        <div class="event-list">
                            <strong>Amarelos</strong><br>{!! $renderList($sumarioMandante['ca']) !!}
                            <div style="height:4px;"></div>
                            <strong>Vermelhos</strong><br>{!! $renderList($sumarioMandante['cv']) !!}
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <div class="event-box">
                        <div class="event-title">Histórico de Substituições - Mandante</div>
                        <div class="event-list">{!! $renderList($sumarioMandante['subs']) !!}</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="page last">
        <div class="header">
            <p class="title">Súmula Oficial de Partida</p>
            <p class="subtitle">{{ $campeonato->nome }} • {{ $partida->fase->nome }}</p>
        </div>

        <table class="meta">
            <tr>
                <td><span class="label">Data/Hora</span><span class="value">{{ $data_jogo }}</span></td>
                <td><span class="label">Local</span><span class="value">{{ $partida->local ?: 'A definir' }}</span></td>
                <td><span class="label">Rodada</span><span class="value">{{ $partida->rodada ? $partida->rodada . 'ª' : '-' }}</span></td>
                <td><span class="label">Árbitro</span><span class="value">{{ $partida->arbitro?->name ?: 'Não designado' }}</span></td>
            </tr>
        </table>

        <div class="score">{{ $partida->placar_mandante ?? 0 }} X {{ $partida->placar_visitante ?? 0 }}</div>
        <div class="legend">Situação: TIT = Titular, RES = Reserva • Cap: CAP = Capitão</div>
        <div class="section-title">Elenco - Visitante</div>
        <div class="team-name visitante">{{ $partida->visitante_equipe?->nome ?: 'Visitante' }}</div>

        <table class="players">
            <thead>
                <tr>
                    <th style="width:6%">Nº</th>
                    <th style="width:8%">Sit</th>
                    <th style="width:8%">Cap</th>
                    <th style="width:36%">Atleta</th>
                    <th style="width:24%">CPF</th>
                    <th style="width:6%">Gol</th>
                    <th style="width:6%">CA</th>
                    <th style="width:6%">CV</th>
                </tr>
            </thead>
            <tbody>
                @if($visitanteAtletas->count())
                    @foreach($visitanteAtletas as $index => $atleta)
                        @php $atletaStats = $stats[(int) $atleta->id] ?? ['gols' => 0, 'ca' => 0, 'cv' => 0]; @endphp
                        <tr>
                            <td class="num">{{ $index + 1 }}</td>
                            <td class="num">{{ $atleta->escala_tipo ?? '' }}</td>
                            <td class="num">{{ !empty($atleta->escala_capitao) ? 'CAP' : '' }}</td>
                            <td>{{ Str::limit($atleta->nome, 44) }}</td>
                            <td>{{ $formatCpf($atleta->cpf) }}</td>
                            <td class="num">{{ $atletaStats['gols'] ?: '' }}</td>
                            <td class="num">{{ $atletaStats['ca'] ?: '' }}</td>
                            <td class="num">{{ $atletaStats['cv'] ?: '' }}</td>
                        </tr>
                    @endforeach
                @else
                    <tr><td colspan="8" class="empty" style="text-align:center;">Sem atletas cadastrados</td></tr>
                @endif
            </tbody>
        </table>

        <table class="events">
            <tr>
                <td>
                    <div class="event-box">
                        <div class="event-title">Gols Visitante</div>
                        <div class="event-list">{!! $renderList($sumarioVisitante['gols']) !!}</div>
                    </div>
                </td>
                <td>
                    <div class="event-box">
                        <div class="event-title">Cartões Visitante</div>
                        <div class="event-list">
                            <strong>Amarelos</strong><br>{!! $renderList($sumarioVisitante['ca']) !!}
                            <div style="height:4px;"></div>
                            <strong>Vermelhos</strong><br>{!! $renderList($sumarioVisitante['cv']) !!}
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <div class="event-box">
                        <div class="event-title">Histórico de Substituições - Visitante</div>
                        <div class="event-list">{!! $renderList($sumarioVisitante['subs']) !!}</div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="obs">
            <div class="obs-title">Observações do Árbitro</div>
            {!! nl2br(e($partida->observacoes ?? '')) !!}
        </div>

        <table class="signatures">
            <tr>
                <td>
                    @if($partida->assinatura_mandante)
                        <img src="{{ $partida->assinatura_mandante }}" class="sign-img">
                    @endif
                    <div class="sign-line">Capitão Mandante</div>
                </td>
                <td>
                    @if($partida->assinatura_arbitro)
                        <img src="{{ $partida->assinatura_arbitro }}" class="sign-img">
                    @endif
                    <div class="sign-line">Árbitro</div>
                </td>
                <td>
                    @if($partida->assinatura_visitante)
                        <img src="{{ $partida->assinatura_visitante }}" class="sign-img">
                    @endif
                    <div class="sign-line">Capitão Visitante</div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
