<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Comprovante de Inscrição</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #1f2937; }
        .card { border: 2px solid #1d4ed8; border-radius: 8px; padding: 20px; }
        .header { text-align: center; border-bottom: 1px solid #d1d5db; padding-bottom: 12px; margin-bottom: 16px; }
        .header h1 { margin: 0; font-size: 18px; color: #1e3a8a; text-transform: uppercase; }
        .header p { margin: 4px 0 0; color: #4b5563; font-size: 11px; }
        .peito-box { text-align: center; background: #eff6ff; border: 1px solid #bfdbfe; padding: 12px; border-radius: 6px; margin-bottom: 16px; }
        .peito-label { font-size: 10px; text-transform: uppercase; color: #1e40af; }
        .peito-num { font-size: 28px; font-weight: bold; color: #1d4ed8; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        td { padding: 7px 6px; border-bottom: 1px solid #e5e7eb; }
        td.label { width: 35%; font-weight: bold; color: #4b5563; }
        .notice { background: #fef3c7; border: 1px solid #fcd34d; padding: 10px; border-radius: 6px; font-size: 11px; color: #92400e; margin-top: 10px; }
        .kit-info { background: #f3f4f6; border: 1px solid #d1d5db; padding: 10px; border-radius: 6px; font-size: 11px; margin-top: 10px; }
        .footer { margin-top: 20px; font-size: 9px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <h1>{{ $campeonato->nome }}</h1>
            <p>Comprovante de Inscrição #{{ str_pad((string) $inscricao->id, 5, '0', STR_PAD_LEFT) }}</p>
            @if($campeonato->local || $campeonato->data_inicio)
                <p>
                    {{ $campeonato->data_inicio ? \Carbon\Carbon::parse($campeonato->data_inicio)->format('d/m/Y') : '' }}
                    @if($campeonato->local) — {{ $campeonato->local }} @endif
                </p>
            @endif
        </div>

        @if($inscricao->numero_peito)
        <div class="peito-box">
            <div class="peito-label">Número de Peito</div>
            <div class="peito-num">#{{ $inscricao->numero_peito }}</div>
        </div>
        @endif

        <table>
            <tr>
                <td class="label">Participante</td>
                <td>{{ $inscricao->atleta?->nome }}</td>
            </tr>
            <tr>
                <td class="label">Prova / Categoria</td>
                <td>
                    {{ $inscricao->categoria?->nome ?? $campeonato->categoria }}
                    @if($inscricao->categoria?->largada_prevista)
                        (Largada prevista: {{ $inscricao->categoria->largada_prevista }})
                    @endif
                </td>
            </tr>
            @if($inscricao->forma_contribuicao)
            <tr>
                <td class="label">Forma de Contribuição</td>
                <td>{{ ucfirst($inscricao->forma_contribuicao) }}</td>
            </tr>
            @endif
            <tr>
                <td class="label">Status da Inscrição</td>
                <td><strong>{{ strtoupper($inscricao->status) }}</strong></td>
            </tr>
            <tr>
                <td class="label">Data da Inscrição</td>
                <td>{{ $inscricao->created_at ? $inscricao->created_at->format('d/m/Y H:i') : $data_emissao }}</td>
            </tr>
        </table>

        @if($inscricao->status === 'pendente' && $campeonato->inscricao_solidaria)
        <div class="notice">
            <strong>Atenção:</strong> Sua inscrição está <strong>pendente</strong> até a entrega/confirmação da contribuição escolhida junto à organização. O número de peito será gerado após a confirmação.
        </div>
        @endif

        @if($depende_termo_menor)
        <div class="notice">
            <strong>Menor de 18 anos:</strong> Para retirar o número de peito, será obrigatório apresentar o termo de autorização assinado pelos pais ou responsável legal{{ $inscricao->responsavel_nome ? ' (' . $inscricao->responsavel_nome . ')' : '' }}.
        </div>
        @endif

        @if($campeonato->kit_retirada_info)
        <div class="kit-info">
            <strong>Retirada do Kit:</strong><br>
            {!! nl2br(e($campeonato->kit_retirada_info)) !!}
        </div>
        @endif

        <div class="footer">
            Emitido em {{ $data_emissao }} — Sistema EsporteLocal
        </div>
    </div>
</body>
</html>
