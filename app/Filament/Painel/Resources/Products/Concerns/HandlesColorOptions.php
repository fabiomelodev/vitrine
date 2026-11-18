<?php

namespace App\Filament\Painel\Resources\Products\Concerns;

use App\Support\VariantGrid;

/**
 * Separa o campo "Cores" do formulário: as fotos de cada cor vão para
 * products.color_images e a grade (cores × tamanhos) vira product_variants.
 */
trait HandlesColorOptions
{
    /** @var array<int, array{name: string, hex: ?string}> */
    protected array $colorOptions = [];

    /**
     * @param  array<string, mixed>  $data  Estado já validado (arquivos já salvos no disco).
     * @return array<string, mixed>
     */
    protected function extractColorOptions(array $data): array
    {
        $colors = collect($data['color_options'] ?? [])
            ->map(fn (array $c) => [
                'name' => trim((string) ($c['name'] ?? '')),
                'hex' => $c['hex'] ?? null,
                'images' => array_values(array_filter((array) ($c['images'] ?? []))),
            ])
            ->filter(fn (array $c) => $c['name'] !== '')
            ->unique(fn (array $c) => mb_strtolower($c['name']))
            ->values();

        $this->colorOptions = $colors->map(fn (array $c) => ['name' => $c['name'], 'hex' => $c['hex']])->all();

        $data['color_images'] = $colors
            ->filter(fn (array $c) => $c['images'] !== [])
            ->mapWithKeys(fn (array $c) => [$c['name'] => $c['images']])
            ->all() ?: null;

        unset($data['color_options']);

        return $data;
    }

    protected function syncVariantGrid(): void
    {
        VariantGrid::sync($this->record, [
            ...($this->data ?? []),
            'color_options' => $this->colorOptions,
        ]);
    }
}
