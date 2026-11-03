<?php

namespace App\Jobs;

use App\Models\Media;
use GdImage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Builds the WebP and thumbnail variants of an image. Needs GD with WebP support; without it
 * the original is used everywhere.
 */
class ProcessMedia implements ShouldQueue
{
    use Queueable;

    public const THUMB_WIDTH = 480;

    public const MAX_WIDTH = 2400;

    public int $tries = 3;

    public function __construct(public readonly Media $media) {}

    public function handle(): void
    {
        $media = $this->media->fresh();

        if ($media === null || ! self::supported($media->mime_type) || $media->blog?->autoconvert_webp === false) {
            return;
        }

        if (! function_exists('imagewebp') || ! function_exists('imagecreatefromstring')) {
            Log::warning('GD with WebP support is missing; media variants were not generated.', ['media' => $media->id]);

            return;
        }

        $disk = Storage::disk($media->filesystemDisk());
        $source = @imagecreatefromstring((string) $disk->get($media->path));

        if (! $source instanceof GdImage) {
            return;
        }

        $base = Str::beforeLast($media->path, '.');
        $variants = [];

        foreach (['webp' => self::MAX_WIDTH, 'thumb' => self::THUMB_WIDTH] as $name => $maxWidth) {
            $image = self::resize($source, $maxWidth);

            ob_start();
            imagewebp($image, null, $name === 'thumb' ? 75 : 82);
            $disk->put($path = "{$base}.{$name}.webp", (string) ob_get_clean(), ['visibility' => 'public']);

            $variants[$name] = $path;
        }

        $media->forceFill(['variants' => $variants])->save();
    }

    public static function supported(string $mimeType): bool
    {
        return in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true);
    }

    private static function resize(GdImage $source, int $maxWidth): GdImage
    {
        $width = imagesx($source);
        $height = imagesy($source);

        if ($width <= $maxWidth) {
            return $source;
        }

        $resized = imagescale($source, $maxWidth, (int) round($height * $maxWidth / $width));

        return $resized instanceof GdImage ? $resized : $source;
    }
}
