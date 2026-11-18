<?php

namespace App\Http\Controllers;

use App\Actions\Billing\HandleAsaasWebhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Recebe os avisos do Asaas. Configure no Asaas:
 *   URL:   https://SEU-DOMINIO/webhooks/asaas
 *   Token: o mesmo valor de ASAAS_WEBHOOK_TOKEN no .env
 */
class AsaasWebhookController extends Controller
{
    public function __invoke(Request $request, HandleAsaasWebhook $handler): JsonResponse
    {
        $expected = (string) config('services.asaas.webhook_token');
        $received = (string) $request->header('asaas-access-token');

        if ($expected === '' || ! hash_equals($expected, $received)) {
            return response()->json(['message' => 'Token inválido.'], 401);
        }

        $handler->handle($request->all());

        return response()->json(['received' => true]);
    }
}
