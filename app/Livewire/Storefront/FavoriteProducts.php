<?php

namespace App\Livewire\Storefront;

use App\Livewire\Storefront\Concerns\InteractsWithStore;
use App\Models\Product;
use Livewire\Component;

/**
 * Favoritos: os ids ficam no navegador; aqui só buscamos os produtos.
 */
class FavoriteProducts extends Component
{
    use InteractsWithStore;

    /** @var array<int, int> */
    public array $ids = [];

    public bool $loaded = false;

    public function setIds(array $ids): void
    {
        $this->ids = array_values(array_unique(array_map('intval', array_slice($ids, 0, 100))));
        $this->loaded = true;
    }

    public function render()
    {
        $products = $this->ids === []
            ? collect()
            : Product::query()
                ->where('store_id', $this->storeId)
                ->where('is_visible', true)
                ->whereIn('id', $this->ids)
                ->with(['variants', 'category'])
                ->get();

        return view('storefront.livewire.favorite-products', [
            'store' => $this->store(),
            'products' => $products,
        ]);
    }
}
