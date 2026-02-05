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

                // даты ИСКЛЮЧИТЕЛЬНО из activity_log
                $bStartRaw  = data_get($p, 'old.date_start');
                $aStartRaw  = data_get($p, 'attributes.date_start');
                $bFinishRaw = data_get($p, 'old.date_finish');
                $aFinishRaw = data_get($p, 'attributes.date_finish');

                // форматирование
                $fmt = fn($v) => $v ? \Carbon\Carbon::parse($v)->format('d.m.Y') : null;

                $bStart  = $fmt($bStartRaw);
                $aStart  = $fmt($aStartRaw);
                $bFinish = $fmt($bFinishRaw);
                $aFinish = $fmt($aFinishRaw);

                $dash = '<span class="text-danger">—</span>';

                $taskLine = trim(($taskGeneral ? $taskGeneral.' → ' : '').($taskName ?? ''));

                // это approve?
                $isApprove = strtolower($taskName ?? '') === 'approved';
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
