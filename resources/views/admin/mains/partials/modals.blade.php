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
@php
    $canEditPartReceipts = auth()->user()?->hasAnyRole('Admin|Manager');
@endphp
<div class="modal fade" id="partsModal{{$current_workorder->number}}" tabindex="-1"
     role="dialog" aria-labelledby="orderModalLabel{{$current_workorder->number}}" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable main-prl-dialog" role="document">
        <div class="modal-content bg-gradient">
            <div class="modal-header">
                <h4 class="modal-title" id="orderModalLabel{{ $current_workorder->number }}">Part Replacement List <span class="text-info">w{{ $current_workorder->number }}</span></h4>
                <small class="main-prl-counts">Total rows: <span class="main-prl-total-count">{{ $prl_parts->count() }}</span> / <span class="main-prl-status-label">Received</span>: <span class="main-prl-green-count">{{ $prl_parts->filter(fn ($part) => !$part->crossed_out && !empty($part->received) && trim((string) $part->po_num) !== '' && $part->received_qty !== null && (int) $part->received_qty === (int) $part->qty)->count() }}</span></small>
                <input type="search" class="form-control form-control-sm main-prl-search" aria-label="Search IPL, Part Number or Description" placeholder="Search IPL / P/N / Description" autocomplete="off">
            <fieldset class="main-prl-filters m-0">
                <legend class="visually-hidden">Receipt status</legend>
                @foreach(['all' => 'All', 'received' => 'Received', 'pending' => 'Pending'] as $filter => $label)
                    <label class="form-check form-check-inline mb-0">
                        <input class="form-check-input main-prl-filter" type="radio" name="partsReceiptStatus{{ $current_workorder->number }}" value="{{ $filter }}" @checked($filter === 'all')>
                        <span class="form-check-label">{{ $label }}</span>
                    </label>
                @endforeach
            </fieldset>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-2">
            @if(count($prl_parts))
                <div class="table-responsive main-prl-table-wrap">
                    <table class="table table-sm table-hover table-bordered align-middle dir-table mb-0">
                        <thead>
                        <tr>
                            <th class="" data-direction="asc">{{__('IPL')}}</th>
                            <th class=""
                                data-direction="asc">{{__('Part Description') }}</th>
                            <th class="" style="width: 250px;"
                                data-direction="asc">{{__('Part Number')}}</th>
                            <th class="main-prl-qty" data-direction="asc">{{__('QTY')}}</th>
                            <th class="main-prl-qty" title="Received quantity">Rec.<br>QTY</th>
                            <th class="">{{__('PO NO.')}} </th>
                            <th class="">{{__('Received')}}</th>
                        </tr>
                        </thead>
                        <tbody>

                        @foreach($prl_parts as $part)
                            @php
                                $currentComponent = $part->orderComponent ?? $part->component;
                            @endphp
                            <tr data-part-kind="{{ $currentComponent?->kit ? 'kit' : 'part' }}" data-crossed-out="{{ $part->crossed_out ? 1 : 0 }}" data-legacy-tdr="{{ $part->tdr_id }}" data-transfer-id="{{ $part->transfer_id }}" class="{{ $part->crossed_out ? 'text-muted main-prl-crossed' : '' }}">

                                <td class="" style="width: 100px"> <span class="main-prl-multiline">{{$currentComponent->ipl_num ?? ''}}</span> </td>
                                <td class="" style="width: 250px">@if($currentComponent?->kit)<span class="badge bg-info text-dark me-1 main-prl-kit">KIT</span>@endif <span class="main-prl-multiline">{{$currentComponent->name ?? ''}}</span> </td>
                                <td class="" style="width: 120px;"> @if($part->options->isNotEmpty())
                                    @foreach($part->options as $option)
                                        <div class="{{ !empty($option['crossed_out']) ? 'text-decoration-line-through text-muted' : '' }}">{{ $option['part_number'] }}@if(isset($option['qty'])) <small>({{ $option['qty'] }})</small>@endif</div>
                                    @endforeach
                                @else {{$currentComponent->part_number ?? ''}} @endif </td>
                                <td class="main-prl-qty"> {{$part->qty}} </td>
                                <td class="main-prl-qty">
                                    @if(!$part->crossed_out)
                                    <input type="number" class="form-control form-control-sm received-qty" min="0" max="{{ (int) $part->qty }}" step="1" aria-label="Received quantity"
                                           @disabled(!$canEditPartReceipts) data-tdrs-id="{{ $part->id }}" data-workorder-number="{{ $current_workorder->number }}"
                                           data-saved-qty="{{ $part->received_qty }}" value="{{ $part->received_qty }}">
                                    @endif
                                </td>
                                <td class="" style="width: 150px;">
                                    @if(!$part->crossed_out)
                                    <div class="po-no-container">
                                        <select class="form-select form-select-sm po-no-select"
                                                @disabled(!$canEditPartReceipts)
                                                data-saved-po="{{ $part->po_num }}"
                                                data-tdrs-id="{{ $part->id }}"
                                                data-workorder-number="{{ $current_workorder->number }}"
                                                style="width: 100%;">
                                            <option
                                                value="Customer" {{ $part->po_num === 'Customer' ? 'selected' : '' }}>
                                                Customer
                                            </option>
                                            <option
                                                value="Transfer from WO" {{ $part->po_num && \Illuminate\Support\Str::startsWith($part->po_num, 'Transfer from WO') ? 'selected' : '' }}>
                                                Transfer from WO
                                            </option>
                                            <option
                                                value="INPUT" {{ !$part->po_num || !\Illuminate\Support\Str::startsWith($part->po_num, ['Customer', 'Transfer from WO']) ? 'selected' : '' }}>
                                                PO No.
                                            </option>
                                        </select>
                                        <input type="text"
                                               class="form-control form-control-sm po-no-input"
                                               @disabled(!$canEditPartReceipts)
                                               data-tdrs-id="{{ $part->id }}"
                                               data-workorder-number="{{ $current_workorder->number }}"
                                               placeholder="Po No."
                                               value="{{ $part->po_num && !\Illuminate\Support\Str::startsWith($part->po_num, ['Customer', 'Transfer from WO']) ? $part->po_num : '' }}"
                                               style="display: {{ !$part->po_num || !\Illuminate\Support\Str::startsWith($part->po_num, ['Customer', 'Transfer from WO']) ? 'block' : 'none' }};">
                                        <button type="button" @disabled(!$canEditPartReceipts) class="btn btn-outline-info btn-sm prl-transfer-details" style="display: {{ $part->transfer_id ? 'block' : 'none' }}" title="View or cancel transfer">Transfer</button>
                                    </div>
                                    @else <span class="text-muted">Excluded</span> @endif
                                </td>
                                <td class="" style="width: 150px;">
                                    @if(!$part->crossed_out)
                                    <input type="text"
                                           placeholder="dd/Mmm/yyyy"
                                           class="form-control form-control-sm received-date"
                                           @disabled(!$canEditPartReceipts)
                                           data-tdrs-id="{{ $part->id }}"
                                           data-workorder-number="{{ $current_workorder->number }}"
                                           value="{{ $part->received ? \Carbon\Carbon::parse($part->received)->format('Y-m-d') : '' }}">
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <h5 class="text-center mt-3 mb-3 text-primary">{{__('No Ordered Parts')}}</h5>
            @endif
            </div>

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

{{-- LOG MODAL --}}
<div class="modal fade" id="logModal" tabindex="-1"
     aria-labelledby="logModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="background-color:var(--avia-modal);color:var(--avia-text);">
            <div class="modal-header">
                <h5 class="modal-title" id="logModalLabel">Activity log</h5>
                <button type="button" class="btn-close btn-close-white"
                        data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="logModalContent">
                    {{-- сюда подставится список логов --}}
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

