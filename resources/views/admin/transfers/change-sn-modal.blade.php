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
