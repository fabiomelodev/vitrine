@php $tenant = \Filament\Facades\Filament::getTenant(); @endphp
@if ($tenant)
    <div style="margin: 0.5rem 1rem 0">
        <x-filament::button
            tag="a"
            :href="route('storefront.home', ['storeSlug' => $tenant->slug])"
            target="_blank"
            rel="noopener"
            color="gray"
            outlined
            :icon="\Filament\Support\Icons\Heroicon::OutlinedArrowTopRightOnSquare"
            style="width: 100%"
        >
            Ver meu catálogo
        </x-filament::button>
    </div>
@endif
