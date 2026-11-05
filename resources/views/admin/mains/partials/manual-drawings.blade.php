<div class="modal fade" id="manualDrawingsModal" tabindex="-1" aria-labelledby="manualDrawingsTitle"
     data-list-url="{{ route('manuals.drawings.index', $manual_id) }}"
     data-upload-url="{{ route('manuals.drawings.store', $manual_id) }}">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header gap-2">
                <h6 class="modal-title" id="manualDrawingsTitle">{{ __('Manual drawings') }} — {{ $current_workorder->unit?->manual?->number }}</h6>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <aside class="manual-drawings-library">
                    <div class="manual-drawings-drop" tabindex="0" role="button" aria-label="Upload drawings">
                        <i class="bi bi-cloud-arrow-up fs-3" aria-hidden="true"></i>
                        <strong>Drop drawings here or click to browse</strong>
                        <small>PDF, JPG, PNG, WebP · up to 10 MB each</small>
                        <input type="file" class="d-none" multiple accept=".pdf,.jpg,.jpeg,.png,.webp">
                    </div>
                    <small class="text-muted">Shared by all workorders using this manual.</small>
                    <div class="manual-drawings-status small" role="status" aria-live="polite"></div>
                    <div class="manual-drawings-list" aria-label="Drawings"></div>
                </aside>
                <section class="manual-drawings-preview" aria-label="Drawing preview">
                    <div class="manual-drawings-toolbar">
                        <span class="manual-drawing-name">Select a drawing</span>
                        <button type="button" class="btn btn-outline-warning btn-sm manual-drawing-rename d-none">Rename</button>
                        <button type="button" class="btn btn-outline-danger btn-sm manual-drawing-delete d-none">Delete</button>
                        <button type="button" class="btn btn-info text-dark btn-sm manual-drawing-print" disabled><i class="bi bi-printer" aria-hidden="true"></i> Print</button>
                    </div>
                    <form class="manual-drawing-rename-form d-none" data-no-spinner>
                        <label for="manualDrawingName" class="small">Drawing name</label>
                        <input id="manualDrawingName" name="name" type="text" class="form-control form-control-sm" maxlength="200" required autocomplete="off">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-outline-success btn-sm">Save</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm manual-drawing-rename-cancel">Cancel</button>
                        </div>
                    </form>
                    <div class="manual-drawing-empty text-muted">Choose a drawing from the list to view and print.</div>
                    <iframe class="d-none" title="Drawing preview"></iframe>
                </section>
            </div>
        </div>
    </div>
</div>
