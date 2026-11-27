<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['store_id', 'asaas_payment_id', 'status', 'billing_type', 'value', 'due_date', 'paid_at', 'invoice_url'])]
class SubscriptionPayment extends Model
{
    /** Status do Asaas que contam como pago. */
    public const PAID_STATUSES = ['RECEIVED', 'CONFIRMED', 'RECEIVED_IN_CASH'];

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
            'CHARGEBACK_REQUESTED', 'CHARGEBACK_DISPUTE', 'AWAITING_CHARGEBACK_REVERSAL' => 'Contestado no cartão',
            default => ucfirst(strtolower(str_replace('_', ' ', $this->status))),
        };
    }

    public function isPaid(): bool
    {
        return in_array($this->status, self::PAID_STATUSES, true);
    }
}
