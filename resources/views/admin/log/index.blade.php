@extends('admin.master')

@section('style')
    <style>
        .logs-page{height:calc(100vh - 70px);display:flex;flex-direction:column;min-height:0;}
        .logs-card{flex:1 1 auto;display:flex;flex-direction:column;min-height:0;}
        .logs-card .card-body{flex:1 1 auto;min-height:0;padding:0;}
        .logs-table-wrap{height:100%;overflow-y:auto;overflow-x:hidden;}
        .logs-card .table-responsive{height:100%;overflow-x:hidden;}
        .logs-table{width:100%;table-layout:fixed;}
        .logs-table thead th{position:sticky;top:0;z-index:2;}
        .logs-table td, .logs-table th{white-space:normal;vertical-align:top;word-break:break-word;overflow-wrap:anywhere;}
        .logs-table th:nth-child(1), .logs-table td:nth-child(1),
        .logs-table th:nth-child(2), .logs-table td:nth-child(2),
        .logs-table th:nth-child(3), .logs-table td:nth-child(3),
        .logs-table th:nth-child(5), .logs-table td:nth-child(5){white-space:nowrap;overflow-wrap:normal;word-break:normal;}
        .logs-table td.props{white-space:normal;}
        .logs-table .props-pre{margin:0;white-space:pre-wrap;word-break:normal;overflow-wrap:break-word;}
        .logs-table .props-value{color:#fff;font-weight:600;}
    </style>
@endsection

@section('content')
    <div class="logs-page dir-page">
        <div class="card logs-card dir-panel">

            <div class="card-header d-flex justify-content-between align-items-center gap-2 flex-wrap">
                <h5 class="mb-0 text-primary">Activity log ({{ number_format($activities->total()) }})</h5>

                <div class="d-flex gap-2 align-items-center flex-wrap">
                    <form method="POST" action="{{ route('admin.activity.purge') }}" class="d-flex gap-2 align-items-center flex-wrap">
                        @csrf
                        <label for="purge_days" class="small text-muted mb-0">Delete older than</label>
                        <select name="days" id="purge_days" class="form-select form-select-sm bg-dark text-light border-secondary" style="width:auto">
                            @foreach($purgeDaysOptions as $daysOption)
                                <option value="{{ $daysOption }}" @selected((int) old('days', session('purge_days', 90)) === $daysOption)>
                                    {{ $daysOption }} days
                                </option>
                            @endforeach
                        </select>
                        <button type="button"
                                class="btn btn-sm btn-outline-danger"
                                data-bs-toggle="modal"
                                data-bs-target="#useConfirmDelete"
                                data-title="Delete old logs"
                                data-purge-button>
                            Delete old logs (0)
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
                            <option value="all">All object types</option>
                            @foreach($subjectTypes as $st)
                                <option value="{{ $st }}" @selected(request('subject_type','all')===$st)>{{ class_basename($st) }}</option>
                            @endforeach
                        </select>

                        <select name="causer_id" class="form-select form-select-sm bg-dark text-light border-secondary" style="width:auto; max-width:220px">
                            <option value="all">All users</option>
                            @foreach($causers as $u)
                                <option value="{{ $u->id }}" @selected((string)request('causer_id','all')===(string)$u->id)>{{ $u->selection_name }}</option>
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
                            <colgroup>
                                <col class="logs-col-date">
                                <col class="logs-col-user">
                                <col class="logs-col-type">
                                <col class="logs-col-object">
                                <col class="logs-col-event">
                                <col class="logs-col-old">
                                <col class="logs-col-new">
                            </colgroup>
                            <thead>
                            <tr class="text-muted small">
                                <th class="text-center">Date</th>
                                <th class="text-center">User</th>
                                <th class="text-center">Object type</th>
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
                                    $meta = (array) ($props['meta'] ?? []);

                                    $decodeLogCardRows = function ($value): array {
                                        if (is_string($value)) {
                                            $decoded = json_decode($value, true);
                                            return is_array($decoded) ? $decoded : [];
                                        }

                                        return is_array($value) ? $value : [];
                                    };

                                    $logCardRowsByField = [
                                        'component_data' => [],
                                        'component_data_out' => [],
                                    ];

                                    if ($a->subject_type === \App\Models\LogCard::class && $a->subject) {
                                        foreach (['component_data', 'component_data_out'] as $logCardField) {
                                            foreach ($decodeLogCardRows($a->subject->{$logCardField} ?? null) as $rowIndex => $row) {
                                                if (! is_array($row)) {
                                                    continue;
                                                }

                                                $rowName = trim((string) ($row['name'] ?? ''));
                                                $rowPartNumber = trim((string) ($row['part_number'] ?? ''));
                                                $parts = array_values(array_filter([$rowName, $rowPartNumber], fn ($value) => $value !== ''));
                                                $componentId = isset($row['component_id']) && is_numeric($row['component_id'])
                                                    ? (int) $row['component_id']
                                                    : null;
                                                $componentLabel = $componentId ? ($componentMap[$componentId] ?? null) : null;

                                                $logCardRowsByField[$logCardField][(string) $rowIndex] = $parts !== []
                                                    ? implode(' / ', $parts)
                                                    : $componentLabel;
                                            }
                                        }
                                    }

                                    $keyLabel = function (string $key) use ($logCardRowsByField): string {
                                        if (preg_match('/^(component_data|component_data_out)\.(\d+)\.(.+)$/', $key, $matches) === 1) {
                                            $field = $matches[1];
                                            $rowIndex = $matches[2];
                                            $tail = str_replace('_', ' ', $matches[3]);
                                            $rowBase = "row ".(((int) $rowIndex) + 1);
                                            $rowLabel = $logCardRowsByField[$field][$rowIndex] ?? null;

                                            return $rowLabel ? "{$rowBase} - {$rowLabel} {$tail}" : "{$rowBase} - {$tail}";
                                        }

                                        return match ($key) {
                                            'workorder_id' => 'workorder',
                                            'general_task_id' => 'general task',
                                            'task_id' => 'task',
                                            'user_id' => 'user',
                                            'manual_id', 'manuals_id' => 'manual',
                                            'component_id' => 'component',
                                            'order_component_id' => 'order component',
                                            'process_names_id' => 'process name',
                                            'process_name_id' => 'process name',
                                            'processes_id' => 'process',
                                            'tdrs_id' => 'tdr',
                                            'source_tdr_id' => 'source tdr',
                                            'source_tdr_process_id' => 'source tdr process',
                                            'codes_id' => 'code',
                                            'conditions_id' => 'condition',
                                            'condition_id' => 'condition',
                                            'necessaries_id' => 'necessary',
                                            'vendor_id' => 'vendor',
                                            'date_start_user_id' => 'sent date user',
                                            'date_finish_user_id' => 'back date user',
                                            'builders_id' => 'builder',
                                            'planes_id' => 'plane',
                                            'scopes_id' => 'scope',
                                            'unit_id' => 'unit',
                                            'instruction_id' => 'instruction',
                                            'customer_id' => 'customer',
                                            'customer_contact_id', 'contact_id' => 'contact',
                                            'customer_interaction_note_id' => 'marketing note',
                                            'customer_marketing_profile_id' => 'marketing profile',
                                            'company_type_id' => 'company type',
                                            'segment_id' => 'segment',
                                            'feature_key' => 'feature',
                                            'granted_by_user_id' => 'granted by',
                                            'plane_id' => 'aircraft',
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
                                        $workorderStdProcessMap,
                                        $workorderUnitInspectionMap,
                                        $codeMap,
                                        $conditionMap,
                                        $vendorMap,
                                        $necessaryMap,
                                        $builderMap,
                                        $planeMap,
                                        $scopeMap,
                                        $unitMap,
                                        $instructionMap,
                                        $customerMap,
                                        $customerContactMap,
                                        $customerNoteMap,
                                        $customerMarketingProfileMap,
                                        $marketingCompanyTypeMap,
                                        $marketingSegmentMap,
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

                                        if (($key === 'user_id' || $key === 'granted_by_user_id') && is_numeric($value)) {
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

                                        if (($key === 'process_names_id' || $key === 'process_name_id') && is_numeric($value)) {
                                            $id = (int)$value;
                                            return $processNameMap[$id] ?? "process_name id {$id}";
                                        }

                                        if ($key === 'processes_id' && is_numeric($value)) {
                                            $id = (int)$value;
                                            return $processMap[$id] ?? "process id {$id}";
                                        }

                                        if (($key === 'tdrs_id' || $key === 'source_tdr_id') && is_numeric($value)) {
                                            $id = (int)$value;
                                            return $tdrMap[$id] ?? "tdr id {$id}";
                                        }

                                        if ($key === 'source_tdr_process_id' && is_numeric($value)) {
                                            $id = (int)$value;
                                            return $tdrProcessMap[$id] ?? "tdr process id {$id}";
                                        }

                                        if ($key === 'codes_id' && is_numeric($value)) {
                                            $id = (int)$value;
                                            return $codeMap[$id] ?? "code id {$id}";
                                        }

                                        if (($key === 'conditions_id' || $key === 'condition_id') && is_numeric($value)) {
                                            $id = (int)$value;
                                            return $conditionMap[$id] ?? "condition id {$id}";
                                        }

                                        if ($key === 'vendor_id' && is_numeric($value)) {
                                            $id = (int)$value;
                                            return $vendorMap[$id] ?? "vendor id {$id}";
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

                                        if (($key === 'customer_contact_id' || $key === 'contact_id') && is_numeric($value)) {
                                            $id = (int)$value;
                                            return $customerContactMap[$id] ?? "contact id {$id}";
                                        }

                                        if ($key === 'customer_interaction_note_id' && is_numeric($value)) {
                                            $id = (int)$value;
                                            return $customerNoteMap[$id] ?? "marketing note id {$id}";
                                        }

                                        if ($key === 'customer_marketing_profile_id' && is_numeric($value)) {
                                            $id = (int)$value;
                                            return $customerMarketingProfileMap[$id] ?? "marketing profile id {$id}";
                                        }

                                        if ($key === 'company_type_id' && is_numeric($value)) {
                                            $id = (int)$value;
                                            return $marketingCompanyTypeMap[$id] ?? "company type id {$id}";
                                        }

                                        if ($key === 'segment_id' && is_numeric($value)) {
                                            $id = (int)$value;
                                            return $marketingSegmentMap[$id] ?? "segment id {$id}";
                                        }

                                        if ($key === 'plane_id' && is_numeric($value)) {
                                            $id = (int)$value;
                                            return $planeMap[$id] ?? "aircraft id {$id}";
                                        }

                                        if (($key === 'done_user_id' || $key === 'date_start_user_id' || $key === 'date_finish_user_id') && is_numeric($value)) {
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
                                            ->map(function ($v, $k) use ($keyLabel, $formatValue) {
                                                return e($keyLabel((string) $k)).': <span class="props-value">'.e($formatValue((string) $k, $v)).'</span>';
                                            })
                                            ->implode("\n");
                                    };

                                    $activitySourceLabel = match ((string) ($meta['source'] ?? '')) {
                                        'quality_assurance_log_card_form' => 'QA',
                                        'tdrs_show_log_card_inline' => 'TDRs',
                                        default => null,
                                    };

                                    $activitySideLabel = match ((string) ($meta['side'] ?? '')) {
                                        'left' => 'As received',
                                        'right' => 'As dispatched',
                                        default => null,
                                    };

                                    $activityContextLines = array_values(array_filter([
                                        $activitySourceLabel ? 'source: '.$activitySourceLabel : null,
                                        $activitySideLabel ? 'side: '.$activitySideLabel : null,
                                    ]));

                                    $tdrActivityFallback = function (array $old, array $new) use ($formatValue): ?string {
                                        $row = array_merge($old, $new);
                                        $parts = ['tdr'];

                                        foreach ([
                                            'workorder_id' => 'workorder',
                                            'component_id' => 'component',
                                            'order_component_id' => 'order component',
                                            'serial_number' => 'serial number',
                                            'assy_serial_number' => 'assy serial number',
                                            'codes_id' => 'code',
                                            'conditions_id' => 'condition',
                                            'necessaries_id' => 'necessary',
                                            'description' => 'description',
                                        ] as $key => $label) {
                                            if (! array_key_exists($key, $row) || $row[$key] === null || $row[$key] === '') {
                                                continue;
                                            }

                                            $value = $formatValue($key, $row[$key]);
                                            if ($value !== '' && $value !== 'null') {
                                                $parts[] = $label.': '.$value;
                                            }
                                        }

                                        return count($parts) > 1 ? implode('   ', $parts) : null;
                                    };

                                    $stdProcessActivityFallback = function (array $old, array $new) use ($formatValue): ?string {
                                        $row = array_merge($old, $new);
                                        $parts = ['std process'];

                                        foreach ([
                                            'std' => 'type',
                                            'manual_id' => 'manual',
                                            'ipl_num' => 'ipl',
                                            'part_number' => 'part number',
                                            'description' => 'description',
                                            'process' => 'process',
                                        ] as $key => $label) {
                                            if (! array_key_exists($key, $row) || $row[$key] === null || $row[$key] === '') {
                                                continue;
                                            }

                                            $value = $key === 'std'
                                                ? strtoupper((string) $row[$key])
                                                : $formatValue($key, $row[$key]);
                                            if ($value !== '' && $value !== 'null') {
                                                $parts[] = $label.': '.$value;
                                            }
                                        }

                                        return count($parts) > 1 ? implode('   ', $parts) : null;
                                    };

                                    $oldText = $old ? $renderProps($old) : '—';
                                    $newText = $new ? $renderProps($new) : '—';
                                    $eventText = (string) $a->event;
                                    $eventClass = match ($a->event) {
                                        'created' => 'bg-success',
                                        'updated' => 'bg-warning text-dark',
                                        'deleted' => 'bg-danger',
                                        default => 'bg-secondary',
                                    };

                                    $isBlankActivityValue = function ($value): bool {
                                        return $value === null || trim((string) $value) === '';
                                    };

                                    $firstEnteredRows = [];
                                    if (
                                        in_array($a->subject_type, [\App\Models\TdrProcess::class, \App\Models\WorkorderStdProcess::class], true)
                                        && $a->event === 'updated'
                                    ) {
                                        foreach ([
                                            'date_start',
                                            'date_finish',
                                            'date_promise',
                                            'repair_order',
                                            'vendor_id',
                                        ] as $firstEntryKey) {
                                            if (
                                                array_key_exists($firstEntryKey, $new)
                                                && $isBlankActivityValue($old[$firstEntryKey] ?? null)
                                                && ! $isBlankActivityValue($new[$firstEntryKey])
                                            ) {
                                                $firstEnteredRows[] = $keyLabel($firstEntryKey).': '.$formatValue($firstEntryKey, $new[$firstEntryKey]);
                                            }
                                        }
                                    }

                                    if ($firstEnteredRows !== []) {
                                        $eventText = 'first entered';
                                        $eventClass = 'bg-info text-dark';
                                        $oldText = 'previously empty';
                                        $newText = "first entered:\n".implode("\n", $firstEnteredRows);
                                    }

                                    if ($old === [] && $new === []) {
                                        $serviceProps = collect($props)
                                            ->except(['old', 'new', 'attributes', 'changes'])
                                            ->all();

                                        if ($serviceProps !== []) {
                                            $newText = $renderProps($serviceProps);
                                        }
                                    }

                                    $subjectId = is_numeric($a->subject_id) ? (int)$a->subject_id : null;
                                    $subjectName = class_basename($a->subject_type);
                                    $objectText = $subjectId ? "{$subjectName} #{$subjectId}" : $subjectName;
                                    $subject = $a->subject;

                                    if ($a->subject_type === \App\Models\Manual::class && $subjectId) {
                                        $fallback = $subject
                                            ? 'manual: '.trim((string) ($subject->number ?? '')).'   lib: '.trim((string) ($subject->lib ?? ''))
                                            : null;
                                        $objectText = $manualMap[$subjectId] ?? $fallback ?? $objectText;
                                    } elseif ($a->subject_type === \App\Models\LogCard::class && $subjectId) {
                                        $subjectWorkorderId = $subject->workorder_id ?? null;
                                        $newWorkorderId = $new['workorder_id'] ?? null;
                                        $oldWorkorderId = $old['workorder_id'] ?? null;
                                        $workorderSourceId = is_numeric($subjectWorkorderId)
                                            ? (int) $subjectWorkorderId
                                            : (is_numeric($newWorkorderId) ? (int) $newWorkorderId : (is_numeric($oldWorkorderId) ? (int) $oldWorkorderId : null));

                                        $label = $workorderSourceId ? ($workorderMap[$workorderSourceId] ?? null) : null;
                                        $objectText = $label ? "log card: WO #{$label}" : $objectText;
                                    } elseif ($a->subject_type === \App\Models\Component::class && $subjectId) {
                                        $fallback = $subject
                                            ? trim(((string) ($subject->part_number ?? '')).' '.((string) ($subject->name ?? '')))
                                            : null;
                                        $label = $componentMap[$subjectId] ?? $fallback;
                                        $objectText = filled($label) ? "component: {$label}" : $objectText;
                                    } elseif ($a->subject_type === \App\Models\Tdr::class && $subjectId) {
                                        $objectText = $tdrMap[$subjectId] ?? $tdrActivityFallback($old, $new) ?? $objectText;
                                    } elseif ($a->subject_type === \App\Models\StdProcess::class && $subjectId) {
                                        $objectText = $stdProcessMap[$subjectId] ?? $stdProcessActivityFallback($old, $new) ?? $objectText;
                                    } elseif ($a->subject_type === \App\Models\Workorder::class && $subjectId) {
                                        $label = $workorderMap[$subjectId] ?? ($subject->number ?? null);
                                        $objectText = $label ? "wo: {$label}" : $objectText;
                                    } elseif ($a->subject_type === \App\Models\User::class && $subjectId) {
                                        $label = $userMap[$subjectId] ?? ($subject->name ?? null);
                                        $objectText = $label ? "user: {$label}" : $objectText;
                                    } elseif ($a->subject_type === \App\Models\UserFeatureAccess::class) {
                                        $row = array_merge($old, $new);
                                        $feature = trim((string) ($row['feature_key'] ?? ($subject->feature_key ?? '')));
                                        $featureLabel = $feature !== '' ? ucfirst($feature) : null;
                                        $accessUserId = $row['user_id'] ?? ($subject->user_id ?? null);
                                        $accessUser = is_numeric($accessUserId) ? ($userMap[(int) $accessUserId] ?? "user id {$accessUserId}") : null;
                                        $parts = array_values(array_filter([$featureLabel, $accessUser], fn ($value) => filled($value)));
                                        $objectText = $parts !== [] ? 'access: '.implode(' / ', $parts) : $objectText;
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
                                    } elseif ($a->subject_type === \App\Models\CustomerContact::class && $subjectId) {
                                        $label = $customerContactMap[$subjectId] ?? null;
                                        $objectText = $label ? "marketing contact: {$label}" : $objectText;
                                    } elseif ($a->subject_type === \App\Models\CustomerInteractionNote::class && $subjectId) {
                                        $label = $customerNoteMap[$subjectId] ?? null;
                                        $objectText = $label ? "marketing note: {$label}" : $objectText;
                                    } elseif ($a->subject_type === \App\Models\CustomerMarketingProfile::class && $subjectId) {
                                        $label = $customerMarketingProfileMap[$subjectId] ?? null;
                                        $objectText = $label ? "marketing profile: {$label}" : $objectText;
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
                                    } elseif ($a->subject_type === \App\Models\WorkorderStdProcess::class && $subjectId) {
                                        $row = array_merge($old, $new);
                                        $parts = [
                                            isset($row['std_type']) ? 'STD '.strtoupper((string) $row['std_type']) : null,
                                            isset($row['workorder_id']) ? $formatValue('workorder_id', $row['workorder_id']) : null,
                                            isset($row['process_name_id']) ? $formatValue('process_name_id', $row['process_name_id']) : null,
                                            filled($row['repair_order'] ?? null) ? 'RO '.$row['repair_order'] : null,
                                            isset($row['vendor_id']) ? $formatValue('vendor_id', $row['vendor_id']) : null,
                                        ];
                                        $fallback = 'workorder std process: '.implode('   ', array_values(array_filter($parts, fn ($value) => filled($value))));
                                        $objectText = $workorderStdProcessMap[$subjectId] ?? $fallback ?? $objectText;
                                    } elseif ($a->subject_type === \App\Models\WorkorderUnitInspection::class && $subjectId) {
                                        $row = array_merge($old, $new);
                                        $parts = [
                                            isset($row['workorder_id']) ? $formatValue('workorder_id', $row['workorder_id']) : null,
                                            isset($row['condition_id']) ? $formatValue('condition_id', $row['condition_id']) : null,
                                            filled($row['notes'] ?? null) ? $row['notes'] : null,
                                        ];
                                        $fallback = 'workorder unit inspection: '.implode('   ', array_values(array_filter($parts, fn ($value) => filled($value))));
                                        $objectText = $workorderUnitInspectionMap[$subjectId] ?? $fallback ?? $objectText;
                                    }

                                    $workorderId = null;
                                    if (isset($new['workorder_id']) && is_numeric($new['workorder_id'])) {
                                        $workorderId = (int)$new['workorder_id'];
                                    } elseif (isset($old['workorder_id']) && is_numeric($old['workorder_id'])) {
                                        $workorderId = (int)$old['workorder_id'];
                                    } elseif (isset($a->subject) && isset($a->subject->workorder_id) && is_numeric($a->subject->workorder_id)) {
                                        $workorderId = (int)$a->subject->workorder_id;
                                    }

                                    if (
                                        $workorderId !== null
                                        && $workorderId > 0
                                        && $a->subject_type !== \App\Models\Workorder::class
                                        && $a->subject_type !== \App\Models\Tdr::class
                                        && $a->subject_type !== \App\Models\LogCard::class
                                    ) {
                                        $workorderNumber = $workorderMap[$workorderId] ?? null;
                                        $objectText .= $workorderNumber ? "   wo: {$workorderNumber}" : "   wo id: {$workorderId}";
                                    }

                                    $objectMetaText = $activityContextLines !== []
                                        ? implode("\n", $activityContextLines)
                                        : null;
                                @endphp
                                <tr>
                                    <td class="text-center small">{{ $a->created_at ? $a->created_at->format('d').'.'.\Illuminate\Support\Str::lower($a->created_at->format('M')).'.'.$a->created_at->format('Y H:i') : '—' }}</td>
                                    <td class="text-center small">{{ $a->causer?->selection_name ?? 'system' }}</td>
                                    <td class="text-center small text-info">{{ class_basename($a->subject_type) }}</td>
                                    <td class="props small">
                                        <pre class="props-pre">{{ $objectText }}@if($objectMetaText)
{{ $objectMetaText }}@endif</pre>
                                    </td>
                                    <td class="text-center">
                                    <span class="badge {{ $eventClass }}">
                                        {{ $eventText }}
                                    </span>
                                    </td>
                                    <td class="props small">
                                        <pre class="props-pre">{!! $oldText !!}</pre>
                                    </td>
                                    <td class="props small">
                                        <pre class="props-pre">{!! $newText !!}</pre>
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
            const logsTableWrap = document.querySelector('.logs-table-wrap');
            const logsTable = document.querySelector('.logs-table');
            const purgeCounts = @json($purgeCounts);
            const purgeDays = document.getElementById('purge_days');
            const purgeButton = document.querySelector('[data-purge-button]');

            if (purgeDays && purgeButton) {
                const formatCount = (value) => new Intl.NumberFormat('en-US').format(Number(value) || 0);
                const updatePurgeButton = () => {
                    const count = purgeCounts[purgeDays.value] || 0;
                    const formatted = formatCount(count);
                    purgeButton.textContent = `Delete old logs (${formatted})`;
                    purgeButton.setAttribute('data-title', `Delete ${formatted} old logs`);
                };

                purgeDays.addEventListener('change', updatePurgeButton);
                updatePurgeButton();
            }

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

            const setColumnWidth = (selector, width) => {
                const col = logsTable ? logsTable.querySelector(selector) : null;
                if (col) {
                    col.style.width = width;
                }
            };

            const measureContentWidth = (cell) => {
                const probe = document.createElement('div');
                const cellStyles = window.getComputedStyle(cell);

                probe.style.position = 'absolute';
                probe.style.visibility = 'hidden';
                probe.style.pointerEvents = 'none';
                probe.style.left = '-99999px';
                probe.style.top = '0';
                probe.style.width = 'max-content';
                probe.style.maxWidth = 'none';
                probe.style.whiteSpace = 'nowrap';
                probe.style.font = cellStyles.font;
                probe.style.fontFamily = cellStyles.fontFamily;
                probe.style.fontSize = cellStyles.fontSize;
                probe.style.fontWeight = cellStyles.fontWeight;
                probe.style.letterSpacing = cellStyles.letterSpacing;
                probe.style.textTransform = cellStyles.textTransform;
                probe.style.padding = cellStyles.padding;
                probe.style.border = cellStyles.border;
                probe.style.boxSizing = cellStyles.boxSizing;
                probe.textContent = cell.textContent || '';

                document.body.appendChild(probe);
                const width = Math.ceil(probe.getBoundingClientRect().width);
                probe.remove();

                return width;
            };

            const measureColumnWidth = (index) => {
                if (!logsTable) {
                    return 0;
                }

                const cells = logsTable.querySelectorAll(`thead th:nth-child(${index}), tbody td:nth-child(${index})`);
                let maxWidth = 0;

                cells.forEach((cell) => {
                    maxWidth = Math.max(maxWidth, measureContentWidth(cell));
                });

                return maxWidth + 8;
            };

            const applyLogsTableLayout = () => {
                if (!logsTableWrap || !logsTable) {
                    return;
                }

                const containerWidth = logsTableWrap.clientWidth;
                if (!containerWidth) {
                    return;
                }

                const dateWidth = measureColumnWidth(1);
                const userWidth = measureColumnWidth(2);
                const typeWidth = measureColumnWidth(3);
                const fixedWidth = dateWidth + userWidth + typeWidth;
                const remainingWidth = Math.max(containerWidth - fixedWidth, 480);

                setColumnWidth('.logs-col-date', `${dateWidth}px`);
                setColumnWidth('.logs-col-user', `${userWidth}px`);
                setColumnWidth('.logs-col-type', `${typeWidth}px`);
                setColumnWidth('.logs-col-object', `${Math.floor(remainingWidth * 0.20)}px`);
                setColumnWidth('.logs-col-event', `${Math.floor(remainingWidth * 0.08)}px`);
                setColumnWidth('.logs-col-old', `${Math.floor(remainingWidth * 0.36)}px`);
                setColumnWidth('.logs-col-new', `${Math.floor(remainingWidth * 0.36)}px`);
            };

            applyLogsTableLayout();
            window.addEventListener('resize', applyLogsTableLayout);
        });
    </script>
@endsection
