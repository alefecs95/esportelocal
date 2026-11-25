<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Lista de Retirada de Kit</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #1f2937; }
        .header { text-align: center; margin-bottom: 16px; border-bottom: 2px solid #d1d5db; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 16px; text-transform: uppercase; }
        .header p { margin: 4px 0 0; color: #6b7280; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #d1d5db; padding: 6px; text-align: left; }
        th { background-color: #f3f4f6; font-weight: bold; font-size: 10px; text-transform: uppercase; }
        .badge { display: inline-block; padding: 2px 5px; border-radius: 3px; font-size: 9px; font-weight: bold; }
        .badge-warn { background-color: #fef3c7; color: #92400e; }
        .badge-ok { background-color: #d1fae5; color: #065f46; }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; font-size: 9px; color: #9ca3af; text-align: center; border-top: 1px solid #e5e7eb; padding-top: 4px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $campeonato->nome }}</h1>
        <p>Lista de Retirada de Kit (Número de Peito) — Total: {{ $inscricoes->count() }}</p>
        <p>Gerado em: {{ $data_emissao }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="8%">Nº Peito</th>
                <th width="26%">Atleta</th>
                <th width="18%">Prova / Categoria</th>
                <th width="14%">Contribuição</th>
                <th width="14%">Termo Menor</th>
                <th width="20%">Assinatura / Retirado por</th>
            </tr>
        </thead>
        <tbody>
            @forelse($inscricoes as $insc)
            <tr>
                <td style="text-align: center; font-weight: bold;">{{ $insc->numero_peito ?? '-' }}</td>
                <td>
                    <strong>{{ $insc->atleta?->nome }}</strong><br>
                    @if($insc->atleta?->tamanho_camiseta)
                        <span style="font-size: 9px; color: #6b7280;">Camiseta: {{ $insc->atleta->tamanho_camiseta }}</span>
                    @endif
                </td>
                <td>{{ $insc->categoria?->nome ?? 'Geral' }}</td>
                <td>
                    {{ $insc->forma_contribuicao ? ucfirst($insc->forma_contribuicao) : '-' }}
                    @if($insc->contribuicao_recebida_em)
                        <br><span class="badge badge-ok">Recebida</span>
                    @endif
                </td>
                <td>
                    @if($insc->menor_idade)
                        @if($insc->termo_responsavel_assinado_em)
                            <span class="badge badge-ok">Assinado</span><br>
                            <span style="font-size: 9px;">{{ $insc->responsavel_nome }}</span>
                        @else
                            <span class="badge badge-warn">Exige Termo</span>
                        @endif
                    @else
                        -
                    @endif
                </td>
                <td>
                    @if($insc->kit_retirado_em)
                        <span class="badge badge-ok">Retirado em {{ $insc->kit_retirado_em->format('d/m H:i') }}</span>
                        @if($insc->kit_retirado_por_nome)
                            <br><span style="font-size: 9px;">3º: {{ $insc->kit_retirado_por_nome }} ({{ $insc->kit_retirado_por_documento }})</span>
                        @endif
                    @else
                        &nbsp;
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align: center; padding: 20px; color: #6b7280;">Nenhuma inscrição encontrada.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Documento gerado pelo sistema EsporteLocal - {{ date('Y') }}
    </div>
</body>
</html>
