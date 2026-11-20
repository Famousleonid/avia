@extends('admin.master')
@section('style')
<style>
    #process-vendor{position:relative}
    #process-vendor[data-pv-initializing]{visibility:hidden}
    #process-vendor>.pv-initial-loading{display:none}
    #process-vendor[data-pv-initializing]>.pv-initial-loading{display:grid;place-content:center;position:absolute;inset:0;visibility:visible;color:var(--pv-muted);font-size:13px}
</style>
<link rel="stylesheet" href="{{ asset('css/process-vendor.css') }}?v={{ filemtime(public_path('css/process-vendor.css')) }}">
@endsection
@section('content')
<section class="pv-page" id="process-vendor" data-pv-initializing aria-busy="true">
    <div class="pv-initial-loading" role="status">Loading Process Vendor…</div>
    <header class="pv-header">
        <div class="pv-heading"><h1 class="text-info">Process Vendor</h1><p>Ready for shipping <span>·</span> @projectDate(now())</p></div>
        <div class="pv-tools"><input id="pv-search" type="search" class="form-control" placeholder="Search all processes: WO, part or technician" aria-label="Search all ready processes"><button type="button" id="pv-refresh" class="btn btn-outline-primary"><i class="bi bi-arrow-clockwise" aria-hidden="true"></i> Refresh</button></div>
    </header>
    <div class="pv-tab-bar pv-header-line">
        <nav class="pv-tabs" aria-label="Process filters">
        @foreach($preferences['enabled'] as $name)
        <button type="button" class="pv-tab" data-process="{{ $name }}" aria-pressed="false">{{ $name }} <span>({{ $rows->where('process_group', $name)->groupBy('workorder_id')->count() }})</span></button>
        @endforeach
        </nav><button type="button" class="btn btn-outline-primary pv-settings-button" id="pv-settings-open"><i class="bi bi-gear" aria-hidden="true"></i> Settings</button></div>
    <nav id="pv-subtabs" class="pv-subtabs" aria-label="Processes in selected group" hidden></nav>
    <div class="pv-body">
        <div class="pv-queue" aria-label="Ready processes">
            <div class="pv-queue-heading"><span><strong id="pv-wo-count">0</strong> workorders · <span id="pv-visible-count">0</span> parts ready</span><label class="pv-check-all"><input type="checkbox" id="pv-select-visible"> Select visible WO</label></div>
            <p id="pv-search-scope" class="text-info small" hidden>Search across all processes, including hidden tabs</p>
            <div id="pv-groups">
            @include('admin.process_vendor._queue')
            </div>
            <div class="pv-empty" id="pv-empty" hidden><i class="bi bi-box-seam" aria-hidden="true"></i><h2>No ready workorders</h2><p id="pv-empty-message">No workorders match this process and search.</p><p>Shimmy Damper O/H requires neither Approval nor Post Disassembly inspection. STD NDT requires Post Disassembly inspection. Other processes require Approval. Preceding operations must be complete.</p></div>
        </div>
        <aside class="pv-selection">
            <h2>Shipping Selection <span>(<span id="pv-selected-count">0</span> WO selected)</span></h2>
            <button type="button" class="btn btn-primary w-100" id="pv-check" disabled><i class="bi bi-check2-circle" aria-hidden="true"></i> Confirm at shipping</button>
            <button type="button" class="btn btn-link pv-uncheck" id="pv-uncheck" disabled>Clear shipping confirmation</button>
            <hr>
            <form id="pv-order-form" data-no-spinner>
                <label for="pv-vendor">Vendor</label><select class="form-select" id="pv-vendor" required><option value="">Select vendor</option>@foreach($vendors as $vendor)<option value="{{ $vendor->id }}">{{ $vendor->name }}</option>@endforeach</select>
                <details class="pv-order-details"><summary>Address &amp; shipping details</summary>
                    <label for="pv-address">Ordered from address</label><textarea class="form-control" id="pv-address" rows="3" maxlength="1500"></textarea>
                    <label for="pv-contact">Contact name</label><input class="form-control" id="pv-contact" maxlength="255">
                    <label for="pv-phone">Phone</label><input class="form-control" id="pv-phone" type="tel" maxlength="80">
                    <label for="pv-email">Email</label><input class="form-control" id="pv-email" type="email" maxlength="255">
                    <label for="pv-vendor-number">Vendor number</label><input class="form-control" id="pv-vendor-number" maxlength="100">
                    <label for="pv-via">Ship via</label><input class="form-control" id="pv-via" maxlength="100">
                    <label for="pv-terms">Terms</label><input class="form-control" id="pv-terms" maxlength="100">
                    <label for="pv-instructions">Additional instructions</label><textarea class="form-control" id="pv-instructions" rows="3" maxlength="4000"></textarea>
                </details>
                <button type="submit" class="btn btn-primary w-100" id="pv-create" disabled>Review Repair Order</button>
            </form>
            <div class="pv-guidance"><p><strong>Shimmy Damper O/H</strong> without Approval or Post Disassembly inspection.</p><p><strong>STD NDT</strong> after Post Disassembly inspection.</p><p><strong>Other processes</strong> after approval. Preceding operations must be complete.</p><p>Confirm only parts physically present at Shipping. Creating an RO does not record dispatch.</p></div>
            <button type="button" class="btn btn-outline-primary w-100" id="pv-orders-open"><i class="bi bi-clock-history" aria-hidden="true"></i> Repair Orders</button>
        </aside>
    </div>
    <footer class="pv-footer"><span id="pv-load-status" role="timer" aria-live="off">Loading saved queue…</span><button class="btn btn-primary pv-mobile-selection" type="button" id="pv-selection-jump">Shipping selection</button><button class="btn btn-outline-primary" type="button" id="pv-clear" disabled>Clear selection</button></footer>
    <dialog id="pv-settings" class="pv-settings-dialog" aria-labelledby="pv-settings-title">
        <form id="pv-settings-form" data-no-spinner>
            <header><h2 id="pv-settings-title">Process tabs</h2><button type="button" class="btn-close" id="pv-settings-close" aria-label="Close settings"></button></header>
            <div class="pv-group-toolbar"><button type="button" class="btn btn-outline-primary" id="pv-group-add" disabled>Add to group</button><span id="pv-group-selection" role="status">Select processes on the right</span><button type="button" class="btn btn-link" id="pv-group-clear" hidden>Clear selection</button></div>
            <small class="pv-members-help">Left: show tab. Right: select processes to group.</small>
            <div class="pv-settings-actions"><button type="button" class="btn btn-link" id="pv-settings-all">Select all</button><button type="button" class="btn btn-link" id="pv-settings-none">Clear all</button><button type="button" class="btn btn-link" id="pv-settings-defaults">Defaults</button></div>
            <div class="pv-settings-columns"><span>Show tab / Process name</span><span>Select</span></div>
            <div class="pv-settings-list" aria-label="Process visibility and group assignment"></div>
            <footer><span>Saved to your AVIA preferences</span><button class="btn btn-primary" type="submit" id="pv-settings-save">Save tabs &amp; groups</button></footer>
        </form>
    </dialog>
    <dialog id="pv-group-dialog" class="pv-group-dialog" aria-labelledby="pv-group-title">
        <form id="pv-group-form" data-no-spinner>
            <header><h2 id="pv-group-title">Add to group</h2><button type="button" class="btn-close" id="pv-group-close" aria-label="Close group name"></button></header>
            <label for="pv-group-name">Group name</label>
            <input id="pv-group-name" class="form-control" maxlength="60" required autocomplete="off" aria-describedby="pv-group-help pv-group-error">
            <small id="pv-group-help">Enter a new name or an existing group name.</small>
            <p id="pv-group-error" role="alert" hidden></p>
            <footer><button type="button" class="btn btn-outline-secondary" id="pv-group-cancel">Cancel</button><button type="submit" class="btn btn-primary" id="pv-group-submit">Add</button></footer>
        </form>
    </dialog>
    <dialog id="pv-orders" class="pv-orders-dialog" aria-labelledby="pv-orders-title">
        <header><h2 id="pv-orders-title">Repair Orders</h2><button type="button" class="btn-close" id="pv-orders-close" aria-label="Close Repair Orders"></button></header>
        <form id="pv-orders-search-form" class="pv-orders-filters" data-no-spinner>
            <input type="search" id="pv-orders-search" class="form-control" placeholder="Search RO, vendor or WO" aria-label="Search Repair Orders" maxlength="100">
            <select id="pv-orders-status" class="form-select" aria-label="Repair Order status"><option value="">All statuses</option><option value="prepared">Prepared</option><option value="cancelled">Cancelled</option></select>
            <button type="submit" class="btn btn-outline-primary">Search</button>
        </form>
        <p id="pv-orders-message" role="status"></p>
        <div id="pv-orders-list" class="pv-orders-list" aria-label="Repair Order results"></div>
        <footer><span id="pv-orders-page"></span><div><button type="button" id="pv-orders-prev" class="btn btn-outline-primary" disabled>Previous</button><button type="button" id="pv-orders-next" class="btn btn-outline-primary" disabled>Next</button></div></footer>
    </dialog>
</section>
<script type="application/json" id="pv-data">{!! json_encode(['filters' => $filters, 'vendors' => $vendors, 'rows' => $rows, 'cache' => $cache, 'preferences' => $preferences, 'ordersUrl' => route('process-vendor.orders.index'), 'refreshUrl' => route('process-vendor.refresh'), 'queueUrl' => route('process-vendor.queue'), 'checkUrl' => route('process-vendor.check'), 'createUrl' => route('process-vendor.orders.store'), 'preferencesUrl' => route('process-vendor.preferences')], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
@endsection
@section('scripts')
<script src="{{ asset('js/process-vendor.js') }}?v={{ filemtime(public_path('js/process-vendor.js')) }}"></script>
@endsection
