<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Manual, Tdr, TravelerProcessTemplate, TravelerTemplateImage};
use App\Services\{TravelerProcessTemplates, TravelerTemplateImages};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Storage};

class TravelerTemplateImageController extends Controller
{
    private function authorizeTemplate(Request $request, Manual $manual, TravelerProcessTemplate $template): void
    {
        abort_unless(TravelerProcessTemplates::canManage($request->user(), $manual), 403);
        abort_unless((int) $template->manual_id === (int) $manual->id, 404);
    }

    public function store(Request $request, Manual $manual, TravelerProcessTemplate $template)
    {
        $this->authorizeTemplate($request, $manual, $template);
        $request->validate([
            'revision' => ['required', 'integer', 'min:1'],
            'images' => ['required', 'array', 'min:1', 'max:20'],
            'images.*' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);
        foreach ($request->file('images') as $index => $file) {
            if ($file->getMimeType() !== 'application/pdf') {
                validator(['images' => [$index => $file]], [
                    'images.*' => ['image', 'dimensions:max_width=10000,max_height=10000'],
                ])->validate();
            }
        }
        $stored = [];
        try {
            $saved = DB::transaction(function () use ($request, $template, &$stored) {
                $template = TravelerProcessTemplate::lockForUpdate()->findOrFail($template->id);
                abort_if($template->revision !== (int) $request->input('revision'), 409, 'The template was edited by another user. Reload before saving.');
                abort_if($template->images()->count() + count($request->file('images')) > 20, 422, 'Maximum 20 attachments per template.');
                $order = (int) $template->images()->max('sort_order');
                foreach ($request->file('images') as $file) {
                    $path = $file->store('traveler-template-images/'.$template->id, 'local');
                    if (!$path) throw new \RuntimeException('Unable to store the image.');
                    $stored[] = $path;
                    $template->images()->create(['path' => $path, 'name' => mb_substr(basename($file->getClientOriginalName()), 0, 255),
                        'mime' => $file->getMimeType(), 'sort_order' => ++$order]);
                }
                $template->increment('revision');
                activity('traveler_templates')->performedOn($template)->causedBy($request->user())
                    ->withProperties(['images' => $template->images()->get()->toArray()])->log('Template images added');
                return $template->fresh(['steps', 'images']);
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($stored);
            throw $e;
        }
        return response()->json(['template' => $saved]);
    }

    public function change(Request $request, Manual $manual, TravelerProcessTemplate $template, TravelerTemplateImage $image)
    {
        $this->authorizeTemplate($request, $manual, $template);
        abort_unless((int) $image->template_id === (int) $template->id, 404);
        $data = $request->validate(['revision' => ['required', 'integer', 'min:1'], 'action' => ['required', 'in:delete,up,down']]);
        $saved = DB::transaction(function () use ($request, $template, $image, $data) {
            $template = TravelerProcessTemplate::lockForUpdate()->findOrFail($template->id);
            abort_if($template->revision !== (int) $data['revision'], 409, 'The template was edited by another user. Reload before saving.');
            $image = $template->images()->findOrFail($image->id);
            if ($data['action'] === 'delete') {
                // Retain the original file for audit; it is no longer served or printed.
                $image->delete();
            } else {
                $images = $template->images()->get();
                $index = $images->search(fn ($item) => $item->id === $image->id);
                $other = $images->get($index + ($data['action'] === 'up' ? -1 : 1));
                if ($other) {
                    $position = $image->sort_order;
                    $image->update(['sort_order' => $other->sort_order]);
                    $other->update(['sort_order' => $position]);
                }
            }
            $template->increment('revision');
            activity('traveler_templates')->performedOn($template)->causedBy($request->user())
                ->withProperties(['action' => $data['action'], 'image_id' => $image->id])->log('Template image changed');
            return $template->fresh(['steps', 'images']);
        });
        return response()->json(['template' => $saved]);
    }

    public function show(Request $request, TravelerTemplateImage $image)
    {
        $manual = Manual::findOrFail($image->template->manual_id);
        abort_unless(TravelerProcessTemplates::canManage($request->user(), $manual), 403);
        return $this->file($image);
    }

    public function tdrImage(Request $request, Tdr $tdr, TravelerTemplateImage $image)
    {
        abort_unless($request->user()->can('workorders.view', $tdr->workorder), 403);
        abort_unless(TravelerTemplateImages::forTdr($tdr)->contains('id', $image->id), 404);
        return $this->file($image);
    }

    private function file(TravelerTemplateImage $image)
    {
        $disk = Storage::disk('local');
        abort_unless($disk->exists($image->path), 404);
        return response()->file($disk->path($image->path), [
            'Content-Type' => $image->mime, 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store',
        ]);
    }
}
