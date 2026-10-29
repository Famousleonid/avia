<script>
    window.currentWorkorderId = {{ $current_wo->id }};
    window.tdrShowUrl = '{{ route("tdrs.show", ["id" => $current_wo->id]) }}';
    window.tdrShowFragmentsUrl = '{{ route("tdrs.show.fragments", ["id" => $current_wo->id]) }}';
</script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script src="{{ asset('js/tdr-processes/sortable-handler.js') }}?v={{ filemtime(public_path('js/tdr-processes/sortable-handler.js')) }}"></script>
<script src="{{ asset('js/tdr-processes/form-link-handler.js') }}"></script>
<script src="{{ asset('js/tdr-processes/combined-form-handler.js') }}?v={{ filemtime(public_path('js/tdr-processes/combined-form-handler.js')) }}"></script>
<script src="{{ asset('js/tdr-processes/edit-process/edit-process.js') }}"></script>
<script src="{{ asset('js/delete-confirm-handler.js') }}"></script>
<script>
window.TdrshowConfig0 = {
    value0: @json(e(route("tdr-processes.update-order"))),
    value1: @json(e(route("vendors.store"))),
    value2: @json(e(__('Unable to save TDR order.'))),
    value3: @json(e(__('Unable to save TDR order.'))),
    value4: @json(e(__("Cancel"))),
    value5: @json(e(__("Delete"))),
    value6: @json(e(__("Delete Confirmation"))),
    value7: @json(e(__("Delete"))),
    value8: @json(e(__("Cancel")))
};
</script>
<script src="{{ asset('js/tdrs/show/page-actions.js') }}?v={{ filemtime(public_path('js/tdrs/show/page-actions.js')) }}"></script>
@include('admin.tdrs.partials.component-inspection-scripts')
<script>
window.TdrshowConfig1 = {
    value0: {!! json_encode(__('Select a batch (B1/B2) for this process before printing.')) !!},
    value1: {!! json_encode(__('Select rows using the small batch checkbox (not grouped yet).')) !!},
    value2: {!! json_encode(__('Select rows using the small checkbox next to B to ungroup.')) !!},
    value3: {!! json_encode(__('Please select rows from one process column only.')) !!},
    value4: @json(e($current_wo->id)),
    value5: @json(e($current_wo->id)),
    value6: @json(e(route("tdr-processes.processesBody", ["tdrId" => "__ID__"]))),
    value7: @json(e(route("tdr-processes.store"))),
    value8: @json(e(route("processes.getProcesses"))),
    value9: @json(e(route("tdr-processes.editForm", ["id" => "__ID__"]))),
    value10: @json(e(route("tdr-processes.update-order"))),
    value11: @json(e(route("extra_processes.processesPartial", ["workorderId" => "__WO__", "componentId" => "__COMP__"]))),
    value12: @json(e(route("extra_processes.edit_component", ["id" => "__ID__"]))),
    value13: @json(e(route("extra_processes.create_processes", ["workorderId" => "__WO__", "componentId" => "__COMP__"]))),
    value14: @json(e(route("extra_processes.edit", ["extra_process" => "__ID__"]))),
    value15: @json(e(route("extra_process.create", ["id" => "__ID__"]))),
    value16: @json(e(route("log_card.partial", ["workorder_id" => $current_wo->id]))),
    value17: @json(e(route("wo_bushings.partial", ["workorder_id" => $current_wo->id]))),
    value18: @json(e(route("rm_reports.partial", ["workorder_id" => $current_wo->id]))),
    value19: @json(e(route("log_card.store"))),
    value20: @json(e(route('log_card.update', ['log_card' => 9999991]))),
    value21: @json(e(route('log_card.inline_field.update', ['log_card' => 9999991]))),
    value22: @json(e(route("wo_bushings.edit", ["wo_bushing" => "__ID__"]))),
    value23: @json(e(url("/get-processes"))),
    value24: @json(e(__("Bushing data saved."))),
    value25: @json(e(__("Loading..."))),
    value26: @json(e(__("Update Bushings List"))),
    value27: @json(e(__("Update Bushings List"))),
    value28: @json(e($current_wo->number)),
    value29: @json(e(__("Failed to load."))),
    value30: @json(e(__("Open in new tab"))),
    value31: @json(e(__("Extra Parts Processes"))),
    value32: @json(e(__("Loading..."))),
    value33: @json(e(__("Failed to load."))),
    value34: @json(e(__("Loading..."))),
    value35: @json(e(__("Session expired. Please log in again."))),
    value36: @json(e(__("Session expired. Please log in again."))),
    value37: @json(e(__("Update Bushings List"))),
    value38: @json(e(__("Create Bushing Data"))),
    value39: @json(e(__("Clear All"))),
    value40: @json(e(__("Bushing Form"))),
    value41: @json(e(__("Failed to load."))),
    value42: @json(e(__("Update Log Card"))),
    value43: @json(e(__("Create Log Card"))),
    value44: @json(e(__("Loading..."))),
    value45: @json(e(__("Failed to load."))),
    value46: @json(e(__("Loading..."))),
    value47: @json(e(__("Failed to load."))),
    value48: @json(e(__("Loading..."))),
    value49: @json(e(__("Failed to load."))),
    value50: @json(e(__("Loading..."))),
    value51: @json(e(__("Failed to load."))),
    value52: @json(e(__("Failed to delete."))),
    value53: @json(e(__("Failed to delete."))),
    value54: @json(e(__("Ungroup"))),
    value55: @json(e(__("Traveler"))),
    value56: @json(e(__("Request failed."))),
    value57: @json(e(__("Request failed."))),
    value58: @json(e(__("Select only ungrouped rows to create a Traveler, or only grouped rows to ungroup."))),
    value59: @json(e(__("Select rows in the Traveler column."))),
    value60: @json(e(__("Selected values will be cleared before grouping. Continue?"))),
    value61: @json(e(__("Create Traveler"))),
    value62: @json(e(__("Create"))),
    value63: @json(e(__("Request failed."))),
    value64: @json(e(__("Request failed."))),
    value65: @json(e(__("Request failed."))),
    value66: @json(e(__("Request failed."))),
    value67: @json(e(__("Request failed."))),
    value68: @json(e(__('Description FIG summary'))),
    value69: @json(e(__('FIG counts'))),
    value70: @json(e(__('Print'))),
    value71: @json(e(__('No FIG references in Description.'))),
    value72: @json(e(__('The print window was blocked by the browser.'))),
    value73: @json(e(__("Loading..."))),
    value74: @json(e(__("Please select a component first."))),
    value75: @json(e(__("Failed to load processes."))),
    value76: @json(e(__("Select process name"))),
    value77: @json(e(__("Save"))),
    value78: @json(e(__("Search process name…"))),
    value79: @json(e(__("Select process name"))),
    value80: @json(e(__("Loading..."))),
    value81: @json(e(__("Failed to load processes."))),
    value82: @json(e(__("Creating new process is not allowed."))),
    value83: @json(e(__("No process found"))),
    value84: @json(e(__("Failed to load processes."))),
    value85: @json(e(__("Failed to load processes."))),
    value86: @json(e(__("Please select Process Name before adding specification."))),
    value87: @json(e(__("Creating new process is not allowed."))),
    value88: @json(e(__("Save Process"))),
    value89: @json(e(__("Select process and process name."))),
    value90: @json(e(__("Saving..."))),
    value91: @json(e(__("Save failed."))),
    value92: @json(e(__("Save"))),
    value93: @json(e(__("Save failed."))),
    value94: @json(e(__("Process added successfully."))),
    value95: @json(e(__("Save failed."))),
    value96: @json(e(__("Save"))),
    value97: @json(e(__("Please select Process Name before adding specification."))),
    value98: @json(e(__("Please enter the new process name."))),
    value99: @json(e(__("Saving..."))),
    value100: @json(e(route("processes.store"))),
    value101: @json(e(__("Save failed."))),
    value102: @json(e(__("Process added successfully."))),
    value103: @json(e(__("Save failed."))),
    value104: @json(e(__("Save Process"))),
    value105: @json(e(__("Are you sure you want to delete this process?"))),
    value106: @json(e(route("tdr-processes.destroy", ["tdr_process" => "__ID__"]))),
    value107: @json(e(__("Delete failed."))),
    value108: @json(e(__("Delete failed."))),
    value109: @json(e(__("Please select at least one component before submitting."))),
    value110: @json(e(__("Please enter Qty for selected bushings."))),
    value111: @json(e(__("Bushing data saved."))),
    value112: @json(e(__("Error creating bushings data."))),
    value113: @json(e(__("Failed to submit."))),
    value114: @json(e(__("Request timed out. Please try again."))),
    value115: @json(e(__("Failed to submit."))),
    value116: @json(e(__("Please select at least one component before submitting."))),
    value117: @json(e(__("Please enter quantity for all groups with selected components."))),
    value118: @json(e(__("Bushing data saved."))),
    value119: @json(e(__("Error creating bushings data."))),
    value120: @json(e(__("Failed to submit."))),
    value121: @json(e(__("Request timed out. Please try again."))),
    value122: @json(e(__("Failed to submit."))),
    value123: @json(e(__("Select at least one component for Log Card."))),
    value124: @json(e(__("Saved."))),
    value125: @json(e(__("Could not save."))),
    value126: @json(e(__("Request failed."))),
    value127: @json(e(__("Could not save."))),
    value128: @json(e(__("Request failed."))),
    value129: @json(e(__("Log Card editing is locked. Please contact Quality Manager."))),
    value130: @json(e(__("Opening..."))),
    value131: @json(e(__("Creating..."))),
    value132: @json(e(__("Log Card editing is locked. Please contact Quality Manager."))),
    value133: @json(e(__("Confirmation dialog is unavailable. Log Card was not changed."))),
    value134: @json(e(__("Update Log Card"))),
    value135: @json(e(__("Save the selected components in this Log Card?"))),
    value136: @json(e(__("Update"))),
    value137: @json(e(__("Continue editing"))),
    value138: @json(e(__("Saving..."))),
    value139: @json(e(__("Save"))),
    value140: @json(e(__("Click a component processes button to load."))),
    value141: @json(e(__("Click Processes in Extra Part Processes table to load."))),
    value142: @json(e(__("Invalid URL."))),
    value143: @json(e(__("Loading..."))),
    value144: @json(e(__("Failed to load."))),
    value145: @json(e(__("Form not found. You may need to log in again."))),
    value146: @json(e(__("Error."))),
    value147: @json(e(__("Failed to submit."))),
    value148: @json(e(__("Failed to load."))),
    value149: @json(e(__("Open in new tab"))),
    value150: @json(e(__("Process added successfully."))),
    value151: @json(e(__("Process updated successfully."))),
    value152: @json(e(__("Part added successfully."))),
    value153: @json(e(__("Process added successfully."))),
    value154: @json(e(__("Loading..."))),
    value155: @json(e(route("tdrs.editForm", ["id" => "__ID__"]))),
    value156: @json(e(__("Saving..."))),
    value157: @json(e(__("Updated."))),
    value158: @json(e(__("Failed to update."))),
    value159: @json(e(__("Failed to update."))),
    value160: @json(e(__("Failed to load form."))),
    value161: @json(url('/extra_processes/partial/'.$current_wo->id)),
    value162: @json(($hasTransfers ?? false) ? route('transfers.partial', ['workorder' => $current_wo->id]) : null)
};
window.TdrshowConfig1InitTransfers = function () {
@include('admin.transfers._sn-edit-script')
@include('admin.transfers._unit-on-po-edit-script')
};
</script>
<script src="{{ asset('js/tdrs/show/tabs.js') }}?v={{ filemtime(public_path('js/tdrs/show/tabs.js')) }}"></script>
<script src="{{ asset('js/tdrs/show/workorder-form-handler.js') }}"></script>
<script src="{{ asset('js/tdrs/show/pdf-badge-handler.js') }}"></script>
<script src="{{ asset('js/tdrs/show/pdf-library-handler.js') }}?v={{ filemtime(public_path('js/tdrs/show/pdf-library-handler.js')) }}"></script>
<script src="{{ asset('js/tdrs/show/pdf-viewer-handler.js') }}?v={{ filemtime(public_path('js/tdrs/show/pdf-viewer-handler.js')) }}"></script>
<script src="{{ asset('js/tdrs/show/pdf-upload-handler.js') }}?v={{ filemtime(public_path('js/tdrs/show/pdf-upload-handler.js')) }}"></script>
<script src="{{ asset('js/tdrs/show/pdf-delete-handler.js') }}"></script>
<script src="{{ asset('js/tdrs/show/show-main.js') }}"></script>
<script>
window.TdrshowConfig2 = {
    value0: @json(e(__("Saving..."))),
    value1: @json(e(csrf_token())),
    value2: @json(e(route("tdrs.store.unit-inspections"))),
    value3: @json(e(__("An error occurred while saving."))),
    value4: @json(e(__('Save'))),
    value5: @json(e(__("An error occurred while saving."))),
    value6: @json(e(__('Save')))
};
</script>
<script src="{{ asset('js/tdrs/show/unit-inspections.js') }}?v={{ filemtime(public_path('js/tdrs/show/unit-inspections.js')) }}"></script>
<script>
window.TdrshowConfig3 = {
    value0: @json(e(csrf_token())),
    value1: @json(e(__("Saving..."))),
    value2: @json(e(__("(No name)"))),
    value3: @json(e(__("An error occurred while saving."))),
    value4: @json(e(__("Save"))),
    value5: @json(e(__("Save"))),
    value6: @json(e(__("Are you sure you want to delete condition"))),
    value7: @json(e(__("Deleting..."))),
    value8: @json(e(__("An error occurred while deleting."))),
    value9: @json(e(__("Delete"))),
    value10: @json(e(__("An error occurred while deleting."))),
    value11: @json(e(__("Delete"))),
    value12: @json(e(__("Saving..."))),
    value13: @json(e(route("conditions.store"))),
    value14: @json(e(__("An error occurred while saving."))),
    value15: @json(e(__("Save Condition"))),
    value16: @json(e(__("An error occurred while saving."))),
    value17: @json(e(__("Save Condition")))
};
</script>
<script src="{{ asset('js/tdrs/show/conditions.js') }}?v={{ filemtime(public_path('js/tdrs/show/conditions.js')) }}"></script>
<script>
window.TdrshowConfig4 = {
    value0: @json(e(__('Delete confirmation dialog is not available.'))),
    value1: @json(e(__('Delete Manufacture pair'))),
    value2: @json(e(__('This will delete both linked Manufacture entries. Create a new TDR entry if the part needs a different disposition.'))),
    value3: @json(e(__('Delete both'))),
    value4: @json(e(__('Cancel')))
};
</script>
<script src="{{ asset('js/tdrs/show/part-images.js') }}?v={{ filemtime(public_path('js/tdrs/show/part-images.js')) }}"></script>


