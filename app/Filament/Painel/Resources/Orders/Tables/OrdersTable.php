<?php

namespace App\Filament\Painel\Resources\Orders\Tables;

use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withSum('items as pieces', 'quantity'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('code')
                    ->label('Pedido')
                    ->formatStateUsing(fn (string $state) => '#'.$state)
                    ->weight('bold')
                    ->searchable(),
                TextColumn::make('customer_name')
                    ->label('Cliente')
                    ->description(fn (Order $record) => $record->formattedPhone())
                    ->searchable(['customer_name', 'customer_phone']),
                TextColumn::make('pieces')
                    ->label('Peças')
                    ->numeric(),
                TextColumn::make('total')
                    ->label('Total')
                    ->money('BRL')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Enviado')
                    ->since()
                    ->dateTimeTooltip('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
            ])
            ->recordActions([
                Action::make('whatsapp')
                    ->label('WhatsApp')
                    ->icon(Heroicon::OutlinedChatBubbleOvalLeft)
                    ->color('success')
                    ->url(fn (Order $record) => $record->customerWhatsappUrl(), shouldOpenInNewTab: true),
                EditAction::make()->label('Abrir'),
            ])
            ->emptyStateHeading('Nenhum pedido ainda')
            ->emptyStateDescription('Quando um cliente enviar a sacola pelo WhatsApp, o pedido aparece aqui.');
    }
}
