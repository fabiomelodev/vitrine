<?php

namespace App\Support;

use App\Enums\OrderStatus;
use App\Models\Store;
use App\Models\StorefrontEvent as Event;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Métricas de visitas e demanda, a partir dos eventos anônimos da vitrine.
 */
class VisitMetrics
{
    public function __construct(public readonly Store $store) {}

    /**
     * Quando a loja começou a ter visitas contadas (null = nenhuma ainda).
     */
    public function trackingSince(): ?CarbonImmutable
    {
        $first = DB::table('storefront_events')->where('store_id', $this->store->getKey())->min('created_at');

        return $first ? CarbonImmutable::parse($first) : null;
    }

    public function visitors(CarbonImmutable $from, CarbonImmutable $to): int
    {
        return $this->distinctVisitors(Event::VISIT, $from, $to);
    }

    /**
     * Visitantes → viram produto → montaram sacola → enviaram pedido → vendido.
     *
     * @return list<array{label: string, value: int, rate: ?int}>
     */
    public function funnel(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $visitors = $this->visitors($from, $to);

        $orders = DB::table('orders')
            ->where('store_id', $this->store->getKey())
            ->whereBetween('created_at', [$from, $to]);

        $steps = [
            ['Visitantes', $visitors],
            ['Viram um produto', $this->distinctVisitors(Event::PRODUCT_VIEW, $from, $to)],
            ['Colocaram na sacola', $this->distinctVisitors(Event::ADD_TO_CART, $from, $to)],
            ['Enviaram pedido', (clone $orders)->count()],
            ['Vendido', (clone $orders)->where('status', OrderStatus::Confirmed->value)->count()],
        ];

        return array_map(fn (array $s) => [
            'label' => $s[0],
            'value' => $s[1],
            'rate' => $visitors > 0 ? (int) round($s[1] / $visitors * 100) : null,
        ], $steps);
    }

    /**
     * Visitantes por dia, incluindo dias sem visita.
     *
     * @return list<array{date: CarbonImmutable, value: int}>
     */
    public function visitorsByDay(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $rows = $this->events(Event::VISIT, $from, $to)
            ->selectRaw('DATE(created_at) as day, COUNT(DISTINCT visitor) as total')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('total', 'day');

        $days = [];

        foreach (CarbonPeriod::create($from->startOfDay(), '1 day', $to->startOfDay()) as $day) {
            $key = $day->format('Y-m-d');
            $days[] = ['date' => CarbonImmutable::parse($key), 'value' => (int) ($rows[$key] ?? 0)];
        }

        return $days;
    }

    /**
     * Visitantes e pedidos por origem (Instagram, WhatsApp, Google...).
     *
     * @return list<array{source: string, label: string, visitors: int, orders: int}>
     */
    public function sources(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $visitors = $this->events(Event::VISIT, $from, $to)
            ->selectRaw("COALESCE(source, 'direto') as src, COUNT(DISTINCT visitor) as total")
            ->groupBy(DB::raw("COALESCE(source, 'direto')"))
            ->pluck('total', 'src');

        $orders = DB::table('orders')
            ->where('store_id', $this->store->getKey())
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw("COALESCE(source, 'direto') as src, COUNT(*) as total")
            ->groupBy(DB::raw("COALESCE(source, 'direto')"))
            ->pluck('total', 'src');

        return collect($visitors->keys())->merge($orders->keys())->unique()
            ->map(fn ($src) => [
                'source' => (string) $src,
                'label' => StorefrontTracker::sourceLabel((string) $src),
                'visitors' => (int) ($visitors[$src] ?? 0),
                'orders' => (int) ($orders[$src] ?? 0),
            ])
            ->sortBy([['visitors', 'desc'], ['orders', 'desc']])
            ->values()
            ->all();
    }

    /**
     * Produtos mais vistos, com quantos pedidos tiveram (conversão).
     *
     * @return list<array{name: string, views: int, orders: int, rate: int}>
     */
    public function mostViewed(CarbonImmutable $from, CarbonImmutable $to, int $limit = 10): array
    {
        $views = $this->productCounts(Event::PRODUCT_VIEW, $from, $to, $limit);

        $orders = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.store_id', $this->store->getKey())
            ->whereBetween('orders.created_at', [$from, $to])
            ->whereIn('order_items.product_id', array_column($views, 'product_id'))
            ->selectRaw('order_items.product_id, COUNT(DISTINCT order_items.order_id) as total')
            ->groupBy('order_items.product_id')
            ->pluck('total', 'product_id');

        return array_map(function (array $row) use ($orders) {
            $count = (int) ($orders[$row['product_id']] ?? 0);

            return [
                'name' => $row['name'],
                'views' => $row['total'],
                'orders' => $count,
                'rate' => $row['total'] > 0 ? (int) round($count / $row['total'] * 100) : 0,
            ];
        }, $views);
    }

    /**
     * @return list<array{name: string, total: int}>
     */
    public function topFavorites(CarbonImmutable $from, CarbonImmutable $to, int $limit = 10): array
    {
        return array_map(fn ($r) => ['name' => $r['name'], 'total' => $r['total']], $this->productCounts(Event::FAVORITE, $from, $to, $limit));
    }

    /**
     * Produtos na vitrine sem nenhuma visita e nenhum pedido no período.
     *
     * @return array{count: int, items: list<string>}
     */
    public function stalledProducts(CarbonImmutable $from, CarbonImmutable $to, int $limit = 10): array
    {
        $query = DB::table('products')
            ->where('store_id', $this->store->getKey())
            ->where('is_visible', true)
            ->where('created_at', '<', $from) // produto novo ainda não teve tempo
            ->whereNotIn('id', $this->events(Event::PRODUCT_VIEW, $from, $to)->whereNotNull('product_id')->select('product_id'))
            ->whereNotIn('id', DB::table('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->where('orders.store_id', $this->store->getKey())
                ->whereBetween('orders.created_at', [$from, $to])
                ->whereNotNull('order_items.product_id')
                ->select('order_items.product_id'));

        return [
            'count' => (clone $query)->count(),
            'items' => (clone $query)->orderBy('created_at')->limit($limit)->pluck('name')->all(),
        ];
    }

    /**
     * Termos buscados. Com $onlyEmpty, só os que não encontraram nada.
     *
     * @return list<array{term: string, total: int}>
     */
    public function searches(CarbonImmutable $from, CarbonImmutable $to, bool $onlyEmpty = false, int $limit = 10): array
    {
        return $this->events(Event::SEARCH, $from, $to)
            ->whereNotNull('detail')
            ->when($onlyEmpty, fn (Builder $q) => $q->where('value', 0))
            ->selectRaw('detail, COUNT(DISTINCT visitor) as total')
            ->groupBy('detail')
            ->orderByDesc('total')
            ->orderBy('detail')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => ['term' => (string) $r->detail, 'total' => (int) $r->total])
            ->all();
    }

    /**
     * Procura por peças e tamanhos esgotados: toques no tamanho e pedidos de aviso.
     *
     * @return list<array{name: string, variant: ?string, taps: int, requests: int}>
     */
    public function soldOutDemand(CarbonImmutable $from, CarbonImmutable $to, int $limit = 10): array
    {
        return DB::table('storefront_events as e')
            ->join('products as p', 'p.id', '=', 'e.product_id')
            ->where('e.store_id', $this->store->getKey())
            ->whereIn('e.type', [Event::SOLDOUT_TAP, Event::RESTOCK_REQUEST])
            ->whereBetween('e.created_at', [$from, $to])
            ->selectRaw('p.name, e.detail')
            ->selectRaw('COUNT(DISTINCT CASE WHEN e.type = ? THEN e.visitor END) as taps', [Event::SOLDOUT_TAP])
            ->selectRaw('COUNT(DISTINCT CASE WHEN e.type = ? THEN e.visitor END) as requests', [Event::RESTOCK_REQUEST])
            ->groupBy('p.id', 'p.name', 'e.detail')
            ->get()
            ->map(fn ($r) => [
                'name' => (string) $r->name,
                'variant' => $r->detail,
                'taps' => (int) $r->taps,
                'requests' => (int) $r->requests,
            ])
            ->sortBy([['requests', 'desc'], ['taps', 'desc'], ['name', 'asc']])
            ->take($limit)
            ->values()
            ->all();
    }

    /**
     * Peças colocadas na sacola pelo "Complete o look".
     */
    public function suggestionAdds(CarbonImmutable $from, CarbonImmutable $to): int
    {
        return (int) $this->events(Event::ADD_TO_CART, $from, $to)->where('detail', 'sugestao')->sum('value');
    }

    protected function events(string $type, CarbonImmutable $from, CarbonImmutable $to): Builder
    {
        return DB::table('storefront_events')
            ->where('store_id', $this->store->getKey())
            ->where('type', $type)
            ->whereBetween('created_at', [$from, $to]);
    }

    protected function distinctVisitors(string $type, CarbonImmutable $from, CarbonImmutable $to): int
    {
        return (int) $this->events($type, $from, $to)->distinct()->count('visitor');
    }

    /**
     * @return list<array{product_id: int, name: string, total: int}>
     */
    protected function productCounts(string $type, CarbonImmutable $from, CarbonImmutable $to, int $limit): array
    {
        return DB::table('storefront_events as e')
            ->join('products as p', 'p.id', '=', 'e.product_id')
            ->where('e.store_id', $this->store->getKey())
            ->where('e.type', $type)
            ->whereBetween('e.created_at', [$from, $to])
            ->selectRaw('p.id as product_id, p.name, COUNT(DISTINCT e.visitor) as total')
            ->groupBy('p.id', 'p.name')
            ->orderByDesc('total')
            ->orderBy('p.name')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => ['product_id' => (int) $r->product_id, 'name' => (string) $r->name, 'total' => (int) $r->total])
            ->all();
    }
}
