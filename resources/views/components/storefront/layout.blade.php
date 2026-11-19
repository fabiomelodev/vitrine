@props(['title' => null, 'description' => null, 'image' => null])
@php
    $pageTitle = $title ? $title.' — '.$store->name : $store->name;
    $logo = \App\Support\Format::image($store->logo_path);
    $metaDescription = filled($description) ? $description : ($store->description ?: 'Catálogo da '.$store->name.'. Escolha suas peças e peça pelo WhatsApp.');
    // Imagem do link compartilhado: foto do produto (quando houver) ou logo da loja.
    $shareImage = $image ?: $logo;
    $shareImage = $shareImage ? url($shareImage) : null;
    $cssVersion = @filemtime(public_path('css/vitrine.css')) ?: 1;
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    <meta property="og:site_name" content="{{ $store->name }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:locale" content="pt_BR">
    @if ($shareImage)
        <meta property="og:image" content="{{ $shareImage }}">
        <meta property="og:image:alt" content="{{ $image ? $title : 'Logo da '.$store->name }}">
        <meta name="twitter:card" content="{{ $image ? 'summary_large_image' : 'summary' }}">
        <meta name="twitter:image" content="{{ $shareImage }}">
    @endif
    @if ($logo)
        <link rel="icon" href="{{ $logo }}">
        <link rel="apple-touch-icon" href="{{ $logo }}">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600&family=Instrument+Serif:ital@0;1&display=swap">
    <link rel="stylesheet" href="{{ asset('css/vitrine.css') }}?v={{ $cssVersion }}">
    <script>
        // Favoritos ficam no navegador (sem login), separados por loja.
        document.addEventListener('alpine:init', () => {
            const key = 'favs:{{ $store->slug }}';
            const read = () => { try { return JSON.parse(localStorage.getItem(key) || '[]'); } catch (e) { return []; } };
            Alpine.store('favs', {
                ids: read(),
                has(id) { return this.ids.includes(id); },
                toggle(id) {
                    const adding = ! this.has(id);
                    this.ids = adding ? [...this.ids, id] : this.ids.filter(i => i !== id);
                    // Conta o favorito nas métricas da loja (anônimo).
                    if (adding) {
                        try {
                            const data = new FormData();
                            data.append('type', 'favorite');
                            data.append('product', id);
                            fetch(@js(route('storefront.event', ['storeSlug' => $store->slug])), { method: 'POST', body: data, keepalive: true, credentials: 'same-origin' }).catch(() => {});
                        } catch (e) {}
                    }
                    try { localStorage.setItem(key, JSON.stringify(this.ids)); } catch (e) {}
                },
            });
        });
    </script>
</head>
<body x-data="{ menu: false }" :class="{ 'no-scroll': menu }">
    <a href="#conteudo" class="skip-link">Pular para o conteúdo</a>

    @if ($store->opening_hours)
        <div class="topbar">Pedidos pelo WhatsApp · {{ $store->opening_hours }}</div>
    @endif

    <header class="site-header">
        <div class="container header-row">
            <button type="button" class="icon-btn only-mobile" aria-label="Abrir menu" @click="menu = true">
                <x-storefront.icon name="menu" />
            </button>

            <a href="{{ route('storefront.home', $store->slug) }}" class="brand">
                @if ($logo)
                    <img src="{{ $logo }}" alt="{{ $store->name }}" class="brand-logo">
                @else
                    <span class="brand-name">{{ $store->name }}</span>
                @endif
            </a>

            <form action="{{ route('storefront.search', $store->slug) }}" method="get" class="header-search only-desktop" role="search">
                <label class="sr-only" for="busca-topo">Buscar produto</label>
                <x-storefront.icon name="search" />
                <input id="busca-topo" type="search" name="q" value="{{ request('q') }}" placeholder="Buscar peças, cores, tamanhos…">
            </form>

            <nav class="header-nav only-desktop" aria-label="Categorias">
                <a href="{{ route('storefront.products', $store->slug) }}">Novidades</a>
                @foreach ($categories->take(5) as $cat)
                    <a href="{{ route('storefront.category', [$store->slug, $cat->slug]) }}"
                       @if (request()->route('categorySlug') === $cat->slug) aria-current="page" @endif>{{ $cat->name }}</a>
                @endforeach
            </nav>

            <div class="header-actions">
                <a href="{{ route('storefront.favorites', $store->slug) }}" class="icon-btn outline only-desktop" aria-label="Favoritos">
                    <x-storefront.icon name="heart" />
                </a>
                <a href="{{ $store->whatsappUrl() }}" class="icon-btn outline only-desktop" aria-label="Fale com a loja pelo WhatsApp" target="_blank" rel="noopener">
                    <x-storefront.icon name="whatsapp" />
                </a>
                <livewire:storefront.cart-button :store-id="$store->id" />
            </div>
        </div>
    </header>

    {{-- Menu mobile --}}
    <div class="drawer-backdrop" x-show="menu" x-transition.opacity @click="menu = false" x-cloak></div>
    <nav class="menu-drawer" x-show="menu" x-transition:enter="slide-in-left" x-transition:leave="slide-out-left" x-cloak
         aria-label="Menu da loja" @keydown.escape.window="menu = false">
        <div class="menu-head">
            <span class="brand-name">{{ $store->name }}</span>
            <button type="button" class="icon-btn outline" aria-label="Fechar menu" @click="menu = false"><x-storefront.icon name="close" /></button>
        </div>
        <form action="{{ route('storefront.search', $store->slug) }}" method="get" class="field-search" role="search">
            <label class="sr-only" for="busca-menu">Buscar produto</label>
            <x-storefront.icon name="search" />
            <input id="busca-menu" type="search" name="q" placeholder="Buscar peças…">
        </form>
        <div class="menu-body">
            <span class="eyebrow">Categorias</span>
            <a class="menu-link" href="{{ route('storefront.products', $store->slug) }}">Todos os produtos</a>
            @foreach ($categories as $cat)
                <a class="menu-link" href="{{ route('storefront.category', [$store->slug, $cat->slug]) }}">
                    {{ $cat->name }} <span class="muted">{{ $cat->products_count }}</span>
                </a>
            @endforeach
            <span class="eyebrow mt">A loja</span>
            <a class="menu-link small" href="{{ route('storefront.favorites', $store->slug) }}"><x-storefront.icon name="heart" /> Favoritos</a>
            <a class="menu-link small" href="{{ route('storefront.about', $store->slug) }}"><x-storefront.icon name="info" /> Sobre a loja e trocas</a>
        </div>
        <div class="menu-foot">
            <a href="{{ $store->whatsappUrl() }}" class="btn btn-whatsapp btn-block" target="_blank" rel="noopener"><x-storefront.icon name="whatsapp" /> Fale com a loja</a>
            @if ($store->instagram)
                <a class="center small" href="https://instagram.com/{{ ltrim($store->instagram, '@') }}" target="_blank" rel="noopener">{{ '@'.ltrim($store->instagram, '@') }} no Instagram</a>
            @endif
        </div>
    </nav>

    <main id="conteudo">
        {{ $slot }}
    </main>

    <footer class="site-footer">
        <div class="container footer-row">
            <div class="footer-info">
                <strong>{{ $store->name }}</strong>
                @if ($store->address || $store->city)
                    <span>{{ collect([$store->address, $store->city ? $store->city.($store->state ? ' — '.$store->state : '') : null])->filter()->implode(', ') }}</span>
                @endif
                <span>WhatsApp {{ \App\Support\Format::phone($store->whatsapp) }}@if ($store->instagram) · {{ '@'.ltrim($store->instagram, '@') }}@endif</span>
            </div>
            <nav class="footer-nav" aria-label="Rodapé">
                <a href="{{ route('storefront.products', $store->slug) }}">Produtos</a>
                <a href="{{ route('storefront.favorites', $store->slug) }}">Favoritos</a>
                <a href="{{ route('storefront.about', $store->slug) }}">Sobre a loja</a>
                <a href="{{ route('storefront.about', $store->slug) }}#trocas">Trocas</a>
            </nav>
        </div>
        <div class="container footer-bottom">
            <span>© {{ now()->year }} {{ $store->name }}</span>
            <span>Catálogo criado com <strong>Provador</strong></span>
        </div>
    </footer>

    <a href="{{ $store->whatsappUrl() }}" class="fab-whatsapp only-mobile" aria-label="Fale com a loja pelo WhatsApp" target="_blank" rel="noopener">
        <x-storefront.icon name="whatsapp" />
    </a>

    @unless (request()->routeIs('storefront.cart'))
        <livewire:storefront.cart-drawer :store-id="$store->id" />
    @endunless
</body>
</html>
