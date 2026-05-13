<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Service Bulletin Log - {{ $current_wo->number }}</title>
    <link rel="stylesheet" href="{{ asset('css/forms/service-bulletin-log.css') }}">
</head>
<body>
@php
    $firstPageRows = 10;
    $rowsPerPage = 9;
    $bulletinPages = collect();
    if ($serviceBulletins->isNotEmpty()) {
        $bulletinPages->push($serviceBulletins->take($firstPageRows)->values());
        $serviceBulletins->slice($firstPageRows)->chunk($rowsPerPage)->each(function ($chunk) use ($bulletinPages) {
            $bulletinPages->push($chunk->values());
        });
    }
    $totalPages = max(1, $bulletinPages->count());
@endphp

<main class="sb-page">
    <form class="sb-log-form" method="post" action="{{ route('tdrs.serviceBulletinLog.update', ['workorder' => $current_wo->id]) }}">
        @csrf
        <input type="hidden" name="clear_status_bulletin_id" value="">

        <div class="sb-screen-actions">
            <button class="sb-print" type="button" onclick="window.print()">Print</button>
            <button class="sb-save" type="submit">
                <span class="sb-save-spinner" aria-hidden="true"></span>
                <span class="sb-save-text">Save</span>
            </button>
        </div>

        @if(! $manual)
            <p class="sb-empty">This work order does not have a manual assigned through its unit.</p>
        @elseif($serviceBulletins->isEmpty())
            <p class="sb-empty">No Service Bulletin rows have been created for manual {{ $manual->number }}.</p>
        @else
            @foreach($bulletinPages as $pageIndex => $pageRows)
                <section class="sb-form-page">
                    <header class="sb-page-header">
                        <div class="sb-logo-cell">
                            <img class="sb-logo" src="{{ asset('img/icons/AT_logo-rb.svg') }}" alt="Aviatechnik">
                        </div>
                        <div class="sb-title-cell">
                            <h1>Service Bulletin Log</h1>
                            <div class="sb-meta-line"><span>Work Order No.:</span><strong>W{{ $current_wo->number }}</strong></div>
                            <div class="sb-meta-line"><span>Component Part No.:</span><strong>{{ $current_wo->unit?->part_number ?? 'N/A' }}</strong></div>
                            <div class="sb-meta-line"><span>Component Description:</span><strong>{{ $current_wo->description ?: ($current_wo->unit?->description ?? 'N/A') }}</strong></div>
                        </div>
                    </header>

                    <div class="sb-table-wrap">
                        <table class="sb-table">
                            <colgroup>
                                <col class="sb-col-year">
                                <col class="sb-col-ac">
                                <col class="sb-col-oem">
                                <col class="sb-col-awd">
                                <col class="sb-col-ident">
                                <col class="sb-col-desc">
                                <col class="sb-col-status">
                                <col class="sb-col-status">
                                <col class="sb-col-status">
                                <col class="sb-col-req">
                                <col class="sb-col-req">
                                <col class="sb-col-req">
                            </colgroup>
                            <thead>
                            <tr>
                                <th>Year Introduced</th>
                                <th>A/C MFG Service Bulletin No.</th>
                                <th>OEM Service Bulletin No.</th>
                                <th>A.W.D. No.</th>
                                <th>Identification Method</th>
                                <th>Description</th>
                                @foreach($statusOptions as $label)
                                    <th>{{ $label }}</th>
                                @endforeach
                                <th>Optional</th>
                                <th>Recommended</th>
                                <th>Mandatory</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($pageRows as $bulletin)
                                @php
                                    $log = $logsByBulletin->get($bulletin->id);
                                    $currentStatus = old("rows.{$bulletin->id}.status", $log?->status);
                                    $requirement = $bulletin->default_requirement;
                                    $stampNumber = trim((string) ($log?->stampUser?->stamp ?? ''));
                                    $stampNumber = $stampNumber !== '' ? $stampNumber : ($log?->stamp_user_id ? (string) $log->stamp_user_id : '');
                                @endphp
                                <tr>
                                    <td>{{ $bulletin->year_introduced }}</td>
                                    <td>{{ $bulletin->ac_mfg_service_bulletin_no }}</td>
                                    <td>{{ $bulletin->oem_service_bulletin_no }}</td>
                                    <td>{{ $bulletin->awd_no ?: 'N/A' }}</td>
                                    <td>{{ $bulletin->identification_method }}</td>
                                    <td class="sb-description-cell">{{ $bulletin->description }}</td>
                                    @foreach($statusOptions as $status => $label)
                                        <td class="sb-status-cell">
                                            <label class="sb-stamp-option">
                                                <input type="radio" name="rows[{{ $bulletin->id }}][status]" value="{{ $status }}" @checked($currentStatus === $status)>
                                                <span class="sb-screen-stamp">STAMP</span>
                                                <span class="sb-print-stamp {{ $currentStatus === null ? 'is-na' : ($currentStatus === $status && $stampNumber !== '' ? 'is-selected' : 'is-placeholder') }}">{{ $currentStatus === null ? 'N/A' : ($currentStatus === $status && $stampNumber !== '' ? $stampNumber : 'STAMP') }}</span>
                                            </label>
                                        </td>
                                    @endforeach
                                    <td class="sb-mark-cell">{{ $requirement === \App\Models\ManualServiceBulletin::REQUIREMENT_OPTIONAL ? 'X' : '' }}</td>
                                    <td class="sb-mark-cell">{{ $requirement === \App\Models\ManualServiceBulletin::REQUIREMENT_RECOMMENDED ? 'X' : '' }}</td>
                                    <td class="sb-mark-cell">{{ $requirement === \App\Models\ManualServiceBulletin::REQUIREMENT_MANDATORY ? 'X' : '' }}</td>
                                </tr>
                                <tr class="sb-notes-row">
                                    <td colspan="12">
                                        <div class="sb-notes-strip">
                                            <label>
                                                <span>Notes</span>
                                                <input type="text" name="rows[{{ $bulletin->id }}][notes]" value="{{ old("rows.{$bulletin->id}.notes", $log?->notes) }}">
                                            </label>
                                            @if($log?->stampUser || $log?->stamped_at)
                                                <span class="sb-stamp-meta">
                                                    Last stamp:
                                                    {{ $log?->stampUser?->name ?? 'Unknown user' }}
                                                    @if($log?->stamped_at)
                                                        on {{ $log->stamped_at->format('d/M/Y H:i') }}
                                                    @endif
                                                </span>
                                            @endif
                                            <button class="sb-clear-status" type="button" data-bulletin-id="{{ $bulletin->id }}">
                                                <span class="sb-clear-spinner" aria-hidden="true"></span>
                                                <span class="sb-clear-text">Clear status</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    <footer class="sb-page-footer">
                        <div>Form # 007</div>
                        <div>{{ $pageIndex + 1 }} of {{ $totalPages }}</div>
                        <div>Rev # 0, 15/Feb/2012</div>
                    </footer>
                </section>
            @endforeach
        @endif
    </form>
</main>
<script>
    (function () {
        var form = document.querySelector('.sb-log-form');
        var saveButton = document.querySelector('.sb-save');
        if (!form || !saveButton) return;

        function markDirty() {
            if (saveButton.classList.contains('is-saving')) return;
            saveButton.classList.add('is-dirty');
        }

        form.addEventListener('change', function (event) {
            if (event.target.matches('input[type="radio"], input[type="text"]')) {
                markDirty();
            }
        });

        form.addEventListener('input', function (event) {
            if (event.target.matches('input[type="text"]')) {
                markDirty();
            }
        });

        form.addEventListener('click', function (event) {
            var clearButton = event.target.closest('.sb-clear-status');
            if (!clearButton) return;

            var bulletinId = clearButton.getAttribute('data-bulletin-id');
            if (!bulletinId) return;

            form.querySelectorAll('input[type="radio"][name="rows[' + bulletinId + '][status]"]').forEach(function (radio) {
                radio.checked = false;
            });

            var clearInput = form.querySelector('input[name="clear_status_bulletin_id"]');
            if (clearInput) clearInput.value = bulletinId;

            clearButton.classList.add('is-saving');
            clearButton.disabled = true;
            clearButton.querySelectorAll('.sb-clear-text').forEach(function (text) {
                text.textContent = 'Clearing';
            });

            form.submit();
        });

        form.addEventListener('submit', function () {
            saveButton.classList.remove('is-dirty');
            saveButton.classList.add('is-saving');
            saveButton.disabled = true;
            var text = saveButton.querySelector('.sb-save-text');
            if (text) text.textContent = 'Saving';
        });
    })();
</script>
</body>
</html>
