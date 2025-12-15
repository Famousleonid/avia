@extends('admin.master')

@section('content')
    <div class="card bg-gradient">
        <div class="card-header m-1 shadow d-flex justify-content-between align-items-center">
            <div>
                <h4 class="text-primary mb-0">{{ __('Transfers') }}</h4>
            </div>
            <div class="d-flex align-items-center">
                <div class="me-3">
                    <span class="text-white-50">{{ __('Work Order') }}:</span>
                    <span class="text-success fw-bold">W{{ $workorder->number }}</span>
                </div>
                <a href="{{ route('tdrs.show', $workorder->id) }}"
                   class="btn btn-outline-secondary">
                    {{ __('Back to TDR') }}
                </a>
            </div>
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="text-primary mb-3">{{ __('Incoming Transfers') }}</h5>

                    @if($incomingTransfers->count())
                        <div class="table-wrapper">
                            <table class="display table table-sm table-hover table-striped align-middle table-bordered bg-gradient">
                                <thead>
                                <tr>
                                    <th class="text-center text-primary">{{ __('From WO') }}</th>
                                    <th class="text-center text-primary">{{ __('Component') }}</th>
                                    <th class="text-center text-primary">{{ __('Part Number') }}</th>
                                    <th class="text-center text-primary">{{ __('SN') }}</th>
                                    <th class="text-center text-primary">{{ __('Reason') }}</th>
                                    <th class="text-center text-primary">{{ __('Created At') }}</th>
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
                                            {{ $transfer->component_sn ?? '-' }}
                                        </td>
                                        <td class="text-center">
                                            {{ optional($transfer->reasonCode)->name ?? '-' }}
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
                        <p class="text-muted">{{ __('No incoming transfers for this Work Order.') }}</p>
                    @endif
                </div>

                <div class="col-md-6">
                    <h5 class="text-primary mb-3">{{ __('Outgoing Transfers') }}</h5>

                    @if($outgoingTransfers->count())
                        <div class="table-wrapper">
                            <table class="display table table-sm table-hover table-striped align-middle table-bordered bg-gradient">
                                <thead>
                                <tr>
                                    <th class="text-center text-primary">{{ __('To WO') }}</th>
                                    <th class="text-center text-primary">{{ __('Component') }}</th>
                                    <th class="text-center text-primary">{{ __('Part Number') }}</th>
                                    <th class="text-center text-primary">{{ __('SN') }}</th>
                                    <th class="text-center text-primary">{{ __('Reason') }}</th>
                                    <th class="text-center text-primary">{{ __('Created At') }}</th>
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
                                            {{ $transfer->component_sn ?? '-' }}
                                        </td>
                                        <td class="text-center">
                                            {{ optional($transfer->reasonCode)->name ?? '-' }}
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
                        <p class="text-muted">{{ __('No outgoing transfers for this Work Order.') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection


