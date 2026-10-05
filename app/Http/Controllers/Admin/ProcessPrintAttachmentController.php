<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProcessName;
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

    public function index(ProcessName $processName)
    {
        $this->authorizeManager();
        return view('admin.directories.process-print-attachments', compact('processName'));
    }

    public function store(Request $request, ProcessName $processName)
    {
        $this->authorizeManager();
        $data = $request->validate([
            'part_number' => ['required', 'string', 'max:150'],
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);
        $part = trim($data['part_number']);
        abort_if($part === '', 422, 'Part number is required.');
        DB::transaction(function () use ($processName, $part) {
            $process = ProcessName::whereKey($processName->id)->lockForUpdate()->firstOrFail();
            $old = $process->printAttachmentFor($part);
            $new = $process->addMediaFromRequest('image')
                ->withCustomProperties(['part_number' => $part, 'part_number_key' => ProcessName::printPartKey($part)])
                ->toMediaCollection(ProcessName::PRINT_ATTACHMENT_COLLECTION);
            activity('process_name')->performedOn($process)->causedBy(auth()->user())
                ->withProperties(['part_number' => $part, 'old_media_id' => $old?->id,
                    'old_file' => $old?->file_name, 'media_id' => $new->id, 'file' => $new->file_name])
                ->log($old ? 'Print attachment replaced' : 'Print attachment added');
            $old?->delete();
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
