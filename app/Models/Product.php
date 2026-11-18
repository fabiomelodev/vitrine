<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Support\ImageThumbs;
use Illuminate\Support\Str;

#[Fillable([
    'store_id', 'category_id', 'name', 'slug', 'sku', 'description',
    'price', 'compare_at_price', 'images', 'color_images', 'is_visible', 'is_featured', 'position',
])]
class Product extends Model
{
    /** Fotos antes da última alteração (para apagar as removidas). */
    protected array $imagesBeforeSave = [];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'images' => 'array',
            'color_images' => 'array',
            'is_visible' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Product $product): void {
            $product->slug = Str::slug($product->slug ?: $product->name);
            $product->imagesBeforeSave = $product->exists
                ? static::flattenImages(
                    json_decode((string) $product->getRawOriginal('images'), true) ?: [],
                    json_decode((string) $product->getRawOriginal('color_images'), true) ?: [],
                )
                : [];
        });

        // Miniaturas das fotos novas e limpeza das fotos removidas.
        static::saved(function (Product $product): void {
            if (! $product->wasRecentlyCreated && ! $product->wasChanged(['images', 'color_images'])) {
                return;
            }

            $current = $product->allImagePaths();
            $previous = $product->imagesBeforeSave;

            foreach (array_diff($current, $previous) as $path) {
                ImageThumbs::generate($path);
            }

            foreach (array_diff($previous, $current) as $path) {
                ImageThumbs::delete($path);
            }
        });

        static::deleted(function (Product $product): void {
            foreach ($product->allImagePaths() as $path) {
                ImageThumbs::delete($path);
            }
        });
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('position');
    }

    /**
     * "Complete o look": peças sugeridas junto com este produto.
     */
    public function related(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'product_related', 'product_id', 'related_product_id');
    }

    public function scopeVisible(Builder $query): void
    {
        $query->where('is_visible', true);
    }

    /**
     * @param  array<int|string, string>  $general
     * @param  array<string, array<int|string, string>>  $byColor
     * @return array<int, string>
     */
    protected static function flattenImages(array $general, array $byColor): array
    {
        return collect($general)
            ->merge(collect($byColor)->flatten())
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Todas as fotos do produto (gerais + por cor), sem repetição.
     *
     * @return array<int, string>
     */
    public function allImagePaths(): array
    {
        return static::flattenImages($this->images ?? [], $this->color_images ?? []);
    }

    /**
     * Fotos de uma cor; se a cor não tiver fotos próprias, as fotos gerais.
     *
     * @return array<int, string>
     */
    public function imagesForColor(?string $color): array
    {
        $own = $color ? array_values(array_filter($this->color_images[$color] ?? [])) : [];

        return $own !== [] ? $own : array_values(array_filter($this->images ?? []));
    }

    public function hasOwnImagesForColor(?string $color): bool
    {
        return $color !== null && ! empty(array_filter($this->color_images[$color] ?? []));
    }

    /**
     * Foto de capa. Com uma cor, usa a primeira foto daquela cor (se houver).
     */
    public function coverImage(?string $color = null): ?string
    {
        return $this->imagesForColor($color)[0]
            ?? collect($this->color_images ?? [])->flatten()->filter()->first();
    }

    public function isOnSale(): bool
    {
        return $this->compare_at_price !== null && (float) $this->compare_at_price > (float) $this->price;
    }

    public function isSoldOut(): bool
    {
        return $this->variants->isNotEmpty() && $this->variants->every(fn (ProductVariant $v) => ! $v->is_available);
    }
}
