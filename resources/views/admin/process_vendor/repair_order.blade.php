@extends('admin.master')
@section('style')
<style>
.content:has(.pv-ro-page) .content-inner{overflow:auto;min-height:0}.pv-ro-page{flex:0 0 auto;padding:24px;background:#edf1f6;color:#17233a;min-height:100%;height:auto}.pv-ro-tools{display:flex;align-items:center;gap:12px;max-width:1000px;margin:0 auto 20px;flex-wrap:wrap}.pv-ro-tools h1{font-size:22px;margin:0 auto 0 0}.pv-ro-notice{max-width:1000px;margin:0 auto 18px;font-size:13px}.pv-ro-paper{background:#fff;color:#111;max-width:1000px;margin:auto;padding:32px;box-shadow:0 8px 28px #14213b12}.pv-ro-paper table{width:100%;border-collapse:collapse}.pv-ro-paper td,.pv-ro-paper th{border:1px solid #222;padding:8px;vertical-align:top}.pv-ro-paper h2{font-size:26px;margin:0 0 10px}.pv-ro-paper p{margin:4px 0}.pv-ro-paper .ro-logo{width:250px;max-width:100%}.pv-ro-paper .ro-meta{font-size:12px}.pv-ro-paper .ro-items{font-size:12px;margin-top:-1px}.pv-ro-paper .ro-items th{font-size:11px}.pv-ro-paper .ro-items thead{display:table-header-group}.pv-ro-paper .ro-items tr{break-inside:avoid}.pv-ro-paper .ro-part{margin:8px 0;padding-left:10px;border-left:2px solid #ddd}.pv-ro-paper .ro-spec{white-space:pre-line;font-size:11px}.pv-ro-paper .ro-terms{font-size:10px;line-height:1.4;border:1px solid #222;padding:12px;margin-top:-1px}.pv-ro-paper .ro-terms p{margin-bottom:8px}.pv-ro-paper .ro-signature{border:1px solid #222;padding:20px 10px;margin-top:-1px;font-size:12px}.pv-ro-paper .ro-cancelled{color:#b91c1c;font-weight:bold;font-size:20px}.ro-address{white-space:pre-line}
@media(max-width:700px){.pv-ro-page{padding:12px}.pv-ro-paper{padding:12px}.pv-ro-paper h2{font-size:20px}.pv-ro-paper td{padding:6px;font-size:11px}.pv-ro-paper .ro-logo{width:170px}}
@media print{@page{size:letter;margin:12mm;
    @bottom-left{content:"RO: {{ $order->number }}";font-family:Arial,Helvetica,sans-serif;font-size:9pt;color:#111}
    @bottom-right{content:"Page " counter(page) " of " counter(pages);font-family:Arial,Helvetica,sans-serif;font-size:9pt;color:#111}
}html,body,.container-fluid,.page-layout,.content,.content-inner{height:auto!important;min-height:0!important;overflow:visible!important;background:#fff!important;display:block!important;width:auto!important;max-width:none!important;padding:0!important;margin:0!important}#sidebarColumn,footer,nav,.pv-ro-tools,.pv-ro-notice,.user-birthday-caption,body>.modal,body>.modal-backdrop{display:none!important}.pv-ro-page{padding:0;background:white}.pv-ro-paper{padding:0;box-shadow:none;max-width:none}.pv-ro-paper table{table-layout:fixed}.pv-ro-paper td{overflow-wrap:anywhere}.pv-ro-paper .ro-logo{width:190px}.pv-ro-paper h2{font-size:22px}.pv-ro-paper .ro-items{font-size:10px}.pv-ro-paper .ro-spec{font-size:9px}.pv-ro-paper .ro-terms{font-size:9px}.pv-ro-paper .ro-signature{break-inside:avoid}body:before,body:after{display:none!important}}
</style>
<style>
.pv-ro-paper{font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:1.4}.ro-brand{display:flex;align-items:center;gap:20px}.ro-brand .ro-logo{width:220px;max-width:45%}.ro-brand .ro-meta{font-size:11px}
@media print{html[data-bs-theme],html[data-bs-theme] body,html[data-bs-theme] .container-fluid,html[data-bs-theme] .page-layout,html[data-bs-theme] .content,html[data-bs-theme] .content-inner{background:white!important;background-image:none!important;color:#111!important;color-scheme:light!important}.pv-ro-page{min-height:0!important;background:white!important}#spinner-load,#birthdayBackgroundBanner,.status,.ai-chat-widget{display:none!important}.pv-ro-paper{font-size:11px;line-height:1.3}.ro-brand{gap:14px}.ro-brand .ro-logo{width:180px}.ro-brand .ro-meta,.pv-ro-paper .ro-meta{font-size:10px}.pv-ro-paper td,.pv-ro-paper th{padding:6px}.pv-ro-paper h2{font-size:20px}.pv-ro-paper .ro-terms{padding:9px;font-size:9px;line-height:1.35}.pv-ro-paper .ro-terms p{margin-bottom:6px}.pv-ro-paper .ro-signature{padding:14px 8px;font-size:10px}}
</style>
@endsection
@section('content')
@php($doc = $order->document)
@php($groups = collect($doc['items'])->groupBy(fn ($row) => $row['workorder_id'].'|'.$row['process_name']))
<section class="pv-ro-page">
    <div class="pv-ro-tools"><h1>{{ $order->number }}</h1><a href="{{ route('process-vendor.index') }}" class="btn btn-outline-primary">Back to queue</a><button class="btn btn-primary" id="pv-print">Print / Save PDF</button>@unless($order->cancelled_at)<button class="btn btn-outline-danger" id="pv-cancel" data-url="{{ route('process-vendor.orders.cancel', $order) }}">Cancel RO</button>@endunless</div>
    <p class="pv-ro-notice">{{ $order->cancelled_at ? 'Cancelled. The saved document is retained for history.' : 'Prepared for shipping. This document does not set process Sent dates. Prices and commercial details are not inferred from the sample.' }}</p>
    <article class="pv-ro-paper">
        <table><tr><td style="width:68%"><div class="ro-brand"><img class="ro-logo" src="{{ asset('img/icons/AT_logo-rb.svg') }}" alt="Aviatechnik"><div class="ro-meta">Aviatechnik Corporation<br>710 Gana Crt.<br>Mississauga, ON, Canada L5S 1P2<br>Ph: 905-806-7778</div></div></td><td><h2>Repair Order</h2><strong>Order #: {{ $order->number }}</strong><p class="ro-meta">RO Date: @projectDate($doc['date'])</p><p class="ro-meta"># of Items: {{ $groups->count() }}</p><p class="ro-meta">Part lines: {{ count($doc['items']) }}</p>@if($order->cancelled_at)<p class="ro-cancelled">CANCELLED</p>@endif</td></tr></table>
        <table style="margin-top:-1px"><tr><td style="width:50%"><strong>Ordered From:</strong><br>{{ $doc['vendor_name'] }}<div class="ro-address">{{ $doc['vendor_address'] }}</div>@if($doc['vendor_contact_name'] ?? '')<div>Attn: {{ $doc['vendor_contact_name'] }}</div>@endif @if($doc['vendor_email'] ?? '')<div>{{ $doc['vendor_email'] }}</div>@endif</td><td><strong>Ship To:</strong><br>Aviatechnik Corporation<br>710 Gana Crt.<br>Mississauga, ON L5S 1P2<br>Canada</td></tr></table>
        <table class="ro-meta" style="margin-top:-1px"><tr><td><p>Prepared By: {{ $doc['prepared_by'] }}</p><p>Ship Date: __________________</p><p>Ship Via: {{ $doc['ship_via'] ?: '__________________' }}</p></td><td><p>RO Date: @projectDate($doc['date'])</p><p>Terms: {{ $doc['terms'] ?: '__________________' }}</p><p>Warranty: __________________</p></td><td><p>Phone #: {{ ($doc['vendor_phone'] ?? '') ?: '__________________' }}</p><p>Vendor #: {{ ($doc['vendor_number'] ?? '') ?: '__________________' }}</p><p>FOB: __________________</p></td></tr></table>
        <p style="text-align:center;font-style:italic;font-weight:bold;border:1px solid #222;margin:-1px 0 0;padding:8px">All items subject to our inspection and acceptance</p>
        <table class="ro-items"><thead><tr><th style="width:6%">Item</th><th style="width:65%">Part Number / Description</th><th style="width:8%">Qty</th><th style="width:8%">UOM</th><th style="width:13%">Line Amt</th></tr></thead><tbody>
        @foreach($groups as $items)
        @php($groupNumber = $loop->iteration)
        @foreach($items as $item)
        <tr><td>{{ $groupNumber }}@if($items->count() > 1).{{ $loop->iteration }}@endif</td><td><strong>{{ $item['process_name'] }}</strong> · WO #: W{{ $item['wo'] }}
            <div class="ro-part"><strong>{{ $item['part_number'] }}</strong> · {{ $item['description'] }}<br>IPL {{ $item['ipl'] ?: '—' }}@if($item['serial']) · S/N {{ $item['serial'] }}@endif<br>CMM {{ $item['manual'] ?: '—' }}<div class="ro-spec">{{ $item['specification'] }}</div></div>
        </td><td>{{ $item['qty'] }}</td><td>EA</td><td>Not quoted</td></tr>
        @endforeach
        @endforeach
        </tbody></table>
        @if($doc['instructions'])<div class="ro-terms"><strong>Additional instructions</strong><p class="ro-address">{{ $doc['instructions'] }}</p></div>@endif
        <div class="ro-terms">
            <p>1. Evidence of Airworthiness - One of the following types of certification must accompany packing with each shipment:<br>a) FAA or Transport Canada Approved release statement.<br>b) FAA Form 8130-3<br>c) EASA Form 1 or JAA Form 1 dated prior to Nov 28, 2004<br>d) Transport Canada Form One</p>
            <p>2. Alteration - No change or modification of this order shall be allowed after its acceptance by the seller unless authorized in writing by the buyer.</p>
            <p>3. Seller's Conditions - No conditions laid down by seller in accepting or acknowledging this order shall be binding upon buyer if in conflict with any instructions or terms and/or condition herein, unless expressly accepted by the buyer in writing.</p>
            <p>4. Seller's Warranty - The seller warrants that all articles to be supplied under this purchase order shall be free from defects in materials, workmanship and design and shall conform strictly to the specifications, drawings or sample if any specified or furnished by the buyer.</p>
            <p>5. Packing Slip - Packing slip must accompany each shipment indicating purchase order number and/or release order(s) number and details of each item shipped.</p>
            <p>6. Shipping Instructions - Seller may ship by method shown in this purchase order. This is a condition precedent to the acceptance of the goods ordered. Failure to comply may result in back charges to the seller.</p>
            <p>7. Inspection &amp; Acceptance - All material shipped against this order is subject to final inspection and approval before acceptance.</p>
        </div>
        <div class="ro-signature">Authorized Signature: __________________________________ <span style="float:right">Total: Not quoted</span></div>
    </article>
</section>
@endsection
@section('scripts')
<script>
document.title = @json($order->number . ' · Repair Order');
document.getElementById('pv-print').addEventListener('click', () => window.print());
document.getElementById('pv-cancel')?.addEventListener('click', async function () {
    if (this.disabled) return;
    if (typeof window.confirmDialog !== 'function') { window.showNotification?.('Confirmation is unavailable. Reload the page.', 'error', 10000); return; }
    this.disabled = true;
    try {
        if (!await window.confirmDialog({ title: 'Cancel Repair Order', message: 'Cancel this prepared RO and release its parts for a new selection? The document remains in history.', okText: 'Cancel RO', danger: true })) return;
        const response = await fetch(this.dataset.url, { method:'POST', headers:{ Accept:'application/json', 'X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content } });
        if (!response.ok) throw new Error('Unable to cancel the RO. Please try again.');
        location.reload();
    } catch (error) { window.showNotification?.(error.message, 'error', 10000); }
    finally { this.disabled = false; }
});
</script>
@endsection
