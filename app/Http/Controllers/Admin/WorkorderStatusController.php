<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Customer, UserUiSetting, Workorder};
use App\Services\WorkorderStatus;
use Illuminate\Http\Request;

class WorkorderStatusController extends Controller
{
    public function updateCompletionDate(Request $request, Workorder $workorder)
    {
        abort_unless($request->user()?->roleIs(['Admin', 'Manager']), 403);
        $data = $request->validate([
            'expected_completion_date' => ['present', 'nullable', 'date_format:d/M/Y'],
        ]);
        $workorder->expected_completion_date = parse_project_date($data['expected_completion_date']);
        $workorder->save();

        return response()->json(['date' => format_project_date($workorder->expected_completion_date)]);
    }

    public function index(Request $request, WorkorderStatus $status)
    {
        abort_unless($request->user()?->roleIs(['Admin', 'Manager']), 403);
        $saved = UserUiSetting::where('user_id', $request->user()->id)
            ->where('scope', 'wo-status')->where('key', 'state')->value('value');
        $saved = is_array($saved) ? $saved : [];
        $savedFilters = is_array($saved['filters'] ?? null) ? $saved['filters'] : [];
        // Restore on the server so the first screen already contains the user's results.
        if (!$request->wantsJson() && !$request->hasAny(['customer_id', 'workorder_id', 'number', 'state', 'page'])) {
            $customer = $savedFilters['customer_id'] ?? null;
            $request->merge([
                'customer_id' => is_scalar($customer) && Customer::whereKey($customer)->exists() ? $customer : null,
                'number' => substr(preg_replace('/\D/', '', is_scalar($savedFilters['number'] ?? null) ? (string) $savedFilters['number'] : ''), 0, 20),
                'state' => $this->normalizeStates($savedFilters['state'] ?? 'active'),
            ]);
        }
        $filters = $request->validate([
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'workorder_id' => ['nullable', 'integer', 'exists:workorders,id'],
            'number' => ['nullable', 'string', 'max:20', 'regex:/^[wW\s\d]*$/'],
            'page' => ['nullable', 'integer', 'min:1'],
            'state' => ['nullable', 'string', 'max:100', 'regex:/^(all|active|approved|completed)(,(all|active|approved|completed))*$/'],
        ]);
        $query = Workorder::query()->when($filters['customer_id'] ?? null, fn ($q, $id) => $q->where('customer_id', $id));
        $query->when($filters['workorder_id'] ?? null, fn ($q, $id) => $q->whereKey($id));
        $number = preg_replace('/\D/', '', $filters['number'] ?? '');
        if ($number !== '') $query->where('number', 'like', '%'.$number.'%');
        $states = $this->normalizeStates(array_key_exists('state', $filters) ? $filters['state'] : 'active');
        if ($states !== 'all') {
            $selectedStates = explode(',', $states);
            if (in_array('active', $selectedStates, true)) $query->whereNull('done_at');
            if (in_array('approved', $selectedStates, true)) $query->whereNotNull('approve_at');
        }
        $selectedNumber = $number ?: (($filters['workorder_id'] ?? null) ? Workorder::whereKey($filters['workorder_id'])->value('number') : '');
        $activeFilters = ['customer_id' => (string) ($filters['customer_id'] ?? ''), 'number' => (string) $selectedNumber, 'state' => $states];
        $matchesSaved = $activeFilters === [
            'customer_id' => (string) ($savedFilters['customer_id'] ?? ''),
            'number' => preg_replace('/\D/', '', is_scalar($savedFilters['number'] ?? null) ? (string) $savedFilters['number'] : ''),
            'state' => $this->normalizeStates($savedFilters['state'] ?? 'active'),
        ];
        $uiState = !$request->wantsJson() && $matchesSaved ? $saved : [];
        $uiState['filters'] = $activeFilters;
        $pagesLoaded = max(1, min(125, (int) ($uiState['pages'] ?? 1)));
        $workorders = $query->with(['customer', 'unit.manual'])->orderByDesc('number')->orderByDesc('id')->paginate(8)
            ->appends(array_filter($activeFilters, fn ($value) => $value !== ''));
        $nextUrl = $workorders->nextPageUrl();
        if (!$request->wantsJson() && $pagesLoaded > 1) {
            $pagesLoaded = min($pagesLoaded, max(1, $workorders->lastPage()));
            $workorders->setCollection((clone $query)->reorder()->orderByDesc('number')->orderByDesc('id')->offset(0)->limit($pagesLoaded * 8)->get());
            $nextUrl = $pagesLoaded < $workorders->lastPage() ? $workorders->url($pagesLoaded + 1) : null;
        }
        $uiState['pages'] = $pagesLoaded;
        $cards = $workorders->getCollection()->map(fn ($wo) => $status->forWorkorder($wo));
        if ($request->wantsJson()) {
            return response()->json([
                'html' => view('admin.workorders.partials.status-cards', compact('cards'))->render(),
                'next' => $nextUrl,
                'total' => $workorders->total(),
            ]);
        }
        $customers = Customer::orderBy('name')->get(['id', 'name']);
        return view('admin.workorders.status', compact('workorders', 'cards', 'customers', 'selectedNumber', 'uiState', 'nextUrl'));
    }
    private function normalizeStates($value): string
    {
        $selected = is_string($value) ? explode(',', $value) : [];
        if (in_array('all', $selected, true)) return 'all';
        // Drop the retired Completed option, including previously saved filters.
        $selected = array_values(array_intersect(['active', 'approved'], $selected));
        return $selected ? implode(',', $selected) : 'all';
    }
}
