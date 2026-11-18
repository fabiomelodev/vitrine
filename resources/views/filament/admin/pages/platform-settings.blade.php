<x-filament-panels::page>
    <form wire:submit="save" style="max-width: 640px">
        <x-filament::section heading="Dados da empresa" description="Opcionais. Quando preenchidos, aparecem no rodapé do site e nos Termos de uso e na Política de privacidade.">
            <div style="display: flex; flex-direction: column; gap: 1rem">
                <label style="display: flex; flex-direction: column; gap: .25rem; font-size: .875rem; font-weight: 500">Razão social
                    <x-filament::input.wrapper :valid="! $errors->has('company_name')">
                        <x-filament::input type="text" wire:model="company_name" placeholder="Ex.: Provador Tecnologia Ltda." />
                    </x-filament::input.wrapper>
                    @error('company_name')<span style="color: rgb(220 38 38); font-size: .8rem">{{ $message }}</span>@enderror
                </label>

                <label style="display: flex; flex-direction: column; gap: .25rem; font-size: .875rem; font-weight: 500">CNPJ
                    <x-filament::input.wrapper :valid="! $errors->has('company_document')">
                        <x-filament::input type="text" inputmode="numeric" wire:model="company_document" placeholder="00.000.000/0000-00" />
                    </x-filament::input.wrapper>
                    @error('company_document')<span style="color: rgb(220 38 38); font-size: .8rem">{{ $message }}</span>@enderror
                </label>

                <div>
                    <x-filament::button type="submit">Salvar</x-filament::button>
                </div>
            </div>
        </x-filament::section>
    </form>
</x-filament-panels::page>
