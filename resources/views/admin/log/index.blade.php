@extends('admin.master')

@section('style')
    <style>
        .logs-page{height:calc(100vh - 70px);display:flex;flex-direction:column;min-height:0;}
        .logs-card{flex:1 1 auto;display:flex;flex-direction:column;min-height:0;}
        .logs-card .card-body{flex:1 1 auto;min-height:0;padding:0;}
        .logs-table-wrap{height:100%;overflow:auto;}
        .logs-table thead th{position:sticky;top:0;z-index:2;}
        .logs-table td, .logs-table th{white-space:nowrap;vertical-align:top;}
        .logs-table td.props{white-space:normal;min-width:520px;}
    </style>
@endsection

@section('content')
    <div class="logs-page dir-page">
        <div class="card logs-card dir-panel">

            <div class="card-header d-flex justify-content-between align-items-center gap-2 flex-wrap">
                <h5 class="mb-0 text-primary">Activity log (all)</h5>

                <div class="d-flex gap-2 align-items-center flex-wrap">
                    <form method="POST" action="{{ route('admin.activity.purge') }}" class="d-flex gap-2 align-items-center flex-wrap">
                        @csrf
                        <label for="purge_days" class="small text-muted mb-0">Delete older than</label>
                        <select name="days" id="purge_days" class="form-select form-select-sm bg-dark text-light border-secondary" style="width:auto">
                            @foreach([30, 60, 90, 180, 365, 730, 1095] as $daysOption)
                                <option value="{{ $daysOption }}" @selected((int) old('days', session('purge_days', 90)) === $daysOption)>
                                    {{ $daysOption }} days
                                </option>
                            @endforeach
                        </select>
                        <button type="button"
                                class="btn btn-sm btn-outline-danger"
                                data-bs-toggle="modal"
                                data-bs-target="#useConfirmDelete"
                                data-title="Delete old logs">
                            Delete old logs
                        </button>
                    </form>

                    <form method="GET" action="{{ route('admin.activity.index') }}" class="d-flex gap-2 align-items-center flex-wrap">

                        <input type="text" name="q" value="{{ request('q') }}"
                               class="form-control bg-dark text-light border-secondary"
                               placeholder="Search in all fields..." autocomplete="off" style="min-width:260px">

                        <select name="log_name" class="form-select form-select-sm bg-dark text-light border-secondary" style="width:auto">
                            <option value="all">All log_name</option>
                            @foreach($logNames as $ln)
                                <option value="{{ $ln }}" @selected(request('log_name','all')===$ln)>{{ $ln }}</option>
                            @endforeach
                        </select>

                        <select name="event" class="form-select form-select-sm bg-dark text-light border-secondary" style="width:auto">
                            <option value="all">All events</option>
                            @foreach(['created','updated','deleted', 'purged'] as $ev)
                                <option value="{{ $ev }}" @selected(request('event','all')===$ev)>{{ $ev }}</option>
                            @endforeach
                        </select>

                        <select name="subject_type" class="form-select form-select-sm bg-dark text-light border-secondary" style="width:auto; max-width:260px">
                            <option value="all">All subjects</option>
                            @foreach($subjectTypes as $st)
                                <option value="{{ $st }}" @selected(request('subject_type','all')===$st)>{{ class_basename($st) }}</option>
                            @endforeach
                        </select>

                        <select name="causer_id" class="form-select form-select-sm bg-dark text-light border-secondary" style="width:auto; max-width:220px">
                            <option value="all">All users</option>
                            @foreach($causers as $u)
                                <option value="{{ $u->id }}" @selected((string)request('causer_id','all')===(string)$u->id)>{{ $u->name }}</option>
                            @endforeach
                        </select>

                        <input type="date" name="from" value="{{ request('from') }}" class="form-control bg-dark text-light border-secondary" style="width:auto">
                        <input type="date" name="to" value="{{ request('to') }}" class="form-control bg-dark text-light border-secondary" style="width:auto">

                        <select name="per_page" class="form-select form-select-sm bg-dark text-light border-secondary" style="width:auto">
                            @foreach([25,50,100,200] as $pp)
                                <option value="{{ $pp }}" @selected((int)request('per_page', $perPage)===$pp)>{{ $pp }}/page</option>
                            @endforeach
                        </select>

                        <button class="btn btn-sm btn-outline-info">Apply</button>

                        @if(request()->query())
                            <a href="{{ route('admin.activity.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                        @endif
                    </form>
                </div>
            </div>

            @if(session()->has('purge_deleted_count'))
                <div class="px-3 py-2 border-bottom small text-warning">
                    Deleted {{ (int) session('purge_deleted_count') }} log entries older than {{ (int) session('purge_days', 0) }} days.
                </div>
            @endif

            <div class="card-body">
                <div class="logs-table-wrap">
                    <div class="table-responsive">
                        <table class="table table-sm table-dark table-hover table-bordered mb-0 logs-table dir-table">
                            <thead>
                            <tr class="text-muted small">
                                <th class="text-center">Date</th>
                                <th class="text-center">User</th>
                                <th class="text-center">Model</th>
                                <th class="text-start">Object</th>
                                <th class="text-center">Event</th>
                                <th class="text-start">Old data</th>
                                <th class="text-start">New data</th>
                            </tr>
                            </thead>

                            <tbody>
                            @forelse($activities as $a)
                                @php
                                    $propsRaw = $a->properties ?? [];
                                    if (is_object($propsRaw) && method_exists($propsRaw, 'toArray')) {
                                        $props = $propsRaw->toArray();
                                    } elseif (is_array($propsRaw)) {
                                        $props = $propsRaw;
                                    } else {
                                        $props = (array) $propsRaw;
                                    }

                                    $changes = (array)($props['changes'] ?? []);
                                    $old = (array)($props['old'] ?? $changes['old'] ?? []);
                                    $new = (array)($props['attributes'] ?? $props['new'] ?? $changes['attributes'] ?? $changes['new'] ?? []);

                                    $keyLabel = function (string $key): string {
                                        return match ($key) {
                                            'workorder_id' => 'workorder',
                                            'general_task_id' => 'general task',
                                            'task_id' => 'task',
                                            'user_id' => 'user',
                                            'manual_id', 'manuals_id' => 'manual',
                                            'component_id' => 'component',
                                            'order_component_id' => 'order component',
                                            'process_names_id' => 'process name',
                                            'processes_id' => 'process',
                                            'tdrs_id' => 'tdr',
                                            'codes_id' => 'code',
                                            'conditions_id' => 'condition',
                                            'necessaries_id' => 'necessary',
                                            'builders_id' => 'builder',
                                            'planes_id' => 'plane',
                                            'scopes_id' => 'scope',
                                            'unit_id' => 'unit',
                                            'instruction_id' => 'instruction',
                                            'customer_id' => 'customer',
                                            'done_user_id' => 'done by',
                                            'notify_user_id' => 'notify user',
                                            default => str_replace('_', ' ', $key),
                                        };
                                    };

                                    $formatValue = function (string $key, $value) use (
                                        $workorderMap,
                                        $generalTaskMap,
                                        $taskMap,
                                        $userMap,
                                        $manualMap,
                                        $componentMap,
                                        $processNameMap,
                                        $processMap,
                                        $tdrMap,
                                        $tdrProcessMap,
                                        $codeMap,
                                        $conditionMap,
                                        $necessaryMap,
                                        $builderMap,
                                        $planeMap,
                                        $scopeMap,
                                        $unitMap,
                                        $instructionMap,
                                        $customerMap,
                                        $doneUserMap,
                                        $notifyUserMap
                                    ) {
                                        if ($value === null) {
                                            return 'null';
                                        }

                                        if ($key === 'workorder_id' && is_numeric($value)) {
                                            $id = (int)$value;
                                            $num = $workorderMap[$id] ?? null;
                                            return $num ? "WO #{$num}" : "WO id {$id}";
                                        }

                                        if ($key === 'general_task_id' && is_numeric($value)) {
                                            $id = (int)$value;
                                            $name = $generalTaskMap[$id] ?? null;
                                            return $name ? $name : "general_task id {$id}";
                                        }

                                        if ($key === 'task_id' && is_numeric($value)) {
                                            $id = (int)$value;
                                            $name = $taskMap[$id] ?? null;
                                            return $name ? $name : "task id {$id}";
                                        }

                                        if ($key === 'user_id' && is_numeric($value)) {
                                            $id = (int)$value;
                                            $name = $userMap[$id] ?? null;
                                            return $name ? $name : "user id {$id}";
                                        }

                                        if (($key === 'manual_id' || $key === 'manuals_id') && is_numeric($value)) {
                                            $id = (int)$value;
                                            return $manualMap[$id] ?? "manual id {$id}";
                                        }

                                        if (($key === 'component_id' || $key === 'order_component_id') && is_numeric($value)) {
                                            $id = (int)$value;
                                            return $componentMap[$id] ?? "component id {$id}";
                                        }

                                        if ($key === 'process_names_id' && is_numeric($value)) {
                                            $id = (int)$value;
                                            return $processNameMap[$id] ?? "process_name id {$id}";
                                        }

                                        if ($key === 'processes_id' && is_numeric($value)) {
                                            $id = (int)$value;
                                            return $processMap[$id] ?? "process id {$id}";
                                        }

                                        if ($key === 'tdrs_id' && is_numeric($value)) {
                                            $id = (int)$value;
                                            return $tdrMap[$id] ?? "tdr id {$id}";
                                        }

                                        if ($key === 'codes_id' && is_numeric($value)) {
                                            $id = (int)$value;
                                            return $codeMap[$id] ?? "code id {$id}";
                                        }

                                        if ($key === 'conditions_id' && is_numeric($value)) {
                                            $id = (int)$value;
                                            return $conditionMap[$id] ?? "condition id {$id}";
                                        }

                                        if ($key === 'necessaries_id' && is_numeric($value)) {
                                            $id = (int)$value;
                                            return $necessaryMap[$id] ?? "necessary id {$id}";
                                        }

                                        if ($key === 'builders_id' && is_numeric($value)) {
                                            $id = (int)$value;
                                            return $builderMap[$id] ?? "builder id {$id}";
                                        }

                                        if ($key === 'planes_id' && is_numeric($value)) {
                                            $id = (int)$value;
                                            return $planeMap[$id] ?? "plane id {$id}";
                                        }

                                        if ($key === 'scopes_id' && is_numeric($value)) {
                                            $id = (int)$value;
                                            return $scopeMap[$id] ?? "scope id {$id}";
                                        }

                                        if ($key === 'unit_id' && is_numeric($value)) {
                                            $id = (int)$value;
                                            return $unitMap[$id] ?? "unit id {$id}";
                                        }

                                        if ($key === 'instruction_id' && is_numeric($value)) {
                                            $id = (int)$value;
                                            return $instructionMap[$id] ?? "instruction id {$id}";
                                        }

                                        if ($key === 'customer_id' && is_numeric($value)) {
                                            $id = (int)$value;
                                            return $customerMap[$id] ?? "customer id {$id}";
                                        }

                                        if ($key === 'done_user_id' && is_numeric($value)) {
                                            $id = (int)$value;
                                            return $doneUserMap[$id] ?? "user id {$id}";
                                        }

                                        if ($key === 'notify_user_id' && is_numeric($value)) {
                                            $id = (int)$value;
                                            return $notifyUserMap[$id] ?? "user id {$id}";
                                        }

                                        return (is_scalar($value) || $value === null)
                                            ? (string)$value
                                            : json_encode($value, JSON_UNESCAPED_UNICODE);
                                    };

                                    $renderProps = function (array $rows) use ($keyLabel, $formatValue) {
                                        return collect($rows)
                                            ->map(fn($v, $k) => $keyLabel((string)$k).': '.$formatValue((string)$k, $v))
                                            ->implode("\n");
                                    };

                                    $oldText = $old ? $renderProps($old) : '—';
                                    $newText = $new ? $renderProps($new) : '—';

                                    $subjectId = is_numeric($a->subject_id) ? (int)$a->subject_id : null;
                                    $subjectName = class_basename($a->subject_type);
                                    $objectText = $subjectId ? "{$subjectName} #{$subjectId}" : $subjectName;
                                    $subject = $a->subject;

                                    if ($a->subject_type === \App\Models\Manual::class && $subjectId) {
                                        $fallback = $subject
                                            ? 'manual: '.trim((string) ($subject->number ?? '')).'   lib: '.trim((string) ($subject->lib ?? ''))
                                            : null;
                                        $objectText = $manualMap[$subjectId] ?? $fallback ?? $objectText;
                                    } elseif ($a->subject_type === \App\Models\Component::class && $subjectId) {
                                        $fallback = $subject
                                            ? trim(((string) ($subject->part_number ?? '')).' '.((string) ($subject->name ?? '')))
                                            : null;
                                        $label = $componentMap[$subjectId] ?? $fallback;
                                        $objectText = filled($label) ? "component: {$label}" : $objectText;
                                    } elseif ($a->subject_type === \App\Models\Tdr::class && $subjectId) {
                                        $objectText = $tdrMap[$subjectId] ?? $objectText;
                                    } elseif ($a->subject_type === \App\Models\Workorder::class && $subjectId) {
                                        $label = $workorderMap[$subjectId] ?? ($subject->number ?? null);
                                        $objectText = $label ? "wo: {$label}" : $objectText;
                                    } elseif ($a->subject_type === \App\Models\User::class && $subjectId) {
                                        $label = $userMap[$subjectId] ?? ($subject->name ?? null);
                                        $objectText = $label ? "user: {$label}" : $objectText;
                                    } elseif ($a->subject_type === \App\Models\GeneralTask::class && $subjectId) {
                                        $label = $generalTaskMap[$subjectId] ?? ($subject->name ?? null);
                                        $objectText = $label ? "general task: {$label}" : $objectText;
                                    } elseif ($a->subject_type === \App\Models\Task::class && $subjectId) {
                                        $label = $taskMap[$subjectId] ?? ($subject->name ?? null);
                                        $objectText = $label ? "task: {$label}" : $objectText;
                                    } elseif ($a->subject_type === \App\Models\Code::class && $subjectId) {
                                        $fallback = $subject
                                            ? trim(((string) ($subject->code ?? '')).' '.((string) ($subject->name ?? '')))
                                            : null;
                                        $label = $codeMap[$subjectId] ?? $fallback;
                                        $objectText = filled($label) ? "code: {$label}" : $objectText;
                                    } elseif ($a->subject_type === \App\Models\Condition::class && $subjectId) {
                                        $label = $conditionMap[$subjectId] ?? ($subject->name ?? null);
                                        $objectText = $label ? "condition: {$label}" : $objectText;
                                    } elseif ($a->subject_type === \App\Models\Necessary::class && $subjectId) {
                                        $label = $necessaryMap[$subjectId] ?? ($subject->name ?? null);
                                        $objectText = $label ? "necessary: {$label}" : $objectText;
                                    } elseif ($a->subject_type === \App\Models\Builder::class && $subjectId) {
                                        $label = $builderMap[$subjectId] ?? ($subject->name ?? null);
                                        $objectText = $label ? "builder: {$label}" : $objectText;
                                    } elseif ($a->subject_type === \App\Models\Plane::class && $subjectId) {
                                        $label = $planeMap[$subjectId] ?? ($subject->type ?? null);
                                        $objectText = $label ? "plane: {$label}" : $objectText;
                                    } elseif ($a->subject_type === \App\Models\Scope::class && $subjectId) {
                                        $label = $scopeMap[$subjectId] ?? ($subject->scope ?? null);
                                        $objectText = $label ? "scope: {$label}" : $objectText;
                                    } elseif ($a->subject_type === \App\Models\Unit::class && $subjectId) {
                                        $fallback = $subject
                                            ? trim(((string) ($subject->part_number ?? '')).' '.((string) ($subject->name ?? '')))
                                            : null;
                                        $label = $unitMap[$subjectId] ?? $fallback;
                                        $objectText = filled($label) ? "unit: {$label}" : $objectText;
                                    } elseif ($a->subject_type === \App\Models\Instruction::class && $subjectId) {
                                        $label = $instructionMap[$subjectId] ?? ($subject->name ?? null);
                                        $objectText = $label ? "instruction: {$label}" : $objectText;
                                    } elseif ($a->subject_type === \App\Models\Customer::class && $subjectId) {
                                        $label = $customerMap[$subjectId] ?? ($subject->name ?? null);
                                        $objectText = $label ? "customer: {$label}" : $objectText;
                                    } elseif ($a->subject_type === \App\Models\Process::class && $subjectId) {
                                        $label = $processMap[$subjectId] ?? ($subject->process ?? null);
                                        $objectText = $label ? "process: {$label}" : $objectText;
                                    } elseif ($a->subject_type === \App\Models\ProcessName::class && $subjectId) {
                                        $label = $processNameMap[$subjectId] ?? ($subject->name ?? null);
                                        $objectText = $label ? "process name: {$label}" : $objectText;
                                    } elseif ($a->subject_type === \App\Models\Main::class && $subjectId) {
                                        $generalName = $a->properties['task']['general'] ?? null;
                                        $taskName = $a->properties['task']['name'] ?? null;
                                        $parts = array_values(array_filter([$generalName, $taskName], fn ($value) => filled($value)));
                                        if ($parts !== []) {
                                            $objectText = 'main: '.implode(' / ', $parts);
                                        }
                                    } elseif ($a->subject_type === \App\Models\TdrProcess::class && $subjectId) {
                                        $subjectTdrId = $subject->tdrs_id ?? null;
                                        $newTdrId = $new['tdrs_id'] ?? null;
                                        $oldTdrId = $old['tdrs_id'] ?? null;
                                        $tdrSourceId = is_numeric($subjectTdrId)
                                            ? (int) $subjectTdrId
                                            : (is_numeric($newTdrId) ? (int) $newTdrId : (is_numeric($oldTdrId) ? (int) $oldTdrId : null));

                                        $subjectProcessNameId = $subject->process_names_id ?? null;
                                        $newProcessNameId = $new['process_names_id'] ?? null;
                                        $oldProcessNameId = $old['process_names_id'] ?? null;
                                        $processSourceId = is_numeric($subjectProcessNameId)
                                            ? (int) $subjectProcessNameId
                                            : (is_numeric($newProcessNameId) ? (int) $newProcessNameId : (is_numeric($oldProcessNameId) ? (int) $oldProcessNameId : null));

                                        $tdrLabel = $tdrSourceId ? ($tdrMap[$tdrSourceId] ?? null) : null;
                                        $processLabel = $processSourceId ? ($processNameMap[$processSourceId] ?? null) : null;
                                        $parts = array_values(array_filter([$tdrLabel, $processLabel], fn ($value) => filled($value)));
                                        $fallback = $parts !== [] ? 'tdr process: '.implode('   process: ', $parts) : null;
                                        $objectText = $tdrProcessMap[$subjectId] ?? $fallback ?? $objectText;
                                    }

                                    $workorderId = null;
                                    if (isset($new['workorder_id']) && is_numeric($new['workorder_id'])) {
                                        $workorderId = (int)$new['workorder_id'];
                                    } elseif (isset($old['workorder_id']) && is_numeric($old['workorder_id'])) {
                                        $workorderId = (int)$old['workorder_id'];
                                    } elseif (isset($a->subject) && isset($a->subject->workorder_id) && is_numeric($a->subject->workorder_id)) {
                                        $workorderId = (int)$a->subject->workorder_id;
                                    }

                                    if ($workorderId !== null && $workorderId > 0 && $a->subject_type !== \App\Models\Workorder::class && $a->subject_type !== \App\Models\Tdr::class) {
                                        $workorderNumber = $workorderMap[$workorderId] ?? null;
                                        $objectText .= $workorderNumber ? "   wo: {$workorderNumber}" : "   wo id: {$workorderId}";
                                    }
                                @endphp
                                <tr>
                                    <td class="text-center small">{{ $a->created_at?->format('d.m.Y H:i') }}</td>
                                    <td class="text-center small">{{ $a->causer?->name ?? 'system' }}</td>
                                    <td class="text-center small text-info">{{ class_basename($a->subject_type) }}</td>
                                    <td class="props small">
                                        <pre class="mb-0" style="white-space:pre-wrap;word-break:break-word;">{{ $objectText }}</pre>
                                    </td>
                                    <td class="text-center">
                                    <span class="badge
                                        @if($a->event === 'created') bg-success
                                        @elseif($a->event === 'updated') bg-warning text-dark
                                        @elseif($a->event === 'deleted') bg-danger
                                        @else bg-secondary
                                        @endif">
                                        {{ $a->event }}
                                    </span>
                                    </td>
                                    <td class="props small">
                                        <pre class="mb-0" style="white-space:pre-wrap;word-break:break-word;">{{ $oldText }}</pre>
                                    </td>
                                    <td class="props small">
                                        <pre class="mb-0" style="white-space:pre-wrap;word-break:break-word;">{{ $newText }}</pre>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-3">No logs</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card-footer py-2">
                {{ $activities->links('pagination::bootstrap-5') }}
            </div>

        </div>
    </div>

    @include('components.delete')
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('useConfirmDelete');
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
            let deleteForm = null;

            if (modal && confirmDeleteBtn) {
                modal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    deleteForm = button ? button.closest('form') : null;

                    const title = button ? button.getAttribute('data-title') : null;
                    const modalTitle = modal.querySelector('#confirmDeleteLabel');

                    if (modalTitle) {
                        modalTitle.textContent = title || 'Delete Confirmation';
                    }
                });

                confirmDeleteBtn.addEventListener('click', function () {
                    if (deleteForm) {
                        deleteForm.submit();
                    }
                });
            }
        });
    </script>
@endsection
