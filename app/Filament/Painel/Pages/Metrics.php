<?php

namespace App\Filament\Painel\Pages;

use App\Filament\Painel\Resources\Orders\OrderResource;
use App\Models\Store;
use App\Support\StoreMetrics;
use App\Support\VisitMetrics;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Url;

/**
 * Página "Métricas" do painel do lojista.
 */
class Metrics extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Métricas';

    protected static ?string $title = 'Métricas';

    protected static ?string $slug = 'metricas';

    protected static ?int $navigationSort = -1;

    protected string $view = 'filament.painel.pages.metrics';

    public const TABS = [
        'vendas' => 'Vendas',
        'visitas' => 'Visitas',
        'produtos' => 'Produtos e procura',
    ];

    #[Url(as: 'periodo')]
    public string $period = '30';

    #[Url(as: 'aba')]
    public string $tab = 'vendas';

    public function updatedPeriod(): void
    {
        if (! array_key_exists($this->period, StoreMetrics::PERIODS)) {
            $this->period = '30';
        }
    }

    public function setTab(string $tab): void
    {
        $this->tab = array_key_exists($tab, self::TABS) ? $tab : 'vendas';
    }

    protected function getViewData(): array
    {
        /** @var Store $store */
        $store = Filament::getTenant()->loadMissing('plan');

        if (! array_key_exists($this->tab, self::TABS)) {
            $this->tab = 'vendas';
        }

        [$from, $to, $prevFrom, $prevTo] = StoreMetrics::range($this->period);

        $metrics = new StoreMetrics($store);
        $visits = new VisitMetrics($store);

        $data = [
            'tabs' => self::TABS,
            'periods' => StoreMetrics::PERIODS,
            'from' => $from,
            'to' => $to,
            'forgotten' => $metrics->forgottenOrders(),
            'forgottenDays' => StoreMetrics::FORGOTTEN_AFTER_DAYS,
            'ordersUrl' => OrderResource::getUrl('index'),
            'trackingSince' => $visits->trackingSince(),
        ];

        return $data + match ($this->tab) {
            'visitas' => $this->visitData($store, $visits, $metrics, $from, $to, $prevFrom, $prevTo),
            'produtos' => $this->productData($visits, $from, $to),
            default => $this->salesData($store, $metrics, $from, $to, $prevFrom, $prevTo),
        };
    }

    protected function salesData(Store $store, StoreMetrics $metrics, $from, $to, $prevFrom, $prevTo): array
    {
        $current = $metrics->summary($from, $to);
        $previous = $metrics->summary($prevFrom, $prevTo);

        $planPrice = $store->plan?->price !== null ? (float) $store->plan->price : null;
        $soldThisMonth = $metrics->soldThisMonth();

        return [
            'current' => $current,
            'changes' => [
                'orders' => StoreMetrics::change($current['orders'], $previous['orders']),
                'sold' => StoreMetrics::change($current['sold'], $previous['sold']),
                'sold_total' => StoreMetrics::change($current['sold_total'], $previous['sold_total']),
                'avg_ticket' => StoreMetrics::change($current['avg_ticket'], $previous['avg_ticket']),
            ],
            'topRequested' => $metrics->topRequested($from, $to),
            'topSold' => $metrics->topSold($from, $to),
            'topVariants' => $metrics->topVariants($from, $to),
            'soldThisMonth' => $soldThisMonth,
            'planPrice' => $planPrice,
            'paidTimes' => $planPrice ? floor($soldThisMonth / $planPrice) : null,
        ];
    }

    protected function visitData(Store $store, VisitMetrics $visits, StoreMetrics $metrics, $from, $to, $prevFrom, $prevTo): array
    {
        $visitors = $visits->visitors($from, $to);
        $orders = $metrics->summary($from, $to)['orders'];
        $base = route('storefront.home', ['storeSlug' => $store->slug]);

        return [
            'visitors' => $visitors,
            'visitorsChange' => StoreMetrics::change($visitors, $visits->visitors($prevFrom, $prevTo)),
            'conversion' => $visitors > 0 ? round($orders / $visitors * 100, 1) : null,
            'funnel' => $visits->funnel($from, $to),
            'byDay' => $visits->visitorsByDay($from, $to),
            'sources' => $visits->sources($from, $to),
            'shareLinks' => [
                ['Instagram (bio e stories)', $base.'?origem=instagram'],
                ['WhatsApp (status e grupos)', $base.'?origem=whatsapp'],
                ['Facebook', $base.'?origem=facebook'],
                ['TikTok', $base.'?origem=tiktok'],
            ],
        ];
    }

    protected function productData(VisitMetrics $visits, $from, $to): array
    {
        return [
            'mostViewed' => $visits->mostViewed($from, $to),
            'favorites' => $visits->topFavorites($from, $to),
            'stalled' => $visits->stalledProducts($from, $to),
            'failedSearches' => $visits->searches($from, $to, onlyEmpty: true),
            'topSearches' => $visits->searches($from, $to),
            'soldOut' => $visits->soldOutDemand($from, $to),
            'suggestionAdds' => $visits->suggestionAdds($from, $to),
        ];
    }
}
