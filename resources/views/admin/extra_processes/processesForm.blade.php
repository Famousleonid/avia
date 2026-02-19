{{--
    Форма процессов Extra Process. Использует shared layout.
    Переменные передаются из ExtraProcessController::processesForm().
    Данные: table_data (extra_processes формат), ndt_processes, ndt1_name_id..ndt8_name_id (NDT),
    process_components (Stress/Other), process_name, current_wo, selectedVendor, manuals.
--}}
@php
    $module = $module ?? 'extra_processes';
    $formConfig = $formConfig ?? config('process_forms.' . $module, config('process_forms.extra_processes'));
@endphp
@include('shared.process-forms._layout', array_merge(get_defined_vars(), [
    'module' => $module,
    'formConfig' => $formConfig,
    'embedded' => $embedded ?? false,
]))
