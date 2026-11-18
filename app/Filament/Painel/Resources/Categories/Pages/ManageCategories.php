<?php

namespace App\Filament\Painel\Resources\Categories\Pages;

use App\Filament\Painel\Resources\Categories\CategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCategories extends ManageRecords
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nova categoria'),
        ];
    }
}
