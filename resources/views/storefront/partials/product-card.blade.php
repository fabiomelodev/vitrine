@php
    /** @var \App\Models\Product $product */
    $preferColor = collect($preferColors ?? [])->first(fn ($c) => $product->hasOwnImagesForColor($c));
    $cover = \App\Support\Format::image($product->coverImage($preferColor), thumb: true);
    // Foto de cada cor (para trocar ao passar o mouse na bolinha).
    $colorThumbs = collect($product->color_images ?? [])
        ->map(fn ($paths) => \App\Support\Format::image(collect($paths)->filter()->first(), thumb: true))
        ->filter();
    $soldOut = $product->isSoldOut();
    $colorsList = $product->variants->whereNotNull('color')->unique('color')->take(4);
    $url = route('storefront.product', [$storeSlug, $product->slug]);
@endphp
<article class="p-card" x-data="{ src: @js($cover), base: @js($cover) }" @mouseleave="src = base">
    <div class="p-media {{ $soldOut ? 'soldout' : '' }}">
        <a href="{{ $url }}" tabindex="-1" aria-hidden="true">
            @if ($cover)
                <img src="{{ $cover }}" :src="src" alt="" loading="lazy">
            @else
                <span class="placeholder-img">Sem foto</span>
            @endif
        </a>
        @if ($soldOut)
            <span class="tag dark">Esgotado</span>
        @elseif ($product->isOnSale())
            <span class="tag">-{{ round((1 - $product->price / $product->compare_at_price) * 100) }}%</span>
        @elseif ($product->created_at?->gt(now()->subDays(15)))
            <span class="tag">Novo</span>
        @endif
        <button type="button" class="fav-btn" x-data
                :class="{ on: $store.favs.has({{ $product->id }}) }"
                :aria-pressed="$store.favs.has({{ $product->id }}).toString()"
                aria-label="Favoritar {{ $product->name }}"
                @click.prevent="$store.favs.toggle({{ $product->id }})">
            <x-storefront.icon name="heart" :size="18" />
        </button>
    </div>
    <div class="p-meta">
        <span>{{ $product->category?->name }}</span>
        @if ($colorsList->isNotEmpty())
            <span class="swatches">
                @foreach ($colorsList as $v)
                    <span class="swatch" style="background: {{ $v->color_hex ?: '#ddd' }}" title="{{ $v->color }}"
                          @if ($colorThumbs->has($v->color)) @mouseenter="src = @js($colorThumbs[$v->color])" @endif></span>
                @endforeach
            </span>
        @endif
    </div>
    <a href="{{ $url }}" class="p-name" style="text-decoration: none">{{ $product->name }}</a>
    <span class="p-price">
        <strong>{{ \App\Support\Format::money($product->price) }}</strong>
        @if ($product->isOnSale())
            <s>{{ \App\Support\Format::money($product->compare_at_price) }}</s>
        @endif
    </span>
</article>
