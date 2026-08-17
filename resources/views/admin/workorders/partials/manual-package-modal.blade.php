<div class="modal fade" id="workorderManualsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable workorder-manuals-dialog" style="--bs-modal-width: 960px;">
        <div class="modal-content bg-dark text-light border-secondary">
            <div class="modal-header border-secondary">
                <div>
                    <h5 class="modal-title mb-0">{{ __('Workorder Manuals') }}</h5>
                    <div class="small text-secondary">W{{ $current_workorder->number }} · {{ $current_workorder->unit?->part_number }}</div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>

            <div class="modal-body" id="workorderManualPackage">
                <div class="alert alert-info py-2">
                    {{ __('Additional manuals come from the primary Manual/CMM. Their USED or NOT USED status applies only to this Workorder.') }}
                </div>

                <div class="table-responsive">
                    <table class="table table-dark table-bordered align-middle mb-0">
                        <thead>
                        <tr class="text-secondary small">
                            <th>{{ __('Manual') }}</th>
                            <th class="workorder-manual-title-column">{{ __('Title') }}</th>
                            <th style="width: 90px;">{{ __('Lib') }}</th>
                            <th style="width: 120px;">{{ __('Type') }}</th>
                            <th style="width: 120px;">{{ __('Status') }}</th>
                            @if($canUpdateWorkorderManuals)
                                <th class="text-end" style="width: 150px;">{{ __('Action') }}</th>
                            @endif
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($workorderManualPackage as $packageManual)
                            @php
                                $isPrimary = (int) $packageManual->id === (int) $current_workorder->unit?->manual_id;
                                $isUsed = $isPrimary || !in_array((int) $packageManual->id, $notUsedManualIds, true);
                            @endphp
                            <tr data-manual-id="{{ $packageManual->id }}">
                                <td><strong>{{ $packageManual->number }}</strong></td>
                                <td class="workorder-manual-title-column">{{ filled($packageManual->title) ? $packageManual->title : '—' }}</td>
                                <td class="text-secondary">{{ filled($packageManual->lib) ? $packageManual->lib : '—' }}</td>
                                <td>
                                    <span class="badge {{ $isPrimary ? 'bg-primary' : 'bg-secondary' }}">
                                        {{ $isPrimary ? __('Main') : __('Additional') }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge {{ $isUsed ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $isUsed ? __('USED') : __('NOT USED') }}
                                    </span>
                                </td>
                                @if($canUpdateWorkorderManuals)
                                    <td class="text-end">
                                        @if(!$isPrimary)
                                            <button type="button"
                                                    class="btn btn-sm {{ $isUsed ? 'btn-outline-warning' : 'btn-outline-success' }} workorder-manual-usage-btn"
                                                    data-manual-id="{{ $packageManual->id }}"
                                                    data-manual-number="{{ $packageManual->number }}"
                                                    data-next-used="{{ $isUsed ? '0' : '1' }}">
                                                {{ $isUsed ? __('Set NOT USED') : __('Set USED') }}
                                            </button>
                                        @else
                                            <span class="small text-secondary">{{ __('Always used') }}</span>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canUpdateWorkorderManuals ? 6 : 5 }}" class="text-center text-secondary py-3">
                                    {{ __('No manual assigned.') }}
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const packageEl = document.getElementById('workorderManualPackage');
        if (!packageEl) return;

        const notify = function (message, type) {
            if (typeof window.showNotification === 'function') {
                window.showNotification(message, type);
            }
        };

        const confirmed = async function (options) {
            if (typeof window.confirmDialog !== 'function') {
                notify('{{ __('Confirmation dialog is unavailable. No changes were made.') }}', 'error');
                return false;
            }

            return await window.confirmDialog(options);
        };

        const requestJson = async function (url, method, body) {
            const response = await fetch(url, {
                method: method,
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: JSON.stringify(body || {})
            });
            const data = await response.json().catch(function () { return {}; });

            if (!response.ok || !data.success) {
                throw new Error(data.message || '{{ __('Unable to update Workorder manuals.') }}');
            }

            return data;
        };

        packageEl.querySelectorAll('.workorder-manual-usage-btn').forEach(function (button) {
            button.addEventListener('click', async function () {
                const manualId = Number(button.dataset.manualId || 0);
                const manualNumber = button.dataset.manualNumber || '';
                const nextUsed = button.dataset.nextUsed === '1';
                const ok = await confirmed({
                    title: nextUsed ? '{{ __('Use manual?') }}' : '{{ __('Mark manual NOT USED?') }}',
                    message: nextUsed
                        ? manualNumber + ' {{ __('will be included in this Workorder STD and KIT forms.') }}'
                        : manualNumber + ' {{ __('will be omitted from this Workorder STD and KIT forms.') }}',
                    okText: nextUsed ? '{{ __('Set USED') }}' : '{{ __('Set NOT USED') }}',
                    cancelText: '{{ __('Cancel') }}',
                    danger: !nextUsed
                });
                if (!ok) return;

                button.disabled = true;
                try {
                    const data = await requestJson(
                        @json(route('workorders.manuals.usage', $current_workorder)),
                        'PATCH',
                        { manual_id: manualId, used: nextUsed }
                    );
                    notify(data.message || '{{ __('Workorder manuals updated.') }}', 'success');
                    window.location.reload();
                } catch (error) {
                    button.disabled = false;
                    notify(error.message || '{{ __('Unable to update Workorder manuals.') }}', 'error');
                }
            });
        });
    });
</script>
