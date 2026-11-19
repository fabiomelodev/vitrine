<?php

namespace App\Filament\Painel\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Store;
use App\Support\StoreMetrics;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StoreOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        /** @var Store $store */
        $store = Filament::getTenant();

        $orders = Order::query()
            ->where('store_id', $store->getKey())
            ->where('created_at', '>=', now()->subDays(30));

        $confirmed = (clone $orders)->where('status', OrderStatus::Confirmed);
        $forgotten = (new StoreMetrics($store))->forgottenOrders();

        return [
            Stat::make('Pedidos novos', $store->orders()->where('status', OrderStatus::New)->count())
                ->description($forgotten > 0
                    ? $forgotten.' em aberto há mais de '.StoreMetrics::FORGOTTEN_AFTER_DAYS.' dias'
                    : 'Aguardando atendimento')
                ->color($forgotten > 0 ? 'warning' : 'success'),
            Stat::make('Pedidos nos últimos 30 dias', (clone $orders)->count()),
            Stat::make('Valor vendido (30 dias)', 'R$ '.number_format((float) $confirmed->sum('total'), 2, ',', '.')),
            Stat::make('Produtos na vitrine', $store->products()->where('is_visible', true)->count())
                ->description('Sua vitrine: '.$store->publicUrl()),
        ];
    }
}
