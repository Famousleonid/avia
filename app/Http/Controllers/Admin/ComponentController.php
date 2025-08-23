<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Component;
use App\Models\Manual;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ComponentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View
     */
    public function index()
    {
        $components = Component::orderBy('ipl_num')->get();
        $manuals = Manual::all();

        return view('admin.components.index', compact('components','manuals'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Application|Factory|View
     */
    public function create()
    {
        $components = Component::all();
        $manuals = Manual::all();

        return view('admin.components.create', compact('components','manuals'));

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
//dd($request);

        $validated = $request->validate([

            'name' => 'required|string|max:250',
            'manual_id' => 'required|exists:manuals,id',
            'part_number' =>'required|string|max:50',
            'ipl_num' =>'string|max:10',
            'assy_ipl_num' => 'nullable|string|max:10|regex:/^\d+-\d+[A-Za-z]?$/',
            'bush_ipl_num' => 'nullable|string|max:10|regex:/^\d+-\d+[A-Za-z]?$/',
//
        ]);

        $validated['assy_part_number'] = $request->assy_part_number;

        $validated['log_card'] = $request->has('log_card') ? 1 : 0;
        $validated['repair'] = $request->has('repair') ? 1 : 0;
        $validated['is_bush'] = $request->has('is_bush') ? 1 : 0;
        $validated['bush_ipl_num'] = $request->bush_ipl_num;
//dd($validated);


//        $request->merge(['log_card' => $request->has('log_card') ? 1 : 0]);

        $component = Component::create($validated);

        if ($request->hasFile('img')) {
            $component->addMedia($request->file('img'))->toMediaCollection('component');
        }
        if ($request->hasFile('assy_img')) {

            $component->addMedia($request->file('assy_img'))->toMediaCollection('assy_component');
        }

        return redirect($request->input('redirect', route('components.index')))
            ->with('success', 'Component created successfully.');

    }


    public function storeFromInspection(Request $request)
    {
//            dd($request);
            $current_wo = $request->current_wo;
//                dd($current_wo);
        try {
            // Валидация данных
            $validated = $request->validate([
                'name' => 'required|string|max:250',
                'manual_id' => 'required|exists:manuals,id',
                'part_number' => 'required|string|max:50',
                'ipl_num' => 'nullable|string|max:10',
                'assy_ipl_num' => 'nullable|string|max:10|regex:/^\d+-\d+[A-Za-z]?$/',
//                'img' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
//                'assy_img' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);
            $validated['assy_part_number'] = $request->assy_part_number;
            $validated['log_card'] = $request->has('log_card') ? 1 : 0;
            $validated['repair'] = $request->has('repair') ? 1 : 0;

            // Создание нового компонента
            $component = Component::create($validated);

            // Добавление изображений, если они есть
            if ($request->hasFile('img')) {
                $component->addMedia($request->file('img'))->toMediaCollection('component');
            }

            if ($request->hasFile('assy_img')) {
                $component->addMedia($request->file('assy_img'))->toMediaCollection('assy_component');
            }

            // Возвращаем успешный ответ с данными компонента
//            return response()->json([
//                'success' => true,
//                'component' => $component
//            ]);
            return redirect()->route('tdrs.inspection.component',['workorder_id' => $current_wo])->with('success', 'Component created successfully.');

        } catch (\Exception $e) {
            // Логирование ошибки
            \Log::error('Error creating component: ' . $e->getMessage());

            // Возвращаем ошибку на фронт
            return response()->json([
                'success' => false,
                'message' => 'Error occurred while adding the component. Please try again.'
            ], 500);
        }
    }

    public function storeFromExtra(Request $request)
    {
        $current_wo = $request->current_wo;

        try {
            // Валидация данных
            $validated = $request->validate([
                'name' => 'required|string|max:250',
                'manual_id' => 'required|exists:manuals,id',
                'part_number' => 'required|string|max:50',
                'ipl_num' => 'nullable|string|max:10',
                'assy_ipl_num' => 'nullable|string|max:10|regex:/^\d+-\d+[A-Za-z]?$/',
            ]);
            $validated['assy_part_number'] = $request->assy_part_number;
            $validated['log_card'] = $request->has('log_card') ? 1 : 0;
            $validated['repair'] = $request->has('repair') ? 1 : 0;

            // Создание нового компонента
            $component = Component::create($validated);

            // Добавление изображений, если они есть
            if ($request->hasFile('img')) {
                $component->addMedia($request->file('img'))->toMediaCollection('component');
            }

            if ($request->hasFile('assy_img')) {
                $component->addMedia($request->file('assy_img'))->toMediaCollection('assy_component');
            }

            // Возвращаем JSON ответ для AJAX запроса
            return response()->json([
                'success' => true,
                'message' => 'Component created successfully.',
                'component' => $component
            ]);

        } catch (\Exception $e) {
            // Логирование ошибки
            \Log::error('Error creating component from extra: ' . $e->getMessage());

            // Возвращаем ошибку на фронт
            return response()->json([
                'success' => false,
                'message' => 'Error occurred while adding the component. Please try again.'
            ], 500);
        }
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $manual = Manual::with(['components' => function ($query) {
            $query->orderBy('ipl_num');
        }])->findOrFail($id);

        $components = $manual->components;

        return view('admin.components.show', compact('manual', 'components'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Application|Factory|View
     */
    public function edit($id)
    {
        $current_component = Component::find($id);
        $manuals = Manual::all();

        return view('admin.components.edit', compact('current_component','manuals'));

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {


        $component = Component::findOrFail($id);


//        dd( $request->all());

        $validated = $request->validate([

            'name' => 'required',
            'manual_id' => 'required|exists:manuals,id',
            'part_number' =>'required',
            'ipl_num' =>'required',
            'assy_ipl_num' => 'nullable|string|max:10|regex:/^\d+-\d+[A-Za-z]?$/',
            'bush_ipl_num' => 'nullable|string|max:10|regex:/^\d+-\d+[A-Za-z]?$/',

        ]);


        $validated['assy_part_number'] = $request->assy_part_number;
        $validated['assy_ipl_num'] = $request->assy_ipl_num;
        $validated['log_card'] = $request->has('log_card') ? 1 : 0;
        $validated['repair'] = $request->has('repair') ? 1 : 0;
        $validated['is_bush'] = $request->has('is_bush') ? 1 : 0;
        $validated['bush_ipl_num'] = $request->bush_ipl_num;
//        dd($validated);

        if ($request->hasFile('img')) {
            if ($component->getMedia('component')->isNotEmpty()) {
                $component->getMedia('component')->first()->delete();
            }

            $component->addMedia($request->file('img'))->toMediaCollection('component');
        }
        if ($request->hasFile('assy_img')) {
            if ($component->getMedia('assy_component')->isNotEmpty()) {
                $component->getMedia('assy_component')->first()->delete();
            }

            $component->addMedia($request->file('assy_img'))->toMediaCollection
            ('assy_component');
        }
        $component->update($validated);

        return redirect()->route('components.index')->with('success', 'Manual updated successfully');

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $component = Component::findOrFail($id);
        $component->delete();

        return redirect()->route('components.index')
            ->with('success', 'Компонент успешно удален.');
    }

    /**
     * Upload components from CSV file
     * Only adds new components, skips exact duplicates to avoid data redundancy
     * Duplicate check considers: all component fields (part_number + manual_id + ipl_num + name + etc.)
     * Multiple components with same part_number but different ipl_num are allowed in the same manual
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadCsv(Request $request)
    {
        try {
            $request->validate([
                'csv_file' => 'required|file|mimes:csv,txt|max:10240', // 10MB max
                'manual_id' => 'required|exists:manuals,id'
            ]);

            $file = $request->file('csv_file');
            $manualId = $request->manual_id;
            
            \Log::info("Received manual_id: " . $manualId);
            \Log::info("Received file: " . ($file ? $file->getClientOriginalName() : 'null'));
            
            // Find the manual
            $manual = Manual::findOrFail($manualId);
            
            // Log existing components count for this manual
            $existingComponentsCount = Component::where('manual_id', $manualId)->count();
            \Log::info("Manual {$manualId} ({$manual->number}) currently has {$existingComponentsCount} components");
            
            // Read CSV file first
            $filePath = $file->getPathname();
            if (!file_exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'CSV file not found on server'
                ], 400);
            }
            
            $csvContent = file_get_contents($filePath);
            if ($csvContent === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to read CSV file content'
                ], 400);
            }
            
            if (empty($csvContent)) {
                return response()->json([
                    'success' => false,
                    'message' => 'CSV file is empty'
                ], 400);
            }
            
            // Check file encoding and try to convert if needed
            $encoding = mb_detect_encoding($csvContent, ['UTF-8', 'ISO-8859-1', 'Windows-1251'], true);
            \Log::info("Detected file encoding: " . ($encoding ?: 'unknown'));
            
            if ($encoding && $encoding !== 'UTF-8') {
                $csvContent = mb_convert_encoding($csvContent, 'UTF-8', $encoding);
                \Log::info("Converted file from {$encoding} to UTF-8");
            }
            
            // Parse CSV content
            $csvData = array_map('str_getcsv', explode("\n", $csvContent));
            
            // Log raw CSV data
            \Log::info("Raw CSV data count: " . count($csvData));
            \Log::info("First few rows: " . json_encode(array_slice($csvData, 0, 3)));
            
            // Alternative parsing method if str_getcsv fails
            if (empty($csvData) || count($csvData) < 2) {
                \Log::warning("str_getcsv parsing failed, trying alternative method");
                $lines = explode("\n", $csvContent);
                $csvData = [];
                foreach ($lines as $line) {
                    if (trim($line)) {
                        $csvData[] = str_getcsv($line);
                    }
                }
                \Log::info("Alternative parsing result: " . count($csvData) . " rows");
            }
            
            // Remove empty rows and clean data
            $csvData = array_filter($csvData, function($row) {
                return !empty(array_filter($row, 'strlen'));
            });
            
            \Log::info("After filtering empty rows: " . count($csvData));
            
            if (empty($csvData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid data rows found in CSV file'
                ], 400);
            }
            
            $headers = array_shift($csvData); // Remove header row
            
            // Clean headers - remove empty columns and trim whitespace
            $headers = array_map('trim', array_filter($headers, 'strlen'));
            
            \Log::info("Headers: " . json_encode($headers));
            
            // Validate headers
            $requiredHeaders = ['part_number', 'name', 'ipl_num'];
            $missingHeaders = array_diff($requiredHeaders, $headers);
            
            if (!empty($missingHeaders)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing required headers: ' . implode(', ', $missingHeaders) . '. Found headers: ' . implode(', ', $headers)
                ], 400);
            }
            
            // Save CSV file to storage directory
            $fileName = 'manual_' . $manualId . '_' . time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('csv_uploads', $fileName, 'public');
            
            // Store file info in database (optional - you can create a separate table for this)
            // For now, we'll just process the CSV without storing file metadata

            $successCount = 0;
            $errorCount = 0;
            $errors = [];

            foreach ($csvData as $rowIndex => $row) {
                try {
                    // Clean row data - trim whitespace but keep all columns including empty ones
                    $cleanRow = array_map('trim', $row);
                    
                    // Ensure we have exactly the right number of columns
                    while (count($cleanRow) < count($headers)) {
                        $cleanRow[] = '';
                    }
                    
                    // If we have more columns than headers, truncate
                    if (count($cleanRow) > count($headers)) {
                        $cleanRow = array_slice($cleanRow, 0, count($headers));
                    }
                    
                    // Create associative array from headers and row data
                    $rowData = array_combine($headers, $cleanRow);
                    
                    // Log the mapping for debugging (only first few rows)
                    if ($rowIndex < 5) {
                        \Log::info("Row " . ($rowIndex + 2) . " mapping:");
                        \Log::info("  Original row: " . json_encode($row));
                        \Log::info("  Cleaned row: " . json_encode($cleanRow));
                        \Log::info("  Headers: " . json_encode($headers));
                        \Log::info("  Mapped data: " . json_encode($rowData));
                    }
                    
                    // Validate required fields
                    if (empty($rowData['part_number']) || empty($rowData['name']) || empty($rowData['ipl_num'])) {
                        $errorCount++;
                        $errors[] = "Row " . ($rowIndex + 2) . ": Missing required fields (part_number: '" . ($rowData['part_number'] ?? '') . "', name: '" . ($rowData['name'] ?? '') . "', ipl_num: '" . ($rowData['ipl_num'] ?? '') . "')";
                        \Log::warning("Row " . ($rowIndex + 2) . " missing required fields");
                        continue;
                    }

                    // Prepare component data
                    $componentData = [
                        'manual_id' => $manualId,
                        'part_number' => trim($rowData['part_number']),
                        'assy_part_number' => isset($rowData['assy_part_number']) ? trim($rowData['assy_part_number']) : null,
                        'name' => trim($rowData['name']),
                        'ipl_num' => trim($rowData['ipl_num']),
                        'assy_ipl_num' => isset($rowData['assy_ipl_num']) ? trim($rowData['assy_ipl_num']) : null,
                        'log_card' => isset($rowData['log_card']) ? (int)($rowData['log_card'] == '1' || $rowData['log_card'] == 'true') : 0,
                        'repair' => isset($rowData['repair']) ? (int)($rowData['repair'] == '1' || $rowData['repair'] == 'true') : 0,
                        'is_bush' => isset($rowData['is_bush']) ? (int)($rowData['is_bush'] == '1' || $rowData['is_bush'] == 'true') : 0,
                        'bush_ipl_num' => isset($rowData['bush_ipl_num']) ? trim($rowData['bush_ipl_num']) : null,
                    ];

                    // Check if component with exactly the same data already exists
                    // This allows multiple components with same part_number but different ipl_num in the same manual
                    $existingComponent = Component::where('part_number', $componentData['part_number'])
                        ->where('manual_id', $manualId)
                        ->where('ipl_num', $componentData['ipl_num'])
                        ->first();

                    if ($existingComponent) {
                        // Skip existing component - exact duplicate found
                        $errorCount++;
                        $errors[] = "Row " . ($rowIndex + 2) . ": Exact duplicate component found - skipped";
                    } else {
                        // Create new component - either new part_number or same part_number with different data
                        try {
                            $newComponent = Component::create($componentData);
                            $successCount++;
                        } catch (\Exception $e) {
                            \Log::error("Row " . ($rowIndex + 2) . ": Failed to create component: " . $e->getMessage());
                            $errorCount++;
                            $errors[] = "Row " . ($rowIndex + 2) . ": Failed to create component: " . $e->getMessage();
                        }
                    }

                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = "Row " . ($rowIndex + 2) . ": " . $e->getMessage();
                }
            }

            // CSV file uploaded successfully
            // Note: Custom properties are not set due to Spatie Media Library version compatibility
            
            $message = "Successfully added {$successCount} new components.";
            if ($errorCount > 0) {
                $message .= " {$errorCount} rows were skipped (duplicates or errors).";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            \Log::error('CSV upload error: ' . $e->getMessage());
            \Log::error('CSV upload error trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error processing CSV file: ' . $e->getMessage(),
                'debug_info' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ], 500);
        }
    }

    /**
     * Download CSV template
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function downloadCsvTemplate()
    {
        $filename = 'components_template.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Headers
            fputcsv($file, [
                'part_number',
                'assy_part_number', 
                'name',
                'ipl_num',
                'assy_ipl_num',
                'log_card',
                'repair',
                'is_bush',
                'bush_ipl_num'
            ]);
            
            // Example rows
            fputcsv($file, [
                'ABC123',
                'ABC123-ASSY',
                'Example Component Name',
                '123-456',
                '123-456A',
                '1',
                '0',
                '0',
                ''
            ]);
            
            fputcsv($file, [
                'XYZ789',
                '',
                'Another Component',
                '789-012',
                '',
                '0',
                '1',
                '1',
                '789-012B'
            ]);
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // Method viewCsv removed - using simple file storage instead

}
