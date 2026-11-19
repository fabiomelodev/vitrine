<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use App\Support\StoreMetrics;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreMetricsTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->store = Store::where('slug', 'atelierlume')->firstOrFail();
        $this->store->orders()->delete(); // começa sem os pedidos de demonstração
    }

    /**
     * @param  list<array{0: string, 1: ?string, 2: ?string, 3: int, 4: float}>  $items  [nome, cor, tamanho, qtd, preço]
     */
    protected function order(OrderStatus $status, array $items, string $when = 'now'): Order
    {
        $this->travelTo(CarbonImmutable::parse($when));

        $total = array_sum(array_map(fn ($i) => $i[3] * $i[4], $items));

        $order = $this->store->orders()->create([
            'customer_name' => 'Cliente Teste',
            'customer_phone' => '11900000000',
            'status' => $status,
            'subtotal' => $total,
            'total' => $total,
        ]);

        foreach ($items as [$name, $color, $size, $qty, $price]) {
            $order->items()->create([
                'product_name' => $name,
                'color' => $color,
                'size' => $size,
                'quantity' => $qty,
                'unit_price' => $price,
                'total' => $qty * $price,
            ]);
        }

        $this->travelBack();

        return $order;
    }

    public function test_requested_and_sold_rankings(): void
    {
        $this->order(OrderStatus::Confirmed, [['Vestido', 'Azul', 'M', 2, 100], ['Bolsa', null, null, 1, 50]]);
        $this->order(OrderStatus::New, [['Vestido', 'Azul', 'M', 1, 100], ['Calça', 'Preto', '38', 3, 80]]);
        $this->order(OrderStatus::Canceled, [['Calça', 'Preto', '38', 1, 80]]);

        $metrics = new StoreMetrics($this->store);
        [$from, $to] = StoreMetrics::range('30');

        // Mais pedidos: conta todas as peças, vendidas ou não.
        $requested = $metrics->topRequested($from, $to);
        $this->assertSame(['Calça', 'Vestido', 'Bolsa'], array_column($requested, 'name'));
        $this->assertSame(4, $requested[0]['pieces']);
        $this->assertSame(0, $requested[0]['sold_pieces']);
        $this->assertSame(67, $requested[1]['close_rate']); // 2 de 3 vestidos vendidos

        // Mais vendidos: só pedidos marcados como Vendido.
        $sold = $metrics->topSold($from, $to);
        $this->assertSame(['Vestido', 'Bolsa'], array_column($sold, 'name'));
        $this->assertSame(200.0, $sold[0]['sold_total']);

        $variants = $metrics->topVariants($from, $to);
        $this->assertSame('Calça', $variants[0]['name']);
        $this->assertSame(4, $variants[0]['pieces']);
        $this->assertSame(3, $variants[1]['pieces']); // Vestido Azul M
        $this->assertSame(2, $variants[1]['sold_pieces']);
    }

    public function test_summary_and_comparison_with_previous_period(): void
    {
        $this->order(OrderStatus::Confirmed, [['Vestido', 'Azul', 'M', 2, 100]], '-3 days');
        $this->order(OrderStatus::Confirmed, [['Bolsa', null, null, 1, 100]], '-2 days');
        $this->order(OrderStatus::New, [['Vestido', 'Azul', 'M', 1, 100]], '-1 day');
        $this->order(OrderStatus::Confirmed, [['Vestido', 'Azul', 'G', 1, 100]], '-40 days'); // período anterior

        $metrics = new StoreMetrics($this->store);
        [$from, $to, $prevFrom, $prevTo] = StoreMetrics::range('30');

        $summary = $metrics->summary($from, $to);
        $this->assertSame(3, $summary['orders']);
        $this->assertSame(2, $summary['sold']);
        $this->assertSame(300.0, $summary['sold_total']);
        $this->assertSame(150.0, $summary['avg_ticket']);
        $this->assertSame(1.5, $summary['pieces_per_order']);
        $this->assertEquals(67, $summary['close_rate']);

        $previous = $metrics->summary($prevFrom, $prevTo);
        $this->assertSame(1, $previous['sold']);
        $this->assertSame(100, StoreMetrics::change($summary['sold'], $previous['sold']));
        $this->assertNull(StoreMetrics::change(5, 0));
    }

    public function test_forgotten_orders_are_open_for_more_than_two_days(): void
    {
        $this->order(OrderStatus::New, [['Vestido', null, 'M', 1, 100]], '-5 days');
        $this->order(OrderStatus::InProgress, [['Vestido', null, 'M', 1, 100]], '-3 days');
        $this->order(OrderStatus::New, [['Vestido', null, 'M', 1, 100]], '-1 day');
        $this->order(OrderStatus::Confirmed, [['Vestido', null, 'M', 1, 100]], '-10 days');

        $this->assertSame(2, (new StoreMetrics($this->store))->forgottenOrders());
    }

    public function test_period_ranges(): void
    {
        $now = CarbonImmutable::parse('2026-03-19 15:00:00');

        [$from, $to, $prevFrom, $prevTo] = StoreMetrics::range('month', $now);
        $this->assertSame('2026-03-01 00:00:00', $from->format('Y-m-d H:i:s'));
        $this->assertSame('2026-02-01 00:00:00', $prevFrom->format('Y-m-d H:i:s'));
        $this->assertTrue($prevTo->lessThan($from));

        [$from, $to, $prevFrom, $prevTo] = StoreMetrics::range('last_month', $now);
        $this->assertSame('2026-02-01', $from->format('Y-m-d'));
        $this->assertSame('2026-02-28', $to->format('Y-m-d'));
        $this->assertSame('2026-01-01', $prevFrom->format('Y-m-d'));
        $this->assertSame('2026-01-31', $prevTo->format('Y-m-d'));

        [$from, $to] = StoreMetrics::range('invalido', $now);
        $this->assertSame('2026-02-17', $from->format('Y-m-d'));
    }

    public function test_metrics_page_loads_for_store_owner(): void
    {
        $this->order(OrderStatus::Confirmed, [['Vestido midi', 'Azul', 'M', 2, 100]]);
        $owner = User::where('email', 'lojista@provador.test')->firstOrFail();

        $this->actingAs($owner)->get('/painel/atelierlume/metricas')
            ->assertOk()
            ->assertSee('Mais pedidos')
            ->assertSee('Mais vendidos')
            ->assertSee('Vestido midi')
            ->assertSee('R$ 200,00');
    }
}
