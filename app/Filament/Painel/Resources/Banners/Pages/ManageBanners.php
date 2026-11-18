<?php

namespace App\Filament\Painel\Resources\Banners\Pages;

use App\Filament\Painel\Resources\Banners\BannerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageBanners extends ManageRecords
{
    protected static string $resource = BannerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Novo banner'),
        ];
    }
}
