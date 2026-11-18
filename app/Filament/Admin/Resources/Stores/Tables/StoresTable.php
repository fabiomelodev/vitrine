<?php

namespace App\Filament\Admin\Resources\Stores\Tables;

use App\Enums\SubscriptionStatus;
use App\Models\Store;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class StoresTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('Loja')
                    ->description(fn (Store $record) => '/'.$record->slug)
                    ->searchable(['name', 'slug'])
                    ->sortable(),
                TextColumn::make('users.email')
                    ->label('Responsáveis')
                    ->listWithLineBreaks()
                    ->limitList(2),
                TextColumn::make('products_count')
                    ->label('Produtos')
                    ->counts('products')
                    ->sortable(),
                TextColumn::make('orders_count')
                    ->label('Pedidos')
                    ->counts('orders')
                    ->sortable(),
                TextColumn::make('subscription_status')
                    ->label('Assinatura')
                    ->badge()
                    ->description(fn (Store $record) => $record->isOnTrial() && $record->trial_ends_at
                        ? 'Teste até '.$record->trial_ends_at->format('d/m')
                        : null),
                ToggleColumn::make('is_active')
                    ->label('Ativa'),
                TextColumn::make('created_at')
                    ->label('Criada em')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Ativa'),
                SelectFilter::make('subscription_status')->label('Assinatura')->options(SubscriptionStatus::class),
            ])
            ->recordActions([
                Action::make('vitrine')
                    ->label('Vitrine')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->url(fn (Store $record) => $record->publicUrl(), shouldOpenInNewTab: true),
                Action::make('painel')
                    ->label('Painel')
                    ->icon(Heroicon::OutlinedSquares2x2)
                    ->color('gray')
                    ->url(fn (Store $record) => url('painel/'.$record->slug), shouldOpenInNewTab: true),
                Action::make('cortesia')
                    ->label('Cortesia')
                    ->icon(Heroicon::OutlinedGift)
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalDescription('Libera a loja sem cobrança até você mudar o status.')
                    ->visible(fn (Store $record) => $record->subscription_status !== SubscriptionStatus::Courtesy)
                    ->action(fn (Store $record) => $record->update(['subscription_status' => SubscriptionStatus::Courtesy])),
                EditAction::make(),
            ]);
    }
}
