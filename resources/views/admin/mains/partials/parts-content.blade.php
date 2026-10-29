@php $canEditPartReceipts = auth()->user()?->hasAnyRole('Admin|Manager'); @endphp
            <div class="modal-header">
                <h4 class="modal-title" id="orderModalLabel{{ $current_workorder->number }}">Part Replacement List <span class="text-info">w{{ $current_workorder->number }}</span></h4>
                <small class="main-prl-counts">Total rows: <span class="main-prl-total-count">{{ $orderedQty }}</span> / <span class="main-prl-status-label">Received</span>: <span class="main-prl-green-count">{{ $receivedQty }}</span></small>
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
                                           data-iso-value="{{ $part->received ? \Carbon\Carbon::parse($part->received)->format('Y-m-d') : '' }}"
                                           value="{{ format_project_date($part->received) }}">
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

