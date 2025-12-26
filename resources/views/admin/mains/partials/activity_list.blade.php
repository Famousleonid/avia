@if($logs->isEmpty())
    <div class="text-muted small">No activity yet.</div>
@else
    <div class="d-flex flex-column gap-3">

        @foreach($logs as $a)
            @php
                $p = $a->properties ?? collect();

                $who  = $a->causer?->name ?? 'system';
                $when = $a->created_at?->format('d-M-y H:i') ?? '';

                $event = $a->event ?: ($a->description ?: 'log');

                $badgeText = match($a->event) {
                    'created' => 'created',
                    'updated' => 'updated',
                    default   => $event,
                };

                $badgeClass = match($a->event) {
                    'created' => 'bg-success',
                    'updated' => 'bg-warning text-dark',
                    default   => 'bg-danger',
                };

                $taskGeneral = data_get($p, 'task.general');
                $taskName    = data_get($p, 'task.name');

               $bStart  = $bStart  ?? data_get($p, 'old.date_start');
               $bFinish = $bFinish ?? data_get($p, 'old.date_finish');
               $aStart  = $aStart  ?? data_get($p, 'attributes.date_start');
               $aFinish = $aFinish ?? data_get($p, 'attributes.date_finish');

                $mainId = data_get($p, 'main_id', $a->subject_id);

                $dash = '<span class="text-danger">—</span>';

                $taskLine = trim(($taskGeneral ? $taskGeneral.' → ' : '').($taskName ?? ''));
            @endphp

            <div class="border border-secondary rounded p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="small">
                        <span class="text-info fw-semibold">{{ $when }}</span>
                        <span class="ms-2">{{ $who }}</span>
                        <span class="ms-2 text-muted">{{ $badgeText }}</span>
                    </div>
                    <span class="badge {{ $badgeClass }}">
                        {{ $badgeText }}
                    </span>
                </div>

                <ul class="mb-0 ps-3 small">
                    <li>
                        <strong>Task: </strong>
                        {!! $taskLine ? '<span class="text-success">'.$taskLine.'</span>' : $dash !!}
                    </li>

                    @if($a->event === 'updated')
                        <li>
                            <strong>Start: </strong>
                            {!! ($bStart || $aStart)
                                ? '<span class="text-muted">'.($bStart ?? ' —').'</span> → <span class="text-success fw-semibold">'.($aStart ?? '—').'</span>'
                                : $dash
                            !!}
                        </li>
                        <li>
                            <strong>Finish: </strong>
                            {!! ($bFinish || $aFinish)
                                ? '<span class="text-muted">'.($bFinish ?? ' —').'</span> → <span class="text-success fw-semibold">'.($aFinish ?? '—').'</span>'
                                : $dash
                            !!}
                        </li>
                    @else
                        <li>
                            <strong>Start: </strong>
                            {!! ($aStart ?? $bStart) ? '<span class="text-success">'.(($aStart ?? $bStart)).'</span>' : $dash !!}
                        </li>
                        <li>
                            <strong>Finish: </strong>
                            {!! ($aFinish ?? $bFinish) ? '<span class="text-success">'.(($aFinish ?? $bFinish)).'</span>' : $dash !!}
                        </li>
                    @endif

                </ul>
            </div>
        @endforeach

    </div>
@endif
