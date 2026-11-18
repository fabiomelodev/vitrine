<?php

namespace App\Filament\Painel\Resources\Products\Pages;

use App\Filament\Painel\Resources\Products\Concerns\HandlesColorOptions;
use App\Filament\Painel\Resources\Products\ProductResource;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    use HandlesColorOptions;

    protected static string $resource = ProductResource::class;

    protected function beforeCreate(): void
    {
        $store = Filament::getTenant();

        if ($store?->reachedProductLimit()) {
            Notification::make()
                ->title('Limite de produtos atingido')
                ->body("O plano permite até {$store->productLimit()} produtos.")
                ->danger()
                ->send();

            $this->halt();
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->extractColorOptions($data);
    }

    protected function afterCreate(): void
    {
        $this->syncVariantGrid();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
