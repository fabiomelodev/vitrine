<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_demo_store_is_seeded_with_catalog_and_orders(): void
    {
        $store = Store::where('slug', 'atelierlume')->firstOrFail();

        $this->assertSame('5511900000000', $store->whatsappInternational());
        $this->assertGreaterThan(0, $store->products()->count());
        $this->assertSame(4, $store->orders()->count());
        $this->assertSame('AL-1001', $store->orders()->orderBy('number')->first()->code);
    }

    public function test_order_numbers_are_sequential_per_store(): void
    {
        $store = Store::where('slug', 'atelierlume')->firstOrFail();

        $order = $store->orders()->create([
            'customer_name' => 'Ana',
            'customer_phone' => '(11) 91111-2222',
        ]);

        $this->assertSame('AL-1005', $order->code);
        $this->assertSame('11911112222', $order->customer_phone);
        $this->assertSame(OrderStatus::New, $order->fresh()->status);
    }

    public function test_store_owner_can_open_own_store_panel(): void
    {
        $owner = User::where('email', 'lojista@provador.test')->firstOrFail();

        $this->actingAs($owner)->get('/painel/atelierlume')
            ->assertOk()
            ->assertSee('Ver meu catálogo')
            ->assertSee(url('/atelierlume'), false);
        // O botão também fica no menu lateral, em todas as páginas do painel.
        $this->actingAs($owner)->get('/painel/atelierlume/products')->assertOk()->assertSee('Ver meu catálogo');
        $this->actingAs($owner)->get('/painel/atelierlume/orders')->assertOk();
    }

    public function test_user_cannot_open_someone_elses_store(): void
    {
        $stranger = User::factory()->create();

        $this->actingAs($stranger)->get('/painel/atelierlume')->assertNotFound();
    }

    public function test_only_super_admin_can_open_admin_panel(): void
    {
        $owner = User::where('email', 'lojista@provador.test')->firstOrFail();
        $admin = User::where('email', 'admin@provador.test')->firstOrFail();

        $this->actingAs($owner)->get('/admin')->assertForbidden();
        $this->actingAs($admin)->get('/admin/stores')->assertOk();
    }

    public function test_suspended_store_blocks_owner_panel(): void
    {
        $owner = User::where('email', 'lojista@provador.test')->firstOrFail();
        Store::where('slug', 'atelierlume')->update(['is_active' => false]);

        $this->actingAs($owner)->get('/painel/atelierlume')->assertForbidden();
    }
}
