<?php

namespace App\Filament\Painel\Resources\Products\Pages;

use App\Filament\Painel\Resources\Products\ProductResource;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Novo produto'),
        ];
    }

    public function getSubheading(): ?string
    {
        $store = Filament::getTenant();
        $limit = $store?->productLimit();

        if (! $limit) {
            return null;
        }

        $count = $store->products()->count();

        return $count >= $limit
            ? "Você chegou ao limite de {$limit} produtos do plano. Esconda ou apague produtos antigos para cadastrar novos."
            : "{$count} de {$limit} produtos do plano.";
    }
}
