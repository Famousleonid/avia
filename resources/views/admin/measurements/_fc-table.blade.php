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

@media print {
    .sidebar, .action-bar { display: none !important; }
    .page-wrap { display: block; height: auto; }
    .content   { padding: 0; overflow: visible; }
    tr.row-hidden { display: none !important; }
    @page { size: A4 landscape; margin: 10mm; }
}
</style>
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
        <button class="btn-close" onclick="window.close()">Close</button>
    </div>
    <div class="doc-title">Fits &amp; Clearances / Measurements</div>
    <div class="doc-meta">
        WO: <strong>W{{ $workorder->number ?? $workorder->id }}</strong>
        &nbsp;·&nbsp; {{ now()->format('d M Y H:i') }}
    </div>

@php
function wfmt($v) {
    if ($v === null || $v === '') return '—';
    return number_format(round((float)$v, 4), 4);
}
function figLabel($fig) {
    $parent = $fig->parentFigure?->title;
    return $parent ? $parent . ': ' . $fig->title : $fig->title;
}
@endphp

@if(empty($fcRows) && empty($extraRows))
    <p style="color:#888">No measurement points found.</p>
@else
<table>
    <thead>
        <tr>
            <th rowspan="3">Figure</th>
            <th rowspan="3">Ref.<br>No.</th>
            <th rowspan="3">Part</th>
            <th colspan="4">Original Manufacturer Limits</th>
            <th colspan="3">In-Service Wear Limits</th>
            <th colspan="3">Actual (WO)</th>
        </tr>
        <tr>
            <th colspan="2">Dimension <span style="font-weight:normal;color:#666">mm</span></th>
            <th colspan="2">Assembly Clearance <span style="font-weight:normal;color:#666">mm</span></th>
            <th colspan="2">Dimension <span style="font-weight:normal;color:#666">mm</span></th>
            <th>Permitted<br>Clearance <span style="font-weight:normal;color:#666">mm</span></th>
            <th>Value <span style="font-weight:normal;color:#666">mm</span></th>
            <th>Clearance <span style="font-weight:normal;color:#666">mm</span></th>
            <th>Result</th>
        </tr>
        <tr>
            <th>Min.</th><th>Max.</th>
            <th>Min.</th><th>Max.</th>
            <th>Min.</th><th>Max.</th>
            <th>Max.</th>
            <th></th><th></th><th></th>
        </tr>
    </thead>
    <tbody>

    {{-- ── F&C rows (pairs) ──────────────────────────────── --}}
    @foreach($fcRows as $row)
    @php
        $iplA  = $row['compA']?->ipl_num;
        $iplB  = $row['compB']?->ipl_num;
        $valA  = $row['measA']?->actual_value;
        $valB  = $row['measB']?->actual_value;
        $rA    = $row['resultA'];
        $rB    = $row['resultB'];
        $ac    = $row['actualClear'];
        $acFail = $ac !== null && $row['permClearMax'] !== null && $ac > $row['permClearMax'];
        $stA   = $row['measA'] ? ' <span class="stage-tag">('.e($row['measA']->stage).')</span>' : '';
        $stB   = $row['measB'] ? ' <span class="stage-tag">('.e($row['measB']->stage).')</span>' : '';
    @endphp
    <tr data-ref="{{ $row['pt']->code }}" data-type="fc">
        <td rowspan="2" class="c" style="color:#666;font-size:10px">{{ figLabel($row['fig']) }}</td>
        <td rowspan="2" class="c" style="font-weight:700">{{ $row['pt']->code }}</td>
        <td>{{ $row['pA']->description }}@if($iplA) <span style="color:#888">({{ $iplA }})</span>@endif</td>
        <td class="r">{{ wfmt($row['pA']->orig_dim_min) }}</td>
        <td class="r">{{ wfmt($row['pA']->orig_dim_max) }}</td>
        <td rowspan="2" class="r{{ $row['clearOrigMin'] !== null && $row['clearOrigMin'] < 0 ? ' neg' : '' }}">{{ wfmt($row['clearOrigMin']) }}</td>
        <td rowspan="2" class="r{{ $row['clearOrigMax'] !== null && $row['clearOrigMax'] < 0 ? ' neg' : '' }}">{{ wfmt($row['clearOrigMax']) }}</td>
        <td class="r">{{ wfmt($row['aWearMin']) }}</td>
        <td class="r">{{ wfmt($row['aWearMax']) }}</td>
        <td rowspan="2" class="r{{ $row['permClearMax'] !== null && $row['permClearMax'] < 0 ? ' neg' : '' }}">{{ wfmt($row['permClearMax']) }}</td>
        <td class="r {{ $rA === 'FAIL' ? 'val-fail' : ($rA === 'PASS' ? 'val-pass' : '') }}">{!! $valA !== null ? wfmt($valA).$stA : '—' !!}</td>
        <td rowspan="2" class="r {{ $acFail ? 'val-fail' : ($ac !== null ? 'val-pass' : '') }}">{{ $ac !== null ? wfmt($ac) : '—' }}</td>
        <td class="c">@if($rA)<span class="{{ strtolower($rA) }}">{{ $rA }}</span>@else —@endif</td>
    </tr>
    <tr data-ref="{{ $row['pt']->code }}" data-type="fc">
        <td>{{ $row['pB']->description }}@if($iplB) <span style="color:#888">({{ $iplB }})</span>@endif</td>
        <td class="r">{{ wfmt($row['pB']->orig_dim_min) }}</td>
        <td class="r">{{ wfmt($row['pB']->orig_dim_max) }}</td>
        <td class="r">{{ wfmt($row['bWearMin']) }}</td>
        <td class="r">{{ wfmt($row['bWearMax']) }}</td>
        <td class="r {{ $rB === 'FAIL' ? 'val-fail' : ($rB === 'PASS' ? 'val-pass' : '') }}">{!! $valB !== null ? wfmt($valB).$stB : '—' !!}</td>
        <td class="c">@if($rB)<span class="{{ strtolower($rB) }}">{{ $rB }}</span>@else —@endif</td>
    </tr>
    @endforeach

    {{-- ── Extra rows (single) ───────────────────────────── --}}
    @foreach($extraRows as $row)
    @php
        $ipl  = $row['comp']?->ipl_num;
        $r    = $row['result'];
        $val  = $row['meas']?->actual_value;
        $st   = $row['meas'] ? ' <span class="stage-tag">('.e($row['meas']->stage).')</span>' : '';
    @endphp
    <tr data-ref="{{ $row['pt']->code }}" data-type="extra">
        <td class="c" style="color:#666;font-size:10px">{{ figLabel($row['fig']) }}</td>
        <td class="c" style="font-weight:700">{{ $row['pt']->code }}</td>
        <td>{{ $row['param']->description }}@if($ipl) <span style="color:#888">({{ $ipl }})</span>@endif</td>
        <td class="r">{{ wfmt($row['param']->orig_dim_min) }}</td>
        <td class="r">{{ wfmt($row['param']->orig_dim_max) }}</td>
        <td class="na">—</td>
        <td class="na">—</td>
        <td class="r">{{ wfmt($row['param']->wear_dim_min) }}</td>
        <td class="r">{{ wfmt($row['param']->wear_dim_max) }}</td>
        <td class="na">—</td>
        <td class="r {{ $r === 'FAIL' ? 'val-fail' : ($r === 'PASS' ? 'val-pass' : '') }}">{!! $val !== null ? wfmt($val).$st : '—' !!}</td>
        <td class="na">—</td>
        <td class="c">@if($r)<span class="{{ strtolower($r) }}">{{ $r }}</span>@else —@endif</td>
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

    window.setType = function (type) {
        currentType = type;
        ['all','fc','extra'].forEach(t =>
            document.getElementById('btn-'+t)?.classList.toggle('active', t === type));
        buildRefList(); applyFilter();
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

    buildRefList(); applyFilter();
})();
</script>
</body>
</html>
