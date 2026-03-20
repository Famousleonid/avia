{{-- Edit form partial for modal (AJAX load) --}}
<style>
    #editTdrProcessModalBody .select2-selection--single { min-height: 38px; }
    #editTdrProcessModalBody .select2-container { width: 100% !important; }
</style>
<div class="p-2">
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
                $isNdtProcess = $currentProcessName && strpos($currentProcessName->name, 'NDT-') === 0;
                $currentPlusProcessIds = !empty($currentPlusProcess) ? explode(',', $currentPlusProcess) : [];
            @endphp
            <form method="POST" action="{{ route('tdr-processes.update', $current_tdr_processes->id) }}" enctype="multipart/form-data" id="editCPForm">
                @csrf
                @method('PUT')
                <input type="hidden" name="tdrs_id" value="{{ $current_tdr->id }}">
                <input type="hidden" name="processes[0][plus_process]" id="plus_process_hidden" value="{{ $currentPlusProcess }}">

                <div id="processes-container" data-manual-id="{{ $current_tdr->workorder->unit->manual_id ?? '' }}">
                    <div class="process-row mb-3">
                        <div class="row">
                            <div class="col-md-3" style="width: 200px">
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
                            <div class="col-md-5">
                                <label for="process">Processes (Specification):</label>
                                <button type="button" class="btn btn-link mb-1" data-bs-toggle="modal" data-bs-target="#addProcessModal">
                                    <img src="{{ asset('img/plus.png') }}" alt="+" style="width: 20px;">
                                </button>
                                <div class="process-options">
                                    @if($current_tdr_processes->process_names_id)
                                        @php
                                            $currentProcesses = json_decode($current_tdr_processes->processes, true) ?: [];
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
                            <div class="col-md-4">
                                <div class="form-check mt-2" id="ec-checkbox-container" style="display: none;">
                                    <input type="checkbox" name="processes[0][ec]" value="1" class="form-check-input" id="ec_edit" {{ $current_tdr_processes->ec ? 'checked' : '' }}>
                                    <label class="form-check-label" for="ec_edit">EC</label>
                                </div>
                                <div>
                                    <label for="description" class="form-label" style="margin-bottom: -5px">Description</label>
                                    <input type="text" class="form-control" id="description" name="description" value="{{ old('description', $current_tdr_processes->description) }}" placeholder="Enter Description">
                                    <label for="notes" class="form-label" style="margin-bottom: -5px">Notes</label>
                                    <input type="text" class="form-control" id="notes" name="notes" value="{{ old('notes', $current_tdr_processes->notes) }}" placeholder="Enter Notes">
                                </div>
                            </div>
                        </div>
                    </div>
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
        currentProcesses: @json(json_decode($current_tdr_processes->processes, true) ?: []),
        dropdownParent: document.body
    };
    window.__editFormConfig = config;
})();
</script>
