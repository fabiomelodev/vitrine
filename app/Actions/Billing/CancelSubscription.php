<?php

namespace App\Actions\Billing;

use App\Enums\SubscriptionStatus;
use App\Models\Store;
use App\Services\Asaas\AsaasClient;
use Illuminate\Support\Carbon;

/**
 * Cancela a assinatura. A loja continua no ar até o fim do período já pago.
 */
class CancelSubscription
{
    public function __construct(protected AsaasClient $asaas) {}

    public function handle(Store $store): void
    {
        if ($store->asaas_subscription_id) {
            $this->asaas->cancelSubscription($store->asaas_subscription_id);
        }

        $lastPaid = $store->subscriptionPayments()
            ->whereIn('status', ['RECEIVED', 'CONFIRMED', 'RECEIVED_IN_CASH'])
            ->max('due_date');

        $until = $lastPaid
            ? Carbon::parse($lastPaid)->addMonth()
            : ($store->isOnTrial() ? $store->trial_ends_at : null);

        $store->forceFill([
            'subscription_status' => SubscriptionStatus::Canceled,
            'asaas_subscription_id' => null,
            'access_until' => $until && $until->isFuture() ? $until : now(),
        ])->save();
    }
}
