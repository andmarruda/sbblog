<?php

namespace App\Actions\Content;

use App\Enums\MediaDisk;
use App\Jobs\ProcessMedia;
use App\Models\AppInstallation;
use App\Models\Blog;
use App\Models\Media;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class StoreMedia
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Store an upload. A file identical to one already in the blog returns that media instead.
     */
    public function handle(Blog $blog, User|AppInstallation $uploader, UploadedFile $file, ?string $alt = null): Media
    {
        $checksum = (string) hash_file('sha256', $file->getRealPath());

        $existing = Media::query()->whereBelongsTo($blog)->where('checksum', $checksum)->first();

        if ($existing !== null) {
            return $existing;
        }

        $disk = MediaDisk::from((string) config('sbblog.media.disk'));
        $extension = strtolower($file->guessExtension() ?? $file->getClientOriginalExtension());
        $path = $file->storeAs(
            "blogs/{$blog->id}/".now()->format('Y/m'),
            Str::uuid7().'.'.$extension,
            ['disk' => $disk === MediaDisk::Local ? 'public' : $disk->value],
        );

        [$width, $height] = @getimagesize($file->getRealPath()) ?: [null, null];

        $media = Media::query()->create([
            'blog_id' => $blog->id,
            'uploaded_by' => $uploader instanceof User ? $uploader->id : null,
            'disk' => $disk,
            'path' => $path,
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'size_bytes' => $file->getSize(),
            'width' => $width,
            'height' => $height,
            'alt_text' => $alt ?? Str::of($file->getClientOriginalName())->beforeLast('.')->replace(['-', '_'], ' ')->limit(120)->toString(),
            'checksum' => $checksum,
        ]);

        ProcessMedia::dispatch($media);

        $this->audit->record('media.uploaded', $media, ['name' => $file->getClientOriginalName()], blog: $blog, app: $uploader instanceof AppInstallation ? $uploader : null);

        return $media;
    }
}
