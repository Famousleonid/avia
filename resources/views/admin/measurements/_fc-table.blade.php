<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>F&amp;C / Measurements — WO W{{ $workorder->number ?? $workorder->id }}</title>
<style>
*, *::before, *::after { box-sizing: border-box; }
body { margin: 0; font-family: Arial, sans-serif; font-size: 12px; background: #fff; color: #000; }

.page-wrap { display: flex; height: 100vh; overflow: hidden; }
.sidebar    { width: 200px; min-width: 200px; border-right: 1px solid #ccc;
              display: flex; flex-direction: column; overflow: hidden; background: #f8f8f8; }
.sidebar-hdr  { padding: 8px 10px 6px; border-bottom: 1px solid #ccc; font-weight: 700; font-size: 11px; color: #555; }
.sidebar-body { flex: 1; overflow-y: auto; padding: 8px 10px; }
.content    { flex: 1; overflow-y: auto; padding: 16px 20px; }

.type-btns { display: flex; gap: 4px; margin-bottom: 10px; }
.type-btn  { flex: 1; padding: 3px 0; font-size: 11px; border: 1px solid #999;
             background: #fff; cursor: pointer; border-radius: 3px; }
.type-btn.active { background: #0d6efd; color: #fff; border-color: #0d6efd; }

.sel-actions { display: flex; gap: 6px; margin-bottom: 6px; }
.sel-btn { font-size: 10px; padding: 2px 8px; border: 1px solid #aaa;
           background: #fff; cursor: pointer; border-radius: 3px; }
.sel-btn:hover { background: #e9ecef; }
.ref-item { display: flex; align-items: center; gap: 5px; padding: 2px 0;
            font-size: 11px; border-bottom: 1px solid #eee; }
.ref-item label { cursor: pointer; flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ref-type-tag { font-size: 9px; padding: 0 4px; border-radius: 2px; flex-shrink: 0;
                background: #dee2e6; color: #555; }
.ref-type-tag.fc    { background: #cff4fc; color: #055160; }
.ref-type-tag.extra { background: #d1e7dd; color: #0a3622; }

.action-bar { display: flex; gap: 8px; margin-bottom: 14px; }
.btn-print  { padding: 5px 16px; font-size: 12px; cursor: pointer;
              background: #0d6efd; color: #fff; border: none; border-radius: 4px; }
.btn-close  { padding: 5px 14px; font-size: 12px; cursor: pointer;
              background: #fff; border: 1px solid #aaa; border-radius: 4px; }

.doc-title { font-size: 15px; font-weight: 700; margin-bottom: 2px; }
.doc-meta  { font-size: 11px; color: #555; margin-bottom: 14px; }

table  { border-collapse: collapse; width: 100%; font-size: 11px; white-space: nowrap; }
th, td { border: 1px solid #aaa; padding: 2px 5px; }
thead th { background: #e9ecef; text-align: center; vertical-align: middle; line-height: 1.3; }
td.r { text-align: right; }
td.c { text-align: center; }
td.na { color: #bbb; text-align: center; background: #fafafa; }
.neg      { color: #dc3545; }
.pass     { background: #d1e7dd; color: #0a3622; padding: 1px 5px; border-radius: 3px; font-weight: 700; font-size: 10px; }
.fail     { background: #f8d7da; color: #58151c; padding: 1px 5px; border-radius: 3px; font-weight: 700; font-size: 10px; }
.val-pass { color: #198754; font-weight: 700; }
.val-fail { color: #dc3545; font-weight: 700; }
.stage-tag { font-size: 9px; color: #888; }
tr.row-hidden { display: none; }
/* F&C mode: Part column ("IPL FIG. \ ITEM NUMBER") is centered */
body.fc-print-vertical td.col-part { text-align: center; }

/* ── dark theme (inherited from the parent app) ───────────── */
html[data-bs-theme="dark"] body { background: #1a1d21; color: #dee2e6; }
html[data-bs-theme="dark"] .sidebar { background: #212529; border-color: #495057; }
html[data-bs-theme="dark"] .sidebar-hdr { border-color: #495057; color: #9aa0a6; }
html[data-bs-theme="dark"] .type-btn,
html[data-bs-theme="dark"] .sel-btn { background: #2b3035; color: #dee2e6; border-color: #495057; }
html[data-bs-theme="dark"] .type-btn.active { background: #0d6efd; border-color: #0d6efd; color: #fff; }
html[data-bs-theme="dark"] .sel-btn:hover { background: #343a40; }
html[data-bs-theme="dark"] .ref-item { border-color: #343a40; }
html[data-bs-theme="dark"] .ref-type-tag { background: #343a40; color: #adb5bd; }
html[data-bs-theme="dark"] .ref-type-tag.fc    { background: #032830; color: #6edff6; }
html[data-bs-theme="dark"] .ref-type-tag.extra { background: #051b11; color: #75b798; }
html[data-bs-theme="dark"] .doc-meta { color: #9aa0a6; }
html[data-bs-theme="dark"] th, html[data-bs-theme="dark"] td { border-color: #495057; }
html[data-bs-theme="dark"] thead th { background: #2b3035; color: #dee2e6; }
html[data-bs-theme="dark"] td.na { background: #212529; color: #555; }
html[data-bs-theme="dark"] .val-pass { color: #75b798; }
html[data-bs-theme="dark"] .val-fail,
html[data-bs-theme="dark"] .neg { color: #ea868f; }
html[data-bs-theme="dark"] .stage-tag { color: #6c757d; }


@media print {
    .sidebar, .action-bar { display: none !important; }
    .page-wrap { display: block; height: auto; }
    .content   { padding: 0; overflow: visible; }
    tr.row-hidden { display: none !important; }
    /* keep the table clear of the QR print-mark (top-right corner) */
    .doc-meta  { margin-bottom: 6mm; }
    @page { size: letter landscape; margin: 10mm; }
    /* print is always light regardless of the screen theme */
    html[data-bs-theme="dark"] body { background: #fff !important; color: #000 !important; }
    html[data-bs-theme="dark"] thead th { background: #e9ecef !important; color: #000 !important; }
    html[data-bs-theme="dark"] th, html[data-bs-theme="dark"] td { border-color: #aaa !important; }
    html[data-bs-theme="dark"] td.na { background: #fafafa !important; color: #bbb !important; }
    html[data-bs-theme="dark"] .val-pass { color: #198754 !important; }
    html[data-bs-theme="dark"] .val-fail,
    html[data-bs-theme="dark"] .neg { color: #dc3545 !important; }
    /* F&C filter prints portrait — shrink the table to fit the page width */
    body.fc-print-vertical table  { font-size: 9px; }
    body.fc-print-vertical th,
    body.fc-print-vertical td     { padding: 1px 3px; }
    body.fc-print-vertical .col-figure,
    body.fc-print-vertical .col-defect,
    body.fc-print-vertical .col-result { display: none !important; }
    body.fc-print-vertical .stage-tag  { display: none !important; }
}
</style>
<script>
// inherit the app theme when opened inside the WO tab iframe
try {
    var t = (window.parent && window.parent.document.documentElement.getAttribute('data-bs-theme'))
         || localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-bs-theme', t);
} catch (e) { /* standalone — keep light */ }
</script>
</head>
<body>
@include('shared.print-mark.qr', ['printMarkWorkorder' => $workorder ?? null])
<div class="page-wrap">

<aside class="sidebar">
    <div class="sidebar-hdr">Filter</div>
    <div class="sidebar-body">
        <div class="type-btns">
            <button class="type-btn active" id="btn-all"   onclick="setType('all')">All</button>
            <button class="type-btn"        id="btn-fc"    onclick="setType('fc')">F&amp;C</button>
            <button class="type-btn"        id="btn-extra" onclick="setType('extra')">Extra</button>
        </div>
        <div class="sel-actions">
            <button class="sel-btn" onclick="selectAll()">All</button>
            <button class="sel-btn" onclick="clearAll()">None</button>
        </div>
        <div id="ref-list"></div>
    </div>
</aside>

<main class="content">
    <div class="action-bar">
        <button class="btn-print" onclick="window.print()">&#128438; Print</button>
    </div>
    <div class="doc-title">Fits &amp; Clearances / Measurements</div>
    <div class="doc-meta">
        WO: <strong>W{{ $workorder->number ?? $workorder->id }}</strong>
        &nbsp;·&nbsp; {{ now()->format('d M Y H:i') }}
    </div>

@php
if (! function_exists('wfmt')) {
    function wfmt($v) {
        if ($v === null || $v === '') return '—';
        return number_format(round((float)$v, 4), 4);
    }
}
if (! function_exists('figLabel')) {
    function figLabel($fig) {
        if (! $fig) return '';
        $parent = $fig->parentFigure?->title;
        return $parent ? $parent . ': ' . $fig->title : $fig->title;
    }
}
@endphp

@if(empty($fcRows) && empty($extraRows))
    <p style="color:#888">No measurement points found.</p>
@else
<table>
    <thead>
        <tr>
            <th rowspan="3" class="col-figure">Figure</th>
            <th rowspan="3" id="th-ref">Ref.<br>No.</th>
            <th rowspan="3" id="th-part">Part</th>
            <th colspan="4">Original Manufacturer Limits</th>
            <th colspan="3" id="th-wear">In-Service Wear / Repair Limits</th>
            <th colspan="3" id="th-actual">Actual (WO)</th>
        </tr>
        <tr>
            <th colspan="2">Dimension <span style="font-weight:normal;color:#666">(in)</span></th>
            <th colspan="2">Assembly Clearance <span style="font-weight:normal;color:#666">(in)</span></th>
            <th colspan="2">Dimension <span style="font-weight:normal;color:#666">(in)</span></th>
            <th>Permitted<br>Clearance <span style="font-weight:normal;color:#666">(in)</span></th>
            <th colspan="2">Value</th>
            <th class="col-result">Result</th>
        </tr>
        <tr>
            <th>Min.</th><th>Max.</th>
            <th>Min.</th><th>Max.</th>
            <th>Min.</th><th>Max.</th>
            <th>Max.</th>
            <th><span style="font-weight:normal;color:#666">(in)</span></th><th class="col-defect">Defect</th>
            <th class="col-result"></th>
        </tr>
    </thead>
    <tbody>

    {{-- ── F&C rows (pairs) ──────────────────────────────── --}}
    @foreach($fcRows as $row)
    @if($row['single'] ?? false)
    @php
        // Single-member F&C row: mate in another manual / Between-Across Faces
        // linear dimension — one line, its ref and limits, clearances n/a.
        $sr   = $row['resultA'];
        $sVal = $row['measA']?->actual_value;
        $sSt  = $row['measA'] ? ' <span class="stage-tag">('.($row['measA']->new_part ? 'new' : e($row['measA']->stage)).')</span>' : '';
        $sFix = $row['measA'] && $row['measA']->stage === 'final' && $sr === 'PASS';
    @endphp
    <tr data-ref="{{ $row['ref'] }}" data-type="fc">
        <td class="c col-figure" style="color:#666;font-size:10px">{{ figLabel($row['fig']) }}</td>
        <td class="c" style="font-weight:700">{{ $row['ref'] }}</td>
        <td class="col-part">{{ $row['pA']->description }}@if($row['compA']?->ipl_num) <span style="color:#888">({{ $row['compA']->ipl_num }})</span>@endif</td>
        <td class="r">{{ wfmt($row['pA']->orig_dim_min) }}</td>
        <td class="r">{{ wfmt($row['pA']->orig_dim_max) }}</td>
        <td class="na">—</td>
        <td class="na">—</td>
        <td class="r">{{ wfmt($row['aWearMin']) }}</td>
        <td class="r">{{ wfmt($row['aWearMax']) }}</td>
        <td class="na">—</td>
        <td class="r {{ $sr === 'FAIL' ? 'val-fail' : ($sr === 'PASS' ? 'val-pass' : '') }}">{!! $sVal !== null ? wfmt($sVal).$sSt : '—' !!}</td>
        <td class="c col-defect" style="color:#dc3545;font-size:10px">{{ $row['findingA'] ?? '—' }}@if($row['findingA'] && $sFix) <span style="color:#198754;font-weight:700">/ OK</span>@endif</td>
        <td class="c col-result">@if($sr)<span class="{{ strtolower($sr) }}">{{ $sr }}</span>@else —@endif</td>
    </tr>
    @else
    @php
        // Two members of the pair (A = ID/bore, B = OD/shaft). Per-member Ref.No:
        // when refSplit, each member is its own numbered row ordered by Ref.No;
        // otherwise one merged Ref.No cell (legacy look), ID row first.
        $split = $row['refSplit'] ?? false;
        $mA = [
            'ref' => $row['refId'] ?? $row['ref'], 'desc' => $row['pA']->description,
            'ipl' => $row['compA']?->ipl_num, 'omin' => $row['pA']->orig_dim_min, 'omax' => $row['pA']->orig_dim_max,
            'wmin' => $row['aWearMin'], 'wmax' => $row['aWearMax'],
            'val' => $row['measA']?->actual_value, 'meas' => $row['measA'],
            'finding' => $row['findingA'] ?? null, 'result' => $row['resultA'],
        ];
        $mB = [
            'ref' => $row['refOd'] ?? $row['ref'], 'desc' => $row['pB']->description,
            'ipl' => $row['compB']?->ipl_num, 'omin' => $row['pB']->orig_dim_min, 'omax' => $row['pB']->orig_dim_max,
            'wmin' => $row['bWearMin'], 'wmax' => $row['bWearMax'],
            'val' => $row['measB']?->actual_value, 'meas' => $row['measB'],
            'finding' => $row['findingB'] ?? null, 'result' => $row['resultB'],
        ];
        $members = $split
            ? collect([$mA, $mB])->sortBy('ref', SORT_NATURAL | SORT_FLAG_CASE)->values()->all()
            : [$mA, $mB];
    @endphp
    @foreach($members as $i => $m)
    @php
        $first = $i === 0;
        $r  = $m['result'];
        $st = $m['meas'] ? ' <span class="stage-tag">('.($m['meas']->new_part ? 'new' : e($m['meas']->stage)).')</span>' : '';
        $fix = $m['meas'] && $m['meas']->stage === 'final' && $r === 'PASS';
    @endphp
    <tr data-ref="{{ $m['ref'] ?: $row['ref'] }}" data-type="fc">
        @if($first)
        <td rowspan="2" class="c col-figure" style="color:#666;font-size:10px">{{ figLabel($row['fig']) }}</td>
        @endif
        @if($split)
        <td class="c" style="font-weight:700">{{ $m['ref'] ?: '—' }}</td>
        @elseif($first)
        <td rowspan="2" class="c" style="font-weight:700">{{ $row['ref'] }}</td>
        @endif
        <td class="col-part">{{ $m['desc'] }}@if($m['ipl']) <span style="color:#888">({{ $m['ipl'] }})</span>@endif</td>
        <td class="r">{{ wfmt($m['omin']) }}</td>
        <td class="r">{{ wfmt($m['omax']) }}</td>
        @if($first)
        <td rowspan="2" class="r{{ $row['clearOrigMin'] !== null && $row['clearOrigMin'] < 0 ? ' neg' : '' }}">{{ wfmt($row['clearOrigMin']) }}</td>
        <td rowspan="2" class="r{{ $row['clearOrigMax'] !== null && $row['clearOrigMax'] < 0 ? ' neg' : '' }}">{{ wfmt($row['clearOrigMax']) }}</td>
        @endif
        <td class="r">{{ wfmt($m['wmin']) }}</td>
        <td class="r">{{ wfmt($m['wmax']) }}</td>
        @if($first)
        <td rowspan="2" class="r{{ $row['permClearMax'] !== null && $row['permClearMax'] < 0 ? ' neg' : '' }}">{{ wfmt($row['permClearMax']) }}</td>
        @endif
        <td class="r {{ $r === 'FAIL' ? 'val-fail' : ($r === 'PASS' ? 'val-pass' : '') }}">{!! $m['val'] !== null ? wfmt($m['val']).$st : '—' !!}</td>
        <td class="c col-defect" style="color:#dc3545;font-size:10px">{{ $m['finding'] ?? '—' }}@if($m['finding'] && $fix) <span style="color:#198754;font-weight:700">/ OK</span>@endif</td>
        <td class="c col-result">@if($r)<span class="{{ strtolower($r) }}">{{ $r }}</span>@else —@endif</td>
    </tr>
    @endforeach
    @endif
    @endforeach

    {{-- ── Extra rows (single) ───────────────────────────── --}}
    @foreach($extraRows as $row)
    @php
        $ipl  = $row['comp']?->ipl_num;
        $r    = $row['result'];
        $val  = $row['meas']?->actual_value;
        $st   = $row['meas'] ? ' <span class="stage-tag">('.($row['meas']->new_part ? 'new' : e($row['meas']->stage)).')</span>' : '';
        $fix  = $row['meas'] && $row['meas']->stage === 'final' && $r === 'PASS';
    @endphp
    <tr data-ref="{{ $row['pt']->code }}" data-type="extra">
        <td class="c col-figure" style="color:#666;font-size:10px">{{ figLabel($row['fig']) }}</td>
        <td class="c" style="font-weight:700">{{ $row['pt']->code }}</td>
        <td class="col-part">{{ $row['param']->description }}@if($ipl) <span style="color:#888">({{ $ipl }})</span>@endif</td>
        <td class="r">{{ wfmt($row['param']->orig_dim_min) }}</td>
        <td class="r">{{ wfmt($row['param']->orig_dim_max) }}</td>
        <td class="na">—</td>
        <td class="na">—</td>
        {{-- repair limits when set (post-repair judge), otherwise wear --}}
        @if($row['repair_min'] !== null || $row['repair_max'] !== null)
        <td class="r" style="color:#0d6efd">@if($row['repair_lbl'])<span style="color:#888;font-size:9px">{{ $row['repair_lbl'] }}:</span> @endif{{ wfmt($row['repair_min']) }}</td>
        <td class="r" style="color:#0d6efd">{{ wfmt($row['repair_max']) }}</td>
        @else
        <td class="r">{{ wfmt($row['param']->wear_dim_min) }}</td>
        <td class="r">{{ wfmt($row['param']->wear_dim_max) }}</td>
        @endif
        <td class="na">—</td>
        <td class="r {{ $r === 'FAIL' ? 'val-fail' : ($r === 'PASS' ? 'val-pass' : '') }}">{!! $val !== null ? wfmt($val).$st : '—' !!}</td>
        <td class="c col-defect" style="color:#dc3545;font-size:10px">{{ $row['finding'] ?? '—' }}@if($row['finding'] && $fix) <span style="color:#198754;font-weight:700">/ OK</span>@endif</td>
        <td class="c col-result">@if($r)<span class="{{ strtolower($r) }}">{{ $r }}</span>@else —@endif</td>
    </tr>
    @endforeach

    </tbody>
</table>

@endif
</main>
</div>

<script>
(function () {
    let currentType = 'all';
    const refState  = {};

    const allRefs = [];
    document.querySelectorAll('tr[data-ref]').forEach(tr => {
        const ref = tr.dataset.ref, type = tr.dataset.type;
        const key = ref + '_' + type;
        if (!allRefs.find(r => r.key === key)) {
            allRefs.push({ key, ref, type });
            refState[key] = true;
        }
    });

    function buildRefList() {
        const list = document.getElementById('ref-list');
        list.innerHTML = '';
        allRefs
            .filter(r => currentType === 'all' || r.type === currentType)
            .forEach(({ key, ref, type }) => {
                const div = document.createElement('div');
                div.className = 'ref-item';
                div.innerHTML =
                    `<input type="checkbox" id="r-${key}" ${refState[key] ? 'checked' : ''}>`+
                    `<label for="r-${key}">${ref}</label>`+
                    `<span class="ref-type-tag ${type}">${type === 'fc' ? 'F&C' : 'Extra'}</span>`;
                div.querySelector('input').addEventListener('change', function () {
                    refState[key] = this.checked; applyFilter();
                });
                list.appendChild(div);
            });
    }

    function applyFilter() {
        document.querySelectorAll('tr[data-ref]').forEach(tr => {
            const key  = tr.dataset.ref + '_' + tr.dataset.type;
            const typeOk = currentType === 'all' || currentType === tr.dataset.type;
            tr.classList.toggle('row-hidden', !(typeOk && refState[key] !== false));
        });
    }

    // F&C filter → print portrait (table shrunk to fit); otherwise landscape.
    function applyLayout() {
        const fc = currentType === 'fc';
        document.body.classList.toggle('fc-print-vertical', fc);
        let st = document.getElementById('page-orient');
        if (!st) { st = document.createElement('style'); st.id = 'page-orient'; document.head.appendChild(st); }
        st.textContent = '@media print{@page{size:letter ' + (fc ? 'portrait' : 'landscape') + ';margin:10mm}}';

        // F&C headers per CMM wording: Figure column hidden (CSS); Ref. No. →
        // "FIGURE <num> NUMBER" (numeric refs) / "FIGURE <num> REF LTR" (letter
        // refs); Part → "IPL FIG. \ ITEM NUMBER".
        const thActual = document.getElementById('th-actual');
        if (thActual) {
            thActual.textContent = fc ? @json('W' . ($workorder->number ?? $workorder->id)) : 'Actual (WO)';
        }

        const thWear = document.getElementById('th-wear');
        if (thWear) {
            thWear.textContent = currentType === 'extra' ? 'Repair Limits'
                               : currentType === 'fc'    ? 'In-Service Wear Limits'
                               : 'In-Service Wear / Repair Limits';
        }

        const thRef  = document.getElementById('th-ref');
        const thPart = document.getElementById('th-part');
        if (thRef && thPart) {
            if (fc) {
                const fcTrs = Array.from(document.querySelectorAll('tr[data-type="fc"]'))
                    .filter(tr => !tr.classList.contains('row-hidden'));
                const codes = [...new Set(fcTrs.map(tr => tr.dataset.ref))];
                const allNumeric = codes.length > 0 && codes.every(c => /^\d+$/.test(c));
                // 8001 = standard CMM F&C table figure number (same in every CMM)
                thRef.innerHTML  = 'FIGURE 8001<br>' + (allNumeric ? 'NUMBER' : 'REF LTR');
                thPart.textContent = 'IPL FIG. \\ ITEM NUMBER';
            } else {
                thRef.innerHTML  = 'Ref.<br>No.';
                thPart.textContent = 'Part';
            }
        }
    }

    window.setType = function (type) {
        currentType = type;
        ['all','fc','extra'].forEach(t =>
            document.getElementById('btn-'+t)?.classList.toggle('active', t === type));
        buildRefList(); applyFilter(); applyLayout();
    };
    window.selectAll = function () {
        document.querySelectorAll('#ref-list input').forEach(cb => {
            refState[cb.id.replace('r-','')] = cb.checked = true;
        }); applyFilter();
    };
    window.clearAll = function () {
        document.querySelectorAll('#ref-list input').forEach(cb => {
            refState[cb.id.replace('r-','')] = cb.checked = false;
        }); applyFilter();
    };

    buildRefList(); applyFilter(); applyLayout();
})();
</script>
</body>
</html>
