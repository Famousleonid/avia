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

    public function view(Manual $manual, $file = null)
    {
        if ($file) {
            $media = $manual->getMedia('csv_files')->firstWhere('id', $file);
        } else {
            $media = $manual->getMedia('csv_files')->first();
        }
        
        if (!$media) {
            return redirect()->back()->with('error', 'CSV файл не найден');
        }

        $csv = Reader::createFromPath($media->getPath(), 'r');
        $csv->setHeaderOffset(0);
        
        $records = $csv->getRecords();
        $headers = $csv->getHeader();

        return view('admin.manuals.csv-view', compact('manual', 'records', 'headers', 'media'));
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
} 