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
                {{-- Кнопка для исходящих трансферов (текущий WO - источник) --}}
                @if($hasOutgoingGroup)
                    <a href="{{ route('transfers.transfersForm', $workorder->id) }}"
                       class="btn btn-outline-info me-2"
                       target="_blank"
                       title="{{ __('Transfers Form for outgoing transfers from WO') }} W{{ $workorder->number }}">
                        {{ __('Transfers Form') }} (Outgoing)
                    </a>
                @endif
                {{-- Кнопки для входящих трансферов (текущий WO - получатель, группируем по источнику) --}}
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
                                    <th class="text-center text-primary">{{ __('Form') }}</th>
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
                                    <th class="text-center text-primary">{{ __('Form') }}</th>
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
                        <p class="text-muted">{{ __('No outgoing transfers for this Work Order.') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Change Serial Number -->
    <div class="modal fade" id="changeSnModal" tabindex="-1" aria-labelledby="changeSnModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content bg-gradient">
                <div class="modal-header">
                    <h5 class="modal-title" id="changeSnModalLabel">{{ __('Change Serial Number') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="changeSnForm">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" id="snTransferId" name="transfer_id">
                        <div class="mb-3">
                            <label for="component_sn" class="form-label">{{ __('Serial Number') }}</label>
                            <input type="text" class="form-control" id="component_sn" name="component_sn" maxlength="255">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let currentSnCell = null;

            document.querySelectorAll('.change-sn-link').forEach(function (link) {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    const transferId = this.dataset.transferId;
                    const currentSn = this.dataset.currentSn || '';

                    currentSnCell = this.closest('td');

                    document.getElementById('snTransferId').value = transferId;
                    document.getElementById('component_sn').value = currentSn;
                });
            });

            const form = document.getElementById('changeSnForm');
            form.addEventListener('submit', function (e) {
                e.preventDefault();

                const transferId = document.getElementById('snTransferId').value;
                const newSn = document.getElementById('component_sn').value;

                const url = "{{ route('transfers.updateSn', ['id' => '__ID__']) }}".replace('__ID__', transferId);

                fetch(url, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ component_sn: newSn })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            if (currentSnCell) {
                                if (data.component_sn) {
                                    currentSnCell.innerHTML = `<a href="#" class="text-decoration-underline text-info change-sn-link" data-transfer-id="${transferId}" data-current-sn="${data.component_sn}" data-bs-toggle="modal" data-bs-target="#changeSnModal">${data.component_sn}</a>`;
                                } else {
                                    currentSnCell.textContent = '-';
                                }
                            }
                            const modalEl = document.getElementById('changeSnModal');
                            const modalInstance = bootstrap.Modal.getInstance(modalEl);
                            modalInstance.hide();
                        } else {
                            alert('Failed to update Serial Number');
                        }
                    })
                    .catch(() => {
                        alert('Server error');
                    });
            });
        });
    </script>
@endsection


