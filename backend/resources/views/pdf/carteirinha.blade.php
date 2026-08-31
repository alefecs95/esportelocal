<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        @page { margin: 0; size: 360pt 220pt; }
        html, body {
            margin: 0;
            padding: 0;
            width: 360pt;
            height: 220pt;
            font-family: DejaVu Sans, Arial, sans-serif;
            background: #f3f4f6;
        }
        .page {
            position: relative;
            width: 360pt;
            height: 220pt;
            overflow: hidden;
            background: #f3f4f6;
        }
        .card {
            position: absolute;
            left: 8pt;
            top: 8pt;
            width: 344pt;
            height: 204pt;
            box-sizing: border-box;
            border: 1pt solid #d1d5db;
            border-radius: 10pt;
            background: #ffffff;
            overflow: hidden;
            color: #111827;
            page-break-inside: avoid;
        }
        .header,
        .body,
        .team,
        .footer {
            position: absolute;
            left: 0;
            width: 100%;
            box-sizing: border-box;
            page-break-inside: avoid;
        }
        .header {
            top: 0;
            height: 52pt;
            padding: 10pt 12pt 8pt 12pt;
            border-bottom: 2pt solid #2563eb;
        }
        .body {
            top: 52pt;
            height: 88pt;
            padding: 10pt 12pt 8pt 12pt;
        }
        .team {
            top: 140pt;
            height: 28pt;
            padding: 5pt 12pt;
            background: #f8fafc;
            border-top: 1pt solid #e5e7eb;
            border-bottom: 1pt solid #e5e7eb;
        }
        .footer {
            top: 168pt;
            height: 36pt;
            padding: 10pt 12pt;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .header-table td {
            vertical-align: top;
        }
        .logo-pref {
            width: 58pt;
        }
        .logo-pref img {
            width: 48pt;
            height: 32pt;
        }
        .org-wrap {
            width: 230pt;
            padding-left: 6pt;
        }
        .org-name {
            font-size: 6.2pt;
            line-height: 1.2;
            font-weight: bold;
            color: #6b7280;
            text-transform: uppercase;
            max-height: 15pt;
            overflow: hidden;
        }
        .champ {
            margin-top: 4pt;
            font-size: 12.2pt;
            line-height: 1.12;
            font-weight: bold;
            color: #1d4ed8;
            text-transform: uppercase;
            max-height: 28pt;
            overflow: hidden;
        }
        .logo-clube {
            width: 32pt;
            text-align: right;
        }
        .logo-clube img {
            width: 28pt;
            height: 28pt;
        }
        .body-table td {
            vertical-align: top;
        }
        .photo-col {
            width: 98pt;
        }
        .photo {
            width: 86pt;
            height: 72pt;
            border: 1pt solid #cbd5e1;
            border-radius: 8pt;
            background: #f8fafc;
            overflow: hidden;
            text-align: center;
        }
        .photo img {
            width: 86pt;
            height: 72pt;
        }
        .placeholder {
            width: 86pt;
            height: 72pt;
            line-height: 72pt;
            font-size: 24pt;
            font-weight: bold;
            color: #94a3b8;
            background: #eef2f7;
        }
        .info-col {
            width: 222pt;
            padding-left: 4pt;
        }
        .athlete-name {
            font-size: 12.2pt;
            line-height: 1.15;
            font-weight: bold;
            color: #111827;
            text-transform: uppercase;
            max-height: 30pt;
            overflow: hidden;
            padding-bottom: 5pt;
            border-bottom: 1pt solid #e5e7eb;
        }
        .meta {
            margin-top: 8pt;
            width: 100%;
        }
        .meta td {
            vertical-align: top;
        }
        .label {
            font-size: 6.1pt;
            line-height: 1.1;
            color: #6b7280;
            text-transform: uppercase;
            margin-bottom: 2pt;
        }
        .value {
            font-size: 10pt;
            line-height: 1.1;
            font-weight: bold;
            color: #111827;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .team-table td {
            vertical-align: middle;
        }
        .team-label {
            width: 56pt;
            font-size: 7pt;
            font-weight: bold;
            color: #2563eb;
            text-transform: uppercase;
        }
        .team-name-wrap {
            border: 1pt solid #cbd5e1;
            border-radius: 6pt;
            background: #ffffff;
            padding: 4pt 8pt;
        }
        .team-name {
            font-size: 10pt;
            font-weight: bold;
            color: #111827;
            text-transform: uppercase;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .footer-text {
            font-size: 9pt;
            font-weight: bold;
            color: #2563eb;
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="card">
            <div class="header">
                <table class="header-table" cellspacing="0" cellpadding="0">
                    <tr>
                        <td class="logo-pref">
                            @if(!empty($logo_secretaria))
                                <img src="{{ $logo_secretaria }}" alt="Logo Prefeitura">
                            @endif
                        </td>
                        <td class="org-wrap">
                            <div class="org-name">{{ Str::upper($prefeitura?->nome ?? 'SECRETARIA DE ESPORTE') }}</div>
                            <div class="champ">{{ Str::upper(Str::limit($campeonato->nome, 34)) }}</div>
                        </td>
                        <td class="logo-clube">
                            @if(!empty($logo_equipe))
                                <img src="{{ $logo_equipe }}" alt="Logo Equipe">
                            @endif
                        </td>
                    </tr>
                </table>
            </div>

            <div class="body">
                <table class="body-table" cellspacing="0" cellpadding="0">
                    <tr>
                        <td class="photo-col">
                            <div class="photo">
                                @if(!empty($foto_atleta))
                                    <img src="{{ $foto_atleta }}" alt="Foto do Atleta">
                                @else
                                    <div class="placeholder">?</div>
                                @endif
                            </div>
                        </td>
                        <td class="info-col">
                            <div class="athlete-name">{{ Str::upper(Str::limit($atleta->nome, 42)) }}</div>
                            <table class="meta" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td style="width: 130pt; padding-right: 10pt;">
                                        <div class="label">CPF</div>
                                        <div class="value">{{ $atleta->cpf }}</div>
                                    </td>
                                    <td>
                                        <div class="label">Nascimento</div>
                                        <div class="value">{{ \Carbon\Carbon::parse($atleta->data_nascimento)->format('d/m/Y') }}</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="team">
                <table class="team-table" cellspacing="0" cellpadding="0">
                    <tr>
                        <td class="team-label">Equipe</td>
                        <td>
                            <div class="team-name-wrap">
                                <div class="team-name">{{ Str::upper(Str::limit($equipe?->nome ?? 'SEM EQUIPE', 30)) }}</div>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="footer">
                <div class="footer-text">Validade: {{ $validade }}</div>
            </div>
        </div>
    </div>
</body>
</html>
