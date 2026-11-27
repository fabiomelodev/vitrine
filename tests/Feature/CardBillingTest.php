<?php

namespace Tests\Feature;

use App\Actions\Billing\SetupCardBilling;
use App\Enums\StoreRole;
use App\Enums\SubscriptionStatus;
use App\Filament\Painel\Pages\Subscription;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class CardBillingTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;

    protected User $owner;

    protected array $card = [
        'holder_name' => 'MARIA L SILVA',
        'number' => '4111111111111111',
        'expiry_month' => '08',
        'expiry_year' => '2030',
        'cvv' => '123',
        'postal_code' => '08700-000',
        'address_number' => '120',
    ];

    protected array $billing = [
        'billing_name' => 'Maria Lojista',
        'billing_document' => '52998224725',
        'billing_email' => 'maria@example.com',
        'billing_phone' => '(11) 98888-7777',
    ];

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
        $this->store = Store::create(['name' => 'Loja Cartão', 'slug' => 'loja-cartao', 'whatsapp' => '(11) 98888-7777']);
        $this->store->users()->attach($this->owner, ['role' => StoreRole::Owner->value]);
        $this->store->refresh();
    }

    public function test_new_card_subscription_during_trial_charges_at_trial_end(): void
    {
        Http::fake([
            '*/customers' => Http::response(['id' => 'cus_c1']),
            '*/subscriptions/sub_c1/payments' => Http::response(['data' => []]),
            '*/subscriptions' => Http::response(['id' => 'sub_c1', 'status' => 'ACTIVE']),
        ]);

        app(SetupCardBilling::class)->handle($this->store, $this->card, $this->billing, '200.100.50.25');

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/subscriptions')
            && $request->method() === 'POST'
            && $request['billingType'] === 'CREDIT_CARD'
            && $request['creditCard']['number'] === '4111111111111111'
            && $request['creditCardHolderInfo']['postalCode'] === '08700000'
            && $request['creditCardHolderInfo']['cpfCnpj'] === '52998224725'
            && $request['remoteIp'] === '200.100.50.25'
            && $request['nextDueDate'] === $this->store->trial_ends_at->toDateString());

        $store = $this->store->fresh();
        $this->assertSame('sub_c1', $store->asaas_subscription_id);
        $this->assertSame(SetupCardBilling::METHOD_CARD, $store->billing_method);
        $this->assertSame('VISA', $store->card_brand);
        $this->assertSame('1111', $store->card_last_digits);
        $this->assertSame(SubscriptionStatus::Trialing, $store->subscription_status);
        $this->assertTrue($store->hasSubscription());
    }

    public function test_switching_from_invoice_to_card_keeps_paid_period_and_cancels_old_subscription(): void
    {
        $paidUntil = now()->addDays(10)->startOfDay();

        $this->store->forceFill([
            'subscription_status' => SubscriptionStatus::Active,
            'asaas_customer_id' => 'cus_old',
            'asaas_subscription_id' => 'sub_old',
            'billing_method' => SetupCardBilling::METHOD_INVOICE,
            'access_until' => $paidUntil,
        ])->save();

        Http::fake([
            '*/subscriptions/sub_old' => Http::response(['deleted' => true]),
            '*/subscriptions/sub_new/payments' => Http::response(['data' => []]),
            '*/subscriptions' => Http::response(['id' => 'sub_new', 'creditCard' => ['creditCardNumber' => '1111', 'creditCardBrand' => 'VISA']]),
        ]);

        app(SetupCardBilling::class)->handle($this->store->fresh(), $this->card, $this->billing, '200.100.50.25');

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/subscriptions')
            && $request['nextDueDate'] === $paidUntil->toDateString());
        Http::assertSent(fn ($request) => $request->method() === 'DELETE' && str_ends_with($request->url(), '/subscriptions/sub_old'));
        Http::assertNotSent(fn ($request) => str_ends_with($request->url(), '/customers'));

        $store = $this->store->fresh();
        $this->assertSame('sub_new', $store->asaas_subscription_id);
        $this->assertSame(SubscriptionStatus::Active, $store->subscription_status);
        $this->assertSame($paidUntil->toDateString(), $store->access_until->toDateString());
    }

    public function test_replacing_card_does_not_create_new_subscription(): void
    {
        $this->store->forceFill([
            'subscription_status' => SubscriptionStatus::Active,
            'asaas_customer_id' => 'cus_1',
            'asaas_subscription_id' => 'sub_card',
            'billing_method' => SetupCardBilling::METHOD_CARD,
            'card_brand' => 'VISA',
            'card_last_digits' => '1111',
            'access_until' => now()->addDays(20),
        ])->save();

        Http::fake([
            '*/subscriptions/sub_card/updateCreditCard' => Http::response(['id' => 'sub_card', 'creditCard' => ['creditCardNumber' => '4444', 'creditCardBrand' => 'MASTERCARD']]),
        ]);

        app(SetupCardBilling::class)->handle($this->store->fresh(), [...$this->card, 'number' => '5555555555554444'], $this->billing, '200.100.50.25');

        Http::assertSentCount(1);

        $store = $this->store->fresh();
        $this->assertSame('sub_card', $store->asaas_subscription_id);
        $this->assertSame('MASTERCARD', $store->card_brand);
        $this->assertSame('4444', $store->card_last_digits);
    }

    public function test_card_helpers(): void
    {
        $this->assertTrue(SetupCardBilling::luhn('4111111111111111'));
        $this->assertFalse(SetupCardBilling::luhn('4111111111111112'));
        $this->assertSame('MASTERCARD', SetupCardBilling::detectBrand('5555555555554444'));
        $this->assertSame('AMEX', SetupCardBilling::detectBrand('378282246310005'));
        $this->assertSame('Visa', SetupCardBilling::brandLabel('VISA'));
    }

    public function test_page_validates_card_and_never_keeps_card_data(): void
    {
        Http::fake();

        $this->actingAs($this->owner);
        Filament::setCurrentPanel(Filament::getPanel('painel'));
        Filament::setTenant($this->store);

        Livewire::test(Subscription::class)
            ->set('billing_document', '529.982.247-25')
            ->set('billing_phone', '(11) 98888-7777')
            ->set('card_holder', 'MARIA')
            ->set('card_number', '4111 1111 1111 1112')
            ->set('card_expiry', '01/20')
            ->set('card_cvv', '123')
            ->set('card_postal_code', '08700-000')
            ->set('card_address_number', '120')
            ->call('subscribe')
            ->assertHasErrors(['card_number', 'card_expiry'])
            ->assertSet('card_number', '')
            ->assertSet('card_cvv', '');

        Http::assertNothingSent();

        $this->get('/painel/loja-cartao/assinatura')->assertOk()->assertSee('Forma de pagamento')->assertSee('Cartão de crédito');
    }
}
