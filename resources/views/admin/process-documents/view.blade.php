{{-- Process document browser view for a WO (opened in a new tab from the Part
     Processes tab). Print-only: nothing is generated or stored — печать идёт
     через браузер, PDF в библиотеку WO эта страница не пишет. --}}
@php
    $title = ($document->title ?: ($document->doc_type ?: 'Document')) . ' — W' . ($workorder->number ?? $workorder->id);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $title }}</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
html,body{font-family:Arial,sans-serif;font-size:12px;background:#f8f9fa;color:#212529}
.toolbar{display:flex;align-items:center;gap:8px;padding:8px 16px;background:#fff;border-bottom:1px solid #dee2e6;position:sticky;top:0;z-index:50}
.toolbar h1{font-size:13px;font-weight:700;flex:1;margin:0}
.btn{display:inline-flex;align-items:center;gap:5px;padding:5px 14px;border-radius:4px;font-size:12px;cursor:pointer;border:1px solid transparent;text-decoration:none}
.btn-primary{background:#0d6efd;color:#fff}
.doc-page{background:#fff;max-width:980px;margin:14px auto;box-shadow:0 1px 4px rgba(0,0,0,.15)}
.doc-page-empty{max-width:980px;margin:40px auto;color:#888;text-align:center}
.pdw-page{position:relative}
.pdw-page img{width:100%;display:block}
.pdw-svg{position:absolute;top:0;left:0;width:100%;height:100%}
.pdw-dot{position:absolute;width:5px;height:5px;margin:-2.5px 0 0 -2.5px;background:#0d9488;border-radius:50%}
.pdw-el{position:absolute;transform:translate(-50%,-50%);font-size:9pt;font-weight:700;white-space:nowrap;line-height:1.2}
.pdw-dim{color:#0d6efd;background:rgba(255,255,255,0.92);border:1px solid #0d6efd;border-radius:2px;padding:0 3px}
.pdw-dim.st-pass{color:#198754;border-color:#198754}
.pdw-dim.st-fail{color:#dc3545;border-color:#dc3545}
.pdw-dim.st-repair{color:#6f42c1;border-color:#6f42c1}
.pdw-dim.st-nodata{color:#b58900;border-color:#b58900}
.pdw-dim.pdw-value{border:none;background:transparent;padding:0}
.pdw-label{color:#0d9488;background:rgba(255,255,255,0.85);padding:0 3px}
.fig-caption{font:700 12px Arial,sans-serif;color:#000;background:#fff;border-bottom:1px solid #000;padding:3px 10px}
@media print{
  html,body{margin:0;padding:0;background:#fff}
  .toolbar{display:none!important}
  /* break BEFORE each page (not after) so no trailing blank sheet */
  .doc-page{box-shadow:none;margin:0 auto;max-width:none;page-break-before:always;page-break-inside:avoid}
  .doc-page:first-child{page-break-before:auto}
  .doc-page .pdw-page img{max-height:268mm;width:auto;max-width:100%;margin:0 auto}
  @page{size:a4 portrait;margin:8mm}
}
</style>
</head>
<body>
<div class="toolbar">
  <h1>{{ $title }}</h1>
  <button type="button" class="btn btn-primary" onclick="window.print()">&#9112; {{ __('Print') }}</button>
</div>
@forelse($pagesHtml as $pageHtml)
  <div class="doc-page">
    @if(trim((string) ($caption ?? '')) !== '')<div class="fig-caption">{{ $caption }}</div>@endif
    {!! $pageHtml !!}
  </div>
@empty
  <div class="doc-page-empty">{{ __('This document has no pages.') }}</div>
@endforelse
</body>
</html>
