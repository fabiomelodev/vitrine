<?php

namespace App\Livewire\Storefront;

use App\Livewire\Storefront\Concerns\InteractsWithStore;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StorefrontEvent;
use App\Support\Format;
use App\Support\StorefrontTracker;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Grade de produtos com filtros (categoria, todos os produtos e busca).
 */
class ProductGrid extends Component
{
    use InteractsWithStore;

    #[Locked]
    public ?int $categoryId = null;

    #[Locked]
    public bool $searchMode = false;

    #[Url(as: 'q', except: '')]
    public string $q = '';

    #[Url(as: 'tam', except: [])]
    public array $sizes = [];

    #[Url(as: 'cor', except: [])]
    public array $colors = [];

    #[Url(as: 'preco', except: '')]
    public string $price = '';

    #[Url(as: 'ordem', except: 'recentes')]
    public string $sort = 'recentes';

    #[Url(as: 'todos', except: false)]
    public bool $includeSoldOut = false;

    public int $perPage = 24;

    /** Última busca registrada nas métricas (evita contar de novo a cada filtro). */
    #[Locked]
    public ?string $loggedSearch = null;

    public const PRICE_RANGES = [
        'ate-100' => ['Até R$ 100', 0, 100],
        '100-200' => ['R$ 100 a R$ 200', 100, 200],
        '200-300' => ['R$ 200 a R$ 300', 200, 300],
        'acima-300' => ['Acima de R$ 300', 300, null],
    ];

    public function toggleSize(string $size): void
    {
        $this->sizes = in_array($size, $this->sizes, true)
            ? array_values(array_diff($this->sizes, [$size]))
            : [...$this->sizes, $size];
        $this->perPage = 24;
    }

    public function toggleColor(string $color): void
    {
        $this->colors = in_array($color, $this->colors, true)
            ? array_values(array_diff($this->colors, [$color]))
            : [...$this->colors, $color];
        $this->perPage = 24;
    }

    public function setPrice(string $range): void
    {
        $this->price = $this->price === $range ? '' : $range;
        $this->perPage = 24;
    }

    public function clearFilters(): void
    {
        $this->sizes = [];
        $this->colors = [];
        $this->price = '';
        $this->perPage = 24;
    }

    public function loadMore(): void
    {
        $this->perPage += 24;
    }

    protected function scope(): Builder
    {
        return Product::query()
            ->where('store_id', $this->storeId)
            ->where('is_visible', true)
            ->when($this->categoryId, fn (Builder $q) => $q->where('category_id', $this->categoryId))
            ->when($this->searchMode && trim($this->q) !== '', function (Builder $query) {
                $term = '%'.str_replace(['%', '_'], ['\%', '\_'], trim($this->q)).'%';

                $query->where(fn (Builder $q) => $q
                    ->where('name', 'like', $term)
                    ->orWhere('description', 'like', $term)
                    ->orWhere('sku', 'like', $term)
                    ->orWhereHas('category', fn (Builder $c) => $c->where('name', 'like', $term))
                    ->orWhereHas('variants', fn (Builder $v) => $v->where('color', 'like', $term)));
            });
    }

    /**
     * Guarda o termo buscado e quantos produtos ele encontrou (sem contar filtros).
     */
    protected function logSearch(Builder $scope): void
    {
        $term = Str::lower(Str::squish($this->q));

        if (! $this->searchMode || $term === '' || $term === $this->loggedSearch) {
            return;
        }

        $this->loggedSearch = $term;

        StorefrontTracker::record($this->store(), StorefrontEvent::SEARCH, null, $term, (clone $scope)->count());
    }

    public function render()
    {
        $store = $this->store();
        $scope = $this->scope();

        // Opções de filtro a partir das peças deste recorte.
        $variantOptions = ProductVariant::query()
            ->whereIn('product_id', (clone $scope)->select('id'))
            ->get(['color', 'color_hex', 'size']);

        $query = (clone $scope)
            ->with(['variants', 'category'])
            ->when(! $this->includeSoldOut, fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->whereDoesntHave('variants')
                ->orWhereHas('variants', fn (Builder $v) => $v->where('is_available', true))))
            ->when($this->sizes !== [], fn (Builder $q) => $q->whereHas('variants', fn (Builder $v) => $v
                ->whereIn('size', $this->sizes)->where('is_available', true)))
            ->when($this->colors !== [], fn (Builder $q) => $q->whereHas('variants', fn (Builder $v) => $v
                ->whereIn('color', $this->colors)))
            ->when(isset(self::PRICE_RANGES[$this->price]), function (Builder $q) {
                [, $min, $max] = self::PRICE_RANGES[$this->price];
                $q->where('price', '>=', $min)->when($max, fn (Builder $w) => $w->where('price', '<', $max));
            });

        match ($this->sort) {
            'menor-preco' => $query->orderBy('price'),
            'maior-preco' => $query->orderByDesc('price'),
            default => $query->orderByDesc('is_featured')->latest(),
        };

        $total = (clone $query)->count();

        $this->logSearch($scope);
        $products = $query->limit($this->perPage)->get();

        return view('storefront.livewire.product-grid', [
            'store' => $store,
            'products' => $products,
            'total' => $total,
            'sizeOptions' => Format::sortSizes($variantOptions->pluck('size')),
            'colorOptions' => $variantOptions->whereNotNull('color')->unique('color')->values(),
            'activeCount' => count($this->sizes) + count($this->colors) + ($this->price !== '' ? 1 : 0),
        ]);
    }
}
