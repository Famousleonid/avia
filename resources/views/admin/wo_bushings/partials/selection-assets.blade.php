<script>
    window.bushingSelectionConfig = {
        url: @json(route('workorders.bushing-selection.show', $current_wo)),
        csrf: @json(csrf_token()),
        updateFormUrl: @json(url('/wo_bushings')),
    };
</script>
<script src="{{ asset('js/wo-bushings/shared-selection.js') }}?v={{ filemtime(public_path('js/wo-bushings/shared-selection.js')) }}"></script>
