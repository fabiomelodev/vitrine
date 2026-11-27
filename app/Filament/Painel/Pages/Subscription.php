<?php

namespace App\Filament\Painel\Pages;

use App\Actions\Billing\CancelSubscription;
use App\Actions\Billing\SetupCardBilling;
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

    /** 'card' = cobrança automática no cartão; 'invoice' = Pix ou boleto a cada mês. */
    public string $payment_method = 'card';

    // Dados do cartão: só ficam na memória durante o envio ao Asaas e são apagados em seguida.
    public string $card_holder = '';

    public string $card_number = '';

    public string $card_expiry = '';

    public string $card_cvv = '';

    public string $card_postal_code = '';

    public string $card_address_number = '';

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

    public function subscribe(StartSubscription $start, SetupCardBilling $card): void
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

        if ($this->payment_method === 'card') {
            $this->submitCard($card, 'Assinatura criada!', 'O cartão será cobrado automaticamente todo mês.');

            return;
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

    /**
     * Ativa o cartão automático numa assinatura de Pix/boleto, ou troca o cartão atual.
     */
    public function saveCard(SetupCardBilling $card): void
    {
        $store = $this->store();
        $replacing = $store->billing_method === SetupCardBilling::METHOD_CARD && $store->hasSubscription();

        $this->submitCard(
            $card,
            $replacing ? 'Cartão atualizado' : 'Cobrança automática ativada',
            $replacing ? 'As próximas cobranças serão feitas no novo cartão.' : 'A partir de agora, a mensalidade é cobrada no cartão todo mês.',
        );
    }

    protected function submitCard(SetupCardBilling $card, string $successTitle, string $successBody): void
    {
        try {
            $data = $this->validateCard();

            $card->handle($this->store(), $data, [
                'billing_name' => trim($this->billing_name),
                'billing_document' => Document::digits($this->billing_document),
                'billing_email' => trim($this->billing_email),
                'billing_phone' => $this->billing_phone,
            ], (string) request()->ip());
        } catch (ValidationException $e) {
            throw $e;
        } catch (AsaasException $e) {
            Notification::make()->title('Não foi possível usar este cartão')->body($e->getMessage())->danger()->send();

            return;
        } catch (\Throwable $e) {
            report($e);
            Notification::make()->title('Não foi possível falar com o sistema de pagamento')->body('Confira em "Atualizar cobranças" antes de tentar de novo, para não duplicar.')->danger()->send();

            return;
        } finally {
            // Nunca mantém número, validade e CVV no componente depois do envio.
            $this->card_number = '';
            $this->card_expiry = '';
            $this->card_cvv = '';
        }

        Notification::make()->title($successTitle)->body($successBody)->success()->send();
        $this->dispatch('card-saved');
    }

    /**
     * @return array{holder_name: string, number: string, expiry_month: string, expiry_year: string, cvv: string, postal_code: string, address_number: string}
     */
    protected function validateCard(): array
    {
        $this->validate([
            'billing_name' => ['required', 'string', 'max:120'],
            'billing_document' => ['required', 'string'],
            'billing_email' => ['required', 'email'],
            'billing_phone' => ['required', 'string', 'max:20'],
            'card_holder' => ['required', 'string', 'max:80'],
            'card_number' => ['required', 'string'],
            'card_expiry' => ['required', 'string'],
            'card_cvv' => ['required', 'regex:/^\d{3,4}$/'],
            'card_postal_code' => ['required', 'string'],
            'card_address_number' => ['required', 'string', 'max:10'],
        ], [
            'billing_phone.required' => 'Informe um telefone: é exigido para pagar no cartão.',
            'card_holder.required' => 'Informe o nome impresso no cartão.',
            'card_number.required' => 'Informe o número do cartão.',
            'card_expiry.required' => 'Informe a validade.',
            'card_cvv.required' => 'Informe o código de segurança.',
            'card_cvv.regex' => 'Código de segurança inválido.',
            'card_postal_code.required' => 'Informe o CEP do titular.',
            'card_address_number.required' => 'Informe o número do endereço.',
        ]);

        $errors = [];
        $number = preg_replace('/\D/', '', $this->card_number);

        if (! SetupCardBilling::luhn($number)) {
            $errors['card_number'] = 'Número de cartão inválido. Confira os dígitos.';
        }

        $expiry = null;
        if (preg_match('/^\s*(\d{1,2})\s*\/\s*(\d{2}|\d{4})\s*$/', $this->card_expiry, $m) && (int) $m[1] >= 1 && (int) $m[1] <= 12) {
            $year = strlen($m[2]) === 2 ? 2000 + (int) $m[2] : (int) $m[2];
            $expiry = [str_pad($m[1], 2, '0', STR_PAD_LEFT), (string) $year];

            if (now()->startOfMonth()->gt(\Illuminate\Support\Carbon::create($year, (int) $m[1], 1))) {
                $errors['card_expiry'] = 'Este cartão está vencido.';
            }
        } else {
            $errors['card_expiry'] = 'Use o formato MM/AA, ex.: 08/29.';
        }

        if (strlen(preg_replace('/\D/', '', $this->card_postal_code)) !== 8) {
            $errors['card_postal_code'] = 'CEP inválido.';
        }

        if (! Document::isValid($this->billing_document)) {
            $errors['billing_document'] = 'CPF ou CNPJ inválido.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return [
            'holder_name' => trim($this->card_holder),
            'number' => $number,
            'expiry_month' => $expiry[0],
            'expiry_year' => $expiry[1],
            'cvv' => $this->card_cvv,
            'postal_code' => $this->card_postal_code,
            'address_number' => trim($this->card_address_number),
        ];
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
