<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProcessName;
use App\Models\Manual;
use App\Models\Tdr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProcessPrintAttachmentController extends Controller
{
    private function authorizeManager(): void
    {
        abort_unless(auth()->user()?->roleIs(['Admin', 'Manager']), 403);
    }

    private function owned(ProcessName $processName, Media $media): void
    {
        abort_unless($media->model_type === $processName->getMorphClass()
            && (int) $media->model_id === (int) $processName->id
            && $media->collection_name === ProcessName::PRINT_ATTACHMENT_COLLECTION, 404);
    }

    private function tdrContext(Request $request): ?Tdr
    {
        if (! $request->filled('tdr_id')) return null;
        $request->validate(['tdr_id' => ['required', 'integer', 'exists:tdrs,id']]);
        $tdr = Tdr::with(['component.manual', 'workorder'])->findOrFail($request->integer('tdr_id'));
        abort_unless($request->user()->can('workorders.view', $tdr->workorder), 403);
        abort_unless($tdr->component?->manual_id && trim((string) $tdr->component?->part_number) !== '', 422, 'The TDR part must have a manual and part number.');
        return $tdr;
    }

    public function index(Request $request, ProcessName $processName)
    {
        $this->authorizeManager();
        $tdr = $this->tdrContext($request);
        $manuals = Manual::withTrashed()->orderBy('number')->get(['id', 'number', 'lib', 'deleted_at']);
        $images = $processName->getMedia(ProcessName::PRINT_ATTACHMENT_COLLECTION);
        if ($tdr) {
            $key = ProcessName::printPartKey($tdr->component->part_number);
            $images = $images->filter(fn ($image) =>
                ProcessName::printPartKey((string) $image->getCustomProperty('part_number')) === $key
                && (! $image->getCustomProperty('manual_id') || (int) $image->getCustomProperty('manual_id') === (int) $tdr->component->manual_id));
        }
        return view('admin.directories.process-print-attachments', compact('processName', 'manuals', 'images', 'tdr'));
    }

    public function store(Request $request, ProcessName $processName)
    {
        $this->authorizeManager();
        $tdr = $this->tdrContext($request);
        if ($tdr) $request->merge(['manual_id' => $tdr->component->manual_id, 'part_number' => $tdr->component->part_number]);
        $data = $request->validate([
            'manual_id' => ['required', 'integer', 'exists:manuals,id'],
            'part_number' => ['required', 'string', 'max:150'],
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);
        $part = trim($data['part_number']);
        abort_if($part === '', 422, 'Part number is required.');
        $manualId = (int) $data['manual_id'];
        DB::transaction(function () use ($processName, $part, $manualId) {
            $process = ProcessName::whereKey($processName->id)->lockForUpdate()->firstOrFail();
            $old = $process->printAttachmentFor($part, $manualId);
            $new = $process->addMediaFromRequest('image')
                ->withCustomProperties(['manual_id' => $manualId, 'part_number' => $part, 'part_number_key' => ProcessName::printPartKey($part)])
                ->toMediaCollection(ProcessName::PRINT_ATTACHMENT_COLLECTION);
            activity('process_name')->performedOn($process)->causedBy(auth()->user())
                ->withProperties(['manual_id' => $manualId, 'part_number' => $part, 'old_media_id' => $old?->id,
                    'old_file' => $old?->file_name, 'media_id' => $new->id, 'file' => $new->file_name])
                ->log($old ? 'Print attachment replaced' : 'Print attachment added');
            $old?->delete();
        });
        return response()->json(['success' => true]);
    }

    public function assignManual(Request $request, ProcessName $processName, Media $media)
    {
        $this->authorizeManager();
        $this->owned($processName, $media);
        $tdr = $this->tdrContext($request);
        if ($tdr) {
            abort_unless(ProcessName::printPartKey((string) $media->getCustomProperty('part_number')) === ProcessName::printPartKey($tdr->component->part_number), 422);
            $request->merge(['manual_id' => $tdr->component->manual_id]);
        }
        $data = $request->validate(['manual_id' => ['required', 'integer', 'exists:manuals,id']]);
        DB::transaction(function () use ($processName, $media, $data) {
            $process = ProcessName::whereKey($processName->id)->lockForUpdate()->firstOrFail();
            $image = Media::whereKey($media->id)->lockForUpdate()->firstOrFail();
            $manualId = (int) $data['manual_id'];
            $part = (string) $image->getCustomProperty('part_number');
            $existing = $process->printAttachmentFor($part, $manualId);
            abort_if($existing && $existing->id !== $image->id, 409, 'This manual, process and P/N already has an image. Both images have been kept.');
            $oldManualId = $image->getCustomProperty('manual_id');
            $image->setCustomProperty('manual_id', $manualId);
            $image->setCustomProperty('part_number_key', ProcessName::printPartKey($part));
            $image->save();
            activity('process_name')->performedOn($process)->causedBy(auth()->user())
                ->withProperties(['media_id' => $image->id, 'part_number' => $part, 'old_manual_id' => $oldManualId, 'manual_id' => $manualId])
                ->log('Print attachment manual assigned');
        });
        return response()->json(['success' => true]);
    }

    public function destroy(ProcessName $processName, Media $media)
    {
        $this->authorizeManager();
        $this->owned($processName, $media);
        activity('process_name')->performedOn($processName)->causedBy(auth()->user())
            ->withProperties(['part_number' => $media->getCustomProperty('part_number'), 'media_id' => $media->id, 'file' => $media->file_name])
            ->log('Print attachment deleted');
        $media->delete();
        return response()->json(['success' => true]);
    }

    public function image(ProcessName $processName, Media $media)
    {
        $this->owned($processName, $media);
        abort_unless(is_file($media->getPath()), 404);
        return response()->file($media->getPath(), ['Cache-Control' => 'private, max-age=3600', 'X-Content-Type-Options' => 'nosniff']);
    }
}
