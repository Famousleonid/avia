<style>
    #nav-traveler .traveler-template-workspace { flex: 1 1 auto; min-height: 0; overflow: auto; padding: 1rem; }
    .traveler-template-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 1rem; }
    .traveler-template-toolbar { --traveler-control-height: calc(1.3125rem + .5rem + 2px); display: flex; align-items: center; gap: .75rem; }
    .traveler-template-toolbar > button { flex: 0 0 auto; white-space: nowrap; }
    .traveler-template-toolbar select { width: auto; flex: 0 1 auto; min-width: 0; white-space: nowrap; height: var(--traveler-control-height); }
    .traveler-template-toolbar [data-template-preview] { flex: 0 0 auto; display: inline-flex; align-items: center; padding: 0; height: var(--traveler-control-height); }
    #nav-traveler .traveler-template-toolbar [data-template-preview] > i.bi { font-size: var(--traveler-control-height); line-height: 1; }
    .traveler-template-toolbar [data-template-preview] > i::before { display: block; }
    .traveler-template-grid > * { min-width: 0; }
    .traveler-template-images { margin-top: 1.5rem; padding: 1rem; border: 1px solid var(--avia-border, #46616f); border-radius: .5rem; }
    .traveler-attachments-row { display: flex; align-items: center; gap: 1rem; min-width: 0; max-width: 100%; overflow-x: auto; padding: .5rem 0; }
    .traveler-template-image-list { display: flex; flex: 0 0 auto; flex-wrap: nowrap; align-items: flex-start; gap: 1rem; }
    .traveler-attachments-row .traveler-image-dropzone { flex: 1 0 260px; width: auto; max-width: 420px; min-height: 112px; }
    .traveler-template-image-card { flex: 0 0 112px; width: 112px; max-width: 100%; min-width: 0; text-align: center; }
    .traveler-attachment-sheet { position: relative; width: 80px; height: 88px; margin: 0 auto; }
    .traveler-attachment-preview { display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; color: var(--bs-light, #f8f9fa); text-decoration: none; }
    .traveler-attachment-preview > i.bi { font-size: 72px; line-height: 1; color: #159fc9; filter: drop-shadow(0 3px 6px rgba(13, 202, 240, .15)); }
    html[data-bs-theme="dark"] .traveler-attachment-preview > i.bi { color: #86e2ff; }
    .traveler-attachment-preview:hover > i.bi { color: #0dcaf0; }
    .traveler-attachment-preview:hover { color: var(--bs-info, #0dcaf0); }
    .traveler-attachment-remove { position: absolute; top: 0; right: 0; display: flex; align-items: center; justify-content: center; width: 24px; height: 24px; padding: 0; border: 1px solid var(--avia-border, #46616f); border-radius: 50%; background: var(--bs-body-bg, #1e2c39); color: var(--bs-light, #f8f9fa); font-size: 20px; line-height: 1; cursor: pointer; }
    .traveler-attachment-remove:hover { color: #fff; background: var(--bs-danger, #dc3545); }
    .traveler-attachment-remove:disabled { opacity: .5; cursor: not-allowed; }
    .traveler-attachment-preview:focus-visible, .traveler-attachment-remove:focus-visible { outline: 2px solid var(--bs-info, #0dcaf0); outline-offset: 2px; }
    .traveler-template-image-card .traveler-image-name { overflow-wrap: anywhere; margin-top: .35rem; }
    .traveler-image-dropzone { display: block; width: 100%; min-width: 0; padding: 1rem; border: 2px dashed var(--avia-border, #46616f); border-radius: .4rem; background: transparent; color: inherit; text-align: center; cursor: pointer; }
    .traveler-image-dropzone:hover, .traveler-image-dropzone:focus-visible, .traveler-image-dropzone.is-dragover { border-color: var(--avia-info, #0dcaf0); background: rgba(13, 202, 240, .08); }
    .traveler-image-dropzone:disabled { opacity: .6; cursor: wait; }
    .traveler-template-active { display: flex; align-items: center; justify-content: center; height: calc(1.5em + .5rem + 2px); font-size: .875rem; margin: 0; cursor: pointer; }
    .traveler-template-active input { width: 1.5rem; height: 1.5rem; margin: 0; cursor: pointer; accent-color: var(--avia-info, #0dcaf0); }
    .traveler-template-step { background: #edf5f7; border: 1px solid #bdd2da; border-radius: .5rem; padding: .85rem; margin-bottom: .75rem; }
    html[data-bs-theme="dark"] .traveler-template-step { background: #263e49; border-color: #3d5a68; box-shadow: inset 0 1px 0 rgba(154, 211, 226, .05); }
    html[data-bs-theme="dark"] .traveler-template-step,
    html[data-bs-theme="dark"] .traveler-step-dropdown {
        --avia-input: #20343f;
        --avia-border: #46616f;
        --dir-input-bg: #20343f;
        --dir-input-border: #46616f;
        --dir-input-text: #edf5f8;
    }
    html[data-bs-theme="dark"] .traveler-step-dropdown { --avia-surface-raised: #20343f; }
    html[data-bs-theme="dark"] body .traveler-template-step .form-control,
    html[data-bs-theme="dark"] body .traveler-template-step .form-select { background-color: #20343f; color: #edf5f8; border-color: #46616f; }
    .traveler-template-step select, .traveler-template-step textarea { width: 100%; min-width: 0; }
    .traveler-template-step [data-process-name],
    .traveler-template-step [data-process],
    .traveler-template-step [data-description],
    .traveler-template-step .select2-selection__rendered,
    .traveler-step-dropdown .select2-search__field,
    .traveler-step-dropdown .select2-results__option,
    .traveler-step-dropdown .traveler-specification-option { font-size: 14px !important; }
    .traveler-template-step-head { display: flex; gap: .4rem; align-items: center; flex-wrap: wrap; margin-bottom: 0; }
    .traveler-step-toggle { display: inline-flex; align-items: center; gap: .45rem; padding: .25rem 0; margin-right: .5rem; border: 0; background: transparent; font: inherit; white-space: nowrap; cursor: pointer; }
    .traveler-step-toggle:focus-visible { outline: 2px solid var(--bs-info, #0dcaf0); outline-offset: 2px; }
    .traveler-step-body { margin-top: .75rem; }
    .traveler-step-body[hidden] { display: none !important; }
    .traveler-step-process-name { flex: 0 1 22rem; min-width: 0; margin: 0 auto 0 0; }
    .traveler-template-step-head > button { flex: 0 0 auto; }
    .traveler-step-membership { display: flex; align-items: center; gap: .4rem; margin: 0 .5rem 0 0; cursor: pointer; }
    .traveler-step-membership input { width: 1.1rem; height: 1.1rem; margin: 0; accent-color: var(--avia-info, #0dcaf0); }
    .traveler-process-picker { display: grid; grid-template-columns: minmax(0, 1fr); align-items: start; }
    .traveler-process-picker > label { min-width: 0; }
    .traveler-process-picker .select2-container { width: 100% !important; min-width: 0; }
    .traveler-specification-option { white-space: pre-wrap; overflow-wrap: anywhere; }
    .traveler-specification-comment { display: block; color: #ffc107; background: #263442; padding: .1rem .25rem; border-radius: .2rem; }
    html[data-bs-theme] body .select2-container .select2-dropdown .select2-results__option .traveler-specification-comment { color: #ffc107 !important; }
    @media (max-width: 600px) { .traveler-process-picker { grid-template-columns: minmax(0, 1fr); } }
    @media (max-width: 850px) { .traveler-template-grid { grid-template-columns: minmax(0, 1fr); } }
</style>
<div class="traveler-template-workspace" data-traveler-editor data-url="{{ route('manuals.traveler-templates.index', $cmm) }}">
    <div class="alert alert-danger d-none" role="alert" data-template-error></div>
    <div class="traveler-template-grid">
        <div class="traveler-template-toolbar">
            <button class="btn btn-outline-info btn-sm" type="button" data-template-new>New template</button>
            <label class="visually-hidden" for="traveler-template-select">Select template</label>
            <select id="traveler-template-select" class="form-select form-select-sm" data-template-list disabled>
                <option value="">Loading…</option>
            </select>
            <a class="btn btn-sm border-0 bg-transparent disabled" data-template-preview target="_blank" rel="noopener" aria-disabled="true" tabindex="-1" title="Preview saved Traveler" aria-label="Preview saved Traveler"><i class="bi bi-file-earmark-text-fill text-white" aria-hidden="true"></i></a>
        </div>
        <form data-template-form data-no-spinner hidden>
            <div class="row g-2 mb-2">
                <div class="col-auto d-flex align-items-end"><label class="traveler-template-active" title="Available in TDR"><input type="checkbox" name="active" checked title="Available in TDR" aria-label="Available in TDR"></label></div>
                <div class="col-md-3" style="min-width: max-content"><label class="form-label d-flex align-items-baseline gap-2 text-nowrap" for="traveler-template-pn">Part number <small id="traveler-template-pn-help" class="text-muted">Separate part numbers with ;</small></label>
                    <input id="traveler-template-pn" name="part_number" class="form-control form-control-sm" list="traveler-template-parts" required maxlength="255" autocomplete="off" aria-describedby="traveler-template-pn-help" placeholder="2821-0221; 2821-0222; 2821-0222R">
                    <datalist id="traveler-template-parts"></datalist>
                </div>
                <div class="col"><label class="form-label" for="traveler-template-name">Template name</label>
                    <input id="traveler-template-name" name="name" class="form-control form-control-sm" required maxlength="255">
                </div>
                <div class="col-auto d-flex align-items-end"><button class="btn btn-sm btn-outline-warning" type="button" data-template-details-save title="Edited — save template details" aria-label="Edited — save template details" hidden><i class="bi bi-pencil-square" aria-hidden="true"></i></button></div>
            </div>
            <div data-template-steps></div>
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-outline-info btn-sm" type="button" data-template-step-add>+ Add step</button>
                <span class="small text-info align-self-center" role="status" data-template-status></span>
            </div>
            <section class="traveler-template-images" aria-label="Traveler images">
                <h6>Traveler attachments</h6>
                <input type="file" accept="image/jpeg,image/png,image/webp,application/pdf,.pdf" multiple data-template-image-files hidden aria-label="Select Traveler images or PDFs">
                <div class="traveler-attachments-row">
                    <div class="traveler-template-image-list" data-template-images></div>
                    <button class="traveler-image-dropzone" type="button" data-template-image-upload>
                        <span>Drop files here or click to choose</span>
                        <small class="d-block text-muted mt-1">JPG, PNG, WebP, PDF · 5 MB/file · max 20</small>
                    </button>
                </div>
            </section>
        </form>
    </div>
</div>
