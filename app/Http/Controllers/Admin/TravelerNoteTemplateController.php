<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Component;
use App\Models\Manual;
use App\Models\ProcessName;
use App\Models\TravelerNoteTemplate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TravelerNoteTemplateController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless(auth()->user()?->can('feature.library.traveler_notes'), 403);
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $editing = $request->filled('edit') ? TravelerNoteTemplate::findOrFail($request->integer('edit')) : null;
        $q = trim((string) $request->query('q', ''));
        $templates = TravelerNoteTemplate::with(['manual', 'processName'])
            ->when($q !== '', fn ($query) => $query->where(function ($query) use ($q) {
                $query->where('part_number', 'like', "%{$q}%")->orWhere('notes', 'like', "%{$q}%")
                    ->orWhereHas('manual', fn ($manual) => $manual->where('number', 'like', "%{$q}%"))
                    ->orWhereHas('processName', fn ($process) => $process->where('name', 'like', "%{$q}%"));
            }))->orderByDesc('id')->paginate(30)->withQueryString();

        return view('admin.traveler-notes.index', [
            'templates' => $templates, 'editing' => $editing, 'q' => $q,
            'selectedManual' => Manual::find(old('manual_id', $editing?->manual_id)),
            'selectedProcess' => ProcessName::find(old('process_names_id', $editing?->process_names_id)),
        ]);
    }

    public function options(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['manual', 'part', 'process'])],
            'q' => ['nullable', 'string', 'max:255'],
            'manual_id' => ['nullable', 'integer', 'exists:manuals,id'],
        ]);
        $search = trim($data['q'] ?? '');
        if ($data['type'] === 'manual') {
            $rows = Manual::where(fn ($q) => $q->where('number', 'like', "%{$search}%")
                ->orWhere('title', 'like', "%{$search}%"))->orderBy('number')->paginate(30);
            $results = $rows->map(fn ($row) => ['id' => $row->id, 'text' => $row->number . ' — ' . $row->title]);
        } elseif ($data['type'] === 'part') {
            $rows = Component::where('manual_id', $data['manual_id'] ?? 0)
                ->where('part_number', 'like', "%{$search}%")->where('part_number', '!=', '')
                ->select('part_number')->distinct()->orderBy('part_number')->paginate(30);
            $results = $rows->map(fn ($row) => ['id' => $row->part_number, 'text' => $row->part_number]);
        } else {
            $rows = ProcessName::where('name', '!=', ProcessName::SYSTEM_TRAVELER_NAME)
                ->where('name', 'like', "%{$search}%")->orderBy('name')->paginate(30);
            $results = $rows->map(fn ($row) => ['id' => $row->id, 'text' => $row->name]);
        }
        return response()->json(['results' => $results, 'pagination' => ['more' => $rows->hasMorePages()]]);
    }

    public function store(Request $request)
    {
        TravelerNoteTemplate::create($this->validated($request));
        return redirect()->route('library.traveler-notes.index')->with('success', 'Traveler note saved.');
    }

    public function update(Request $request, TravelerNoteTemplate $travelerNote)
    {
        $travelerNote->update($this->validated($request, $travelerNote));
        return redirect()->route('library.traveler-notes.index')->with('success', 'Traveler note updated.');
    }

    public function destroy(TravelerNoteTemplate $travelerNote)
    {
        $travelerNote->delete();
        return redirect()->route('library.traveler-notes.index')->with('success', 'Traveler note deleted.');
    }

    private function validated(Request $request, ?TravelerNoteTemplate $template = null): array
    {
        return $request->validate([
            'manual_id' => ['required', 'integer', 'exists:manuals,id'],
            'part_number' => ['required', 'string', 'max:255',
                Rule::exists('components', 'part_number')->where(fn ($q) => $q
                    ->where('manual_id', $request->input('manual_id'))->whereNull('deleted_at')),
                Rule::unique('traveler_note_templates')->where(fn ($q) => $q
                    ->where('manual_id', $request->input('manual_id'))
                    ->where('process_names_id', $request->input('process_names_id')))->ignore($template?->id),
            ],
            'process_names_id' => ['required', 'integer', Rule::exists('process_names', 'id')
                ->where(fn ($q) => $q->where('name', '!=', ProcessName::SYSTEM_TRAVELER_NAME))],
            'notes' => ['required', 'string', 'max:2000'],
        ], ['part_number.unique' => 'A note already exists for this manual, part number and process. Edit the existing note.']);
    }
}
