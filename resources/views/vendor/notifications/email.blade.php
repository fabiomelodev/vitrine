{{-- Modelo dos e-mails de notificação, em português (substitui o padrão do Laravel). --}}
<x-mail::message>
{{-- Saudação --}}
@if (! empty($greeting))
# {{ $greeting }}
@else
# {{ $level === 'error' ? 'Ops!' : 'Olá!' }}
@endif

{{-- Texto --}}
@foreach ($introLines as $line)
{{ $line }}

@endforeach

{{-- Botão --}}
@isset($actionText)
<?php
    $color = match ($level) {
        'success', 'error' => $level,
        default => 'primary',
    };
?>
<x-mail::button :url="$actionUrl" :color="$color">
{{ $actionText }}
</x-mail::button>
@endisset

{{-- Texto final --}}
@foreach ($outroLines as $line)
{{ $line }}

@endforeach

{{-- Assinatura --}}
@if (! empty($salutation))
{{ $salutation }}
@else
Abraços,<br>
Equipe {{ config('app.name') }}
@endif

{{-- Link alternativo ao botão --}}
@isset($actionText)
<x-slot:subcopy>
Se o botão "{{ $actionText }}" não funcionar, copie e cole este endereço no navegador: <span class="break-all">[{{ $displayableActionUrl }}]({{ $actionUrl }})</span>
</x-slot:subcopy>
@endisset
</x-mail::message>
