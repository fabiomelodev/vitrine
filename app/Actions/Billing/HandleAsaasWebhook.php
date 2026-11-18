<?php

namespace App\Actions\Billing;

use App\Enums\SubscriptionStatus;
use App\Models\Store;
use Illuminate\Support\Carbon;

/**
 * Atualiza a loja conforme os eventos do Asaas.
 * https://docs.asaas.com/docs/payment-events
 */
class HandleAsaasWebhook
{
    public const PAID = ['PAYMENT_CONFIRMED', 'PAYMENT_RECEIVED'];

    public function handle(array $payload): void
    {
        $event = (string) ($payload['event'] ?? '');

        if (isset($payload['payment']) && is_array($payload['payment'])) {
            $this->handlePayment($event, $payload['payment']);

            return;
        }

        if (str_starts_with($event, 'SUBSCRIPTION_') && isset($payload['subscription']['id'])) {
            $this->handleSubscription($event, $payload['subscription']);
        }
    }

    protected function findStore(?string $subscriptionId, ?string $customerId): ?Store
    {
        if ($subscriptionId) {
            $store = Store::query()->where('asaas_subscription_id', $subscriptionId)->first();
            if ($store) {
                return $store;
            }
        }

        return $customerId ? Store::query()->where('asaas_customer_id', $customerId)->first() : null;
    }

    protected function handlePayment(string $event, array $payment): void
    {
        $store = $this->findStore($payment['subscription'] ?? null, $payment['customer'] ?? null);

        // Cobrança avulsa ou de outra integração: ignora.
        if (! $store || empty($payment['subscription'])) {
            return;
        }

        SyncSubscriptionPayments::upsert($store, $payment);

        // Cortesia é decisão manual do admin; o Asaas não muda.
        if ($store->subscription_status === SubscriptionStatus::Courtesy) {
            return;
        }

        if (in_array($event, self::PAID, true)) {
            $paidThrough = Carbon::parse($payment['dueDate'] ?? now())->addMonth();

            $store->forceFill([
                'subscription_status' => SubscriptionStatus::Active,
                'past_due_since' => null,
                'access_until' => $store->access_until && $store->access_until->gt($paidThrough) ? $store->access_until : $paidThrough,
            ])->save();

            return;
        }

        if ($event === 'PAYMENT_OVERDUE') {
            $store->forceFill([
                'subscription_status' => SubscriptionStatus::PastDue,
                'past_due_since' => $store->past_due_since ?? now(),
            ])->save();
        }
    }

    protected function handleSubscription(string $event, array $subscription): void
    {
        if (! in_array($event, ['SUBSCRIPTION_DELETED', 'SUBSCRIPTION_INACTIVATED'], true)) {
            return;
        }

        $store = Store::query()->where('asaas_subscription_id', $subscription['id'])->first();

        if (! $store || $store->subscription_status === SubscriptionStatus::Courtesy) {
            return;
        }

        $store->forceFill([
            'subscription_status' => SubscriptionStatus::Canceled,
            'asaas_subscription_id' => null,
            'access_until' => $store->access_until ?? now(),
        ])->save();
    }
}
