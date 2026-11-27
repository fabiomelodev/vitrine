<?php

namespace App\Actions\Billing;

use App\Enums\SubscriptionStatus;
use App\Models\Store;
use App\Services\Asaas\AsaasClient;
use App\Services\Asaas\AsaasException;
use App\Support\Document;
use Throwable;

/**
 * Cobrança automática no cartão: cria a assinatura no cartão, troca uma assinatura
 * de Pix/boleto para o cartão, ou só troca o cartão de quem já paga no cartão.
 *
 * Os dados do cartão passam direto para o Asaas e não são gravados aqui:
 * guardamos apenas a bandeira e os 4 últimos dígitos para exibir no painel.
 */
class SetupCardBilling
{
    public const METHOD_CARD = 'CREDIT_CARD';

    public const METHOD_INVOICE = 'UNDEFINED';

    public function __construct(
        protected AsaasClient $asaas,
        protected SyncSubscriptionPayments $syncPayments,
    ) {}

    /**
     * @param  array{holder_name: string, number: string, expiry_month: string, expiry_year: string, cvv: string, postal_code: string, address_number: string}  $card
     * @param  array{billing_name: string, billing_document: string, billing_email: string, billing_phone: string}  $billing
     */
    public function handle(Store $store, array $card, array $billing, string $remoteIp): void
    {
        $plan = $store->plan;

        if (! $plan) {
            throw new AsaasException('A loja não tem plano definido. Fale com o suporte.');
        }

        $store->fill($billing)->save();

        $payload = [
            'creditCard' => [
                'holderName' => $card['holder_name'],
                'number' => $card['number'],
                'expiryMonth' => $card['expiry_month'],
                'expiryYear' => $card['expiry_year'],
                'ccv' => $card['cvv'],
            ],
            'creditCardHolderInfo' => [
                'name' => $billing['billing_name'],
                'email' => $billing['billing_email'],
                'cpfCnpj' => Document::digits($billing['billing_document']),
                'postalCode' => preg_replace('/\D/', '', $card['postal_code']),
                'addressNumber' => $card['address_number'],
                'phone' => preg_replace('/\D/', '', $billing['billing_phone']),
                'mobilePhone' => preg_replace('/\D/', '', $billing['billing_phone']),
            ],
            'remoteIp' => $remoteIp,
        ];

        // Já paga no cartão: só troca o cartão (sem cobrança na hora).
        if ($store->hasSubscription() && $store->billing_method === self::METHOD_CARD) {
            $response = $this->asaas->updateSubscriptionCard($store->asaas_subscription_id, $payload);
            $this->rememberCard($store, $card['number'], $response);

            return;
        }

        $this->ensureCustomer($store, $billing);

        $inTrial = $store->isOnTrial() && $store->trial_ends_at?->isFuture();

        // Período já pago (ativa, ou cancelada que ainda está no ar): o cartão só é cobrado quando ele acabar.
        $paidUntil = in_array($store->subscription_status, [SubscriptionStatus::Active, SubscriptionStatus::Canceled], true)
            && $store->access_until?->isFuture()
            ? $store->access_until->copy()
            : null;

        // 1º vencimento: fim do teste, fim do período pago, ou hoje (cobra na hora).
        $firstDue = match (true) {
            $inTrial => $store->trial_ends_at->copy()->startOfDay(),
            $paidUntil !== null => $paidUntil->copy()->startOfDay(),
            default => now()->startOfDay(),
        };

        $subscription = $this->asaas->createCardSubscription([
            ...$payload,
            'customer' => $store->asaas_customer_id,
            'value' => (float) $plan->price,
            'nextDueDate' => $firstDue->toDateString(),
            'cycle' => 'MONTHLY',
            'description' => "Assinatura Provador — {$store->name}",
            'externalReference' => 'store:'.$store->getKey(),
        ]);

        $oldSubscriptionId = $store->asaas_subscription_id;

        $attributes = [
            'asaas_subscription_id' => $subscription['id'],
            'billing_method' => self::METHOD_CARD,
        ];

        $attributes += match (true) {
            $inTrial => ['subscription_status' => SubscriptionStatus::Trialing],
            $paidUntil !== null => ['subscription_status' => SubscriptionStatus::Active, 'access_until' => $paidUntil, 'past_due_since' => null],
            // Em atraso continua assim até o Asaas confirmar a cobrança do cartão (chega pelo webhook).
            $store->subscription_status === SubscriptionStatus::PastDue => [],
            default => ['subscription_status' => SubscriptionStatus::Trialing, 'trial_ends_at' => now(), 'past_due_since' => null],
        };

        $store->forceFill($attributes)->save();
        $this->rememberCard($store, $card['number'], $subscription);

        // Troca de Pix/boleto para cartão: encerra a assinatura antiga para não cobrar duas vezes.
        if ($oldSubscriptionId && $oldSubscriptionId !== $subscription['id']) {
            try {
                $this->asaas->cancelSubscription($oldSubscriptionId);
            } catch (Throwable $e) {
                report($e);
            }
        }

        try {
            $this->syncPayments->handle($store);
        } catch (Throwable $e) {
            report($e);
        }
    }

    protected function ensureCustomer(Store $store, array $billing): void
    {
        if ($store->asaas_customer_id) {
            return;
        }

        $customer = $this->asaas->createCustomer(array_filter([
            'name' => $billing['billing_name'],
            'cpfCnpj' => Document::digits($billing['billing_document']),
            'email' => $billing['billing_email'],
            'mobilePhone' => preg_replace('/\D/', '', (string) $billing['billing_phone']) ?: null,
            'externalReference' => 'store:'.$store->getKey(),
        ]));

        $store->forceFill(['asaas_customer_id' => $customer['id']])->save();
    }

    /**
     * Guarda só bandeira e final do cartão (do retorno do Asaas, ou calculados do número).
     */
    protected function rememberCard(Store $store, string $number, array $response): void
    {
        $digits = preg_replace('/\D/', '', $number);

        $store->forceFill([
            'card_brand' => $response['creditCard']['creditCardBrand'] ?? static::detectBrand($digits),
            'card_last_digits' => $response['creditCard']['creditCardNumber'] ?? substr($digits, -4),
        ])->save();
    }

    public static function detectBrand(string $digits): string
    {
        return match (true) {
            (bool) preg_match('/^(4011|4312|4389|4514|4576|5041|5066|5067|509|6277|6362|6363|650|6516|6550)/', $digits) => 'ELO',
            (bool) preg_match('/^(606282|3841)/', $digits) => 'HIPERCARD',
            (bool) preg_match('/^3[47]/', $digits) => 'AMEX',
            (bool) preg_match('/^4/', $digits) => 'VISA',
            (bool) preg_match('/^(5[1-5]|2[2-7])/', $digits) => 'MASTERCARD',
            default => 'CARTAO',
        };
    }

    public static function brandLabel(?string $brand): string
    {
        return match (strtoupper((string) $brand)) {
            'VISA' => 'Visa',
            'MASTERCARD' => 'Mastercard',
            'ELO' => 'Elo',
            'AMEX' => 'American Express',
            'HIPERCARD' => 'Hipercard',
            default => 'Cartão',
        };
    }

    /**
     * Número de cartão válido pelo algoritmo de Luhn (pega erro de digitação antes de ir ao Asaas).
     */
    public static function luhn(string $digits): bool
    {
        if (! preg_match('/^\d{13,19}$/', $digits)) {
            return false;
        }

        $sum = 0;
        foreach (str_split(strrev($digits)) as $i => $d) {
            $n = (int) $d;
            if ($i % 2 === 1) {
                $n *= 2;
                $n = $n > 9 ? $n - 9 : $n;
            }
            $sum += $n;
        }

        return $sum % 10 === 0;
    }
}
