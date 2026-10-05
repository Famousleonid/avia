<div class="modal-header">
    <div><h5 class="modal-title">{{ __('Add Component') }}</h5>
        <div class="text-muted small">{{ __('Trainee') }}: {{ $targetUser->stamp }} — {{ $targetUser->selection_name }}</div>
        <div class="text-info mt-1" id="addUnitPartTitle"></div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body">
    <div class="alert alert-danger d-none" role="alert" id="addUnitError"></div>
    @include('admin.trainings.partials.unit-form')
</div>
<template id="addUnitPaper132"><x-paper-button text="132" color="outline-info" width="34" fontSize="72px" disabled aria-disabled="true" tabindex="-1" /></template>
<template id="addUnitPaper112"><x-paper-button text="112" color="outline-success" width="34" fontSize="72px" disabled aria-disabled="true" tabindex="-1" /></template>
