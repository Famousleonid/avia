<style>
    #nav-traveler .traveler-template-workspace { flex: 1 1 auto; min-height: 0; overflow: auto; padding: 1rem; }
    .traveler-template-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 1rem; }
    .traveler-template-toolbar { display: flex; align-items: center; gap: .75rem; }
    .traveler-template-toolbar > button { flex: 0 0 auto; white-space: nowrap; }
    .traveler-template-toolbar select { width: auto; flex: 0 0 auto; white-space: nowrap; }
    .traveler-template-grid > * { min-width: 0; }
    .traveler-template-active { display: flex; align-items: center; justify-content: center; height: calc(1.5em + .5rem + 2px); font-size: .875rem; margin: 0; cursor: pointer; }
    .traveler-template-active input { width: 1.5rem; height: 1.5rem; margin: 0; cursor: pointer; accent-color: var(--avia-info, #0dcaf0); }
    .traveler-template-step { background: #edf5f7; border: 1px solid #bdd2da; border-radius: .5rem; padding: .85rem; margin-bottom: 2rem; }
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
    .traveler-template-step-head { display: flex; gap: .4rem; align-items: center; flex-wrap: wrap; margin-bottom: .5rem; }
    .traveler-template-step-head > [data-step-number] { flex: 0 0 auto; white-space: nowrap; margin-right: .5rem; }
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
        </form>
    </div>
</div>
