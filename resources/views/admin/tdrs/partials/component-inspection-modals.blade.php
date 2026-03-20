{{-- Add Component Modal (from component-inspection) --}}
<div class="modal fade" id="addComponentModal" tabindex="-1" aria-labelledby="addComponentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-gradient">
            <div class="modal-header">
                <h5 class="modal-title" id="addComponentModalLabel">{{ __('Add Part') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('components.storeFromInspection') }}" method="POST" id="addComponentForm">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="manual_id" id="addComponentManualId" value="{{ $current_wo->unit->manual_id }}">
                    <input type="hidden" name="current_wo" value="{{ $current_wo->id }}">
                    <div class="form-group">
                        <label for="name">{{ __('Name') }}</label>
                        <input id="name" type="text" class="form-control" name="name" required>
                    </div>
                    <div class="d-flex flex-wrap">
                        <div class="m-3">
                            <div class="mb-2">
                                <label for="ipl_num">{{ __('IPL Number') }}</label>
                                <input id="ipl_num" type="text" class="form-control" name="ipl_num" required>
                            </div>
                            <div class="mb-2">
                                <label>{{ __('Image') }}</label>
                                <input type="file" name="img" class="form-control" placeholder="Image">
                            </div>
                            <div class="mb-2">
                                <label for="part_number">{{ __('Part Number') }}</label>
                                <input id="part_number" type="text" class="form-control" name="part_number" required>
                            </div>
                            <div class="mb-2">
                                <label for="eff_code">{{ __('EFF Code') }}</label>
                                <input id="eff_code" type="text" class="form-control" name="eff_code" placeholder="Enter EFF code (optional)">
                            </div>
                        </div>
                        <div class="m-3">
                            <div class="mb-2">
                                <label for="assy_ipl_num">{{ __('Assembly IPL Number') }}</label>
                                <input id="assy_ipl_num" type="text" class="form-control" name="assy_ipl_num">
                            </div>
                            <div class="mb-2">
                                <label>{{ __('Assy Image') }}</label>
                                <input type="file" name="assy_img" class="form-control" placeholder="Image">
                            </div>
                            <div class="mb-2">
                                <label for="assy_part_number">{{ __('Assembly Part Number') }}</label>
                                <input id="assy_part_number" type="text" class="form-control" name="assy_part_number">
                            </div>
                            <div class="mb-2">
                                <label for="units_assy">{{ __('Units per Assy') }}</label>
                                <input id="units_assy" type="text" class="form-control" name="units_assy" placeholder="Enter units per assembly">
                            </div>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="log_card" name="log_card">
                            <label class="form-check-label" for="log_card">Log Card</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_bush" name="is_bush">
                            <label class="form-check-label" for="is_bush">Is Bush</label>
                        </div>
                        <div class="form-group" id="bush_ipl_container" style="display: none;">
                            <label for="bush_ipl_num">{{ __('Initial Bushing IPL Number') }}</label>
                            <input id="bush_ipl_num" type="text" class="form-control" name="bush_ipl_num"
                                   pattern="^\d+-\d+[A-Za-z]?$" title="Format: 1-200A, 1001-100, 5-398B" style="width: 100px">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">{{ __('Save Component') }}</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Component Modal (from component-inspection) --}}
<div class="modal fade" id="editComponentModal" tabindex="-1" aria-labelledby="editComponentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-gradient">
            <div class="modal-header">
                <h5 class="modal-title" id="editComponentModalLabel">{{ __('Edit Part') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST" id="editComponentForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="workorder_id" value="{{ $current_wo->id }}">
                <input type="hidden" name="manual_id" value="{{ $current_wo->unit->manual_id }}">
                <div class="modal-body">
                    <div class="form-group mb-2">
                        <label for="edit_name">{{ __('Name') }}</label>
                        <input id="edit_name" type="text" class="form-control" name="name" required>
                    </div>
                    <div class="d-flex flex-wrap">
                        <div class="m-3">
                            <div class="mb-2">
                                <label for="edit_ipl_num">{{ __('IPL Number') }}</label>
                                <input id="edit_ipl_num" type="text" class="form-control" name="ipl_num" required>
                            </div>
                            <div class="mb-2">
                                <label for="edit_part_number">{{ __('Part Number') }}</label>
                                <input id="edit_part_number" type="text" class="form-control" name="part_number" required>
                            </div>
                            <div class="mb-2">
                                <label for="edit_eff_code">{{ __('EFF Code') }}</label>
                                <input id="edit_eff_code" type="text" class="form-control" name="eff_code">
                            </div>
                            <div class="mb-2">
                                <label for="edit_units_assy">{{ __('Units per Assy') }}</label>
                                <input id="edit_units_assy" type="text" class="form-control" name="units_assy">
                            </div>
                            <div class="mb-2">
                                <label>{{ __('Image') }}</label>
                                <input type="file" name="img" class="form-control">
                            </div>
                        </div>
                        <div class="m-3">
                            <div class="mb-2">
                                <label for="edit_assy_ipl_num">{{ __('Assembly IPL Number') }}</label>
                                <input id="edit_assy_ipl_num" type="text" class="form-control" name="assy_ipl_num">
                            </div>
                            <div class="mb-2">
                                <label>{{ __('Assy Image') }}</label>
                                <input type="file" name="assy_img" class="form-control">
                            </div>
                            <div class="mb-2">
                                <label for="edit_assy_part_number">{{ __('Assembly Part Number') }}</label>
                                <input id="edit_assy_part_number" type="text" class="form-control" name="assy_part_number">
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="edit_log_card" name="log_card">
                                <label class="form-check-label" for="edit_log_card">Log Card</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="edit_repair" name="repair">
                                <label class="form-check-label" for="edit_repair">Repair</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="edit_is_bush" name="is_bush">
                                <label class="form-check-label" for="edit_is_bush">Is Bush</label>
                            </div>
                            <div class="mb-2" id="edit_bush_ipl_container" style="display: none;">
                                <label for="edit_bush_ipl_num">{{ __('Initial Bushing IPL Number') }}</label>
                                <input id="edit_bush_ipl_num" type="text" class="form-control" name="bush_ipl_num"
                                       pattern="^\d+-\d+[A-Za-z]?$" title="Format: 1-200A, 1001-100, 5-398B" style="width: 140px">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">{{ __('Save changes') }}</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
