<?php

namespace App\Livewire\Storefront;

use App\Livewire\Storefront\Concerns\InteractsWithStore;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StorefrontEvent;
use App\Support\Cart;
use App\Support\Format;
use App\Support\StorefrontTracker;
use Illuminate\Support\Collection;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Escolha de cor, tamanho e quantidade na página do produto.
 */
class ProductOptions extends Component
{
    use InteractsWithStore;

    #[Locked]
    public int $productId;

    public ?string $color = null;

    public ?string $size = null;

    public int $qty = 1;

    public ?string $error = null;

    public bool $added = false;

    /** Tamanho esgotado que o cliente tocou (mostra o "Avise-me"). */
    public ?string $soldOutSize = null;

    protected ?Product $productModel = null;

    public function mount(): void
    {
        $colors = $this->colors();

        // Já deixa selecionada a primeira cor com estoque.
        $this->color = $colors->firstWhere('available', true)['name'] ?? null;
    }

    protected function product(): Product
    {
        return $this->productModel ??= Product::query()
            ->where('store_id', $this->storeId)
            ->where('is_visible', true)
            ->with('variants')
            ->findOrFail($this->productId);
    }

    /**
     * @return Collection<int, array{name: string, hex: ?string, available: bool}>
     */
    protected function colors(): Collection
    {
        return $this->product()->variants
            ->filter(fn (ProductVariant $v) => filled($v->color))
            ->groupBy('color')
            ->map(fn (Collection $group, string $name) => [
                'name' => $name,
                'hex' => $group->first()->color_hex,
                'available' => $group->contains('is_available', true),
            ])
            ->values();
    }

    /**
     * @return Collection<int, array{name: string, available: bool}>
     */
    protected function sizes(): Collection
    {
        $variants = $this->product()->variants->filter(fn (ProductVariant $v) => filled($v->size));

        if ($this->colors()->isNotEmpty() && $this->color) {
            $variants = $variants->where('color', $this->color);
        }

        $bySize = $variants->groupBy('size');

        return collect(Format::sortSizes($bySize->keys()))
            ->map(fn (string $size) => [
                'name' => $size,
                'available' => $bySize[$size]->contains('is_available', true),
            ]);
    }

    public function selectColor(string $color): void
    {
        $this->color = $color;

        // A galeria (Alpine) escuta este evento e troca para as fotos da cor.
        $this->dispatch('color-selected', color: $color);
        $this->error = null;
        $this->added = false;
        $this->soldOutSize = null;

        // Se o tamanho escolhido não existe/esgotou nesta cor, limpa.
        $size = $this->sizes()->firstWhere('name', $this->size);
        if (! $size || ! $size['available']) {
            $this->size = null;
        }
    }

    public function selectSize(string $size): void
    {
        $option = $this->sizes()->firstWhere('name', $size);

        if ($option && $option['available']) {
            $this->size = $size;
            $this->error = null;
            $this->added = false;
            $this->soldOutSize = null;

            return;
        }

        // Tamanho esgotado: conta a procura e oferece o "Avise-me quando voltar".
        if ($option) {
            $this->soldOutSize = $size;
            $this->error = null;

            StorefrontTracker::recordOnce(
                $this->store(),
                StorefrontEvent::SOLDOUT_TAP,
                $this->productId,
                collect([$this->color, $size])->filter()->implode(' / '),
            );
        }
    }

    public function increment(): void
    {
        $this->qty = min(Cart::MAX_QTY, $this->qty + 1);
    }

    public function decrement(): void
    {
        $this->qty = max(1, $this->qty - 1);
    }

    /**
     * Encontra a variação escolhida. Retorna false quando falta escolher algo.
     */
    protected function resolveVariant(): ProductVariant|false|null
    {
        $variants = $this->product()->variants;

        if ($variants->isEmpty()) {
            return null;
        }

        $needsColor = $variants->contains(fn ($v) => filled($v->color));
        $needsSize = $variants->contains(fn ($v) => filled($v->size));

        if ($needsColor && ! $this->color) {
            $this->error = 'Escolha uma cor para continuar.';

            return false;
        }

        if ($needsSize && ! $this->size) {
            $this->error = 'Escolha um tamanho para continuar.';

            return false;
        }

        $variant = $variants->first(fn (ProductVariant $v) => ($needsColor ? $v->color === $this->color : true)
            && ($needsSize ? $v->size === $this->size : true));

        if (! $variant || ! $variant->is_available) {
            $this->error = 'Essa combinação esgotou. Escolha outra opção.';

            return false;
        }

        return $variant;
    }

    public function addToCart(): void
    {
        $variant = $this->resolveVariant();

        if ($variant === false) {
            return;
        }

        $this->cart()->add($this->productId, $variant?->getKey(), $this->qty);
        $this->error = null;
        $this->added = true;

        StorefrontTracker::record($this->store(), StorefrontEvent::ADD_TO_CART, $this->productId, null, $this->qty);

        $this->dispatch('cart-updated');
    }

    public function buyNow(): void
    {
        $variant = $this->resolveVariant();

        if ($variant === false) {
            return;
        }

        $this->error = null;

        StorefrontTracker::record($this->store(), StorefrontEvent::ADD_TO_CART, $this->productId, 'pedido_direto', $this->qty);

        $this->dispatch('checkout-direct', productId: $this->productId, variantId: $variant?->getKey(), qty: $this->qty);
    }

    public function render()
    {
        $product = $this->product();
        $variants = $product->variants;

        return view('storefront.livewire.product-options', [
            'product' => $product,
            'colors' => $this->colors(),
            'sizes' => $this->sizes(),
            'soldOut' => $variants->isNotEmpty() && ! $variants->contains('is_available', true),
            'cartCount' => $this->cart()->count(),
            'notifyUrl' => route('storefront.restock', ['storeSlug' => $this->store()->slug, 'productSlug' => $product->slug]),
            'sizeNotifyUrl' => $this->soldOutSize ? route('storefront.restock', array_filter([
                'storeSlug' => $this->store()->slug,
                'productSlug' => $product->slug,
                'cor' => $this->color,
                'tam' => $this->soldOutSize,
            ])) : null,
        ]);
    }
}
