{{-- Edit form partial for modal (AJAX load) --}}
<style>
    .part-process-editor .select2-selection--single { min-height: 38px; }
    .part-process-editor .select2-container { width: 100% !important; }
    .part-process-editor .card { min-height: calc(100dvh - 32px); }
    .part-process-editor .card-body,
    .part-process-editor form { display: flex; flex-direction: column; flex: 1; min-width: 0; }
    .part-process-editor .row > div { min-width: 0; }
    .part-process-editor .process-options { overflow-wrap: anywhere; }
    .part-process-editor .process-description-field { display: flex; flex-direction: column; flex: 1; }
    .part-process-editor textarea { min-height: 160px; flex: 1; resize: vertical; }
</style>
<div class="p-2 part-process-editor">
    <div class="card bg-gradient">
        <div class="card-header">
            <div class="d-flex justify-content-between">
                <h5 class="text-primary mb-0">{{ __('Edit Part Processes') }}</h5>
                <span class="pe-3">{{ __('W') }}{{ $current_tdr->workorder->number }}</span>
            </div>
            <small class="text-muted">{{ $current_tdr->component->name ?? 'N/A' }} | PN: {{ $current_tdr->component->part_number ?? 'N/A' }} | SN: {{ $current_tdr->serial_number ?? 'N/A' }}</small>
        </div>
        <div class="card-body">
            @php
                $currentPlusProcess = $current_tdr_processes->plus_process ?? '';
                $currentProcessName = $current_tdr_processes->processName;
                $isNdtProcess = $currentProcessName && strpos($currentProcessName->identityName(), 'NDT-') === 0;
                $processUsesNotes = $currentProcessName && !\App\Models\ProcessName::canPrintProcessForm($currentProcessName);
                $currentPlusProcessIds = !empty($currentPlusProcess) ? explode(',', $currentPlusProcess) : [];
            @endphp
            <form method="POST" action="{{ route('tdr-processes.update', $current_tdr_processes->id) }}" enctype="multipart/form-data" id="editCPForm">
                @csrf
                @method('PUT')
                <input type="hidden" name="tdrs_id" value="{{ $current_tdr->id }}">
                <input type="hidden" name="processes[0][plus_process]" id="plus_process_hidden" value="{{ $currentPlusProcess }}">

                <div id="processes-container" data-manual-id="{{ $current_tdr->workorder->unit->manual_id ?? '' }}">
                    <div class="process-row mb-3">
                        <div class="row g-3">
                            <div class="col-12 col-md-3">
                                <label for="process_names">Process Name:</label>
                                <select name="processes[0][process_names_id]" class="form-control select2-process" required>
                                    <option value=""></option>
                                    @foreach ($processNames as $processName)
                                        <option value="{{ $processName->id }}" {{ $current_tdr_processes->process_names_id == $processName->id ? 'selected' : '' }}>
                                            {{ $processName->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="process">Processes (Specification):</label>
                                <button type="button" class="btn btn-link mb-1" data-bs-toggle="modal" data-bs-target="#addProcessModal">
                                    <img src="{{ asset('img/plus.png') }}" alt="+" style="width: 20px;">
                                </button>
                                <div class="process-options">
                                    @if($current_tdr_processes->process_names_id)
                                        @php
                                            $currentProcesses = \App\Models\TdrProcess::normalizeStoredProcessIds($current_tdr_processes->processes);
                                            $currentProcessNameId = $current_tdr_processes->process_names_id;
                                            $firstProcessId = $currentProcesses[0] ?? null;
                                        @endphp
                                        @foreach ($processes as $process)
                                            @if($process->process_names_id == $currentProcessNameId)
                                                @php $isChecked = $process->id == $firstProcessId; @endphp
                                                <div class="form-check" data-process-name-id="{{ $process->process_names_id }}">
                                                    <input type="radio" name="processes[0][process][]" value="{{ $process->id }}" class="form-check-input" id="process_0_{{ $process->id }}" {{ $isChecked ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="process_0_{{ $process->id }}">{{ $process->process }}</label>
                                                </div>
                                            @endif
                                        @endforeach
                                    @endif
                                </div>
                                <div class="ndt-plus-process-container mt-3" style="display: {{ $isNdtProcess ? 'block' : 'none' }};">
                                    <label for="ndt_plus_process_0">Additional NDT Process(es):</label>
                                    <select name="processes[0][ndt_plus_process][]" class="form-control select2-ndt-plus" id="ndt_plus_process_0" data-row-index="0" multiple style="width: 100%; min-height: 70px;">
                                        @foreach ($ndtProcessNames as $ndtProcessName)
                                            <option value="{{ $ndtProcessName->id }}" data-process-name="{{ $ndtProcessName->name }}" {{ in_array((string)$ndtProcessName->id, $currentPlusProcessIds) ? 'selected' : '' }}>
                                                {{ $ndtProcessName->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="ndt-plus-process-options mt-2"></div>
                                </div>
                            </div>
                            <div class="col-12 col-md-3">
                                @if($currentProcessName && $currentProcessName->hasIdentity('EC') && $current_tdr_processes->standalone_ec_only)
                                    <input type="hidden" name="processes[0][standalone_ec_only]" value="1">
                                @endif
                                <div class="form-check mt-2" id="ec-checkbox-container" style="display: none;">
                                    <input type="checkbox" name="processes[0][ec]" value="1" class="form-check-input" id="ec_edit" {{ $current_tdr_processes->ec ? 'checked' : '' }}>
                                    <label class="form-check-label" for="ec_edit">EC</label>
                                </div>
                                <div>
                                    <div class="process-notes-field {{ $processUsesNotes ? '' : 'd-none' }}">
                                        <label for="notes" class="form-label" style="margin-bottom: -5px">Notes</label>
                                        <input type="text" class="form-control" id="notes" name="notes" value="{{ old('notes', $current_tdr_processes->notes) }}" placeholder="Enter Notes" @disabled(!$processUsesNotes)>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="process-description-field">
                    <label for="description" class="form-label">{{ __('Description') }}</label>
                    <textarea class="form-control" id="description" name="description" rows="6" maxlength="255" placeholder="{{ __('Enter Description') }}">{{ old('description', $current_tdr_processes->description) }}</textarea>
                </div>
                <div class="text-end mb-2 mt-3">
                    <button type="submit" class="btn btn-outline-primary" id="updateButton">{{ __('Update') }}</button>
                    <button type="button" class="btn btn-outline-secondary cancel-edit-process">{{ __('Cancel') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="addProcessModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Enter Process') }} (<span id="modalProcessName"></span>)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="newProcessInput" class="form-label">{{ __('New Process') }}</label>
                    <input type="text" class="form-control" id="newProcessInput" placeholder="{{ __('Enter new process') }}">
                </div>
                <input type="hidden" id="modalProcessNameId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-primary" id="saveProcessModal">{{ __('Save Process') }}</button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var config = {
        getProcessesUrl: '{{ route('processes.getProcesses') }}',
        processesStoreUrl: '{{ route('processes.store') }}',
        csrfToken: '{{ csrf_token() }}',
        ndtProcessNames: @json($ndtProcessNames->pluck('id')->toArray()),
        ndtProcessNamesData: @json($ndtProcessNames->keyBy('id')),
        ecEligibleProcessNameIds: @json($ecEligibleProcessNameIds ?? []),
        processNamesData: @json($processNames->keyBy('id')),
        machiningEcProcessNameIds: @json(\App\Models\ProcessName::identityIds('Machining (EC)')),
        currentProcesses: @json(\App\Models\TdrProcess::normalizeStoredProcessIds($current_tdr_processes->processes)),
        dropdownParent: document.body
    };
    window.__editFormConfig = config;
})();
</script>
