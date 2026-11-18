<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Store;
use App\Support\Format;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\View;

class StorefrontController extends Controller
{
    protected Store $store;

    /**
     * Carrega a loja pelo endereço. Loja inexistente: 404. Loja suspensa: página própria.
     */
    protected function resolveStore(string $slug): ?Response
    {
        $store = Store::query()->where('slug', $slug)->first();

        abort_if(! $store, 404);

        $this->store = $store;

        View::share('store', $store);
        View::share('categories', $store->categories()->where('is_visible', true)->withCount([
            'products' => fn (Builder $q) => $q->where('is_visible', true),
        ])->get());

        if (! $store->is_active || ! $store->hasActiveAccess()) {
            return response()->view('storefront.unavailable', [], 503);
        }

        return null;
    }

    protected function notFound(): Response
    {
        return response()->view('storefront.not-found', [], 404);
    }

    protected function visibleProducts(): Builder
    {
        return $this->store->products()->getQuery()->where('is_visible', true);
    }

    public function home(string $storeSlug)
    {
        if ($blocked = $this->resolveStore($storeSlug)) {
            return $blocked;
        }

        $base = fn () => $this->visibleProducts()->with(['variants', 'category']);

        $featured = $base()->where('is_featured', true)->orderBy('position')->limit(8)->get();
        $latest = $base()->latest()->limit(8)->get();
        $onSale = $base()->whereNotNull('compare_at_price')->whereColumn('compare_at_price', '>', 'price')->limit(8)->get();

        // Banners do carrossel do topo (cadastrados no painel do lojista).
        $banners = $this->store->banners()
            ->where('is_active', true)
            ->with(['linkCategory', 'linkProduct'])
            ->get()
            ->each(fn ($banner) => $banner->setRelation('store', $this->store));

        return view('storefront.home', [
            'tabs' => collect([
                'novidades' => ['label' => 'Novidades', 'products' => $latest],
                'destaques' => ['label' => 'Destaques', 'products' => $featured],
                'promocoes' => ['label' => 'Promoções', 'products' => $onSale],
            ])->filter(fn ($tab) => $tab['products']->isNotEmpty()),
            'banners' => $banners,
        ]);
    }

    public function products(string $storeSlug)
    {
        if ($blocked = $this->resolveStore($storeSlug)) {
            return $blocked;
        }

        return view('storefront.listing', [
            'title' => 'Todos os produtos',
            'category' => null,
        ]);
    }

    public function category(string $storeSlug, string $categorySlug)
    {
        if ($blocked = $this->resolveStore($storeSlug)) {
            return $blocked;
        }

        $category = $this->store->categories()
            ->where('slug', $categorySlug)
            ->where('is_visible', true)
            ->first();

        if (! $category) {
            return $this->notFound();
        }

        return view('storefront.listing', [
            'title' => $category->name,
            'category' => $category,
        ]);
    }

    public function search(Request $request, string $storeSlug)
    {
        if ($blocked = $this->resolveStore($storeSlug)) {
            return $blocked;
        }

        return view('storefront.search', [
            'q' => trim((string) $request->query('q', '')),
        ]);
    }

    public function product(string $storeSlug, string $productSlug)
    {
        if ($blocked = $this->resolveStore($storeSlug)) {
            return $blocked;
        }

        /** @var Product|null $product */
        $product = $this->visibleProducts()
            ->where('slug', $productSlug)
            ->with(['variants', 'category'])
            ->first();

        if (! $product) {
            return $this->notFound();
        }

        $related = $product->related()
            ->where('is_visible', true)
            ->with(['variants', 'category'])
            ->limit(4)
            ->get();

        if ($related->isEmpty() && $product->category_id) {
            $related = $this->visibleProducts()
                ->where('category_id', $product->category_id)
                ->whereKeyNot($product->getKey())
                ->with(['variants', 'category'])
                ->inRandomOrder()
                ->limit(4)
                ->get();
        }

        // Mesma cor que o seletor deixa marcada ao abrir: a primeira com estoque.
        $colored = $product->variants->filter(fn ($v) => filled($v->color));
        $initialColor = $colored->firstWhere('is_available', true)?->color ?? $colored->first()?->color;

        $toUrls = fn (array $paths) => array_map(fn (string $p) => [
            'full' => Format::image($p),
            'thumb' => Format::image($p, thumb: true),
        ], array_values($paths));

        $gallery = [
            'general' => $toUrls(array_values(array_filter($product->images ?? []))),
            'byColor' => collect($product->color_images ?? [])
                ->map(fn ($paths) => $toUrls(array_values(array_filter((array) $paths))))
                ->filter()
                ->all(),
        ];

        // Sem fotos gerais: usa as da primeira cor que tiver.
        if ($gallery['general'] === [] && $gallery['byColor'] !== []) {
            $gallery['general'] = reset($gallery['byColor']);
        }

        return view('storefront.product', [
            'product' => $product,
            'related' => $related,
            'gallery' => $gallery,
            'initialColor' => $initialColor,
        ]);
    }

    public function cart(string $storeSlug)
    {
        if ($blocked = $this->resolveStore($storeSlug)) {
            return $blocked;
        }

        return view('storefront.cart');
    }

    public function favorites(string $storeSlug)
    {
        if ($blocked = $this->resolveStore($storeSlug)) {
            return $blocked;
        }

        return view('storefront.favorites');
    }

    public function about(string $storeSlug)
    {
        if ($blocked = $this->resolveStore($storeSlug)) {
            return $blocked;
        }

        return view('storefront.about');
    }

    public function order(string $storeSlug, string $token)
    {
        if ($blocked = $this->resolveStore($storeSlug)) {
            return $blocked;
        }

        $order = $this->store->orders()->where('public_token', $token)->with('items.product')->first();

        if (! $order) {
            return $this->notFound();
        }

        return view('storefront.order', [
            'order' => $order,
            'whatsappUrl' => $this->store->whatsappUrl($order->whatsapp_message),
            // Abre o WhatsApp sozinho só logo depois do envio (não a cada visita ao link).
            'autoOpen' => session()->pull('order.just_placed') === $order->public_token,
        ]);
    }
}
