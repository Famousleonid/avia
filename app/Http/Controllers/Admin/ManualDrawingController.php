<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Manual;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ManualDrawingController extends Controller
{
    public const COLLECTION = 'manual_drawings';

    public function index(Manual $manual)
    {
        return response()->json(['items' => $manual->media()
            ->where('collection_name', self::COLLECTION)->latest('id')->get()
            ->map(fn (Media $media) => $this->item($manual, $media))])
            ->header('Cache-Control', 'no-store, private');
    }

    public function store(Request $request, Manual $manual)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240']]);
        $media = $manual->addMediaFromRequest('file')->withCustomProperties([
            'uploaded_by' => $request->user()->id,
            'uploader_name' => $request->user()->selection_name,
        ])->toMediaCollection(self::COLLECTION, 'private');
        activity('manual')->performedOn($manual)->causedBy($request->user())
            ->withProperties(['source' => self::COLLECTION, 'media_id' => $media->id, 'file' => $media->file_name])
            ->log('Manual drawing uploaded');
        return response()->json($this->item($manual, $media), 201);
    }

    public function show(Manual $manual, Media $media)
    {
        $this->assertBelongsToManual($manual, $media);
        abort_unless(is_file($media->getPath()), 404);
        return response()->file($media->getPath(), [
            'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function update(Request $request, Manual $manual, Media $media)
    {
        $this->assertBelongsToManual($manual, $media);
        abort_unless($this->canManage($media), 403);
        $validated = $request->validate(['name' => ['required', 'string', 'max:200', 'not_regex:/[\\x00-\\x1F\\x7F]/u']]);
        return DB::transaction(function () use ($manual, $media, $validated) {
            $media = Media::whereKey($media->id)->lockForUpdate()->firstOrFail();
            $this->assertBelongsToManual($manual, $media);
            abort_unless($this->canManage($media), 403);
            $oldName = $media->getCustomProperty('display_name', $media->file_name);
            $media->setCustomProperty('display_name', $validated['name'])->save();
            activity('manual')->performedOn($manual)->causedBy(auth()->user())
                ->withProperties(['source' => self::COLLECTION, 'media_id' => $media->id,
                    'old' => ['name' => $oldName], 'attributes' => ['name' => $validated['name']]])
                ->log('Manual drawing renamed');
            return response()->json($this->item($manual, $media));
        });
    }

    public function destroy(Manual $manual, Media $media)
    {
        return DB::transaction(function () use ($manual, $media) {
            $media = Media::whereKey($media->id)->lockForUpdate()->firstOrFail();
            $this->assertBelongsToManual($manual, $media);
            abort_unless($this->canManage($media), 403);
            activity('manual')->performedOn($manual)->causedBy(auth()->user())
                ->withProperties(['source' => self::COLLECTION, 'media_id' => $media->id,
                    'file' => $media->file_name, 'name' => $media->getCustomProperty('display_name', $media->file_name)])
                ->log('Manual drawing deleted');
            $media->delete();
            return response()->json(['success' => true]);
        });
    }

    private function canManage(Media $media): bool
    {
        $user = auth()->user();
        return $user && ($user->roleIs('Admin')
            || (int) $media->getCustomProperty('uploaded_by', 0) === (int) $user->id);
    }

    private function assertBelongsToManual(Manual $manual, Media $media): void
    {
        abort_unless($media->model_type === $manual->getMorphClass()
            && (int) $media->model_id === (int) $manual->id
            && $media->collection_name === self::COLLECTION, 404);
    }

    private function item(Manual $manual, Media $media): array
    {
        return [
            'id' => $media->id, 'name' => $media->getCustomProperty('display_name', $media->file_name), 'mime' => $media->mime_type,
            'size' => $media->size, 'uploaded_by' => $media->getCustomProperty('uploader_name', ''),
            'date' => format_project_date($media->created_at),
            'url' => route('manuals.drawings.show', [$manual, $media]),
            'can_manage' => $this->canManage($media),
            'update_url' => route('manuals.drawings.update', [$manual, $media]),
            'delete_url' => route('manuals.drawings.destroy', [$manual, $media]),
        ];
    }
}
