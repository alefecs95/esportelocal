@component('mail::message')
# Olá, {{ $item->doador_nome }}

@if($aprovado)
Sua contribuição **{{ $item->titulo }}** foi aprovada e já pode aparecer na Memória do Esporte da cidade.
@else
Sua contribuição **{{ $item->titulo }}** não foi publicada neste momento.

@if($motivo)
**Motivo:** {{ $motivo }}
@endif
@endif

Obrigado por ajudar a preservar a história do esporte municipal.

@endcomponent
