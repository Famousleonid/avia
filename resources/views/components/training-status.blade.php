{{-- resources/views/admin/mains/partials/training-status.blade.php --}}

@props([
    'trainingUser',   // User model
    'training',       // Training model|null (latest)
    'manualId',       // int|null
    'isOwner' => false, // bool: true = (me)
    'history' => null,  // Collection of Training (date_training), optional
])

@php
    $trainingDate = null;
    $monthsDiff = null;
    $isThisMonth = false;

    if ($training && $training->date_training) {
        $trainingDate = \Carbon\Carbon::parse($training->date_training);
        $monthsDiff = $trainingDate->diffInMonths(now());
        $isThisMonth = $trainingDate->isCurrentMonth();
    }

    $history = $history ?? collect();

    // Build Tippy HTML (history)
    $historyHtml = '<div style="min-width:240px">';
    $historyHtml .= '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">'
        .'<div style="color:#adb5bd;font-size:12px;">Training history</div>'
        .'<div style="color:#0dcaf0;font-size:12px;font-weight:600;">'.e($trainingUser?->name ?? '—').'</div>'
        .'</div>';

    if ($history->count() === 0) {
        $historyHtml .= '<div style="color:#dc3545">No records</div>';
    } else {
        $historyHtml .= '<ol style="margin:0;padding-left:16px;">';
        foreach ($history as $h) {
    $d = $h->date_training
        ? \Carbon\Carbon::parse($h->date_training)->format('M d, Y')
        : '—';

    $ft = $h->form_type ?? '—';

    $historyHtml .= '<li style="margin:0 0 2px 0;">'
        . e($d) . ' <span style="color:#adb5bd">—</span> '
        . '<span style="color:#ffc107;font-weight:600">' . e($ft) . '</span>'
        . '</li>';
}
        $historyHtml .= '</ol>';
    }
    $historyHtml .= '</div>';
    $historyHtmlAttr = str_replace("'", '&#039;', $historyHtml);
    // When to show action buttons (only for the logged-in user who owns this training block)
    $canAct = auth()->check() && $trainingUser && (auth()->id() === $trainingUser->id);
@endphp

<div class="training-status ms-4 text-center border rounded" data-tippy-content='{!! $historyHtmlAttr !!}'>

    {{-- Header row: user left, action button right --}}
    <div class="training-row">
        <div class="training-user fw-semibold text-info small">
            Training: {{ $trainingUser?->name ?? '—' }}
            @if($isOwner)
                <span class="text-muted">(me)</span>
            @endif
        </div>

        @if($manualId && $canAct)
            @if(!$trainingDate)
                <button
                    class="btn btn-outline-primary btn-sm"
                    style="height:30px;width:30px;padding:0;display:flex;align-items:center;justify-content:center;"
                    title="{{ __('Create Trainings') }}"
                    onclick="createTrainings({{ $manualId }})">
                    <i class="bi bi-plus-circle" style="font-size:14px;"></i>
                </button>
            @elseif($monthsDiff !== null && $monthsDiff >= 6)
                <button
                    class="btn btn-outline-success btn-sm"
                    style="height:30px;width:30px;padding:0;display:flex;align-items:center;justify-content:center;"
                    title="{{ __('Update to Today') }}"
                    onclick="updateTrainingToToday({{ $manualId }}, '{{ $training->date_training }}')">
                    <i class="bi bi-calendar-check" style="font-size:14px;"></i>
                </button>
            @else
                {{-- чтобы оба блоки были одинаковые по виду: "пустое место" под кнопку --}}
                <span style="display:inline-block;width:30px;height:30px;"></span>
            @endif
        @else
            {{-- если кнопки не должно быть (WO user) — тоже оставим место, чтобы карточки были одинаковые --}}
            <span style="display:inline-block;width:30px;height:30px;"></span>
        @endif
    </div>

    {{-- Body --}}
    @if($trainingDate)

        @if($monthsDiff <= 12)
            <div class="training-status-text" style="color: lawngreen;">
                @if($monthsDiff === 0 && $isThisMonth)
                    Last training this month · {{ $trainingDate->format('M d, Y') }}
                @else
                    Last training {{ $monthsDiff }} month{{ $monthsDiff === 1 ? '' : 's' }} ago · {{ $trainingDate->format('M d, Y') }}
                @endif
            </div>
        @else
            <div class="training-status-text text-success small">
                Last training {{ $monthsDiff }} months ago · {{ $trainingDate->format('M d, Y') }}
            </div>
        @endif
    @else
        <div class="training-status-text text-danger small">
            There are no trainings for this unit.
        </div>
    @endif
</div>
