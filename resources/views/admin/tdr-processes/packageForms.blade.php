<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Package Process Forms</title>
    <link rel="stylesheet" href="{{asset('assets/Bootstrap 5/bootstrap.min.css')}}">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: "Times New Roman", serif;
        }
        .form-wrapper {
            page-break-after: always;
            page-break-inside: avoid;
            min-height: 100vh;
        }
        .form-wrapper:last-child {
            page-break-after: auto;
        }
        @media print {
            @page {
                size: letter portrait;
                margin: 1cm 1cm 1cm 1cm;
            }
            .form-wrapper {
                page-break-after: always;
                page-break-inside: avoid;
                min-height: 100vh;
            }
            .form-wrapper:last-child {
                page-break-after: auto;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
<div class="text-start m-3 no-print">
    <button class="btn btn-outline-primary" onclick="window.print()">Print All Forms</button>
</div>

@foreach($formsData as $index => $formData)
    <div class="form-wrapper">
        @php
            // Устанавливаем переменные для использования в processesForm
            $process_name = $formData['process_name'];
            $current_wo = $formData['current_wo'];
            $components = $formData['components'];
            $tdrs = $formData['tdrs'];
            $manuals = $formData['manuals'];
            $manual_id = $formData['manual_id'];
            $selectedVendor = $formData['selectedVendor'];
            $current_tdr = $formData['current_tdr'] ?? null;
            $hidePrintButton = true;
            
            // Устанавливаем переменные для NDT или обычных процессов
            if (isset($formData['ndt_processes'])) {
                $ndt_processes = $formData['ndt_processes'];
                $ndt_components = $formData['ndt_components'];
                $current_ndt_id = $formData['current_ndt_id'];
                $ndt1_name_id = $formData['ndt1_name_id'];
                $ndt4_name_id = $formData['ndt4_name_id'];
                $ndt6_name_id = $formData['ndt6_name_id'];
                $ndt5_name_id = $formData['ndt5_name_id'];
            } else {
                $process_components = $formData['process_components'];
                $process_tdr_components = $formData['process_tdr_components'];
            }
        @endphp
        @include('admin.tdr-processes.processesForm', array_merge($formData, ['hidePrintButton' => true]))
    </div>
@endforeach

</body>
</html>
