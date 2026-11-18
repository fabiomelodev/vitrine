<x-storefront.layout :title="$title">
    <div class="container">
        <div class="page-head">
            <nav class="crumbs" aria-label="Você está em">
                <a href="{{ route('storefront.home', $store->slug) }}">Início</a><span aria-hidden="true">/</span><span>{{ $title }}</span>
            </nav>
            <h1 class="page-title"><span class="serif">{{ $title }}</span></h1>
        </div>
        <livewire:storefront.product-grid :store-id="$store->id" :category-id="$category?->id" />
    </div>
</x-storefront.layout>
