<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Workorder;
use App\Services\Quality\QualityAssuranceService;
use Carbon\Carbon;
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
        return view('admin.quality.index');
    }

    public function workorder(Request $request)
    {
        $number = $this->normalizeWorkorderSearch((string) $request->query('q', ''));

        if (! preg_match('/^\d{6}$/', $number)) {
            return response()->json([
                'found' => false,
                'message' => 'Enter full 6-digit workorder number.',
                'normalized' => $number,
            ], 422);
        }

        $workorder = Workorder::query()
            ->withDrafts()
            ->where('is_draft', false)
            ->where('number', (int) $number)
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
            ->first();

        if (! $workorder) {
            return response()->json([
                'found' => false,
                'message' => 'Workorder not found.',
                'normalized' => $number,
            ], 404);
        }

        return response()->json([
            'found' => true,
            'normalized' => $number,
            'workorder' => $this->buildSingleWorkorderPayload($workorder),
        ]);
    }

    public function shipmentReleaseForm(Request $request, Workorder $workorder)
    {
        $workorder->loadMissing(['customer', 'user']);

        return view('admin.quality.forms.shipmentReleaseForm', [
            'current_wo' => $workorder,
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

    private function normalizeWorkorderSearch(string $query): string
    {
        $query = trim($query);

        return preg_match('/\d/', $query) === 1
            ? preg_replace('/\D+/', '', $query)
            : $query;
    }

    private function buildSingleWorkorderPayload(Workorder $workorder): array
    {
        $qaRow = $this->qualityAssuranceService->buildWorkorderQaRows(collect([$workorder]))->first();

        return [
            'id' => (int) $workorder->id,
            'number' => (string) $workorder->number,
            'url' => route('mains.show', $workorder->id),
            'top' => $this->buildTopPayload($workorder, $qaRow),
            'warnings' => $qaRow['all_messages'],
            'checks' => $this->buildCheckPayload($qaRow),
            'photos' => $this->buildPhotoGroupsPayload($workorder),
            'submitted' => $this->qualityAssuranceService
                ->buildSubmittedInspectionRows(collect([$qaRow]))
                ->map(fn (array $row) => array_merge($row, [
                    'open_date' => $this->formatQaDate($row['open_date'] ?? null),
                    'submitted_date' => $this->formatQaDate($row['submitted_date'] ?? null),
                ]))
                ->values()
                ->all(),
            'repair_orders' => $this->buildRepairOrderPayload($workorder),
            'forms' => $this->buildFormsPayload($workorder),
        ];
    }

    private function buildCheckPayload(array $qaRow): array
    {
        $messages = collect($qaRow['all_messages'] ?? []);
        $processCounts = $qaRow['processes']['counts'] ?? [];

        return [
            [
                'label' => 'Incomplete processes',
                'ok' => (int) ($processCounts['incomplete'] ?? 0) === 0
                    && (int) ($processCounts['finished_without_start'] ?? 0) === 0,
            ],
            [
                'label' => 'Missing RO',
                'ok' => (int) ($processCounts['missing_ro'] ?? 0) === 0,
            ],
            [
                'label' => 'Missing TDR data',
                'ok' => ! $messages->contains('Missing TDR data'),
            ],
            [
                'label' => 'Completed task not finished',
                'ok' => ! $messages->contains('Completed task not finished'),
            ],
        ];
    }

    private function buildTopPayload(Workorder $workorder, array $qaRow): array
    {
        $manual = $workorder->unit?->manual;

        return [
            'customer' => $workorder->customer?->name ?? '-',
            'instruction' => $workorder->instruction?->name ?? '-',
            'technician' => $workorder->user?->name ?? '-',
            'unit' => $workorder->unit?->part_number ?? '-',
            'serial' => $qaRow['serial_number'],
            'manual' => trim((string) $qaRow['manual_number'] . ' (' . (string) $qaRow['manual_lib'] . ')'),
            'manual_revision' => $this->formatQaDate($manual?->revision_date),
            'open_date' => $this->formatQaDate($qaRow['open_date'] ?? null),
            'approved' => (bool) $qaRow['approved'],
            'approved_at' => $this->formatQaDate($qaRow['approved_at'] ?? null),
            'done' => (bool) $qaRow['is_done'],
            'status' => strtoupper($qaRow['status']),
            'customer_po' => $workorder->customer_po ?: '-',
            'description' => $workorder->description ?: '-',
        ];
    }

    private function buildPhotoGroupsPayload(Workorder $workorder): array
    {
        $configuredGroups = collect(config('workorder_media.groups', []))
            ->mapWithKeys(fn ($label, $collection) => [$collection => $label]);

        return ($workorder->media ?? collect())
            ->filter(fn (Media $media) => str_starts_with((string) $media->mime_type, 'image/'))
            ->groupBy('collection_name')
            ->map(function ($items, string $collection) use ($configuredGroups) {
                return [
                    'collection' => $collection,
                    'label' => $configuredGroups->get($collection, Str::headline($collection)),
                    'count' => $items->count(),
                    'items' => $items->values()->map(function (Media $media) use ($collection) {
                        $version = $this->mediaCacheVersion($media);
                        $bigUrl = route('image.show.big', [
                            'mediaId' => $media->id,
                            'modelId' => $media->model_id,
                            'mediaName' => $collection,
                            'v' => $version,
                        ]);

                        return [
                            'id' => (int) $media->id,
                            'name' => $media->name ?: $media->file_name,
                            'file_name' => $media->file_name,
                            'thumb' => $collection === 'logs' ? $bigUrl : route('image.show.thumb', [
                                'mediaId' => $media->id,
                                'modelId' => $media->model_id,
                                'mediaName' => $collection,
                                'v' => $version,
                            ]),
                            'big' => $bigUrl,
                        ];
                    })->all(),
                ];
            })
            ->values()
            ->all();
    }

    private function mediaCacheVersion(Media $media): int
    {
        $path = $media->getPath();

        if ($path && file_exists($path)) {
            return (int) filemtime($path);
        }

        return (int) ($media->updated_at?->timestamp ?? $media->id);
    }

    private function buildRepairOrderPayload(Workorder $workorder): array
    {
        return ($workorder->tdrs ?? collect())
            ->flatMap(function ($tdr) {
                return ($tdr->tdrProcesses ?? collect())->map(function ($process) use ($tdr) {
                    return [
                        'tdr_id' => (int) $tdr->id,
                        'process_id' => (int) $process->id,
                        'component' => $tdr->component?->part_number ?? '-',
                        'process_name' => $process->processName?->name ?? '-',
                        'repair_order' => $process->repair_order ?: '-',
                        'date_start' => $this->formatQaDate($process->date_start),
                        'date_finish' => $this->formatQaDate($process->date_finish),
                        'ok' => filled($process->repair_order) && $process->date_start !== null && $process->date_finish !== null,
                    ];
                });
            })
            ->values()
            ->all();
    }

    private function buildFormsPayload(Workorder $workorder): array
    {
        return collect([
            [
                'key' => 'certificate_of_destruction',
                'title' => 'Certificate of Destruction',
                'url' => route('log_card.sertDistrForm', ['id' => $workorder->id]),
            ],
            [
                'key' => 'shipment',
                'title' => 'Shipment',
                'url' => route('quality.forms.shipment_release', ['workorder' => $workorder->id]),
            ],
            ['key' => 'spf', 'title' => 'SPF'],
            [
                'key' => 'log_card',
                'title' => 'Log card',
                'url' => route('log_card.logCardForm', ['id' => $workorder->id]),
            ],
        ])->map(fn ($form) => $form + [
            'workorder_number' => (string) $workorder->number,
            'status' => 'Draft',
            'url' => null,
        ])->values()->all();
    }

    private function formatQaDate(mixed $date): string
    {
        if (blank($date) || $date === '-') {
            return '-';
        }

        return strtolower(Carbon::parse($date)->format('d.M.Y'));
    }
}
