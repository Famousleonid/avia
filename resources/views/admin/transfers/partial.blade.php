<div class="transfers-partial">
    <div class="row">
        <div class="col-md-6">
            <h5 class="text-primary mb-3">{{ __('Incoming Transfers') }}</h5>

            @if($incomingTransfers->count())
                <div class="table-responsive" style="max-height: calc(100vh - 320px); overflow-y: auto;">
                    <table class="table table-bordered table-hover dir-table align-middle bg-gradient w-100">
                        <thead class="table-dark" style="position: sticky; top: 0; z-index: 5;">
                        <tr>
                            <th class="text-primary text-center">{{ __('From WO') }}</th>
                            <th class="text-primary text-center">{{ __('Part') }}</th>
                            <th class="text-primary text-center">{{ __('Part Number') }}</th>
                            <th class="text-primary text-center">{{ __('SN') }}</th>
                            <th class="text-primary text-center">{{ __('Reason') }}</th>
                            <th class="text-primary text-center">{{ __('Form') }}</th>
                            <th class="text-primary text-center">{{ __('Created At') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($incomingTransfers as $transfer)
                            <tr>
                                <td class="text-center">
                                    {{ optional($transfer->workorderSource)->number ?? '-' }}
                                </td>
                                <td class="text-center">
                                    {{ optional($transfer->component)->name ?? '-' }}
                                </td>
                                <td class="text-center">
                                    {{ optional($transfer->component)->part_number ?? '-' }}
                                </td>
                                <td class="text-center">
                                    @if($transfer->component_sn)
                                        <a href="#"
                                           class="text-decoration-underline text-info change-sn-link"
                                           data-transfer-id="{{ $transfer->id }}"
                                           data-current-sn="{{ $transfer->component_sn }}"
                                           data-bs-toggle="modal"
                                           data-bs-target="#changeSnModal">
                                            {{ $transfer->component_sn }}
                                        </a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-center">
                                    {{ optional($transfer->reasonCode)->name ?? '-' }}
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('transfers.transferForm', $transfer->id) }}"
                                       class="btn btn-outline-info btn-sm"
                                       target="_blank">
                                        {{ __('Form') }}
                                    </a>
                                </td>
                                <td class="text-center">
                                    {{ optional($transfer->created_at)->format('Y-m-d H:i') }}
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-center text-muted mt-3">{{ __('No incoming transfers for this Work Order.') }}</p>
            @endif
        </div>

        <div class="col-md-6">
            <h5 class="text-primary mb-3">{{ __('Outgoing Transfers') }}</h5>

            @if($outgoingTransfers->count())
                <div class="table-responsive" style="max-height: calc(100vh - 320px); overflow-y: auto;">
                    <table class="table table-bordered table-hover dir-table align-middle bg-gradient w-100">
                        <thead class="table-dark" style="position: sticky; top: 0; z-index: 5;">
                        <tr>
                            <th class="text-primary text-center">{{ __('To WO') }}</th>
                            <th class="text-primary text-center">{{ __('Part') }}</th>
                            <th class="text-primary text-center">{{ __('Part Number') }}</th>
                            <th class="text-primary text-center">{{ __('SN') }}</th>
                            <th class="text-primary text-center">{{ __('Reason') }}</th>
                            <th class="text-primary text-center">{{ __('Form') }}</th>
                            <th class="text-primary text-center">{{ __('Created At') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($outgoingTransfers as $transfer)
                            <tr>
                                <td class="text-center">
                                    {{ optional($transfer->workorder)->number ?? '-' }}
                                </td>
                                <td class="text-center">
                                    {{ optional($transfer->component)->name ?? '-' }}
                                </td>
                                <td class="text-center">
                                    {{ optional($transfer->component)->part_number ?? '-' }}
                                </td>
                                <td class="text-center">
                                    @if($transfer->component_sn)
                                        <a href="#"
                                           class="text-decoration-underline text-info change-sn-link"
                                           data-transfer-id="{{ $transfer->id }}"
                                           data-current-sn="{{ $transfer->component_sn }}"
                                           data-bs-toggle="modal"
                                           data-bs-target="#changeSnModal">
                                            {{ $transfer->component_sn }}
                                        </a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-center">
                                    {{ optional($transfer->reasonCode)->name ?? '-' }}
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('transfers.transferForm', $transfer->id) }}"
                                       class="btn btn-outline-info btn-sm"
                                       target="_blank">
                                        {{ __('Form') }}
                                    </a>
                                </td>
                                <td class="text-center">
                                    {{ optional($transfer->created_at)->format('Y-m-d H:i') }}
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-center text-muted mt-3">{{ __('No outgoing transfers for this Work Order.') }}</p>
            @endif
        </div>
    </div>
</div>
