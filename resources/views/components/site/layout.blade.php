@props(['title' => null, 'description' => 'Crie o catálogo online da sua loja e receba pedidos organizados pelo WhatsApp. Sem taxa por venda.'])
@php $cssVersion = @filemtime(public_path('css/vitrine.css')) ?: 1; @endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' — Provador' : 'Provador — catálogo online com pedidos pelo WhatsApp' }}</title>
    <meta name="description" content="{{ $description }}">
    <meta property="og:title" content="Provador — catálogo online com pedidos pelo WhatsApp">
    <meta property="og:description" content="{{ $description }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600&family=Instrument+Serif:ital@0;1&display=swap">
    <link rel="stylesheet" href="{{ asset('css/vitrine.css') }}?v={{ $cssVersion }}">
</head>
<body>
    <a href="#conteudo" class="skip-link">Pular para o conteúdo</a>
    <header class="site-header">
        <div class="container header-row">
            <a href="{{ url('/') }}" class="brand"><span class="brand-name">provador</span></a>
            <nav class="header-nav only-desktop" aria-label="Site">
                <a href="{{ url('/') }}#como-funciona">Como funciona</a>
                <a href="{{ url('/') }}#preco">Preço</a>
                <a href="{{ url('/') }}#duvidas">Dúvidas</a>
            </nav>
            <div class="header-actions">
                <a href="{{ url('painel/login') }}" class="btn" style="min-height: 44px">Entrar</a>
                <a href="{{ url('painel/register') }}" class="btn btn-dark only-desktop" style="min-height: 44px">Criar minha loja</a>
            </div>
        </div>
    </header>

    <main id="conteudo">
        {{ $slot }}
    </main>

    <footer class="site-footer">
        <div class="container footer-row">
            <div class="footer-info">
                <strong>Provador</strong>
                <span>Catálogo online com pedidos pelo WhatsApp.</span>
            </div>
            <nav class="footer-nav" aria-label="Rodapé">
                <a href="{{ url('painel/register') }}">Criar minha loja</a>
                <a href="{{ url('painel/login') }}">Entrar</a>
                <a href="{{ url('termos') }}">Termos de uso</a>
                <a href="{{ url('privacidade') }}">Privacidade</a>
            </nav>
        </div>
        <div class="container footer-bottom">
            <span>© {{ now()->year }} Provador</span>
            @if ($legal = \App\Support\Platform::legalLine())
                <span>{{ $legal }}</span>
            @endif
        </div>
    </footer>
</body>
</html>
