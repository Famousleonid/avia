<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Code;
use App\Models\Component;
use App\Models\ExtraProcess;
use App\Models\Instruction;
use App\Models\LogCard;
use App\Models\Manual;
use App\Models\Task;
use App\Models\Tdr;
use App\Models\Transfer;
use App\Models\Unit;
use App\Models\User;
use App\Models\Workorder;
use App\Models\WorkorderStdProcess;
use App\Models\WorkorderUnitInspection;
use App\Services\Quality\QualityAssuranceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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
        $unitOptions = Unit::query()
            ->with('manual')
            ->orderBy('part_number')
            ->get()
            ->map(fn (Unit $unit) => $this->buildUnitOptionPayload($unit))
            ->values();

        $manualOptions = Manual::query()
            ->orderBy('number')
            ->get()
            ->map(fn (Manual $manual) => [
                'id' => (int) $manual->id,
                'number' => (string) $manual->number,
                'title' => (string) $manual->title,
                'lib' => (string) $manual->lib,
            ])
            ->values();

        return view('admin.quality.index', [
            'unitOptions' => $unitOptions,
            'manualOptions' => $manualOptions,
        ]);
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
                'stdProcesses',
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

    public function serialSearch(Request $request)
    {
        $serial = trim((string) $request->query('q', ''));

        if (mb_strlen($serial) < 2) {
            return response()->json([
                'ok' => false,
                'message' => 'Enter at least 2 characters.',
                'results' => [],
            ], 422);
        }

        $matches = [];
        $needle = mb_strtolower($serial);
        $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $serial) . '%';
        $jsonLike = '%' . str_replace(['\\', '%', '_', '/'], ['\\\\', '\\%', '\\_', '\\/'], $serial) . '%';
        $logCardCandidateTerm = collect(preg_split('/[\/\\\\]+/', $serial) ?: [])
            ->map(fn (string $part): string => trim($part))
            ->filter(fn (string $part): bool => mb_strlen($part) >= 2)
            ->sortByDesc(fn (string $part): int => mb_strlen($part))
            ->first() ?: $serial;
        $logCardLike = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $logCardCandidateTerm) . '%';

        $addMatch = function (?Workorder $workorder, string $source, string $serialValue, ?Component $component = null) use (&$matches): void {
            if (! $workorder || $workorder->is_draft) {
                return;
            }

            $key = $workorder->id . '|' . $source . '|' . $serialValue . '|' . ($component?->id ?? 0);
            $matches[$key] = [
                'workorder_id' => (int) $workorder->id,
                'workorder_number' => (string) $workorder->number,
                'workorder_url' => route('mains.show', $workorder->id),
                'tdr_url' => route('tdrs.show', $workorder->id),
                'source' => $source,
                'serial' => $serialValue,
                'component' => $component
                    ? trim(implode(' - ', array_filter([
                        (string) ($component->ipl_num ?? ''),
                        (string) ($component->part_number ?? ''),
                        (string) ($component->name ?? ''),
                    ])))
                    : '',
            ];
        };

        Workorder::query()
            ->where('is_draft', false)
            ->where('serial_number', 'like', $like)
            ->limit(25)
            ->get()
            ->each(fn (Workorder $workorder) => $addMatch($workorder, 'Workorder S/N', (string) $workorder->serial_number));

        Tdr::query()
            ->with(['workorder', 'component'])
            ->where(function ($query) use ($like): void {
                $query->where('serial_number', 'like', $like)
                    ->orWhere('assy_serial_number', 'like', $like);
            })
            ->limit(50)
            ->get()
            ->each(function (Tdr $tdr) use ($addMatch): void {
                foreach (['serial_number' => 'TDR S/N', 'assy_serial_number' => 'TDR Assy S/N'] as $field => $source) {
                    $value = trim((string) ($tdr->{$field} ?? ''));
                    if ($value !== '') {
                        $addMatch($tdr->workorder, $source, $value, $tdr->component);
                    }
                }
            });

        WorkorderUnitInspection::query()
            ->with(['workorder'])
            ->where(function ($query) use ($like): void {
                $query->where('serial_number', 'like', $like)
                    ->orWhere('assy_serial_number', 'like', $like);
            })
            ->limit(50)
            ->get()
            ->each(function (WorkorderUnitInspection $inspection) use ($addMatch): void {
                foreach (['serial_number' => 'Unit Inspection S/N', 'assy_serial_number' => 'Unit Inspection Assy S/N'] as $field => $source) {
                    $value = trim((string) ($inspection->{$field} ?? ''));
                    if ($value !== '') {
                        $addMatch($inspection->workorder, $source, $value);
                    }
                }
            });

        Transfer::query()
            ->with(['workorder', 'workorderSource', 'component'])
            ->where('component_sn', 'like', $like)
            ->limit(50)
            ->get()
            ->each(function (Transfer $transfer) use ($addMatch): void {
                $value = trim((string) ($transfer->component_sn ?? ''));
                $addMatch($transfer->workorder, 'Transfer S/N', $value, $transfer->component);
                $addMatch($transfer->workorderSource, 'Transfer Source S/N', $value, $transfer->component);
            });

        ExtraProcess::query()
            ->with(['workorder', 'component'])
            ->where('serial_num', 'like', $like)
            ->limit(50)
            ->get()
            ->each(fn (ExtraProcess $process) => $addMatch(
                $process->workorder,
                'Extra Process S/N',
                (string) $process->serial_num,
                $process->component
            ));

        LogCard::query()
            ->with('workorder')
            ->where(function ($query) use ($like, $jsonLike, $logCardLike): void {
                $query->where('component_data', 'like', $like)
                    ->orWhere('component_data', 'like', $jsonLike)
                    ->orWhere('component_data', 'like', $logCardLike)
                    ->orWhere('component_data_out', 'like', $like)
                    ->orWhere('component_data_out', 'like', $jsonLike)
                    ->orWhere('component_data_out', 'like', $logCardLike)
                    ->orWhere('destruction_certificate_data', 'like', $like);
            })
            ->limit(50)
            ->get()
            ->each(function (LogCard $logCard) use ($addMatch, $needle): void {
                $rows = array_merge(
                    $this->decodeLogCardRows($logCard->component_data),
                    $this->decodeLogCardRows($logCard->component_data_out)
                );
                $fields = ['serial_number', 'assy_serial_number', 'fit_csn', 'removed_csn'];

                foreach ($rows as $row) {
                    foreach ($fields as $field) {
                        $value = trim((string) ($row[$field] ?? ''));
                        if ($value !== '' && str_contains(mb_strtolower($value), $needle)) {
                            $addMatch($logCard->workorder, 'Log Card ' . strtoupper(str_replace('_', ' ', $field)), $value);
                        }
                    }
                }
            });

        $results = collect(array_values($matches))
            ->sortBy([['workorder_number', 'desc'], ['source', 'asc']])
            ->values()
            ->take(30)
            ->all();

        return response()->json([
            'ok' => true,
            'query' => $serial,
            'results' => $results,
        ]);
    }

    public function updateTopFields(Request $request, Workorder $workorder)
    {
        $workorder->loadMissing('unit');

        $field = (string) $request->input('field');
        abort_unless(in_array($field, ['unit_id', 'instruction_id', 'description', 'component_name', 'modified', 'serial'], true), 422);

        $rules = [
            'field' => ['required', 'in:unit_id,instruction_id,description,component_name,modified,serial'],
            'value' => ['nullable', 'string', 'max:255'],
        ];

        if ($field === 'unit_id') {
            $rules['value'] = ['required', 'integer', 'exists:units,id'];
        }
        if ($field === 'instruction_id') {
            $rules['value'] = ['required', 'integer', 'exists:instructions,id'];
        }
        if ($field === 'component_name') {
            $rules['value'] = ['required', 'string', 'max:250'];
            $rules['component_id'] = ['required', 'integer', 'exists:components,id'];
        }

        $data = $request->validate($rules);
        $value = trim((string) ($data['value'] ?? ''));

        match ($field) {
            'unit_id' => $workorder->forceFill(['unit_id' => (int) $value])->save(),
            'instruction_id' => $workorder->forceFill(['instruction_id' => (int) $value])->save(),
            'description' => tap($workorder->unit, function (?Unit $unit) use ($value): void {
                abort_unless($unit, 422, 'Workorder unit is missing.');

                $unit->forceFill(['name' => $value !== '' ? $value : null])->save();
            }),
            'component_name' => tap(Component::query()->findOrFail((int) $data['component_id']), function (Component $component) use ($workorder, $value): void {
                abort_unless(
                    $workorder->unit?->manual_id !== null
                    && (int) $component->manual_id === (int) $workorder->unit->manual_id,
                    422,
                    'Selected part does not belong to this workorder manual.'
                );

                $component->forceFill(['name' => $value])->save();
            }),
            'modified' => $workorder->forceFill(['modified' => $value !== '' ? $value : null])->save(),
            'serial' => $workorder->forceFill(['serial_number' => $value !== '' ? $value : null])->save(),
        };

        $workorder->refresh()->load([
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
        ]);
        $qaRow = $this->qualityAssuranceService->buildWorkorderQaRows(collect([$workorder]))->first();

        return response()->json([
            'ok' => true,
            'top' => $this->buildTopPayload($workorder, $qaRow),
        ]);
    }

    public function storeUnit(Request $request)
    {
        $data = $request->validate([
            'manual_id' => ['required', 'exists:manuals,id'],
            'part_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('units', 'part_number')
                    ->where(fn ($query) => $query->where('manual_id', $request->input('manual_id'))),
            ],
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
        ], [
            'part_number.unique' => 'Part number already exists in this CMM.',
        ]);

        $unit = Unit::query()->create([
            'manual_id' => (int) $data['manual_id'],
            'part_number' => $data['part_number'],
            'name' => $data['name'] ?? null,
            'description' => $data['description'] ?? null,
            'verified' => true,
        ])->load('manual');

        return response()->json($this->buildUnitOptionPayload($unit), 201);
    }

    public function shipmentReleaseForm(Request $request, Workorder $workorder)
    {
        $workorder->loadMissing(['customer', 'user']);

        return view('admin.quality.forms.shipmentReleaseForm', [
            'current_wo' => $workorder,
        ]);
    }

    public function logCardForm(Request $request, Workorder $workorder)
    {
        $workorder->loadMissing(['unit.manuals']);
        $manualId = $workorder->unit->manual_id;
        $manuals = Manual::where('id', $manualId)->with('builder')->get();
        $components = Component::where('manual_id', $manualId)->get();
        $log_card = LogCard::where('workorder_id', $workorder->id)->first();
        $componentData = $this->decodeLogCardRows($log_card?->component_data);
        $componentDataOut = $this->decodeLogCardRows($log_card?->component_data_out);

        if ($componentDataOut === [] && $componentData !== []) {
            $componentDataOut = $componentData;
        }

        return view('admin.quality.forms.logCardDoubleForm', [
            'current_wo' => $workorder,
            'manuals' => $manuals,
            'components' => $components,
            'componentData' => $componentData,
            'componentDataOut' => $componentDataOut,
            'codes' => Code::all(),
        ]);
    }

    public function certificateForm(Request $request, Workorder $workorder)
    {
        $workorder->loadMissing([
            'customer',
            'instruction',
            'tdrs',
            'unit.manual.revisionChecks',
            'user.role',
            'doneUser.role',
            'serviceBulletinLogs.serviceBulletin',
            'unitInspections',
        ]);

        $logCard = LogCard::query()
            ->where('workorder_id', $workorder->id)
            ->first();
        $decodeCertificateLogRows = static function (mixed $value): array {
            if (is_string($value)) {
                $value = json_decode($value, true);
            }

            if (! is_array($value)) {
                return [];
            }

            return collect($value)
                ->filter(fn ($row, $key) => is_int($key) && is_array($row))
                ->values()
                ->all();
        };
        $certificateLogRows = $decodeCertificateLogRows($logCard?->component_data_out ?: $logCard?->component_data);
        $certificateLogComponentIds = collect($certificateLogRows)
            ->map(fn (array $row): int => (int) ($row['component_id'] ?? 0))
            ->filter()
            ->unique()
            ->values();
        $certificateLogComponents = $certificateLogComponentIds->isNotEmpty()
            ? Component::query()
                ->whereIn('id', $certificateLogComponentIds)
                ->get(['id', 'name', 'part_number', 'assy_part_number'])
                ->keyBy('id')
            : collect();
        $user = $request->user();
        $managerOptions = User::query()
            ->with('role')
            ->where('can_sign_certificates', true)
            ->orderBy('name')
            ->get(['id', 'name', 'role_id', 'can_sign_certificates']);
        $certificateData = is_array($workorder->certificate_data) ? $workorder->certificate_data : [];
        $certificateStringSetting = static function (string $key, string $default = '') use ($certificateData): string {
            if (! array_key_exists($key, $certificateData)) {
                return $default;
            }

            $value = $certificateData[$key];

            return is_scalar($value) ? trim((string) $value) : $default;
        };
        $certificateBoolSetting = static function (string $key, bool $default) use ($certificateData): bool {
            if (! array_key_exists($key, $certificateData)) {
                return $default;
            }

            return filter_var($certificateData[$key], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
        };
        $certificateItemSettings = is_array($certificateData['item_settings'] ?? null) ? $certificateData['item_settings'] : [];
        $savedCertificateItemSource = $certificateStringSetting('certificate_item_source', 'main');
        $savedCertificateTrackingMode = $certificateStringSetting('certificate_tracking_mode');
        $certificateStateKey = $savedCertificateItemSource === 'main' && strcasecmp($savedCertificateTrackingMode, 'c') === 0
            ? 'main:c'
            : $savedCertificateItemSource;
        $selectedCertificateStateSettings = is_array($certificateItemSettings[$certificateStateKey] ?? null)
            ? $certificateItemSettings[$certificateStateKey]
            : [];
        $certificateStateStringSetting = static function (string $key, string $default = '') use ($selectedCertificateStateSettings, $certificateData): string {
            $value = $selectedCertificateStateSettings[$key] ?? $certificateData[$key] ?? null;

            return is_scalar($value) ? trim((string) $value) : $default;
        };
        $certificateStateBoolSetting = static function (string $key, bool $default) use ($selectedCertificateStateSettings, $certificateData): bool {
            $value = $selectedCertificateStateSettings[$key] ?? $certificateData[$key] ?? null;
            if ($value === null) {
                return $default;
            }

            return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
        };
        $canEditCertificateManager = (bool) $user?->canAccessQualityAssurancePage();
        $requestedManagerId = (int) $request->query('certificate_manager_id');
        $savedManagerId = (int) $certificateStateStringSetting('certificate_manager_id');
        $selectedManager = $canEditCertificateManager
            ? (
                $managerOptions->firstWhere('id', $requestedManagerId ?: $savedManagerId)
                ?: $managerOptions->firstWhere('id', $user?->id)
                ?: $managerOptions->first()
            )
            : null;
        $defaultCertificateDate = $workorder->doneDate() ?? $workorder->approve_at ?? now();
        $defaultCertificateDateIso = Carbon::parse($defaultCertificateDate)->format('Y-m-d');
        $certificateDateIso = $defaultCertificateDateIso;
        $savedCertificateDate = $certificateStateStringSetting('certificate_date');
        if ($savedCertificateDate !== '') {
            try {
                $certificateDateIso = parse_project_date($savedCertificateDate) ?: $defaultCertificateDateIso;
            } catch (\Throwable) {
                $certificateDateIso = $defaultCertificateDateIso;
            }
        }
        $includeLandingGearLogCard = $certificateStateBoolSetting('include_landing_gear_log_card', true);
        $includeRoycoService = $certificateStateBoolSetting('include_royco_service', false);
        $certificateInstructionNames = ['Test & inspect', 'Repair', 'Overhaul'];
        $certificateStatusOptions = Instruction::query()
            ->whereIn('name', $certificateInstructionNames)
            ->get(['id', 'name'])
            ->sortBy(fn (Instruction $instruction): int => array_search($instruction->name, $certificateInstructionNames, true))
            ->values();
        $certificateManagerName = trim((string) (
            $selectedManager?->name
            ?: $this->resolveCertificateManagerName($workorder, $user)
        ));

        return view('admin.quality.forms.certificateForm', [
            'current_wo' => $workorder,
            'certificateLogCard' => $logCard,
            'managerOptions' => $managerOptions,
            'canEditCertificateManager' => $canEditCertificateManager,
            'selectedCertificateManagerId' => $canEditCertificateManager
                ? (int) ($selectedManager?->id ?? $user?->id)
                : null,
            'certificateDateIso' => $certificateDateIso,
            'includeLandingGearLogCard' => $includeLandingGearLogCard,
            'includeRoycoService' => $includeRoycoService,
            'certificateItemSettings' => $certificateItemSettings,
            'certificateStatusOptions' => $certificateStatusOptions,
            'selectedCertificateInstructionId' => $workorder->instruction_id ? (int) $workorder->instruction_id : null,
            'selectedCertificateItemSource' => $savedCertificateItemSource,
            'selectedCertificateTrackingMode' => $savedCertificateTrackingMode,
            'certificateDetailOpen' => $certificateBoolSetting('certificate_detail_open', false),
            'certificateLogComponents' => $certificateLogComponents,
            'certificateManagerName' => $certificateManagerName,
        ]);
    }

    public function updateCertificateState(Request $request, Workorder $workorder)
    {
        $data = $request->validate([
            'key' => [
                'required',
                'string',
                Rule::in([
                    'certificate_item_source',
                    'certificate_detail_open',
                    'certificate_tracking_mode',
                    'certificate_manager_id',
                    'certificate_date',
                    'include_landing_gear_log_card',
                    'include_royco_service',
                ]),
            ],
            'value' => ['nullable'],
            'item_source' => ['nullable', 'string', 'max:40'],
        ]);

        $key = (string) $data['key'];
        $value = $data['value'] ?? null;

        if (in_array($key, ['certificate_detail_open', 'include_landing_gear_log_card', 'include_royco_service'], true)) {
            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
        } elseif ($key === 'certificate_tracking_mode') {
            $value = strtolower(trim((string) $value)) === 'c' ? 'c' : '';
        } elseif ($key === 'certificate_manager_id') {
            $value = trim((string) $value);
            if ($value !== '') {
                User::query()
                    ->where('can_sign_certificates', true)
                    ->findOrFail((int) $value);
            }
        } elseif ($key === 'certificate_date') {
            $value = trim((string) $value);
            if ($value !== '') {
                $value = parse_project_date($value) ?: $value;
                abort_unless((bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $value), 422, 'Invalid certificate date.');
            }
        } else {
            $value = trim((string) $value);
            abort_unless($value === 'main' || str_starts_with($value, 'log:'), 422, 'Invalid certificate detail source.');
        }

        $certificateData = is_array($workorder->certificate_data) ? $workorder->certificate_data : [];
        if (in_array($key, ['certificate_manager_id', 'certificate_date', 'include_landing_gear_log_card', 'include_royco_service'], true)) {
            $itemSource = trim((string) ($data['item_source'] ?? 'main')) ?: 'main';
            abort_unless($itemSource === 'main' || $itemSource === 'main:c' || str_starts_with($itemSource, 'log:'), 422, 'Invalid certificate detail source.');

            $itemSettings = is_array($certificateData['item_settings'] ?? null) ? $certificateData['item_settings'] : [];
            $itemSettings[$itemSource] = is_array($itemSettings[$itemSource] ?? null) ? $itemSettings[$itemSource] : [];
            $itemSettings[$itemSource][$key] = $value;
            $certificateData['item_settings'] = $itemSettings;
        } else {
            $certificateData[$key] = $value;
        }

        if ($key === 'certificate_item_source' && $value !== 'main') {
            $certificateData['certificate_tracking_mode'] = '';
        }

        $workorder->forceFill(['certificate_data' => $certificateData])->save();

        return response()->json([
            'ok' => true,
            'data' => $workorder->certificate_data,
        ]);
    }

    public function updateLogCardForm(Request $request, Workorder $workorder)
    {
        $data = $request->validate([
            'side' => ['required', 'in:left,right'],
            'section' => ['required', 'in:aircraft,primary,note,header'],
            'row' => ['required', 'integer', 'min:0', 'max:200'],
            'field' => ['required', 'string', 'max:60'],
            'value' => ['nullable', 'string', 'max:255'],
            'style' => ['nullable', 'in:text,background'],
        ]);

        $primaryFields = [
            'description',
            'part_number',
            'serial_number',
            'fit_date',
            'fit_cso',
            'fit_csn',
            'removed_date',
            'removed_cso',
            'removed_csn',
            'reason',
        ];

        $aircraftFields = [
            'fit_date',
            'fit_cso',
            'fit_csn',
            'fit_cycles',
            'removed_date',
            'removed_cso',
            'removed_csn',
            'removed_cycles',
            'reason',
        ];

        $noteFields = ['note6_text', 'note6_enabled'];
        $headerFields = ['part_number'];
        $allowedFields = match ($data['section']) {
            'aircraft' => $aircraftFields,
            'note' => $noteFields,
            'header' => $headerFields,
            default => $primaryFields,
        };
        abort_unless(in_array($data['field'], $allowedFields, true), 422);

        $logCard = LogCard::firstOrCreate(['workorder_id' => $workorder->id]);
        $wasRecentlyCreated = $logCard->wasRecentlyCreated;
        $sourceRows = $this->decodeLogCardRows($logCard->component_data);
        $targetColumn = $data['side'] === 'right' ? 'component_data_out' : 'component_data';
        $rows = $data['side'] === 'right'
            ? $this->decodeLogCardRows($logCard->component_data_out)
            : $sourceRows;

        if ($rows === [] && $sourceRows !== []) {
            $rows = $sourceRows;
        }

        $style = $data['style'] ?? 'text';
        $activityPath = $this->logCardActivityPath($data, $style);
        $beforeValue = data_get($rows, $activityPath);
        $beforeComparable = $this->normalizeLogCardActivityComparable($beforeValue);

        if ($style === 'background') {
            abort_unless(in_array($data['section'], ['aircraft', 'primary', 'header'], true), 422);
            $color = trim((string) ($data['value'] ?? ''));
            abort_unless($color === '' || preg_match('/^#[0-9A-Fa-f]{6}$/', $color), 422);
            $color = strtolower($color);

            if ($data['section'] === 'header') {
                $rows[0] ??= [];
                $rows[0]['qa_header_cell_colors'] ??= [];
                if ($color === '') {
                    unset($rows[0]['qa_header_cell_colors'][$data['field']]);
                } else {
                    $rows[0]['qa_header_cell_colors'][$data['field']] = $color;
                }
            } elseif ($data['section'] === 'aircraft') {
                $rows[0] ??= [];
                $rows[0]['qa_aircraft_cell_colors'] ??= [];
                if ($color === '') {
                    unset($rows[0]['qa_aircraft_cell_colors'][(int) $data['row']][$data['field']]);
                } else {
                    $rows[0]['qa_aircraft_cell_colors'][(int) $data['row']][$data['field']] = $color;
                }
            } else {
                $rows[(int) $data['row']] ??= [];
                $rows[(int) $data['row']]['qa_cell_colors'] ??= [];
                if ($color === '') {
                    unset($rows[(int) $data['row']]['qa_cell_colors'][$data['field']]);
                } else {
                    $rows[(int) $data['row']]['qa_cell_colors'][$data['field']] = $color;
                }
            }
        } elseif ($data['section'] === 'aircraft') {
            $rows[0] ??= [];
            $rows[0]['qa_aircraft_records'] ??= [];
            $rows[0]['qa_aircraft_records'][(int) $data['row']][$data['field']] = trim((string) ($data['value'] ?? ''));
        } elseif ($data['section'] === 'note') {
            $rows[0] ??= [];
            $rows[0]['qa_note6_text'] = $data['field'] === 'note6_text'
                ? trim((string) ($data['value'] ?? ''))
                : ($rows[0]['qa_note6_text'] ?? 'The Log Card was created refer to client\'s provided documents.');
            if ($data['field'] === 'note6_enabled') {
                $rows[0]['qa_note6_enabled'] = in_array((string) ($data['value'] ?? ''), ['1', 'true', 'on', 'yes'], true);
            }
        } elseif ($data['section'] === 'header') {
            abort_unless($data['side'] === 'right', 422);
            $rows[0] ??= [];
            $rows[0]['qa_header_part_number'] = trim((string) ($data['value'] ?? ''));
        } else {
            $rows[(int) $data['row']] ??= [];
            $value = trim((string) ($data['value'] ?? ''));
            if ($data['side'] === 'left') {
                $fieldMap = [
                    'description' => 'name',
                    'part_number' => 'part_number',
                    'serial_number' => 'serial_number',
                    'reason' => 'reason',
                ];
                if (array_key_exists($data['field'], $fieldMap)) {
                    $rows[(int) $data['row']][$fieldMap[$data['field']]] = $value;
                } else {
                    $rows[(int) $data['row']]['qa_'.$data['field']] = $value;
                }
            } else {
                $rows[(int) $data['row']]['qa_'.$data['field']] = $value;
            }
        }

        $logCard->{$targetColumn} = $targetColumn === 'component_data'
            ? json_encode(array_values($rows), JSON_UNESCAPED_UNICODE)
            : array_values($rows);

        $afterRows = $this->decodeLogCardRows($logCard->{$targetColumn});
        $afterValue = data_get($afterRows, $activityPath);
        $afterComparable = $this->normalizeLogCardActivityComparable($afterValue);

        if ($beforeComparable !== $afterComparable) {
            $logCard->save();
            if ($wasRecentlyCreated) {
                $logCard->logActivityEvent('created', [], LogCard::summarizeForActivity($logCard));
            }
            $logCard->logActivityEvent(
                'updated',
                [$targetColumn.'.'.$activityPath => $beforeValue],
                [$targetColumn.'.'.$activityPath => $afterValue],
                [
                    'source' => 'quality_assurance_log_card_form',
                    'side' => $data['side'],
                    'section' => $data['section'],
                    'row' => (int) $data['row'],
                    'field' => $data['field'],
                    'style' => $style,
                ]
            );
        }

        return response()->json([
            'success' => true,
            'field' => $data['field'],
            'style' => $style,
            'value' => trim((string) ($data['value'] ?? '')),
        ]);
    }

    private function decodeLogCardRows(mixed $value): array
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->filter(fn ($row, $key) => is_int($key) && is_array($row))
            ->values()
            ->all();
    }

    private function normalizeLogCardActivityComparable(mixed $value): mixed
    {
        if ($value === null) {
            return '';
        }

        if (is_string($value)) {
            return trim($value);
        }

        return $value;
    }

    private function logCardActivityPath(array $data, string $style): string
    {
        $row = (int) $data['row'];
        $field = (string) $data['field'];

        if ($style === 'background') {
            return match ($data['section']) {
                'aircraft' => "0.qa_aircraft_cell_colors.{$row}.{$field}",
                'header' => "0.qa_header_cell_colors.{$field}",
                default => "{$row}.qa_cell_colors.{$field}",
            };
        }

        if ($data['section'] === 'aircraft') {
            return "0.qa_aircraft_records.{$row}.{$field}";
        }

        if ($data['section'] === 'note') {
            return $field === 'note6_enabled'
                ? '0.qa_note6_enabled'
                : '0.qa_note6_text';
        }

        if ($data['section'] === 'header') {
            return '0.qa_header_'.$field;
        }

        if ($data['side'] === 'left') {
            $fieldMap = [
                'description' => 'name',
                'part_number' => 'part_number',
                'serial_number' => 'serial_number',
                'reason' => 'reason',
            ];

            if (array_key_exists($field, $fieldMap)) {
                return $row.'.'.$fieldMap[$field];
            }
        }

        return $row.'.qa_'.$field;
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

        abort_unless($user && $user->can('manager.qa'), 403);
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

    private function resolveCertificateManagerName(Workorder $workorder, ?User $user): string
    {
        $approvedBy = trim((string) ($workorder->approve_name ?? ''));
        if ($approvedBy !== '') {
            return $approvedBy;
        }

        if ($workorder->doneUser?->canSignCertificates()) {
            return (string) $workorder->doneUser->name;
        }

        if ($workorder->user?->canSignCertificates()) {
            return (string) $workorder->user->name;
        }

        return $user?->canSignCertificates() ? (string) $user->name : '';
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
            'checks' => $this->buildCheckPayload($qaRow, $workorder),
            'photos' => $this->buildPhotoGroupsPayload($workorder),
            'submitted' => $this->qualityAssuranceService
                ->buildSubmittedInspectionRows(collect([$qaRow]))
                ->map(function (array $row) use ($workorder) {
                    $submittedTarget = [
                        'task_id' => $row['submitted_task_id'] ?? null,
                        'general_task_id' => $row['submitted_general_task_id'] ?? null,
                    ];
                    $inspectionTarget = [
                        'task_id' => $row['inspection_task_id'] ?? null,
                        'general_task_id' => $row['inspection_general_task_id'] ?? null,
                    ];

                    return array_merge($row, [
                        'open_date' => $this->formatQaDate($row['open_date'] ?? null),
                        'submitted_date' => $this->formatQaDate($row['submitted_date'] ?? null),
                        'inspection_date' => $this->formatQaDate($row['inspection_date'] ?? null),
                        'submitted_url' => $this->mainTaskTargetUrl($workorder, $submittedTarget, 'date_finish'),
                        'inspection_url' => $this->mainTaskTargetUrl($workorder, $inspectionTarget, 'date_finish'),
                    ]);
                })
                ->values()
                ->all(),
            'std_processes' => $this->buildStdProcessPayload($workorder),
            'repair_orders' => $this->buildRepairOrderPayload($workorder),
            'forms' => $this->buildFormsPayload($workorder),
        ];
    }

    private function buildCheckPayload(array $qaRow, Workorder $workorder): array
    {
        $messages = collect($qaRow['all_messages'] ?? []);
        $processCounts = $qaRow['processes']['counts'] ?? [];
        $submittedRows = collect($qaRow['submitted_inspections']['pending'] ?? []);
        $stdProcessRows = collect($this->buildStdProcessPayload($workorder));
        $completedTaskUrl = $this->completedTaskUrl($workorder);
        $requiresStdProcesses = $this->isOverhaulWorkorder($workorder);

        return [
            [
                'label' => 'Submitted WO',
                'ok' => $submittedRows->isNotEmpty()
                    && $submittedRows->every(fn (array $row) => filled($row['submitted_date'] ?? null)
                        && filled($row['inspection_date'] ?? null)),
                'target' => 'qaSubmittedInspectionCards',
            ],
            [
                'label' => 'Incomplete processes',
                'ok' => (int) ($processCounts['incomplete'] ?? 0) === 0
                    && (int) ($processCounts['finished_without_start'] ?? 0) === 0,
                'target' => 'qaRepairBlock',
            ],
            [
                'label' => 'Missing RO',
                'ok' => (int) ($processCounts['missing_ro'] ?? 0) === 0,
                'target' => 'qaRepairBlock',
            ],
            [
                'label' => 'Completed task finished',
                'ok' => ! $messages->contains('Completed task not finished'),
                'target' => 'qaRepairBlock',
                'url' => $completedTaskUrl,
            ],
            [
                'label' => 'STD processes complete',
                'ok' => ! $requiresStdProcesses || $stdProcessRows->whereIn('type', ['ndt', 'cad'])
                    ->reject(fn (array $row) => (bool) ($row['ignored'] ?? false))
                    ->every(fn (array $row) => (bool) ($row['complete'] ?? false)),
                'target' => 'qaStdProcessBlock',
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
            'unit_id' => $workorder->unit_id ? (int) $workorder->unit_id : null,
            'unit_manual_id' => $workorder->unit?->manual_id ? (int) $workorder->unit->manual_id : null,
            'unit' => $workorder->unit?->part_number ?? '-',
            'modified' => $workorder->modified ?: '-',
            'serial' => $qaRow['serial_number'],
            'manual' => trim((string) $qaRow['manual_number'] . ' (' . (string) $qaRow['manual_lib'] . ')'),
            'manual_revision' => $this->formatQaDate($manual?->revision_date),
            'open_date' => $this->formatQaDate($qaRow['open_date'] ?? null),
            'approved' => (bool) $qaRow['approved'],
            'approved_at' => $this->formatQaDate($qaRow['approved_at'] ?? null),
            'done' => (bool) $qaRow['is_done'],
            'status' => strtoupper($qaRow['status']),
            'customer_po' => $workorder->customer_po ?: '-',
            'description' => $workorder->displayDescription() ?: '-',
        ];
    }

    private function buildUnitOptionPayload(Unit $unit): array
    {
        return [
            'id' => (int) $unit->id,
            'part_number' => (string) $unit->part_number,
            'name' => (string) ($unit->name ?? ''),
            'description' => (string) ($unit->description ?? ''),
            'manual_id' => $unit->manual_id ? (int) $unit->manual_id : null,
            'manual_number' => (string) ($unit->manual?->number ?? ''),
            'manual_title' => (string) ($unit->manual?->title ?? ''),
            'manual_lib' => (string) ($unit->manual?->lib ?? ''),
        ];
    }

    private function buildPhotoGroupsPayload(Workorder $workorder): array
    {
        $configuredGroups = collect(config('workorder_media.groups', []))
            ->mapWithKeys(fn ($label, $collection) => [$collection => $label]);

        $groupedMedia = collect(($workorder->media ?? collect())
            ->filter(fn (Media $media) => str_starts_with((string) $media->mime_type, 'image/'))
            ->groupBy('collection_name')
            ->all());

        $configuredPayload = $configuredGroups
            ->map(function (string $label, string $collection) use ($groupedMedia) {
                $items = $groupedMedia->get($collection, collect());

                return [
                    'collection' => $collection,
                    'label' => $label,
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
            });

        $extraPayload = $groupedMedia
            ->except($configuredGroups->keys()->all())
            ->map(function ($items, string $collection) {
                return [
                    'collection' => $collection,
                    'label' => Str::headline($collection),
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
            });

        return $configuredPayload
            ->merge($extraPayload)
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
            ->flatMap(function ($tdr) use ($workorder) {
                return ($tdr->tdrProcesses ?? collect())->map(function ($process) use ($tdr, $workorder) {
                    return [
                        'tdr_id' => (int) $tdr->id,
                        'process_id' => (int) $process->id,
                        'component' => $tdr->component?->part_number ?? '-',
                        'process_name' => $process->processName?->name ?? '-',
                        'repair_order' => $process->repair_order ?: '-',
                        'date_start' => $this->formatQaDate($process->date_start),
                        'date_finish' => $this->formatQaDate($process->date_finish),
                        'ok' => filled($process->repair_order) && $process->date_start !== null && $process->date_finish !== null,
                        'repair_order_url' => $this->mainTargetUrl($workorder, [
                            'tab' => 'parts',
                            'process' => $process->id,
                            'field' => 'repair_order',
                        ]),
                        'date_start_url' => $this->mainTargetUrl($workorder, [
                            'tab' => 'parts',
                            'process' => $process->id,
                            'field' => 'date_start',
                        ]),
                        'date_finish_url' => $this->mainTargetUrl($workorder, [
                            'tab' => 'parts',
                            'process' => $process->id,
                            'field' => 'date_finish',
                        ]),
                    ];
                });
            })
            ->values()
            ->all();
    }

    private function buildStdProcessPayload(Workorder $workorder): array
    {
        if (! $this->isOverhaulWorkorder($workorder)) {
            return [];
        }

        $rows = ($workorder->stdProcesses ?? collect())
            ->whereIn('std_type', ['ndt', 'cad'])
            ->keyBy('std_type');

        return collect([
            'ndt' => 'STD Process NDT',
            'cad' => 'STD Process CAD',
        ])->map(function (string $label, string $type) use ($rows) {
            /** @var WorkorderStdProcess|null $row */
            $row = $rows->get($type);

            return [
                'type' => $type,
                'label' => $label,
                'short_label' => strtoupper($type),
                'ignored' => (bool) ($row?->ignore_row ?? false),
                'date_start' => $this->formatQaDate($row?->date_start),
                'date_finish' => $this->formatQaDate($row?->date_finish),
                'complete' => $row !== null && ! $row->ignore_row && $row->date_finish !== null,
            ];
        })->values()->all();
    }

    private function isOverhaulWorkorder(Workorder $workorder): bool
    {
        return strcasecmp((string) ($workorder->instruction?->name ?? ''), 'Overhaul') === 0;
    }

    private function completedTaskUrl(Workorder $workorder): string
    {
        $mainRows = $workorder->relationLoaded('main')
            ? $workorder->main
            : $workorder->main()->with('task')->get();

        $completedMain = $mainRows
            ->filter(fn ($main) => strcasecmp((string) ($main->task?->name ?? ''), 'Completed') === 0)
            ->sortBy(fn ($main) => $main->task?->sort_order ?? $main->task_id ?? 999999)
            ->first();
        $completedTask = $completedMain
            ? null
            : Task::query()
                ->where('name', 'Completed')
                ->orderBy('id')
                ->first(['id', 'general_task_id']);

        return $this->mainTargetUrl($workorder, [
            'tab' => 'tasks',
            'general_task' => $completedMain?->general_task_id ?? $completedTask?->general_task_id,
            'task' => $completedMain?->task_id ?? $completedTask?->id,
            'field' => 'date_finish',
        ]);
    }

    private function mainTaskTargetUrl(?Workorder $workorder, array $target, string $field): string
    {
        return $this->mainTargetUrl($workorder, [
            'tab' => 'tasks',
            'general_task' => $target['general_task_id'] ?? null,
            'task' => $target['task_id'] ?? null,
            'field' => $field,
        ]);
    }

    private function buildFormsPayload(Workorder $workorder): array
    {
        $hasProcessFormTdrs = $workorder->relationLoaded('tdrs')
            ? $workorder->tdrs->contains(fn (Tdr $tdr) => (bool) $tdr->use_process_forms)
            : $workorder->tdrs()->where('use_process_forms', true)->exists();

        return collect([
            [
                'key' => 'log_card',
                'title' => 'Log Card',
                'url' => route('quality.forms.log_card', ['workorder' => $workorder->id]),
            ],
            [
                'key' => 'service_bulletin_log',
                'title' => 'SB log',
                'url' => route('tdrs.serviceBulletinLog', ['workorder' => $workorder->id]),
            ],
            [
                'key' => 'sp_form',
                'title' => 'SP Form',
                'url' => $hasProcessFormTdrs
                    ? route('tdrs.specProcessForm', ['id' => $workorder->id])
                    : route('tdrs.specProcessFormEmp', ['id' => $workorder->id]),
            ],
            [
                'key' => 'certificate',
                'title' => 'Form ONE',
                'url' => route('quality.forms.certificate', ['workorder' => $workorder->id]),
            ],
            [
                'key' => 'shipment',
                'title' => 'Shipment',
                'url' => route('quality.forms.shipment_release', ['workorder' => $workorder->id]),
            ],
            [
                'key' => 'certificate_of_destruction',
                'title' => 'Certificate of destruction',
                'url' => route('log_card.sertDistrForm', ['id' => $workorder->id]),
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

        $formatDateParts = static function (int $year, int $month, int $day): ?string {
            if (! checkdate($month, $day, $year)) {
                return null;
            }

            return Carbon::create($year, $month, $day)->format('d/M/Y');
        };
        $monthMap = [
            'jan' => 1, 'january' => 1,
            'feb' => 2, 'february' => 2,
            'mar' => 3, 'march' => 3,
            'apr' => 4, 'april' => 4,
            'may' => 5,
            'jun' => 6, 'june' => 6,
            'jul' => 7, 'july' => 7,
            'aug' => 8, 'august' => 8,
            'sep' => 9, 'sept' => 9, 'september' => 9,
            'oct' => 10, 'october' => 10,
            'nov' => 11, 'november' => 11,
            'dec' => 12, 'december' => 12,
        ];

        try {
            if ($date instanceof \DateTimeInterface) {
                return Carbon::instance($date)->format('d/M/Y');
            }

            $value = trim((string) $date);
            if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $value, $matches)) {
                return $formatDateParts((int) $matches[1], (int) $matches[2], (int) $matches[3]) ?? $value;
            }

            if (preg_match('/^(\d{1,2})[\/.\-]([a-z]{3,9})[\/.\-](\d{4})$/i', $value, $matches)) {
                $month = $monthMap[strtolower($matches[2])] ?? null;

                return $month !== null
                    ? ($formatDateParts((int) $matches[3], $month, (int) $matches[1]) ?? $value)
                    : $value;
            }

            if (preg_match('/^(\d{1,2})[\/.\-](\d{1,2})[\/.\-](\d{4})$/', $value, $matches)) {
                return $formatDateParts((int) $matches[3], (int) $matches[2], (int) $matches[1]) ?? $value;
            }

            return Carbon::parse($value)->format('d/M/Y');
        } catch (\Throwable) {
            return trim((string) $date) ?: '-';
        }
    }

    private function mainTargetUrl(?Workorder $workorder, array $params): string
    {
        if (! $workorder) {
            return '#';
        }

        $params = array_filter($params, fn ($value) => filled($value));

        return route('mains.show', $workorder->id) . '#qa-main:' . http_build_query($params);
    }
}
