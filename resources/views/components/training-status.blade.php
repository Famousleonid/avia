{{-- resources/views/admin/mains/partials/training-status.blade.php --}}

@props([
    'manualId' => null,
    'unit' => null,

    'ownerUser' => null,        // User
    'ownerTraining' => null,    // Training|null
    'ownerHistory' => null,     // Collection|null

    // auth user
    'myTraining' => null,       // Training|null
    'myHistory' => null,        // Collection|null
])

@php
    $auth = auth()->user();

    // ✅ НЕ через @role/@roles — иногда в компонентах не срабатывает как ожидаешь
    $isAdminManager = $auth && (
        $auth->roleIs('Admin') ||
        $auth->roleIs('Manager')
    );

    // HARD rule:
    // Admin|Manager => ONLY owner
    // Others => ONLY auth
    if ($isAdminManager) {
        $displayUser     = $ownerUser;
        $displayTraining = $ownerTraining;
        $displayHistory  = $ownerHistory ?? collect();
    } else {
        $displayUser     = $auth;
        $displayTraining = $myTraining;
        $displayHistory  = $myHistory ?? collect();
    }

    $displayHistory = $displayHistory ?? collect();

    // PLUS button:
    // показываем только для не-Admin|Manager (то есть "мой блок") + есть manualId
    $showPlus = (bool)$manualId && ! $isAdminManager;

    // Compute status
    $trainingDate = null;
    $monthsDiff = null;
    $isThisMonth = false;

    if ($displayTraining && $displayTraining->date_training) {
        $trainingDate = \Carbon\Carbon::parse($displayTraining->date_training);
        $monthsDiff = $trainingDate->diffInMonths(now());
        $isThisMonth = $trainingDate->isCurrentMonth();
    }

    // -----------------------------------------------------
    // Tippy content (history) with form_type shown after "—"
    // -----------------------------------------------------
    $historyHtml = '<div style="min-width:240px">';
    $historyHtml .= '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">'
        .'<div style="color:#adb5bd;font-size:12px;">Training history</div>'
        .'<div style="color:#0dcaf0;font-size:12px;font-weight:600;">'.e($displayUser?->name ?? '—').'</div>'
        .'</div>';

    if ($displayHistory->count() === 0) {
        $historyHtml .= '<div style="color:#dc3545">No records</div>';
    } else {
        $historyHtml .= '<ol style="margin:0;padding-left:16px;">';
        foreach ($displayHistory as $h) {
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
@endphp

<style>
    .training-status {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 6px 10px;
        white-space: nowrap;
    }
    .training-row {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .training-user { margin-bottom: 0; }
    .training-status-text {
        margin: 0;
        font-size: 13px;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .training-status button { flex-shrink: 0; }
</style>

<div class="training-status ms-4 text-center border rounded" data-tippy-content='{!! $historyHtmlAttr !!}'>
    <div class="training-row">
        <div class="training-user fw-semibold text-info small">
            Training: {{ $displayUser?->name ?? '—' }}
        </div>

        @if($showPlus)
            <button
                class="btn btn-outline-primary btn-sm mains-add-trainings-btn"
                data-manual-id="{{ $manualId }}"
                style="height:30px;width:30px;padding:0;display:flex;align-items:center;justify-content:center;"
                title="{{ __('Add training') }}">
                <i class="bi bi-plus-circle" style="font-size:14px;"></i>
            </button>
        @else
{{--            <span style="display:inline-block;width:30px;height:30px;"></span>--}}
        @endif
    </div>

    @if($trainingDate)
        @if($monthsDiff !== null && $monthsDiff <= 12)
            <div class="training-status-text" style="color: lawngreen;">
                @if($monthsDiff === 0 && $isThisMonth)
                    Last training this month
                @else
                    Last training {{ $monthsDiff }} month{{ $monthsDiff === 1 ? '' : 's' }} ago
                @endif
            </div>
        @else
            <div class="training-status-text text-success small">
                Last training {{ $monthsDiff }} months ago
            </div>
        @endif
    @else
        <div class="training-status-text text-danger small">
            There are no trainings for: <span class="text-muted">{{ $unit?->part_number }}</span>
        </div>
    @endif
</div>
