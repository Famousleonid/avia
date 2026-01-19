<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Workorder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaController extends Controller
{


    protected function store_avatar(Request $request, $id)
    {
        $user = User::find($id);
        $file = $request->File('avatar');

        if ($request->hasFile('avatar')) {
            $user->clearMediaCollection('avatar');
            $user->addMedia($file)->toMediaCollection('avatar');
        }

        return redirect()->route('cabinet.profile');
    }

    public function store_photo_workorders(Request $request, Workorder $workorder)
    {

        $category = $request->query('category', 'photos');

        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                // Формируем уникальное читаемое имя файла
                $filename = 'wo_' . $workorder->number . '_' . now()->format('Ymd_Hi') . '_' . Str::random(3) . '.' . $photo->getClientOriginalExtension();

                $workorder->addMedia($photo)
                    ->usingFileName($filename)
                    ->toMediaCollection($category);
            }
        }

        // Формируем список загруженных файлов для фронта
        $uploadedPhotos = [];
        foreach ($workorder->getMedia($category) as $media) {
            if (!$media->id) continue;

            $uploadedPhotos[] = [
                'id' => $media->id,
                'big_url' => route('image.show.big', [
                    'mediaId' => $media->id,
                    'modelId' => $workorder->id,
                    'mediaName' => $category
                ]),
                'thumb_url' => route('image.show.thumb', [
                    'mediaId' => $media->id,
                    'modelId' => $workorder->id,
                    'mediaName' => $category
                ]),
                'alt' => $media->name ?? 'Photo',
            ];
        }

        // Ответ для JS
        return response()->json([
            'success' => true,
            'photos' => $uploadedPhotos,
            'photo_count' => count($uploadedPhotos),
        ]);
    }

    public function delete_photo($id)
    {
        $media = Media::findOrFail($id);
        $media->delete();

        return response()->json(['success' => true]);
    }

    protected function mobile_store_avatar(Request $request, $id)
    {
        $user = User::find($id);
        $file = $request->File('avatar');
        $size = $request->File('avatar')->getSize();

        if ($request->hasFile('avatar')) {
            $user->clearMediaCollection('avatar');
            $user->addMedia($file)->toMediaCollection('avatar');
        }

        return redirect()->route('mobile.profile');
    }

    public function showThumb($mediaId, $modelId, $mediaName)
    {
        $media = Media::findOrFail($mediaId);
        if (!$media->mime_type || !str_starts_with($media->mime_type, 'image/')) {
            abort(404);
        }
        // путь к thumb
        $thumbPath = $media->getPath('thumb');

        // ✅ fallback: если thumb не существует — отдаём оригинал
        if (!$thumbPath || !file_exists($thumbPath)) {
            $originalPath = $media->getPath();

            if (!$originalPath || !file_exists($originalPath)) {
                abort(404, 'Media file not found');
            }

            return response()->file($originalPath);
        }

        return response()->file($thumbPath);
    }

    public function showBig($mediaId, $modelId, $mediaName)
    {
        $media = Media::findOrFail($mediaId);

        $path = $media->getPath();

        if (!$path || !file_exists($path)) {
            abort(404, 'Media file not found');
        }

        return response()->file($path);
    }

    public function get_photos(Workorder $workorder, Request $request)
    {
        $category = $request->query('category', 'photos'); // по умолчанию photos


        $uploadedPhotos = [];

        foreach ($workorder->getMedia($category) as $media) {
            $uploadedPhotos[] = [
                'id' => $media->id,
                'big_url' => route('image.show.big', [
                    'mediaId' => $media->id,
                    'modelId' => $workorder->id,
                    'mediaName' => $category,
                ]),
                'thumb_url' => route('image.show.thumb', [
                    'mediaId' => $media->id,
                    'modelId' => $workorder->id,
                    'mediaName' => $category,
                ]),
                'alt' => $media->name ?? 'Photo',
            ];
        }

        return response()->json([
            'success' => true,
            'photos' => $uploadedPhotos,
            'photo_count' => count($uploadedPhotos),
        ]);
    }

    /**
     * Загрузка PDF файлов для workorder
     */
    public function store_pdf_workorders(Request $request, $id)
    {
        $workorder = Workorder::findOrFail($id);

        $request->validate([
            'pdf' => 'required|mimes:pdf|max:10240', // максимум 10MB на файл
            'document_name' => 'nullable|string|max:255',
        ]);

        if ($request->hasFile('pdf')) {
            $pdf = $request->file('pdf');
            $documentName = $request->input('document_name');

            // Формируем уникальное читаемое имя файла
            $filename = 'wo_' . $workorder->number . '_' . now()->format('Ymd_Hi') . '_' . Str::random(3) . '.pdf';

            $media = $workorder->addMedia($pdf)
                ->usingFileName($filename)
                ->toMediaCollection('pdfs');

            // Сохраняем название документа в custom property
            if ($documentName) {
                $media->setCustomProperty('document_name', $documentName);
                $media->name = $documentName;
                $media->save();
            }
        }

        // Формируем список загруженных PDF для фронта
        $uploadedPdfs = [];
        foreach ($workorder->getMedia('pdfs') as $media) {
            if (!$media->id) continue;

            $documentName = $media->getCustomProperty('document_name') ?: ($media->name ?? null);

            $uploadedPdfs[] = [
                'id' => $media->id,
                'name' => $documentName ?: $media->file_name,
                'file_name' => $media->file_name,
                'size' => $media->size,
                'created_at' => $media->created_at->format('Y-m-d H:i:s'),
                'url' => route('workorders.pdf.show', [
                    'workorderId' => $workorder->id,
                    'mediaId' => $media->id,
                ]),
                'download_url' => route('workorders.pdf.download', [
                    'workorderId' => $workorder->id,
                    'mediaId' => $media->id,
                ]),
            ];
        }

        return response()->json([
            'success' => true,
            'pdfs' => $uploadedPdfs,
            'pdf_count' => count($uploadedPdfs),
        ]);
    }

    /**
     * Показать PDF файл в браузере
     */
    public function showPdf($workorderId, $mediaId)
    {
        $workorder = Workorder::findOrFail($workorderId);
        $media = $workorder->getMedia('pdfs')->where('id', $mediaId)->first();

        if (!$media) {
            abort(404, 'PDF not found');
        }

        $filePath = $media->getPath();

        if (!file_exists($filePath)) {
            abort(404, 'PDF file not found');
        }

        return response()->file($filePath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $media->file_name . '"',
        ]);
    }


    /**
     * Скачать PDF файл
     */
    public function downloadPdf($workorderId, $mediaId)
    {
        $workorder = Workorder::findOrFail($workorderId);
        $media = $workorder->getMedia('pdfs')->where('id', $mediaId)->first();

        if (!$media) {
            abort(404, 'PDF not found');
        }

        $filePath = $media->getPath();

        if (!file_exists($filePath)) {
            abort(404, 'PDF file not found');
        }

        return response()->download($filePath, $media->file_name);
    }

    /**
     * Удалить PDF файл
     */
    public function delete_pdf($id)
    {
        $media = Media::findOrFail($id);

        // Проверяем, что это PDF из коллекции pdfs
        if ($media->collection_name !== 'pdfs') {
            return response()->json(['error' => 'Invalid file type'], 400);
        }

        $media->delete();

        return response()->json(['success' => true]);
    }
}
