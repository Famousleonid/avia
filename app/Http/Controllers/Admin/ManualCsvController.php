<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Manual;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;
use League\Csv\Writer;

class ManualCsvController extends Controller
{
    public function upload(Request $request, Manual $manual)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240', // Максимальный размер 10MB
        ]);

        // Удаляем старый файл, если он существует
        $manual->clearMediaCollection('csv_files');

        // Сохраняем новый файл
        $manual->addMediaFromRequest('csv_file')
            ->toMediaCollection('csv_files');

        return redirect()->back()->with('success', 'CSV файл успешно загружен');
    }

    public function download(Manual $manual)
    {
        $media = $manual->getMedia('csv_files')->first();

        if (!$media) {
            return redirect()->back()->with('error', 'CSV файл не найден');
        }

        return response()->download($media->getPath(), $media->file_name);
    }

    public function view(Manual $manual, $file)
    {
        try {
            $media = $manual->getMedia('csv_files')->firstWhere('id', $file);

            if (!$media) {
                return redirect()->back()->with('error', 'CSV файл не найден');
            }

            $path = $media->getPath();
            $records = [];
            $headers = [];

            if (($handle = fopen($path, "r")) !== FALSE) {
                // Читаем заголовки
                if (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    $headers = $data;
                }

                // Читаем данные
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    $records[] = $data;
                }
                fclose($handle);
            }

            return view('admin.manuals.csv-view', compact('manual', 'records', 'headers', 'media'));
        } catch (\Exception $e) {
            \Log::error('Error viewing CSV file: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Ошибка при просмотре файла: ' . $e->getMessage());
        }
    }

    public function delete(Manual $manual, $file)
    {
        try {
            $media = $manual->getMedia('csv_files')->firstWhere('id', $file);

            if (!$media) {
                return response()->json([
                    'success' => false,
                    'error' => 'CSV файл не найден'
                ], 404);
            }

            $media->delete();

            return response()->json([
                'success' => true,
                'message' => 'CSV файл успешно удален'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error deleting CSV file: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Ошибка при удалении файла: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request, Manual $manual)
    {
        try {
            if (!$request->hasFile('csv_file')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Файл не был загружен'
                ], 400);
            }

            $file = $request->file('csv_file');
            $processType = $request->input('process_type');

            // Проверяем, существует ли уже файл с таким process_type
            if ($processType) {
                $existingFile = $manual->getMedia('csv_files')
                    ->first(function ($media) use ($processType) {
                        return $media->getCustomProperty('process_type') === $processType;
                    });

                // Если файл существует, удаляем его
                if ($existingFile) {
                    $existingFile->delete();
                }
            }

            // Добавляем новый файл
            $media = $manual->addMedia($file)
                ->withCustomProperties(['process_type' => $processType])
                ->toMediaCollection('csv_files');

            return response()->json([
                'success' => true,
                'message' => 'CSV файл успешно загружен',
                'file' => [
                    'id' => $media->id,
                    'name' => $media->file_name,
                    'process_type' => $processType
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error uploading CSV file: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Ошибка при загрузке файла: ' . $e->getMessage()
            ], 500);
        }
    }
}
