{{-- resources/views/admin/mains/partials/training-status.blade.php --}}

@props([
    'trainingUser',   // User model (for whom this block is rendered)
    'training',       // Training model|null (latest)
    'manualId',       // int|null
    'isOwner' => false, // bool: true = this block is for current logged-in user
    'history' => null,  // Collection of Training (date_training, form_type), optional
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

    // -----------------------------------------------------
    // Tippy content (history) with form_type shown after "—"
    // -----------------------------------------------------
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

    // safe for single-quoted attribute
    $historyHtmlAttr = str_replace("'", '&#039;', $historyHtml);
@endphp

{{-- =========================================================
     SHOW RULE:
     - Owner (current user) => always show
     - Workorder->user (someone else) => show ONLY Admin|Manager
   ========================================================= --}}
@if($isOwner)

    <div class="training-status ms-4 text-center border rounded" data-tippy-content='{!! $historyHtmlAttr !!}'>
        {{-- Header row --}}
        <div class="training-row">
            <div class="training-user fw-semibold text-info small">
                Training: {{ $trainingUser?->name ?? '—' }}
                <span class="text-muted">(me)</span>
            </div>

            {{-- Owner: PLUS button ALWAYS (if manualId exists) --}}
            @if($manualId)
                <button
                    class="btn btn-outline-primary btn-sm mains-add-trainings-btn"
                    data-manual-id="{{ $manualId }}"
                    style="height:30px;width:30px;padding:0;display:flex;align-items:center;justify-content:center;"
                    title="{{ __('Add training') }}">
                    <i class="bi bi-plus-circle" style="font-size:14px;"></i>
              </button>
          @else
              <span style="display:inline-block;width:30px;height:30px;"></span>
          @endif
      </div>

      {{-- Body --}}
        @if($trainingDate)
            @if($monthsDiff !== null && $monthsDiff <= 12)
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

@else

    {{-- Not owner: render ONLY for Admin|Manager --}}
    @roles("Admin|Manager|Team Leader")
    <div class="training-status ms-4 text-center border rounded" data-tippy-content='{!! $historyHtmlAttr !!}'>
        {{-- Header row --}}
        <div class="training-row">
            <div class="training-user fw-semibold text-info small">
                Training: {{ $trainingUser?->name ?? '—' }}
            </div>

            {{-- No action buttons for workorder->user (Admin/Manager view-only) --}}
            <span style="display:inline-block;width:30px;height:30px;"></span>
        </div>

        {{-- Body --}}
        @if($trainingDate)
            @if($monthsDiff !== null && $monthsDiff <= 12)
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
    @endroles

@endif
