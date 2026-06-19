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
                @if($hasOutgoingGroup)
                    <a href="{{ route('transfers.transfersForm', $workorder->id) }}"
                       class="btn btn-outline-info me-2"
                       target="_blank"
                       title="{{ __('Transfers Form for outgoing transfers from WO') }} W{{ $workorder->number }}">
                        {{ __('Transfers Form') }} (Outgoing)
                    </a>
                @endif
                @foreach($incomingGroupsWithMultiple as $sourceWoId => $transfers)
                    @php
                        $sourceWo = $transfers->first()->workorderSource;
                    @endphp
                    @if($sourceWo)
                        <a href="{{ route('transfers.transfersForm', $sourceWoId) }}"
                           class="btn btn-outline-info me-2"
                           target="_blank"
                           title="{{ __('Transfers Form for transfers from WO') }} W{{ $sourceWo->number }}">
                            {{ __('Transfers Form') }} (From W{{ $sourceWo->number }})
                        </a>
                    @endif
                @endforeach
                <a href="{{ route('tdrs.show', ['id' => $workorder->id]) }}"
                   class="btn btn-outline-secondary">
                    {{ __('Back to TDR') }}
                </a>
            </div>
        </div>

        <div class="card-body">
            @include('admin.transfers.partial')
        </div>
    </div>

    @include('admin.transfers.change-sn-modal')

    <script>
        @include('admin.transfers._sn-edit-script')
        @include('admin.transfers._unit-on-po-edit-script')
    </script>
@endsection

