@php $cssVersion = @filemtime(public_path('css/vitrine.css')) ?: 1; @endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $store->name }} — catálogo fora do ar</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600&family=Instrument+Serif:ital@0;1&display=swap">
    <link rel="stylesheet" href="{{ asset('css/vitrine.css') }}?v={{ $cssVersion }}">
</head>
<body>
    <main class="state-page">
        <h1 style="font-size: 26px; font-weight: 600">Este catálogo está temporariamente fora do ar</h1>
        <p class="muted" style="max-width: 440px; margin: 0">A {{ $store->name }} continua atendendo pelos seus canais. Volte em breve.</p>
        <a href="{{ $store->whatsappUrl() }}" class="btn" target="_blank" rel="noopener">Falar com a loja</a>
        <span class="small muted" style="margin-top: 16px">Tem uma loja? <a href="{{ url('painel/register') }}"><strong>Crie seu catálogo com Provador</strong></a></span>
    </main>
</body>
</html>
