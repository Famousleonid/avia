<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Component;
use App\Models\Manual;
use App\Models\Plane;
use App\Models\Builder;
use App\Models\Scope;
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
        $components = Component::with('manuals')->orderBy('ipl_num')->get();
        $manuals = Manual::all();
        $planes = Plane::pluck('type', 'id');
        $builders = Builder::pluck('name', 'id');
        $scopes = Scope::pluck('scope', 'id');

        return view('admin.components.index', compact('components', 'manuals', 'planes', 'builders', 'scopes'));
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
            'eff_code' => 'nullable|string|max:100',
            'units_assy' => 'nullable|string|max:100',

        ]);

        $validated['assy_part_number'] = $request->assy_part_number;
        $validated['eff_code'] = $request->eff_code;
        $validated['units_assy'] = $request->units_assy;

        $validated['log_card'] = $request->has('log_card') ? 1 : 0;
        $validated['repair'] = $request->has('repair') ? 1 : 0;
        $validated['is_bush'] = $request->has('is_bush') ? 1 : 0;
        $validated['bush_ipl_num'] = $request->bush_ipl_num;

        $component = Component::create($validated);

        if ($request->hasFile('img')) {
            $component->addMedia($request->file('img'))->toMediaCollection('components');
        }
        if ($request->hasFile('assy_img')) {

            $component->addMedia($request->file('assy_img'))->toMediaCollection('assy_components');
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
                'bush_ipl_num' => 'nullable|string|max:10|regex:/^\d+-\d+[A-Za-z]?$/',
                'eff_code' => 'nullable|string|max:100',
                'units_assy' => 'nullable|string|max:100',
//                'img' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
//                'assy_img' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);
            $validated['assy_part_number'] = $request->assy_part_number;
            $validated['eff_code'] = $request->eff_code;
            $validated['units_assy'] = $request->units_assy;
            $validated['log_card'] = $request->has('log_card') ? 1 : 0;
            $validated['repair'] = $request->has('repair') ? 1 : 0;
            $validated['is_bush'] = $request->has('is_bush') ? 1 : 0;
            $validated['bush_ipl_num'] = $request->bush_ipl_num;

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
                'eff_code' => 'nullable|string|max:100',
                'units_assy' => 'nullable|string|max:100',
            ]);
            $validated['assy_part_number'] = $request->assy_part_number;
            $validated['eff_code'] = $request->eff_code;
            $validated['units_assy'] = $request->units_assy;
            $validated['log_card'] = $request->has('log_card') ? 1 : 0;
            $validated['repair'] = $request->has('repair') ? 1 : 0;
            $validated['is_bush'] = $request->has('is_bush') ? 1 : 0;
            $validated['bush_ipl_num'] = $request->bush_ipl_num;

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
        $manual = Manual::findOrFail($id);

        // Get components with custom sorting for IPL numbers
        $components = $manual->components()
            ->orderByRaw("CAST(SUBSTRING_INDEX(ipl_num, '-', 1) AS UNSIGNED)")
            ->orderByRaw("CAST(REGEXP_REPLACE(SUBSTRING_INDEX(ipl_num, '-', -1), '[^0-9]', '') AS UNSIGNED)")
            ->orderByRaw("REGEXP_REPLACE(SUBSTRING_INDEX(ipl_num, '-', -1), '[0-9]', '')")
            ->get();

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

        $validated = $request->validate([

            'name' => 'required',
            'manual_id' => 'required|exists:manuals,id',
            'part_number' =>'required',
            'ipl_num' =>'required',
            'assy_ipl_num' => 'nullable|string|max:10|regex:/^\d+-\d+[A-Za-z]?$/',
            'bush_ipl_num' => 'nullable|string|max:10|regex:/^\d+-\d+[A-Za-z]?$/',
            'eff_code' => 'nullable|string|max:100',
            'units_assy' => 'nullable|string|max:100',

        ]);


        $validated['assy_part_number'] = $request->assy_part_number;
        $validated['eff_code'] = $request->eff_code;
        $validated['units_assy'] = $request->units_assy;
        $validated['assy_ipl_num'] = $request->assy_ipl_num;
        $validated['log_card'] = $request->has('log_card') ? 1 : 0;
        $validated['repair'] = $request->has('repair') ? 1 : 0;
        $validated['is_bush'] = $request->has('is_bush') ? 1 : 0;
        $validated['bush_ipl_num'] = $request->bush_ipl_num;
//        dd($validated);

        if ($request->hasFile('img')) {
            if ($component->getMedia('components')->isNotEmpty()) {
                $component->getMedia('components')->first()->delete();
            }

            $component->addMedia($request->file('img'))->toMediaCollection('components');
        }
        if ($request->hasFile('assy_img')) {
            if ($component->getMedia('assy_components')->isNotEmpty()) {
                $component->getMedia('assy_components')->first()->delete();
            }

            $component->addMedia($request->file('assy_img'))->toMediaCollection('assy_components');
        }
        $component->update($validated);

        return redirect()->route('components.show', $component->manual_id)->with('success', 'Component updated successfully');

    }

    /**
     * Return component data as JSON (for editing from inspection form).
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function showJson($id)
    {
        $component = Component::findOrFail($id);

        return response()->json([
            'success'   => true,
            'component' => [
                'id'               => $component->id,
                'name'             => $component->name,
                'ipl_num'          => $component->ipl_num,
                'part_number'      => $component->part_number,
                'assy_ipl_num'     => $component->assy_ipl_num,
                'assy_part_number' => $component->assy_part_number,
                'eff_code'         => $component->eff_code,
                'units_assy'       => $component->units_assy,
                'log_card'         => (bool) $component->log_card,
                'repair'           => (bool) $component->repair,
                'is_bush'          => (bool) $component->is_bush,
                'bush_ipl_num'     => $component->bush_ipl_num,
            ],
        ]);
    }

    /**
     * Update component from TDR component-inspection page.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateFromInspection(Request $request, $id)
    {
        $component = Component::findOrFail($id);

        $validated = $request->validate([
            'name'         => 'required|string|max:250',
            'manual_id'    => 'required|exists:manuals,id',
            'part_number'  => 'required|string|max:50',
            'ipl_num'      => 'required|string|max:10',
            'assy_ipl_num' => 'nullable|string|max:10|regex:/^\d+-\d+[A-Za-z]?$/',
            'bush_ipl_num' => 'nullable|string|max:10|regex:/^\d+-\d+[A-Za-z]?$/',
            'eff_code'     => 'nullable|string|max:100',
            'units_assy'   => 'nullable|string|max:100',
        ]);

        $validated['assy_part_number'] = $request->assy_part_number;
        $validated['eff_code']         = $request->eff_code;
        $validated['units_assy']       = $request->units_assy;
        $validated['assy_ipl_num']     = $request->assy_ipl_num;
        $validated['log_card']         = $request->has('log_card') ? 1 : 0;
        $validated['repair']           = $request->has('repair') ? 1 : 0;
        $validated['is_bush']          = $request->has('is_bush') ? 1 : 0;
        $validated['bush_ipl_num']     = $request->bush_ipl_num;

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

            $component->addMedia($request->file('assy_img'))->toMediaCollection('assy_component');
        }

        $component->update($validated);

        return redirect()
            ->route('tdrs.inspection.component', ['workorder_id' => $request->workorder_id])
            ->with('success', 'Component updated successfully.');
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
     *
     * This method handles CSV uploads for components. If a CSV file already exists
     * for the given manual, it will be replaced with the new one. Existing components
     * with the same part_number and ipl_num will be updated, while new components
     * will be created.
     *
     * Features:
     * - Replaces existing CSV files instead of duplicating them
     * - Updates existing components with new data from CSV
     * - Creates new components for new entries
     * - Prevents duplicate files in media collection
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

            if (!$file) {
                \Log::error("No CSV file received in request");
                return response()->json([
                    'success' => false,
                    'message' => 'No CSV file received'
                ], 400);
            }

            if (!$file->isValid()) {
                \Log::error("Invalid CSV file: " . $file->getError());
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid CSV file: ' . $file->getErrorMessage()
                ], 400);
            }

            // Find the manual
            $manual = Manual::findOrFail($manualId);

            // Check if manual has media collections
            if (!method_exists($manual, 'addMedia')) {
                \Log::error("Manual model does not have addMedia method - Spatie Media Library not properly configured");
                return response()->json([
                    'success' => false,
                    'message' => 'Media library not properly configured for Manual model'
                ], 500);
            }

            // Log existing components count for this manual
            $existingComponentsCount = Component::where('manual_id', $manualId)->count();
            \Log::info("Manual {$manualId} ({$manual->number}) currently has {$existingComponentsCount} components");

            // Log existing CSV files count for this manual
            $existingCsvFilesCount = $manual->getMedia('component_csv_files')->count();
            \Log::info("Manual {$manualId} ({$manual->number}) currently has {$existingCsvFilesCount} CSV files in media collection");

            if ($existingCsvFilesCount > 0) {
                \Log::info("This is a replacement upload - existing CSV files will be removed");
            }

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

            // Log original content for debugging
            \Log::info("Original CSV content length: " . strlen($csvContent));
            \Log::info("First 500 characters: " . substr($csvContent, 0, 500));

            // Check file encoding and try to convert if needed
            $encoding = mb_detect_encoding($csvContent, ['UTF-8', 'ISO-8859-1', 'Windows-1251', 'CP1251'], true);
            \Log::info("Detected file encoding: " . ($encoding ?: 'unknown'));

            if ($encoding && $encoding !== 'UTF-8') {
                $csvContent = mb_convert_encoding($csvContent, 'UTF-8', $encoding);
                \Log::info("Converted file from {$encoding} to UTF-8");
            }

            // Try to fix BOM if present
            if (substr($csvContent, 0, 3) === "\xEF\xBB\xBF") {
                $csvContent = substr($csvContent, 3);
                \Log::info("Removed UTF-8 BOM");
            }

            // Additional encoding fixes for problematic files
            // Try to fix common encoding issues that might cause the \u043f\u00bb\u0457 problem
            if (preg_match('/\\\u[0-9a-fA-F]{4}/', $csvContent)) {
                \Log::info("Detected Unicode escape sequences, attempting to fix...");
                // Try to decode JSON-like strings
                $csvContent = preg_replace_callback('/\\\u([0-9a-fA-F]{4})/', function($matches) {
                    return json_decode('"\u' . $matches[1] . '"');
                }, $csvContent);
            }

            // Try to fix any remaining encoding issues
            if (!mb_check_encoding($csvContent, 'UTF-8')) {
                \Log::info("Content still has encoding issues, attempting additional fixes...");
                $csvContent = mb_convert_encoding($csvContent, 'UTF-8', 'UTF-8');
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

            // Clean headers - remove empty columns, trim whitespace, and fix encoding issues
            $headers = array_map(function($header) {
                $header = trim($header);

                // Try to fix common encoding issues
                if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $header)) {
                    $header = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $header);
                }

                // Try to fix UTF-8 encoding issues
                if (!mb_check_encoding($header, 'UTF-8')) {
                    $header = mb_convert_encoding($header, 'UTF-8', 'UTF-8');
                }

                // Additional cleaning for problematic characters
                $header = preg_replace('/[^\x20-\x7E\xA0-\xFF]/u', '', $header);

                // Try to fix specific encoding issues that might cause the \u043f\u00bb\u0457 problem
                if (preg_match('/\\\u[0-9a-fA-F]{4}/', $header)) {
                    $header = json_decode('"' . $header . '"', true) ?: $header;
                }

                return $header;
            }, array_filter($headers, 'strlen'));

            \Log::info("Headers: " . json_encode($headers));

            // Additional header validation and cleaning
            $cleanedHeaders = [];
            foreach ($headers as $header) {
                $cleanHeader = $header;

                // Remove any remaining problematic characters
                $cleanHeader = preg_replace('/[^\x20-\x7E\xA0-\xFF]/u', '', $cleanHeader);

                // Ensure header is not empty after cleaning
                if (!empty(trim($cleanHeader))) {
                    $cleanedHeaders[] = trim($cleanHeader);
                }
            }

            if (empty($cleanedHeaders)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid headers found after cleaning'
                ], 400);
            }

            $headers = $cleanedHeaders;
            \Log::info("Cleaned headers: " . json_encode($headers));

            // Final validation that headers don't contain problematic characters
            foreach ($headers as $index => $header) {
                if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $header)) {
                    \Log::warning("Header at index {$index} still contains problematic characters: " . json_encode($header));
                    $headers[$index] = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $header);
                }

                // Additional check for Unicode escape sequences
                if (preg_match('/\\\u[0-9a-fA-F]{4}/', $header)) {
                    \Log::warning("Header at index {$index} contains Unicode escape sequences: " . json_encode($header));
                    $decoded = json_decode('"' . $header . '"', true);
                    if ($decoded !== null) {
                        $headers[$index] = $decoded;
                        \Log::info("Successfully decoded header at index {$index}: " . $decoded);
                    }
                }
            }

            \Log::info("Final cleaned headers: " . json_encode($headers));

            // Validate headers
            $requiredHeaders = ['part_number', 'name', 'ipl_num'];
            $missingHeaders = array_diff($requiredHeaders, $headers);

            if (!empty($missingHeaders)) {
                \Log::error("Missing required headers: " . implode(', ', $missingHeaders));
                \Log::error("Found headers: " . implode(', ', $headers));
                \Log::error("Headers count: " . count($headers));

                return response()->json([
                    'success' => false,
                    'message' => 'Missing required headers: ' . implode(', ', $missingHeaders) . '. Found headers: ' . implode(', ', $headers)
                ], 400);
            }

            \Log::info("Headers validation passed. All required headers found.");

            // Check if there's an existing CSV file for this manual and remove it
            $existingCsvFiles = $manual->getMedia('component_csv_files');
            if ($existingCsvFiles->count() > 0) {
                \Log::info("Found " . $existingCsvFiles->count() . " existing CSV files, removing them before upload");
                foreach ($existingCsvFiles as $existingFile) {
                    $fileName = $existingFile->file_name;
                    $fileId = $existingFile->id;
                    $existingFile->delete();
                    \Log::info("Removed existing CSV file: ID {$fileId}, Name: {$fileName}");
                }
            }

            // Save CSV file to component-specific media collection
            try {
                $uploadedCsvFile = $manual->addMedia($file)
                    ->toMediaCollection('component_csv_files');

                if ($existingCsvFilesCount > 0) {
                    \Log::info("CSV file replaced successfully in media collection. New file ID: " . ($uploadedCsvFile ? $uploadedCsvFile->id : 'null'));
                } else {
                    \Log::info("CSV file uploaded successfully to media collection: " . ($uploadedCsvFile ? $uploadedCsvFile->id : 'null'));
                }
            } catch (\Exception $e) {
                \Log::error("Failed to upload CSV to media collection: " . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to save CSV file: ' . $e->getMessage()
                ], 500);
            }

            $successCount = 0;
            $updateCount = 0;
            $createCount = 0;
            $errorCount = 0;
            $errors = [];

            foreach ($csvData as $rowIndex => $row) {
                try {
                    // Clean row data - trim whitespace and fix encoding issues
                    $cleanRow = array_map(function($cell) {
                        $cell = trim($cell);

                        // Try to fix common encoding issues
                        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $cell)) {
                            $cell = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $cell);
                        }

                        // Try to fix UTF-8 encoding issues
                        if (!mb_check_encoding($cell, 'UTF-8')) {
                            $cell = mb_convert_encoding($cell, 'UTF-8', 'UTF-8');
                        }

                        // Additional cleaning for problematic characters
                        $cell = preg_replace('/[^\x20-\x7E\xA0-\xFF]/u', '', $cell);

                                            // Try to fix specific encoding issues that might cause the \u043f\u00bb\u0457 problem
                    if (preg_match('/\\\u[0-9a-fA-F]{4}/', $cell)) {
                        $decoded = json_decode('"' . $cell . '"', true);
                        if ($decoded !== null) {
                            $cell = $decoded;
                        } else {
                            // If JSON decode fails, try to manually decode common Unicode sequences
                            $cell = preg_replace_callback('/\\\u([0-9a-fA-F]{4})/', function($matches) {
                                return json_decode('"\u' . $matches[1] . '"');
                            }, $cell);
                        }
                    }

                        return $cell;
                    }, $row);

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

                    // Additional validation for problematic characters
                    if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $rowData['part_number']) ||
                        preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $rowData['name']) ||
                        preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $rowData['ipl_num'])) {
                        \Log::warning("Row " . ($rowIndex + 2) . " contains problematic characters, attempting to clean...");
                        $rowData['part_number'] = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $rowData['part_number']);
                        $rowData['name'] = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $rowData['name']);
                        $rowData['ipl_num'] = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $rowData['ipl_num']);
                    }

                    // Final check that required fields are not empty after cleaning
                    if (empty(trim($rowData['part_number'])) || empty(trim($rowData['name'])) || empty(trim($rowData['ipl_num']))) {
                        $errorCount++;
                        $errors[] = "Row " . ($rowIndex + 2) . ": Required fields became empty after cleaning";
                        \Log::warning("Row " . ($rowIndex + 2) . " required fields became empty after cleaning");
                        continue;
                    }

                    // Дополнительная валидация для специальных полей
                    if (isset($rowData['units_assy']) && !empty(trim($rowData['units_assy']))) {
                        $unitsAssy = trim($rowData['units_assy']);
                        // Проверяем, что units_assy содержит только допустимые символы
                        if (!preg_match('/^[a-zA-Z0-9\s\-_\.]+$/', $unitsAssy)) {
                            \Log::warning("Row " . ($rowIndex + 2) . ": units_assy contains invalid characters: " . $unitsAssy);
                            // Очищаем поле от недопустимых символов
                            $rowData['units_assy'] = preg_replace('/[^a-zA-Z0-9\s\-_\.]/', '', $unitsAssy);
                        }
                    }

                    // Prepare component data
                    $componentData = [
                        'manual_id' => $manualId,
                        'part_number' => trim($rowData['part_number']),
                        'assy_part_number' => isset($rowData['assy_part_number']) ? trim($rowData['assy_part_number']) : null,
                        'name' => trim($rowData['name']),
                        'ipl_num' => trim($rowData['ipl_num']),
                        'assy_ipl_num' => isset($rowData['assy_ipl_num']) ? trim($rowData['assy_ipl_num']) : null,
                        'eff_code' => isset($rowData['eff_code']) ? trim($rowData['eff_code']) : null,
                        'units_assy' => isset($rowData['units_assy']) ? trim($rowData['units_assy']) : null,
                        'log_card' => isset($rowData['log_card']) ? (int)($rowData['log_card'] == '1' || $rowData['log_card'] == 'true') : 0,
                        'repair' => isset($rowData['repair']) ? (int)($rowData['repair'] == '1' || $rowData['repair'] == 'true') : 0,
                        'is_bush' => isset($rowData['is_bush']) ? (int)($rowData['is_bush'] == '1' || $rowData['is_bush'] == 'true') : 0,
                        'bush_ipl_num' => isset($rowData['bush_ipl_num']) ? trim($rowData['bush_ipl_num']) : null,
                    ];

                    // Дополнительная проверка на минимальную полноту данных
                    $hasMinimalData = !empty($componentData['part_number']) &&
                                     !empty($componentData['name']) &&
                                     !empty($componentData['ipl_num']);

                    if (!$hasMinimalData) {
                        $errorCount++;
                        $errors[] = "Row " . ($rowIndex + 2) . ": Insufficient data for component creation";
                        \Log::warning("Row " . ($rowIndex + 2) . " has insufficient data, skipping");
                        continue;
                    }

                    // Check if component with the same part_number and ipl_num already exists in this manual
                    // Приоритет поиска: сначала по точному совпадению part_number + ipl_num + manual_id
                    $existingComponent = Component::where('part_number', $componentData['part_number'])
                        ->where('manual_id', $manualId)
                        ->where('ipl_num', $componentData['ipl_num'])
                        ->first();

                    // Дополнительная проверка на дублирование по содержимому (для случаев, когда CSV содержит дублирующиеся строки)
                    if (!$existingComponent) {
                        $similarComponent = Component::where('manual_id', $manualId)
                            ->where('name', $componentData['name'])
                            ->where('part_number', $componentData['part_number'])
                            ->where('assy_part_number', $componentData['assy_part_number'])
                            ->where('eff_code', $componentData['eff_code'])
                            ->where('units_assy', $componentData['units_assy'])
                            ->first();

                        if ($similarComponent && $similarComponent->ipl_num !== $componentData['ipl_num']) {
                            \Log::info("Found similar component with different IPL: existing IPL '{$similarComponent->ipl_num}' vs CSV IPL '{$componentData['ipl_num']}' for component '{$componentData['part_number']}'");

                            // Если найден похожий компонент, предлагаем обновить IPL вместо создания нового
                            if ($similarComponent->ipl_num !== $componentData['ipl_num']) {
                                \Log::info("Suggesting to update IPL from '{$similarComponent->ipl_num}' to '{$componentData['ipl_num']}' for component '{$componentData['part_number']}'");
                                // Можно добавить логику для автоматического обновления IPL
                            }
                        }
                    }

                    // Если не найден, проверяем на дублирование по part_number в том же мануале
                    if (!$existingComponent) {
                        $duplicateComponents = Component::where('part_number', $componentData['part_number'])
                            ->where('manual_id', $manualId)
                            ->get();

                        if ($duplicateComponents->count() > 0) {
                            $iplNumbers = $duplicateComponents->pluck('ipl_num')->implode(', ');
                            \Log::warning("Potential duplicates found: part_number '{$componentData['part_number']}' already exists in manual {$manualId} with IPL numbers: [{$iplNumbers}], but CSV has IPL '{$componentData['ipl_num']}'");

                            // Если есть только один дубликат и он имеет другой IPL, предлагаем обновить IPL
                            if ($duplicateComponents->count() === 1) {
                                $duplicateComponent = $duplicateComponents->first();
                                if ($duplicateComponent->ipl_num !== $componentData['ipl_num']) {
                                    \Log::info("Consider updating IPL number from '{$duplicateComponent->ipl_num}' to '{$componentData['ipl_num']}' for component '{$componentData['part_number']}'");
                                }
                            }
                        }
                    }

                    if ($existingComponent) {
                        // Update existing component with new data from CSV
                        try {
                            // Обновляем все поля из CSV файла, избегая дублирования
                            // Фильтруем пустые значения, чтобы не перезаписывать существующие данные
                            $updateData = array_intersect_key($componentData, array_flip([
                                'name', 'assy_part_number', 'assy_ipl_num', 'eff_code',
                                'units_assy', 'log_card', 'repair', 'is_bush', 'bush_ipl_num'
                            ]));

                            // Убираем пустые строки и null значения, но оставляем 0 для boolean полей
                            $updateData = array_filter($updateData, function($value, $key) {
                                if (in_array($key, ['log_card', 'repair', 'is_bush'])) {
                                    return $value !== null; // Оставляем 0 для boolean полей
                                }
                                return $value !== null && $value !== ''; // Убираем пустые строки для текстовых полей
                            }, ARRAY_FILTER_USE_BOTH);

                            // Проверяем, есть ли реальные изменения в данных
                            $hasChanges = false;
                            foreach ($updateData as $field => $value) {
                                if ($existingComponent->$field != $value) {
                                    $hasChanges = true;
                                    break;
                                }
                            }

                            if (!empty($updateData) && $hasChanges) {
                                $existingComponent->update($updateData);
                                $successCount++;
                                $updateCount++;
                                \Log::info("Updated existing component: " . $componentData['part_number'] . " (IPL: " . $componentData['ipl_num'] . ") with fields: " . implode(', ', array_keys($updateData)));
                            } else {
                                \Log::info("No changes needed for component: " . $componentData['part_number'] . " (IPL: " . $componentData['ipl_num'] . ")");
                            }
                        } catch (\Exception $e) {
                            \Log::error("Row " . ($rowIndex + 2) . ": Failed to update existing component: " . $e->getMessage());
                            $errorCount++;
                            $errors[] = "Row " . ($rowIndex + 2) . ": Failed to update existing component: " . $e->getMessage();
                        }
                    } else {
                        // Create new component
                        try {
                            // Дополнительная проверка на уникальность перед созданием
                            $finalCheck = Component::where('part_number', $componentData['part_number'])
                                ->where('manual_id', $manualId)
                                ->where('ipl_num', $componentData['ipl_num'])
                                ->exists();

                            if ($finalCheck) {
                                \Log::warning("Component already exists after final check, skipping creation: " . $componentData['part_number'] . " (IPL: " . $componentData['ipl_num'] . ")");
                                $errorCount++;
                                $errors[] = "Row " . ($rowIndex + 2) . ": Component already exists, skipping creation";
                                continue;
                            }

                            $newComponent = Component::create($componentData);
                            $successCount++;
                            $createCount++;
                            \Log::info("Created new component: " . $componentData['part_number'] . " (IPL: " . $componentData['ipl_num'] . ")");
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

            $message = "Successfully processed {$successCount} components: {$createCount} created, {$updateCount} updated.";
            if ($errorCount > 0) {
                $message .= " {$errorCount} rows had errors.";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'success_count' => $successCount,
                'create_count' => $createCount,
                'update_count' => $updateCount,
                'error_count' => $errorCount,
                'errors' => $errors,
                'manual_id' => $manualId,
                'manual_number' => $manual->number,
                'existing_components_before' => $existingComponentsCount,
                'csv_files_replaced' => $existingCsvFilesCount > 0 ? $existingCsvFilesCount : 0
            ]);

        } catch (\Exception $e) {
            \Log::error('CSV upload error: ' . $e->getMessage());
            \Log::error('CSV upload error trace: ' . $e->getTraceAsString());
            \Log::error('CSV upload error file: ' . $e->getFile() . ':' . $e->getLine());

            return response()->json([
                'success' => false,
                'message' => 'Error processing CSV file: ' . $e->getMessage(),
                'debug_info' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
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
                'eff_code',
                'units_assy',
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
                'EFF001',
                'UNITS001',
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
                'EFF002',
                'UNITS002',
                '0',
                '1',
                '1',
                '789-012B'
            ]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * View CSV file content
     *
     * @param  int  $manual_id
     * @param  int  $file_id
     * @return \Illuminate\Http\Response
     */
    public function viewCsv($manual_id, $file_id)
    {
        try {
            $manual = Manual::findOrFail($manual_id);
            $csvFile = $manual->getMedia('component_csv_files')->find($file_id);

            if (!$csvFile) {
                abort(404, 'CSV file not found');
            }

            // Read CSV file content
            $filePath = $csvFile->getPath();
            $csvContent = file_get_contents($filePath);

            // Parse CSV content for display
            $csvData = array_map('str_getcsv', explode("\n", $csvContent));
            $headers = array_shift($csvData); // Remove header row

            // Filter out empty rows
            $csvData = array_filter($csvData, function($row) {
                return !empty(array_filter($row, 'strlen'));
            });

            return view('admin.components.view-csv', compact('manual', 'csvFile', 'headers', 'csvData'));

        } catch (\Exception $e) {
            \Log::error('CSV view error: ' . $e->getMessage());
            abort(500, 'Error viewing CSV file');
        }
    }

    /**
     * Display CSV components management page
     *
     * @return Application|Factory|View
     */
    public function csvComponents()
    {
        $manuals = Manual::with('media')->get();
        $planes = Plane::pluck('type', 'id');
        $builders = Builder::pluck('name', 'id');
        $scopes = Scope::pluck('scope', 'id');

        return view('admin.components.csv-components', compact('manuals', 'planes', 'builders', 'scopes'));
    }

    /**
     * Edit CSV file content
     *
     * @param  int  $manual_id
     * @param  int  $file_id
     * @return \Illuminate\Http\Response
     */
    public function editCsv($manual_id, $file_id)
    {
        try {
            $manual = Manual::findOrFail($manual_id);
            $csvFile = $manual->getMedia('component_csv_files')->find($file_id);

            if (!$csvFile) {
                abort(404, 'CSV file not found');
            }

            // Read CSV file content
            $filePath = $csvFile->getPath();
            $csvContent = file_get_contents($filePath);

            // Parse CSV content for editing
            $csvData = array_map('str_getcsv', explode("\n", $csvContent));
            $headers = array_shift($csvData); // Remove header row

            // Ensure headers is an array
            if (!is_array($headers)) {
                $headers = [];
            }

            // Filter out empty rows
            $csvData = array_filter($csvData, function($row) {
                return !empty(array_filter($row, 'strlen'));
            });

            // Ensure csvData is an array
            if (!is_array($csvData)) {
                $csvData = [];
            }

            return view('admin.components.edit-csv', compact('manual', 'csvFile', 'headers', 'csvData'));

        } catch (\Exception $e) {
            \Log::error('CSV edit error: ' . $e->getMessage());
            abort(500, 'Error loading CSV file for editing');
        }
    }

    /**
     * Update CSV file content and components in database
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $manual_id
     * @param  int  $file_id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateCsv(Request $request, $manual_id, $file_id)
    {
        try {
            $manual = Manual::findOrFail($manual_id);
            $csvFile = $manual->getMedia('component_csv_files')->find($file_id);

            if (!$csvFile) {
                abort(404, 'CSV file not found');
            }

            // Get updated CSV data from request
            $headers = $request->input('headers', []);
            $rows = $request->input('rows', []);

            // Логируем входящие данные для отладки
            \Log::info('Update CSV - Headers: ' . json_encode($headers));
            \Log::info('Update CSV - Rows count: ' . count($rows));
            if (count($rows) > 0) {
                \Log::info('Update CSV - First row: ' . json_encode($rows[0]));
            }

            // Validate headers
            if (empty($headers)) {
                return redirect()->back()
                    ->with('error', 'Headers are required');
            }

            $requiredHeaders = ['part_number', 'name', 'ipl_num'];
            $missingHeaders = array_diff($requiredHeaders, $headers);

            if (!empty($missingHeaders)) {
                return redirect()->back()
                    ->with('error', 'Missing required headers: ' . implode(', ', $missingHeaders));
            }

            // Build CSV content
            $file = fopen('php://temp', 'r+');

            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Add headers
            fputcsv($file, $headers);

            // Add rows
            foreach ($rows as $row) {
                // Ensure row has same number of columns as headers
                $rowData = [];
                foreach ($headers as $index => $header) {
                    $rowData[] = isset($row[$index]) ? $row[$index] : '';
                }
                fputcsv($file, $rowData);
            }

            rewind($file);
            $csvContent = stream_get_contents($file);
            fclose($file);

            // Save updated content to file
            $filePath = $csvFile->getPath();
            file_put_contents($filePath, $csvContent);

            // Process components update/create
            $successCount = 0;
            $updateCount = 0;
            $createCount = 0;
            $errorCount = 0;
            $errors = [];

            foreach ($rows as $rowIndex => $row) {
                try {
                    // Ensure row is an array
                    if (!is_array($row)) {
                        $errorCount++;
                        $errors[] = "Row " . ($rowIndex + 1) . ": Invalid row format";
                        continue;
                    }

                    // Ensure row has same number of columns as headers
                    $cleanRow = [];
                    foreach ($headers as $index => $header) {
                        $cellValue = isset($row[$index]) ? trim($row[$index]) : '';
                        $cleanRow[] = $cellValue;
                    }

                    // Create associative array from headers and row data
                    $rowData = array_combine($headers, $cleanRow);

                    // Validate required fields
                    if (empty($rowData['part_number']) || empty($rowData['name']) || empty($rowData['ipl_num'])) {
                        $errorCount++;
                        $errors[] = "Row " . ($rowIndex + 1) . ": Missing required fields";
                        continue;
                    }

                    // Prepare component data
                    $partNumber = trim($rowData['part_number']);
                    $iplNum = trim($rowData['ipl_num']);
                    $name = trim($rowData['name']);

                    $componentData = [
                        'manual_id' => $manual_id,
                        'part_number' => $partNumber,
                        'assy_part_number' => isset($rowData['assy_part_number']) ? trim($rowData['assy_part_number']) : null,
                        'name' => $name,
                        'ipl_num' => $iplNum,
                        'assy_ipl_num' => isset($rowData['assy_ipl_num']) ? trim($rowData['assy_ipl_num']) : null,
                        'eff_code' => isset($rowData['eff_code']) ? trim($rowData['eff_code']) : null,
                        'units_assy' => isset($rowData['units_assy']) ? trim($rowData['units_assy']) : null,
                        'log_card' => isset($rowData['log_card']) ? (int)($rowData['log_card'] == '1' || $rowData['log_card'] == 'true') : 0,
                        'repair' => isset($rowData['repair']) ? (int)($rowData['repair'] == '1' || $rowData['repair'] == 'true') : 0,
                        'is_bush' => isset($rowData['is_bush']) ? (int)($rowData['is_bush'] == '1' || $rowData['is_bush'] == 'true') : 0,
                        'bush_ipl_num' => isset($rowData['bush_ipl_num']) ? trim($rowData['bush_ipl_num']) : null,
                    ];

                    // Check if component exists - сначала по ipl_num (более стабильный идентификатор)
                    // Это позволяет обновлять компонент даже если part_number был исправлен
                    $existingComponent = Component::where('manual_id', $manual_id)
                        ->whereRaw('TRIM(ipl_num) = ?', [$iplNum])
                        ->first();

                    // Если не найден по ipl_num, пробуем найти по part_number + ipl_num (для обратной совместимости)
                    if (!$existingComponent) {
                        $existingComponent = Component::where('manual_id', $manual_id)
                            ->whereRaw('TRIM(part_number) = ?', [$partNumber])
                            ->whereRaw('TRIM(ipl_num) = ?', [$iplNum])
                            ->first();
                    }

                    // Логируем для отладки
                    \Log::info("Update CSV - Row " . ($rowIndex + 1) . ": part_number='{$partNumber}', ipl_num='{$iplNum}', manual_id={$manual_id}");
                    if ($existingComponent) {
                        \Log::info("Found existing component ID: " . $existingComponent->id . " (old part_number: " . $existingComponent->part_number . ")");
                    } else {
                        \Log::info("Component not found, will create new");
                    }

                    if ($existingComponent) {
                        // Update existing component - включая part_number, если он изменился
                        try {
                            $updateData = array_intersect_key($componentData, array_flip([
                                'part_number', 'name', 'assy_part_number', 'assy_ipl_num', 'eff_code',
                                'units_assy', 'log_card', 'repair', 'is_bush', 'bush_ipl_num'
                            ]));

                            // Убираем пустые строки и null значения, но оставляем 0 для boolean полей
                            // НО оставляем part_number всегда, даже если он пустой (для исправления ошибок)
                            $updateData = array_filter($updateData, function($value, $key) {
                                if ($key === 'part_number') {
                                    return true; // Всегда обновляем part_number, даже если пустой
                                }
                                if (in_array($key, ['log_card', 'repair', 'is_bush'])) {
                                    return $value !== null;
                                }
                                return $value !== null && $value !== '';
                            }, ARRAY_FILTER_USE_BOTH);

                            // Проверяем, изменился ли part_number
                            $partNumberChanged = false;
                            if (isset($updateData['part_number']) && $existingComponent->part_number !== $updateData['part_number']) {
                                $partNumberChanged = true;
                                \Log::info("Part number changed from '{$existingComponent->part_number}' to '{$updateData['part_number']}' for component ID: " . $existingComponent->id);
                            }

                            // Всегда обновляем, даже если данные не изменились (для синхронизации)
                            $existingComponent->update($updateData);
                            $successCount++;
                            $updateCount++;
                            \Log::info("Updated component ID: " . $existingComponent->id . " with data: " . json_encode($updateData) . ($partNumberChanged ? " (part_number changed)" : ""));
                        } catch (\Exception $e) {
                            \Log::error("Row " . ($rowIndex + 1) . ": Failed to update component: " . $e->getMessage());
                            \Log::error("Row " . ($rowIndex + 1) . ": Component data: " . json_encode($componentData));
                            $errorCount++;
                            $errors[] = "Row " . ($rowIndex + 1) . ": Failed to update component: " . $e->getMessage();
                        }
                    } else {
                        // Create new component
                        try {
                            $newComponent = Component::create($componentData);
                            $successCount++;
                            $createCount++;
                            \Log::info("Created new component ID: " . $newComponent->id . " with part_number: " . $partNumber . ", ipl_num: " . $iplNum);
                        } catch (\Exception $e) {
                            \Log::error("Row " . ($rowIndex + 1) . ": Failed to create component: " . $e->getMessage());
                            \Log::error("Row " . ($rowIndex + 1) . ": Component data: " . json_encode($componentData));
                            $errorCount++;
                            $errors[] = "Row " . ($rowIndex + 1) . ": Failed to create component: " . $e->getMessage();
                        }
                    }

                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = "Row " . ($rowIndex + 1) . ": " . $e->getMessage();
                }
            }

            // Build success message
            $message = "CSV file updated successfully. Processed {$successCount} components: {$createCount} created, {$updateCount} updated.";
            if ($errorCount > 0) {
                $message .= " {$errorCount} rows had errors.";
            }

            return redirect()->route('components.csv-components')
                ->with('success', $message);

        } catch (\Exception $e) {
            \Log::error('CSV update error: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error updating CSV file: ' . $e->getMessage());
        }
    }

    /**
     * Delete CSV file
     *
     * @param  int  $manual_id
     * @param  int  $file_id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteCsv($manual_id, $file_id)
    {
        try {
            $manual = Manual::findOrFail($manual_id);
            $csvFile = $manual->getMedia('component_csv_files')->find($file_id);

            if (!$csvFile) {
                abort(404, 'CSV file not found');
            }

            $csvFile->delete();

            return redirect()->route('components.csv-components')
                ->with('success', 'CSV file deleted successfully');

        } catch (\Exception $e) {
            \Log::error('CSV delete error: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error deleting CSV file: ' . $e->getMessage());
        }
    }

    /**
     * Download CSV file
     *
     * @param  int  $manual_id
     * @param  int  $file_id
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function downloadCsv($manual_id, $file_id)
    {
        try {
            $manual = Manual::findOrFail($manual_id);
            $csvFile = $manual->getMedia('component_csv_files')->find($file_id);

            if (!$csvFile) {
                abort(404, 'CSV file not found');
            }

            $filePath = $csvFile->getPath();

            return response()->download($filePath, $csvFile->file_name, [
                'Content-Type' => 'text/csv',
            ]);

        } catch (\Exception $e) {
            \Log::error('CSV download error: ' . $e->getMessage());
            abort(500, 'Error downloading CSV file');
        }
    }

}
