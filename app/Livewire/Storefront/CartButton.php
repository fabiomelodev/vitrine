<?php

namespace App\Livewire\Storefront;

use App\Livewire\Storefront\Concerns\InteractsWithStore;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Botão "Sacola (n)" do topo. Atualiza quando algo entra na sacola.
 */
class CartButton extends Component
{
    use InteractsWithStore;

    #[On('cart-updated')]
    public function onCartUpdated(): void
    {
        // Só re-renderiza com o novo total.
    }

    #[On('cart-count-changed')]
    public function onCountChanged(): void
    {
        // Só re-renderiza com o novo total.
    }

    public function render()
    {
        return view('storefront.livewire.cart-button', [
            'count' => $this->cart()->count(),
        ]);
    }
}
