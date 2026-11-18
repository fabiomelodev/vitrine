<x-storefront.layout title="Página não encontrada">
    <div class="state-page container">
        <span class="big">404</span>
        <h1 style="font-size: 30px">Esta peça <span class="serif">não está mais aqui</span></h1>
        <p class="muted" style="max-width: 420px; margin: 0">Ela pode ter saído do catálogo ou o link mudou. Que tal procurar algo parecido?</p>
        <form action="{{ route('storefront.search', $store->slug) }}" method="get" class="field-search" style="width: 100%; max-width: 380px" role="search">
            <label class="sr-only" for="busca-404">Buscar na loja</label>
            <x-storefront.icon name="search" />
            <input id="busca-404" type="search" name="q" placeholder="Buscar na loja">
        </form>
        <div style="display: flex; gap: 8px; flex-wrap: wrap; justify-content: center">
            <a href="{{ route('storefront.home', $store->slug) }}" class="btn btn-dark">Ir para a vitrine</a>
            @foreach ($categories->take(2) as $cat)
                <a href="{{ route('storefront.category', [$store->slug, $cat->slug]) }}" class="chip">{{ $cat->name }}</a>
            @endforeach
        </div>
    </div>
</x-storefront.layout>
