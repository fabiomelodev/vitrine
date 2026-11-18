<?php

namespace App\Filament\Painel\Resources\Products\Tables;

use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with('category')
                ->withCount([
                    'variants',
                    'variants as unavailable_variants_count' => fn (Builder $q) => $q->where('is_available', false),
                ]))
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('images')
                    ->label('')
                    ->disk('public')
                    ->limit(1)
                    ->imageHeight(56),
                TextColumn::make('name')
                    ->label('Produto')
                    ->searchable(['name', 'sku'])
                    ->sortable()
                    ->description(fn ($record) => $record->sku ? 'Ref. '.$record->sku : null),
                TextColumn::make('category.name')
                    ->label('Categoria')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('variants_count')
                    ->label('Variações')
                    ->formatStateUsing(fn ($state, $record) => $record->unavailable_variants_count
                        ? "{$state} ({$record->unavailable_variants_count} esgotadas)"
                        : (string) $state),
                TextColumn::make('price')
                    ->label('Preço')
                    ->money('BRL')
                    ->sortable(),
                ToggleColumn::make('is_visible')
                    ->label('Na vitrine'),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Categoria')
                    ->relationship('category', 'name', fn (Builder $query) => $query->where('store_id', Filament::getTenant()->getKey())),
                TernaryFilter::make('is_visible')
                    ->label('Na vitrine'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('hide')
                        ->label('Esconder da vitrine')
                        ->icon(Heroicon::OutlinedEyeSlash)
                        ->action(fn (Collection $records) => $records->each->update(['is_visible' => false]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('show')
                        ->label('Mostrar na vitrine')
                        ->icon(Heroicon::OutlinedEye)
                        ->action(fn (Collection $records) => $records->each->update(['is_visible' => true]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
