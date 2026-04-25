<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Admin\MachiningController as AdminMachiningController;
use App\Http\Controllers\Controller;
use App\Models\Component;
use App\Models\Customer;
use App\Models\GeneralTask;
use App\Models\MachiningWorkStep;
use App\Models\Main;
use App\Models\Manual;
use App\Models\Material;
use App\Models\Paint;
use App\Models\Task;
use App\Models\Tdr;
use App\Models\TdrProcess;
use App\Models\WoBushingBatch;
use App\Models\WoBushingProcess;
use App\Models\Unit;
use App\Models\User;
use App\Models\Workorder;
use App\Services\MachiningListingRowsBuilder;
use App\Services\PaintIndexRowsBuilder;
use App\Services\WorkorderNotifyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;


class MobileController extends Controller
{
    /** @var list<string> */
    private const MOBILE_MACHINING_NO_WORK_FOR_USER_MESSAGES = [
        'You have no machining steps assigned on this work order.',
        'There is no machining work for you on this work order.',
        'This work order has no machining steps assigned to you.',
        'You are not assigned to any machining work on this work order.',
        'No matching machining tasks for you on this work order.',
    ];

    public function index()
    {
        if (Auth::user()?->roleIs('Paint')) {
            return redirect()->route('mobile.paint');
        }

        if (Auth::user()?->roleIs('Machining')) {
            return redirect()->route('mobile.machining');
        }

        $userId = Auth::id();

        $workorders = Workorder::withDrafts()
            ->with(['unit.manuals', 'customer', 'instruction',])
            ->orderByDesc('number')
            ->get();

        return view('mobile.pages.index', compact('workorders', 'userId'));
    }

    public function paint(Request $request)
    {
        $user = Auth::user();
        abort_unless($user !== null && $user->roleIs(['Paint', 'Admin', 'Manager']), 403);

        $workorders = Workorder::query()
            ->whereNotNull('approve_at')
            ->whereNull('done_at')
            ->where('is_draft', 0)
            ->with([
                'user:id,name',
                'unit.manual.plane:id,type',
                'tdrs' => function ($q) {
                    $q->with([
                        'component:id,part_number,name,ipl_num',
                        'tdrProcesses.processName',
                    ]);
                },
            ])
            ->orderByRaw('CASE WHEN paint_queue_order IS NULL THEN 1 ELSE 0 END ASC')
            ->orderBy('paint_queue_order', 'asc')
            ->orderBy('number', 'asc')
            ->get();

        $rows = app(PaintIndexRowsBuilder::class)->build($workorders);

        $lostParts = Paint::query()
            ->with(['user:id,name', 'media'])
            ->latest()
            ->limit(100)
            ->get();

        $activeTab = $request->query('tab', 'wo');
        if ($activeTab !== 'lost') {
            $activeTab = 'wo';
        }

        return view('mobile.pages.paint', [
            'rows' => $rows,
            'lostParts' => $lostParts,
            'activeTab' => $activeTab,
        ]);
    }

    public function storePaintLost(Request $request)
    {
        $user = Auth::user();
        abort_unless($user !== null && $user->roleIs(['Paint', 'Admin', 'Manager']), 403);

        $validated = $request->validate([
            'part_number' => ['required', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'comment' => ['nullable', 'string', 'max:2000'],
            'photo' => ['required', 'image', 'max:10240'],
        ]);

        $paint = Paint::query()->create([
            'user_id' => $user->id,
            'part_number' => $validated['part_number'],
            'serial_number' => $validated['serial_number'] !== null && $validated['serial_number'] !== ''
                ? $validated['serial_number']
                : null,
            'comment' => $validated['comment'] !== null && $validated['comment'] !== ''
                ? $validated['comment']
                : null,
        ]);

        $paint->addMediaFromRequest('photo')->toMediaCollection('lost');

        return redirect()->route('mobile.paint', ['tab' => 'lost'])->with('success', 'Lost part added');
    }

    public function destroyPaintLost(Paint $paint)
    {
        $user = Auth::user();
        abort_unless($user !== null && $user->roleIs(['Paint', 'Admin', 'Manager']), 403);

        $mediaIds = $paint->media()->pluck('id')->map(static fn ($id) => (int) $id)->values()->all();
        activity('paint_lost_delete')
            ->causedBy($user)
            ->performedOn($paint)
            ->event('deleted')
            ->withProperties([
                'paint_id' => (int) $paint->id,
                'part_number' => (string) ($paint->part_number ?? ''),
                'serial_number' => (string) ($paint->serial_number ?? ''),
                'comment' => (string) ($paint->comment ?? ''),
                'owner_user_id' => (int) ($paint->user_id ?? 0),
                'media_ids' => $mediaIds,
                'source' => 'mobile.paint.lost',
            ])
            ->log('Paint lost image deleted');

        $paint->delete();

        return redirect()->route('mobile.paint', ['tab' => 'lost'])->with('success', 'Lost part deleted');
    }

    public function machining(Request $request)
    {
        $user = Auth::user();
        abort_unless($user !== null && $user->roleIs(['Machining', 'Admin', 'Manager']), 403);

        if ($request->has('set_my_wo')) {
            session(['mobile_machining_my_wo' => $request->boolean('set_my_wo')]);

            return redirect()->route('mobile.machining');
        }

        if ($request->boolean('toggle_my_wo')) {
            session(['mobile_machining_my_wo' => ! (bool) session('mobile_machining_my_wo', false)]);

            return redirect()->route('mobile.machining');
        }
        $myWo = (bool) session('mobile_machining_my_wo', false);

        $workorders = $this->mobileMachiningWorkordersQuery($myWo)->get();
        // All: все открытые строки machining; My: только строки, где у пользователя есть шаг.
        $rows = $this->buildMobileMachiningFilteredRows($workorders, $user, $myWo);
        $uid = (int) ($user->id ?? 0);
        if ($myWo) {
            $rows = $rows
                ->filter(static fn (object $row) => self::mobileMachiningRowHasAssignedStepForUser($row, $uid))
                ->values();
        }
        $woList = $this->aggregateMobileMachiningWorkorderList($rows);

        return view('mobile.pages.machining', [
            'woList' => $woList,
        ]);
    }

    public function machiningWorkorder(Workorder $workorder)
    {
        $user = Auth::user();
        abort_unless($user !== null && $user->roleIs(['Machining', 'Admin', 'Manager']), 403);

        abort_unless(
            $workorder->approve_at
            && $workorder->done_at === null
            && ! $workorder->is_draft,
            404
        );

        $workorder->loadMissing($this->mobileMachiningRelations());

        $rows = $this->buildMobileMachiningFilteredRows(collect([$workorder]), $user, false);
        $uid = (int) ($user->id ?? 0);
        $rows = $rows
            ->filter(static fn (object $row) => self::mobileMachiningRowHasAssignedStepForUser($row, $uid))
            ->values();
        if ($rows->isEmpty()) {
            return redirect()
                ->route('mobile.machining')
                ->with('error', $this->randomMobileMachiningNoWorkForUserMessage());
        }

        $detailItems = $this->buildMobileMachiningWorkorderDetailItems($rows, $uid);
        if ($detailItems->isEmpty()) {
            return redirect()
                ->route('mobile.machining')
                ->with('error', $this->randomMobileMachiningNoWorkForUserMessage());
        }

        return view('mobile.pages.machining-workorder', [
            'workorder' => $workorder,
            'detailItems' => $detailItems,
        ]);
    }

    private function randomMobileMachiningNoWorkForUserMessage(): string
    {
        return collect(self::MOBILE_MACHINING_NO_WORK_FOR_USER_MESSAGES)->random();
    }

    public function updateMachiningWorkStepMobile(Request $request, MachiningWorkStep $machiningWorkStep): JsonResponse
    {
        $user = Auth::user();
        abort_unless($user !== null && $user->roleIs(['Machining', 'Admin', 'Manager']), 403);
        if (! $user->roleIs(['Admin', 'Manager'])) {
            abort_unless((int) $machiningWorkStep->machinist_user_id === (int) $user->id, 403);
        }

        return app(AdminMachiningController::class)->updateMachiningWorkStep($request, $machiningWorkStep);
    }

    /**
     * @return array<string, mixed>
     */
    private function mobileMachiningRelations(): array
    {
        return [
            'unit' => function ($q) {
                $q->select('id', 'part_number', 'name', 'manual_id')
                    ->with(['manual.plane:id,type']);
            },
            'tdrs' => function ($q) {
                $q->with([
                    'component:id,part_number,name,ipl_num',
                    'tdrProcesses.processName',
                    'tdrProcesses.machiningWorkSteps.machinist:id,name',
                ]);
            },
            'woBushingProcesses' => function ($q) {
                $q->with([
                    'line.component',
                    'process.process_name',
                    'batch.machiningWorkSteps.machinist:id,name',
                    'machiningWorkSteps.machinist:id,name',
                ]);
            },
        ];
    }

    private function mobileMachiningWorkordersQuery(bool $myWo)
    {
        return Workorder::query()
            ->whereNotNull('approve_at')
            ->whereNull('done_at')
            ->where('is_draft', 0)
            ->when($myWo, static function ($q): void {
                $uid = (int) Auth::id();
                $q->where(static function ($w) use ($uid): void {
                    $w->where('user_id', $uid)
                        ->orWhereHas('tdrs', static function ($tdrQ) use ($uid): void {
                            $tdrQ->whereHas('tdrProcesses', static function ($tpQ) use ($uid): void {
                                $tpQ->whereHas('processName', static function ($pnQ): void {
                                    $pnQ->where('name', 'Machining');
                                })->whereHas('machiningWorkSteps', static function ($s) use ($uid): void {
                                    $s->where('machinist_user_id', $uid);
                                });
                            });
                        })
                        ->orWhereHas('woBushingProcesses', static function ($wbpQ) use ($uid): void {
                            $wbpQ->where(static function ($inner) use ($uid): void {
                                $inner->whereHas('machiningWorkSteps', static function ($s) use ($uid): void {
                                    $s->where('machinist_user_id', $uid);
                                })->orWhereHas('batch.machiningWorkSteps', static function ($s) use ($uid): void {
                                    $s->where('machinist_user_id', $uid);
                                });
                            });
                        });
                });
            })
            ->with($this->mobileMachiningRelations())
            ->orderByRaw('CASE WHEN machining_queue_order IS NULL THEN 1 ELSE 0 END ASC')
            ->orderBy('machining_queue_order', 'asc')
            ->orderBy('number', 'asc');
    }

    /**
     * @param  bool  $onlyRowsForCurrentUser  true = «My»: только строки, где пользователь участвует в шагах (как раньше).
     *                                       false = «All»: все открытые строки machining без фильтра по machinist.
     */
    private function buildMobileMachiningFilteredRows(Collection $workorders, ?User $user = null, bool $onlyRowsForCurrentUser = true): Collection
    {
        $user = $user ?? Auth::user();
        $rows = app(MachiningListingRowsBuilder::class)->build($workorders);
        $uid = (int) ($user?->id ?? 0);

        $rows = $rows->filter(static fn (object $row) => ! self::mobileMachiningRowIsClosed($row));

        if ($onlyRowsForCurrentUser) {
            $rows = $rows->filter(static fn (object $row) => self::mobileMachiningRowHasUserInSteps($row, $uid));
        }

        return $rows->values();
    }

    private function aggregateMobileMachiningWorkorderList(Collection $rows): Collection
    {
        $seen = [];
        $woList = collect();
        foreach ($rows as $row) {
            $wid = (int) $row->workorder->id;
            if (isset($seen[$wid])) {
                continue;
            }
            $seen[$wid] = true;
            $wo = $row->workorder;
            $rowHasDateFinish = self::mobileMachiningDatePresent($row->date_finish ?? null);
            $showQueueNum = $wo->machining_queue_order !== null && ! $rowHasDateFinish;
            $qPos = (int) ($row->machining_queue_position ?? 0);
            $queueCellText = ($showQueueNum && $qPos > 0) ? str_pad((string) $qPos, 2, '0', STR_PAD_LEFT) : '—';
            $queueSort = ($showQueueNum && $qPos > 0) ? $qPos : PHP_INT_MAX;
            $woList->push((object) [
                'workorder' => $wo,
                'queue_display' => $queueCellText,
                'queue_sort' => $queueSort,
            ]);
        }

        return $woList->sort(function ($a, $b) {
            if ($a->queue_sort !== $b->queue_sort) {
                return $a->queue_sort <=> $b->queue_sort;
            }

            return (int) $b->workorder->number <=> (int) $a->workorder->number;
        })->values();
    }

    private function buildMobileMachiningWorkorderDetailItems(Collection $rows, int $userId): Collection
    {
        $items = collect();
        foreach ($rows as $row) {
            $allSteps = self::mobileMachiningStepsForRowUserAssignment($row);
            $mySteps = $allSteps
                ->filter(static fn ($s) => (int) ($s->machinist_user_id ?? 0) === $userId)
                ->sortBy('step_index')
                ->values();
            if ($mySteps->isEmpty()) {
                continue;
            }
            foreach ($mySteps as $step) {
                [$detailName, $detailLabel] = self::mobileMachiningStepDetailLabels($step, $row);
                $items->push((object) [
                    'kind' => 'step',
                    'step' => $step,
                    'row' => $row,
                    'detail_name' => $detailName,
                    'detail_label' => $detailLabel,
                    'date_parent' => self::machiningStepDateParent($step),
                ]);
            }
        }

        return $items;
    }

    public function show(Workorder $workorder)
    {
        $workorder->load(['unit', 'media']);

        return view('mobile.pages.show', compact('workorder'));
    }

    public function updateStorage(Request $request, Workorder $workorder): JsonResponse
    {
        $data = $request->validate([
            'storage_rack' => ['nullable', 'integer', 'min:0', 'max:999'],
            'storage_level' => ['nullable', 'integer', 'min:0', 'max:999'],
            'storage_column' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        $workorder->update($data);

        return response()->json([
            'success' => true,
            'storage_location' => $workorder->storage_location,
        ]);
    }

    public function materials()
    {
        $user = Auth::user();
        $materials = Material::all();

        return view('mobile.pages.materials', compact('user', 'materials'));
    }

    public function updateMaterialDescription(Request $request, $id)
    {
        $material = Material::findOrFail($id);
        $material->description = $request->input('description', '');
        $material->save();

        return response()->json(['success' => true]);
    }

    public function createDraft()
    {

        $draftNumber = Workorder::nextDraftNumber();
        $units = Unit::query()->with('manual')->orderBy('part_number')->get();
        $customers = Customer::query()->orderBy('name')->get(['id','name']);
        $manuals = Manual::query()->orderBy('title')->get(['id','number']);

        return view('mobile.pages.createdraft', compact('draftNumber','units','customers', 'manuals'));

    }

    public function storeDraft(\Illuminate\Http\Request $request)
    {
        $data = $request->validate([
            'unit_id'        => ['required','integer'],
            'customer_id'    => ['required','integer'],
            'instruction_id' => ['nullable','integer'],
            'serial_number'  => ['nullable','string','max:255'],
            'description'    => ['nullable','string','max:255'],
            'open_at'        => ['nullable','string'],
            'customer_po'    => ['nullable','string','max:255'],

            'external_damage'        => ['nullable'],
            'received_disassembly'   => ['nullable'],
            'disassembly_upon_arrival'=> ['nullable'],
            'nameplate_missing'      => ['nullable'],
            'extra_parts'            => ['nullable'],
            'storage_rack'   => ['nullable','integer','min:0','max:999'],
            'storage_level'  => ['nullable','integer','min:0','max:999'],
            'storage_column' => ['nullable','integer','min:0','max:999'],
        ]);

        // чекбоксы → bool
        foreach (['external_damage','received_disassembly','disassembly_upon_arrival','nameplate_missing','extra_parts'] as $k) {
            $data[$k] = $request->boolean($k);
        }

        try {
            $data['open_at'] = parse_project_date($request->input('open_at'));
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages(['open_at' => $e->getMessage()]);
        }

        $data['user_id'] = auth()->id();
        $data['instruction_id'] = 6 ;


        // createDraft сам присвоит number и is_draft=true
        $wo = Workorder::createDraft($data);

        app(WorkorderNotifyService::class)->draftCreated(
            $wo,
            (int) auth()->id(),
            (string) auth()->user()?->name
        );

        return redirect()->route('mobile.show', $wo->id);
    }

    public function storePendingDraftUnit(Request $request): JsonResponse
    {
        $data = $request->validate([
            'part_number' => ['required', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $partNumber = trim($data['part_number']);

        $unit = Unit::query()
            ->whereNull('manual_id')
            ->where('part_number', $partNumber)
            ->first();

        if (!$unit) {
            $unit = Unit::query()->create([
                'part_number' => $partNumber,
                'manual_id' => null,
                'verified' => true,
                'name' => $data['name'] ?? null,
                'description' => $data['description'] ?? null,
            ]);
        }

        return response()->json([
            'id' => $unit->id,
            'part_number' => $unit->part_number,
            'name' => $unit->name,
            'description' => $unit->description,
            'manual_number' => null,
            'manual_id' => null,
            'verified' => (bool) $unit->verified,
        ], $unit->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * Строка со стартом и финишем (в т.ч. финиш с последнего шага после build) — не показываем на mobile machining.
     */
    private static function mobileMachiningRowIsClosed(object $row): bool
    {
        return self::mobileMachiningDatePresent($row->date_start ?? null)
            && self::mobileMachiningDatePresent($row->date_finish ?? null);
    }

    private static function mobileMachiningDatePresent(mixed $d): bool
    {
        if ($d === null) {
            return false;
        }
        if ($d instanceof \DateTimeInterface) {
            return true;
        }

        return trim((string) $d) !== '';
    }

    /**
     * Есть ли у строки хотя бы один machining_work_step с machinist_user_id = пользователь.
     */
    private static function mobileMachiningRowHasAssignedStepForUser(object $row, int $userId): bool
    {
        foreach (self::mobileMachiningStepsForRowUserAssignment($row) as $step) {
            if ((int) ($step->machinist_user_id ?? 0) === $userId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Если есть записи machining steps (на batch — также на дочерних wo_bushing_process) — строка только
     * при назначении текущего пользователя на шаг; без шагов в БД — строка общая.
     */
    private static function mobileMachiningRowHasUserInSteps(object $row, int $userId): bool
    {
        $steps = self::mobileMachiningStepsForRowUserAssignment($row);
        if ($steps->isEmpty()) {
            if (self::mobileMachiningParentWorkingStepsCount($row) >= 1) {
                return false;
            }

            return true;
        }

        foreach ($steps as $step) {
            if ((int) ($step->machinist_user_id ?? 0) === $userId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Шаги строго по родителю строки списка (даты/закрытие строки). У батча — только записи с wo_bushing_batch_id,
     * у процесса — только wo_bushing_process_id (в БД они не смешиваются).
     */
    private static function mobileMachiningStepsForRow(object $row): Collection
    {
        $parent = $row->edit_machining_process ?? $row->bushing_batch ?? $row->bushing_process ?? null;
        if ($parent === null) {
            return collect();
        }

        if ($parent instanceof TdrProcess) {
            $parent->loadMissing('machiningWorkSteps');

            return $parent->machiningWorkSteps->values();
        }

        if ($parent instanceof WoBushingBatch) {
            $parent->loadMissing('machiningWorkSteps');

            return $parent->machiningWorkSteps->values();
        }

        if ($parent instanceof WoBushingProcess) {
            $parent->loadMissing('machiningWorkSteps');

            return $parent->machiningWorkSteps->values();
        }

        return collect();
    }

    /**
     * Шаги для проверки «участвует ли пользователь»: для агрегатной строки батча — все шаги батча и дочерних процессов,
     * иначе как {@see mobileMachiningStepsForRow()}.
     */
    private static function mobileMachiningStepsForRowUserAssignment(object $row): Collection
    {
        if (! empty($row->bushing_batch) && empty($row->bushing_process)) {
            return self::mobileMachiningStepsForBushingBatch($row->bushing_batch);
        }

        return self::mobileMachiningStepsForRow($row);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private static function mobileMachiningStepDetailLabels(MachiningWorkStep $step, object $fallbackRow): array
    {
        if ($step->tdr_process_id) {
            $tp = TdrProcess::query()->with(['tdr.component'])->find($step->tdr_process_id);
            if ($tp) {
                $c = $tp->tdr?->component;

                return [
                    trim((string) ($c?->name ?? '')) !== '' ? trim((string) $c->name) : '—',
                    trim((string) ($c?->part_number ?? '')),
                ];
            }
        }
        if ($step->wo_bushing_process_id) {
            $wp = WoBushingProcess::query()->with(['line.component'])->find($step->wo_bushing_process_id);
            if ($wp) {
                $c = $wp->line?->component;

                return [
                    trim((string) ($c?->name ?? '')) !== '' ? trim((string) $c->name) : 'Bushing',
                    trim((string) ($c?->part_number ?? '')),
                ];
            }
        }
        if ($step->wo_bushing_batch_id) {
            return [
                'Bushing · Batch',
                self::mobileMachiningBatchPartNumbersLabel((int) $step->wo_bushing_batch_id),
            ];
        }

        return [
            (string) ($fallbackRow->detail_name ?? '—'),
            (string) ($fallbackRow->detail_label ?? ''),
        ];
    }

    private static function machiningStepDateParent(MachiningWorkStep $step): TdrProcess|WoBushingBatch|WoBushingProcess|null
    {
        if ($step->tdr_process_id) {
            return TdrProcess::query()->find($step->tdr_process_id);
        }
        if ($step->wo_bushing_batch_id) {
            return WoBushingBatch::query()->find($step->wo_bushing_batch_id);
        }
        if ($step->wo_bushing_process_id) {
            return WoBushingProcess::query()->find($step->wo_bushing_process_id);
        }

        return null;
    }

    private static function mobileMachiningBatchPartNumbersLabel(int $batchId): string
    {
        $batch = WoBushingBatch::query()->find($batchId);
        if ($batch === null) {
            return '—';
        }
        $batch->loadMissing(['woBushingProcesses.line.component']);
        $pns = $batch->woBushingProcesses
            ->map(static fn (WoBushingProcess $wp) => trim((string) ($wp->line?->component?->part_number ?? '')))
            ->filter()
            ->unique()
            ->values();

        return $pns->isNotEmpty() ? $pns->implode(', ') : '—';
    }

    private static function mobileMachiningStepsForBushingBatch(WoBushingBatch $batch): Collection
    {
        $batch->loadMissing(['machiningWorkSteps', 'woBushingProcesses.machiningWorkSteps']);
        $merged = $batch->machiningWorkSteps->values();
        foreach ($batch->woBushingProcesses as $proc) {
            $proc->loadMissing('machiningWorkSteps');
            $merged = $merged->concat($proc->machiningWorkSteps);
        }

        return $merged->unique('id')->values();
    }

    private static function mobileMachiningParentWorkingStepsCount(object $row): int
    {
        $p = $row->edit_machining_process ?? $row->bushing_batch ?? $row->bushing_process ?? null;
        if ($p === null) {
            return 0;
        }
        if ($p instanceof WoBushingProcess) {
            $n = (int) ($p->working_steps_count ?? 0);
            if ($n >= 1) {
                return $n;
            }
            $p->loadMissing('batch');

            return (int) ($p->batch?->working_steps_count ?? 0);
        }

        return (int) ($p->working_steps_count ?? 0);
    }
}
