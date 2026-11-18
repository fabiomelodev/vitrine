<?php

namespace App\Actions\Billing;

use App\Enums\SubscriptionStatus;
use App\Models\Store;
use App\Services\Asaas\AsaasClient;
use App\Services\Asaas\AsaasException;
use App\Support\Document;

/**
 * Cria (ou reaproveita) o cliente no Asaas e a assinatura mensal da loja.
 * Retorna o link da 1ª cobrança, onde o lojista escolhe Pix, boleto ou cartão.
 */
class StartSubscription
{
    public function __construct(
        protected AsaasClient $asaas,
        protected SyncSubscriptionPayments $syncPayments,
    ) {}

    /**
     * @param  array{billing_name: string, billing_document: string, billing_email: string, billing_phone: ?string}  $billing
     */
    public function handle(Store $store, array $billing): ?string
    {
        $plan = $store->plan;

        if (! $plan) {
            throw new AsaasException('A loja não tem plano definido. Fale com o suporte.');
        }

        if ($store->hasSubscription()) {
            return $store->subscriptionPayments()->whereNotIn('status', ['RECEIVED', 'CONFIRMED', 'RECEIVED_IN_CASH'])->value('invoice_url');
        }

        $store->fill($billing)->save();

        if (! $store->asaas_customer_id) {
            $customer = $this->asaas->createCustomer(array_filter([
                'name' => $billing['billing_name'],
                'cpfCnpj' => Document::digits($billing['billing_document']),
                'email' => $billing['billing_email'],
                'mobilePhone' => preg_replace('/\D/', '', (string) ($billing['billing_phone'] ?? '')) ?: null,
                'externalReference' => 'store:'.$store->getKey(),
            ]));

            $store->forceFill(['asaas_customer_id' => $customer['id']])->save();
        }

        // Ainda em teste: o 1º vencimento é no fim do teste (não perde os dias grátis).
        $firstDue = $store->isOnTrial() && $store->trial_ends_at?->isFuture()
            ? $store->trial_ends_at->copy()->startOfDay()
            : now()->startOfDay();

        $subscription = $this->asaas->createSubscription([
            'customer' => $store->asaas_customer_id,
            'billingType' => 'UNDEFINED',
            'value' => (float) $plan->price,
            'nextDueDate' => $firstDue->toDateString(),
            'cycle' => 'MONTHLY',
            'description' => "Assinatura Provador — {$store->name}",
            'externalReference' => 'store:'.$store->getKey(),
        ]);

        $store->forceFill([
            'asaas_subscription_id' => $subscription['id'],
            // Até o 1º pagamento compensar, a loja fica "em teste" com a tolerância de Store::GRACE_DAYS.
            'subscription_status' => SubscriptionStatus::Trialing,
            'trial_ends_at' => $store->isOnTrial() && $store->trial_ends_at?->isFuture() ? $store->trial_ends_at : now(),
            'past_due_since' => null,
        ])->save();

        $payments = $this->syncPayments->handle($store);

        return collect($payments)->first()?->invoice_url;
    }
}
