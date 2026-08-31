<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        @page { margin: 0; size: 360pt 220pt; }
        html, body { margin: 0; padding: 0; width: 360pt; height: 220pt; font-family: DejaVu Sans, Arial, sans-serif; }
        .page { width: 360pt; height: 220pt; padding: 8pt; box-sizing: border-box; background: #f3f4f6; }
        .card { width: 344pt; height: 204pt; box-sizing: border-box; padding: 16pt; background: #ffffff; color: #111827; border: 1pt solid #d1d5db; border-radius: 10pt; overflow: hidden; }
        .title { font-size: 13pt; font-weight: bold; margin-bottom: 8pt; text-transform: uppercase; color: #2563eb; }
        .label { font-size: 6.5pt; color: #6b7280; text-transform: uppercase; margin-top: 5pt; }
        .value { font-size: 10pt; font-weight: bold; color: #111827; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .footer { margin-top: 10pt; font-size: 8.5pt; font-weight: bold; color: #2563eb; }
    </style>
</head>
<body>
    <div class="page">
        <div class="card">
            <div class="title">{{ Str::upper(Str::limit($campeonato->nome, 34)) }}</div>
            <div class="label">Prefeitura</div>
            <div class="value">{{ Str::upper($prefeitura?->nome ?? 'SEMEL') }}</div>
            <div class="label">Atleta</div>
            <div class="value">{{ Str::upper($atleta->nome) }}</div>
            <div class="label">CPF</div>
            <div class="value">{{ $atleta->cpf }}</div>
            <div class="label">Nascimento</div>
            <div class="value">{{ \Carbon\Carbon::parse($atleta->data_nascimento)->format('d/m/Y') }}</div>
            <div class="label">Equipe</div>
            <div class="value">{{ Str::upper($equipe?->nome ?? 'SEM EQUIPE') }}</div>
            <div class="footer">Validade: {{ $validade }}</div>
        </div>
    </div>
</body>
</html>
