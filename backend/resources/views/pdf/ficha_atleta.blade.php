<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Ficha do Atleta</title>
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
            padding: 14px 16px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-logo {
            width: 74px;
            text-align: center;
            vertical-align: middle;
        }
        .logo-box {
            width: 58px;
            height: 58px;
            border-radius: 8px;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.35);
            overflow: hidden;
            display: inline-block;
        }
        .logo-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .header-content {
            vertical-align: middle;
        }
        .header-title {
            margin: 0;
            font-size: 19px;
            font-weight: 700;
        }
        .header-subtitle {
            margin: 2px 0 0;
            font-size: 10px;
            color: #bfdbfe;
        }
        .header-meta {
            margin-top: 6px;
            font-size: 10px;
            color: #e5e7eb;
        }
        .content {
            padding: 14px;
            background: #f8fafc;
        }
        .section {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 10px;
            overflow: hidden;
        }
        .section-title {
            margin: 0;
            background: #f1f5f9;
            color: #0f172a;
            font-size: 11px;
            font-weight: 700;
            padding: 7px 10px;
            border-bottom: 1px solid #e5e7eb;
            letter-spacing: 0.2px;
        }
        .section-body {
            padding: 8px 10px;
        }
        .grid {
            width: 100%;
            border-collapse: collapse;
        }
        .grid td {
            width: 50%;
            padding: 4px 6px 5px 0;
            vertical-align: top;
        }
        .grid td.full {
            width: 100%;
        }
        .label {
            font-size: 9px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.25px;
            margin-bottom: 1px;
        }
        .value {
            font-size: 11px;
            color: #111827;
            font-weight: 600;
            word-break: break-word;
        }
        .profile-table {
            width: 100%;
            border-collapse: collapse;
        }
        .profile-photo {
            width: 96px;
            vertical-align: top;
            padding-right: 10px;
        }
        .photo-box {
            width: 90px;
            height: 110px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            background: #e2e8f0;
            overflow: hidden;
            text-align: center;
            font-size: 9px;
            color: #334155;
            line-height: 110px;
        }
        .photo-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .profile-info {
            vertical-align: top;
        }
        .badge {
            display: inline-block;
            font-size: 9px;
            font-weight: 700;
            border-radius: 999px;
            padding: 4px 9px;
            margin-right: 6px;
            margin-bottom: 4px;
        }
        .ok {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }
        .blocked {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        .footer {
            font-size: 9px;
            color: #64748b;
            margin-top: 6px;
            text-align: right;
        }
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
        }
        .signature-table td {
            width: 50%;
            text-align: center;
            padding-top: 18px;
            color: #334155;
            font-size: 10px;
        }
        .line {
            border-top: 1px solid #94a3b8;
            margin: 0 auto 4px;
            width: 80%;
        }
    </style>
</head>
<body>
    <div class="sheet">
        <div class="header">
            <table class="header-table">
                <tr>
                    <td class="header-logo">
                        <div class="logo-box">
                            @if($instituicao_logo)
                                <img src="{{ $instituicao_logo }}" alt="Logo Instituição">
                            @endif
                        </div>
                    </td>
                    <td class="header-content">
                        <h1 class="header-title">Ficha Cadastral do Atleta</h1>
                        <p class="header-subtitle">Documento oficial para controle esportivo municipal</p>
                        <div class="header-meta">
                            <strong>{{ $instituicao->nome ?? 'Instituição não informada' }}</strong>
                            @if(!empty($instituicao?->endereco))
                                • {{ $instituicao->endereco }}
                            @endif
                            @if(!empty($instituicao?->telefone))
                                • {{ $instituicao->telefone }}
                            @endif
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="content">
            <div class="section">
                <h2 class="section-title">Identificação do Atleta</h2>
                <div class="section-body">
                    <table class="profile-table">
                        <tr>
                            <td class="profile-photo">
                                <div class="photo-box">
                                    @if($foto_atleta)
                                        <img src="{{ $foto_atleta }}" alt="Foto do Atleta">
                                    @else
                                        SEM FOTO
                                    @endif
                                </div>
                            </td>
                            <td class="profile-info">
                                <table class="grid">
                                    <tr>
                                        <td>
                                            <div class="label">Nome Completo</div>
                                            <div class="value">{{ $atleta->nome ?: '-' }}</div>
                                        </td>
                                        <td>
                                            <div class="label">CPF</div>
                                            <div class="value">{{ $atleta->cpf ?: '-' }}</div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="label">Data de Nascimento</div>
                                            <div class="value">{{ $atleta->data_nascimento ? \Carbon\Carbon::parse($atleta->data_nascimento)->format('d/m/Y') : '-' }}</div>
                                        </td>
                                        <td>
                                            <div class="label">Sexo</div>
                                            <div class="value">{{ $atleta->sexo ?: '-' }}</div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="label">Nome da Mãe</div>
                                            <div class="value">{{ $atleta->nome_mae ?: '-' }}</div>
                                        </td>
                                        <td>
                                            <div class="label">Nome do Pai</div>
                                            <div class="value">{{ $atleta->nome_pai ?: '-' }}</div>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="section">
                <h2 class="section-title">Dados de Contato e Endereço</h2>
                <div class="section-body">
                    <table class="grid">
                        <tr>
                            <td>
                                <div class="label">Telefone</div>
                                <div class="value">{{ $atleta->telefone ?: '-' }}</div>
                            </td>
                            <td>
                                <div class="label">Email</div>
                                <div class="value">{{ $atleta->email ?: '-' }}</div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="label">CEP</div>
                                <div class="value">{{ $atleta->cep ?: '-' }}</div>
                            </td>
                            <td>
                                <div class="label">Cidade / Estado</div>
                                <div class="value">{{ ($atleta->cidade ?: '-') . ' / ' . ($atleta->estado ?: '-') }}</div>
                            </td>
                        </tr>
                        <tr>
                            <td class="full" colspan="2">
                                <div class="label">Endereço Completo</div>
                                <div class="value">
                                    {{ $atleta->endereco ?: '-' }},
                                    {{ $atleta->numero ?: '-' }}
                                    @if($atleta->complemento)
                                        - {{ $atleta->complemento }}
                                    @endif
                                    - {{ $atleta->bairro ?: '-' }}
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="section">
                <h2 class="section-title">Dados Eleitorais</h2>
                <div class="section-body">
                    <table class="grid">
                        <tr>
                            <td>
                                <div class="label">Título de Eleitor</div>
                                <div class="value">{{ $atleta->titulo_eleitor ?: '-' }}</div>
                            </td>
                            <td>
                                <div class="label">Zona / Seção</div>
                                <div class="value">{{ ($atleta->zona_eleitoral ?: '-') . ' / ' . ($atleta->secao_eleitoral ?: '-') }}</div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="label">Bairro Eleitoral</div>
                                <div class="value">{{ $atleta->bairro_eleitoral ?: '-' }}</div>
                            </td>
                            <td>
                                <div class="label">Cidade / UF Eleitoral</div>
                                <div class="value">{{ ($atleta->cidade_eleitoral ?: '-') . ' / ' . ($atleta->uf_eleitoral ?: '-') }}</div>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="section">
                <h2 class="section-title">Situação do Cadastro</h2>
                <div class="section-body">
                    <span class="badge {{ $atleta->acesso_ativo ? 'ok' : 'blocked' }}">
                        {{ $atleta->acesso_ativo ? 'Acesso Ativo' : 'Acesso Inativo' }}
                    </span>
                    <span class="badge {{ $atleta->pode_participar_competicoes ? 'ok' : 'blocked' }}">
                        {{ $atleta->pode_participar_competicoes ? 'Competições Liberadas' : 'Competições Bloqueadas' }}
                    </span>
                </div>
            </div>

            <table class="signature-table">
                <tr>
                    <td>
                        <div class="line"></div>
                        Assinatura do Responsável
                    </td>
                    <td>
                        <div class="line"></div>
                        Assinatura do Atleta
                    </td>
                </tr>
            </table>

            <div class="footer">
                Documento emitido em {{ $data_emissao }} • {{ $instituicao->nome ?? 'SEMEL' }}
            </div>
        </div>
    </div>
</body>
</html>
