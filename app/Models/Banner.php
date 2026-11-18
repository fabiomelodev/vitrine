<?php

namespace App\Models;

use App\Enums\BannerLinkType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'store_id', 'title', 'image_path', 'image_mobile_path',
    'link_type', 'link_category_id', 'link_product_id', 'link_url', 'open_in_new_tab',
    'position', 'is_active',
])]
class Banner extends Model
{
    protected function casts(): array
    {
        return [
            'link_type' => BannerLinkType::class,
            'open_in_new_tab' => 'boolean',
            'is_active' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function linkCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'link_category_id');
    }

    public function linkProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'link_product_id');
    }

    /**
     * Endereço para onde o banner leva (ou null quando não tem link
     * ou quando a categoria/produto foi escondido ou apagado).
     */
    public function href(): ?string
    {
        $slug = $this->store->slug;

        return match ($this->link_type) {
            BannerLinkType::Category => $this->linkCategory?->is_visible
                ? route('storefront.category', [$slug, $this->linkCategory->slug])
                : null,
            BannerLinkType::Product => $this->linkProduct?->is_visible
                ? route('storefront.product', [$slug, $this->linkProduct->slug])
                : null,
            BannerLinkType::Url => filled($this->link_url) ? $this->link_url : null,
            default => null,
        };
    }
}
