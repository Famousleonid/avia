<div class="rm-reports-partial">
    <style>
        .rm-reports-partial {
            height: 100%;
            min-height: 0;
            overflow: hidden;
        }
        .rm-reports-partial > .row {
            height: 100%;
            min-height: 0;
        }
        .rm-reports-partial > .row > [class*="col-"] {
            display: flex;
            height: 100%;
            max-height: 100%;
            min-height: 0;
        }
        .rm-reports-partial .card {
            height: 100% !important;
            max-height: 100%;
            min-height: 0;
            width: 100%;
        }
        .rm-reports-partial .rm-preview-card-body,
        .rm-reports-partial .rm-editor-card-body {
            flex: 1 1 auto;
            height: 100%;
            max-height: 100%;
            min-height: 0;
            overflow-y: scroll;
            overflow-x: hidden;
        }
        .table-scroll-rm-records {
            overflow-y: auto;
            overflow-x: auto;
            position: relative;
        }
        .table-scroll-technical-notes {
            max-height: 18vh;
            overflow-y: auto;
            overflow-x: auto;
            position: relative;
        }
        .table-scroll-rm-records thead th,
        .table-scroll-technical-notes thead th {
            position: sticky;
            top: 0;
            background-color: #031e3a !important;
            z-index: 10;
            box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.4);
            align-content: center;
            font-size: small;
        }
        .table-scroll-rm-records table,
        .table-scroll-technical-notes table { margin-bottom: 0; }
        .rm-records-table-right {
            font-size: 12px;
        }
        .rm-records-table-right th,
        .rm-records-table-right td {
            font-size: 12px;
        }
        .rm-reports-partial .rm-section-title {
            font-size: 1rem;
            line-height: 1.15;
        }
        .rm-assy-conversion-summary {
            display: block;
            margin-top: .2rem;
            color: var(--bs-info);
            font-size: 11px;
            line-height: 1.25;
        }
        .rm-assy-conversion-fields[hidden] { display: none !important; }
        .table-scroll-technical-notes table { border-collapse: separate; border-spacing: 0; }
        .table-scroll-technical-notes tbody tr td {
            padding: 0.25rem 0.5rem;
            line-height: 1.2;
            vertical-align: middle;
        }
        .preview-papyrus {
            color: #000;
            background-color: #f4e4bc;
            background-image:
                linear-gradient(rgba(139, 119, 101, 0.06) 1px, transparent 1px),
                linear-gradient(90deg, rgba(139, 119, 101, 0.06) 1px, transparent 1px),
                linear-gradient(180deg, #faf0dc 0%, #f0e0c0 50%, #e8d5b0 100%);
            background-size: 22px 22px, 22px 22px, 100% 100%;
        }
        .preview-papyrus h4, .preview-papyrus h5, .preview-papyrus h6,
        .preview-papyrus td, .preview-papyrus th,
        .preview-papyrus #previewTechnicalNotes,
        .preview-papyrus .table-scroll-rm-records tbody td,
        .preview-papyrus .table-scroll-rm-records thead th { color: #000 !important; }
        .preview-papyrus .table, .preview-papyrus .table thead th,
        .preview-papyrus .table tbody, .preview-papyrus .table tbody tr,
        .preview-papyrus .table tbody td { background-color: transparent !important; }
        .preview-papyrus .table thead th,
        .preview-papyrus .table-scroll-rm-records thead th {
            background-color: #f0e0c0 !important;
            color: #000 !important;
        }
        .preview-papyrus .table tbody tr:nth-of-type(odd) td,
        .preview-papyrus .table tbody tr:nth-of-type(even) td,
        .preview-papyrus .table-scroll-rm-records tbody tr:nth-of-type(odd) td,
        .preview-papyrus .table-scroll-rm-records tbody tr:nth-of-type(even) td {
            background-color: transparent !important;
            color: #000 !important;
        }
        .preview-papyrus .table-scroll-rm-records table,
        .preview-papyrus .table-scroll-rm-records th,
        .preview-papyrus .table-scroll-rm-records td {
            border-color: #6c757d !important;
        }
        .preview-papyrus .dir-table {
            background: transparent !important;
            color: #111 !important;
            --bs-table-bg: transparent !important;
            --bs-table-color: #111 !important;
            --bs-table-striped-bg: transparent !important;
            --bs-table-striped-color: #111 !important;
            --bs-table-hover-bg: rgba(76, 61, 38, 0.08) !important;
            --bs-table-hover-color: #111 !important;
        }
        .preview-papyrus .dir-table > tbody > tr > td,
        .preview-papyrus .dir-table > tbody > tr > th {
            background: transparent !important;
            color: #111 !important;
            box-shadow: none !important;
        }
        .preview-papyrus .dir-table > thead > tr > th {
            background: #f0e0c0 !important;
            color: #111 !important;
        }
        html[data-bs-theme="dark"] .preview-papyrus .dir-table.table-striped > tbody > tr:nth-of-type(odd) > * {
            background: transparent !important;
            color: #111 !important;
            box-shadow: none !important;
        }
    </style>

    <div class="row g-3">
        {{-- Card 1: Preview (papyrus) --}}
        <div class="col-md-6">
            <div class="card h-100 preview-papyrus border">
                <div class="card-body rm-preview-card-body">
                    <h4 class="text-center">{{__('Repair and Modification Record WO')}}{{$current_wo->number}}</h4>
                    <div class="p-2">
                        <h6>{{__('Technical Notes')}}</h6>
                        <div id="previewTechnicalNotes" class="border rounded p-2" style="min-height: 50px; white-space: pre-line;"></div>
                    </div>
                    <div class="table mt-3 table-scroll-rm-records">
                        <table class="table table-striped text-center align-items-center dir-table" style="font-size: 12px">
                            <thead>
                            <tr>
                                <th class="border align-middle">{{ __('Item') }}</th>
                                <th class="border align-middle">{{ __('Part Description') }}</th>
                                <th class="border align-middle">{{ __('Modification or Repair #') }}</th>
                                <th class="border align-middle">{{ __('Description Of Modification or Repair') }}</th>
                                <th class="border align-middle">{{ __('Identification Method') }}</th>
                            </tr>
                            </thead>
                            <tbody id="previewRecordsTableBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card 2: Technical Notes + Repair and Modification --}}
        <div class="col-md-6">
            <div class="card bg-gradient h-100">
                <div class="card-body rm-editor-card-body">
                    {{-- Technical Notes section --}}
                    <div class="mb-3 rm-editor-section">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="text-primary mb-0 rm-section-title">{{ __('Technical Notes') }}</h5>
                            <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#technicalNoteModal">
                                {{ __('Add Notes') }}
                            </button>
                        </div>
                        <div class="table-responsive table-scroll-technical-notes">
                            <table class="table table-bordered dir-table">
                                <tbody id="technicalNotesTableBody"></tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Repair and Modification section --}}
                    <div class="rm-editor-section rm-editor-section--records">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="text-primary mb-0 rm-section-title">{{ __('Repair and Modification') }}</h5>
                            <div class="d-flex gap-2 align-items-center">
                                <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#addRmRecordModal">
                                    {{ __('ADD')}}
                                </button>
                            </div>
                        </div>

                        <div id="rmAssemblyScopeStatus" class="alert alert-info py-2 px-3 mb-2 {{ $current_wo->modified_scope_part_group_option_id ? '' : 'd-none' }}">
                            <strong>{{ __('Effective assembly scope') }}:</strong>
                            <span data-rm-modified-value>{{ $current_wo->modifiedScopePartGroupOption?->part_number ?: $current_wo->modified }}</span>
                            <span class="ms-1">({{ __('the received Work Scope remains unchanged') }})</span>
                        </div>

                        @php
                                $savedData = $current_wo->rm_report ? json_decode($current_wo->rm_report, true) : null;
                                $savedRecordIds = $savedData['rm_records'] ?? [];
                                $savedRecordIds = collect($savedRecordIds)->pluck('id')->toArray();
                            @endphp

                            <div id="rmRecordsList">
                                @if($rm_reports->count() > 0)
                                    <div class="table-responsive mt-3">
                                        <table class="table table-hover table-bordered dir-table align-middle bg-gradient rm-records-table-right">
                                            <thead class="table-dark" style="position: sticky; top: 0; z-index: 5;">
                                            <tr>
                                                <th class="text-primary text-center align-middle" style="width: 20%">{{ __('Part
                                                 Description') }}</th>
                                                <th class="text-primary text-center align-middle" style="width: 10%">{{ __
                                                ('Modification or Repair #') }}</th>
                                                <th class="text-primary text-center align-middle" style="width: 25%">{{ __
                                                ('Description') }}</th>
                                                <th class="text-primary text-center align-middle" style="width: 25%">{{ __
                                                ('Identification Method') }}</th>
                                                <th class="text-primary text-center align-middle" style="width: 10%">{{ __('Select Record') }}</th>
                                                <th class="text-primary text-center align-middle" style="width: 10%">{{ __('Actions') }}</th>
                                            </tr>
                                            </thead>
                                            <tbody id="rmRecordsTableBody">
                                            @foreach($rm_reports as $report)
                                                @php
                                                    $sourceAssyLabel = trim((string) $report->sourceAssyOption?->part_number);
                                                    $targetAssyLabel = trim((string) $report->targetAssyOption?->part_number);
                                                @endphp
                                                <tr data-record-id="{{ $report->id }}" data-admin-template="{{ $report->is_admin_template ? 1 : 0 }}"
                                                    data-changes-assembly-scope="{{ $report->changesAssemblyScope() ? '1' : '0' }}"
                                                    data-source-assy-label="{{ $sourceAssyLabel }}"
                                                    data-target-assy-label="{{ $targetAssyLabel }}">
                                                    <td class="border align-middle">
                                                        <span data-rm-cell="part-description">{{ $report->part_description }}</span>
                                                        <small data-admin-template-badge class="text-warning d-block {{ $report->is_admin_template ? '' : 'd-none' }}">{{ __('Protected template') }}</small>
                                                        <small class="rm-assy-conversion-summary {{ $report->changesAssemblyScope() ? '' : 'd-none' }}" data-rm-conversion-summary>
                                                            {{ __('ASSY') }} {{ $sourceAssyLabel }} → {{ $targetAssyLabel }}
                                                        </small>
                                                    </td>
                                                    <td class="border align-middle" data-rm-cell="mod-repair">{{ $report->mod_repair }}</td>
                                                    <td class="border align-middle" data-rm-cell="description">{{ $report->description }}</td>
                                                    <td class="border align-middle" data-rm-cell="ident-method">{{ $report->ident_method }}</td>
                                                    <td class="border align-middle">
                                                        <div class="form-check">
                                                            <input class="form-check-input record-checkbox" type="checkbox"
                                                                   id="record_{{ $report->id }}" value="{{ $report->id }}"
                                                                   {{ in_array($report->id, $savedRecordIds) ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="record_{{ $report->id }}">Select</label>
                                                        </div>
                                                    </td>
                                                    <td class="align-middle">
                                                        @if(! $report->is_admin_template || auth()->user()->roleIs('Admin'))
                                                        <button class="btn btn-sm btn-outline-primary me-1" onclick="window.rmPartialEditRecord({{ $report->id }})" data-bs-toggle="modal" data-bs-target="#editRmRecordModal">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger" onclick="window.rmPartialDeleteRecord({{ $report->id }})">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="alert alert-info mt-3">
                                        {{ __('No R&M records found for this work order.') }}
                                        {{ __('Use "Add Repair OR Modification" to create the first record.') }}
                                    </div>
                                @endif
                            </div>
                        </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addRmRecordModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content bg-gradient">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('ADD Repair OR Modification') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addRmRecordForm" data-no-spinner>
                    @csrf
                    <input type="hidden" name="workorder_id" value="{{ $current_wo->id }}">
                    <div class="modal-body">
                        @if(auth()->user()->roleIs('Admin'))
                        <div class="form-check mb-3">
                            <input type="hidden" name="is_admin_template" value="0">
                            <input class="form-check-input" type="checkbox" name="is_admin_template" value="1" id="is_admin_template">
                            <label class="form-check-label" for="is_admin_template">{{ __('Protected template (Admin only)') }}</label>
                            <small class="d-block text-secondary">{{ __('Always listed first. Everyone can select or deselect it; only Admin can edit or delete it.') }}</small>
                        </div>
                        @endif
                        <div class="form-group">
                            <label for="part_description">{{ __('Part Description') }}</label>
                            <input type="text" class="form-control" id="part_description" name="part_description" required>
                        </div>
                        <div class="form-group mt-3">
                            <label>{{ __('Modification or Repair') }}</label>
                            <div class="d-flex gap-3 mt-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="mod_repair" id="mod_repair_mod" value="Mod" required>
                                    <label class="form-check-label" for="mod_repair_mod">Mod</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="mod_repair" id="mod_repair_repair" value="Repair" required>
                                    <label class="form-check-label" for="mod_repair_repair">Repair</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="mod_repair" id="mod_repair_sb" value="SB" required>
                                    <label class="form-check-label" for="mod_repair_sb">SB</label>
                                </div>
                            </div>
                        </div>
                        <div class="border rounded p-3 mt-3 rm-assy-conversion-fields" data-assy-conversion-fields hidden>
                            <div class="text-info fw-semibold mb-2">{{ __('Optional Workorder assembly conversion') }}</div>
                            <div class="row g-2">
                                <div class="col-md-12">
                                    <label for="manual_service_bulletin_id">{{ __('Service Bulletin') }}</label>
                                    <select class="form-select" id="manual_service_bulletin_id" name="manual_service_bulletin_id">
                                        <option value="">{{ __('No assembly conversion') }}</option>
                                        @foreach($serviceBulletins as $bulletin)
                                            @php
                                                $bulletinLabel = collect([$bulletin->ac_mfg_service_bulletin_no, $bulletin->oem_service_bulletin_no, $bulletin->description])->map(fn($value) => trim((string) $value))->first(fn($value) => $value !== '') ?: 'SB #'.$bulletin->id;
                                            @endphp
                                            <option value="{{ $bulletin->id }}">{{ $bulletinLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="source_assy_option_id">{{ __('Received ASSY') }}</label>
                                    <select class="form-select" id="source_assy_option_id" name="source_assy_option_id">
                                        <option value="">—</option>
                                        @foreach($assyOptions as $option)
                                            <option value="{{ $option->id }}">{{ $option->part_number }}{{ $option->ipl_num ? ' · IPL '.$option->ipl_num : '' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="target_assy_option_id">{{ __('Modified ASSY') }}</label>
                                    <select class="form-select" id="target_assy_option_id" name="target_assy_option_id">
                                        <option value="">—</option>
                                        @foreach($assyOptions as $option)
                                            <option value="{{ $option->id }}">{{ $option->part_number }}{{ $option->ipl_num ? ' · IPL '.$option->ipl_num : '' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <small class="text-secondary d-block mt-2">{{ __('When this R&M record is selected, new TDRs and process lists use every part from the Modified ASSY. Existing TDR history is preserved.') }}</small>
                        </div>
                        <div class="form-group mt-3">
                            <label for="mod_repair_description">{{ __('Description of Modification or Repair') }}</label>
                            <input type="text" class="form-control" id="mod_repair_description" name="mod_repair_description" maxlength="250" required>
                        </div>
                        <div class="form-group mt-3">
                            <label for="ident_method">{{ __('Identification Method') }}</label>
                            <input type="text" class="form-control" id="ident_method" name="ident_method">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-outline-primary">{{ __('Save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editRmRecordModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content bg-gradient">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Edit Repair OR Modification') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editRmRecordForm" data-no-spinner>
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="workorder_id" value="{{ $current_wo->id }}">
                    <input type="hidden" name="record_id" id="edit_record_id">
                    <div class="modal-body">
                        @if(auth()->user()->roleIs('Admin'))
                        <div class="form-check mb-3">
                            <input type="hidden" name="is_admin_template" value="0">
                            <input class="form-check-input" type="checkbox" name="is_admin_template" value="1" id="edit_is_admin_template">
                            <label class="form-check-label" for="edit_is_admin_template">{{ __('Protected template (Admin only)') }}</label>
                            <small class="d-block text-secondary">{{ __('Always listed first. Everyone can select or deselect it; only Admin can edit or delete it.') }}</small>
                        </div>
                        @endif
                        <div class="form-group">
                            <label for="edit_part_description">{{ __('Part Description') }}</label>
                            <input type="text" class="form-control" id="edit_part_description" name="part_description" required>
                        </div>
                        <div class="form-group mt-3">
                            <label>{{ __('Modification or Repair') }}</label>
                            <div class="d-flex gap-3 mt-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="mod_repair" id="edit_mod_repair_mod" value="Mod" required>
                                    <label class="form-check-label" for="edit_mod_repair_mod">Mod</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="mod_repair" id="edit_mod_repair_repair" value="Repair" required>
                                    <label class="form-check-label" for="edit_mod_repair_repair">Repair</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="mod_repair" id="edit_mod_repair_sb" value="SB" required>
                                    <label class="form-check-label" for="edit_mod_repair_sb">SB</label>
                                </div>
                            </div>
                        </div>
                        <div class="border rounded p-3 mt-3 rm-assy-conversion-fields" data-assy-conversion-fields hidden>
                            <div class="text-info fw-semibold mb-2">{{ __('Optional Workorder assembly conversion') }}</div>
                            <div class="row g-2">
                                <div class="col-md-12">
                                    <label for="edit_manual_service_bulletin_id">{{ __('Service Bulletin') }}</label>
                                    <select class="form-select" id="edit_manual_service_bulletin_id" name="manual_service_bulletin_id">
                                        <option value="">{{ __('No assembly conversion') }}</option>
                                        @foreach($serviceBulletins as $bulletin)
                                            @php
                                                $bulletinLabel = collect([$bulletin->ac_mfg_service_bulletin_no, $bulletin->oem_service_bulletin_no, $bulletin->description])->map(fn($value) => trim((string) $value))->first(fn($value) => $value !== '') ?: 'SB #'.$bulletin->id;
                                            @endphp
                                            <option value="{{ $bulletin->id }}">{{ $bulletinLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="edit_source_assy_option_id">{{ __('Received ASSY') }}</label>
                                    <select class="form-select" id="edit_source_assy_option_id" name="source_assy_option_id">
                                        <option value="">—</option>
                                        @foreach($assyOptions as $option)
                                            <option value="{{ $option->id }}">{{ $option->part_number }}{{ $option->ipl_num ? ' · IPL '.$option->ipl_num : '' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="edit_target_assy_option_id">{{ __('Modified ASSY') }}</label>
                                    <select class="form-select" id="edit_target_assy_option_id" name="target_assy_option_id">
                                        <option value="">—</option>
                                        @foreach($assyOptions as $option)
                                            <option value="{{ $option->id }}">{{ $option->part_number }}{{ $option->ipl_num ? ' · IPL '.$option->ipl_num : '' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <small class="text-secondary d-block mt-2">{{ __('When this R&M record is selected, new TDRs and process lists use every part from the Modified ASSY. Existing TDR history is preserved.') }}</small>
                        </div>
                        <div class="form-group mt-3">
                            <label for="edit_mod_repair_description">{{ __('Description of Modification or Repair') }}</label>
                            <input type="text" class="form-control" id="edit_mod_repair_description" name="mod_repair_description" maxlength="250" required>
                        </div>
                        <div class="form-group mt-3">
                            <label for="edit_ident_method">{{ __('Identification Method') }}</label>
                            <input type="text" class="form-control" id="edit_ident_method" name="ident_method">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-outline-primary">{{ __('Update') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="technicalNoteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content bg-gradient">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Enter Note') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <textarea id="technicalNoteInput" class="form-control" rows="3" placeholder="{{ __('Enter technical note') }}"></textarea>
                    <input type="hidden" id="technicalNoteIndex" value="-1">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="button" class="btn btn-outline-primary" id="rmPartialSaveTechnicalNoteBtn">{{ __('Save') }}</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var workorderId = {{ $current_wo->id }};
    var technicalNotes = @json($savedData['technical_notes'] ?? []);
    if (!Array.isArray(technicalNotes)) technicalNotes = [];

    var DEBOUNCE_MS = 400;
    var saveTimeout = null;
    var storeUrl = '{{ route("rm_reports.store") }}';
    var updateUrl = '{{ route("rm_reports.update", $current_wo->id) }}';
    var getRecordUrl = '{{ route("rm_reports.getRecord", ":id") }}';
    var updateRecordUrl = '{{ route("rm_reports.updateRecord", ":id") }}';
    var destroyUrl = '{{ route("rm_reports.destroy", ":id") }}';
    var csrfToken = '{{ csrf_token() }}';

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }

    function notifyRm(message, type) {
        if (typeof showNotification === 'function') {
            showNotification(message, type || 'error');
        }
    }

    function responseError(data, fallback) {
        var errors = data && data.errors ? Object.values(data.errors).flat() : [];
        return errors.length ? errors[0] : ((data && data.message) || fallback);
    }

    function setRowConversionData(row, data) {
        if (!row) return;
        row.dataset.adminTemplate = data.is_admin_template ? '1' : '0';
        var badge = row.querySelector('[data-admin-template-badge]');
        if (!badge) {
            badge = document.createElement('small');
            badge.setAttribute('data-admin-template-badge', '');
            badge.className = 'text-warning d-block';
            badge.textContent = '{{ __('Protected template') }}';
            row.cells[0].appendChild(badge);
        }
        badge.classList.toggle('d-none', !data.is_admin_template);
        var tbody = row.parentElement;
        Array.from(tbody.querySelectorAll('tr[data-record-id]')).sort(function(a, b) {
            return Number(b.dataset.adminTemplate || 0) - Number(a.dataset.adminTemplate || 0)
                || Number(a.dataset.recordId) - Number(b.dataset.recordId);
        }).forEach(function(item) { tbody.appendChild(item); });
        var changesScope = !!data.changes_assembly_scope;
        var source = data.source_assy_part_number || '';
        var target = data.target_assy_part_number || '';
        row.dataset.changesAssemblyScope = changesScope ? '1' : '0';
        row.dataset.sourceAssyLabel = source;
        row.dataset.targetAssyLabel = target;
        var summary = row.querySelector('[data-rm-conversion-summary]');
        if (summary) {
            summary.textContent = changesScope ? '{{ __('ASSY') }} ' + source + ' → ' + target : '';
            summary.classList.toggle('d-none', !changesScope);
        }
    }

    function toggleConversionFields(form) {
        if (!form) return;
        var selected = form.querySelector('input[name="mod_repair"]:checked');
        var panel = form.querySelector('[data-assy-conversion-fields]');
        if (!panel) return;
        panel.hidden = !selected || selected.value !== 'SB';
        if (panel.hidden) {
            panel.querySelectorAll('select').forEach(function(select) { select.value = ''; });
        }
    }

    function bindConversionFieldToggles(form) {
        if (!form) return;
        form.querySelectorAll('input[name="mod_repair"]').forEach(function(input) {
            input.addEventListener('change', function() { toggleConversionFields(form); });
        });
        toggleConversionFields(form);
    }

    function updatePreview() {
        var notesEl = document.getElementById('previewTechnicalNotes');
        if (notesEl) notesEl.textContent = technicalNotes.join('\n');

        var tbody = document.getElementById('previewRecordsTableBody');
        if (!tbody) return;
        tbody.innerHTML = '';
        var itemNum = 1;
        var checkboxes = document.querySelectorAll('.record-checkbox:checked');
        checkboxes.forEach(function(cb) {
            var row = cb.closest('tr');
            if (!row) return;
            var partDesc = escapeHtml(row.querySelector('[data-rm-cell="part-description"]')?.textContent.trim() || '');
            var modRepair = escapeHtml(row.querySelector('[data-rm-cell="mod-repair"]')?.textContent.trim() || '');
            var desc = escapeHtml(row.querySelector('[data-rm-cell="description"]')?.textContent.trim() || '');
            var identMethod = escapeHtml(row.querySelector('[data-rm-cell="ident-method"]')?.textContent.trim() || '');
            var tr = document.createElement('tr');
            tr.innerHTML = '<td class="border">' + itemNum + '</td><td class="border">' + partDesc + '</td><td class="border">' + modRepair + '</td><td class="border">' + desc + '</td><td class="border">' + identMethod + '</td>';
            tbody.appendChild(tr);
            itemNum++;
        });
    }

    function triggerDebouncedSave() {
        if (saveTimeout) clearTimeout(saveTimeout);
        saveTimeout = setTimeout(performSave, DEBOUNCE_MS);
    }

    async function confirmRecordSave(form) {
        if (typeof window.confirmDialog !== 'function') {
            notifyRm('{{ __('Confirmation dialog is unavailable. Nothing was saved.') }}', 'error');
            return false;
        }
        var modalElement = form.closest('.modal');
        var modal = bootstrap.Modal.getInstance(modalElement);
        if (modal && modalElement.classList.contains('show')) {
            await new Promise(function(resolve) {
                modalElement.addEventListener('hidden.bs.modal', resolve, {once: true});
                modal.hide();
            });
        }
        var confirmed = await window.confirmDialog({title: '{{ __('Save R&M template') }}', message: '{{ __('Save this template for the manual?') }}', okText: '{{ __('Save') }}', cancelText: '{{ __('Cancel') }}'});
        var confirmation = document.getElementById('globalConfirmModal');
        if (confirmation && bootstrap.Modal.getInstance(confirmation)?._isTransitioning) {
            await new Promise(function(resolve) { confirmation.addEventListener('hidden.bs.modal', resolve, {once: true}); });
        }
        if (!confirmed && modal) modal.show();
        return confirmed;
    }

    function rmPartialConfirmDelete(message) {
        if (typeof window.confirmDialog !== 'function') {
            notifyRm('{{ __('Confirmation dialog is unavailable. Nothing was deleted.') }}', 'error');
            return Promise.resolve(false);
        }

        return window.confirmDialog({
            title: '{{ __('Delete Confirmation') }}',
            message: message,
            okText: '{{ __('Delete') }}',
            cancelText: '{{ __('Cancel') }}',
            danger: true
        });
    }

    function performSave() {
        saveTimeout = null;
        var selectedRecords = [];
        document.querySelectorAll('.record-checkbox:checked').forEach(function(cb) { selectedRecords.push(cb.value); });
        var formData = new FormData();
        formData.append('selected_records', JSON.stringify(selectedRecords));
        formData.append('workorder_id', workorderId);
        formData.append('_token', csrfToken);
        formData.append('_method', 'PUT');
        technicalNotes.forEach(function(note, i) { formData.append('notes[' + i + ']', note); });

        return fetch(updateUrl, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            credentials: 'same-origin'
        }).then(function(r) { return r.json().catch(function() { return {}; }); })
        .then(function(data) {
            if (!data.success) {
                notifyRm(responseError(data, '{{ __('Unable to save the R&M selection.') }}'), 'error');
                return false;
            }
            var status = document.getElementById('rmAssemblyScopeStatus');
            var value = status ? status.querySelector('[data-rm-modified-value]') : null;
            var modification = data.modification || {};
            if (status) status.classList.toggle('d-none', !modification.target_option_id);
            if (value) value.textContent = modification.modified || '';
            return true;
        }).catch(function() {
            notifyRm('{{ __('Unable to save the R&M selection.') }}', 'error');
            return false;
        });
    }

    function bindRecordCheckbox(cb) {
        if (!cb || cb.dataset.rmBound === '1') return;
        cb.dataset.rmBound = '1';
        cb.addEventListener('change', async function() {
            var row = cb.closest('tr');
            var changedTo = cb.checked;
            if (row && row.dataset.changesAssemblyScope === '1') {
                if (typeof window.confirmDialog !== 'function') {
                    cb.checked = !changedTo;
                    updatePreview();
                    notifyRm('{{ __('Confirmation dialog is unavailable. The assembly scope was not changed.') }}', 'error');
                    return;
                }
                var source = row.dataset.sourceAssyLabel || '';
                var target = row.dataset.targetAssyLabel || '';
                var confirmed = await window.confirmDialog({
                    title: changedTo ? '{{ __('Apply assembly conversion?') }}' : '{{ __('Remove assembly conversion?') }}',
                    message: changedTo
                        ? '{{ __('New TDRs and process lists will use the modified assembly') }} ' + target + ' {{ __('instead of received assembly') }} ' + source + '. {{ __('Existing TDRs will remain unchanged.') }}'
                        : '{{ __('New TDRs and process lists will return to the received assembly') }} ' + source + '. {{ __('Existing TDRs will remain unchanged.') }}',
                    okText: changedTo ? '{{ __('Apply') }}' : '{{ __('Remove') }}',
                    cancelText: '{{ __('Cancel') }}',
                    danger: false
                });
                if (!confirmed) {
                    cb.checked = !changedTo;
                    updatePreview();
                    return;
                }
            }

            updatePreview();
            if (!await performSave()) {
                cb.checked = !changedTo;
                updatePreview();
            }
        });
    }

    function renderTechnicalNotesTable() {
        var tbody = document.getElementById('technicalNotesTableBody');
        if (!tbody) return;
        tbody.innerHTML = '';
        technicalNotes.forEach(function(note, index) {
            var tr = document.createElement('tr');
            tr.innerHTML = '<td class="align-middle">' + escapeHtml(note) + '</td>' +
                '<td class="text-end" style="width: 120px;">' +
                '<button type="button" class="btn btn-sm btn-outline-primary me-1" data-edit-idx="' + index + '"><i class="fas fa-edit"></i></button>' +
                '<button type="button" class="btn btn-sm btn-outline-danger" data-delete-idx="' + index + '"><i class="fas fa-trash"></i></button>' +
                '</td>';
            tbody.appendChild(tr);
        });
        tbody.querySelectorAll('[data-edit-idx]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var idx = parseInt(this.getAttribute('data-edit-idx'), 10);
                document.getElementById('technicalNoteInput').value = technicalNotes[idx] || '';
                document.getElementById('technicalNoteIndex').value = idx;
                new bootstrap.Modal(document.getElementById('technicalNoteModal')).show();
            });
        });
        tbody.querySelectorAll('[data-delete-idx]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var idx = parseInt(this.getAttribute('data-delete-idx'), 10);
                technicalNotes.splice(idx, 1);
                renderTechnicalNotesTable();
                triggerDebouncedSave();
            });
        });
        updatePreview();
    }

    document.getElementById('technicalNoteModal') && document.getElementById('rmPartialSaveTechnicalNoteBtn') && document.getElementById('rmPartialSaveTechnicalNoteBtn').addEventListener('click', function() {
        var noteText = (document.getElementById('technicalNoteInput') && document.getElementById('technicalNoteInput').value) ? document.getElementById('technicalNoteInput').value.trim() : '';
        var index = parseInt(document.getElementById('technicalNoteIndex') ? document.getElementById('technicalNoteIndex').value : -1, 10);
        if (!noteText && typeof showNotification === 'function') {
            showNotification('{{ __("Please enter a note text.") }}', 'warning');
            return;
        }
        if (!isNaN(index) && index >= 0 && index < technicalNotes.length) {
            technicalNotes[index] = noteText;
        } else {
            technicalNotes.push(noteText);
        }
        document.getElementById('technicalNoteInput').value = '';
        document.getElementById('technicalNoteIndex').value = '-1';
        var m = bootstrap.Modal.getInstance(document.getElementById('technicalNoteModal'));
        if (m) m.hide();
        renderTechnicalNotesTable();
        triggerDebouncedSave();
    });

    document.getElementById('technicalNoteModal') && document.getElementById('technicalNoteModal').addEventListener('show.bs.modal', function() {
        document.getElementById('technicalNoteInput').focus();
    });

    document.querySelectorAll('.record-checkbox').forEach(bindRecordCheckbox);

    document.getElementById('addRmRecordForm') && document.getElementById('addRmRecordForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        var form = this;
        if (!await confirmRecordSave(form)) return;
        var fd = new FormData(form);
        var submitBtn = form.querySelector('button[type="submit"]');
        var origHtml = submitBtn ? submitBtn.innerHTML : '';
        if (submitBtn) { submitBtn.disabled = true; submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>'; }
        fetch(storeUrl, {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            credentials: 'same-origin',
            spinner: false
        })
        .then(function(r) { return r.json().catch(function() { return {}; }); })
        .then(function(res) {
            if (res.success && res.data) {
                var d = res.data;
                var tbody = document.getElementById('rmRecordsTableBody');
                var rmRecordsList = document.getElementById('rmRecordsList');
                if (!tbody && rmRecordsList) {
                    rmRecordsList.innerHTML = '<div class="table-responsive mt-3"><table class="table table-striped text-center dir-table rm-records-table-right"><thead><tr><th>{{ __("Part Description") }}</th><th>{{ __("Modification or Repair #") }}</th><th>{{ __("Description") }}</th><th>{{ __("Identification Method") }}</th><th>{{ __("Select Record") }}</th><th>{{ __("Actions") }}</th></tr></thead><tbody id="rmRecordsTableBody"></tbody></table></div>';
                    tbody = document.getElementById('rmRecordsTableBody');
                }
                if (tbody) {
                    var tr = document.createElement('tr');
                    tr.setAttribute('data-record-id', d.id);
                    tr.innerHTML = '<td class="align-middle"><span data-rm-cell="part-description">' + escapeHtml(d.part_description) + '</span><small class="rm-assy-conversion-summary d-none" data-rm-conversion-summary></small></td><td class="align-middle" data-rm-cell="mod-repair">' + escapeHtml(d.mod_repair) + '</td><td class="align-middle" data-rm-cell="description">' + escapeHtml(d.description) + '</td><td class="align-middle" data-rm-cell="ident-method">' + escapeHtml(d.ident_method || '') + '</td><td class="align-middle"><div class="form-check"><input class="form-check-input record-checkbox" type="checkbox" id="record_' + d.id + '" value="' + d.id + '"><label class="form-check-label" for="record_' + d.id + '">Select</label></div></td><td class="align-middle"><button class="btn btn-sm btn-outline-primary me-1" onclick="window.rmPartialEditRecord(' + d.id + ')" data-bs-toggle="modal" data-bs-target="#editRmRecordModal"><i class="fas fa-edit"></i></button><button class="btn btn-sm btn-outline-danger" onclick="window.rmPartialDeleteRecord(' + d.id + ')"><i class="fas fa-trash"></i></button></td>';
                    tbody.appendChild(tr);
                    setRowConversionData(tr, d);
                    bindRecordCheckbox(tr.querySelector('.record-checkbox'));
                    updatePreview();
                }
                var m = bootstrap.Modal.getInstance(document.getElementById('addRmRecordModal'));
                if (m) m.hide();
                form.reset();
                toggleConversionFields(form);
            } else {
                bootstrap.Modal.getOrCreateInstance(form.closest('.modal')).show();
                notifyRm(responseError(res, '{{ __('Error creating record.') }}'), 'error');
            }
        })
        .catch(function() {
            if (typeof showNotification === 'function') {
                showNotification('{{ __("Error creating record.") }}', 'error');
            }
        })
        .finally(function() {
            if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = origHtml; }
        });
    });

    document.getElementById('editRmRecordForm') && document.getElementById('editRmRecordForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        var form = this;
        if (!await confirmRecordSave(form)) return;
        var url = form.getAttribute('action');
        if (!url) return;
        var fd = new FormData(form);
        fd.append('_method', 'PUT');
        var submitBtn = form.querySelector('button[type="submit"]');
        var origHtml = submitBtn ? submitBtn.innerHTML : '';
        if (submitBtn) { submitBtn.disabled = true; submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>'; }
        fetch(url, {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            credentials: 'same-origin',
            spinner: false
        })
        .then(function(r) { return r.json().catch(function() { return {}; }); })
        .then(function(res) {
            if (res.success && res.data) {
                var d = res.data;
                var row = document.querySelector('tr[data-record-id="' + d.id + '"]');
                if (row) {
                    var partCell = row.querySelector('[data-rm-cell="part-description"]');
                    var modCell = row.querySelector('[data-rm-cell="mod-repair"]');
                    var descriptionCell = row.querySelector('[data-rm-cell="description"]');
                    var identCell = row.querySelector('[data-rm-cell="ident-method"]');
                    if (partCell) partCell.textContent = d.part_description;
                    if (modCell) modCell.textContent = d.mod_repair;
                    if (descriptionCell) descriptionCell.textContent = d.description;
                    if (identCell) identCell.textContent = d.ident_method || '';
                    setRowConversionData(row, d);
                }
                updatePreview();
                triggerDebouncedSave();
                var m = bootstrap.Modal.getInstance(document.getElementById('editRmRecordModal'));
                if (m) m.hide();
            } else {
                bootstrap.Modal.getOrCreateInstance(form.closest('.modal')).show();
                notifyRm(responseError(res, '{{ __('Error updating record.') }}'), 'error');
            }
        })
        .catch(function() {
            if (typeof showNotification === 'function') {
                showNotification('{{ __("Error updating record.") }}', 'error');
            }
        })
        .finally(function() {
            if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = origHtml; }
        });
    });

    window.rmPartialEditRecord = function(recordId) {
        fetch(getRecordUrl.replace(':id', recordId), { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.success && res.data) {
                    var r = res.data;
                    document.getElementById('edit_record_id').value = r.id;
                    var protectedInput = document.getElementById('edit_is_admin_template');
                    if (protectedInput) protectedInput.checked = !!r.is_admin_template;
                    document.getElementById('edit_part_description').value = r.part_description || '';
                    document.getElementById('edit_mod_repair_description').value = r.description || '';
                    document.getElementById('edit_ident_method').value = r.ident_method || '';
                    document.querySelectorAll('#editRmRecordForm input[name="mod_repair"]').forEach(function(inp) { inp.checked = false; });
                    var modInput = document.getElementById('edit_mod_repair_' + (r.mod_repair || '').toLowerCase());
                    if (modInput) modInput.checked = true;
                    document.getElementById('edit_manual_service_bulletin_id').value = r.manual_service_bulletin_id || '';
                    document.getElementById('edit_source_assy_option_id').value = r.source_assy_option_id || '';
                    document.getElementById('edit_target_assy_option_id').value = r.target_assy_option_id || '';
                    toggleConversionFields(document.getElementById('editRmRecordForm'));
                    document.getElementById('editRmRecordForm').setAttribute('action', updateRecordUrl.replace(':id', r.id));
                }
            })
            .catch(function() { if (typeof showNotification === 'function') showNotification('{{ __("Error loading record.") }}', 'error'); });
    };

    window.rmPartialDeleteRecord = function(recordId) {
        rmPartialConfirmDelete('{{ __("Are you sure you want to delete this record?") }}').then(function(confirmed) {
            if (!confirmed) return;
            var fd = new FormData();
            fd.append('_token', csrfToken);
            fd.append('_method', 'DELETE');
            fd.append('workorder_id', workorderId);
            fetch(destroyUrl.replace(':id', recordId), {
                method: 'POST',
                body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                credentials: 'same-origin',
                spinner: false
            })
            .then(function(r) { return r.json().catch(function() { return {}; }); })
            .then(function(res) {
                if (res.success) {
                    var row = document.querySelector('tr[data-record-id="' + recordId + '"]');
                    if (row) row.remove();
                    updatePreview();
                    triggerDebouncedSave();
                } else if (res.message && typeof showNotification === 'function') {
                    showNotification(res.message, 'error');
                }
            })
            .catch(function() {
                if (typeof showNotification === 'function') {
                    showNotification('{{ __("Delete failed.") }}', 'error');
                }
            });
        });
    };

    renderTechnicalNotesTable();
    bindConversionFieldToggles(document.getElementById('addRmRecordForm'));
    bindConversionFieldToggles(document.getElementById('editRmRecordForm'));
    updatePreview();
})();
</script>
