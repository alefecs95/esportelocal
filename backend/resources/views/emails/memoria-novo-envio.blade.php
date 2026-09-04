@component('mail::message')
# Novo envio da comunidade

**{{ $item->titulo }}** foi enviado por {{ $item->doador_nome }}.

Acesse a fila de curadoria da Memória do Esporte para aprovar ou recusar.

@component('mail::button', ['url' => $url ?? '#'])
Abrir curadoria
@endcomponent

@endcomponent
