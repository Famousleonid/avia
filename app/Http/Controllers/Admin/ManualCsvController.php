<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Manual;
use App\Models\StdProcess;
use App\Models\Workorder;
use App\Services\WorkorderStdProcessItemsService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ManualCsvController extends Controller
{
    public function store(Request $request, Manual $manual)
    {
        try {
            if (! $request->hasFile('csv_file')) {
                return response()->json([
                    'success' => false,
                    'error' => 'No file was uploaded.',
                ], 400);
            }

            $file = $request->file('csv_file');
            $processType = $request->input('process_type');

            if (! in_array($processType, StdProcess::validStdValues(), true)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid process type.',
                ], 422);
            }

            $path = $file->getRealPath();
            if (! $path || ! is_readable($path)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Could not read the uploaded file.',
                ], 400);
            }

            $formatErrors = StdProcess::validateStdCsvFormat($path);
            if ($formatErrors !== []) {
                return response()->json([
                    'success' => false,
                    'error' => 'CSV format error: '.implode(' ', $formatErrors),
                ], 422);
            }

            $parsedRows = StdProcess::loadComponentRowsFromCsv($path, $processType);
            if ($parsedRows === []) {
                return response()->json([
                    'success' => false,
                    'error' => 'CSV format error: no valid STD rows were found.',
                ], 422);
            }

            $resolutions = $this->csvImportResolutions($request);
            $conflicts = StdProcess::reviewComponentRowsAgainstParts($manual->id, $parsedRows);
            $unresolved = array_values(array_filter($conflicts, function (array $conflict) use ($resolutions): bool {
                return ! array_key_exists((string) $conflict['index'], $resolutions);
            }));

            if ($unresolved !== []) {
                return response()->json([
                    'success' => false,
                    'needs_review' => true,
                    'message' => 'CSV rows must be reviewed against Parts before import.',
                    'conflicts' => $unresolved,
                ]);
            }

            try {
                StdProcess::replaceFromComponentRows($manual->id, $processType, $parsedRows, $resolutions);
                $this->rebuildExistingWorkorderStdItems($manual);
            } catch (ValidationException $e) {
                $message = (string) collect($e->errors())->flatten()->first();

                return response()->json([
                    'success' => false,
                    'error' => 'STD Processes import error: '.$message,
                ], 422);
            } catch (\Throwable $e) {
                \Log::error('STD Processes import failed after upload', [
                    'manual_id' => $manual->id,
                    'process_type' => $processType,
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'STD Processes import error: '.$e->getMessage(),
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'STD rows were imported from CSV',
                'process_type' => $processType,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error uploading CSV file: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Error while uploading file: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * @return array<string, string>
     */
    private function csvImportResolutions(Request $request): array
    {
        $raw = $request->input('csv_resolutions');
        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return [];
        }

        $allowed = ['add_component', 'use_component', 'overwrite_component', 'skip'];
        $resolutions = [];
        foreach ($decoded as $index => $action) {
            if ((is_string($index) || is_int($index)) && is_string($action) && in_array($action, $allowed, true)) {
                $resolutions[(string) $index] = $action;
            }
        }

        return $resolutions;
    }

    private function rebuildExistingWorkorderStdItems(Manual $manual): void
    {
        $service = app(WorkorderStdProcessItemsService::class);

        Workorder::query()
            ->whereHas('unit', function ($query) use ($manual): void {
                $query->where('manual_id', $manual->id);
            })
            ->with('unit.manuals')
            ->chunkById(100, function ($workorders) use ($service): void {
                foreach ($workorders as $workorder) {
                    $service->rebuild($workorder);
                }
            });
    }
}
