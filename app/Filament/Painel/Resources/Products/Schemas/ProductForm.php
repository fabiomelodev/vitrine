<?php

namespace App\Filament\Painel\Resources\Products\Schemas;

use App\Models\Product;
use Filament\Facades\Filament;
use App\Support\VariantGrid;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make()
                    ->columnSpan(['lg' => 2])
                    ->schema([
                        static::infoSection(),
                        static::photosSection(),
                        static::variantsSection(),
                        static::relatedSection(),
                    ]),
                Group::make()
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        static::priceSection(),
                        static::visibilitySection(),
                    ]),
            ]);
    }

    protected static function storeId(): int
    {
        return Filament::getTenant()->getKey();
    }

    protected static function infoSection(): Section
    {
        return Section::make('Informações')
            ->columns(2)
            ->schema([
                TextInput::make('name')
                    ->label('Nome do produto')
                    ->required()
                    ->maxLength(120)
                    ->columnSpanFull()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, Get $get, ?string $state, ?string $old): void {
                        if (blank($get('slug')) || $get('slug') === Str::slug((string) $old)) {
                            $set('slug', Str::slug((string) $state));
                        }
                    }),
                TextInput::make('slug')
                    ->label('Endereço do produto')
                    ->helperText('Usado no link da página do produto.')
                    ->required()
                    ->alphaDash()
                    ->maxLength(140)
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: fn (Unique $rule) => $rule->where('store_id', static::storeId()),
                    ),
                TextInput::make('sku')
                    ->label('Referência (SKU)')
                    ->maxLength(40),
                Select::make('category_id')
                    ->label('Categoria')
                    ->relationship(
                        'category',
                        'name',
                        fn (Builder $query) => $query->where('store_id', static::storeId())->orderBy('position'),
                    )
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        TextInput::make('name')->label('Nome da categoria')->required(),
                    ])
                    ->createOptionUsing(fn (array $data) => Filament::getTenant()->categories()->create($data)->getKey())
                    ->columnSpanFull(),
                Textarea::make('description')
                    ->label('Descrição')
                    ->rows(5)
                    ->maxLength(3000)
                    ->columnSpanFull(),
            ]);
    }

    protected static function photosSection(): Section
    {
        return Section::make('Fotos gerais')
            ->description('A primeira foto é a capa. Arraste para reordenar. Fotos verticais (3:4) ficam melhor. Para fotos de cada cor, use o campo dentro da cor em "Variações".')
            ->schema([
                FileUpload::make('images')
                    ->hiddenLabel()
                    ->image()
                    ->multiple()
                    ->reorderable()
                    ->appendFiles()
                    ->maxFiles(8)
                    ->maxSize(10240)
                    ->panelLayout('grid')
                    // Reduz no próprio navegador antes de enviar (fotos de celular costumam ter 4000px+).
                    ->automaticallyResizeImagesMode('contain')
                    ->automaticallyResizeImagesToWidth('1600')
                    ->automaticallyResizeImagesToHeight('1600')
                    ->automaticallyUpscaleImagesWhenResizing(false)
                    ->disk('public')
                    ->directory(fn () => 'stores/'.static::storeId().'/products'),
            ]);
    }

    protected static function variantsSection(): Section
    {
        return Section::make('Variações')
            ->description('Cadastre as cores e os tamanhos. Todas as combinações ficam disponíveis; marque abaixo só o que esgotou.')
            ->schema([
                Repeater::make('color_options')
                    ->label('Cores')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome da cor')
                            ->placeholder('Ex.: Off-white')
                            ->required()
                            ->maxLength(40)
                            ->live(onBlur: true),
                        ColorPicker::make('hex')
                            ->label('Amostra'),
                        FileUpload::make('images')
                            ->label('Fotos desta cor (opcional)')
                            ->helperText('Aparecem quando o cliente escolhe esta cor. Sem fotos aqui, usamos as fotos gerais.')
                            ->image()
                            ->multiple()
                            ->reorderable()
                            ->appendFiles()
                            ->maxFiles(8)
                            ->maxSize(10240)
                            ->panelLayout('grid')
                            ->automaticallyResizeImagesMode('contain')
                            ->automaticallyResizeImagesToWidth('1600')
                            ->automaticallyResizeImagesToHeight('1600')
                            ->automaticallyUpscaleImagesWhenResizing(false)
                            ->disk('public')
                            ->directory(fn () => 'stores/'.static::storeId().'/products')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->defaultItems(0)
                    ->reorderable()
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => filled($state['name'] ?? null) ? $state['name'] : null)
                    ->addActionLabel('Adicionar cor')
                    ->live(),
                Grid::make(3)
                    ->schema([
                        Select::make('size_preset')
                            ->label('Grade pronta')
                            ->placeholder('Escolher…')
                            ->options(collect(VariantGrid::SIZE_PRESETS)->map(fn (array $p) => $p['label'])->all())
                            ->live()
                            ->dehydrated(false)
                            ->afterStateUpdated(function (?string $state, Set $set): void {
                                if ($state && isset(VariantGrid::SIZE_PRESETS[$state])) {
                                    $set('size_options', VariantGrid::SIZE_PRESETS[$state]['sizes']);
                                }
                            }),
                        TagsInput::make('size_options')
                            ->label('Tamanhos')
                            ->placeholder('Digite e tecle Enter')
                            ->helperText('Ou escolha uma grade pronta ao lado.')
                            ->suggestions(['PP', 'P', 'M', 'G', 'GG', 'XG', 'U', '34', '36', '38', '40', '42', '44'])
                            ->reorderable()
                            ->live()
                            ->dehydrated(false)
                            ->columnSpan(2),
                    ]),
                Group::make()
                    ->schema(fn (Get $get): array => static::soldOutFields($get)),
            ]);
    }

    /**
     * Uma linha por cor, com os tamanhos para marcar como esgotados.
     *
     * @return array<int, \Filament\Schemas\Components\Component|\Filament\Forms\Components\Field>
     */
    protected static function soldOutFields(Get $get): array
    {
        $colors = collect($get('color_options') ?? [])
            ->pluck('name')
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique(fn ($name) => mb_strtolower($name))
            ->values();

        $sizes = collect($get('size_options') ?? [])->filter()->values();

        if ($colors->isEmpty() && $sizes->isEmpty()) {
            return [
                Text::make('Sem cores e tamanhos, o produto é vendido sem variações.'),
            ];
        }

        $options = $sizes->isEmpty()
            ? [VariantGrid::NO_SIZE => 'Esgotada']
            : $sizes->mapWithKeys(fn ($size) => [$size => $size])->all();

        $rows = $colors->isEmpty() ? collect([null]) : $colors;

        return [
            Text::make('Marque o que está esgotado:')->weight(FontWeight::Bold),
            ...$rows->map(fn (?string $color) => CheckboxList::make('soldout.'.VariantGrid::colorKey($color))
                ->label($color ?? 'Tamanhos esgotados')
                ->options($options)
                ->columns(min(8, max(2, count($options))))
                ->dehydrated(false))
                ->all(),
        ];
    }

    protected static function relatedSection(): Section
    {
        return Section::make('Complete o look')
            ->description('Peças sugeridas na página deste produto e na sacola.')
            ->collapsible()
            ->schema([
                Select::make('related')
                    ->hiddenLabel()
                    ->relationship(
                        'related',
                        'name',
                        fn (Builder $query, ?Product $record) => $query
                            ->where('products.store_id', static::storeId())
                            ->when($record, fn (Builder $q) => $q->whereKeyNot($record->getKey())),
                    )
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->maxItems(6),
            ]);
    }

    protected static function priceSection(): Section
    {
        return Section::make('Preço')
            ->schema([
                TextInput::make('price')
                    ->label('Preço')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01)
                    ->prefix('R$'),
                TextInput::make('compare_at_price')
                    ->label('Preço "de" (opcional)')
                    ->helperText('Preencha para mostrar a peça em promoção.')
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01)
                    ->prefix('R$')
                    ->gt('price')
                    ->validationMessages(['gt' => 'O preço "de" precisa ser maior que o preço atual.']),
            ]);
    }

    protected static function visibilitySection(): Section
    {
        return Section::make('Vitrine')
            ->schema([
                Toggle::make('is_visible')
                    ->label('Mostrar na vitrine')
                    ->default(true),
                Toggle::make('is_featured')
                    ->label('Destaque na home'),
            ]);
    }
}
