<?php

namespace App\Filament\Admin\Resources\Plans;

use App\Filament\Admin\Resources\Plans\Pages\ManagePlans;
use App\Models\Plan;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static ?string $modelLabel = 'plano';

    protected static ?string $pluralModelLabel = 'planos';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')->label('Nome')->required()->maxLength(60),
                TextInput::make('price')
                    ->label('Preço mensal')
                    ->helperText('Vale para novas assinaturas. Assinaturas já criadas no Asaas mantêm o valor antigo.')
                    ->required()->numeric()->minValue(1)->step(0.01)->prefix('R$'),
                TextInput::make('max_products')
                    ->label('Limite de produtos')
                    ->helperText('Deixe vazio para ilimitado.')
                    ->numeric()->minValue(1),
                TextInput::make('trial_days')
                    ->label('Dias de teste grátis')
                    ->required()->numeric()->minValue(0)->maxValue(90)->default(14),
                Textarea::make('description')->label('Descrição')->rows(2)->columnSpanFull(),
                Toggle::make('is_active')->label('Disponível para lojas novas')->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Plano'),
                TextColumn::make('price')->label('Preço')->money('BRL'),
                TextColumn::make('max_products')->label('Produtos')->placeholder('Ilimitado'),
                TextColumn::make('trial_days')->label('Teste')->suffix(' dias'),
                TextColumn::make('stores_count')->label('Lojas')->counts('stores'),
                ToggleColumn::make('is_active')->label('Ativo'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePlans::route('/'),
        ];
    }
}
