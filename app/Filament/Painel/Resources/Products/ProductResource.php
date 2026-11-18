<?php

namespace App\Filament\Painel\Resources\Products;

use App\Filament\Painel\Resources\Products\Pages\CreateProduct;
use App\Filament\Painel\Resources\Products\Pages\EditProduct;
use App\Filament\Painel\Resources\Products\Pages\ListProducts;
use App\Filament\Painel\Resources\Products\Schemas\ProductForm;
use App\Filament\Painel\Resources\Products\Tables\ProductsTable;
use App\Models\Product;
use App\Models\Store;
use Filament\Facades\Filament;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static ?string $modelLabel = 'produto';

    protected static ?string $pluralModelLabel = 'produtos';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 1;

    /**
     * Limite de produtos do plano (hoje: 200).
     */
    public static function canCreate(): bool
    {
        $store = Filament::getTenant();

        return ! $store instanceof Store || ! $store->reachedProductLimit();
    }

    public static function form(Schema $schema): Schema
    {
        return ProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
