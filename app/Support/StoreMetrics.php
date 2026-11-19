<?php

namespace App\Support;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Store;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Métricas do lojista calculadas a partir dos pedidos.
 *
 * "Pedido" = toda sacola enviada pelo WhatsApp (possível venda).
 * "Vendido" = pedido marcado pelo lojista com o status Vendido.
 */
class StoreMetrics
{
    /** Pedidos em aberto há mais que isso entram no alerta de "esquecidos". */
    public const FORGOTTEN_AFTER_DAYS = 2;

    public const PERIODS = [
        '7' => 'Últimos 7 dias',
        '30' => 'Últimos 30 dias',
        '90' => 'Últimos 90 dias',
        'month' => 'Este mês',
        'last_month' => 'Mês passado',
    ];

    public function __construct(public readonly Store $store) {}

    /**
     * Início e fim do período escolhido e do período anterior de mesmo tamanho.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable, 2: CarbonImmutable, 3: CarbonImmutable}
     */
    public static function range(string $period, ?CarbonImmutable $now = null): array
    {
        $now ??= CarbonImmutable::now();

        if ($period === 'month') {
            $from = $now->startOfMonth();
            $to = $now;
            $prevFrom = $from->subMonthNoOverflow();
            // Mesmo trecho do mês anterior (ex.: dia 1 a 19), para comparar de forma justa.
            $prevTo = $prevFrom->addSeconds((int) abs($from->diffInSeconds($to)));

            if ($prevTo->greaterThanOrEqualTo($from)) {
                $prevTo = $from->subSecond();
            }

            return [$from, $to, $prevFrom, $prevTo];
        }

        if ($period === 'last_month') {
            $from = $now->subMonthNoOverflow()->startOfMonth();
            $to = $from->endOfMonth();
            $prevFrom = $from->subMonthNoOverflow()->startOfMonth();

            return [$from, $to, $prevFrom, $prevFrom->endOfMonth()];
        }

        $days = in_array($period, ['7', '30', '90'], true) ? (int) $period : 30;
        $from = $now->subDays($days);

        return [$from, $now, $from->subDays($days), $from->subSecond()];
    }

    /**
     * Números gerais do período.
     *
     * @return array{orders: int, sold: int, sold_total: float, avg_ticket: ?float, pieces_per_order: ?float, close_rate: ?float}
     */
    public function summary(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $orders = $this->orders($from, $to);

        $received = (clone $orders)->count();
        $sold = (clone $orders)->where('status', OrderStatus::Confirmed)->count();
        $soldTotal = (float) (clone $orders)->where('status', OrderStatus::Confirmed)->sum('total');

        $soldPieces = (int) DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.store_id', $this->store->getKey())
            ->whereBetween('orders.created_at', [$from, $to])
            ->where('orders.status', OrderStatus::Confirmed->value)
            ->sum('order_items.quantity');

        return [
            'orders' => $received,
            'sold' => $sold,
            'sold_total' => $soldTotal,
            'avg_ticket' => $sold > 0 ? round($soldTotal / $sold, 2) : null,
            'pieces_per_order' => $sold > 0 ? round($soldPieces / $sold, 1) : null,
            'close_rate' => $received > 0 ? round($sold / $received * 100) : null,
        ];
    }

    /**
     * Variação percentual entre dois valores (null quando não dá para comparar).
     */
    public static function change(int|float|null $current, int|float|null $previous): ?int
    {
        if ($current === null || $previous === null || (float) $previous === 0.0) {
            return null;
        }

        return (int) round(($current - $previous) / $previous * 100);
    }

    /**
     * Produtos por peças pedidas (todas as sacolas enviadas) e por peças vendidas.
     *
     * @return list<array{name: string, orders: int, pieces: int, sold_pieces: int, sold_total: float, close_rate: ?int}>
     */
    public function products(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $confirmed = OrderStatus::Confirmed->value;

        return $this->items($from, $to)
            // Produto apagado continua aparecendo pelo nome guardado no pedido.
            ->groupBy('order_items.product_id', DB::raw('CASE WHEN order_items.product_id IS NULL THEN order_items.product_name END'))
            ->selectRaw('MAX(order_items.product_name) as name')
            ->selectRaw('COUNT(DISTINCT order_items.order_id) as orders')
            ->selectRaw('SUM(order_items.quantity) as pieces')
            ->selectRaw('SUM(CASE WHEN orders.status = ? THEN order_items.quantity ELSE 0 END) as sold_pieces', [$confirmed])
            ->selectRaw('SUM(CASE WHEN orders.status = ? THEN order_items.total ELSE 0 END) as sold_total', [$confirmed])
            ->get()
            ->map(fn ($row) => [
                'name' => (string) $row->name,
                'orders' => (int) $row->orders,
                'pieces' => (int) $row->pieces,
                'sold_pieces' => (int) $row->sold_pieces,
                'sold_total' => (float) $row->sold_total,
                'close_rate' => (int) $row->pieces > 0 ? (int) round($row->sold_pieces / $row->pieces * 100) : null,
            ])
            ->all();
    }

    /**
     * Mais pedidos (possíveis vendas), do maior para o menor.
     */
    public function topRequested(CarbonImmutable $from, CarbonImmutable $to, int $limit = 10): array
    {
        return collect($this->products($from, $to))
            ->sortBy([['pieces', 'desc'], ['orders', 'desc'], ['name', 'asc']])
            ->take($limit)
            ->values()
            ->all();
    }

    /**
     * Mais vendidos (só pedidos marcados como Vendido).
     */
    public function topSold(CarbonImmutable $from, CarbonImmutable $to, int $limit = 10): array
    {
        return collect($this->products($from, $to))
            ->filter(fn (array $p) => $p['sold_pieces'] > 0)
            ->sortBy([['sold_pieces', 'desc'], ['sold_total', 'desc'], ['name', 'asc']])
            ->take($limit)
            ->values()
            ->all();
    }

    /**
     * Combinações de cor e tamanho mais pedidas.
     *
     * @return list<array{name: string, color: ?string, size: ?string, pieces: int, sold_pieces: int}>
     */
    public function topVariants(CarbonImmutable $from, CarbonImmutable $to, int $limit = 10): array
    {
        $confirmed = OrderStatus::Confirmed->value;

        return $this->items($from, $to)
            ->where(fn ($q) => $q->whereNotNull('order_items.color')->orWhereNotNull('order_items.size'))
            ->groupBy('order_items.product_name', 'order_items.color', 'order_items.size')
            ->select('order_items.product_name as name', 'order_items.color', 'order_items.size')
            ->selectRaw('SUM(order_items.quantity) as pieces')
            ->selectRaw('SUM(CASE WHEN orders.status = ? THEN order_items.quantity ELSE 0 END) as sold_pieces', [$confirmed])
            ->orderByDesc('pieces')
            ->orderBy('order_items.product_name')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'name' => (string) $row->name,
                'color' => $row->color,
                'size' => $row->size,
                'pieces' => (int) $row->pieces,
                'sold_pieces' => (int) $row->sold_pieces,
            ])
            ->all();
    }

    /**
     * Pedidos ainda em aberto (Novo ou Em atendimento) há mais de alguns dias.
     */
    public function forgottenOrders(): int
    {
        return Order::query()
            ->where('store_id', $this->store->getKey())
            ->whereIn('status', [OrderStatus::New, OrderStatus::InProgress])
            ->where('created_at', '<', now()->subDays(self::FORGOTTEN_AFTER_DAYS))
            ->count();
    }

    /**
     * Vendido no mês atual, para comparar com o valor da assinatura.
     */
    public function soldThisMonth(): float
    {
        $now = CarbonImmutable::now();

        return $this->summary($now->startOfMonth(), $now)['sold_total'];
    }

    protected function orders(CarbonImmutable $from, CarbonImmutable $to)
    {
        return Order::query()
            ->where('store_id', $this->store->getKey())
            ->whereBetween('created_at', [$from, $to]);
    }

    protected function items(CarbonImmutable $from, CarbonImmutable $to)
    {
        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.store_id', $this->store->getKey())
            ->whereBetween('orders.created_at', [$from, $to]);
    }
}
