<?php

namespace App\Support;

use App\Enums\SubscriptionStatus;
use App\Models\Store;
use App\Models\SubscriptionPayment;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Números das assinaturas da plataforma, para o painel do super admin.
 */
class PlatformMetrics
{
    public const PAYING = 'pagante';

    public const TRIAL = 'teste';

    public const TRIAL_SUBSCRIBED = 'teste_assinado';

    public const OVERDUE = 'atraso';

    public const CANCELED = 'cancelada';

    public const OFFLINE = 'fora_do_ar';

    public const COURTESY = 'cortesia';

    public const SUSPENDED = 'suspensa';

    public const LABELS = [
        self::PAYING => 'Pagante',
        self::TRIAL => 'Em teste',
        self::TRIAL_SUBSCRIBED => 'Assinou no teste',
        self::OVERDUE => 'Em atraso',
        self::CANCELED => 'Cancelada (ainda no ar)',
        self::OFFLINE => 'Fora do ar',
        self::COURTESY => 'Cortesia',
        self::SUSPENDED => 'Suspensa pelo admin',
    ];

    /**
     * Situação da loja do ponto de vista da cobrança.
     */
    public static function classify(Store $store): string
    {
        if (! $store->is_active) {
            return self::SUSPENDED;
        }

        $status = $store->subscription_status ?? SubscriptionStatus::Trialing;

        if ($status === SubscriptionStatus::Courtesy) {
            return self::COURTESY;
        }

        if (! $store->hasActiveAccess()) {
            return self::OFFLINE;
        }

        return match (true) {
            $status === SubscriptionStatus::PastDue, $store->paidPeriodOverdue() => self::OVERDUE,
            $status === SubscriptionStatus::Active => self::PAYING,
            $status === SubscriptionStatus::Canceled => self::CANCELED,
            filled($store->asaas_subscription_id) => self::TRIAL_SUBSCRIBED,
            default => self::TRIAL,
        };
    }

    /**
     * Quantidade de lojas em cada situação e a receita mensal recorrente (MRR).
     *
     * @return array{counts: array<string, int>, mrr: float}
     */
    public static function overview(): array
    {
        $counts = array_fill_keys(array_keys(self::LABELS), 0);
        $mrr = 0.0;

        Store::query()
            ->with('plan')
            // Valor da última cobrança: quem assinou com preço antigo continua com ele no Asaas.
            ->addSelect(['last_charge' => SubscriptionPayment::query()
                ->select('value')
                ->whereColumn('subscription_payments.store_id', 'stores.id')
                ->orderByDesc('due_date')
                ->limit(1)])
            ->chunkById(200, function ($stores) use (&$counts, &$mrr) {
                foreach ($stores as $store) {
                    $type = self::classify($store);
                    $counts[$type]++;

                    if (in_array($type, [self::PAYING, self::OVERDUE], true)) {
                        $mrr += (float) ($store->last_charge ?? $store->plan?->price ?? 0);
                    }
                }
            });

        return ['counts' => $counts, 'mrr' => round($mrr, 2)];
    }

    /**
     * Total recebido (pagamentos confirmados) entre duas datas.
     */
    public static function received(CarbonImmutable $from, CarbonImmutable $to): float
    {
        return (float) SubscriptionPayment::query()
            ->whereIn('status', SubscriptionPayment::PAID_STATUSES)
            ->whereBetween('paid_at', [$from->toDateString(), $to->toDateString()])
            ->sum('value');
    }

    /**
     * Lojas cujo primeiro pagamento aconteceu no período (novos assinantes).
     */
    public static function newSubscribers(CarbonImmutable $from, CarbonImmutable $to): int
    {
        return DB::query()
            ->fromSub(
                SubscriptionPayment::query()
                    ->whereIn('status', SubscriptionPayment::PAID_STATUSES)
                    ->whereNotNull('paid_at')
                    ->groupBy('store_id')
                    ->selectRaw('store_id, MIN(paid_at) as first_paid'),
                'firsts',
            )
            ->whereBetween('first_paid', [$from->toDateString(), $to->toDateString()])
            ->count();
    }

    /**
     * Dos testes que terminaram nos últimos dias, quantos viraram assinatura paga.
     *
     * @return array{ended: int, converted: int, rate: ?int}
     */
    public static function trialConversion(int $days = 90): array
    {
        $ended = Store::query()
            ->whereBetween('trial_ends_at', [now()->subDays($days), now()])
            ->where(fn (Builder $q) => $q->whereNull('subscription_status')->orWhere('subscription_status', '!=', SubscriptionStatus::Courtesy->value));

        $total = (clone $ended)->count();
        $converted = (clone $ended)
            ->whereHas('subscriptionPayments', fn (Builder $q) => $q->whereIn('status', SubscriptionPayment::PAID_STATUSES))
            ->count();

        return [
            'ended' => $total,
            'converted' => $converted,
            'rate' => $total > 0 ? (int) round($converted / $total * 100) : null,
        ];
    }

    /**
     * Testes sem assinatura que terminam nos próximos dias.
     */
    public static function trialsEndingQuery(int $days = 7): Builder
    {
        return Store::query()
            ->where('is_active', true)
            ->where('subscription_status', SubscriptionStatus::Trialing->value)
            ->whereNull('asaas_subscription_id')
            ->whereBetween('trial_ends_at', [now(), now()->addDays($days)])
            ->orderBy('trial_ends_at');
    }

    /**
     * Lojas que precisam de atenção: em atraso ou que saíram do ar recentemente.
     */
    public static function attentionQuery(int $recentDays = 30): Builder
    {
        $now = now();
        $recent = now()->subDays($recentDays);

        return Store::query()
            ->where('is_active', true)
            ->where(fn (Builder $q) => $q
                ->where('subscription_status', SubscriptionStatus::PastDue->value)
                ->orWhere(fn (Builder $w) => $w
                    ->where('subscription_status', SubscriptionStatus::Active->value)
                    ->where('access_until', '<', $now))
                ->orWhere(fn (Builder $w) => $w
                    ->where('subscription_status', SubscriptionStatus::Trialing->value)
                    ->whereBetween('trial_ends_at', [$recent, $now]))
                ->orWhere(fn (Builder $w) => $w
                    ->where('subscription_status', SubscriptionStatus::Canceled->value)
                    ->whereBetween('access_until', [$recent, $now])))
            ->orderByRaw('COALESCE(past_due_since, access_until, trial_ends_at) desc');
    }
}
