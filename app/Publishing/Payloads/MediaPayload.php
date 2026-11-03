<?php

namespace App\Publishing\Payloads;

use App\Models\Media;

class MediaPayload
{
    /**
     * @return array{id: string, url: string, mime_type: string, width: int|null, height: int|null, alt: string|null, variants: object}
     */
    public static function make(Media $media): array
    {
        return [
            'id' => $media->id,
            'url' => $media->url(),
            'mime_type' => $media->mime_type,
            'width' => $media->width,
            'height' => $media->height,
            'alt' => $media->alt_text,
            // Always a JSON object, even when empty.
            'variants' => (object) collect($media->variants ?? [])
                ->mapWithKeys(fn (string $path, string $name) => [$name => $media->url($name)])
                ->all(),
        ];
    }

    /**
     * @return array{id: string, url: string, mime_type: string, width: int|null, height: int|null, alt: string|null, variants: object}|null
     */
    public static function maybe(?Media $media): ?array
    {
        return $media === null ? null : self::make($media);
    }
}
