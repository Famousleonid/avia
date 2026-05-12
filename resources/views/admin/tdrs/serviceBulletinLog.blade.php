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
    $requirementLabels = [
        \App\Models\ManualServiceBulletin::REQUIREMENT_OPTIONAL => 'Optional',
        \App\Models\ManualServiceBulletin::REQUIREMENT_RECOMMENDED => 'Recommended',
        \App\Models\ManualServiceBulletin::REQUIREMENT_MANDATORY => 'Mandatory',
    ];
@endphp

<main class="sb-page">
    <form class="sb-log-form" method="post" action="{{ route('tdrs.serviceBulletinLog.update', ['workorder' => $current_wo->id]) }}">
        @csrf

        <header class="sb-header">
            <div class="sb-logo-cell">
                <img class="sb-logo" src="{{ asset('img/icons/AT_logo-rb.svg') }}" alt="Aviatechnik">
            </div>
            <div class="sb-title-cell">
                <h1>Service Bulletin Log</h1>
            </div>
            <div class="sb-action-cell">
                <button class="sb-print" type="button" onclick="window.print()">Print</button>
                <button class="sb-save" type="submit">Save</button>
            </div>
        </header>

        @if(session('success'))
            <div class="sb-message">{{ session('success') }}</div>
        @endif

        <section class="sb-meta" aria-label="Work order information">
            <div class="sb-meta-label">Work Order No.</div>
            <div class="sb-meta-value">{{ $current_wo->number }}</div>
            <div class="sb-meta-label">Component Part No.</div>
            <div class="sb-meta-value">{{ $current_wo->unit?->part_number ?? 'N/A' }}</div>
            <div class="sb-meta-label">Component Description</div>
            <div class="sb-meta-value">{{ $current_wo->description ?: ($current_wo->unit?->description ?? 'N/A') }}</div>
            <div class="sb-meta-label">Manual</div>
            <div class="sb-meta-value">{{ $manual?->number ?? 'N/A' }}</div>
        </section>

        @if(! $manual)
            <p class="sb-empty">This work order does not have a manual assigned through its unit.</p>
        @elseif($serviceBulletins->isEmpty())
            <p class="sb-empty">No Service Bulletin rows have been created for manual {{ $manual->number }}.</p>
        @else
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
                    @foreach($serviceBulletins as $bulletin)
                        @php
                            $log = $logsByBulletin->get($bulletin->id);
                            $currentStatus = old("rows.{$bulletin->id}.status", $log?->status);
                            $requirement = $bulletin->default_requirement;
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
                                        <span>STAMP</span>
                                    </label>
                                </td>
                            @endforeach
                            <td class="sb-mark-cell">{{ $requirement === \App\Models\ManualServiceBulletin::REQUIREMENT_OPTIONAL ? 'X' : '' }}</td>
                            <td class="sb-mark-cell">{{ $requirement === \App\Models\ManualServiceBulletin::REQUIREMENT_RECOMMENDED ? 'X' : '' }}</td>
                            <td class="sb-mark-cell">{{ $requirement === \App\Models\ManualServiceBulletin::REQUIREMENT_MANDATORY ? 'X' : '' }}</td>
                        </tr>
                        <tr class="sb-notes-row">
                            <td colspan="12">
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
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </form>
</main>
</body>
</html>
