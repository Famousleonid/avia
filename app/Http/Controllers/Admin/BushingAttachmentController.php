<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Component;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class BushingAttachmentController extends Controller
{
    public const COLLECTION = 'bushing_documents';

    public function store(Request $request, Component $component)
    {
        abort_unless(auth()->check(), 403);
        abort_unless(Component::whereKey($component->id)->bushingCandidates()->exists(), 404);
        $request->validate(['file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240']]);
        $family = app(\App\Services\BushingDocumentFamily::class);
        $members = $family->members($component);
        $owner = $family->owner($component);
        $media = DB::transaction(function () use ($owner, $members) {
            // Serialize replacements for the same part, including uploads from different WOs.
            $part = Component::whereKey($owner->id)->lockForUpdate()->firstOrFail();
            $previous = Media::where('model_type', $part->getMorphClass())->whereIn('model_id', $members->pluck('id'))->where('collection_name', self::COLLECTION)->get();
            $media = $part->addMediaFromRequest('file')->toMediaCollection(self::COLLECTION, 'private');
            activity('component')->performedOn($part)->causedBy(auth()->user())
                ->withProperties([
                    'source' => 'bushing_documents',
                    'component_id' => $part->id,
                    'part_number' => $part->part_number,
                    'manual_id' => $part->manual_id,
                    'family_part_numbers' => $members->pluck('part_number')->all(),
                    'old' => $previous->map(fn ($item) => ['media_id' => $item->id, 'file' => $item->file_name])->all(),
                    'attributes' => ['media_id' => $media->id, 'file' => $media->file_name],
                ])->log($previous->isEmpty() ? 'Bushing document added' : 'Bushing document replaced');
            // Keep the old document until the new file has been stored successfully.
            foreach ($previous as $old) {
                $old->delete();
            }
            return $media;
        });
        return response()->json(['id' => $media->id, 'name' => $media->file_name, 'mime' => $media->mime_type,
            'url' => route('components.bushing-documents.show', [$owner, $media])], 201);
    }

    public function show(Component $component, Media $media)
    {
        abort_unless(auth()->check(), 403);
        abort_unless($media->model_type === $component->getMorphClass()
            && (int) $media->model_id === (int) $component->id
            && $media->collection_name === self::COLLECTION, 404);
        abort_unless(is_file($media->getPath()), 404);
        return response()->file($media->getPath(), ['Cache-Control' => 'private, max-age=3600', 'X-Content-Type-Options' => 'nosniff']);
    }
}
