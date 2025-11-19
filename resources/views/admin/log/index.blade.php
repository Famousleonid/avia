@extends('admin.master')

@section('style')
    <style>
        .card-full-height {
            display: flex;
            flex-direction: column;
            height: calc(100vh - 70px); /* подправь при необходимости */
        }
        .card-full-height .card-body {
            flex: 1 1 auto;
            overflow-y: auto;
        }
        .logs-table th,
        .logs-table td {
            white-space: nowrap;
            vertical-align: top;
        }
        .logs-table td.changes-cell {
            white-space: normal;
        }
    </style>
@endsection

@section('content')
    <div class="card card-full-height shadow">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-primary">Workorders activity log</h5>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-striped table-hover mb-0 logs-table">
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

                            // номер воркдера: сначала из subject, если он есть,
                            // иначе из атрибутов / old
                            $subject   = $activity->subject;
                            $woNumber  = $subject->number
                                         ?? ($attributes['number'] ?? ($old['number'] ?? '—'));

                            // готовим список изменений ТОЛЬКО для update
                            $changes = [];

                            if ($activity->event === 'updated') {
                                $formatValue = function ($field, $value)
                                    use ($unitsMap, $customersMap, $instructionsMap, $usersMap) {

                                    if ($value === null || $value === '') {
                                        return '—';
                                    }

                                    switch ($field) {
                                        case 'unit_id':
                                            return $unitsMap[$value] ?? $value;
                                        case 'customer_id':
                                            return $customersMap[$value] ?? $value;
                                        case 'instruction_id':
                                            return $instructionsMap[$value] ?? $value;
                                        case 'user_id':
                                            return $usersMap[$value] ?? $value;
                                        default:
                                            return $value;
                                    }
                                };

                                foreach ($attributes as $field => $newValue) {
                                    $oldValue = $old[$field] ?? null;

                                    if ($oldValue === $newValue) {
                                        continue;
                                    }

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
                            <td class="text-center">
                                {{ $activity->created_at->format('d.m.Y H:i') }}
                            </td>
                            <td class="text-center">
                                {{ $activity->causer?->name ?? 'system' }}
                            </td>
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
                            <td class="text-center">
                                {{ $woNumber }}
                            </td>
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
                                    {{-- для create деталей не показываем --}}
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

        @if($activities instanceof \Illuminate\Pagination\LengthAwarePaginator)
            <div class="card-footer">
                {{ $activities->links() }}
            </div>
        @endif
    </div>
@endsection

