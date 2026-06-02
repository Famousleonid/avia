@php
    $module = $module ?? 'wo_bushings';
    $formConfig = $formConfig ?? config('process_forms.' . $module, config('process_forms.wo_bushings'));
@endphp
@include('shared.process-forms._layout', array_merge(get_defined_vars(), [
    'module' => $module,
    'formConfig' => $formConfig,
    'embedded' => $embedded ?? false,
]))
