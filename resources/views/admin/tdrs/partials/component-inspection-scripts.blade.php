{{-- Component Inspection form scripts (from component-inspection) --}}
<script>
window.TdrinspectionConfig0 = {
    value0: {!! json_encode($canManageAllManualParts ?? false) !!},
    value1: {!! json_encode($allowedManualIds ?? []) !!},
    value2: @json($manual_id),
    value3: @json(e(route("components.showJson", ["component" => "__ID__"]))),
    value4: @json(e(route("components.updateFromInspection", ["component" => "__ID__"]))),
    value5: @json($manual_id),
    value6: @json(e(__("Loading..."))),
    value7: @json(e(__("Loading..."))),
    value8: @json(e(route("api.get-components-by-manual"))),
    value9: @json($current_wo->id),
    value10: @json(e(csrf_token())),
    value11: @json($logCardSerialsByComponent ?? [])
};
</script>
<script src="{{ asset('js/tdrs/show/component-inspection.js') }}?v={{ filemtime(public_path('js/tdrs/show/component-inspection.js')) }}"></script>
<script>
window.TdrinspectionConfig1 = {
    value0: {!! json_encode($canManageAllManualParts ?? false) !!},
    value1: {!! json_encode($allowedManualIds ?? []) !!},
    value2: @json($manual_id),
    value3: @json(e(__("Description"))),
    value4: @json(e(__("Description"))),
    value5: @json(e(__("P/N"))),
    value6: @json(e(__("P/N"))),
    value7: @json(e(__("Click code"))),
    value8: @json(e(__("Click necessary"))),
    value9: @json(e(__("S/N"))),
    value10: @json(e(__("Add"))),
    value11: @json(e(__("Loading..."))),
    value12: @json(e(__("Loading..."))),
    value13: @json(e(route("api.get-components-by-manual"))),
    value14: @json($current_wo->id),
    value15: @json(e(csrf_token())),
    value16: @json(e($current_wo->number)),
    value17: @json(e($current_wo->number)),
    value18: @json(e(route("components.showJson", ["component" => "__ID__"]))),
    value19: @json(e(route("components.updateFromInspection", ["component" => "__ID__"]))),
    value20: @json(e(__('Confirmation dialog is unavailable. Image was not deleted.'))),
    value21: @json(e(__('Delete image'))),
    value22: @json(e(__('Delete the current part image?'))),
    value23: @json(e(__('Delete'))),
    value24: @json(e(__('Cancel'))),
    value25: @json(e(csrf_token())),
    value26: @json(e(__('Failed to delete image.'))),
    value27: @json(e(__('Image deleted.'))),
    value28: @json(e(__('Failed to delete image.'))),
    value29: @json(e(__('Select a part before saving.'))),
    value30: @json(e(__('Select a code before saving.'))),
    value31: @json(e(__("Select order component."))),
    value32: @json(e(__("Saving..."))),
    value33: @json(e(csrf_token())),
    value34: @json(e(__("Failed to save."))),
    value35: @json(e(__("Saved."))),
    value36: @json(e(__("Failed to save."))),
    value37: @json($logCardSerialsByComponent ?? []),
    value38: @json($missingCondition?->id),
    value39: @json($necessary?->id)
};
</script>
<script src="{{ asset('js/tdrs/show/inline-inspection.js') }}?v={{ filemtime(public_path('js/tdrs/show/inline-inspection.js')) }}"></script>
