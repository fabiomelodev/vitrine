<?php

namespace App\Filament\Painel\Resources\Banners;

use App\Enums\BannerLinkType;
use App\Filament\Painel\Resources\Banners\Pages\ManageBanners;
use App\Models\Banner;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Banners do carrossel do topo da vitrine.
 */
class BannerResource extends Resource
{
    protected static ?string $model = Banner::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?string $modelLabel = 'banner';

    protected static ?string $pluralModelLabel = 'banners';

    protected static ?int $navigationSort = 3;

    protected static function linkIs(Get $get, BannerLinkType $type): bool
    {
        $state = $get('link_type');

        return $state === $type || $state === $type->value;
    }

    protected static function storeId(): int
    {
        return Filament::getTenant()->getKey();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Imagens')
                    ->columns(2)
                    ->schema([
                        FileUpload::make('image_path')
                            ->label('Imagem (computador)')
                            ->helperText('Formato largo. Recomendado: 1600 × 600 px.')
                            ->image()
                            ->required()
                            ->maxSize(10240)
                            ->automaticallyResizeImagesMode('contain')
                            ->automaticallyResizeImagesToWidth('2000')
                            ->automaticallyResizeImagesToHeight('2000')
                            ->automaticallyUpscaleImagesWhenResizing(false)
                            ->disk('public')
                            ->directory(fn () => 'stores/'.static::storeId().'/banners'),
                        FileUpload::make('image_mobile_path')
                            ->label('Imagem (celular) — opcional')
                            ->helperText('Formato vertical. Recomendado: 1080 × 1350 px. Sem ela, usamos a do computador.')
                            ->image()
                            ->maxSize(10240)
                            ->automaticallyResizeImagesMode('contain')
                            ->automaticallyResizeImagesToWidth('1200')
                            ->automaticallyResizeImagesToHeight('1500')
                            ->automaticallyUpscaleImagesWhenResizing(false)
                            ->disk('public')
                            ->directory(fn () => 'stores/'.static::storeId().'/banners'),
                        TextInput::make('title')
                            ->label('Descrição da imagem')
                            ->helperText('Ex.: "Coleção Verão — até 30% off". Ajuda quem usa leitor de tela e o Google.')
                            ->maxLength(120)
                            ->columnSpanFull(),
                    ]),
                Section::make('Link')
                    ->schema([
                        ToggleButtons::make('link_type')
                            ->label('Ao clicar no banner, levar para')
                            ->options(BannerLinkType::class)
                            ->default(BannerLinkType::None->value)
                            ->inline()
                            ->live()
                            ->required(),
                        Select::make('link_category_id')
                            ->label('Categoria')
                            ->relationship('linkCategory', 'name', fn (Builder $query) => $query->where('store_id', static::storeId()))
                            ->preload()
                            ->searchable()
                            ->visible(fn (Get $get) => static::linkIs($get, BannerLinkType::Category))
                            ->required(fn (Get $get) => static::linkIs($get, BannerLinkType::Category)),
                        Select::make('link_product_id')
                            ->label('Produto')
                            ->relationship('linkProduct', 'name', fn (Builder $query) => $query->where('store_id', static::storeId()))
                            ->searchable()
                            ->preload()
                            ->visible(fn (Get $get) => static::linkIs($get, BannerLinkType::Product))
                            ->required(fn (Get $get) => static::linkIs($get, BannerLinkType::Product)),
                        TextInput::make('link_url')
                            ->label('Endereço')
                            ->placeholder('https://instagram.com/sualoja')
                            ->url()
                            ->maxLength(500)
                            ->visible(fn (Get $get) => static::linkIs($get, BannerLinkType::Url))
                            ->required(fn (Get $get) => static::linkIs($get, BannerLinkType::Url)),
                        Toggle::make('open_in_new_tab')
                            ->label('Abrir em nova aba')
                            ->visible(fn (Get $get) => static::linkIs($get, BannerLinkType::Url)),
                    ]),
                Toggle::make('is_active')
                    ->label('Mostrar na vitrine')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('position')
            ->defaultSort('position')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['linkCategory', 'linkProduct']))
            ->columns([
                ImageColumn::make('image_path')
                    ->label('Banner')
                    ->disk('public')
                    ->imageHeight(64),
                TextColumn::make('title')
                    ->label('Descrição')
                    ->placeholder('—')
                    ->wrap(),
                TextColumn::make('link_type')
                    ->label('Leva para')
                    ->formatStateUsing(fn (Banner $record) => match ($record->link_type) {
                        BannerLinkType::Category => 'Categoria: '.($record->linkCategory?->name ?? '—'),
                        BannerLinkType::Product => 'Produto: '.($record->linkProduct?->name ?? '—'),
                        BannerLinkType::Url => $record->link_url,
                        default => 'Sem link',
                    }),
                ToggleColumn::make('is_active')
                    ->label('Na vitrine'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('Nenhum banner ainda')
            ->emptyStateDescription('Os banners aparecem em carrossel no topo da vitrine. Arraste para mudar a ordem.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageBanners::route('/'),
        ];
    }
}
