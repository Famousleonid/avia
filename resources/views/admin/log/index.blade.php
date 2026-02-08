@extends('admin.master')

@section('style')
    <style>
        /* Важно: чтобы внутренние блоки могли сжиматься и скроллиться */
        .logs-page {
            height: calc(100vh - 70px); /* подгони под свою шапку админки */
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        .logs-card {
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            min-height: 0; /* критично для overflow */
        }

        .logs-card .card-body {
            flex: 1 1 auto;
            min-height: 0; /* критично */
            padding: 0;
        }

        /* Вот тут скролл только таблицы */
        .logs-table-wrap {
            height: 100%;
            overflow: auto;
        }

        .logs-table th,
        .logs-table td {
            white-space: nowrap;
            vertical-align: top;
        }

        .logs-table td.changes-cell {
            white-space: normal;
            min-width: 420px;
        }

        /* чтобы заголовок таблицы был "липкий" внутри скролла */
        .logs-table thead th {
            position: sticky;
            top: 0;
            z-index: 2;
        }

        /* компактнее */
        .logs-toolbar .form-control {
            height: 34px;
            font-size: 0.9rem;
        }
    </style>
@endsection

@section('content')
    <div class="logs-page dir-page">

        <div class="card logs-card dir-panel">
            <div class="card-header d-flex justify-content-between align-items-center logs-toolbar gap-2">
                <h5 class="mb-0 text-primary">Workorders activity log</h5>

                {{-- Поиск (серверный) --}}
                <form method="GET" action="{{ url()->current() }}" class="d-flex gap-2 align-items-center">
                    <input
                        type="text"
                        name="q"
                        value="{{ request('q') }}"
                        class="form-control"
                        placeholder="Search (WO, user, event, changes...)"
                        autocomplete="off"
                    >
                    <button class="btn btn-sm btn-outline-info">Search</button>

                    @if(request('q'))
                        <a href="{{ url()->current() }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                    @endif
                </form>
            </div>

            <div class="card-body">
                <div class="logs-table-wrap">
                    <div class="table-responsive">
                        <table class="table table-sm  table-hover mb-0 logs-table dir-table">
                            <thead class="table-light">
                            <tr>
                                <th class="text-center">Дата</th>
                                <th class="text-center">Пользователь</th>
                                <th class="text-center">Событие</th>
                                <th class="text-center">WO №</th>
                                <th class="text-start">Изменения</th>
                            </tr>
                            </thead>

                            <tbody>
                            @forelse($activities as $activity)
                                @php
                                    $props      = $activity->properties ?? collect();
                                    $attributes = $props['attributes'] ?? [];
                                    $old        = $props['old'] ?? [];

                                    $subject   = $activity->subject;
                                    $woNumber  = $subject->number
                                                ?? ($attributes['number'] ?? ($old['number'] ?? '—'));

                                    $changes = [];

                                    if ($activity->event === 'updated') {
                                        $formatValue = function ($field, $value)
                                            use ($unitsMap, $customersMap, $instructionsMap, $usersMap) {

                                            if ($value === null || $value === '') {
                                                return '—';
                                            }

                                            return match ($field) {
                                                'unit_id' => $unitsMap[$value] ?? $value,
                                                'customer_id' => $customersMap[$value] ?? $value,
                                                'instruction_id' => $instructionsMap[$value] ?? $value,
                                                'user_id' => $usersMap[$value] ?? $value,
                                                default => $value,
                                            };
                                        };

                                        foreach ($attributes as $field => $newValue) {
                                            $oldValue = $old[$field] ?? null;
                                            if ($oldValue === $newValue) continue;

                                            $label = $fieldLabels[$field] ?? $field;

                                            $changes[] = [
                                                'label' => $label,
                                                'old'   => $formatValue($field, $oldValue),
                                                'new'   => $formatValue($field, $newValue),
                                            ];
                                        }
                                    }
                                @endphp

                                <tr>
                                    <td class="text-center">{{ $activity->created_at->format('d.m.Y H:i') }}</td>
                                    <td class="text-center">{{ $activity->causer?->name ?? 'system' }}</td>
                                    <td class="text-center">
                                        <span class="badge
                                            @if($activity->event === 'created') bg-success
                                            @elseif($activity->event === 'updated') bg-warning text-dark
                                            @elseif($activity->event === 'deleted') bg-danger
                                            @else bg-secondary
                                            @endif">
                                            {{ ucfirst($activity->event) }}
                                        </span>
                                    </td>
                                    <td class="text-center">{{ $woNumber }}</td>

                                    <td class="changes-cell">
                                        @if($activity->event === 'updated' && count($changes))
                                            <ul class="mb-0 small">
                                                @foreach($changes as $change)
                                                    <li>
                                                        <strong>{{ $change['label'] }}:</strong>
                                                        <span class="text-muted">{{ $change['old'] }}</span>
                                                        &rarr;
                                                        <span class="text-primary">{{ $change['new'] }}</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @elseif($activity->event === 'created')
                                            <span class="text-muted small">Создан</span>
                                        @elseif($activity->event === 'deleted')
                                            <span class="text-muted small">Удалён</span>
                                        @else
                                            <span class="text-muted small">Без деталей</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">
                                        Логи по workorders пока отсутствуют.
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if($activities instanceof \Illuminate\Pagination\LengthAwarePaginator)
                <div class="card-footer py-2">
                    {{-- ВАЖНО: bootstrap пагинация без гигантских SVG --}}
                    {{ $activities->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>

    </div>
@endsection
