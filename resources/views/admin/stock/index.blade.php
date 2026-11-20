@extends('admin.master')
@section('style')
<style>
.stock-page{height:100%;min-height:0;min-width:0;display:flex;flex-direction:column;gap:12px;padding:16px;overflow:hidden;color:var(--avia-text,#212529)}
.stock-page header,.stock-page form{display:flex;gap:12px;align-items:center;flex-wrap:wrap;flex-shrink:0}
.stock-page h1{font-size:20px;margin:0}.stock-updated{margin-left:auto;font-size:12px;color:var(--bs-secondary-color)}
.stock-page form{align-items:end}.stock-field{flex:1 1 220px;max-width:440px}.stock-field label{display:block;font-size:12px;margin-bottom:4px}
.stock-table-wrap{flex:1 1 0;min-height:0;min-width:0;overflow:auto;scrollbar-gutter:stable;border:1px solid var(--avia-border,#dee2e6)}
.stock-page table{width:100%;min-width:660px;border-spacing:0;border-collapse:separate;font-size:13px}
.stock-page th,.stock-page td{padding:9px 12px;border-bottom:1px solid var(--avia-border,#dee2e6);vertical-align:top}
.stock-page thead{position:sticky;top:0;z-index:1;background:var(--avia-surface-raised,#f8f9fa)}
.stock-page th{background:var(--avia-surface-raised,#f8f9fa)}.stock-qty{text-align:right;white-space:nowrap}.stock-pn{white-space:nowrap}
.stock-footer{flex-shrink:0;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;font-size:13px}.stock-footer .pagination{margin:0}
.stock-reservations{margin:auto;position:fixed;inset:0;width:min(520px,calc(100vw - 24px));max-height:80vh;padding:0;border:1px solid var(--avia-border,#dee2e6);border-radius:8px;background:var(--avia-surface-raised,#fff);color:var(--avia-text,#212529)}
.stock-reservations::backdrop{background:rgba(0,0,0,.5)}.stock-reservations[open]{display:flex;flex-direction:column}.stock-reservations header{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px;flex-shrink:0}.stock-reservations h2{font-size:18px;margin:0}.stock-reservation-body{overflow:auto;min-height:0;padding:0 16px 16px}.stock-reservations table{width:100%;border-collapse:collapse}.stock-reservations th,.stock-reservations td{padding:8px;border-bottom:1px solid var(--avia-border,#dee2e6)}.stock-reservations p{margin:10px 0}
@media(max-width:600px){.stock-page{padding:8px}.stock-updated{margin-left:0}.stock-field{max-width:none}}
</style>
@endsection
@section('content')
<div class="stock-page">
    <header><h1>Stock</h1><span id="stockCounts" class="small text-info">Total: {{ number_format($snapshot['row_count']) }} · Filtered: {{ number_format($rows->total()) }}</span><span class="small text-body-secondary">Daily update · 05:00 bridge time</span><span class="stock-updated">@if($snapshot['exported_at'])Updated {{ \Carbon\CarbonImmutable::parse($snapshot['exported_at'])->timezone('America/Toronto')->format('d/M/Y H:i') }} (Toronto)@else No Quantum snapshot received @endif</span></header>
    <form method="get" action="{{ route('stock.index') }}" data-no-spinner>
        <div class="stock-field"><label for="stockQuery">P/N or Description</label><input id="stockQuery" name="q" class="form-control" value="{{ $query }}" maxlength="255" autocomplete="off" placeholder="Filter P/N or Description"></div>
        <label class="form-check-label mb-2"><input type="checkbox" id="stockNonzero" name="nonzero" value="1" class="form-check-input me-2" @checked($nonzero)> Only nonzero on hand</label>
    </form>
    <div class="stock-table-wrap" tabindex="0" aria-label="Quantum stock">
        <table><thead><tr><th>P/N</th><th>Description</th><th class="stock-qty">On hand</th><th class="stock-qty">Reserved</th><th class="stock-qty">Available</th></tr></thead>
        <tbody id="stockRows">@include('admin.stock.rows')</tbody></table>
    </div>
    <footer class="stock-footer"><span id="stockLoadState" role="status"></span><button id="stockRetry" type="button" class="btn btn-outline-secondary btn-sm" hidden>Retry</button></footer>
</div>
<dialog id="stockReservations" class="stock-reservations" aria-labelledby="stockReservationsTitle">
    <header><h2 id="stockReservationsTitle">Reserved</h2><button type="button" class="btn btn-outline-secondary btn-sm" id="stockReservationsClose">Close</button></header>
    <div class="stock-reservation-body"><p id="stockReservationsSummary"></p><table><thead><tr><th>Workorder</th><th class="text-end">Quantity</th></tr></thead><tbody id="stockReservationsRows"></tbody></table><p id="stockReservationsNote"></p></div>
</dialog>
@endsection
@section('scripts')
<script>
(() => {
    const page=document.querySelector('.stock-page');
    const input=document.getElementById('stockQuery'), checkbox=document.getElementById('stockNonzero'),
        scroller=page.querySelector('.stock-table-wrap'), rows=document.getElementById('stockRows'),
        counts=document.getElementById('stockCounts'), status=document.getElementById('stockLoadState'), retry=document.getElementById('stockRetry');
    let next=@json($rows->hasMorePages() ? $rows->currentPage()+1 : null), snapshot=@json($snapshot['exported_at']),
        generation=0, controller, timer, loading=false, failed=false, pendingReset=false;
    const nearEnd=()=>scroller.scrollHeight-scroller.scrollTop-scroller.clientHeight<250;
    async function load(reset=false){
        if(loading || (!reset && (!next || failed)))return;
        const version=generation;
        controller=new AbortController();loading=true;retry.hidden=true;status.textContent='Loading…';
        const url=new URL(@json(route('stock.index')),location.href);
        if(input.value.trim())url.searchParams.set('q',input.value.trim());
        if(checkbox.checked)url.searchParams.set('nonzero','1');
        const requestedPage=reset?1:next;url.searchParams.set('page',requestedPage);
        try{
            const response=await fetch(url,{signal:controller.signal,headers:{Accept:'application/json','X-No-Spinner':'1'}});
            if(!response.ok)throw new Error('Unable to load Stock.');
            const result=await response.json();if(version!==generation)return;
            if(!reset && snapshot!==result.snapshot){loading=false;generation++;pendingReset=true;rows.replaceChildren();scroller.scrollTop=0;load(true);return;}
            if(reset){rows.innerHTML=result.html;scroller.scrollTop=0;}else rows.insertAdjacentHTML('beforeend',result.html);
            snapshot=result.snapshot;next=result.next;pendingReset=false;failed=false;
            page.querySelector('.stock-updated').textContent=result.updated;
            counts.textContent=`Total: ${Number(result.total).toLocaleString()} · Filtered: ${Number(result.filtered).toLocaleString()}`;
            status.textContent=next?'':'All matching rows loaded';
            url.searchParams.delete('page');history.replaceState(null,'',url);
        }catch(error){if(version===generation && error.name!=='AbortError'){failed=true;status.textContent='Could not load rows. Retry.';retry.hidden=false;}}
        finally{if(version===generation){loading=false;if(!failed && next && nearEnd())load(false);}}
    }
    function filter(){
        clearTimeout(timer);generation++;controller?.abort();loading=false;failed=false;pendingReset=true;
        next=null;rows.replaceChildren();scroller.scrollTop=0;retry.hidden=true;counts.textContent='Total: {{ number_format($snapshot["row_count"]) }} · Filtered: …';status.textContent='Filtering…';
        timer=setTimeout(()=>load(true),250);
    }
    input.addEventListener('input',filter);checkbox.addEventListener('change',filter);
    page.querySelector('form').addEventListener('submit',event=>{event.preventDefault();filter();});
    scroller.addEventListener('scroll',()=>{if(nearEnd())load(false);},{passive:true});
    retry.addEventListener('click',()=>{failed=false;load(pendingReset);});
    if(next && nearEnd())load(false);

    const dialog=document.getElementById('stockReservations');
    document.getElementById('stockReservationsClose').addEventListener('click',()=>dialog.close());
    page.addEventListener('click',event=>{
        const control=event.target.closest('.stock-reserved'); if(!control)return;

        const data=JSON.parse(control.dataset.reservations), body=document.getElementById('stockReservationsRows');
        document.getElementById('stockReservationsTitle').textContent=`Reserved — ${data.pn}`;
        document.getElementById('stockReservationsSummary').textContent=`Reserved: ${data.reserved}`;
        body.replaceChildren(); let allocated=0;
        for(const item of data.links??[]){
            const tr=document.createElement('tr'), wo=document.createElement('td'), qty=document.createElement('td');
            wo.textContent=item.wo;qty.textContent=Number(item.quantity).toLocaleString(undefined,{maximumFractionDigits:4});qty.className='text-end';tr.append(wo,qty);body.append(tr);allocated+=Number(item.quantity);
        }
        const notes=[];
        if(data.links===null)notes.push('Reservation links are not in this snapshot. They will arrive with the next scheduled update.');
        else {
            if(data.unassigned)notes.push(`Without a confirmed WO link: ${data.unassigned}.`);
            const difference=Number(data.reserved)-allocated-Number(data.unassigned);
            if(Math.abs(difference)>0.0001)notes.push(`Difference from the Quantum reserved balance: ${Number(difference.toFixed(4))}.`);
            if(!data.links.length)notes.push('No linked workorders in this snapshot.');
        }
        document.getElementById('stockReservationsNote').textContent=notes.join(' ');
        dialog.showModal();
    });
})();
</script>
@endsection
