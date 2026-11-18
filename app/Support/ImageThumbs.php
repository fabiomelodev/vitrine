<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Miniaturas das fotos (cards, sacola), geradas com a extensão GD do PHP,
 * que está disponível na maioria das hospedagens compartilhadas.
 *
 * foto:      stores/1/products/abc.jpg
 * miniatura: stores/1/products/thumbs/abc-600.webp
 */
class ImageThumbs
{
    public const WIDTH = 600;

    public static function thumbPath(string $path): string
    {
        $dir = trim(dirname($path), '.');
        $name = pathinfo($path, PATHINFO_FILENAME);

        return ($dir !== '' ? $dir.'/' : '').'thumbs/'.$name.'-'.self::WIDTH.'.'.static::extension();
    }

    protected static function extension(): string
    {
        return function_exists('imagewebp') ? 'webp' : 'jpg';
    }

    public static function exists(string $path): bool
    {
        return Storage::disk('public')->exists(static::thumbPath($path));
    }

    /**
     * Gera a miniatura. Se der qualquer problema, a vitrine usa a foto original.
     */
    public static function generate(string $path): bool
    {
        if (! function_exists('imagecreatefromstring')) {
            return false;
        }

        $disk = Storage::disk('public');

        try {
            if (! $disk->exists($path)) {
                return false;
            }

            $source = @imagecreatefromstring($disk->get($path));

            if (! $source) {
                return false;
            }

            $width = imagesx($source);
            $height = imagesy($source);
            $targetWidth = min(self::WIDTH, $width);
            $targetHeight = (int) round($height * ($targetWidth / $width));

            $thumb = imagecreatetruecolor($targetWidth, $targetHeight);
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
            imagecopyresampled($thumb, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

            ob_start();
            static::extension() === 'webp' ? imagewebp($thumb, null, 80) : imagejpeg($thumb, null, 82);
            $binary = ob_get_clean();

            imagedestroy($source);
            imagedestroy($thumb);

            return $disk->put(static::thumbPath($path), $binary);
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }

    public static function delete(string $path): void
    {
        Storage::disk('public')->delete([$path, static::thumbPath($path)]);
    }
}
