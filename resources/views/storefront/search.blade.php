<x-storefront.layout title="Busca">
    <div class="container">
        <div class="page-head">
            <h1 class="page-title" style="font-size: clamp(32px, 4vw, 44px); margin-bottom: 20px">Buscar na <span class="serif">loja</span></h1>
        </div>
        <livewire:storefront.product-grid :store-id="$store->id" :search-mode="true" />
    </div>
</x-storefront.layout>
