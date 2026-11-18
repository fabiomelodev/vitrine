<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Support\Collection;

/**
 * Sacola do cliente, guardada na sessão (sem login), separada por loja.
 *
 * Cada linha: ['product_id' => int, 'variant_id' => ?int, 'qty' => int]
 */
class Cart
{
    public const MAX_QTY = 10;

    public function __construct(protected Store $store) {}

    public static function for(Store $store): self
    {
        return new self($store);
    }

    protected function key(): string
    {
        return 'cart.'.$this->store->getKey();
    }

    /**
     * @return array<string, array{product_id: int, variant_id: ?int, qty: int}>
     */
    public function raw(): array
    {
        return session($this->key(), []);
    }

    public static function lineKey(int $productId, ?int $variantId): string
    {
        return $productId.'-'.($variantId ?? 0);
    }

    public function add(int $productId, ?int $variantId, int $qty = 1): void
    {
        $lines = $this->raw();
        $key = static::lineKey($productId, $variantId);
        $current = $lines[$key]['qty'] ?? 0;

        $lines[$key] = [
            'product_id' => $productId,
            'variant_id' => $variantId,
            'qty' => min(static::MAX_QTY, $current + max(1, $qty)),
        ];

        session([$this->key() => $lines]);
    }

    public function setQty(string $key, int $qty): void
    {
        $lines = $this->raw();

        if (! isset($lines[$key])) {
            return;
        }

        $lines[$key]['qty'] = max(1, min(static::MAX_QTY, $qty));
        session([$this->key() => $lines]);
    }

    public function remove(string $key): void
    {
        $lines = $this->raw();
        unset($lines[$key]);
        session([$this->key() => $lines]);
    }

    public function clear(): void
    {
        session()->forget($this->key());
    }

    public function count(): int
    {
        return (int) collect($this->raw())->sum('qty');
    }

    /**
     * Linhas com os dados atuais do banco (preço, nome, disponibilidade).
     *
     * @return Collection<int, CartLine>
     */
    public function lines(): Collection
    {
        $raw = $this->raw();

        if ($raw === []) {
            return collect();
        }

        $products = Product::query()
            ->where('store_id', $this->store->getKey())
            ->whereIn('id', collect($raw)->pluck('product_id'))
            ->with('variants')
            ->get()
            ->keyBy('id');

        return collect($raw)
            ->map(function (array $line, string $key) use ($products): ?CartLine {
                $product = $products->get($line['product_id']);

                if (! $product) {
                    return null;
                }

                $variant = $line['variant_id']
                    ? $product->variants->firstWhere('id', $line['variant_id'])
                    : null;

                return new CartLine($key, $product, $variant, (int) $line['qty']);
            })
            ->filter()
            ->values();
    }
}
