<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>In-Process Check Sheet — WO {{ $workorder->number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; color: #000; background: #e9edf1; }
        .toolbar, .provenance { max-width: 215.9mm; margin: 16px auto; padding: 0 12px; font: 13px Arial, sans-serif; }
        .toolbar { display: flex; gap: 16px; align-items: center; }
        .toolbar button { border: 1px solid #315d7c; background: #315d7c; color: white; padding: 9px 20px; border-radius: 4px; cursor: pointer; }
        /* Preserve Letter geometry on narrow screens; only this wrapper pans. */
        .form-viewport { width: 100%; overflow-x: auto; }
        .sheet { width: 215.9mm; min-height: 279.4mm; margin: 0 auto 20px; padding: 8mm 10.2mm 6mm; background: #fff; display: flex; flex-direction: column; font: 10pt "Times New Roman", Times, serif; }
        .form-body { flex: 0 0 auto; }
        table { width: 100%; table-layout: fixed; border-collapse: collapse; }
        .heading { height: 16.5mm; border: .75pt solid #000; }
        .brand { width: 25%; text-align: center; vertical-align: middle; }
        .brand img { width: 43mm; max-width: 95%; height: auto; filter: grayscale(1) brightness(0); }
        .heading-title { width: 44%; vertical-align: top; text-align: center; padding: 3mm 1mm 0; }
        h1 { margin: 0; white-space: nowrap; font-size: 13pt; font-weight: bold; }
        .wo { width: 31%; border-left: .75pt solid #000; text-align: center; vertical-align: top; padding: 2mm 1mm; font-size: 11pt; font-weight: bold; line-height: 1; }
        .wo strong { display: block; font-size: 13pt; padding-top: 3mm; }
        .meta { border: .75pt solid #000; border-top: 0; font-size: 11pt; font-weight: bold; }
        .meta tr { height: 8mm; }
        .meta th, .meta td { padding: .5mm; vertical-align: middle; }
        .meta tr + tr { border-top: .75pt solid #000; }
        .meta th { text-align: left; }
        .meta td { text-align: center; overflow-wrap: anywhere; }
        .meta .right-label { border-left: .75pt solid #000; }
        .meta .description-label { text-align: center; }
        .instruction { min-height: 15.4mm; display: flex; align-items: center; text-align: center; padding: 1.5mm .5mm; border: 1.2pt solid #000; border-top: 0; font-size: 11pt; line-height: 1.2; }
        .section-title { height: 6.8mm; border: .75pt solid #000; border-top: 0; text-align: center; font-size: 19pt; line-height: 1; }
        .task { border: .75pt solid #000; border-top: 0; break-inside: avoid; }
        .task-main { height: 27mm; }
        .task:nth-of-type(2) .task-main, .task:nth-of-type(3) .task-main { height: 22.5mm; }
        .task-main td { vertical-align: middle; }
        .stage { width: 9.1%; border-right: .75pt solid #000; text-align: center; font-size: 8pt; font-weight: bold; }
        .stage strong { display: block; font-size: 14pt; font-weight: normal; margin-top: 4mm; }
        .task-cell { width: 79%; padding: 2mm 6.5mm; }
        .task-text { white-space: pre-wrap; overflow-wrap: anywhere; font-size: 9pt; line-height: 1.2; }
        .stamp { width: 11.9%; padding: 1mm; border-left: .75pt solid #000; text-align: center; font-size: 7.5pt; }
        .second-stamp { border-top: .75pt solid #000; }
        .reference { height: 6.5mm; border-top: .75pt solid #000; font-size: 9pt; }
        .reference th { width: 30.5%; text-align: left; border-right: .75pt solid #000; padding: .5mm; }
        .reference td { padding: .5mm; white-space: pre-wrap; overflow-wrap: anywhere; }
        .legend { border: .75pt solid #000; margin-top: 2mm; padding: 0 .5mm .5mm; break-inside: avoid; }
        .legend h2 { margin: 0; text-align: center; font-size: 9pt; line-height: 1.3; }
        .legend-list { display: grid; grid-template-columns: 52% 48%; grid-auto-flow: column; grid-template-rows: repeat(4, auto); font-size: 9pt; line-height: 1.3; }
        .form-footer { margin-top: auto; padding: 4mm 9.5mm 0; display: flex; justify-content: space-between; font-size: 9.5pt; }
        .missing { margin-top: 24px; padding: 16px; border: 1px solid #b88b35; background: #fff8e8; }
        .provenance { color: #53616c; overflow-wrap: anywhere; }
        @media print {
            @page { size: letter portrait; margin: 0; }
            body { background: white; }
            .toolbar, .provenance { display: none; }
            .form-viewport { overflow: visible; width: auto; }
            .sheet { margin: 0; break-after: page; }
            .sheet:last-child { break-after: auto; }
            .heading, .meta, .instruction, .section-title { break-after: avoid; }
        }
    </style>
</head>
<body>
<nav class="toolbar" aria-label="Form actions">
    @if($available)<button type="button" onclick="window.print()">Print</button>@endif
</nav>
@php($pages = $available ? array_chunk($content['rows'], 5) : [[]])
<div class="form-viewport" role="region" aria-label="Printable check sheet" tabindex="0">
@foreach($pages as $pageIndex => $rows)
<main class="sheet">
    <div class="form-body">
        <table class="heading" aria-label="Form heading">
            <tr>
                <td class="brand"><img src="{{ asset('img/icons/AT_logo-rb.svg') }}" alt="AVIATECHNIK AEROSPACE"></td>
                <td class="heading-title"><h1>IN-PROCESS CHECK SHEET</h1></td>
                <td class="wo">WORK ORDER No.<strong>{{ $workorder->number }}</strong></td>
            </tr>
        </table>
        <table class="meta" aria-label="Workorder identity">
            <colgroup><col style="width:24%"><col style="width:28%"><col style="width:21%"><col style="width:27%"></colgroup>
            <tr>
                <th>Master Part Number:</th><td>{{ $workorder->unit?->part_number ?: '—' }}</td>
                <th class="right-label">Manufacturer:</th><td>{{ $manual?->builder?->name ?: '—' }}</td>
            </tr>
            <tr>
                <th class="description-label">Description:</th><td>{{ $workorder->unit?->name ?: $manual?->title ?: '—' }}</td>
                <th class="right-label">Library Man#:</th><td>{{ $manual?->lib ?: '—' }}</td>
            </tr>
        </table>
        @if($available)
            {{-- Escape source text first; only the fixed form emphasis introduces markup. --}}
            <div class="instruction"><span>{!! str_replace('IN-PROCESS STAGES', '<strong>IN-PROCESS STAGES</strong>', e($content['header']['instruction'])) !!}</span></div>
            <div class="section-title">In-Process Task</div>
            <div class="tasks">
            @foreach($rows as $row)
                <section class="task" data-source-cell="{{ $row['source_cell'] }}">
                    <table class="task-main" aria-label="In-process stage {{ $row['stage'] }}">
                        <colgroup><col style="width:9.1%"><col style="width:79%"><col style="width:11.9%"></colgroup>
                        <tr>
                            <td class="stage" rowspan="2">INPROCESS<br>STAGE<strong># {{ $row['stage'] }}</strong></td>
                            <td class="task-cell" rowspan="2"><div class="task-text">{!! preg_replace('/\b(must)\b/i', '<u>$1</u>', e($row['task'])) !!}</div></td>
                            <td class="stamp">{{ $row['stamp_labels'][0] ?? '' }}</td>
                        </tr>
                        <tr><td class="stamp second-stamp">{{ $row['stamp_labels'][1] ?? '' }}</td></tr>
                    </table>
                    <table class="reference" aria-label="Reference"><tr><th>{{ $row['reference_label'] }}</th><td>{{ $row['reference'] }}</td></tr></table>
                </section>
            @endforeach
            </div>
            <section class="legend">
                <h2>INPROCESS STAGE LEGEND</h2>
                <div class="legend-list">@foreach($content['legend'] as $label)<div>{{ $label }}</div>@endforeach</div>
            </section>
        @else
            <div class="missing" role="status">IN PROCESS CHECK SHEET has not been imported or reviewed for this manual. Ask an administrator to import the manual workbook. No default task list has been substituted.</div>
        @endif
    </div>
    <footer class="form-footer"><span>Form # 004</span><span>{{ $pageIndex + 1 }} of {{ count($pages) }}</span><span>Rev # 0, 15/Dec/2012</span></footer>
</main>
@endforeach
</div>
@if($available)
<aside class="provenance">Manual: {{ $manual?->number }} · Template: {{ $template->source_file }} · {{ $template->source_sheet }} · Imported @projectDate($template->updated_at)<br>Template data revision: {{ substr($template->content_sha256, 0, 12) }} · WO identity from AVIA; tasks from the manual workbook.</aside>
@endif
</body>
</html>
