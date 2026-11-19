<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Evento anônimo da vitrine, usado nas métricas do lojista.
 */
#[Fillable(['store_id', 'type', 'product_id', 'visitor', 'source', 'detail', 'value', 'created_at'])]
class StorefrontEvent extends Model
{
    use MassPrunable;

    public const VISIT = 'visit';

    public const PRODUCT_VIEW = 'product_view';

    public const ADD_TO_CART = 'add_to_cart';

    public const FAVORITE = 'favorite';

    public const SEARCH = 'search';

    public const SOLDOUT_TAP = 'soldout_tap';

    public const RESTOCK_REQUEST = 'restock_request';

    /** Guardamos 13 meses: dá para comparar com o mesmo mês do ano anterior. */
    public const KEEP_DAYS = 400;

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'value' => 'integer',
        ];
    }

    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subDays(self::KEEP_DAYS));
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
