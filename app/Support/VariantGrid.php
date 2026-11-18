<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Converte entre a grade do formulário (cores × tamanhos + esgotados)
 * e as linhas de product_variants.
 *
 * Estado do formulário:
 *  - color_options: [['name' => 'Off-white', 'hex' => '#EFEAE2', 'images' => [...]], ...]
 *  - size_options:  ['P', 'M', 'G']
 *  - soldout:       ['<chave da cor>' => ['P', 'G']]   (tamanhos esgotados por cor)
 */
class VariantGrid
{
    public const NO_COLOR = 'sem-cor';

    public const NO_SIZE = 'unico';

    public const SIZE_PRESETS = [
        'letras' => ['label' => 'Letras (PP a GG)', 'sizes' => ['PP', 'P', 'M', 'G', 'GG']],
        'letras-xg' => ['label' => 'Letras com XG', 'sizes' => ['PP', 'P', 'M', 'G', 'GG', 'XG']],
        'numerica' => ['label' => 'Numérica (34 a 46)', 'sizes' => ['34', '36', '38', '40', '42', '44', '46']],
        'calcados' => ['label' => 'Calçados (33 a 40)', 'sizes' => ['33', '34', '35', '36', '37', '38', '39', '40']],
        'infantil' => ['label' => 'Infantil (2 a 16)', 'sizes' => ['2', '4', '6', '8', '10', '12', '14', '16']],
        'unico' => ['label' => 'Tamanho único', 'sizes' => ['U']],
    ];

    public static function colorKey(?string $color): string
    {
        return filled($color) ? 'c'.substr(md5(mb_strtolower(trim($color))), 0, 10) : self::NO_COLOR;
    }

    /**
     * @return array{color_options: array<int, array{name: string, hex: ?string}>, size_options: array<int, string>, soldout: array<string, array<int, string>>}
     */
    public static function fromProduct(Product $product): array
    {
        $variants = $product->variants()->orderBy('position')->get();

        $colorImages = $product->color_images ?? [];

        $colors = $variants->filter(fn (ProductVariant $v) => filled($v->color))
            ->unique('color')
            ->map(fn (ProductVariant $v) => [
                'name' => $v->color,
                'hex' => $v->color_hex,
                'images' => array_values($colorImages[$v->color] ?? []),
            ])
            ->values()
            ->all();

        $sizes = $variants->pluck('size')->filter()->unique()->values()->all();

        $soldout = $variants->where('is_available', false)
            ->groupBy(fn (ProductVariant $v) => static::colorKey($v->color))
            ->map(fn (Collection $group) => $group->map(fn ($v) => $v->size ?? self::NO_SIZE)->values()->all())
            ->all();

        return [
            'color_options' => $colors,
            'size_options' => $sizes,
            'soldout' => $soldout,
        ];
    }

    /**
     * @param  array<string, mixed>  $state  Estado bruto do formulário.
     */
    public static function sync(Product $product, array $state): void
    {
        $colors = collect($state['color_options'] ?? [])
            ->map(fn ($c) => ['name' => trim((string) ($c['name'] ?? '')), 'hex' => $c['hex'] ?? null])
            ->filter(fn ($c) => $c['name'] !== '')
            ->unique(fn ($c) => mb_strtolower($c['name']))
            ->values();

        $sizes = collect($state['size_options'] ?? [])
            ->map(fn ($s) => trim((string) $s))
            ->filter()
            ->unique()
            ->values();

        $soldout = $state['soldout'] ?? [];

        $colorRows = $colors->isEmpty() ? collect([null]) : $colors;
        $sizeRows = $sizes->isEmpty() ? collect([null]) : $sizes;

        DB::transaction(function () use ($product, $colorRows, $sizeRows, $soldout, $colors, $sizes): void {
            $keep = [];
            $position = 0;

            // Sem cor e sem tamanho: produto sem variações.
            if ($colors->isEmpty() && $sizes->isEmpty()) {
                $product->variants()->delete();

                return;
            }

            foreach ($colorRows as $color) {
                $name = $color['name'] ?? null;
                $soldSizes = (array) ($soldout[static::colorKey($name)] ?? []);

                foreach ($sizeRows as $size) {
                    $variant = $product->variants()->updateOrCreate(
                        ['color' => $name, 'size' => $size],
                        [
                            'color_hex' => $color['hex'] ?? null,
                            'is_available' => ! in_array($size ?? self::NO_SIZE, $soldSizes, true),
                            'position' => $position++,
                        ],
                    );

                    $keep[] = $variant->getKey();
                }
            }

            $product->variants()->whereNotIn('id', $keep)->delete();
        });

        $product->unsetRelation('variants');
    }
}
