<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductVariant;

class CartLine
{
    public function __construct(
        public string $key,
        public Product $product,
        public ?ProductVariant $variant,
        public int $qty,
    ) {}

    public function isAvailable(): bool
    {
        if (! $this->product->is_visible) {
            return false;
        }

        if ($this->variant) {
            return $this->variant->is_available;
        }

        // Produto sem variações cadastradas: disponível se não houver variações.
        return $this->product->variants->isEmpty();
    }

    public function unavailableReason(): ?string
    {
        if ($this->isAvailable()) {
            return null;
        }

        if (! $this->product->is_visible) {
            return 'Esta peça saiu do catálogo.';
        }

        return 'Esta opção esgotou. Escolha outra ou remova.';
    }

    public function unitPrice(): float
    {
        return (float) $this->product->price;
    }

    public function total(): float
    {
        return $this->unitPrice() * $this->qty;
    }

    public function variantLabel(): string
    {
        return $this->variant?->label() ?? '';
    }
}
