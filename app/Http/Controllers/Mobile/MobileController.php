<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Component;
use App\Models\Customer;
use App\Models\GeneralTask;
use App\Models\Main;
use App\Models\Manual;
use App\Models\Material;
use App\Models\Paint;
use App\Models\Task;
use App\Models\Tdr;
use App\Models\Team;
use App\Models\Unit;
use App\Models\User;
use App\Models\Workorder;
use App\Services\PaintIndexRowsBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;


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

        $workorders = Workorder::query()
            ->whereNotNull('approve_at')
            ->whereNull('done_at')
            ->where('is_draft', 0)
            ->with([
                'unit.manual.plane:id,type',
                'tdrs' => function ($q) {
                    $q->with(['tdrProcesses.processName']);
                },
            ])
            ->orderByRaw('CASE WHEN machining_queue_order IS NULL THEN 1 ELSE 0 END ASC')
            ->orderBy('machining_queue_order', 'asc')
            ->orderBy('number', 'asc')
            ->get();

        $rows = $workorders->map(function (Workorder $wo) {
            $machiningProcesses = $wo->tdrs
                ->flatMap(fn ($tdr) => $tdr->tdrProcesses)
                ->filter(function ($tp) {
                    $name = trim((string) ($tp->processName?->name ?? ''));
                    if ($name !== 'Machining') {
                        return false;
                    }

                    return ! ((bool) ($tp->ignore_row ?? false));
                })
                ->values();

            if ($machiningProcesses->isEmpty()) {
                return null;
            }

            $editProcess = $machiningProcesses->first();

            $starts = $machiningProcesses->pluck('date_start')->filter();
            $finishes = $machiningProcesses->pluck('date_finish')->filter();

            return (object) [
                'workorder' => $wo,
                'queue_order' => $wo->machining_queue_order,
                'plane_type' => (string) ($wo->unit?->manual?->plane?->type ?? ''),
                'date_start' => $starts->isNotEmpty() ? $starts->min() : null,
                'date_finish' => $finishes->isNotEmpty() ? $finishes->max() : null,
                'edit_machining_process' => $editProcess,
            ];
        })->filter()->values();

        $withQueue = $rows
            ->filter(static fn ($row) => $row->queue_order !== null)
            ->sortBy(static fn ($row) => (int) $row->queue_order)
            ->values();

        $withoutQueue = $rows
            ->filter(static fn ($row) => $row->queue_order === null)
            ->sortByDesc(static fn ($row) => (int) $row->workorder->number)
            ->values();

        $rows = $withQueue->concat($withoutQueue)->values();

        $pos = 0;
        $rows = $rows->map(function ($row) use (&$pos) {
            if ($row->queue_order !== null) {
                $pos++;
                $row->queue_position = $pos;
            } else {
                $row->queue_position = null;
            }

            return $row;
        });

        return view('mobile.pages.machining', [
            'rows' => $rows,
        ]);
    }

    public function show(Workorder $workorder)
    {
        $workorder->load(['unit', 'media']);

        return view('mobile.pages.show', compact('workorder'));
    }

    public function profile()
    {
        $user = Auth::user();
        $teams = Team::all();

        return view('mobile.pages.profile', compact('user', 'teams'));
    }

    public function update_profile(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required',
            'phone' => 'nullable',
            'stamp' => 'required',
            'team_id' => 'required|exists:teams,id',
            'file' => 'nullable|image',
        ]);

        $user->update($request->only(['name', 'phone', 'stamp', 'team_id']));

        if ($request->hasFile('file')) {
            $user->clearMediaCollection('avatar');
            $user->addMedia($request->file('file'))->toMediaCollection('avatar');
        }

        return redirect()->route('mobile.profile')->with('success', 'Changes saved');
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

    public function changePassword(Request $request, $id)
    {
        $request->validate([
            'old_pass' => 'required',
            'password' => 'required|confirmed|min:3',
        ]);

        $user = User::findOrFail($id);

        if (!Hash::check($request->old_pass, $user->password)) {
            return redirect()->back()->with('error', 'The current password is incorrect');
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return redirect()->back()->with('success', 'New password saved');
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
            'open_at'        => ['nullable','date'],
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

        $data['user_id'] = auth()->id();
        $data['instruction_id'] = 6 ;


        // createDraft сам присвоит number и is_draft=true
        $wo = Workorder::createDraft($data);

        return redirect()->route('mobile.show', $wo->id);
    }

}
