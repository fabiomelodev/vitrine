@php
    use App\Enums\SubscriptionStatus;
    use App\Support\Format;
    $status = $store->subscription_status ?? SubscriptionStatus::Trialing;
    $days = $store->trialDaysLeft();
@endphp
<x-filament-panels::page>
    <div style="display: grid; gap: 1.5rem; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); align-items: start">

        <x-filament::section heading="Sua assinatura">
            <div style="display: flex; flex-direction: column; gap: 1rem">
                <div style="display: flex; align-items: center; gap: .75rem; flex-wrap: wrap">
                    <x-filament::badge :color="$status->getColor()">{{ $status->getLabel() }}</x-filament::badge>
                    @if ($status === SubscriptionStatus::Trialing && $days !== null)
                        <span style="font-size: .875rem">
                            @if ($store->hasActiveAccess())
                                Teste grátis: {{ $days === 0 ? 'termina hoje' : ($days === 1 ? 'falta 1 dia' : "faltam {$days} dias") }}
                                ({{ $store->trial_ends_at?->format('d/m/Y') }}).
                            @else
                                O período de teste terminou em {{ $store->trial_ends_at?->format('d/m/Y') }}.
                            @endif
                        </span>
                    @elseif ($status === SubscriptionStatus::PastDue)
                        <span style="font-size: .875rem">Pagamento em atraso desde {{ $store->past_due_since?->format('d/m/Y') }}. A vitrine sai do ar em {{ $store->blocksAt()?->format('d/m/Y') }} se não for pago.</span>
                    @elseif ($store->paidPeriodOverdue())
                        <span style="font-size: .875rem">A mensalidade venceu em {{ $store->access_until->format('d/m/Y') }} e ainda não recebemos a confirmação do pagamento. A vitrine sai do ar em {{ $store->blocksAt()?->format('d/m/Y') }} se não for pago.</span>
                    @elseif ($status === SubscriptionStatus::Active && $store->access_until)
                        <span style="font-size: .875rem">Pago até {{ $store->access_until->format('d/m/Y') }}.</span>
                    @elseif ($status === SubscriptionStatus::Canceled && $store->hasActiveAccess())
                        <span style="font-size: .875rem">Cancelada. Sua vitrine fica no ar até {{ $store->access_until?->format('d/m/Y') }}.</span>
                    @endif
                </div>

                @unless ($store->hasActiveAccess())
                    <div style="padding: .75rem 1rem; border-radius: .75rem; background: #FBF1EF; color: #8A2F22; font-size: .875rem">
                        Sua vitrine está fora do ar e o painel está limitado a esta página. Assine para reativar — seus produtos e pedidos continuam salvos.
                    </div>
                @endunless

                @if ($plan)
                    <div style="display: flex; justify-content: space-between; align-items: baseline; gap: 1rem; flex-wrap: wrap">
                        <div>
                            <div style="font-weight: 600">Plano {{ $plan->name }}</div>
                            <div style="font-size: .875rem; opacity: .75">{{ $plan->description }}</div>
                        </div>
                        <div style="font-size: 1.5rem; font-weight: 700; white-space: nowrap">{{ Format::money($plan->price) }}<span style="font-size: .875rem; font-weight: 400">/mês</span></div>
                    </div>
                    @if ($plan->max_products)
                        <div style="font-size: .875rem">Produtos: <strong>{{ $productCount }}</strong> de {{ $plan->max_products }}</div>
                    @endif
                @endif

                @if ($store->hasSubscription())
                    <p style="font-size: .875rem; margin: 0">
                        @if ($store->billing_method === \App\Actions\Billing\SetupCardBilling::METHOD_CARD)
                            Assinatura mensal ativa, cobrada no cartão. Os recibos chegam no e-mail <strong>{{ $store->billing_email }}</strong>.
                        @else
                            Assinatura mensal ativa no Asaas. As próximas cobranças chegam no e-mail <strong>{{ $store->billing_email }}</strong>.
                        @endif
                    </p>
                    <div style="display: flex; gap: .5rem; flex-wrap: wrap">
                        <x-filament::button color="gray" wire:click="refreshPayments" icon="heroicon-o-arrow-path">Atualizar cobranças</x-filament::button>
                        <x-filament::button color="danger" outlined wire:click="cancel"
                            wire:confirm="Cancelar a assinatura? Sua vitrine fica no ar até o fim do período já pago.">Cancelar assinatura</x-filament::button>
                    </div>
                @endif
            </div>
        </x-filament::section>

        @if ($store->hasSubscription())
            <x-filament::section heading="Forma de pagamento">
                <div x-data="{ open: false }" x-on:card-saved.window="open = false" style="display: flex; flex-direction: column; gap: 1rem">
                    @if ($store->billing_method === \App\Actions\Billing\SetupCardBilling::METHOD_CARD)
                        <div style="display: flex; gap: .75rem; align-items: center; flex-wrap: wrap">
                            <x-filament::icon icon="heroicon-o-credit-card" style="width: 1.5rem; height: 1.5rem" />
                            <span style="font-size: .9375rem">
                                <strong>{{ \App\Actions\Billing\SetupCardBilling::brandLabel($store->card_brand) }} •••• {{ $store->card_last_digits }}</strong><br>
                                <span style="font-size: .8125rem; opacity: .75">Cobrado automaticamente todo mês.</span>
                            </span>
                        </div>
                        <div x-show="! open">
                            <x-filament::button color="gray" outlined x-on:click="open = true">Trocar cartão</x-filament::button>
                        </div>
                    @else
                        <p style="margin: 0; font-size: .875rem">Hoje você paga por <strong>Pix ou boleto</strong> a cada mês. Ative o cartão para a mensalidade ser cobrada automaticamente e sua vitrine nunca sair do ar por esquecimento.</p>
                        <div x-show="! open">
                            <x-filament::button icon="heroicon-o-credit-card" x-on:click="open = true">Pagar automaticamente no cartão</x-filament::button>
                        </div>
                    @endif

                    <form x-show="open" x-cloak wire:submit="saveCard" style="display: flex; flex-direction: column; gap: 1rem">
                        @include('filament.painel.pages.partials.card-fields')
                        @error('billing_phone')<span style="color: rgb(220 38 38); font-size: .8rem">{{ $message }}</span>@enderror
                        @error('billing_document')<span style="color: rgb(220 38 38); font-size: .8rem">{{ $message }}</span>@enderror
                        <div style="display: flex; gap: .5rem; flex-wrap: wrap">
                            <x-filament::button type="submit" wire:loading.attr="disabled" wire:target="saveCard">Salvar cartão</x-filament::button>
                            <x-filament::button color="gray" x-on:click="open = false">Cancelar</x-filament::button>
                        </div>
                        @if ($store->billing_method !== \App\Actions\Billing\SetupCardBilling::METHOD_CARD && ! $store->isOnTrial() && ! ($store->access_until?->isFuture()))
                            <p style="margin: 0; font-size: .8rem; opacity: .75">Como não há período pago em aberto, o cartão é cobrado na hora.</p>
                        @endif
                    </form>
                </div>
            </x-filament::section>
        @endif

        @if ($status === SubscriptionStatus::Courtesy)
            <x-filament::section heading="Cortesia">
                <p style="margin: 0; font-size: .875rem">Esta loja tem acesso liberado pela equipe do Provador. Nenhuma cobrança será feita.</p>
            </x-filament::section>
        @elseif (! $store->hasSubscription())
            <x-filament::section heading="Assinar" description="Se ainda estiver no teste, a 1ª cobrança só acontece no fim dele: você não perde nenhum dia grátis.">
                <form wire:submit="subscribe" style="display: flex; flex-direction: column; gap: 1rem">
                    <label style="display: flex; flex-direction: column; gap: .25rem; font-size: .875rem; font-weight: 500">Nome completo ou razão social
                        <x-filament::input.wrapper :valid="! $errors->has('billing_name')">
                            <x-filament::input type="text" wire:model="billing_name" autocomplete="name" />
                        </x-filament::input.wrapper>
                        @error('billing_name')<span style="color: rgb(220 38 38); font-size: .8rem">{{ $message }}</span>@enderror
                    </label>
                    <label style="display: flex; flex-direction: column; gap: .25rem; font-size: .875rem; font-weight: 500">CPF ou CNPJ
                        <x-filament::input.wrapper :valid="! $errors->has('billing_document')">
                            <x-filament::input type="text" inputmode="numeric" wire:model="billing_document" placeholder="000.000.000-00" />
                        </x-filament::input.wrapper>
                        @error('billing_document')<span style="color: rgb(220 38 38); font-size: .8rem">{{ $message }}</span>@enderror
                    </label>
                    <label style="display: flex; flex-direction: column; gap: .25rem; font-size: .875rem; font-weight: 500">E-mail para as cobranças
                        <x-filament::input.wrapper :valid="! $errors->has('billing_email')">
                            <x-filament::input type="email" wire:model="billing_email" autocomplete="email" />
                        </x-filament::input.wrapper>
                        @error('billing_email')<span style="color: rgb(220 38 38); font-size: .8rem">{{ $message }}</span>@enderror
                    </label>
                    <label style="display: flex; flex-direction: column; gap: .25rem; font-size: .875rem; font-weight: 500">Celular{{ $payment_method === 'card' ? '' : ' (opcional)' }}
                        <x-filament::input.wrapper :valid="! $errors->has('billing_phone')">
                            <x-filament::input type="tel" wire:model="billing_phone" placeholder="(11) 91234-5678" />
                        </x-filament::input.wrapper>
                        @error('billing_phone')<span style="color: rgb(220 38 38); font-size: .8rem">{{ $message }}</span>@enderror
                    </label>

                    <fieldset style="border: 0; margin: 0; padding: 0; display: flex; flex-direction: column; gap: .5rem">
                        <legend style="font-size: .875rem; font-weight: 500; margin-bottom: .5rem">Forma de pagamento</legend>
                        @foreach (['card' => ['Cartão de crédito', 'Cobrado automaticamente todo mês. Você não precisa lembrar de pagar.'], 'invoice' => ['Pix ou boleto', 'Você recebe a cobrança por e-mail e paga a cada mês.']] as $value => [$label, $hint])
                            <label style="display: flex; gap: .75rem; align-items: flex-start; padding: .75rem 1rem; border-radius: .75rem; cursor: pointer; border: 1px solid {{ $payment_method === $value ? 'var(--primary-500, #78716c)' : 'color-mix(in oklab, currentColor 15%, transparent)' }}">
                                <input type="radio" wire:model.live="payment_method" value="{{ $value }}" style="margin-top: .2rem">
                                <span style="display: flex; flex-direction: column; gap: .125rem; font-size: .875rem">
                                    <strong style="font-weight: 600">{{ $label }}</strong>
                                    <span style="opacity: .75">{{ $hint }}</span>
                                </span>
                            </label>
                        @endforeach
                    </fieldset>

                    @if ($payment_method === 'card')
                        @include('filament.painel.pages.partials.card-fields')
                    @endif

                    <x-filament::button type="submit" size="lg" icon="heroicon-o-credit-card" wire:loading.attr="disabled" wire:target="subscribe">
                        Assinar por {{ $plan ? Format::money($plan->price) : '' }}/mês
                    </x-filament::button>
                </form>
            </x-filament::section>
        @endif
    </div>

    @if ($payments->isNotEmpty())
        <x-filament::section heading="Cobranças">
            <div style="overflow-x: auto">
                <table style="width: 100%; font-size: .875rem; border-collapse: collapse">
                    <thead>
                        <tr style="text-align: left; opacity: .7">
                            <th style="padding: .5rem">Vencimento</th>
                            <th style="padding: .5rem">Valor</th>
                            <th style="padding: .5rem">Status</th>
                            <th style="padding: .5rem"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($payments as $payment)
                            <tr style="border-top: 1px solid rgba(127,127,127,.2)">
                                <td style="padding: .5rem">{{ $payment->due_date->format('d/m/Y') }}</td>
                                <td style="padding: .5rem">{{ Format::money($payment->value) }}</td>
                                <td style="padding: .5rem">
                                    <x-filament::badge :color="$payment->isPaid() ? 'success' : ($payment->status === 'OVERDUE' ? 'danger' : 'warning')">{{ $payment->statusLabel() }}</x-filament::badge>
                                </td>
                                <td style="padding: .5rem; text-align: right">
                                    @if ($payment->invoice_url)
                                        <x-filament::link :href="$payment->invoice_url" target="_blank">{{ $payment->isPaid() ? 'Recibo' : 'Pagar' }}</x-filament::link>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
