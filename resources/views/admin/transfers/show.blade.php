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
                <a href="{{ route('tdrs.show', $workorder->id) }}"
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
                            showNotification('Failed to update Serial Number', 'error');
                        }
                    })
                    .catch(() => {
                        showNotification('Server error', 'error');
                    });
            });
        });
    </script>
@endsection

