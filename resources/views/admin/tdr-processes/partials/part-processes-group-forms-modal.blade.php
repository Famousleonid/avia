{{-- Модалка Group Process Forms для вкладки Part Processes (TDR show); id уникален, чтобы не конфликтовать с #groupFormsModal на других вкладках. --}}
@php
    $processGroupsForModal = collect($processGroups ?? [])->filter(function ($g) {
        return (int) ($g['count'] ?? 0) > 1;
    })->all();
@endphp
@if(isset($processGroupsForModal) && count($processGroupsForModal) > 0 && isset($current_tdr) && $current_tdr->workorder)
    <div class="modal fade" id="partProcessesGroupFormsModal" tabindex="-1" aria-labelledby="partProcessesGroupFormsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="partProcessesGroupFormsModalLabel">
                        <i class="fas fa-print"></i> {{ __('Group Process Forms') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <p class="text-muted mb-3">
                                <i class="fas fa-info-circle"></i>
                                {{ __('Select a process type to generate a grouped form. Each process type can have its own vendor and process selection.') }}
                            </p>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover table-bordered bg-gradient shadow dir-table">
                            <thead>
                            <tr>
                                <th class="text-primary ps-2" style="width: 15%;">{{ __('Process') }}</th>
                                <th class="text-primary text-center" style="width: 45%;">{{ __('Processes') }}</th>
                                <th class="text-primary text-center" style="width: 20%;">{{ __('Vendor') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($processGroupsForModal as $groupKey => $group)
                                @php
                                    $actualProcessNameId = $group['representative_process_name_id'] ?? $groupKey;
                                    $displayName = $group['process_name']->name ?? 'N/A';
                                @endphp
                                <tr>
                                    <td class="align-middle">
                                        <div class="position-relative d-inline-block ms-5">
                                            <x-paper-button
                                                text="{{ $displayName }} "
                                                size="landscape"
                                                width="120px"
                                                href="{{ route('tdrs.show_group_forms', ['id' => $current_tdr->workorder->id, 'processNameId' => $actualProcessNameId, 'tdrId' => $current_tdr->id]) }}"
                                                target="_blank"
                                                fontSize="30px"
                                                class="group-form-button"
                                                data-process-name-id="{{ $actualProcessNameId }}"
                                            ></x-paper-button>

                                            <span class="badge bg-success mt-1 ms-1 process-qty-badge"
                                                  data-process-name-id="{{ $actualProcessNameId }}"
                                                  style="position: absolute; top: -5px; left: 5px; min-width: 20px;
                                                  height: 30px; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; padding: 0 5px;">
                                                {{ $group['qty'] }} pcs
                                            </span>
                                        </div>
                                    </td>
                                    <td class="align-middle">
                                        <div class="process-checkboxes" data-process-name-id="{{ $actualProcessNameId }}">
                                            @foreach($group['processes'] as $processItem)
                                                <div class="form-check">
                                                    <input class="ms-1 form-check-input process-checkbox"
                                                           type="checkbox"
                                                           value="{{ $processItem['id'] }}"
                                                           id="pp_gf_{{ $actualProcessNameId }}_{{ $processItem['tdr_process_id'] }}_{{ $processItem['id'] }}"
                                                           data-process-name-id="{{ $actualProcessNameId }}"
                                                           data-qty="{{ $processItem['qty'] }}"
                                                           data-tdr-process-id="{{ $processItem['tdr_process_id'] }}"
                                                           checked>
                                                    <label class="form-check-label" for="pp_gf_{{ $actualProcessNameId }}_{{ $processItem['tdr_process_id'] }}_{{ $processItem['id'] }}">
                                                        <strong>{{ $processItem['name'] }}</strong>@if($processItem['ec']) (EC)@endif
                                                        <span class="">Qty: {{ $processItem['qty'] }}</span>
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="align-middle">
                                        <select class="form-select vendor-select"
                                                data-process-name-id="{{ $actualProcessNameId }}"
                                                style="font-size: 0.9rem;">
                                            <option value="">{{ __('No vendor') }}</option>
                                            @foreach($vendors as $vendor)
                                                <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
