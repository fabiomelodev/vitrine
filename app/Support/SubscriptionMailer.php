<?php

namespace App\Support;

use App\Enums\SubscriptionStatus;
use App\Models\Store;
use App\Notifications\SubscriptionNotice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Envia os e-mails da assinatura, cada aviso uma única vez.
 * Falhas de e-mail nunca interrompem o webhook nem o painel.
 */
class SubscriptionMailer
{
    /** Loja que saiu do ar há mais tempo que isso não recebe o aviso (ex.: no primeiro deploy). */
    public const OFFLINE_NOTICE_WINDOW_DAYS = 7;

    /**
     * @param  array<string, mixed>  $data
     */
    public static function send(Store $store, string $type, string $reference, array $data = []): bool
    {
        if ($store->subscription_status === SubscriptionStatus::Courtesy) {
            return false;
        }

        $emails = $store->billingEmails();

        if ($emails === []) {
            return false;
        }

        try {
            $inserted = DB::table('subscription_notices')->insertOrIgnore([
                'store_id' => $store->getKey(),
                'type' => $type,
                'reference' => $reference,
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            report($e);

            return false;
        }

        if ($inserted === 0) {
            return false; // já enviado
        }

        try {
            Notification::route('mail', $emails)->notify(new SubscriptionNotice($store, $type, $data));
        } catch (Throwable $e) {
            report($e);

            // Libera para tentar de novo na próxima verificação.
            DB::table('subscription_notices')
                ->where(['store_id' => $store->getKey(), 'type' => $type, 'reference' => $reference])
                ->delete();

            return false;
        }

        return true;
    }

    /**
     * Pagamento em atraso: um aviso por mensalidade.
     */
    public static function overdue(Store $store): bool
    {
        $cycle = $store->access_until ?? $store->past_due_since ?? now();

        return static::send($store, SubscriptionNotice::OVERDUE, 'ciclo:'.$cycle->toDateString(), [
            'blocks_at' => $store->blocksAt(),
        ]);
    }

    /**
     * Verificação diária: fim do teste, atraso sem aviso do Asaas e vitrine que saiu do ar.
     * Retorna o tipo de aviso enviado (ou null).
     */
    public static function check(Store $store): ?string
    {
        if (! $store->is_active || $store->subscription_status === SubscriptionStatus::Courtesy) {
            return null;
        }

        if (! $store->hasActiveAccess()) {
            $blockedAt = $store->blocksAt();

            if (! $blockedAt || $blockedAt->lt(now()->subDays(self::OFFLINE_NOTICE_WINDOW_DAYS))) {
                return null;
            }

            $reason = $store->isOnTrial() && ! $store->asaas_subscription_id ? 'trial' : 'payment';

            return static::send($store, SubscriptionNotice::OFFLINE, 'bloqueio:'.$blockedAt->toDateString(), ['reason' => $reason])
                ? SubscriptionNotice::OFFLINE : null;
        }

        if ($store->paidPeriodOverdue() || $store->subscription_status === SubscriptionStatus::PastDue) {
            return static::overdue($store) ? SubscriptionNotice::OVERDUE : null;
        }

        if ($store->isOnTrial() && ! $store->asaas_subscription_id && $store->trial_ends_at?->isFuture()) {
            $days = $store->trialDaysLeft();
            $reference = 'teste:'.$store->trial_ends_at->toDateString();

            if ($days !== null && $days <= 1) {
                return static::send($store, SubscriptionNotice::TRIAL_LAST_DAY, $reference)
                    ? SubscriptionNotice::TRIAL_LAST_DAY : null;
            }

            if ($days !== null && $days <= 3) {
                return static::send($store, SubscriptionNotice::TRIAL_ENDING, $reference, ['days' => $days])
                    ? SubscriptionNotice::TRIAL_ENDING : null;
            }
        }

        return null;
    }

    /**
     * @return array<string, int> quantidade de avisos enviados por tipo
     */
    public static function checkAll(): array
    {
        $sent = [];

        Store::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('subscription_status')->orWhere('subscription_status', '!=', SubscriptionStatus::Courtesy->value))
            ->chunkById(100, function ($stores) use (&$sent) {
                foreach ($stores as $store) {
                    if ($type = static::check($store)) {
                        $sent[$type] = ($sent[$type] ?? 0) + 1;
                    }
                }
            });

        return $sent;
    }
}
