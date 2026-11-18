<?php

namespace App\Filament\Painel\Resources\Products\Pages;

use App\Filament\Painel\Resources\Products\Concerns\HandlesColorOptions;
use App\Filament\Painel\Resources\Products\ProductResource;
use App\Support\VariantGrid;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditProduct extends EditRecord
{
    use HandlesColorOptions;

    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return [...$data, ...VariantGrid::fromProduct($this->record)];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->extractColorOptions($data);
    }

    protected function afterSave(): void
    {
        $this->syncVariantGrid();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('verNaVitrine')
                ->label('Ver na vitrine')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->url(fn () => url(Filament::getTenant()->slug.'/p/'.$this->record->slug), shouldOpenInNewTab: true),
            DeleteAction::make(),
        ];
    }
}
