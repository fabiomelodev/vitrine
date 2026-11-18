<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description', 'price', 'max_products', 'trial_days', 'is_active'])]
class Plan extends Model
{
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'max_products' => 'integer',
            'trial_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Plano usado nas lojas novas (hoje existe um só).
     */
    public static function default(): ?self
    {
        return static::query()->where('is_active', true)->orderBy('id')->first();
    }

    public function stores(): HasMany
    {
        return $this->hasMany(Store::class);
    }
}
