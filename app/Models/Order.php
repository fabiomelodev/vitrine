<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'store_id', 'customer_name', 'customer_phone', 'status',
    'subtotal', 'discount', 'total', 'whatsapp_message', 'internal_notes',
])]
class Order extends Model
{
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order): void {
            if (! $order->number) {
                $store = $order->store ?? Store::findOrFail($order->store_id);
                $order->number = $store->reserveOrderNumber();
                $order->code = $store->order_prefix.'-'.$order->number;
            }

            $order->public_token ??= Str::lower((string) Str::ulid());
            $order->customer_phone = preg_replace('/\D/', '', (string) $order->customer_phone);
            $order->status ??= OrderStatus::New;
        });
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Link para a loja chamar o cliente no WhatsApp.
     */
    public function customerWhatsappUrl(): string
    {
        $digits = preg_replace('/\D/', '', (string) $this->customer_phone);

        return 'https://wa.me/'.(str_starts_with($digits, '55') && strlen($digits) > 11 ? $digits : '55'.$digits);
    }

    public function formattedPhone(): string
    {
        $d = preg_replace('/\D/', '', (string) $this->customer_phone);

        return strlen($d) === 11
            ? sprintf('(%s) %s-%s', substr($d, 0, 2), substr($d, 2, 5), substr($d, 7))
            : $d;
    }
}
