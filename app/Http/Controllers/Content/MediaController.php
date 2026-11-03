<?php

namespace App\Http\Controllers\Content;

use App\Actions\Content\StoreMedia;
use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Media;
use App\Models\User;
use App\Models\Workspace;
use App\Support\AuditLogger;
use App\Support\MediaUsage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class MediaController extends Controller
{
    public function index(Request $request, Workspace $workspace, Blog $blog): Response
    {
        return Inertia::render('content/media', [
            'media' => $this->paginate($request, $blog, 24),
            'query' => $request->only('search'),
        ]);
    }

    /**
     * JSON listing for the media picker dialog.
     */
    public function picker(Request $request, Workspace $workspace, Blog $blog): JsonResponse
    {
        return response()->json($this->paginate($request, $blog, 18));
    }

    public function store(Request $request, Workspace $workspace, Blog $blog, StoreMedia $store): JsonResponse|RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:'.config('sbblog.media.max_kilobytes'), 'mimes:'.implode(',', config('sbblog.media.mimes'))],
            'alt_text' => ['nullable', 'string', 'max:500'],
        ]);

        /** @var User $user */
        $user = $request->user();
        /** @var UploadedFile $file */
        $file = $request->file('file');

        $media = $store->handle($blog, $user, $file, $request->string('alt_text')->toString() ?: null);

        if ($request->expectsJson()) {
            return response()->json($this->present($media), 201);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Uploaded.')]);

        return back();
    }

    public function update(Request $request, Workspace $workspace, Blog $blog, Media $media, AuditLogger $audit): RedirectResponse
    {
        Gate::authorize('update', $media);

        $media->update($request->validate(['alt_text' => ['nullable', 'string', 'max:500']]));
        $audit->record('media.updated', $media, blog: $blog);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Saved.')]);

        return back();
    }

    public function destroy(Workspace $workspace, Blog $blog, Media $media, AuditLogger $audit): RedirectResponse
    {
        Gate::authorize('delete', $media);

        $usage = MediaUsage::of($media);

        if ($usage['articles'] > 0 || $usage['brand'] || $usage['avatars'] > 0) {
            throw ValidationException::withMessages(['media' => __('This file is still in use (:articles articles, brand: :brand, avatars: :avatars).', [
                'articles' => $usage['articles'],
                'brand' => $usage['brand'] ? __('yes') : __('no'),
                'avatars' => $usage['avatars'],
            ])]);
        }

        $disk = Storage::disk($media->filesystemDisk());
        $disk->delete([$media->path, ...array_values($media->variants ?? [])]);
        $media->delete();

        $audit->record('media.deleted', null, ['path' => $media->path], blog: $blog);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Deleted.')]);

        return back();
    }

    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function paginate(Request $request, Blog $blog, int $perPage): LengthAwarePaginator
    {
        $search = $request->string('search')->trim()->toString();

        return Media::query()
            ->whereBelongsTo($blog)
            ->when($search !== '', fn ($query) => $query->where('alt_text', 'ilike', '%'.addcslashes($search, '%_\\').'%'))
            ->latest()
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Media $media) => $this->present($media));
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Media $media): array
    {
        return [
            'id' => $media->id,
            'url' => $media->url(),
            'thumb_url' => $media->url('thumb'),
            'mime_type' => $media->mime_type,
            'size_bytes' => $media->size_bytes,
            'width' => $media->width,
            'height' => $media->height,
            'alt_text' => $media->alt_text,
            'uploaded_by' => $media->uploaded_by,
            'created_at' => $media->created_at?->toIso8601String(),
        ];
    }
}
