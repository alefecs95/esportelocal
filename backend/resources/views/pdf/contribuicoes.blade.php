<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Relatório de Contribuições Solidárias</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #1f2937; }
        .header { text-align: center; margin-bottom: 16px; border-bottom: 2px solid #d1d5db; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 16px; text-transform: uppercase; }
        .header p { margin: 4px 0 0; color: #6b7280; font-size: 10px; }
        .summary { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .summary td { border: 1px solid #d1d5db; padding: 8px; text-align: center; background-color: #f9fafb; }
        .summary .label { font-size: 9px; text-transform: uppercase; color: #6b7280; }
        .summary .value { font-size: 14px; font-weight: bold; color: #111827; margin-top: 2px; }
        table.lista { width: 100%; border-collapse: collapse; }
        table.lista th, table.lista td { border: 1px solid #d1d5db; padding: 5px 6px; text-align: left; }
        table.lista th { background-color: #f3f4f6; font-size: 10px; text-transform: uppercase; }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; font-size: 9px; color: #9ca3af; text-align: center; border-top: 1px solid #e5e7eb; padding-top: 4px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $campeonato->nome }}</h1>
        <p>Relatório de Contribuições Arrecadadas</p>
        <p>Gerado em: {{ $data_emissao }}</p>
    </div>

    <table class="summary">
        <tr>
            <td>
                <div class="label">Contribuições Recebidas</div>
                <div class="value">{{ $resumo['total_recebidas'] }} / {{ $resumo['total_inscricoes'] }}</div>
            </td>
            <td>
                <div class="label">Alimentos Arrecadados</div>
                <div class="value">{{ number_format($resumo['total_alimento_kg'], 2, ',', '.') }} kg</div>
            </td>
            <td>
                <div class="label">Brinquedos Arrecadados</div>
                <div class="value">{{ (int) $resumo['total_brinquedos'] }} un.</div>
            </td>
            <td>
                <div class="label">Valor em Dinheiro</div>
                <div class="value">R$ {{ number_format($resumo['total_dinheiro'], 2, ',', '.') }}</div>
            </td>
        </tr>
    </table>

    <table class="lista">
        <thead>
            <tr>
                <th width="8%">Peito</th>
                <th width="30%">Atleta</th>
                <th width="20%">Prova</th>
                <th width="14%">Forma</th>
                <th width="14%">Quantidade / Valor</th>
                <th width="14%">Recebido em</th>
            </tr>
        </thead>
        <tbody>
            @forelse($inscricoes as $insc)
            <tr>
                <td style="text-align: center;">{{ $insc->numero_peito_formatado ?? '-' }}</td>
                <td>{{ $insc->atleta?->nome }}</td>
                <td>{{ $insc->categoria?->nome ?? 'Geral' }}</td>
                <td>{{ $insc->forma_contribuicao ? ucfirst($insc->forma_contribuicao) : '-' }}</td>
                <td>
                    @if($insc->forma_contribuicao === 'dinheiro')
                        R$ {{ number_format((float) ($insc->valor_pago ?? 0), 2, ',', '.') }}
                    @elseif($insc->forma_contribuicao === 'alimento')
                        {{ number_format((float) ($insc->contribuicao_quantidade ?? 0), 2, ',', '.') }} kg
                    @elseif($insc->forma_contribuicao === 'brinquedo')
                        {{ (int) ($insc->contribuicao_quantidade ?? 1) }} un.
                    @else
                        -
                    @endif
                </td>
                <td>{{ $insc->contribuicao_recebida_em ? $insc->contribuicao_recebida_em->format('d/m/Y H:i') : 'Pendente' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align: center; padding: 20px; color: #6b7280;">Nenhuma inscrição registrada.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Documento gerado pelo sistema EsporteLocal - {{ date('Y') }}
    </div>
</body>
</html>
