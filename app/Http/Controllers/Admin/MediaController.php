<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\PaginatesTables;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MediaRequest;
use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MediaController extends Controller
{
    use PaginatesTables;

    public function __construct(protected MediaService $media) {}

    public function index(Request $request): Response
    {
        return Inertia::render('admin/media/index', [
            'media' => $this->library($request)->paginate(42)->withQueryString(),
            'filters' => [
                'search' => trim((string) $request->query('search', '')),
                'type' => $this->typeFilter($request),
            ],
        ]);
    }

    /**
     * JSON listing used by the media picker modal and the editor.
     */
    public function browse(Request $request): JsonResponse
    {
        return response()->json($this->library($request)->paginate(28)->withQueryString());
    }

    public function store(MediaRequest $request): JsonResponse
    {
        $media = $this->media->store($request->file('file'), $request->user()?->id);

        return response()->json($media, 201);
    }

    public function update(MediaRequest $request, Media $media): RedirectResponse
    {
        $media->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Media berhasil diperbarui.']);

        return to_route('admin.media.index');
    }

    public function destroy(Media $media): RedirectResponse
    {
        $usages = $this->media->usageCount($media);

        if ($usages > 0) {
            Inertia::flash('toast', [
                'type' => 'danger',
                'message' => "Media masih dipakai di {$usages} data dan tidak dapat dihapus.",
            ]);

            return to_route('admin.media.index');
        }

        $this->media->delete($media);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Media berhasil dihapus.']);

        return to_route('admin.media.index');
    }

    /**
     * @return Builder<Media>
     */
    private function library(Request $request): Builder
    {
        $query = Media::query()->latest('id');
        $search = trim((string) $request->query('search', ''));

        if ($search !== '') {
            $like = '%'.addcslashes($search, '\\%_').'%';
            $query->where(fn ($group) => $group->where('name', 'like', $like)->orWhere('alt', 'like', $like));
        }

        match ($this->typeFilter($request)) {
            'image' => $query->where('mime_type', 'like', 'image/%'),
            'document' => $query->where('mime_type', 'not like', 'image/%'),
            default => null,
        };

        return $query;
    }

    private function typeFilter(Request $request): string
    {
        $type = (string) $request->query('type', 'all');

        return in_array($type, ['image', 'document'], true) ? $type : 'all';
    }
}
