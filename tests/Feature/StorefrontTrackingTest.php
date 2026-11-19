<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Livewire\Storefront\ProductGrid;
use App\Livewire\Storefront\ProductOptions;
use App\Models\Product;
use App\Models\Store;
use App\Models\StorefrontEvent;
use App\Models\User;
use App\Support\StoreMetrics;
use App\Support\StorefrontTracker;
use App\Support\VisitMetrics;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Livewire\Livewire;
use Tests\TestCase;

class StorefrontTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->store = Store::where('slug', 'atelierlume')->firstOrFail();
    }

    protected function events(string $type)
    {
        return StorefrontEvent::where('store_id', $this->store->id)->where('type', $type);
    }

    public function test_visit_is_counted_once_per_day_with_its_source(): void
    {
        $this->get('/atelierlume?origem=instagram')->assertOk();
        $this->get('/atelierlume/produtos')->assertOk();
        $this->get('/atelierlume')->assertOk();

        $this->assertSame(1, $this->events(StorefrontEvent::VISIT)->count());
        $this->assertSame('instagram', $this->events(StorefrontEvent::VISIT)->value('source'));
    }

    public function test_bots_and_store_team_are_not_counted(): void
    {
        $this->withHeader('User-Agent', 'WhatsApp/2.23.20 A')->get('/atelierlume')->assertOk();
        $this->withHeader('User-Agent', 'Mozilla/5.0 (compatible; Googlebot/2.1)')->get('/atelierlume')->assertOk();

        $owner = User::where('email', 'lojista@provador.test')->firstOrFail();
        $this->withHeader('User-Agent', 'Mozilla/5.0 Chrome/120')->actingAs($owner)->get('/atelierlume')->assertOk();

        $this->assertSame(0, StorefrontEvent::count());
    }

    public function test_product_view_is_counted_once(): void
    {
        $product = $this->store->products()->where('is_visible', true)->firstOrFail();

        $this->get('/atelierlume/p/'.$product->slug)->assertOk();
        $this->get('/atelierlume/p/'.$product->slug)->assertOk();

        $this->assertSame(1, $this->events(StorefrontEvent::PRODUCT_VIEW)->where('product_id', $product->id)->count());
    }

    public function test_search_records_term_and_results(): void
    {
        Livewire::test(ProductGrid::class, ['storeId' => $this->store->id, 'searchMode' => true])
            ->set('q', '  Macacão  Jeans ')
            ->call('loadMore'); // não conta de novo

        $event = $this->events(StorefrontEvent::SEARCH)->sole();
        $this->assertSame('macacão jeans', $event->detail);
        $this->assertSame(0, $event->value);
    }

    public function test_favorite_endpoint_counts_only_store_products(): void
    {
        $product = $this->store->products()->where('is_visible', true)->firstOrFail();

        $this->post('/atelierlume/evento', ['type' => 'favorite', 'product' => $product->id])->assertNoContent();
        $this->post('/atelierlume/evento', ['type' => 'favorite', 'product' => 999999])->assertNoContent();
        $this->post('/atelierlume/evento', ['type' => 'hack', 'product' => $product->id])->assertNoContent();

        $this->assertSame(1, $this->events(StorefrontEvent::FAVORITE)->count());
    }

    public function test_sold_out_size_tap_and_restock_request(): void
    {
        $product = Product::where('slug', 'vestido-midi-de-linho')->with('variants')->firstOrFail();
        $product->variants()->where('size', 'M')->update(['is_available' => false]);

        $component = Livewire::test(ProductOptions::class, ['storeId' => $this->store->id, 'productId' => $product->id]);
        $color = $component->get('color');

        $component->call('selectSize', 'M')
            ->assertSet('size', null)
            ->assertSet('soldOutSize', 'M')
            ->assertSee('Avise-me quando o M voltar');

        $tap = $this->events(StorefrontEvent::SOLDOUT_TAP)->sole();
        $this->assertSame($color.' / M', $tap->detail);

        $this->get('/atelierlume/p/'.$product->slug.'/avise-me?cor='.urlencode($color).'&tam=M')
            ->assertRedirectContains('https://wa.me/');

        $this->assertSame(1, $this->events(StorefrontEvent::RESTOCK_REQUEST)->count());
    }

    public function test_add_to_cart_is_recorded(): void
    {
        $product = Product::where('slug', 'vestido-midi-de-linho')->firstOrFail();

        Livewire::test(ProductOptions::class, ['storeId' => $this->store->id, 'productId' => $product->id])
            ->call('selectSize', 'M')
            ->call('addToCart');

        $this->assertSame(1, $this->events(StorefrontEvent::ADD_TO_CART)->where('product_id', $product->id)->count());
    }

    public function test_source_detection(): void
    {
        $make = fn (array $query = [], array $server = []) => Request::create('https://ct.test/atelierlume', 'GET', $query, [], [], $server);

        $this->assertSame('whatsapp', StorefrontTracker::detectSource($make(['origem' => 'status'])));
        $this->assertSame('instagram', StorefrontTracker::detectSource($make([], ['HTTP_REFERER' => 'https://l.instagram.com/?u=x'])));
        $this->assertSame('google', StorefrontTracker::detectSource($make([], ['HTTP_REFERER' => 'https://www.google.com.br/'])));
        $this->assertSame('outros', StorefrontTracker::detectSource($make([], ['HTTP_REFERER' => 'https://blog.exemplo.com/'])));
        $this->assertNull(StorefrontTracker::detectSource($make([], ['HTTP_REFERER' => 'https://ct.test/atelierlume/produtos'])));
        $this->assertSame('instagram', StorefrontTracker::detectSource($make([], ['HTTP_USER_AGENT' => 'Mozilla/5.0 Instagram 300.0'])));
        $this->assertSame('direto', StorefrontTracker::detectSource($make()));
        $this->assertSame('Instagram', StorefrontTracker::sourceLabel('instagram'));
    }

    public function test_funnel_and_sources(): void
    {
        $this->store->orders()->delete();
        $product = $this->store->products()->firstOrFail();

        $add = fn (string $type, string $visitor, ?string $source = null, ?int $productId = null) => StorefrontEvent::create([
            'store_id' => $this->store->id, 'type' => $type, 'visitor' => $visitor, 'source' => $source, 'product_id' => $productId,
        ]);

        foreach (['a', 'b', 'c', 'd'] as $v) {
            $add(StorefrontEvent::VISIT, str_repeat($v, 16), $v === 'd' ? null : 'instagram');
        }
        $add(StorefrontEvent::PRODUCT_VIEW, str_repeat('a', 16), null, $product->id);
        $add(StorefrontEvent::PRODUCT_VIEW, str_repeat('b', 16), null, $product->id);
        $add(StorefrontEvent::ADD_TO_CART, str_repeat('a', 16), null, $product->id);

        $order = $this->store->orders()->create(['customer_name' => 'Ana', 'customer_phone' => '11900000000', 'status' => OrderStatus::Confirmed, 'total' => 100]);
        $order->forceFill(['source' => 'instagram'])->save();

        $metrics = new VisitMetrics($this->store);
        [$from, $to] = StoreMetrics::range('30');

        $this->assertSame([4, 2, 1, 1, 1], array_column($metrics->funnel($from, $to), 'value'));
        $this->assertSame([100, 50, 25, 25, 25], array_column($metrics->funnel($from, $to), 'rate'));

        $sources = $metrics->sources($from, $to);
        $this->assertSame('instagram', $sources[0]['source']);
        $this->assertSame(3, $sources[0]['visitors']);
        $this->assertSame(1, $sources[0]['orders']);
        $this->assertSame('Direto ou sem origem', $sources[1]['label']);

        $viewed = $metrics->mostViewed($from, $to);
        $this->assertSame(2, $viewed[0]['views']);
        $this->assertSame(31, count($metrics->visitorsByDay($from, $to)));
    }

    public function test_metrics_tabs_load(): void
    {
        $owner = User::where('email', 'lojista@provador.test')->firstOrFail();
        $this->actingAs($owner);

        $this->get('/painel/atelierlume/metricas?aba=visitas')->assertOk()->assertSee('Funil da vitrine')->assertSee('?origem=instagram');
        $this->get('/painel/atelierlume/metricas?aba=produtos')->assertOk()->assertSee('Buscas sem resultado')->assertSee('Procura por esgotados');
        $this->get('/painel/atelierlume/metricas')->assertOk()->assertSee('Mais vendidos');
    }
}
