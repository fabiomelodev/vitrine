<?php

namespace App\Filament\Painel\Pages;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;

/**
 * Painel de controle do lojista, com atalho para a vitrine pública.
 */
class Dashboard extends BaseDashboard
{
    protected function getHeaderActions(): array
    {
        return [
            Action::make('verCatalogo')
                ->label('Ver meu catálogo')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->url(fn (): string => route('storefront.home', ['storeSlug' => Filament::getTenant()->slug]), shouldOpenInNewTab: true),
        ];
    }
}
