@php use App\Support\Format; @endphp
<x-storefront.layout :title="$product->name" :description="\Illuminate\Support\Str::limit($product->description, 150)">
    <div class="container">
        <nav class="crumbs" aria-label="Você está em" style="padding: 24px 0">
            <a href="{{ route('storefront.home', $store->slug) }}">Início</a><span aria-hidden="true">/</span>
            @if ($product->category)
                <a href="{{ route('storefront.category', [$store->slug, $product->category->slug]) }}">{{ $product->category->name }}</a><span aria-hidden="true">/</span>
            @endif
            <span>{{ $product->name }}</span>
        </nav>

        <div class="product">
            <div class="gallery"
                 x-data="{
                     gallery: @js($gallery),
                     color: @js($initialColor),
                     current: 0,
                     get images() { return (this.color && this.gallery.byColor[this.color]) || this.gallery.general; },
                     setColor(c) { if (c === this.color) return; this.color = c; this.current = 0; },
                 }"
                 @color-selected.window="setColor($event.detail.color)">
                <div class="thumbs" x-show="images.length > 1">
                    <template x-for="(img, i) in images" :key="img.full">
                        <button type="button" class="thumb" @click="current = i" :aria-current="(current === i).toString()" :aria-label="'Ver foto ' + (i + 1)">
                            <img :src="img.thumb" alt="" loading="lazy">
                        </button>
                    </template>
                </div>
                <div class="main-photo">
                    <template x-for="(img, i) in images" :key="img.full">
                        <img :src="img.full" :alt="@js($product->name) + (color ? ' — ' + color : '') + ' — foto ' + (i + 1)" x-show="current === i">
                    </template>
                    <span class="placeholder-img" x-show="images.length === 0" @if ($gallery['general'] !== []) x-cloak @endif>Sem foto</span>
                    <button type="button" class="fav-btn" :class="{ on: $store.favs.has({{ $product->id }}) }"
                            :aria-pressed="$store.favs.has({{ $product->id }}).toString()" aria-label="Favoritar"
                            @click="$store.favs.toggle({{ $product->id }})"><x-storefront.icon name="heart" :size="18" /></button>
                    <div class="only-mobile photo-dots">
                        <template x-for="(img, i) in (images.length > 1 ? images : [])" :key="'dot-' + img.full">
                            <button type="button" class="dot-btn" style="width: 24px; height: 28px" @click="current = i" :aria-label="'Ver foto ' + (i + 1)"><span class="dot" :class="{ on: current === i }"></span></button>
                        </template>
                    </div>
                </div>
            </div>

            <div class="buy">
                <div class="stack" style="gap: 10px">
                    <span class="eyebrow">{{ $product->category?->name }}@if ($product->sku) · Ref. {{ $product->sku }}@endif</span>
                    <h1>{{ $product->name }}</h1>
                    <div class="price-lg">
                        <strong>{{ Format::money($product->price) }}</strong>
                        @if ($product->isOnSale())
                            <s class="muted">{{ Format::money($product->compare_at_price) }}</s>
                        @endif
                    </div>
                </div>

                <livewire:storefront.product-options :store-id="$store->id" :product-id="$product->id" />

                <div class="details">
                    @if ($product->description)
                        <details open><summary>Descrição</summary><p>{{ $product->description }}</p></details>
                    @endif
                    @if ($store->exchange_policy)
                        <details><summary>Trocas e devoluções</summary><p>{{ \Illuminate\Support\Str::limit($store->exchange_policy, 400) }}</p></details>
                    @endif
                    @if ($store->delivery_info)
                        <details><summary>Entrega</summary><p>{{ $store->delivery_info }}</p></details>
                    @endif
                </div>
            </div>
        </div>

        @if ($related->isNotEmpty())
            <section class="section" style="padding-top: 24px">
                <h2 class="section-title" style="margin-bottom: 24px">Complete <span class="serif">o look</span></h2>
                <div class="grid-products">
                    @foreach ($related as $item)
                        @include('storefront.partials.product-card', ['product' => $item, 'storeSlug' => $store->slug])
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-storefront.layout>
