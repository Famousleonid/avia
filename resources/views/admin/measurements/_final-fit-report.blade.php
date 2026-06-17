<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Final Dimensional Report — WO W{{ $workorder->number ?? $workorder->id }}</title>
<style>
*, *::before, *::after { box-sizing: border-box; }
body { margin: 0; font-family: Arial, sans-serif; font-size: 12px; background: #fff; color: #000; padding: 16px 20px; }
.action-bar { display: flex; gap: 8px; margin-bottom: 14px; }
.btn-print  { padding: 5px 16px; font-size: 12px; cursor: pointer;
              background: #0d6efd; color: #fff; border: none; border-radius: 4px; }
.doc-title { font-size: 15px; font-weight: 700; margin-bottom: 2px; }
.doc-meta  { font-size: 11px; color: #555; margin-bottom: 14px; }
table  { border-collapse: collapse; width: 100%; font-size: 11px; }
th, td { border: 1px solid #aaa; padding: 3px 6px; }
thead th { background: #e9ecef; text-align: center; }
td.c { text-align: center; }
td.r { text-align: right; font-family: monospace; }
.pass { background: #d1e7dd; color: #0a3622; padding: 1px 5px; border-radius: 3px; font-weight: 700; font-size: 10px; }
.fail { background: #f8d7da; color: #58151c; padding: 1px 5px; border-radius: 3px; font-weight: 700; font-size: 10px; }
.miss { color: #dc3545; font-size: 10px; }
.src  { font-size: 9px; color: #888; }
/* ── dark theme (inherited from the parent app) ───────────── */
html[data-bs-theme="dark"] body { background: #1a1d21; color: #dee2e6; }
html[data-bs-theme="dark"] .doc-meta { color: #9aa0a6; }
html[data-bs-theme="dark"] th, html[data-bs-theme="dark"] td { border-color: #495057; }
html[data-bs-theme="dark"] thead th { background: #2b3035; color: #dee2e6; }
html[data-bs-theme="dark"] .miss { color: #ea868f; }
@media print {
    .action-bar { display: none !important; }
    .doc-meta { margin-bottom: 10mm; }
    body { padding: 0; }
    @page { size: letter portrait; margin: 12mm; }
    html[data-bs-theme="dark"] body { background: #fff !important; color: #000 !important; }
    html[data-bs-theme="dark"] thead th { background: #e9ecef !important; color: #000 !important; }
    html[data-bs-theme="dark"] th, html[data-bs-theme="dark"] td { border-color: #aaa !important; }
}
</style>
<script>
try {
    var t = (window.parent && window.parent.document.documentElement.getAttribute('data-bs-theme'))
         || localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-bs-theme', t);
} catch (e) { /* standalone — keep light */ }
</script>
</head>
<body>
@include('shared.print-mark.qr', ['printMarkWorkorder' => $workorder ?? null, 'printMarkQrSize' => 32, 'printMarkFormName' => 'Final Dimensional Report'])

<div class="action-bar">
    <button class="btn-print" onclick="window.print()">&#128438; Print</button>
</div>
<div class="doc-title">Final Dimensional Report — Bushing Fits</div>
<div class="doc-meta">
    WO: <strong>W{{ $workorder->number ?? $workorder->id }}</strong>
    &nbsp;·&nbsp; {{ now()->format('d M Y H:i') }}
    &nbsp;·&nbsp; final bore / bushing OD sizes and resulting fit &nbsp;·&nbsp; dimensions (in), + = interference
</div>

@php
if (! function_exists('ffmt')) {
    function ffmt($v) { return $v === null ? '—' : number_format((float)$v, 4); }
}
@endphp

@if(empty($rows))
    <p style="color:#888">No bushing positions configured in this manual.</p>
@else
<table>
    <thead>
        <tr>
            <th>Pos.</th>
            <th>Part / Bore</th>
            <th>Bore final</th>
            <th>Bushing</th>
            <th>OD final</th>
            <th>Actual fit</th>
            <th>Allowed fit</th>
            <th>Qty</th>
            <th>Result</th>
        </tr>
    </thead>
    <tbody>
    @php
        // merge the Pos. cell across consecutive rows of the same bushing position
        $posSpan = [];
        foreach ($rows as $i => $r) {
            if ($i > 0 && $rows[$i - 1]['pos'] === $r['pos']) { $posSpan[$i] = 0; continue; }
            $n = 1;
            while (isset($rows[$i + $n]) && $rows[$i + $n]['pos'] === $r['pos']) $n++;
            $posSpan[$i] = $n;
        }
    @endphp
    @foreach($rows as $i => $r)
    <tr>
        @if($posSpan[$i] > 0)
        <td class="c" style="font-weight:700;vertical-align:middle" @if($posSpan[$i] > 1) rowspan="{{ $posSpan[$i] }}" @endif>{{ $r['pos'] }}</td>
        @endif
        <td>{{ $r['bore_part'] }}</td>
        <td class="r">{{ ffmt($r['bore_val']) }}@if($r['bore_step']) <span class="src">{{ $r['bore_step'] }}</span>@endif
            @if($r['bore_val'] === null)<div class="miss">not measured</div>@endif</td>
        <td>{{ $r['bushing'] }}</td>
        <td class="r">{{ ffmt($r['od_val']) }}@if($r['od_val'] === null)<div class="miss">not measured</div>@endif</td>
        <td class="r" style="font-weight:700">{{ ffmt($r['fit']) }}</td>
        <td class="r">@if($r['allow_min'] !== null){{ ffmt($r['allow_min']) }} … {{ ffmt($r['allow_max']) }} <span class="src">({{ $r['allow_src'] }})</span>@else —@endif</td>
        <td class="c">{{ $r['qty'] }}</td>
        <td class="c">@if($r['result'])<span class="{{ strtolower($r['result']) }}">{{ $r['result'] }}</span>@else —@endif</td>
    </tr>
    @endforeach
    </tbody>
</table>
@endif
</body>
</html>
