{{-- Campos do cartão (usados em "Assinar" e em "Trocar cartão"). Nada disso é gravado no Provador. --}}
@php
    $field = 'display: flex; flex-direction: column; gap: .25rem; font-size: .875rem; font-weight: 500';
    $error = 'color: rgb(220 38 38); font-size: .8rem';
@endphp
<div style="display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(min(180px, 100%), 1fr))">
    <label style="{{ $field }}; grid-column: 1 / -1">Número do cartão
        <x-filament::input.wrapper :valid="! $errors->has('card_number')">
            <x-filament::input type="text" inputmode="numeric" wire:model="card_number" autocomplete="cc-number" placeholder="0000 0000 0000 0000" maxlength="23" />
        </x-filament::input.wrapper>
        @error('card_number')<span style="{{ $error }}">{{ $message }}</span>@enderror
    </label>
    <label style="{{ $field }}; grid-column: 1 / -1">Nome impresso no cartão
        <x-filament::input.wrapper :valid="! $errors->has('card_holder')">
            <x-filament::input type="text" wire:model="card_holder" autocomplete="cc-name" />
        </x-filament::input.wrapper>
        @error('card_holder')<span style="{{ $error }}">{{ $message }}</span>@enderror
    </label>
    <label style="{{ $field }}">Validade
        <x-filament::input.wrapper :valid="! $errors->has('card_expiry')">
            <x-filament::input type="text" inputmode="numeric" wire:model="card_expiry" autocomplete="cc-exp" placeholder="MM/AA" maxlength="7" />
        </x-filament::input.wrapper>
        @error('card_expiry')<span style="{{ $error }}">{{ $message }}</span>@enderror
    </label>
    <label style="{{ $field }}">Código de segurança (CVV)
        <x-filament::input.wrapper :valid="! $errors->has('card_cvv')">
            <x-filament::input type="password" inputmode="numeric" wire:model="card_cvv" autocomplete="cc-csc" maxlength="4" />
        </x-filament::input.wrapper>
        @error('card_cvv')<span style="{{ $error }}">{{ $message }}</span>@enderror
    </label>
    <label style="{{ $field }}">CEP do titular
        <x-filament::input.wrapper :valid="! $errors->has('card_postal_code')">
            <x-filament::input type="text" inputmode="numeric" wire:model="card_postal_code" autocomplete="postal-code" placeholder="00000-000" maxlength="9" />
        </x-filament::input.wrapper>
        @error('card_postal_code')<span style="{{ $error }}">{{ $message }}</span>@enderror
    </label>
    <label style="{{ $field }}">Número do endereço
        <x-filament::input.wrapper :valid="! $errors->has('card_address_number')">
            <x-filament::input type="text" wire:model="card_address_number" maxlength="10" />
        </x-filament::input.wrapper>
        @error('card_address_number')<span style="{{ $error }}">{{ $message }}</span>@enderror
    </label>
</div>
<p style="margin: 0; font-size: .8rem; opacity: .75; display: flex; gap: .375rem; align-items: center">
    <x-filament::icon icon="heroicon-o-lock-closed" style="width: 1rem; height: 1rem; flex: 0 0 auto" />
    Os dados do cartão vão direto para o Asaas, nosso parceiro de pagamentos. O Provador guarda só a bandeira e os 4 últimos dígitos.
</p>
