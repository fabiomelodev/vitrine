<div x-data x-init="$wire.setIds($store.favs.ids); $watch('$store.favs.ids', ids => $wire.setIds(ids))">
    @if (! $loaded)
        <p class="muted">Carregando seus favoritos…</p>
    @elseif ($products->isEmpty())
        <div class="empty-box">
            <x-storefront.icon name="heart" :size="40" style="color: var(--accent)" />
            <strong>Nenhum favorito ainda</strong>
            <span class="muted">Toque no coração das peças que gostar para guardar aqui.</span>
            <a href="{{ route('storefront.home', $store->slug) }}" class="btn">Ver a vitrine</a>
        </div>
    @else
        <div class="toolbar">
            <span class="small muted">{{ $products->count() }} {{ $products->count() === 1 ? 'peça' : 'peças' }} · salvos neste navegador, sem precisar de conta</span>
            <a href="{{ $store->whatsappUrl("Olá! Tenho dúvidas sobre estas peças:\n".$products->map(fn ($p) => '• '.$p->name)->implode("\n")) }}"
               class="btn btn-whatsapp-outline" target="_blank" rel="noopener"><x-storefront.icon name="whatsapp" /> Perguntar à loja sobre estas peças</a>
        </div>
        <div class="grid-products">
            @foreach ($products as $product)
                <div wire:key="fav-{{ $product->id }}">
                    @include('storefront.partials.product-card', ['product' => $product, 'storeSlug' => $store->slug])
                </div>
            @endforeach
        </div>
    @endif
</div>
