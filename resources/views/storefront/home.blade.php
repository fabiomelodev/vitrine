@php use App\Support\Format; @endphp
<x-storefront.layout>
    <section class="hero container">
        <h1 class="hero-title">{{ $store->name }}<br><span class="serif">vitrine</span> online</h1>
        <p class="hero-sub">{{ $store->description ?: 'Escolha a peça, o tamanho e a cor — a gente finaliza seu pedido com você pelo WhatsApp.' }}</p>

        @if ($banners->isNotEmpty())
            <div class="banner-carousel" role="region" aria-roledescription="carrossel" aria-label="Destaques da loja"
                 x-data="{
                     current: 0,
                     total: {{ $banners->count() }},
                     paused: false,
                     timer: null,
                     go(i) { this.current = (i + this.total) % this.total; },
                     start() { if (this.total > 1) this.timer = setInterval(() => { if (! this.paused) this.go(this.current + 1) }, 6000); },
                 }"
                 x-init="start()"
                 @mouseenter="paused = true" @mouseleave="paused = false"
                 @focusin="paused = true" @focusout="paused = false"
                 @keydown.arrow-left="go(current - 1)" @keydown.arrow-right="go(current + 1)">
                <div @class(['banner-viewport', 'has-mobile' => $banners->every(fn ($b) => filled($b->image_mobile_path))])>
                    @foreach ($banners as $i => $banner)
                        @php
                            $href = $banner->href();
                            $desktop = \App\Support\Format::image($banner->image_path);
                            $mobile = \App\Support\Format::image($banner->image_mobile_path);
                        @endphp
                        <div class="banner-slide" role="group" aria-roledescription="slide" aria-label="{{ $i + 1 }} de {{ $banners->count() }}"
                             x-show="current === {{ $i }}" x-transition.opacity.duration.400ms @if ($i > 0) x-cloak @endif>
                            @if ($href)
                                <a href="{{ $href }}" @if ($banner->open_in_new_tab) target="_blank" rel="noopener" @endif>
                            @endif
                            <picture>
                                @if ($mobile)
                                    <source media="(max-width: 760px)" srcset="{{ $mobile }}">
                                @endif
                                <img src="{{ $desktop }}" alt="{{ $banner->title ?? '' }}" @if ($i > 0) loading="lazy" @endif>
                            </picture>
                            @if ($href)
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>

                @if ($banners->count() > 1)
                    <button type="button" class="banner-arrow prev" aria-label="Banner anterior" @click="go(current - 1)"><x-storefront.icon name="chevron-left" :size="18" /></button>
                    <button type="button" class="banner-arrow next" aria-label="Próximo banner" @click="go(current + 1)"><x-storefront.icon name="chevron-right" :size="18" /></button>
                    <div class="banner-dots" aria-label="Paginação do carrossel">
                        @foreach ($banners as $i => $banner)
                            <button type="button" class="dot-btn" @click="go({{ $i }})" aria-label="Ir para o banner {{ $i + 1 }}" :aria-current="(current === {{ $i }}).toString()">
                                <span class="dot" :class="{ on: current === {{ $i }} }"></span>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif
    </section>

    @if ($categories->isNotEmpty())
        <section class="section section-alt" id="categorias">
            <div class="container cat-grid">
                <h2 class="section-title">Navegue por<br><span class="serif">categorias</span></h2>
                <div class="cat-list">
                    @foreach ($categories as $cat)
                        <a href="{{ route('storefront.category', [$store->slug, $cat->slug]) }}">{{ $cat->name }} <span class="pill-count">{{ $cat->products_count }}</span></a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($tabs->isNotEmpty())
        <section class="section container" id="produtos" x-data="{ tab: @js($tabs->keys()->first()) }">
            <div class="toolbar">
                <div class="tabs" role="tablist" aria-label="Filtrar vitrine">
                    @foreach ($tabs as $key => $tab)
                        <button type="button" class="tab" role="tab" :aria-selected="(tab === @js($key)).toString()" @click="tab = @js($key)">{{ $tab['label'] }}</button>
                    @endforeach
                </div>
                <a href="{{ route('storefront.products', $store->slug) }}" class="small">Ver todos os produtos</a>
            </div>
            @foreach ($tabs as $key => $tab)
                <div class="grid-products" role="tabpanel" x-show="tab === @js($key)" @if (! $loop->first) x-cloak @endif>
                    @foreach ($tab['products'] as $product)
                        @include('storefront.partials.product-card', ['product' => $product, 'storeSlug' => $store->slug])
                    @endforeach
                </div>
            @endforeach
        </section>
    @else
        <section class="section container">
            <div class="empty-box"><strong>Em breve, novidades por aqui.</strong><span class="muted">A loja ainda está cadastrando as peças.</span></div>
        </section>
    @endif

    <section class="section section-alt">
        <div class="container">
            <h2 class="section-title" style="margin-bottom: 28px">Como <span class="serif">comprar</span></h2>
            <ol class="steps">
                <li><span class="num">01</span><strong>Escolha a peça</strong><span class="muted small">Navegue pelo catálogo e abra o produto que gostou.</span></li>
                <li><span class="num">02</span><strong>Selecione tamanho e cor</strong><span class="muted small">E coloque na sacola quantas peças quiser.</span></li>
                <li><span class="num" style="color: var(--green)">03</span><strong>Peça pelo WhatsApp</strong><span class="muted small">A mensagem vai pronta. Pagamento e entrega são combinados com a loja.</span></li>
            </ol>
        </div>
    </section>
</x-storefront.layout>
