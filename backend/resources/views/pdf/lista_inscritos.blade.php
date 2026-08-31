<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Lista de Inscritos</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #ddd;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            text-transform: uppercase;
        }
        .header p {
            margin: 5px 0 0;
            color: #666;
        }
        .category-section {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        .category-header {
            background-color: #f3f4f6;
            padding: 8px;
            border-bottom: 1px solid #ddd;
            font-weight: bold;
            font-size: 14px;
            display: flex;
            justify-content: space-between;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        th, td {
            border: 1px solid #eee;
            padding: 6px;
            text-align: left;
        }
        th {
            background-color: #fafafa;
            font-weight: bold;
            color: #444;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .status-badge {
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: bold;
        }
        .status-confirmada { background-color: #d1fae5; color: #065f46; }
        .status-pendente { background-color: #fef3c7; color: #92400e; }
        .status-cancelada { background-color: #fee2e2; color: #b91c1c; }
        
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            font-size: 10px;
            color: #999;
            text-align: center;
            border-top: 1px solid #eee;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $campeonato->nome }}</h1>
        <p>Lista de Participantes Inscritos</p>
        <p style="font-size: 10px; margin-top: 5px;">Gerado em: {{ date('d/m/Y H:i') }}</p>
    </div>

    @forelse($inscricoesPorCategoria as $categoriaNome => $inscricoes)
        <div class="category-section">
            <div class="category-header">
                <span>{{ $categoriaNome }}</span>
                <span style="float: right; font-size: 12px; font-weight: normal;">{{ count($inscricoes) }} inscritos</span>
            </div>
            <table>
                <thead>
                    <tr>
                        <th width="5%">#</th>
                        <th width="40%">Atleta</th>
                        <th width="15%">Idade</th>
                        <th width="20%">Equipe</th>
                        <th width="20%">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($inscricoes as $index => $inscricao)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            <div style="font-weight: bold;">{{ $inscricao->atleta->nome }}</div>
                            <!-- <div style="font-size: 10px; color: #666;">CPF: {{ $inscricao->atleta->cpf }}</div> -->
                        </td>
                        <td>
                            {{ \Carbon\Carbon::parse($inscricao->atleta->data_nascimento)->age }} anos
                        </td>
                        <td>
                            {{ $inscricao->atleta->equipes->where('campeonato_id', $campeonato->id)->first()->nome ?? '-' }}
                        </td>
                        <td>
                            <span class="status-badge status-{{ $inscricao->status }}">
                                {{ ucfirst($inscricao->status) }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @empty
        <div style="text-align: center; padding: 40px; color: #666;">
            Nenhum participante inscrito.
        </div>
    @endforelse

    <div class="footer">
        Documento gerado pelo sistema SEMEL - {{ date('Y') }}
    </div>
</body>
</html>
