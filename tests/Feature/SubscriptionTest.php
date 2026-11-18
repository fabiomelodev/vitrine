<?php

namespace Tests\Feature;

use App\Actions\Billing\StartSubscription;
use App\Enums\StoreRole;
use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;

    protected User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        config([
            'services.asaas.key' => 'test-key',
            'services.asaas.url' => 'https://api-sandbox.asaas.com/v3',
            'services.asaas.webhook_token' => 'segredo-webhook',
        ]);

        $this->owner = User::factory()->create();
        $this->store = Store::create(['name' => 'Loja Teste', 'slug' => 'loja-teste', 'whatsapp' => '(11) 98888-7777']);
        $this->store->users()->attach($this->owner, ['role' => StoreRole::Owner->value]);
        $this->store->refresh();
    }

    public function test_new_store_starts_with_default_plan_and_14_day_trial(): void
    {
        $plan = Plan::default();

        $this->assertSame($plan->id, $this->store->plan_id);
        $this->assertSame(200, $this->store->productLimit());
        $this->assertSame(SubscriptionStatus::Trialing, $this->store->subscription_status);
        $this->assertSame(14, $this->store->trialDaysLeft());
        $this->assertTrue($this->store->hasActiveAccess());
    }

    public function test_expired_trial_takes_store_offline_and_limits_panel_to_subscription_page(): void
    {
        $this->travel(15)->days();

        $this->assertFalse($this->store->fresh()->hasActiveAccess());

        $this->get('/loja-teste')->assertStatus(503);

        $this->actingAs($this->owner)
            ->get('/painel/loja-teste/products')
            ->assertRedirect(url('painel/loja-teste/assinatura'));

        $this->actingAs($this->owner)
            ->get('/painel/loja-teste/assinatura')
            ->assertOk()
            ->assertSee('O período de teste terminou');
    }

    public function test_courtesy_store_never_expires(): void
    {
        $this->store->update(['subscription_status' => SubscriptionStatus::Courtesy]);
        $this->travel(400)->days();

        $this->assertTrue($this->store->fresh()->hasActiveAccess());
    }

    public function test_product_limit_blocks_new_products(): void
    {
        Plan::query()->update(['max_products' => 2]);
        $this->store->products()->create(['name' => 'Peça 1', 'price' => 10]);
        $this->store->products()->create(['name' => 'Peça 2', 'price' => 10]);

        $this->assertTrue($this->store->fresh()->reachedProductLimit());

        $this->actingAs($this->owner)
            ->get('/painel/loja-teste/products/create')
            ->assertForbidden();
    }

    public function test_start_subscription_creates_customer_subscription_and_returns_invoice(): void
    {
        Http::fake([
            '*/customers' => Http::response(['id' => 'cus_123']),
            '*/subscriptions/sub_123/payments' => Http::response(['data' => [[
                'id' => 'pay_1', 'status' => 'PENDING', 'value' => 49.9, 'billingType' => 'UNDEFINED',
                'dueDate' => now()->addDays(14)->toDateString(), 'invoiceUrl' => 'https://sandbox.asaas.com/i/pay_1',
            ]]]),
            '*/subscriptions' => Http::response(['id' => 'sub_123', 'status' => 'ACTIVE']),
        ]);

        $url = app(StartSubscription::class)->handle($this->store, [
            'billing_name' => 'Maria Lojista',
            'billing_document' => '52998224725',
            'billing_email' => 'maria@example.com',
            'billing_phone' => '(11) 98888-7777',
        ]);

        $this->assertSame('https://sandbox.asaas.com/i/pay_1', $url);

        $store = $this->store->fresh();
        $this->assertSame('cus_123', $store->asaas_customer_id);
        $this->assertSame('sub_123', $store->asaas_subscription_id);
        $this->assertTrue($store->hasSubscription());
        $this->assertSame(1, $store->subscriptionPayments()->count());

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/subscriptions')
            && $request['billingType'] === 'UNDEFINED'
            && $request['cycle'] === 'MONTHLY'
            && (float) $request['value'] === 49.9
            && $request['nextDueDate'] === $this->store->trial_ends_at->toDateString()
            && $request->hasHeader('access_token', 'test-key'));
    }

    public function test_webhook_rejects_wrong_token(): void
    {
        $this->postJson('/webhooks/asaas', ['event' => 'PAYMENT_RECEIVED'], ['asaas-access-token' => 'errado'])
            ->assertUnauthorized();
    }

    public function test_webhook_payment_received_activates_and_overdue_suspends_after_grace(): void
    {
        $this->store->forceFill(['asaas_customer_id' => 'cus_9', 'asaas_subscription_id' => 'sub_9'])->save();

        $payment = [
            'id' => 'pay_9', 'customer' => 'cus_9', 'subscription' => 'sub_9', 'value' => 49.9,
            'status' => 'RECEIVED', 'dueDate' => now()->toDateString(), 'paymentDate' => now()->toDateString(),
            'invoiceUrl' => 'https://sandbox.asaas.com/i/pay_9', 'billingType' => 'PIX',
        ];

        $this->postJson('/webhooks/asaas', ['event' => 'PAYMENT_RECEIVED', 'payment' => $payment], ['asaas-access-token' => 'segredo-webhook'])
            ->assertOk();

        $store = $this->store->fresh();
        $this->assertSame(SubscriptionStatus::Active, $store->subscription_status);
        $this->assertTrue($store->hasActiveAccess());
        $this->assertTrue($store->subscriptionPayments()->first()->isPaid());

        $overdue = [...$payment, 'id' => 'pay_10', 'status' => 'OVERDUE', 'paymentDate' => null];
        $this->postJson('/webhooks/asaas', ['event' => 'PAYMENT_OVERDUE', 'payment' => $overdue], ['asaas-access-token' => 'segredo-webhook'])
            ->assertOk();

        $store = $this->store->fresh();
        $this->assertSame(SubscriptionStatus::PastDue, $store->subscription_status);
        $this->assertTrue($store->hasActiveAccess(), 'Ainda dentro da tolerância.');

        $this->travel(Store::GRACE_DAYS + 1)->days();
        $this->assertFalse($store->fresh()->hasActiveAccess());
    }

    public function test_site_pages_and_register_terms(): void
    {
        $this->get('/')->assertOk()->assertSee('Criar minha loja grátis')->assertSee('R$ 49,90');
        $this->get('/termos')->assertOk()->assertSee('Termos de');
        $this->get('/privacidade')->assertOk()->assertSee('LGPD');
        $this->get('/painel/register')->assertOk()->assertSee('Termos de uso');
    }
}
