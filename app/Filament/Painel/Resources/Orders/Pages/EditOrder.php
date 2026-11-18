<?php

namespace App\Filament\Painel\Resources\Orders\Pages;

use App\Filament\Painel\Resources\Orders\OrderResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    public function getTitle(): string|Htmlable
    {
        return 'Pedido #'.$this->record->code;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('chamarCliente')
                ->label('Chamar cliente no WhatsApp')
                ->icon(Heroicon::OutlinedChatBubbleOvalLeft)
                ->color('success')
                ->url(fn () => $this->record->customerWhatsappUrl(), shouldOpenInNewTab: true),
        ];
    }

    protected function getRedirectUrl(): ?string
    {
        return $this->getResource()::getUrl('index');
    }
}
