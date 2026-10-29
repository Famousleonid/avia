{{-- modal Assembly --}}
<div class="offcanvas offcanvas-end text-bg-dark" tabindex="-1" id="assemblyCanvas">
    <div class="offcanvas-header border-bottom border-secondary">
        <h5 class="mb-0">Assembly</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <div class="text-muted small">Future data</div>
    </div>
</div>

<!--  Parts Modal -->
<template id="mainPartsLoadingTemplate">@include('admin.mains.partials.parts-loading')</template>
<div class="modal fade" id="partsModal{{$current_workorder->number}}" tabindex="-1"
     role="dialog" aria-labelledby="orderModalLabel{{$current_workorder->number}}" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable main-prl-dialog" role="document">
        <div class="modal-content bg-gradient">
            <div class="modal-header">
                <h4 class="modal-title" id="orderModalLabel{{ $current_workorder->number }}">Part Replacement List <span class="text-info">w{{ $current_workorder->number }}</span></h4>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3" data-parts-loading>@include('admin.mains.partials.parts-loading')</div>
        </div>
    </div>
</div>

<div class="modal fade" id="logCardTransferModal" tabindex="-1" aria-labelledby="logCardTransferTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content dir-modal">
            <div class="modal-header">
                <h5 class="modal-title" id="logCardTransferTitle">Transfer from Log Card</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="small mb-2" id="logCardTransferTarget"></p>
                <label for="logCardTransferSource" class="form-label">Source workorder</label>
                <div class="d-flex gap-2 mb-3">
                    <input type="text" id="logCardTransferSource" class="form-control" placeholder="w107736" autocomplete="off">
                    <button type="button" id="logCardTransferLoad" class="btn btn-outline-info text-nowrap">Load Log Card</button>
                </div>
                <label for="logCardTransferPart" class="form-label">Part from source Log Card — same manual</label>
                <select id="logCardTransferPart" class="form-select" size="6" aria-describedby="logCardTransferStatus"></select>
                <p id="logCardTransferStatus" class="small mt-2" role="status"></p>
                <p class="text-muted small mb-0">P/N and S/N are taken from the selected Log Card. Log Cards are not changed.</p>
                <a id="logCardTransferForm" class="btn btn-outline-info btn-sm mt-2 d-none" target="_blank">Transfer Form</a>
            </div>
            <div class="modal-footer">
                <button type="button" id="logCardTransferCancel" class="btn btn-outline-danger me-auto d-none">Cancel transfer</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" id="logCardTransferSave" class="btn btn-info" disabled>Create transfer</button>
            </div>
        </div>
    </div>
</div>

