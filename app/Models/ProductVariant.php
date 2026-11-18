<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['product_id', 'color', 'color_hex', 'size', 'is_available', 'position'])]
class ProductVariant extends Model
{
    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function label(): string
    {
        return collect([$this->color, $this->size])->filter()->implode(' · ');
    }
}
