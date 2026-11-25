<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Lista de Largada por Prova</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #1f2937; }
        .header { text-align: center; margin-bottom: 16px; border-bottom: 2px solid #d1d5db; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 16px; text-transform: uppercase; }
        .header p { margin: 4px 0 0; color: #6b7280; font-size: 10px; }
        .section { margin-bottom: 18px; page-break-inside: avoid; }
        .section-title { background-color: #eff6ff; border-left: 4px solid #2563eb; padding: 6px 10px; font-weight: bold; font-size: 12px; margin-bottom: 6px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 5px 6px; text-align: left; }
        th { background-color: #f9fafb; font-weight: bold; font-size: 10px; text-transform: uppercase; }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; font-size: 9px; color: #9ca3af; text-align: center; border-top: 1px solid #e5e7eb; padding-top: 4px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $campeonato->nome }}</h1>
        <p>Lista de Largada por Prova / Categoria — Confirmados: {{ $total }}</p>
        <p>Gerado em: {{ $data_emissao }}</p>
    </div>

    @forelse($gruposPorProva as $grupo)
    <div class="section">
        <div class="section-title">
            {{ $grupo['nome'] }}
            @if(!empty($grupo['largada_prevista']))
                — Largada prevista: {{ $grupo['largada_prevista'] }}
            @endif
            ({{ count($grupo['inscricoes']) }} atletas)
        </div>
        <table>
            <thead>
                <tr>
                    <th width="12%">Nº Peito</th>
                    <th width="42%">Atleta</th>
                    <th width="12%">Sexo</th>
                    <th width="14%">Kit</th>
                    <th width="20%">Tempo / Chegada</th>
                </tr>
            </thead>
            <tbody>
                @foreach($grupo['inscricoes'] as $insc)
                <tr>
                    <td style="text-align: center; font-weight: bold;">{{ $insc->numero_peito ?? '-' }}</td>
                    <td>{{ $insc->atleta?->nome }}</td>
                    <td>{{ $insc->atleta?->sexo ?? '-' }}</td>
                    <td>{{ $insc->kit_retirado_em ? 'Retirado' : 'Pendente' }}</td>
                    <td>&nbsp;</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @empty
        <p style="text-align: center; color: #6b7280; padding: 24px;">Nenhum atleta confirmado para largada.</p>
    @endforelse

    <div class="footer">
        Documento gerado pelo sistema EsporteLocal - {{ date('Y') }}
    </div>
</body>
</html>
