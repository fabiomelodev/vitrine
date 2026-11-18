<?php

namespace App\Services\Asaas;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Cliente mínimo da API v3 do Asaas.
 * Documentação: https://docs.asaas.com
 */
class AsaasClient
{
    protected function http(): PendingRequest
    {
        $key = config('services.asaas.key');

        if (blank($key)) {
            throw new AsaasException('A chave do Asaas (ASAAS_API_KEY) não está configurada.');
        }

        return Http::baseUrl(rtrim((string) config('services.asaas.url'), '/'))
            ->withHeaders([
                'access_token' => $key,
                'User-Agent' => 'Provador/1.0',
            ])
            ->acceptJson()
            ->asJson()
            ->timeout(20);
    }

    protected function check(Response $response): array
    {
        if ($response->failed()) {
            $message = $response->json('errors.0.description') ?? 'Erro ao falar com o Asaas (HTTP '.$response->status().').';

            throw new AsaasException($message);
        }

        return $response->json() ?? [];
    }

    /**
     * @param  array{name: string, cpfCnpj: string, email?: string, mobilePhone?: string, externalReference?: string}  $data
     */
    public function createCustomer(array $data): array
    {
        return $this->check($this->http()->post('/customers', $data));
    }

    /**
     * @param  array{customer: string, billingType: string, value: float, nextDueDate: string, cycle: string, description?: string, externalReference?: string}  $data
     */
    public function createSubscription(array $data): array
    {
        return $this->check($this->http()->post('/subscriptions', $data));
    }

    public function subscriptionPayments(string $subscriptionId): array
    {
        return $this->check($this->http()->get("/subscriptions/{$subscriptionId}/payments"))['data'] ?? [];
    }

    public function cancelSubscription(string $subscriptionId): array
    {
        return $this->check($this->http()->delete("/subscriptions/{$subscriptionId}"));
    }
}
