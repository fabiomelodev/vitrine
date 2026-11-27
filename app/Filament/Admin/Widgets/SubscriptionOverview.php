<?php

namespace App\Filament\Admin\Widgets;

use App\Support\Format;
use App\Support\PlatformMetrics;
use Carbon\CarbonImmutable;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Resumo das assinaturas da plataforma.
 */
class SubscriptionOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'Assinaturas';

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $overview = PlatformMetrics::overview();
        $c = $overview['counts'];

        $now = CarbonImmutable::now();
        $thisMonth = PlatformMetrics::received($now->startOfMonth(), $now);
        $lastMonth = PlatformMetrics::received($now->subMonthNoOverflow()->startOfMonth(), $now->subMonthNoOverflow());
        $newSubs = PlatformMetrics::newSubscribers($now->startOfMonth(), $now);
        $conversion = PlatformMetrics::trialConversion();

        $change = $lastMonth > 0 ? (int) round(($thisMonth - $lastMonth) / $lastMonth * 100) : null;

        return [
            Stat::make('Receita mensal (MRR)', Format::money($overview['mrr']))
                ->description($c[PlatformMetrics::PAYING].' pagantes + '.$c[PlatformMetrics::OVERDUE].' em atraso')
                ->icon(Heroicon::OutlinedBanknotes),
            Stat::make('Recebido neste mês', Format::money($thisMonth))
                ->description($change === null
                    ? 'Mesmo período do mês passado: '.Format::money($lastMonth)
                    : ($change >= 0 ? '↑ ' : '↓ ').abs($change).'% vs mesmo período do mês passado')
                ->color($change !== null && $change < 0 ? 'danger' : 'success'),
            Stat::make('Novos assinantes no mês', $newSubs)
                ->description('Primeiro pagamento confirmado neste mês'),
            Stat::make('Em teste', $c[PlatformMetrics::TRIAL] + $c[PlatformMetrics::TRIAL_SUBSCRIBED])
                ->description($c[PlatformMetrics::TRIAL_SUBSCRIBED].' já assinaram, aguardando o 1º pagamento'),
            Stat::make('Conversão do teste', $conversion['rate'] !== null ? $conversion['rate'].'%' : '—')
                ->description($conversion['ended'] > 0
                    ? $conversion['converted'].' de '.$conversion['ended'].' testes dos últimos 90 dias viraram assinatura'
                    : 'Nenhum teste terminou nos últimos 90 dias'),
            Stat::make('Em atraso', $c[PlatformMetrics::OVERDUE])
                ->description('Ainda no ar, dentro da tolerância')
                ->color($c[PlatformMetrics::OVERDUE] > 0 ? 'warning' : 'gray'),
            Stat::make('Fora do ar', $c[PlatformMetrics::OFFLINE])
                ->description('Teste vencido ou pagamento não feito')
                ->color($c[PlatformMetrics::OFFLINE] > 0 ? 'danger' : 'gray'),
            Stat::make('Cortesia', $c[PlatformMetrics::COURTESY])
                ->description($c[PlatformMetrics::CANCELED].' canceladas ainda no ar · '.$c[PlatformMetrics::SUSPENDED].' suspensas'),
        ];
    }
}
