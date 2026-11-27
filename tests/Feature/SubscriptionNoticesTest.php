<?php

namespace Tests\Feature;

use App\Actions\Billing\CancelSubscription;
use App\Enums\StoreRole;
use App\Enums\SubscriptionStatus;
use App\Models\Store;
use App\Models\User;
use App\Notifications\SubscriptionNotice;
use App\Support\SubscriptionMailer;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SubscriptionNoticesTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        config([
            'services.asaas.key' => 'test-key',
            'services.asaas.url' => 'https://api-sandbox.asaas.com/v3',
            'services.asaas.webhook_token' => 'segredo-webhook',
        ]);

        $owner = User::factory()->create(['email' => 'dona@loja.test']);
        $this->store = Store::create(['name' => 'Loja Avisos', 'slug' => 'loja-avisos', 'whatsapp' => '(11) 98888-7777']);
        $this->store->users()->attach($owner, ['role' => StoreRole::Owner->value]);
        $this->store->forceFill(['billing_email' => 'Financeiro@Loja.test'])->save();
        $this->store->refresh();

        Notification::fake();
    }

    protected function assertNoticeSent(string $type, int $times = 1): void
    {
        $sent = Notification::sent(new AnonymousNotifiable, SubscriptionNotice::class, fn (SubscriptionNotice $n) => $n->type === $type);

        $this->assertCount($times, $sent, "Aviso {$type}");
    }

    public function test_billing_emails_include_billing_and_owner(): void
    {
        $this->assertSame(['financeiro@loja.test', 'dona@loja.test'], $this->store->billingEmails());
    }

    public function test_trial_reminders_are_sent_once_each(): void
    {
        $this->store->forceFill(['trial_ends_at' => now()->addDays(2)->addHour()])->save();

        $this->assertSame(SubscriptionNotice::TRIAL_ENDING, SubscriptionMailer::check($this->store->fresh()));
        $this->assertNull(SubscriptionMailer::check($this->store->fresh()), 'Não repete.');

        $this->travelTo($this->store->trial_ends_at->copy()->subHours(12));
        $this->assertSame(SubscriptionNotice::TRIAL_LAST_DAY, SubscriptionMailer::check($this->store->fresh()));

        $this->assertNoticeSent(SubscriptionNotice::TRIAL_ENDING);
        $this->assertNoticeSent(SubscriptionNotice::TRIAL_LAST_DAY);
    }

    public function test_offline_notice_after_trial_ends_but_not_for_old_blocks(): void
    {
        $this->store->forceFill(['trial_ends_at' => now()->subDay()])->save();
        $this->assertSame(SubscriptionNotice::OFFLINE, SubscriptionMailer::check($this->store->fresh()));

        Notification::assertSentOnDemand(SubscriptionNotice::class, fn (SubscriptionNotice $n) => ($n->data['reason'] ?? null) === 'trial');

        $this->store->forceFill(['trial_ends_at' => now()->subDays(30)])->save();
        $this->assertNull(SubscriptionMailer::check($this->store->fresh()));
    }

    public function test_overdue_without_webhook_is_detected_by_daily_check(): void
    {
        $this->store->forceFill([
            'subscription_status' => SubscriptionStatus::Active,
            'access_until' => now()->subDay(),
        ])->save();

        $this->assertSame(SubscriptionNotice::OVERDUE, SubscriptionMailer::check($this->store->fresh()));
        $this->assertNull(SubscriptionMailer::check($this->store->fresh()));
    }

    public function test_courtesy_store_gets_no_notices(): void
    {
        $this->store->forceFill(['subscription_status' => SubscriptionStatus::Courtesy, 'trial_ends_at' => now()->addDay()])->save();

        $this->assertNull(SubscriptionMailer::check($this->store->fresh()));
        Notification::assertNothingSent();
    }

    public function test_webhooks_send_overdue_and_paid_notices_once(): void
    {
        $this->store->forceFill(['asaas_customer_id' => 'cus_1', 'asaas_subscription_id' => 'sub_1'])->save();
        $headers = ['asaas-access-token' => 'segredo-webhook'];
        $payment = [
            'id' => 'pay_1', 'customer' => 'cus_1', 'subscription' => 'sub_1', 'value' => 49.9,
            'status' => 'OVERDUE', 'dueDate' => now()->subDay()->toDateString(), 'billingType' => 'BOLETO',
        ];

        $this->postJson('/webhooks/asaas', ['event' => 'PAYMENT_OVERDUE', 'payment' => $payment], $headers)->assertOk();
        $this->postJson('/webhooks/asaas', ['event' => 'PAYMENT_OVERDUE', 'payment' => $payment], $headers)->assertOk();
        $this->assertNoticeSent(SubscriptionNotice::OVERDUE);

        $paid = [...$payment, 'status' => 'CONFIRMED', 'paymentDate' => now()->toDateString()];
        $this->postJson('/webhooks/asaas', ['event' => 'PAYMENT_CONFIRMED', 'payment' => $paid], $headers)->assertOk();
        $this->postJson('/webhooks/asaas', ['event' => 'PAYMENT_RECEIVED', 'payment' => [...$paid, 'status' => 'RECEIVED']], $headers)->assertOk();

        $this->assertNoticeSent(SubscriptionNotice::PAID);
        Notification::assertSentOnDemand(SubscriptionNotice::class, fn (SubscriptionNotice $n) => $n->type === SubscriptionNotice::PAID && $n->data['reactivated'] === true);
    }

    public function test_cancel_sends_notice(): void
    {
        Http::fake(['*' => Http::response(['deleted' => true])]);
        $this->store->forceFill(['asaas_subscription_id' => 'sub_5'])->save();

        app(CancelSubscription::class)->handle($this->store->fresh());

        $this->assertNoticeSent(SubscriptionNotice::CANCELED);
    }

    public function test_every_notice_renders_in_portuguese(): void
    {
        $this->store->forceFill(['trial_ends_at' => now()->addDays(3)])->save();

        $cases = [
            [SubscriptionNotice::TRIAL_ENDING, ['days' => 3], 'Assinar agora'],
            [SubscriptionNotice::TRIAL_LAST_DAY, [], 'último dia do teste'],
            [SubscriptionNotice::OVERDUE, ['blocks_at' => now()->addDays(5)], 'sua vitrine sai do ar em'],
            [SubscriptionNotice::OFFLINE, ['reason' => 'payment'], 'Reativar minha vitrine'],
            [SubscriptionNotice::PAID, ['value' => 49.9, 'paid_until' => now()->addMonth(), 'reactivated' => true], 'R$ 49,90'],
            [SubscriptionNotice::CANCELED, ['until' => now()->addDays(10)], 'continua no ar até'],
        ];

        foreach ($cases as [$type, $data, $expected]) {
            $html = (string) (new SubscriptionNotice($this->store, $type, $data))
                ->toMail(new AnonymousNotifiable)
                ->render();

            $this->assertStringContainsString($expected, $html, $type);
            $this->assertStringContainsString('Equipe', $html, $type);
            $this->assertStringNotContainsString('Regards', $html, $type);
        }
    }
}
