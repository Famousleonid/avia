{{--
    Форма процессов Wo Bushing. Использует shared layout.
    Переменные передаются из WoBushingController::processesForm().
    Данные: table_data (wo_bushings формат — component, qty, process, process_name),
    ndt_processes, ndt1_name_id..ndt8_name_id (NDT), process_components (Other),
    process_name, current_wo, selectedVendor, manuals.
    Wo_bushings: только NDT и Other (без Stress Relief).
--}}
@php
    $module = $module ?? 'wo_bushings';
    $formConfig = $formConfig ?? config('process_forms.' . $module, config('process_forms.wo_bushings'));
@endphp
@include('shared.process-forms._layout', array_merge(get_defined_vars(), [
    'module' => $module,
    'formConfig' => $formConfig,
    'embedded' => $embedded ?? false,
]))
