<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relação Oficial de Atletas da Equipe</title>
    <style>
        @page {
            margin: 18px;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #111827;
            font-size: 11px;
            line-height: 1.35;
        }
        .sheet {
            border: 1px solid #d1d5db;
            border-radius: 10px;
            overflow: hidden;
        }
        .header {
            background: #0f172a;
            color: #ffffff;
            padding: 12px 14px;
            border-bottom: 3px solid #1d4ed8;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .logo-col {
            width: 76px;
            text-align: center;
            vertical-align: middle;
        }
        .title-col {
            vertical-align: middle;
            padding: 0 8px;
        }
        .logo-box {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            border: 1px solid rgba(255,255,255,0.35);
            background: rgba(255,255,255,0.15);
            overflow: hidden;
            display: inline-block;
        }
        .logo-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .title {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 0.2px;
        }
        .subtitle {
            margin: 2px 0 0;
            font-size: 10px;
            color: #bfdbfe;
        }
        .meta {
            margin-top: 5px;
            font-size: 10px;
            color: #e5e7eb;
        }
        .content {
            padding: 12px;
            background: #f8fafc;
        }
        .info-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 10px;
            overflow: hidden;
        }
        .info-title {
            background: #eff6ff;
            color: #1e3a8a;
            border-bottom: 1px solid #dbeafe;
            padding: 7px 9px;
            font-size: 11px;
            font-weight: 700;
        }
        .info-body {
            padding: 8px 9px;
        }
        .grid {
            width: 100%;
            border-collapse: collapse;
        }
        .grid td {
            width: 50%;
            vertical-align: top;
            padding: 2px 8px 6px 0;
        }
        .label {
            font-size: 9px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.25px;
        }
        .value {
            font-size: 11px;
            font-weight: 600;
            color: #0f172a;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        .table th, .table td {
            border: 1px solid #d1d5db;
            padding: 7px;
            text-align: left;
            vertical-align: top;
        }
        .table th {
            background: #e2e8f0;
            font-weight: 700;
            color: #0f172a;
        }
        .table tbody tr:nth-child(even) {
            background: #f8fafc;
        }
        .empty {
            text-align: center;
            color: #64748b;
        }
        .footer {
            border-top: 1px solid #e5e7eb;
            background: #ffffff;
            padding: 8px 12px;
            font-size: 9px;
            color: #334155;
        }
        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }
        .footer-left {
            vertical-align: middle;
        }
        .footer-right {
            width: 90px;
            text-align: right;
            vertical-align: middle;
        }
        .logo-rodape {
            max-width: 84px;
            max-height: 34px;
        }
        .official {
            margin-top: 4px;
            font-weight: 700;
            color: #1e3a8a;
            text-transform: uppercase;
            letter-spacing: 0.35px;
        }
    </style>
</head>
<body>
    @php
        $formatCpf = function (?string $cpf) {
            $digits = preg_replace('/\D/', '', (string) $cpf);
            if (strlen($digits) !== 11) return $cpf ?: '-';
            return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $digits);
        };
    @endphp

    <div class="sheet">
        <div class="header">
            <table class="header-table">
                <tr>
                    <td class="logo-col">
                        <div class="logo-box">
                            @if($logo_secretaria)
                                <img src="{{ $logo_secretaria }}" alt="Logo da Secretaria">
                            @endif
                        </div>
                    </td>
                    <td class="title-col">
                        <p class="title">Relação Oficial de Atletas da Equipe</p>
                        <p class="subtitle">Documento oficial para controle esportivo municipal</p>
                        <div class="meta">
                            <strong>{{ $prefeitura?->nome ?? 'Secretaria Municipal de Esporte' }}</strong>
                            @if(!empty($prefeitura?->telefone))
                                • {{ $prefeitura->telefone }}
                            @endif
                            @if(!empty($prefeitura?->email_contato))
                                • {{ $prefeitura->email_contato }}
                            @endif
                        </div>
                    </td>
                    <td class="logo-col">
                        <div class="logo-box">
                            @if($logo_equipe)
                                <img src="{{ $logo_equipe }}" alt="Logo da Equipe">
                            @endif
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="content">
            <div class="info-card">
                <div class="info-title">Dados Institucionais e da Equipe</div>
                <div class="info-body">
                    <table class="grid">
                        <tr>
                            <td>
                                <div class="label">Competição</div>
                                <div class="value">{{ $campeonato?->nome ?? 'N/A' }}</div>
                            </td>
                            <td>
                                <div class="label">Equipe</div>
                                <div class="value">{{ $equipe->nome }}</div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="label">Entidade</div>
                                <div class="value">{{ $equipe->entidade?->nome ?? '-' }}</div>
                            </td>
                            <td>
                                <div class="label">Responsável da Equipe</div>
                                <div class="value">{{ $equipe->responsavel ?: '-' }}</div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="label">Contato</div>
                                <div class="value">{{ $equipe->contato ?: '-' }}</div>
                            </td>
                            <td>
                                <div class="label">Data de Emissão</div>
                                <div class="value">{{ $data_emissao }}</div>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 34%">Nome</th>
                        <th style="width: 20%">CPF</th>
                        <th style="width: 12%">Idade</th>
                        <th style="width: 34%">Nome da Mãe</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($atletas as $atleta)
                        <tr>
                            <td>{{ $atleta->nome }}</td>
                            <td>{{ $formatCpf($atleta->cpf) }}</td>
                            <td>
                                @if($atleta->data_nascimento)
                                    {{ \Carbon\Carbon::parse($atleta->data_nascimento)->age }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $atleta->nome_mae ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="empty">Nenhum atleta encontrado nesta equipe.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="footer">
            <table class="footer-table">
                <tr>
                    <td class="footer-left">
                        {{ $prefeitura?->footer_titulo ?: ($prefeitura?->nome ?? 'Secretaria Municipal de Esporte') }}
                        <div class="official">Documento oficial da secretaria</div>
                    </td>
                    <td class="footer-right">
                        @if($logo_rodape)
                            <img src="{{ $logo_rodape }}" alt="Logo Rodapé" class="logo-rodape">
                        @endif
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
