{{--
    Форма процессов TDR. Использует shared layout.
    Переменные передаются из TdrProcessController::processesForm(), show(), packageForms().
--}}
@php
    $module = $module ?? 'tdr-processes';
    $formConfig = $formConfig ?? config('process_forms.' . $module, config('process_forms.tdr-processes'));
@endphp
@include('shared.process-forms._layout', array_merge(get_defined_vars(), [
    'module' => $module,
    'formConfig' => $formConfig,
    'embedded' => $embedded ?? false,
]))
