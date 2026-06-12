<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Manual;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LibraryUnitController extends Controller
{
    public function __construct()
    {
        $this->middleware(function (Request $request, $next) {
            abort_unless(auth()->check() && auth()->user()->roleIs('Admin'), 403);

            return $next($request);
        });
    }

    public function index(Request $request): View|JsonResponse
    {
        $search = trim((string) $request->query('q', ''));
        $manualFilter = trim((string) $request->query('manual_id', ''));

        $units = Unit::query()
            ->with('manual:id,number,title')
            ->withCount('workorders')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner
                        ->where('part_number', 'like', '%' . $search . '%')
                        ->orWhere('name', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%')
                        ->orWhere('eff_code', 'like', '%' . $search . '%');
                });
            })
            ->when($manualFilter !== '', function ($query) use ($manualFilter): void {
                if ($manualFilter === 'pending') {
                    $query->whereNull('manual_id');
                    return;
                }

                $query->where('manual_id', (int) $manualFilter);
            })
            ->orderByRaw('CASE WHEN manual_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('part_number')
            ->orderBy('id')
            ->paginate(50)
            ->withQueryString();

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'html' => view('admin.library_units.partials.rows', [
                    'units' => $units,
                    'showEmpty' => false,
                ])->render(),
                'next_page_url' => $units->nextPageUrl(),
                'has_more' => $units->hasMorePages(),
                'total' => $units->total(),
            ]);
        }

        return view('admin.library_units.index', [
            'units' => $units,
            'manuals' => $this->manualOptions(),
            'q' => $search,
            'manualFilter' => $manualFilter,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedUnitData($request);
        $this->ensureUniquePartNumber($data);

        Unit::query()->create($data);

        return redirect()
            ->route('library.units.index', $this->indexQuery($request));
    }

    public function update(Request $request, Unit $unit): RedirectResponse
    {
        $data = $this->validatedUnitData($request);
        $this->ensureUniquePartNumber($data, $unit);

        $unit->update($data);

        return redirect()
            ->route('library.units.index', $this->indexQuery($request));
    }

    public function destroy(Request $request, Unit $unit): RedirectResponse
    {
        $workorderCount = $unit->workorders()->count();
        if ($workorderCount > 0) {
            return redirect()
                ->route('library.units.index', $this->indexQuery($request))
                ->with('error', "Cannot delete unit: {$workorderCount} workorder(s) are linked to it.");
        }

        $unit->delete();

        return redirect()
            ->route('library.units.index', $this->indexQuery($request));
    }

    /**
     * @return array{part_number:string, manual_id:?int, verified:bool, eff_code:?string, name:?string, description:?string}
     */
    private function validatedUnitData(Request $request): array
    {
        $data = $request->validate([
            'part_number' => ['required', 'string', 'max:255'],
            'manual_id' => ['nullable', 'integer', 'exists:manuals,id'],
            'verified' => ['nullable', 'boolean'],
            'eff_code' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $manualId = $data['manual_id'] ?? null;
        $manualId = $manualId !== null && (int) $manualId > 0 ? (int) $manualId : null;

        return [
            'part_number' => trim((string) $data['part_number']),
            'manual_id' => $manualId,
            'verified' => $request->boolean('verified'),
            'eff_code' => $this->nullableString($data['eff_code'] ?? null),
            'name' => $this->nullableString($data['name'] ?? null),
            'description' => $this->nullableString($data['description'] ?? null),
        ];
    }

    /**
     * MySQL composite unique indexes allow many NULL manual_id rows, so pending units need an explicit check too.
     *
     * @param  array{part_number:string, manual_id:?int}  $data
     */
    private function ensureUniquePartNumber(array $data, ?Unit $current = null): void
    {
        $duplicate = Unit::withTrashed()
            ->where('part_number', $data['part_number'])
            ->when(
                $data['manual_id'] === null,
                fn ($query) => $query->whereNull('manual_id'),
                fn ($query) => $query->where('manual_id', $data['manual_id'])
            )
            ->when($current !== null, fn ($query) => $query->whereKeyNot($current->id))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'part_number' => ['Part number already exists for this CMM/manual state.'],
            ]);
        }
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value !== '' ? $value : null;
    }

    private function manualOptions()
    {
        return Manual::query()
            ->orderByRaw('CASE WHEN number IS NULL OR number = "" THEN 1 ELSE 0 END')
            ->orderBy('number')
            ->orderBy('title')
            ->get(['id', 'number', 'title']);
    }

    /**
     * @return array<string, string>
     */
    private function indexQuery(Request $request): array
    {
        return collect([
            'q' => $request->input('index_q', $request->query('q')),
            'manual_id' => $request->input('index_manual_id', $request->query('manual_id')),
        ])
            ->map(fn ($value): string => trim((string) $value))
            ->filter(fn (string $value): bool => $value !== '')
            ->all();
    }
}
