<?php

namespace App\Livewire\Storefront;

use App\Actions\PlaceOrder;
use App\Livewire\Storefront\Concerns\InteractsWithStore;
use App\Models\Product;
use App\Models\StorefrontEvent;
use App\Support\CartLine;
use App\Support\StorefrontTracker;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Sacola (gaveta lateral ou página /sacola) + formulário de nome/WhatsApp + envio do pedido.
 */
class CartDrawer extends Component
{
    use InteractsWithStore;

    /** Exibida como página (/sacola) em vez de gaveta. */
    public bool $inline = false;

    public bool $open = false;

    /** 'cart' ou 'form' (formulário de dados aberto). */
    public string $step = 'cart';

    public string $name = '';

    public string $phone = '';

    /** Pedido direto de uma peça só (botão "Pedir só esta peça"). */
    public ?array $direct = null;

    public ?int $picking = null;

    #[On('open-cart')]
    public function openCart(): void
    {
        $this->open = true;
        $this->step = 'cart';
    }

    #[On('cart-updated')]
    public function onCartUpdated(): void
    {
        $this->openCart();
    }

    public function close(): void
    {
        $this->open = false;
        $this->step = 'cart';
        $this->direct = null;
    }

    public function increment(string $key): void
    {
        $line = $this->cart()->raw()[$key] ?? null;

        if ($line) {
            $this->cart()->setQty($key, $line['qty'] + 1);
            $this->dispatch('cart-count-changed')->to(CartButton::class);
        }
    }

    public function decrement(string $key): void
    {
        $line = $this->cart()->raw()[$key] ?? null;

        if ($line) {
            $this->cart()->setQty($key, $line['qty'] - 1);
            $this->dispatch('cart-count-changed')->to(CartButton::class);
        }
    }

    public function remove(string $key): void
    {
        $this->cart()->remove($key);
        $this->dispatch('cart-count-changed')->to(CartButton::class);
    }

    public function pick(int $productId): void
    {
        $this->picking = $this->picking === $productId ? null : $productId;
    }

    public function addSuggestion(int $productId, ?int $variantId = null): void
    {
        $product = $this->store()->products()->where('is_visible', true)->with('variants')->find($productId);

        if (! $product) {
            return;
        }

        if ($product->variants->isNotEmpty()) {
            $variant = $product->variants->firstWhere('id', $variantId);

            if (! $variant || ! $variant->is_available) {
                return;
            }
        }

        $this->cart()->add($product->getKey(), $variantId, 1);
        $this->picking = null;

        // Peça adicionada pelo "Complete o look".
        StorefrontTracker::record($this->store(), StorefrontEvent::ADD_TO_CART, $product->getKey(), 'sugestao', 1);
        $this->dispatch('cart-count-changed')->to(CartButton::class);
    }

    public function startCheckout(): void
    {
        $this->direct = null;
        $this->resetErrorBag();
        $this->step = 'form';
    }

    #[On('checkout-direct')]
    public function checkoutDirect(int $productId, ?int $variantId = null, int $qty = 1): void
    {
        $this->direct = ['product_id' => $productId, 'variant_id' => $variantId, 'qty' => max(1, min(10, $qty))];
        $this->resetErrorBag();
        $this->open = true;
        $this->step = 'form';
    }

    public function backToCart(): void
    {
        $this->step = 'cart';

        if ($this->direct) {
            $this->close();
        }
    }

    public function updatedPhone(string $value): void
    {
        $this->phone = static::maskPhone($value);
    }

    public static function maskPhone(string $value): string
    {
        $d = substr(preg_replace('/\D/', '', $value), 0, 11);

        return match (true) {
            strlen($d) === 0 => '',
            strlen($d) <= 2 => '('.$d,
            strlen($d) <= 7 => '('.substr($d, 0, 2).') '.substr($d, 2),
            default => '('.substr($d, 0, 2).') '.substr($d, 2, 5).'-'.substr($d, 7),
        };
    }

    public function submit(PlaceOrder $placeOrder)
    {
        $this->phone = static::maskPhone($this->phone);

        $this->validate([
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'phone' => ['required', 'regex:/^\(\d{2}\) 9\d{4}-\d{4}$/'],
        ], [
            'name.required' => 'Informe seu nome.',
            'name.min' => 'Informe seu nome.',
            'phone.required' => 'Informe seu WhatsApp com DDD.',
            'phone.regex' => 'Informe um celular com DDD, ex.: (11) 91234-5678.',
        ]);

        $limiterKey = 'place-order:'.request()->ip();

        if (RateLimiter::tooManyAttempts($limiterKey, 6)) {
            throw ValidationException::withMessages([
                'name' => 'Muitos pedidos em sequência. Aguarde um minuto e tente de novo.',
            ]);
        }

        RateLimiter::hit($limiterKey, 60);

        $lines = $this->checkoutLines();

        if ($lines->filter(fn (CartLine $l) => $l->isAvailable())->isEmpty()) {
            throw ValidationException::withMessages([
                'name' => 'As peças escolhidas não estão mais disponíveis.',
            ]);
        }

        $order = $placeOrder->handle($this->store(), $lines, $this->name, $this->phone);

        StorefrontTracker::attachToOrder($this->store(), $order);

        if (! $this->direct) {
            $this->cart()->clear();
        }

        session()->put('order.just_placed', $order->public_token);

        return $this->redirectRoute('storefront.order', [
            'storeSlug' => $this->store()->slug,
            'token' => $order->public_token,
        ]);
    }

    /**
     * @return Collection<int, CartLine>
     */
    protected function checkoutLines(): Collection
    {
        if (! $this->direct) {
            return $this->cart()->lines();
        }

        $product = $this->store()->products()->with('variants')->find($this->direct['product_id']);

        if (! $product) {
            return collect();
        }

        $variant = $this->direct['variant_id']
            ? $product->variants->firstWhere('id', $this->direct['variant_id'])
            : null;

        return collect([new CartLine('direct', $product, $variant, (int) $this->direct['qty'])]);
    }

    /**
     * Sugestões "Combina com": peças relacionadas às da sacola que ainda não estão nela.
     *
     * @param  Collection<int, CartLine>  $lines
     * @return Collection<int, Product>
     */
    protected function suggestions(Collection $lines): Collection
    {
        if ($lines->isEmpty()) {
            return collect();
        }

        $inCart = $lines->pluck('product.id')->all();

        return Product::query()
            ->where('store_id', $this->storeId)
            ->where('is_visible', true)
            ->whereNotIn('id', $inCart)
            ->whereIn('id', function ($q) use ($inCart) {
                $q->select('related_product_id')->from('product_related')->whereIn('product_id', $inCart);
            })
            ->with('variants')
            ->limit(3)
            ->get();
    }

    public function render()
    {
        $lines = $this->checkoutLines();
        $available = $lines->filter(fn (CartLine $l) => $l->isAvailable());

        return view('storefront.livewire.cart-drawer', [
            'store' => $this->store(),
            'lines' => $lines,
            'count' => $available->sum('qty'),
            'total' => $available->sum(fn (CartLine $l) => $l->total()),
            'hasUnavailable' => $lines->count() !== $available->count(),
            'suggestions' => $this->direct ? collect() : $this->suggestions($lines),
        ]);
    }
}
