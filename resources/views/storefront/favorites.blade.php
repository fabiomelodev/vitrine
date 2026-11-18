<x-storefront.layout title="Favoritos">
    <div class="container" style="padding-bottom: 80px">
        <div class="page-head" style="margin-bottom: 24px">
            <h1 class="page-title">Seus <span class="serif">favoritos</span></h1>
        </div>
        <livewire:storefront.favorite-products :store-id="$store->id" />
    </div>
</x-storefront.layout>
