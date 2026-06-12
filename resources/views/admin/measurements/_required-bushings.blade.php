<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Required Bushings — WO W{{ $workorder->number ?? $workorder->id }}</title>
<style>
*, *::before, *::after { box-sizing: border-box; }
body { margin: 0; font-family: Arial, sans-serif; font-size: 12px; background: #fff; color: #000; padding: 16px 20px; }
.action-bar { display: flex; gap: 8px; margin-bottom: 14px; }
.btn-print  { padding: 5px 16px; font-size: 12px; cursor: pointer;
              background: #0d6efd; color: #fff; border: none; border-radius: 4px; }
.btn-close  { padding: 5px 14px; font-size: 12px; cursor: pointer;
              background: #fff; border: 1px solid #aaa; border-radius: 4px; }
.doc-title { font-size: 15px; font-weight: 700; margin-bottom: 2px; }
.doc-meta  { font-size: 11px; color: #555; margin-bottom: 14px; }
table  { border-collapse: collapse; width: 100%; font-size: 11px; }
th, td { border: 1px solid #aaa; padding: 3px 6px; }
thead th { background: #e9ecef; text-align: center; }
td.c { text-align: center; }
td.r { text-align: right; }
.ovs   { color: #0d6efd; font-weight: 700; }
.ok    { color: #198754; font-weight: 700; }
.pend  { color: #dc3545; }
.note  { font-size: 10px; color: #555; }
/* ── dark theme (inherited from the parent app) ───────────── */
html[data-bs-theme="dark"] body { background: #1a1d21; color: #dee2e6; }
html[data-bs-theme="dark"] .doc-meta { color: #9aa0a6; }
html[data-bs-theme="dark"] th, html[data-bs-theme="dark"] td { border-color: #495057; }
html[data-bs-theme="dark"] thead th { background: #2b3035; color: #dee2e6; }
html[data-bs-theme="dark"] .btn-close { background: #2b3035; color: #dee2e6; border-color: #495057; }
html[data-bs-theme="dark"] .note { color: #9aa0a6; }
html[data-bs-theme="dark"] .ovs { color: #6ea8fe; }
html[data-bs-theme="dark"] .ok  { color: #75b798; }
html[data-bs-theme="dark"] .pend { color: #ea868f; }
@media print {
    .action-bar, .no-print { display: none !important; }
    .doc-meta { margin-bottom: 10mm; }
    body { padding: 0; }
    @page { size: letter portrait; margin: 12mm; }
    /* print is always light regardless of the screen theme */
    html[data-bs-theme="dark"] body { background: #fff !important; color: #000 !important; }
    html[data-bs-theme="dark"] thead th { background: #e9ecef !important; color: #000 !important; }
    html[data-bs-theme="dark"] th, html[data-bs-theme="dark"] td { border-color: #aaa !important; }
}
</style>
<script>
// inherit the app theme when opened inside the WO tab iframe
try {
    var t = (window.parent && window.parent.document.documentElement.getAttribute('data-bs-theme'))
         || localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-bs-theme', t);
} catch (e) { /* cross-origin / standalone — keep light */ }
</script>
</head>
<body>
@include('shared.print-mark.qr', ['printMarkWorkorder' => $workorder ?? null, 'printMarkQrSize' => 32, 'printMarkFormName' => 'Required Bushings'])

<div class="action-bar">
    <button class="btn-print" onclick="window.print()">&#128438; Print</button>
</div>
<div class="doc-title">Required Bushings</div>
<div class="doc-meta">
    WO: <strong>W{{ $workorder->number ?? $workorder->id }}</strong>
    &nbsp;·&nbsp; {{ now()->format('d M Y H:i') }}
    &nbsp;·&nbsp; P/N per position derived from bore measurements
</div>

@if(empty($rows))
    <p style="color:#888">No bushing positions configured in this manual.</p>
@else
<table>
    <thead>
        <tr>
            <th>Position</th>
            <th>Bushing</th>
            <th>Bore</th>
            <th>P/N</th>
            <th>IPL#</th>
            <th>Qty</th>
            <th class="no-print">Sketch</th>
        </tr>
    </thead>
    <tbody>
    @foreach($rows as $r)
    @php
        $boreCls = $r['bore'] === 'OK (initial)' ? 'ok'
                 : ($r['bore'] === 'not inspected' ? 'pend' : 'ovs');
    @endphp
    <tr>
        <td class="c" style="font-weight:700">{{ $r['point'] ?: '—' }}</td>
        <td>{{ $r['bushing'] }}</td>
        <td class="c {{ $boreCls }}">{{ $r['bore'] }}</td>
        <td class="c">{{ $r['pn'] ?? '—' }}@if($r['note'])<div class="note">{{ $r['note'] }}</div>@endif</td>
        <td class="c">{{ $r['ipl'] ?? '—' }}</td>
        <td class="c" style="font-weight:700">{{ $r['qty'] }}</td>
        <td class="c no-print">
            @if($r['sketch'])
            <a href="{{ route('inspection-components.bushing-sketch-view', [$workorder->id, $r['ic_id']]) }}?param_id={{ $r['param_id'] }}" target="_blank"
               style="font-size:11px;text-decoration:none;color:#0d6efd" title="Print bushing sketch">&#128438; Sketch</a>
            @else
            <span style="color:#bbb">—</span>
            @endif
        </td>
    </tr>
    @endforeach
    </tbody>
</table>
@endif
</body>
</html>
