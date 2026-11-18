<?php

namespace App\Filament\Painel\Pages;

use App\Actions\Billing\CancelSubscription;
use App\Actions\Billing\StartSubscription;
use App\Actions\Billing\SyncSubscriptionPayments;
use App\Models\Store;
use App\Services\Asaas\AsaasException;
use App\Support\Document;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;

/**
 * Página "Assinatura" do painel do lojista.
 */
class Subscription extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static ?string $navigationLabel = 'Assinatura';

    protected static ?string $title = 'Assinatura';

    protected static ?string $slug = 'assinatura';

    protected static ?int $navigationSort = 90;

    protected string $view = 'filament.painel.pages.subscription';

    public string $billing_name = '';

    public string $billing_document = '';

    public string $billing_email = '';

    public string $billing_phone = '';

    public function mount(): void
    {
        $store = $this->store();
        $user = auth()->user();

        $this->billing_name = (string) ($store->billing_name ?? $user?->name ?? '');
        $this->billing_document = (string) ($store->billing_document ?? '');
        $this->billing_email = (string) ($store->billing_email ?? $store->email ?? $user?->email ?? '');
        $this->billing_phone = (string) ($store->billing_phone ?? $store->whatsapp ?? '');
    }

    protected function store(): Store
    {
        /** @var Store */
        return Filament::getTenant()->fresh(['plan']);
    }

    public function subscribe(StartSubscription $start): void
    {
        $this->validate([
            'billing_name' => ['required', 'string', 'max:120'],
            'billing_document' => ['required', 'string'],
            'billing_email' => ['required', 'email'],
            'billing_phone' => ['nullable', 'string', 'max:20'],
        ], [
            'billing_name.required' => 'Informe o nome ou a razão social.',
            'billing_document.required' => 'Informe o CPF ou CNPJ.',
            'billing_email.required' => 'Informe o e-mail para receber as cobranças.',
            'billing_email.email' => 'E-mail inválido.',
        ]);

        if (! Document::isValid($this->billing_document)) {
            throw ValidationException::withMessages(['billing_document' => 'CPF ou CNPJ inválido.']);
        }

        try {
            $invoiceUrl = $start->handle($this->store(), [
                'billing_name' => trim($this->billing_name),
                'billing_document' => Document::digits($this->billing_document),
                'billing_email' => trim($this->billing_email),
                'billing_phone' => $this->billing_phone ?: null,
            ]);
        } catch (AsaasException $e) {
            Notification::make()->title('Não foi possível criar a assinatura')->body($e->getMessage())->danger()->send();

            return;
        } catch (\Throwable $e) {
            report($e);
            Notification::make()->title('Não foi possível falar com o sistema de pagamento')->body('Tente novamente em alguns minutos.')->danger()->send();

            return;
        }

        Notification::make()->title('Assinatura criada!')->body('Abrimos a cobrança para você escolher Pix, boleto ou cartão.')->success()->send();

        if ($invoiceUrl) {
            $this->js('window.open('.json_encode($invoiceUrl).", '_blank')");
        }
    }

    public function refreshPayments(SyncSubscriptionPayments $sync): void
    {
        try {
            $sync->handle($this->store());
            Notification::make()->title('Cobranças atualizadas')->success()->send();
        } catch (\Throwable $e) {
            report($e);
            Notification::make()->title('Não foi possível atualizar agora')->danger()->send();
        }
    }

    public function cancel(CancelSubscription $cancel): void
    {
        try {
            $cancel->handle($this->store());
        } catch (\Throwable $e) {
            report($e);
            Notification::make()->title('Não foi possível cancelar agora')->body('Tente novamente ou fale com o suporte.')->danger()->send();

            return;
        }

        Notification::make()->title('Assinatura cancelada')->body('Sua vitrine continua no ar até o fim do período já pago.')->success()->send();
    }

    protected function getViewData(): array
    {
        $store = $this->store();

        return [
            'store' => $store,
            'plan' => $store->plan,
            'payments' => $store->subscriptionPayments()->limit(12)->get(),
            'productCount' => $store->products()->count(),
        ];
    }
}
