<?php

namespace Tests\Feature;

use App\Enums\SubscriptionStatus;
use App\Filament\Admin\Widgets\AttentionTable;
use App\Filament\Admin\Widgets\RecentPaymentsTable;
use App\Filament\Admin\Widgets\SubscriptionOverview;
use App\Filament\Admin\Widgets\TrialsEndingTable;
use App\Models\Store;
use App\Models\User;
use App\Support\PlatformMetrics;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PlatformMetricsTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, Store> */
    protected array $stores = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        Store::query()->update(['subscription_status' => SubscriptionStatus::Courtesy->value]); // lojas do seeder fora da conta

        $make = function (string $slug, array $attributes) {
            $store = Store::create(['name' => 'Loja '.$slug, 'slug' => $slug, 'whatsapp' => '(11) 98888-7777']);
            $store->forceFill($attributes)->save();

            return $this->stores[$slug] = $store->fresh();
        };

        $make('pagante', ['subscription_status' => SubscriptionStatus::Active, 'access_until' => now()->addDays(20), 'asaas_subscription_id' => 'sub_a']);
        $make('atrasada', ['subscription_status' => SubscriptionStatus::PastDue, 'past_due_since' => now()->subDay(), 'asaas_subscription_id' => 'sub_b']);
        $make('teste', ['trial_ends_at' => now()->addDays(3)]);
        $make('teste-longe', ['trial_ends_at' => now()->addDays(12)]);
        $make('fora', ['trial_ends_at' => now()->subDays(2)]);
        $make('cancelada', ['subscription_status' => SubscriptionStatus::Canceled, 'access_until' => now()->addDays(5)]);

        // Pagante com preço antigo (R$ 39,90) e atrasada com o preço atual.
        $this->stores['pagante']->subscriptionPayments()->create([
            'asaas_payment_id' => 'pay_a', 'status' => 'RECEIVED', 'value' => 39.90,
            'due_date' => now()->subDays(10)->toDateString(), 'paid_at' => now()->toDateString(), 'billing_type' => 'PIX',
        ]);
        $this->stores['atrasada']->subscriptionPayments()->create([
            'asaas_payment_id' => 'pay_b', 'status' => 'OVERDUE', 'value' => 49.90,
            'due_date' => now()->subDay()->toDateString(),
        ]);
    }

    public function test_classifies_stores_and_computes_mrr(): void
    {
        $this->assertSame(PlatformMetrics::PAYING, PlatformMetrics::classify($this->stores['pagante']));
        $this->assertSame(PlatformMetrics::OVERDUE, PlatformMetrics::classify($this->stores['atrasada']));
        $this->assertSame(PlatformMetrics::TRIAL, PlatformMetrics::classify($this->stores['teste']));
        $this->assertSame(PlatformMetrics::OFFLINE, PlatformMetrics::classify($this->stores['fora']));
        $this->assertSame(PlatformMetrics::CANCELED, PlatformMetrics::classify($this->stores['cancelada']));

        $overview = PlatformMetrics::overview();
        $this->assertSame(1, $overview['counts'][PlatformMetrics::PAYING]);
        $this->assertSame(2, $overview['counts'][PlatformMetrics::TRIAL]);
        $this->assertSame(89.8, $overview['mrr']); // 39,90 (preço antigo) + 49,90
    }

    public function test_received_and_new_subscribers(): void
    {
        $now = CarbonImmutable::now();

        $this->assertEquals(39.9, PlatformMetrics::received($now->startOfMonth(), $now));
        $this->assertSame(1, PlatformMetrics::newSubscribers($now->startOfMonth(), $now));
    }

    public function test_trials_ending_and_attention_lists(): void
    {
        $ending = PlatformMetrics::trialsEndingQuery()->pluck('slug')->all();
        $this->assertSame(['teste'], $ending);

        $attention = PlatformMetrics::attentionQuery()->pluck('slug')->all();
        $this->assertEqualsCanonicalizing(['atrasada', 'fora'], $attention);
    }

    public function test_trial_conversion(): void
    {
        $conversion = PlatformMetrics::trialConversion();

        $this->assertSame(1, $conversion['ended']); // só a loja "fora" terminou o teste no período
        $this->assertSame(0, $conversion['converted']);
    }

    public function test_dashboard_widgets_render_for_super_admin(): void
    {
        $admin = User::where('email', 'admin@provador.test')->firstOrFail();
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->get('/admin')->assertOk();

        Livewire::test(SubscriptionOverview::class)->assertSee('Receita mensal (MRR)')->assertSee('R$ 89,80');
        Livewire::test(TrialsEndingTable::class)->assertSee('Loja teste')->assertDontSee('Loja teste-longe');
        Livewire::test(AttentionTable::class)->assertSee('Loja atrasada')->assertSee('Fora do ar');
        Livewire::test(RecentPaymentsTable::class)->assertSee('Loja pagante');
    }
}
