@extends('admin.master')
@section('content')
<style>
.lc-order-page{padding:16px;display:flex;flex-direction:column;flex:1;min-height:0;gap:12px}.lc-order-page header{display:flex;align-items:center;flex-wrap:wrap;gap:12px;flex-shrink:0}.lc-order-page h5{margin:0;flex:1}.lc-order-scroll{flex:1;min-height:0;overflow:auto;border:1px solid var(--avia-border)}.lc-order-scroll table{margin:0}.lc-order-scroll th{position:sticky;top:0;background:var(--avia-surface);z-index:1}.lc-order-handle{cursor:grab}.lc-order-row.dragging{opacity:.4}.lc-order-actions{white-space:nowrap}.lc-order-variants{white-space:pre-line}.lc-order-message:empty{display:none}.lc-order-message{white-space:pre-line;margin:0;max-height:140px;overflow:auto}
</style>
<style>
.lc-order-position{width:76px;min-width:76px;text-align:center}
.lc-order-search{max-width:420px}
.lc-order-row[hidden]{display:none!important}
.lc-order-row{transition:transform .2s ease;position:relative}
.lc-order-row.dragging{opacity:.55;z-index:2;pointer-events:none}
.lc-order-slot>td{background:rgba(13,202,240,.16)!important;box-shadow:inset 0 2px #0dcaf0,inset 0 -2px #0dcaf0}
.lc-order-slot>td:first-child{border-left:2px solid #0dcaf0}
.lc-order-slot>td:last-child{border-right:2px solid #0dcaf0}
.lc-order-ghost{position:fixed;top:-1000px;left:0;max-width:650px;padding:14px 20px;border:2px solid #0dcaf0;border-radius:6px;background:#33414e;color:#fff;box-shadow:0 8px 24px #0006;font-weight:600}
.lc-order-dragging .lc-order-handle{cursor:grabbing}
.lc-order-landed>td{animation:lc-order-land .8s ease-out}
@keyframes lc-order-land{from{background-color:rgba(13,202,240,.35)}to{background-color:transparent}}
@media(prefers-reduced-motion:reduce){.lc-order-row{transition:none}.lc-order-landed>td{animation:none}}
</style>
<div class="lc-order-page" id="lcOrder" data-save="{{ route('manuals.log-card-order.update', $manual) }}" data-revision="{{ $revision }}">
<header><a class="btn btn-outline-secondary btn-sm" href="{{ route('manuals.show', $manual) }}#nav-parts">← Manual</a><h5>Log Card order · {{ $manual->number }}</h5><button type="button" class="btn btn-primary btn-sm" id="lcOrderSave">Save order</button></header>
<input id="lcOrderSearch" type="search" class="form-control form-control-sm lc-order-search" placeholder="Search IPL, part number, description…" aria-label="Search Log Card parts">
<p id="lcOrderMessage" class="lc-order-message" role="status"></p>
<div class="lc-order-scroll"><table class="table table-sm align-middle"><thead><tr><th></th><th>#</th><th>IPL</th><th>Part number</th><th>Description</th><th></th></tr></thead><tbody id="lcOrderRows"></tbody></table><p id="lcOrderNoResults" class="text-muted p-3 mb-0" hidden>No matching parts.</p></div>
</div>
<script type="application/json" id="lcOrderData">@json($groups)</script>
@endsection
@section('scripts')
<script src="{{ asset('js/manual-log-card-order.js') }}?v={{ filemtime(public_path('js/manual-log-card-order.js')) }}"></script>
@endsection
