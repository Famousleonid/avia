<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{ManualBushingGuide, Workorder};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManualBushingGuideController extends Controller
{
    private function guide(Workorder $workorder): ManualBushingGuide
    {
        return ManualBushingGuide::forManual($workorder->unit?->manual_id) ?? abort(404);
    }

    private function canEdit(Request $request): bool
    {
        // This shared reference is explicitly editable by every signed-in user, including technicians.
        return $request->user() !== null;
    }

    public function show(Request $request, Workorder $workorder)
    {
        $guide = $this->guide($workorder);
        return view('admin.bushing-guide.show', [
            'guide' => $guide, 'workorder' => $workorder, 'document' => $guide->document,
            'canEdit' => $this->canEdit($request), 'manualNumbers' => $guide->manuals->pluck('number'),
        ]);
    }

    public function update(Request $request, Workorder $workorder)
    {
        abort_unless($this->canEdit($request), 403);
        $guide = $this->guide($workorder);
        // Cell whitespace is workbook content, not form-input padding. Restore it
        // after the global TrimStrings middleware, then validate every cell below.
        if ($request->isJson()) {
            $raw = json_decode($request->getContent(), true);
            if (isset($raw['changes']) && is_array($raw['changes'])) $request->merge(['changes' => $raw['changes']]);
        }
        $input = $request->validate([
            'revision' => ['required', 'integer', 'min:1'],
            'changes' => ['required', 'array', 'min:1', 'max:2000'],
            'changes.*.sheet' => ['required', 'integer', 'min:0', 'max:1'],
            'changes.*.address' => ['required', 'string', 'regex:/^[A-Z]{1,2}[1-9][0-9]{0,2}$/'],
            'changes.*.value' => ['present', 'nullable', 'string', 'max:4000'],
        ]);
        return DB::transaction(function () use ($guide, $input, $request, $workorder) {
            $guide = ManualBushingGuide::lockForUpdate()->findOrFail($guide->id);
            abort_if($guide->revision !== (int) $input['revision'], 409, 'This form was changed by another user. Reload before saving. Your unsaved changes are still on screen.');
            $document = $guide->document;
            $lookup = [];
            foreach ($document['sheets'] as $s => $sheet) foreach ($sheet['rows'] as $r => $row) foreach ($row['cells'] as $c => $cell) {
                $lookup[$s.':'.$cell['address']] = [$r, $c];
            }
            $changes = [];
            $seen = [];
            foreach ($input['changes'] as $edit) {
                $s = $edit['sheet'];
                $key = $s.':'.$edit['address'];
                if (!isset($lookup[$key]) || isset($seen[$key])) throw ValidationException::withMessages(['changes' => 'Invalid or duplicate cell.']);
                $seen[$key] = true;
                [$r, $c] = $lookup[$key];
                $old = $document['sheets'][$s]['rows'][$r]['cells'][$c]['value'];
                $new = $edit['value'] ?? '';
                if ($old === $new) continue;
                $document['sheets'][$s]['rows'][$r]['cells'][$c]['value'] = $new;
                $changes[] = ['sheet' => $document['sheets'][$s]['name'], 'cell' => $edit['address'], 'old' => $old, 'new' => $new];
            }
            if ($changes) {
                $guide->update(['document' => $document, 'revision' => $guide->revision + 1]);
                DB::table('manual_bushing_guide_changes')->insert([
                    'guide_id' => $guide->id, 'user_id' => $request->user()->id, 'user_name' => $request->user()->name,
                    'revision' => $guide->revision, 'event' => 'updated', 'created_at' => now(),
                    'changes' => json_encode($changes, JSON_UNESCAPED_UNICODE),
                ]);
                $oldValues = $newValues = [];
                foreach ($changes as $change) {
                    $label = $change['sheet'].'!'.$change['cell'];
                    $oldValues[$label] = $change['old'];
                    $newValues[$label] = $change['new'];
                }
                activity('manual_bushing_guide')->performedOn($guide)->causedBy($request->user())->event('updated')
                    ->withProperties(['old' => $oldValues, 'attributes' => $newValues, 'revision' => $guide->revision,
                        'manuals' => $guide->manuals->pluck('number')->all(), 'workorder_id' => $workorder->id])
                    ->log($guide->title.' — shared manual form');
            }
            return response()->json(['revision' => $guide->revision, 'changed' => count($changes)]);
        });
    }

    public function history(Workorder $workorder)
    {
        abort_unless(auth()->user()?->isSystemAdmin(), 403);
        $guide = $this->guide($workorder);
        $entries = DB::table('manual_bushing_guide_changes')->where('guide_id', $guide->id)->orderByDesc('id')->paginate(10);
        return view('admin.bushing-guide.history', compact('guide', 'workorder', 'entries'));
    }
}
