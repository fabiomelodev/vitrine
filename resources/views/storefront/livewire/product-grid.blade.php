@php use App\Livewire\Storefront\ProductGrid; @endphp
<div x-data="{ filtersOpen: false }">
    @if ($searchMode)
        <form class="field-search" style="max-width: 640px; height: 52px; border: 1.5px solid var(--ink)" role="search" wire:submit="$refresh">
            <label class="sr-only" for="busca">Buscar na loja</label>
            <x-storefront.icon name="search" />
            <input id="busca" type="search" wire:model.live.debounce.400ms="q" placeholder="Buscar peças, cores, tamanhos…" autofocus style="font-size: 16px">
        </form>
    @endif

    <div class="listing">
        <aside class="filters" :class="{ open: filtersOpen }" aria-label="Filtros">
            <div class="row-between only-mobile" style="width: 100%">
                <strong style="font-size: 18px">Filtrar</strong>
                <button type="button" class="link-btn" wire:click="clearFilters">Limpar</button>
            </div>

            @if ($sizeOptions)
                <fieldset>
                    <legend>Tamanho</legend>
                    <div class="size-grid">
                        @foreach ($sizeOptions as $opt)
                            <button type="button" class="size-btn" wire:click="toggleSize(@js($opt))" aria-pressed="{{ in_array($opt, $sizes, true) ? 'true' : 'false' }}">{{ $opt }}</button>
                        @endforeach
                    </div>
                </fieldset>
            @endif

            @if ($colorOptions->isNotEmpty())
                <fieldset>
                    <legend>Cor</legend>
                    @foreach ($colorOptions as $opt)
                        <button type="button" class="color-opt" wire:click="toggleColor(@js($opt->color))" aria-pressed="{{ in_array($opt->color, $colors, true) ? 'true' : 'false' }}">
                            <span class="swatch" style="background: {{ $opt->color_hex ?: '#ddd' }}"></span>{{ $opt->color }}
                        </button>
                    @endforeach
                </fieldset>
            @endif

            <fieldset>
                <legend>Preço</legend>
                <div class="size-grid">
                    @foreach (ProductGrid::PRICE_RANGES as $key => [$label])
                        <button type="button" class="chip" wire:click="setPrice('{{ $key }}')" aria-pressed="{{ $price === $key ? 'true' : 'false' }}">{{ $label }}</button>
                    @endforeach
                </div>
            </fieldset>

            <label class="switch-row">Mostrar peças esgotadas <input type="checkbox" wire:model.live="includeSoldOut"></label>

            <button type="button" class="btn btn-dark btn-block only-mobile" @click="filtersOpen = false">Ver {{ $total }} {{ $total === 1 ? 'peça' : 'peças' }}</button>
        </aside>
        <div class="drawer-backdrop" x-show="filtersOpen" x-cloak @click="filtersOpen = false"></div>

        <div class="listing-main">
            <div class="toolbar">
                <span class="small muted" aria-live="polite">
                    @if ($searchMode && trim($q) !== '')
                        {{ $total }} {{ $total === 1 ? 'resultado' : 'resultados' }} para “{{ $q }}”
                    @else
                        {{ $total }} {{ $total === 1 ? 'peça' : 'peças' }}
                    @endif
                </span>
                <div style="display: flex; gap: 8px; flex-wrap: wrap">
                    <button type="button" class="btn filters-toggle" style="min-height: 44px" @click="filtersOpen = true">
                        <x-storefront.icon name="filter" :size="16" /> Filtrar{{ $activeCount ? ' ('.$activeCount.')' : '' }}
                    </button>
                    <label class="sr-only" for="ordem">Ordenar por</label>
                    <select id="ordem" class="select" wire:model.live="sort">
                        <option value="recentes">Mais recentes</option>
                        <option value="menor-preco">Menor preço</option>
                        <option value="maior-preco">Maior preço</option>
                    </select>
                </div>
            </div>

            @if ($activeCount)
                <div class="active-filters">
                    @foreach ($sizes as $s)
                        <button type="button" class="chip active" wire:click="toggleSize(@js($s))" aria-label="Remover filtro tamanho {{ $s }}">Tamanho {{ $s }} ×</button>
                    @endforeach
                    @foreach ($colors as $c)
                        <button type="button" class="chip active" wire:click="toggleColor(@js($c))" aria-label="Remover filtro cor {{ $c }}">{{ $c }} ×</button>
                    @endforeach
                    @if ($price !== '' && isset(ProductGrid::PRICE_RANGES[$price]))
                        <button type="button" class="chip active" wire:click="setPrice('{{ $price }}')">{{ ProductGrid::PRICE_RANGES[$price][0] }} ×</button>
                    @endif
                    <button type="button" class="link-btn" wire:click="clearFilters">Limpar filtros</button>
                </div>
            @endif

            @if ($products->isEmpty())
                <div class="empty-box">
                    @if ($searchMode && trim($q) === '')
                        <strong>O que você procura?</strong>
                        <span class="muted">Digite o nome da peça, uma cor ou um tamanho.</span>
                    @elseif ($searchMode)
                        <strong>Nada encontrado para “{{ $q }}”</strong>
                        <span class="muted">Confira a grafia ou tente palavras mais gerais. Se procura algo específico, a loja pode ajudar.</span>
                        <a href="{{ $store->whatsappUrl('Olá! Estou procurando: '.$q) }}" class="btn btn-whatsapp-outline" target="_blank" rel="noopener"><x-storefront.icon name="whatsapp" /> Perguntar à loja</a>
                    @else
                        <strong>Nenhuma peça com esses filtros</strong>
                        <span class="muted">Tente outro tamanho ou cor — ou pergunte à loja se chega reposição.</span>
                    @endif
                    @if ($activeCount)
                        <button type="button" class="btn" wire:click="clearFilters">Limpar filtros</button>
                    @endif
                </div>
            @else
                <div class="grid-products">
                    @foreach ($products as $product)
                        <div wire:key="p-{{ $product->id }}">
                            @include('storefront.partials.product-card', ['product' => $product, 'storeSlug' => $store->slug, 'preferColors' => $colors])
                        </div>
                    @endforeach
                </div>

                @if ($products->count() < $total)
                    <div class="stack" style="align-items: center; margin-top: 48px">
                        <span class="small muted">Você viu {{ $products->count() }} de {{ $total }}</span>
                        <button type="button" class="btn" wire:click="loadMore" wire:loading.attr="disabled">Carregar mais</button>
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
