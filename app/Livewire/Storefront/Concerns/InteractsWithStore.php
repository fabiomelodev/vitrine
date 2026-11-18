<?php

namespace App\Livewire\Storefront\Concerns;

use App\Models\Store;
use App\Support\Cart;
use Livewire\Attributes\Locked;

trait InteractsWithStore
{
    #[Locked]
    public int $storeId;

    protected ?Store $storeModel = null;

    protected function store(): Store
    {
        if (! $this->storeModel) {
            $store = Store::query()->where('is_active', true)->findOrFail($this->storeId);
            abort_unless($store->hasActiveAccess(), 503);
            $this->storeModel = $store;
        }

        return $this->storeModel;
    }

    protected function cart(): Cart
    {
        return Cart::for($this->store());
    }
}
