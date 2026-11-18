<?php

namespace App\Actions\Billing;

use App\Models\Store;
use App\Models\SubscriptionPayment;
use App\Services\Asaas\AsaasClient;

/**
 * Busca as cobranças da assinatura no Asaas e guarda uma cópia local.
 */
class SyncSubscriptionPayments
{
    public function __construct(protected AsaasClient $asaas) {}

    /**
     * @return array<int, SubscriptionPayment>
     */
    public function handle(Store $store): array
    {
        if (! $store->asaas_subscription_id) {
            return [];
        }

        return collect($this->asaas->subscriptionPayments($store->asaas_subscription_id))
            ->map(fn (array $payment) => static::upsert($store, $payment))
            ->sortBy('due_date')
            ->values()
            ->all();
    }

    public static function upsert(Store $store, array $payment): SubscriptionPayment
    {
        return SubscriptionPayment::query()->updateOrCreate(
            ['asaas_payment_id' => $payment['id']],
            [
                'store_id' => $store->getKey(),
                'status' => $payment['status'] ?? 'PENDING',
                'billing_type' => $payment['billingType'] ?? null,
                'value' => $payment['value'] ?? 0,
                'due_date' => $payment['dueDate'] ?? now()->toDateString(),
                'paid_at' => $payment['paymentDate'] ?? $payment['clientPaymentDate'] ?? null,
                'invoice_url' => $payment['invoiceUrl'] ?? null,
            ],
        );
    }
}
