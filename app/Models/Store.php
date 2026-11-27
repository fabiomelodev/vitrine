<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

#[Fillable([
    'name', 'slug', 'whatsapp', 'email', 'instagram', 'description', 'logo_path',
    'address', 'city', 'state', 'opening_hours',
    'delivery_info', 'payment_info', 'exchange_policy',
    'order_prefix', 'order_message_template', 'notify_new_orders', 'is_active',
    'plan_id', 'subscription_status', 'trial_ends_at', 'access_until', 'past_due_since',
    'billing_name', 'billing_document', 'billing_email', 'billing_phone',
])]
class Store extends Model
{
    /**
     * Endereços que não podem virar slug de loja, porque já são rotas do sistema.
     */
    public const RESERVED_SLUGS = [
        'admin', 'painel', 'login', 'logout', 'register', 'cadastro', 'api', 'storage',
        'livewire', 'filament', 'up', 'sobre', 'planos', 'precos', 'ajuda', 'contato',
        'termos', 'privacidade', 'blog', 'www', 'app', 'assets', 'build', 'vendor',
        'webhooks', 'assinatura', 'suporte',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'notify_new_orders' => 'boolean',
            'subscription_status' => SubscriptionStatus::class,
            'trial_ends_at' => 'datetime',
            'access_until' => 'datetime',
            'past_due_since' => 'datetime',
            'order_sequence' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Store $store): void {
            $store->slug = Str::slug($store->slug ?: $store->name);
            $store->order_prefix = $store->order_prefix ?: static::makeOrderPrefix($store->name);

            // Loja nova: plano padrão e período de teste.
            $plan = $store->plan_id ? Plan::find($store->plan_id) : Plan::default();
            $store->plan_id ??= $plan?->getKey();
            $store->subscription_status ??= SubscriptionStatus::Trialing;

            if ($store->subscription_status === SubscriptionStatus::Trialing && ! $store->trial_ends_at) {
                $store->trial_ends_at = now()->addDays($plan?->trial_days ?? 14);
            }
        });

        static::saving(function (Store $store): void {
            $store->whatsapp = preg_replace('/\D/', '', (string) $store->whatsapp);
        });
    }

    public static function makeOrderPrefix(string $name): string
    {
        $initials = collect(preg_split('/\s+/', Str::ascii($name)))
            ->filter()
            ->map(fn (string $word) => Str::upper(Str::substr($word, 0, 1)))
            ->take(3)
            ->implode('');

        return $initials !== '' ? $initials : 'PD';
    }

    /** Dias de tolerância depois do vencimento antes de tirar a loja do ar. */
    public const GRACE_DAYS = 5;

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function subscriptionPayments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class)->latest('due_date');
    }

    /**
     * A loja pode usar a vitrine e o painel?
     */
    public function hasActiveAccess(): bool
    {
        $now = now();

        return match ($this->subscription_status ?? SubscriptionStatus::Trialing) {
            SubscriptionStatus::Courtesy => true,
            // Rede de segurança: se o aviso de atraso do Asaas não chegar, a loja
            // sai do ar sozinha depois do período pago + tolerância.
            SubscriptionStatus::Active => ! $this->access_until
                || $now->lt($this->access_until->copy()->addDays(self::GRACE_DAYS)),
            // Quem já assinou durante o teste ganha a tolerância para o 1º pagamento compensar.
            SubscriptionStatus::Trialing => ! $this->trial_ends_at
                || $now->lt($this->trial_ends_at->copy()->addDays($this->asaas_subscription_id ? self::GRACE_DAYS : 0)),
            SubscriptionStatus::PastDue => $this->past_due_since
                && $now->lt($this->past_due_since->copy()->addDays(self::GRACE_DAYS)),
            SubscriptionStatus::Canceled => $this->access_until && $now->lt($this->access_until),
        };
    }

    /**
     * Assinatura ativa cujo período pago já terminou sem novo pagamento confirmado.
     */
    public function paidPeriodOverdue(): bool
    {
        return $this->subscription_status === SubscriptionStatus::Active
            && $this->access_until
            && now()->gte($this->access_until);
    }

    /**
     * Quando a vitrine sai do ar se nada for pago (null = não está prevista).
     */
    public function blocksAt(): ?\Illuminate\Support\Carbon
    {
        return match ($this->subscription_status ?? SubscriptionStatus::Trialing) {
            SubscriptionStatus::PastDue => $this->past_due_since?->copy()->addDays(self::GRACE_DAYS),
            SubscriptionStatus::Active => $this->access_until?->copy()->addDays(self::GRACE_DAYS),
            SubscriptionStatus::Trialing => $this->trial_ends_at?->copy()->addDays($this->asaas_subscription_id ? self::GRACE_DAYS : 0),
            SubscriptionStatus::Canceled => $this->access_until?->copy(),
            SubscriptionStatus::Courtesy => null,
        };
    }

    /**
     * Fim do período coberto pelo último pagamento válido (vencimento + 1 mês).
     */
    public function paidThrough(): ?\Illuminate\Support\Carbon
    {
        $lastPaid = $this->subscriptionPayments()
            ->getQuery()
            ->reorder()
            ->whereIn('status', SubscriptionPayment::PAID_STATUSES)
            ->max('due_date');

        return $lastPaid ? \Illuminate\Support\Carbon::parse($lastPaid)->addMonth() : null;
    }

    public function isOnTrial(): bool
    {
        return $this->subscription_status === SubscriptionStatus::Trialing;
    }

    public function trialDaysLeft(): ?int
    {
        if (! $this->isOnTrial() || ! $this->trial_ends_at) {
            return null;
        }

        return max(0, (int) ceil(now()->diffInHours($this->trial_ends_at, false) / 24));
    }

    public function hasSubscription(): bool
    {
        return filled($this->asaas_subscription_id)
            && in_array($this->subscription_status, [SubscriptionStatus::Trialing, SubscriptionStatus::Active, SubscriptionStatus::PastDue], true);
    }

    public function productLimit(): ?int
    {
        return $this->plan?->max_products;
    }

    public function reachedProductLimit(): bool
    {
        $limit = $this->productLimit();

        return $limit !== null && $this->products()->count() >= $limit;
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class)->orderBy('position');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function banners(): HasMany
    {
        return $this->hasMany(Banner::class)->orderBy('position');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Reserva o próximo número de pedido da loja de forma segura
     * (dois clientes enviando ao mesmo tempo não recebem o mesmo número).
     */
    public function reserveOrderNumber(): int
    {
        return DB::transaction(function (): int {
            $store = static::query()->lockForUpdate()->findOrFail($this->getKey());
            $store->increment('order_sequence');

            return $store->order_sequence;
        });
    }

    /**
     * Número no formato internacional usado pelo wa.me (ex.: 5511900000000).
     */
    public function whatsappInternational(): string
    {
        $digits = preg_replace('/\D/', '', (string) $this->whatsapp);

        return str_starts_with($digits, '55') && strlen($digits) > 11 ? $digits : '55'.$digits;
    }

    public function whatsappUrl(?string $message = null): string
    {
        $url = 'https://wa.me/'.$this->whatsappInternational();

        return $message ? $url.'?text='.rawurlencode($message) : $url;
    }

    /**
     * E-mails que recebem o aviso de pedido novo: o e-mail da loja ou, se não houver, o dos donos.
     *
     * @return array<int, string>
     */
    public function orderNotificationEmails(): array
    {
        if (filled($this->email)) {
            return [$this->email];
        }

        return $this->users()
            ->wherePivot('role', 'owner')
            ->pluck('email')
            ->filter()
            ->values()
            ->all();
    }

    /**
     * E-mails que recebem os avisos da assinatura: o e-mail de cobrança e os donos da loja.
     *
     * @return array<int, string>
     */
    public function billingEmails(): array
    {
        return collect([$this->billing_email])
            ->merge($this->users()->wherePivot('role', 'owner')->pluck('email'))
            ->filter()
            ->map(fn ($email) => mb_strtolower(trim($email)))
            ->unique()
            ->values()
            ->all();
    }

    public function publicUrl(): string
    {
        return url($this->slug);
    }
}
