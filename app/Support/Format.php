<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class Format
{
    public static function money(float|string|null $value): string
    {
        return 'R$ '.number_format((float) $value, 2, ',', '.');
    }

    /**
     * URL da foto. Com $thumb = true, usa a miniatura (600px) quando ela existe.
     */
    public static function image(?string $path, bool $thumb = false): ?string
    {
        if (! $path) {
            return null;
        }

        if ($thumb && ImageThumbs::exists($path)) {
            return Storage::disk('public')->url(ImageThumbs::thumbPath($path));
        }

        return Storage::disk('public')->url($path);
    }

    public static function phone(?string $value): string
    {
        $d = preg_replace('/\D/', '', (string) $value);

        return strlen($d) === 11
            ? sprintf('(%s) %s-%s', substr($d, 0, 2), substr($d, 2, 5), substr($d, 7))
            : (string) $value;
    }

    /**
     * Ordena tamanhos de forma natural: PP, P, M, G, GG... e depois numéricos.
     *
     * @param  iterable<string>  $sizes
     * @return array<int, string>
     */
    public static function sortSizes(iterable $sizes): array
    {
        $order = ['U', 'UN', 'ÚNICO', 'PP', 'XS', 'P', 'S', 'M', 'G', 'L', 'GG', 'XL', 'XG', 'XXL', 'EXG', 'G1', 'G2', 'G3'];

        $list = collect($sizes)->filter()->unique()->values()->all();

        usort($list, function (string $a, string $b) use ($order): int {
            $ia = array_search(mb_strtoupper($a), $order, true);
            $ib = array_search(mb_strtoupper($b), $order, true);

            if ($ia !== false && $ib !== false) {
                return $ia <=> $ib;
            }
            if (is_numeric($a) && is_numeric($b)) {
                return (float) $a <=> (float) $b;
            }
            if ($ia !== false) {
                return -1;
            }
            if ($ib !== false) {
                return 1;
            }

            return strnatcasecmp($a, $b);
        });

        return $list;
    }
}
