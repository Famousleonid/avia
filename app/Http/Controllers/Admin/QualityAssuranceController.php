<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Workorder;
use App\Services\Quality\QualityAssuranceService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class QualityAssuranceController extends Controller
{
    public function __construct(
        private readonly QualityAssuranceService $qualityAssuranceService,
    ) {
        $this->middleware(function (Request $request, $next) {
            $this->authorizeQualityAccess($request);

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $filters = $this->normalizedFilters($request);
        $activeTab = $this->normalizeTab($request->get('tab', 'overview'));

        $workorders = Workorder::query()
            ->withDrafts()
            ->where('is_draft', false)
            ->with([
                'customer',
                'instruction',
                'main.task',
                'media',
                'tdrs.component.manual',
                'tdrs.conditions',
                'tdrs.necessaries',
                'tdrs.codes',
                'tdrs.tdrProcesses.processName',
                'unit.manual',
                'user',
            ])
            ->orderByDesc('open_at')
            ->orderByDesc('number')
            ->get();

        $qaRows = $this->qualityAssuranceService->buildWorkorderQaRows($workorders);
        $filteredRows = $this->qualityAssuranceService->filterRows($qaRows, $filters);

        $qualityDocumentRows = $this->qualityAssuranceService->buildQualityDocumentTabRows($filteredRows);
        $documentsFor = $request->integer('documents_for');

        if ($request->expectsJson()) {
            return response()->json([
                'summary' => $this->qualityAssuranceService->buildSummary($filteredRows),
                'count' => $filteredRows->count(),
                'statuses' => $filteredRows->map(fn ($row) => [
                    'id' => $row['id'],
                    'number' => $row['number'],
                    'status' => $row['status'],
                    'warnings' => $row['all_messages'],
                ])->values(),
            ]);
        }

        return view('admin.quality.index', [
            'activeTab' => $activeTab,
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name']),
            'filters' => $filters,
            'summary' => $this->qualityAssuranceService->buildSummary($filteredRows),
            'overviewRows' => $filteredRows,
            'workorderRows' => $filteredRows,
            'processRows' => $this->qualityAssuranceService->buildProcessTabRows($filteredRows),
            'photoRows' => $this->qualityAssuranceService->buildPhotoTabRows($filteredRows),
            'trainingRows' => $this->qualityAssuranceService->buildTrainingTabRows($filteredRows),
            'qualityDocumentRows' => $qualityDocumentRows,
            'openDocumentWorkorderId' => $documentsFor,
        ]);
    }

    public function storeQualityDocuments(Request $request, Workorder $workorder)
    {
        $this->authorizeQualityAccess($request);

        $data = $request->validate([
            'files' => ['required', 'array'],
            'files.*' => ['file', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx,csv', 'max:20480'],
        ]);

        foreach ($request->file('files', []) as $file) {
            $safeName = 'wo_' . $workorder->number . '_quality_' . now()->format('Ymd_His') . '_' . Str::random(4) . '.' . $file->getClientOriginalExtension();

            $workorder
                ->addMedia($file)
                ->usingName(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                ->usingFileName($safeName)
                ->withCustomProperties([
                    'uploaded_by' => $request->user()->id,
                    'uploaded_by_name' => $request->user()->name,
                ])
                ->toMediaCollection('quality');
        }

        return back()->with('success', 'Quality documents uploaded successfully.');
    }

    public function destroyQualityDocument(Request $request, Workorder $workorder, Media $media)
    {
        $this->authorizeQualityAccess($request);
        $this->abortUnlessQualityMediaBelongsToWorkorder($workorder, $media);

        $media->delete();

        return back()->with('success', 'Quality document deleted successfully.');
    }

    public function showQualityDocument(Request $request, Workorder $workorder, Media $media)
    {
        $this->authorizeQualityAccess($request);
        $this->abortUnlessQualityMediaBelongsToWorkorder($workorder, $media);

        return response()->file($media->getPath(), [
            'Content-Type' => $media->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="' . $media->file_name . '"',
        ]);
    }

    public function downloadQualityDocument(Request $request, Workorder $workorder, Media $media)
    {
        $this->authorizeQualityAccess($request);
        $this->abortUnlessQualityMediaBelongsToWorkorder($workorder, $media);

        return response()->download($media->getPath(), $media->file_name);
    }

    private function authorizeQualityAccess(Request $request): void
    {
        $user = $request->user();

        abort_unless($user && ($user->roleIs('Admin') || $user->can('manager.qa')), 403);
    }

    private function abortUnlessQualityMediaBelongsToWorkorder(Workorder $workorder, Media $media): void
    {
        abort_unless(
            $media->model_type === Workorder::class
            && (int) $media->model_id === (int) $workorder->id
            && $media->collection_name === 'quality',
            404
        );
    }

    private function normalizedFilters(Request $request): array
    {
        $status = strtolower((string) $request->get('status', 'all'));
        if (! in_array($status, ['all', 'ok', 'warning', 'critical'], true)) {
            $status = 'all';
        }

        return [
            'q' => trim((string) $request->get('q', '')),
            'status' => $status,
            'customer_id' => (string) $request->get('customer_id', ''),
            'missing_photos' => $request->boolean('missing_photos'),
            'missing_ro' => $request->boolean('missing_ro'),
            'incomplete_processes' => $request->boolean('incomplete_processes'),
            'missing_quality_documents' => $request->boolean('missing_quality_documents'),
        ];
    }

    private function normalizeTab(string $tab): string
    {
        $allowedTabs = ['overview', 'workorders', 'processes', 'photos', 'training', 'documents'];

        return in_array($tab, $allowedTabs, true) ? $tab : 'overview';
    }
}
