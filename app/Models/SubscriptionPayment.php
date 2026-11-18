<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['store_id', 'asaas_payment_id', 'status', 'billing_type', 'value', 'due_date', 'paid_at', 'invoice_url'])]
class SubscriptionPayment extends Model
{
    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'due_date' => 'date',
            'paid_at' => 'date',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'PENDING' => 'Aguardando pagamento',
            'RECEIVED', 'CONFIRMED', 'RECEIVED_IN_CASH' => 'Pago',
            'OVERDUE' => 'Vencido',
            'REFUNDED' => 'Estornado',
            default => ucfirst(strtolower(str_replace('_', ' ', $this->status))),
        };
    }

    public function isPaid(): bool
    {
        return in_array($this->status, ['RECEIVED', 'CONFIRMED', 'RECEIVED_IN_CASH'], true);
    }
}
