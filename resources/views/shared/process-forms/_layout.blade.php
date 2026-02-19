{{--
    Общий layout для печатных форм процессов (NDT, Stress Relief, Other).
    Используется в tdr-processes, extra_processes, wo_bushings.

    $embedded = true: только контент формы (для packageForms) — без html/head/body, кнопок, модалки
    Обязательные переменные: $module, $process_name, $current_wo, $selectedVendor
--}}
@php
    $formConfig = $formConfig ?? config('process_forms.' . ($module ?? 'tdr-processes'), config('process_forms.tdr-processes'));
    $embedded = $embedded ?? false;
    $header_title = $header_title ?? ($formConfig['header_title'] ?? null);
    // Для одиночной формы — только настройки текущего типа; для packageForms передаётся showFormTypes
    $showFormTypes = $showFormTypes ?? (
        isset($process_name) && $process_name->process_sheet_name == 'NDT' ? ['ndt'] :
        (isset($process_name) && $process_name->process_sheet_name == 'STRESS RELIEF' ? ['stress'] : ['other'])
    );
@endphp
@if(!$embedded)
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $process_name->process_sheet_name ?? $process_name->name ?? 'Process' }}</title>
    <link rel="stylesheet" href="{{ asset('assets/Bootstrap 5/bootstrap.min.css') }}">
    @include('shared.process-forms._styles')
</head>
<body>
@if(!isset($hidePrintButton) || !$hidePrintButton)
<div class="text-start m-3 no-print">
    <button class="btn btn-outline-primary" onclick="window.print()">Print Form</button>
    <button class="btn btn-secondary ms-2" data-bs-toggle="modal" data-bs-target="#printSettingsModal">⚙️ Print Settings</button>
</div>
@endif

@if(!isset($hidePrintSettingsModal) || !$hidePrintSettingsModal)
@include('shared.process-forms._print-settings-modal', ['module' => $module ?? 'tdr-processes', 'formConfig' => $formConfig, 'showFormTypes' => $showFormTypes])
@endif
@endif

<div class="container-fluid">
    <div class="form-page-block form-page-block-first">
        @include('shared.process-forms._header')

        @if(isset($process_name) && $process_name->process_sheet_name == 'NDT')
            @include('shared.process-forms.ndt._content', array_merge(get_defined_vars(), ['formConfig' => $formConfig]))
        @elseif(isset($process_name) && $process_name->process_sheet_name == 'STRESS RELIEF')
            @include('shared.process-forms.stress._content', array_merge(get_defined_vars(), ['formConfig' => $formConfig]))
        @else
            @include('shared.process-forms.other._content', array_merge(get_defined_vars(), ['formConfig' => $formConfig]))
        @endif

        @include('shared.process-forms._footer')
    </div>
</div>

@if(!$embedded)
@include('shared.process-forms._scripts', ['module' => $module ?? 'tdr-processes', 'formConfig' => $formConfig])

@if(!isset($hideBootstrapJS) || !$hideBootstrapJS)
<script>
    if (typeof window.bootstrapLoaded === 'undefined') {
        window.bootstrapLoaded = true;
        const script = document.createElement('script');
        script.src = "{{ asset('assets/Bootstrap 5/bootstrap.bundle.min.js') }}";
        script.async = true;
        document.head.appendChild(script);
    }
</script>
@endif
</body>
</html>
@else
{{-- В embedded режиме (packageForms) скрипты и модалка на странице-хозяине --}}
@include('shared.process-forms._scripts', ['module' => $module ?? 'tdr-processes', 'formConfig' => $formConfig])
@endif
