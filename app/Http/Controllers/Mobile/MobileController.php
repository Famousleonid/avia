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
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;


class MobileController extends Controller
{
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

    /**
     * ?set_my_wo=0|1 на странице карточки WO — сохранить сессию и остаться на этом URL без query.
     */
    private function redirectMachiningMyWoSessionFromQuery(Request $request): ?RedirectResponse
    {
        if (! $request->has('set_my_wo')) {
            return null;
        }
        session(['mobile_machining_my_wo' => $request->boolean('set_my_wo')]);

        return redirect()->to($request->url());
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

        $workorders = $this->mobileMachiningWorkordersQuery()->get();
        /** Те же строки что machining.index; режим «Мои WO» оставляет только строки с шагом, назначенным пользователю. */
        $rows = $this->buildMobileMachiningFilteredRows($workorders, $user, $myWo);
        /** На экране списка — только WO с незавершённой строкой Machining (Date finish пустой). */
        $rows = $rows
            ->filter(static fn (object $row) => ! self::mobileMachiningDatePresent($row->date_finish ?? null))
            ->values();
        $woList = $this->aggregateMobileMachiningWorkorderList($rows);

        return view('mobile.pages.machining', [
            'woList' => $woList,
        ]);
    }

    public function machiningWorkorder(Request $request, Workorder $workorder)
    {
        if ($redirectMyWo = $this->redirectMachiningMyWoSessionFromQuery($request)) {
            return $redirectMyWo;
        }

        $ctx = $this->getMobileMachiningWorkorderContext($workorder);
        if ($ctx instanceof RedirectResponse) {
            return $ctx;
        }

        $machinistName = '';
        $authId = (int) (Auth::id() ?? 0);
        $firstItem = $ctx['detailItems']->first();
        if ($firstItem?->step !== null) {
            $firstItem->step->loadMissing('machinist:id,name');
            $assignedMachinistId = (int) ($firstItem->step->machinist_user_id ?? 0);
            if ($assignedMachinistId > 0 && $assignedMachinistId !== $authId) {
                $machinistName = trim((string) ($firstItem->step->machinist?->name ?? ''));
            }
        }

        $stepMachinistIds = $ctx['detailItems']
            ->map(static fn ($item) => (int) ($item->display_machinist_user_id ?? $item->step->machinist_user_id ?? 0))
            ->filter(static fn ($id) => $id > 0)
            ->unique()
            ->values();

        $machiningStepMachinistNames = [];
        if ($stepMachinistIds->isNotEmpty()) {
            $machiningStepMachinistNames = User::query()
                ->withTrashed()
                ->whereIn('id', $stepMachinistIds->all())
                ->get(['id', 'name'])
                ->mapWithKeys(static fn (User $u) => [(int) $u->id => trim((string) ($u->name ?? ''))])
                ->all();
        }

        return view('mobile.pages.machining-workorder', [
            'workorder' => $ctx['workorder'],
            'detailItems' => $ctx['detailItems'],
            'machinistName' => $machinistName,
            'machiningStepMachinistNames' => $machiningStepMachinistNames,
            'machiningPhotoCount' => $ctx['workorder']->getMedia('Machining')->count(),
            'pdfCount' => $ctx['workorder']->getMedia('pdfs')->count(),
        ]);
    }

    public function machiningWorkorderPhotos(Request $request, Workorder $workorder)
    {
        if ($redirectMyWo = $this->redirectMachiningMyWoSessionFromQuery($request)) {
            return $redirectMyWo;
        }

        $ctx = $this->getMobileMachiningWorkorderContext($workorder);
        if ($ctx instanceof RedirectResponse) {
            return $ctx;
        }

        $wo = $ctx['workorder'];
        $photos = collect();
        foreach ($wo->getMedia('Machining') as $media) {
            if (! $media->mime_type || ! str_starts_with($media->mime_type, 'image/')) {
                continue;
            }
            $photos->push([
                'id' => $media->id,
                'thumb_url' => route('image.show.thumb', [
                    'mediaId' => $media->id,
                    'modelId' => $wo->id,
                    'mediaName' => 'Machining',
                ]),
                'big_url' => route('image.show.big', [
                    'mediaId' => $media->id,
                    'modelId' => $wo->id,
                    'mediaName' => 'Machining',
                ]),
            ]);
        }

        return view('mobile.pages.machining-workorder-photos', [
            'workorder' => $wo,
            'photos' => $photos,
        ]);
    }

    public function machiningWorkorderPdfs(Request $request, Workorder $workorder)
    {
        if ($redirectMyWo = $this->redirectMachiningMyWoSessionFromQuery($request)) {
            return $redirectMyWo;
        }

        $ctx = $this->getMobileMachiningWorkorderContext($workorder);
        if ($ctx instanceof RedirectResponse) {
            return $ctx;
        }

        $wo = $ctx['workorder'];
        $pdfs = $wo->getMedia('pdfs')->map(function ($media) use ($wo) {
            $documentName = $media->getCustomProperty('document_name') ?: ($media->name ?? null);
            $label = $documentName ?: $media->file_name;

            return [
                'id' => $media->id,
                'label' => $label,
                'file_name' => $media->file_name,
                'size' => $media->size,
                'created_at' => $media->created_at?->format('Y-m-d H:i'),
                'show_url' => route('workorders.pdf.show', [
                    'workorderId' => $wo->id,
                    'mediaId' => $media->id,
                ]),
                'download_url' => route('workorders.pdf.download', [
                    'workorderId' => $wo->id,
                    'mediaId' => $media->id,
                ]),
            ];
        })->values();

        return view('mobile.pages.machining-workorder-pdfs', [
            'workorder' => $wo,
            'pdfs' => $pdfs,
        ]);
    }

    public function storeMachiningWorkorderPhoto(Request $request, Workorder $workorder): JsonResponse
    {
        $user = Auth::user();
        if ($user === null || ! $user->roleIs(['Machining', 'Admin', 'Manager'])) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }
        if (! $workorder->isOpenForMachiningBoard()) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }
        $ctx = $this->mobileMachiningWorkorderContextCore($workorder, $user);
        if ($ctx === null) {
            return response()->json([
                'success' => false,
                'message' => 'This work order is not on the machining board or has no machining steps.',
            ], 403);
        }

        $wo = $ctx['workorder'];
        $request->validate([
            'photos' => ['required', 'array', 'min:1'],
            'photos.*' => ['file', 'image', 'max:15360'],
        ], [
            'photos.required' => 'Select at least one image.',
        ]);

        $category = 'Machining';
        foreach ($request->file('photos', []) as $photo) {
            $filename = 'wo_' . $wo->number . '_' . now()->format('Ymd_Hi') . '_' . Str::random(3) . '.' . $photo->getClientOriginalExtension();
            $wo->addMedia($photo)
                ->usingFileName($filename)
                ->toMediaCollection($category);
        }

        $wo->refresh();
        $uploaded = [];
        foreach ($wo->getMedia($category) as $media) {
            if (! $media->id) {
                continue;
            }
            $uploaded[] = [
                'id' => $media->id,
                'big_url' => route('image.show.big', [
                    'mediaId' => $media->id,
                    'modelId' => $wo->id,
                    'mediaName' => $category,
                ]),
                'thumb_url' => route('image.show.thumb', [
                    'mediaId' => $media->id,
                    'modelId' => $wo->id,
                    'mediaName' => $category,
                ]),
            ];
        }

        return response()->json([
            'success' => true,
            'photo_count' => count($uploaded),
            'machining_photo_count' => $wo->getMedia('Machining')->count(),
            'pdf_count' => $wo->getMedia('pdfs')->count(),
        ]);
    }

    public function storeMachiningWorkorderDocPdf(Request $request, Workorder $workorder): JsonResponse
    {
        $user = Auth::user();
        if ($user === null || ! $user->roleIs(['Machining', 'Admin', 'Manager'])) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }
        if (! $workorder->isOpenForMachiningBoard()) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }
        $ctx = $this->mobileMachiningWorkorderContextCore($workorder, $user);
        if ($ctx === null) {
            return response()->json([
                'success' => false,
                'message' => 'This work order is not on the machining board or has no machining steps.',
            ], 403);
        }

        $wo = $ctx['workorder'];
        $request->validate([
            'image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:15360'],
        ], [
            'image.required' => 'Select an image for the document.',
        ]);

        $file = $request->file('image');
        $mime = (string) $file->getMimeType();
        $path = $file->getRealPath();
        $raw = $path && is_readable($path) ? (string) file_get_contents($path) : (string) $file->getContent();
        if ($raw === '') {
            return response()->json(['success' => false, 'message' => 'Could not read the uploaded image.'], 422);
        }

        try {
            $binary = $this->buildMachiningWorkorderDocPdfBinary($raw, $mime);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to build PDF. Try a smaller image.',
            ], 500);
        }

        $filename = 'wo_' . $wo->number . '_machining_doc_' . now()->format('Ymd_Hi') . '_' . Str::random(3) . '.pdf';
        $label = 'Machining doc ' . now()->format('Y-m-d H:i');

        $media = $wo
            ->addMediaFromString($binary)
            ->usingFileName($filename)
            ->toMediaCollection('pdfs');
        $media->setCustomProperty('document_name', $label);
        $media->setCustomProperty('source', 'mobile_machining_doc');
        $media->name = $label;
        $media->save();

        $wo->refresh();

        return response()->json([
            'success' => true,
            'media_id' => $media->id,
            'machining_photo_count' => $wo->getMedia('Machining')->count(),
            'pdf_count' => $wo->getMedia('pdfs')->count(),
        ]);
    }

    public function destroyMachiningWorkorderMedia(Workorder $workorder, Media $media): RedirectResponse
    {
        $ctx = $this->getMobileMachiningWorkorderContext($workorder);
        if ($ctx instanceof RedirectResponse) {
            return $ctx;
        }

        $wo = $ctx['workorder'];
        abort_unless(
            (int) $media->model_id === (int) $wo->id
            && $media->model_type === $wo->getMorphClass()
            && in_array($media->collection_name, ['Machining', 'pdfs'], true),
            404
        );

        if ($media->collection_name === 'Machining') {
            abort_unless(
                $media->mime_type && str_starts_with($media->mime_type, 'image/'),
                404
            );
        }

        $media->delete();

        return back()->with('success', __('Deleted.'));
    }

    /**
     * @return RedirectResponse|array{workorder: Workorder, detailItems: Collection<int, object>}
     */
    private function getMobileMachiningWorkorderContext(Workorder $workorder): RedirectResponse|array
    {
        $user = Auth::user();
        abort_unless($user !== null && $user->roleIs(['Machining', 'Admin', 'Manager']), 403);

        abort_unless($workorder->isOpenForMachiningBoard(), 404);

        $ctx = $this->mobileMachiningWorkorderContextCore($workorder, $user);
        if ($ctx === null) {
            return redirect()
                ->route('mobile.machining')
                ->with('error', 'This work order is not on the machining board or has no machining steps.');
        }

        return $ctx;
    }

    /**
     * @return array{workorder: Workorder, detailItems: Collection<int, object>}|null
     */
    private function mobileMachiningWorkorderContextCore(Workorder $workorder, User $user): ?array
    {
        $workorder->loadMissing($this->mobileMachiningRelations());

        $rows = $this->buildMobileMachiningFilteredRows(collect([$workorder]), $user, false)->values();
        if ($rows->isEmpty()) {
            return null;
        }

        $uid = (int) ($user->id ?? 0);
        $onlyMine = (bool) session('mobile_machining_my_wo', false);
        if ($onlyMine && $uid > 0) {
            $rows = $rows
                ->filter(static fn (object $row) => self::mobileMachiningRowHasAssignedStepForUser($row, $uid))
                ->values();
            if ($rows->isEmpty()) {
                return null;
            }
        }

        $restrictStepsToMachinistId = ($onlyMine && $uid > 0) ? $uid : null;
        $detailItems = $this->buildMobileMachiningWorkorderDetailItems($rows, $restrictStepsToMachinistId);
        if ($detailItems->isEmpty()) {
            return null;
        }

        return [
            'workorder' => $workorder,
            'detailItems' => $detailItems,
        ];
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

    /**
     * Тот же набор WO и eager-load, что у {@see MachiningController} machining index (без сужения по роли в SQL).
     */
    private function mobileMachiningWorkordersQuery()
    {
        return Workorder::query()
            ->whereNull('done_at')
            ->whereMachiningHasDateSent()
            ->with(array_merge([
                'user:id,name',
                'customer:id,name',
            ], $this->mobileMachiningRelations()))
            ->orderByRaw('CASE WHEN machining_queue_order IS NULL THEN 1 ELSE 0 END ASC')
            ->orderBy('machining_queue_order', 'asc')
            ->orderBy('number', 'asc');
    }

    /**
     * @param  bool  $onlyMyMachiningSteps  true = только строки с шагом machinist = текущий пользователь (как «Мои WO»).
     */
    private function buildMobileMachiningFilteredRows(Collection $workorders, ?User $user = null, bool $onlyMyMachiningSteps = false): Collection
    {
        $user = $user ?? Auth::user();
        $rows = app(MachiningListingRowsBuilder::class)->build($workorders);
        $uid = (int) ($user?->id ?? 0);

        if ($onlyMyMachiningSteps && $uid > 0) {
            $rows = $rows->filter(static fn (object $row) => self::mobileMachiningRowHasAssignedStepForUser($row, $uid));
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

            return (int) $a->workorder->number <=> (int) $b->workorder->number;
        })->values();
    }

    /**
     * @param  ?int  $restrictStepsToMachinistId  только шаги с machinist_user_id = этому id (режим «My WO» на карточке).
     */
    private function buildMobileMachiningWorkorderDetailItems(Collection $rows, ?int $restrictStepsToMachinistId = null): Collection
    {
        $items = collect();
        foreach ($rows as $row) {
            $allStepsFull = self::mobileMachiningStepsForRowUserAssignment($row)
                ->sortBy('step_index')
                ->values();
            $fallbackMachinistId = (int) ($allStepsFull->first(static fn ($x) => (int) ($x->machinist_user_id ?? 0) > 0)?->machinist_user_id ?? 0);

            $allSteps = $allStepsFull;
            if ($restrictStepsToMachinistId !== null && $restrictStepsToMachinistId > 0) {
                $allSteps = $allStepsFull
                    ->filter(static fn ($s) => (int) ($s->machinist_user_id ?? 0) === $restrictStepsToMachinistId)
                    ->values();
            }
            foreach ($allSteps as $step) {
                $assignedId = (int) ($step->machinist_user_id ?? 0);
                $displayMachinistId = $assignedId > 0 ? $assignedId : ($fallbackMachinistId > 0 ? $fallbackMachinistId : 0);

                [$detailName, $detailLabel] = self::mobileMachiningStepDetailLabels($step, $row);
                $items->push((object) [
                    'kind' => 'step',
                    'step' => $step,
                    'row' => $row,
                    'detail_name' => $detailName,
                    'detail_label' => $detailLabel,
                    'detail_serial' => self::mobileMachiningStepDetailSerial($step, $row),
                    'date_parent' => self::machiningStepDateParent($step),
                    /** Для подписи возле Step: если на шаге пусто — как у другого шага той же строки Machining. Права редактирования — только по фактическому machinist_user_id шага. */
                    'display_machinist_user_id' => $displayMachinistId > 0 ? $displayMachinistId : null,
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

    /** SN детали по строке Machining: только у соответствующего {@see Tdr} (не serial WO). */
    private static function mobileMachiningStepDetailSerial(MachiningWorkStep $step, object $row): string
    {
        if (! $step->tdr_process_id) {
            return '';
        }

        $tpid = (int) $step->tdr_process_id;
        $embTp = $row->edit_machining_process ?? null;
        $tdr = null;
        if ($embTp !== null && (int) $embTp->id === $tpid) {
            $embTp->loadMissing('tdr');
            $tdr = $embTp->tdr;
        } else {
            $tdr = TdrProcess::query()->with(['tdr'])->find($tpid)?->tdr;
        }

        if ($tdr === null) {
            return '';
        }

        foreach (['serial_number', 'assy_serial_number'] as $attr) {
            $v = trim((string) ($tdr->{$attr} ?? ''));
            if ($v !== '') {
                return $v;
            }
        }

        return '';
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

    /**
     * One PDF page sized to the photo (within A4), explicit mm layout — avoids DomPDF splitting / half-empty A4.
     *
     * @throws \RuntimeException
     */
    private function buildMachiningWorkorderDocPdfBinary(string $raw, string $mime): string
    {
        $normalized = $this->normalizeMachiningDocImageForPdf($raw, $mime);
        $embed = $normalized ?? ['data' => $raw, 'mime' => $mime];
        $data = $embed['data'];
        $mimeOut = (string) $embed['mime'];

        $info = @getimagesizefromstring($data);
        if ($info === false || ($info[0] ?? 0) < 1 || ($info[1] ?? 0) < 1) {
            throw new \RuntimeException('Invalid image dimensions.');
        }

        $wPx = (int) $info[0];
        $hPx = (int) $info[1];
        $imgRatio = $wPx / $hPx;

        $marginMm = 5.0;
        // US Letter 8.5" × 11" (page box for fitting the image, not fixed DomPDF "letter" preset)
        $paperWmm = 8.5 * 25.4;
        $paperHmm = 11.0 * 25.4;
        $maxInnerW = $paperWmm - 2 * $marginMm;
        $maxInnerH = $paperHmm - 2 * $marginMm;
        $boxRatio = $maxInnerW / $maxInnerH;

        if ($imgRatio > $boxRatio) {
            $dispWmm = $maxInnerW;
            $dispHmm = $maxInnerW / $imgRatio;
        } else {
            $dispHmm = $maxInnerH;
            $dispWmm = $maxInnerH * $imgRatio;
        }

        $pageWmm = $dispWmm + 2 * $marginMm;
        $pageHmm = $dispHmm + 2 * $marginMm;

        $mm2pt = static fn (float $mm): float => $mm * 72 / 25.4;
        $pageWpt = $mm2pt($pageWmm);
        $pageHpt = $mm2pt($pageHmm);

        $src = 'data:' . $mimeOut . ';base64,' . base64_encode($data);
        $html = '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
            . '<style>@page{margin:0;}html,body{margin:0;padding:0;}'
            . 'body{padding:' . $marginMm . 'mm;box-sizing:border-box;}'
            . 'img{display:block;margin:0;padding:0;width:' . $dispWmm . 'mm;height:' . $dispHmm . 'mm;}'
            . '</style></head><body><img src="'
            . htmlspecialchars($src, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . '" alt=""></body></html>';

        return Pdf::loadHTML($html)
            ->setPaper([0.0, 0.0, $pageWpt, $pageHpt])
            ->output();
    }

    /**
     * Apply EXIF orientation (phone camera) and re-encode as JPEG for reliable DomPDF embedding.
     *
     * @return array{data: string, mime: string}|null
     */
    private function normalizeMachiningDocImageForPdf(string $raw, string $mime): ?array
    {
        if (! function_exists('imagecreatefromstring')) {
            return null;
        }

        $im = @imagecreatefromstring($raw);
        if ($im === false) {
            return null;
        }

        if (function_exists('exif_read_data') && str_contains(strtolower($mime), 'jpeg')) {
            $tmp = tempnam(sys_get_temp_dir(), 'mdoc');
            if ($tmp !== false) {
                file_put_contents($tmp, $raw);
                $exif = @exif_read_data($tmp);
                @unlink($tmp);
                if (is_array($exif) && ! empty($exif['Orientation'])) {
                    $fixed = $this->applyExifOrientationToGdImage($im, (int) $exif['Orientation']);
                    if ($fixed !== false) {
                        imagedestroy($im);
                        $im = $fixed;
                    }
                }
            }
        }

        if (function_exists('imageistruecolor') && ! imageistruecolor($im)) {
            $w = imagesx($im);
            $h = imagesy($im);
            $tc = imagecreatetruecolor($w, $h);
            if ($tc !== false) {
                $white = imagecolorallocate($tc, 255, 255, 255);
                imagefill($tc, 0, 0, $white);
                imagecopy($tc, $im, 0, 0, 0, 0, $w, $h);
                imagedestroy($im);
                $im = $tc;
            }
        }

        ob_start();
        imagejpeg($im, null, 92);
        $out = (string) ob_get_clean();
        imagedestroy($im);

        if ($out === '') {
            return null;
        }

        return ['data' => $out, 'mime' => 'image/jpeg'];
    }

    /**
     * @param \GdImage|resource $im
     * @return \GdImage|resource|false
     */
    private function applyExifOrientationToGdImage($im, int $orientation)
    {
        $rot = null;
        $flipH = false;
        $flipV = false;

        switch ($orientation) {
            case 2:
                $flipH = true;
                break;
            case 3:
                $rot = 180;
                break;
            case 4:
                $flipV = true;
                break;
            case 5:
                $flipH = true;
                $rot = 270;
                break;
            case 6:
                $rot = 270;
                break;
            case 7:
                $flipH = true;
                $rot = 90;
                break;
            case 8:
                $rot = 90;
                break;
            default:
                return $im;
        }

        if ($flipH && function_exists('imageflip')) {
            imageflip($im, IMG_FLIP_HORIZONTAL);
        }
        if ($flipV && function_exists('imageflip')) {
            imageflip($im, IMG_FLIP_VERTICAL);
        }
        if ($rot !== null) {
            $bg = imagecolorallocatealpha($im, 255, 255, 255, 127);
            $turned = imagerotate($im, $rot, $bg !== false ? $bg : 0);
            if ($turned === false) {
                return false;
            }
            imagedestroy($im);

            return $turned;
        }

        return $im;
    }
}
