<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PRL Form</title>
    <link rel="stylesheet" href="{{asset('assets/Bootstrap 5/bootstrap.min.css')}}">

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: "Times New Roman", serif;
        }

        :root {
            --container-max-width: 1020px;
            --container-padding: 0;
            --container-margin-left: 0;
            --container-margin-right: 0;
            --print-page-margin: 5mm 5mm 5mm 5mm;
            --print-body-height: 86%;
            --print-body-width: 100%;
            --table-font-size: 0.875rem;
            --prl-row-height: 32px;
            --print-footer-width: 100%;
            --print-footer-font-size: 10px;
            --print-footer-padding: 3px 3px;
        }

        .container-fluid {
            max-width: var(--container-max-width);
            width: 100%;
            height: 99%;
            padding: var(--container-padding);
            margin: var(--container-margin-left) var(--container-margin-right);
        }

        @media print {
            .container-fluid {
                max-width: 100%;
                width: 100%;
                padding: 0;
                margin: 0;
            }
        }

        @media print {
            /* Задаем размер страницы Letter (8.5 x 11 дюймов) */
            @page {
                size: letter;
                margin: var(--print-page-margin);
            }
            /* Убираем фиксированное позиционирование */
            .header-page {
                position: static;
                width: 100%;
                background-color: white;
            }

            /* Разрыв страницы после каждой страницы, кроме последней */
            .page:not(:last-of-type) {
                page-break-after: always;
            }

            .header-page {
                position: running(header);
            }
            /* Убедитесь, что вся страница помещается на один лист */
            html, body {
                height: var(--print-body-height);
                width: var(--print-body-width);
                margin: 0;
                padding: 0;
            }

            /*!* Контейнер использует всю ширину без дополнительных отступов *!*/
            .container-fluid {
                max-width: 100%;
                width: 100%;
                padding: 0;
                margin: 0;
            }

            /* Отключаем разрывы страниц внутри элементов */
            table, h1, p {
                page-break-inside: avoid;
            }

            /*!* Скрываем ненужные элементы при печати *!*/
            .no-print {
                display: none;
            }

            /*!* Скрываем строки сверх лимита *!*/
            .print-hide-row {
                display: none !important;
            }

            /* Показываем клоны блока печатей только при печати */
            .stamps-block-clone {
                display: block;
            }

            /* Колонтитул внизу страницы */
            footer {
                position: fixed;
                bottom: 0;
                width: var(--print-footer-width);
                text-align: center;
                font-size: var(--print-footer-font-size);
                background-color: #fff;
                padding: var(--print-footer-padding);
            }

            /* Обрезка контента и размещение на одной странице */
            .container {
                max-height: 100vh;
                overflow: hidden;
            }

            /* Оптимизация таблицы для печати */
            .data-row-prl {
                width: 100% !important;
                margin: 0 !important;
                padding: 0;
                font-size: var(--table-font-size);
            }

            /* Применение font-size ко всей таблице PRL при печати (включая заголовки) */
            .header-page .row.mt-2.ms-3,
            .header-page .row.mt-2.ms-3 h6,
            .all-rows-container .data-row-prl,
            .all-rows-container .data-row-prl h6,
            .stamps-block .data-row-prl,
            .stamps-block-clone .data-row-prl {
                font-size: var(--table-font-size) !important;
            }

            .row {
                margin-left: 10px !important;
                margin-right: 0 !important;
            }

            /* Убираем отступы от ms-2 и других классов */
            .ms-2 {
                margin-left: 0 !important;
            }

            /* Убеждаемся, что таблица использует всю ширину */
            .col-1, .col-2, .col-3, .col-4, .col-5, .col-6, .col-7, .col-8, .col-9, .col-10 {
                padding-left: 0;
                padding-right: 0;
            }

            /* Убираем пробел между col-5 и col-7 */
            .col-5 {
                padding-right: 0 !important;
            }

            .col-7 {
                padding-left: 0 !important;
            }

            /* Убираем все отступы у контейнера и его дочерних элементов */
            .container-fluid > * {
                margin-left: 0;
                margin-right: 0;
            }

        }

        /* Скрываем строки сверх лимита на экране тоже */
        .print-hide-row {
            display: none !important;
        }

        /* Клоны блока печатей, создаваемые для печати */
        .stamps-block-clone {
            display: block; /* Показываем на экране и при печати */
        }

        /* Стили для строк таблицы PRL */
        .data-row-prl {
            font-size: var(--table-font-size);
            min-height: var(--prl-row-height, 32px);
        }

        /* Применение font-size ко всей таблице PRL (включая заголовки) */
        .header-page .row.mt-2.ms-3,
        .header-page .row.mt-2.ms-3 h6,
        .all-rows-container .data-row-prl,
        .all-rows-container .data-row-prl h6,
        .stamps-block .data-row-prl,
        .stamps-block-clone .data-row-prl {
            font-size: var(--table-font-size) !important;
        }

        .border-all {
            border: 1px solid black;
        }
        .border-all-b {
            border: 2px solid black;
        }

        .border-l-t-r {
            border-left: 1px solid black;
            border-top: 1px solid black;
            border-right: 1px solid black;
        }
        .border-l-b-r {
            border-left: 1px solid black;
            border-bottom: 1px solid black;
            border-right: 1px solid black;
        }
        .border-lll-b-r {
            border-left: 8px  solid lightgrey;
            border-bottom: 1px solid black;
            border-right: 1px solid black;
        }
        .border-b-r {
            border-bottom: 1px solid black;
            border-right: 1px solid black;
        }
        .border-l-b-rrr {
            border-left: 1px solid black;
            border-bottom: 1px solid black;
            border-right: 5px solid black;
        }
        .border-l-b {
            border-left: 1px solid black;
            border-bottom: 1px solid black;

        }
        .border-t-r {
            border-top: 1px solid black;
            border-right: 1px solid black;
        }
        .border-t-b {
            border-top: 1px solid black;
            border-bottom: 1px solid black;
        }
        .border-l-t-b {
            border-left: 1px solid black;
            border-top: 1px solid black;
            border-bottom: 1px solid black;
        }
        .border-ll-bb {
            border-left: 2px solid black;
            border-bottom: 2px solid black;

        }
        .border-ll-bb-rr {
            border-left: 2px solid black;
            border-bottom: 2px solid black;
            border-right: 2px solid black;
        }
        .border-bb {
            border-bottom: 2px solid black;
        }
        .border-b {
            border-bottom: 1px solid black;
        }
        .border-t-r-b {
            border-top: 1px solid black;
            border-right: 1px solid black;
            border-bottom: 1px solid black;
        }
        .border-l-t-r-b {
            border-left: 1px solid black;
            border-top: 1px solid black;
            border-right: 1px solid black;
            border-bottom: 1px solid black;
        }
        .border-r-b {

            border-right: 1px solid black;
            border-bottom: 1px solid black;
        }
        .text-center {
            text-align: center;

        }

        .text-black {
            color: #000;
        }

        /*.p-1, .p-2, .p-3, .p-4 {*/
        /*    padding: 0.25rem;*/
        /*    padding: 0.5rem;*/
        /*    padding: 0.75rem;*/
        /*    padding: 1rem;*/
        /*}*/

        .topic-header {
            width: 100px;
        }

        .topic-content {
            width: 600px;
        }

        .topic-content-2 {
            width: 701px;
        }

        .hrs-topic, .trainer-init {
            width: 100px;
        }
        .hrs-topic-1,.trainer-init-1 {
            width: 98px;
        }
        .trainer-init-1 {
            width: 99px;
        }
        .fs-7 {
            font-size: 0.9rem; /* или любое другое подходящее значение */
        }
        .fs-75 {
            font-size: 0.8rem; /* или любое другое подходящее значение */
        }
        .fs-8 {
            font-size: 0.7rem; /* или любое другое подходящее значение */
        }
        .fs-9 {
            font-size: 0.4rem; /* или любое другое подходящее значение */
        }

        .details-row {
            display: flex;
            align-items: center; /* Выравнивание элементов по вертикали */
            height: var(--prl-row-height, 32px); /* Высота строки — настраивается в Print Settings */
        }
        .details-cell {
            flex-grow: 1; /* Позволяет колонкам растягиваться и занимать доступное пространство */
            display: flex;
            justify-content: center; /* Центрирование содержимого по горизонтали */
            align-items: center; /* Центрирование содержимого по вертикали */
            border: 1px solid black; /* Границы для наглядности */
        }
        .check-icon {
            width: 22px; /* Меньший размер изображения */
            height: auto;
            margin: 0 5px; /* Отступы вокруг изображения */
        }

        /* Единая сетка PRL-таблицы по ширине колонок (в процентах) */
        .prl-col-fig   { flex: 0 0 5%;  max-width: 5%; }
        .prl-col-item  { flex: 0 0 5%;  max-width: 5%; }
        .prl-col-desc  { flex: 0 0 25%; max-width: 25%; }
        .prl-col-part  { flex: 0 0 35%; max-width: 35%; }
        .prl-col-qty   { flex: 0 0 5%; max-width: 5%; }
        .prl-col-code  { flex: 0 0 5%; max-width: 5%; }
        .prl-col-po    { flex: 0 0 10%; max-width: 10%; }
        .prl-col-notes { flex: 0 0 10%; max-width: 10%; }

        /* Минимальная высота для строк PRL (в т.ч. пустых) — настраивается в Print Settings */
        .data-row-prl {
            min-height: var(--prl-row-height, 32px);
        }

    </style>
</head>

<body>
<!-- Кнопки для печати и настроек -->
<div class="text-start m-3 no-print">
    <button class="btn btn-outline-primary" onclick="window.print()">
        Print Form
    </button>
    <button class="btn btn-secondary ms-2" data-bs-toggle="modal" data-bs-target="#printSettingsModal">
        ⚙️ Print Settings
    </button>
</div>

<div class="container-fluid">
    <!-- Первая страница с заголовком -->
    <div class="page data-page" data-page-index="1">
        <!-- Верхняя часть формы -->
        <div class="header-page">
            <div class="row">
                <div class="col-4">
                    <img src="{{ asset('img/icons/AT_logo-rb.svg') }}" alt="Logo"
                         style="width: 120px; margin: 6px 10px 0;">
                </div>
                <div class="col-8">
                    <h5 class="p-2 mt-3 text-black text-"><strong>PART REPLACEMENT LIST</strong></h5>
                </div>
            </div>
            <div class="row">
                <div class="col-1 text-end pe-2"><h6><strong>P/N:</strong> </h6></div>
                <div class="col-4 ">
                    <div class="border-b">
                        <h6 class=""><strong> {{$current_wo->unit->part_number}}</strong></h6>
                    </div>
                </div>
                <div class="col-4 ">
                    <div class="row ">
                        <div class="col-10 border-b">
                            <div class="d-flex ">
                                <h6 class=" "><strong>MFR: </strong></h6>
                                @foreach($manuals as $manual)
                                    @if($manual->id == $current_wo->unit->manual_id)
                                        <h6 class=" ms-2"><strong> {{$manual->builder->name}}</strong></h6>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                        <div class="col-2 border-b"> </div>
                    </div>
                </div>
                <div class="col-3 ">
                    <h5 class=" border-all text-center  " style="height: 36px;align-content: center">
                        <strong>{{__('WO No:     W')}}{{$current_wo->number}}</strong>
                    </h5>
                </div>
            </div>
            <div class="row mt-1">
                <div class="col-1"><h6 class="ms-3 me-3"><strong>DESC: </strong></h6></div>
                <div class="col-4  ">
                            <div class="  border-b">
                                @foreach($manuals as $manual)
                                    @if($manual->id == $current_wo->unit->manual_id)
                                        <h6 class=""><strong> {{$manual->title}}</strong></h6>
                                    @endif
                                @endforeach
                            </div>
                </div>
                <div class="col-5 ">
                    <div class="row">
                        <div class="col-2 border-b">
                            <h6 class="" ><strong>CMM: </strong></h6>
                        </div>
                        <div class="col-8 border-b">
                            @php
                                $uniqueManuals = $uniqueManuals ?? [];
                                $hasMultipleManuals = $hasMultipleManuals ?? false;
                            @endphp
                            @if($hasMultipleManuals && count($uniqueManuals) > 0)
                                {{-- Показываем все номера manual через ';' --}}
                                <h6 class=""><strong>{{ implode('; ', array_map(function($num) { return substr($num, 0, 8); }, $uniqueManuals)) }}</strong></h6>
                            @else
                                {{-- Показываем основной manual --}}
                                @foreach($manuals as $manual)
                                    @if($manual->id == $current_wo->unit->manual_id)
                                        <h6 class=""><strong> {{substr($manual->number, 0, 8)}}</strong></h6>
                                    @endif
                                @endforeach
                            @endif
                        </div>
                        <div class="col-3"></div>
                    </div>
                </div>
            </div>


            <div class="row mt-2 ms-3" style="width: 100%">
                <div class="col-1 prl-col-fig border-l-t-b text-center align-content-center">
                            <h6 style="margin-top: 5px; font-size: 0.75rem;">FIG No.</h6>
                        </div>
                <div class="col-1 prl-col-item border-l-t-b text-center align-content-center">
                    <h6 style="margin-top: 5px; font-size: 0.75rem;">ITEM No.</h6>
                        </div>
                <div class="col-3 prl-col-desc border-l-t-b text-center align-content-center">
                    <h6 style="font-size: 0.8rem;">DESCRIPTION</h6>
                    </div>
                <div class="col-3 prl-col-part border-l-t-b text-center align-content-center">
                            <h6 style="margin-top: 10px; font-size: 0.75rem;">PART NUMBER</h6>
                        </div>
                <div class="col-1 prl-col-qty border-l-t-b text-center align-content-center">
                            <h6 style="margin-top: 10px; font-size: 0.75rem;">QTY</h6>
                        </div>
                <div class="col-1 prl-col-code border-l-t-b text-center align-content-center">
                            <h6 style="margin-top: 10px; font-size: 0.75rem;">CODE</h6>
                        </div>
                <div class="col-1 prl-col-po border-l-t-b text-center align-content-center">
                            <h6 style="margin-top: 10px; font-size: 0.75rem;">PO No.</h6>
                        </div>
                <div class="col-1 prl-col-notes border-all text-center align-content-center">
                            <h6 style="margin-top: 10px; font-size: 0.75rem;">Notes</h6>
                </div>
            </div>
        </div>

        <!-- Контейнер для всех строк (разбиение на страницы происходит на фронтенде) -->
        <div class="all-rows-container">
            @php
                $ordersParts = $ordersParts ?? [];
                $uniqueManuals = $uniqueManuals ?? [];
                $hasMultipleManuals = $hasMultipleManuals ?? false;
                $previousManual = null;
                $rowIndex = 1;
            @endphp

            @if(count($ordersParts) > 0)
            @foreach($ordersParts as $tdr)
                @php
                    // Проверяем, является ли $tdr массивом или объектом
                    $isArray = is_array($tdr);

                    // Получаем manual (работает и для массива, и для объекта)
                    $currentManual = $isArray ? ($tdr['manual'] ?? null) : ($tdr->manual ?? null);

                    // Если manual изменился и не пустой, и есть несколько manual, вставляем строку с manual
                    $hasMultipleManuals = $hasMultipleManuals ?? false;
                    $shouldInsertManualRow = $hasMultipleManuals && ($currentManual !== null && $currentManual !== '' && $currentManual !== $previousManual);

                    // Получаем компонент (orderComponent или component)
                    if ($isArray) {
                        $component = $tdr['orderComponent'] ?? $tdr['component'] ?? null;
                    } else {
                        $component = $tdr->orderComponent ?? $tdr->component;
                    }

                    // Используем assy_ipl_num если он есть и не пустой, иначе ipl_num
                    $ipl_num = '';
                    if ($component) {
                        if (is_object($component)) {
                            $ipl_num = (isset($component->assy_ipl_num) && $component->assy_ipl_num !== null && $component->assy_ipl_num !== '')
                                ? $component->assy_ipl_num
                                : ($component->ipl_num ?? '');
                        } else {
                            $ipl_num = (isset($component['assy_ipl_num']) && $component['assy_ipl_num'] !== null && $component['assy_ipl_num'] !== '')
                                ? $component['assy_ipl_num']
                                : ($component['ipl_num'] ?? '');
                        }
                    }
                    $ipl_parts = explode('-', $ipl_num);
                    $first_part = $ipl_parts[0] ?? '';
                    $second_part = $ipl_parts[1] ?? '';
                @endphp

                @if($shouldInsertManualRow)
                    {{-- Строка с Manual --}}
                    <div class="row data-row-prl ms-3 manual-row" style="width: 100%" data-row-index="{{ $rowIndex }}">
                        <div class="border-l-b-r text-center align-content-center" style="font-weight: bold;">
                                    <strong>{{ $currentManual }}</strong>
                                </div>
{{--                        <div class="col-5">--}}
{{--                            <div class="row" style="height: 40px">--}}
{{--                                <div class="col-1 border-l-b text-center align-content-center">--}}
{{--                                    <!-- Пустая ячейка -->--}}
{{--                                </div>--}}
{{--                                <div class="col-2 border-l-b text-center align-content-center">--}}
{{--                                    <!-- Пустая ячейка -->--}}
{{--                                </div>--}}
{{--                                --}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                        <div class="col-7">--}}
{{--                            <div class="row" style="height: 40px">--}}
{{--                                <div class="col-4 border-l-b text-center align-content-center">--}}
{{--                                    <!-- Пустая ячейка -->--}}
{{--                                </div>--}}
{{--                                <div class="col-1 border-l-b text-center align-content-center">--}}
{{--                                    <!-- Пустая ячейка -->--}}
{{--                                </div>--}}
{{--                                <div class="col-1 border-l-b text-center align-content-center">--}}
{{--                                    <!-- Пустая ячейка -->--}}
{{--                                </div>--}}
{{--                                <div class="col-2 border-l-b text-center align-content-center">--}}
{{--                                    <!-- Пустая ячейка -->--}}
{{--                                </div>--}}
{{--                                <div class="col-2 border-l-b-r text-center align-content-center">--}}
{{--                                    <!-- Пустая ячейка -->--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                        </div>--}}
                    </div>
                    @php $rowIndex++; @endphp
                @endif

                <div class="row data-row-prl ms-3" style="width: 100%" data-row-index="{{ $rowIndex }}">
                    <div class="col-1 prl-col-fig border-l-b text-center pt-1 align-content-center">
                        <h6>{{ $first_part }}</h6>
                            </div>
                    <div class="col-1 prl-col-item border-l-b text-center pt-2 align-content-center">
                                <h6>{{ $second_part }}</h6>
                            </div>
                    <div class="col-3 prl-col-desc border-l-b text-center pt-1 align-content-center">
                                @php
                                    if ($component) {
                                        $componentName = is_object($component) ? ($component->name ?? '') : ($component['name'] ?? '');
                                        $assyPartNumber = is_object($component) ? ($component->assy_part_number ?? '') : ($component['assy_part_number'] ?? '');
                                        echo $assyPartNumber ? $componentName . ' ASSY' : $componentName;
                                    }
                                @endphp
                            </div>
                    <div class="col-3 prl-col-part border-l-b text-center pt-2 align-content-center">
                                @if($component)
                                    <h6>
                                        @php
                                            $assyPartNumber = is_object($component) ? ($component->assy_part_number ?? '') : ($component['assy_part_number'] ?? '');
                                            $partNumber = is_object($component) ? ($component->part_number ?? '') : ($component['part_number'] ?? '');
                                        @endphp
                                        {{ (!empty($assyPartNumber)) ? $assyPartNumber : $partNumber }}
                                    </h6>
                                @else
                                    <h6> </h6>
                                @endif
                            </div>
                    <div class="col-1 prl-col-qty border-l-b text-center pt-2 align-content-center">
                                <h6>{{ $isArray ? ($tdr['qty'] ?? '') : ($tdr->qty ?? '') }}</h6>
                            </div>
                    <div class="col-1 prl-col-code border-l-b text-center pt-2 align-content-center">
                                @php
                                    if ($isArray) {
                                        $code = isset($tdr['codes']) && is_array($tdr['codes']) ? ($tdr['codes']['code'] ?? '') : '';
                                    } else {
                                        $code = $tdr->codes->code ?? '';
                                    }
                                @endphp
                                <h6>{{ $code }}</h6>
                            </div>
                    <div class="col-1 prl-col-po border-l-b text-center pt-1 align-content-center">
                                @php
                                    $poRaw = $isArray ? ($tdr['po_num'] ?? '') : ($tdr->po_num ?? '');
                                    if (\Illuminate\Support\Str::startsWith($poRaw, 'Transfer from WO')) {
                                        // Оставляем только номер WO после "Transfer from WO"
                                        $poDisplay = trim(\Illuminate\Support\Str::after($poRaw, 'Transfer from WO'));
                                    } else {
                                        $poDisplay = $poRaw;
                                    }
                                @endphp
                                <h6>{{ $poDisplay }}</h6>
                            </div>
                    <div class="col-1 prl-col-notes border-l-b-r text-center pt-1 align-content-center">
                                <h6>{{ $isArray ? ($tdr['notes'] ?? '') : ($tdr->notes ?? '') }}</h6>
                    </div>
                </div>
                @php
                    $rowIndex++;
                    $previousManual = $currentManual;
                @endphp
            @endforeach
            @endif
            </div>

        <!-- Блок с печатями (отображается на последней странице через JavaScript) -->
        <div class="stamps-block mt-3" style="display: none; ">
            <div class="row data-row-prl ms-3 " style="width: 100%">
                <div class="prl-col-fig "></div>
                <div class="prl-col-item "></div>
                <div class="prl-col-desc "></div>
                <div class="prl-col-part "></div>
                <div class="prl-col-qty border-l-t-b text-center align-content-center d-flex justify-content-center
                align-items-center" style="height: 40px">
                        <img src="{{ asset('img/icons/prod_st.png') }}" alt="stamp"
                         style="max-height: 40px; width: 34px">
                    </div>
                <div class="prl-col-code border-all text-center align-content-center d-flex justify-content-center align-items-center">
                        <img src="{{ asset('img/icons/qual_st.png') }}" alt="stamp"
                         style="max-height: 40px; width: 34px;padding-bottom: 1">
                    </div>
                <div class="prl-col-po "></div>
                <div class="prl-col-notes "></div>
                </div>
        </div>

    <footer>
        <div class="row" style="width: 100%; padding: 5px 0;">
            <div class="col-6 text-start">
               <h6>{{__("Form #028")}}</h6>
            </div>
            <div class="col-6 text-end pe-4 ">
                <h6>{{__('Rev#0, 15/Dec/2012   ')}}</h6>
            </div>
        </div>
    </footer>
</div>

<!-- Модальное окно настроек печати -->
<div class="modal fade print-settings-modal" id="printSettingsModal" tabindex="-1" aria-labelledby="printSettingsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header justify-content-between">
                <h5 class="modal-title" id="printSettingsModalLabel">
                    ⚙️ Print Settings
                </h5>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="langToggleBtn" onclick="toggleTooltipLanguage()">
                        <span id="langToggleText">US</span>
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body">
                <form id="printSettingsForm">
                    <!-- Table Setting - Основная группа (не collapse) -->
                    <div class="mb-4">
                        <h5 class="mb-3" data-bs-toggle="tooltip"
                            data-bs-placement="top"
                            title="Настройки количества строк в таблице PRL. Строки сверх лимита скрываются при печати. Настройки применяются автоматически при загрузке страницы."
                            data-tooltip-ru="Настройки количества строк в таблице PRL. Строки сверх лимита скрываются при печати. Настройки применяются автоматически при загрузке страницы."
                            data-tooltip-en="PRL table row settings. Rows exceeding the limit are hidden when printing. Settings are applied automatically on page load.">
                            📊 Tables
                        </h5>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="prlTableRows" class="form-label" data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        title="Максимальное количество строк в таблице PRL на одной странице. По умолчанию: 19 строк. Используется для всех страниц формы."
                                        data-tooltip-ru="Максимальное количество строк в таблице PRL на одной странице. По умолчанию: 19 строк. Используется для всех страниц формы."
                                        data-tooltip-en="Maximum number of rows in PRL table per page. Default: 19 rows. Used for all pages of the form.">
                                    PRL Table (row)
                                </label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="prlTableRows" name="prlTableRows"
                                           min="1" max="100" step="1" value="19">
                                </div>
                            </div>
                        </div>

                        <!-- Table Setting (collapse) -->
                        <div class="accordion mb-3" id="tableSettingsAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="tableSettingsHeading">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#tableSettingsCollapse" aria-expanded="false"
                                            aria-controls="tableSettingsCollapse">
                                        <span data-bs-toggle="tooltip" data-bs-placement="right"
                                              title="Дополнительные настройки таблицы: ширина контейнера."
                                              data-tooltip-ru="Дополнительные настройки таблицы: ширина контейнера."
                                              data-tooltip-en="Additional table settings: container width.">
                                            Table Setting
                                        </span>
                                    </button>
                                </h2>
                                <div id="tableSettingsCollapse" class="accordion-collapse collapse"
                                     aria-labelledby="tableSettingsHeading" data-bs-parent="#tableSettingsAccordion">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="containerMaxWidth" class="form-label" data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        title="Максимальная ширина контейнера с таблицей в пикселях. Рекомендуемое значение: 1020px для PRL формы."
                                                        data-tooltip-ru="Максимальная ширина контейнера с таблицей в пикселях. Рекомендуемое значение: 1020px для PRL формы."
                                                        data-tooltip-en="Maximum width of the table container in pixels. Recommended value: 1020px for PRL form.">
                                                    Max Width (px)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="containerMaxWidth" name="containerMaxWidth"
                                                           min="500" max="2000" step="10" value="1020">
                                                </div>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="tableFontSize" class="form-label" data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        title="Размер шрифта текста в таблице PRL. Рекомендуемое значение: 0.875rem (14px). Увеличьте для лучшей читаемости."
                                                        data-tooltip-ru="Размер шрифта текста в таблице PRL. Рекомендуемое значение: 0.875rem (14px). Увеличьте для лучшей читаемости."
                                                        data-tooltip-en="Font size for PRL table text. Recommended value: 0.875rem (14px). Increase for better readability.">
                                                    Font Size (rem)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="tableFontSize" name="tableFontSize"
                                                           min="0.5" max="2" step="0.05" value="0.875">
                                                </div>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="prlRowHeight" class="form-label" data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        title="Высота строк с данными в таблице PRL в пикселях. По умолчанию: 28px."
                                                        data-tooltip-ru="Высота строк с данными в таблице PRL в пикселях. По умолчанию: 28px."
                                                        data-tooltip-en="Height of data rows in PRL table in pixels. Default: 28px.">
                                                    Data Row Height (px)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="prlRowHeight" name="prlRowHeight"
                                                           min="18" max="60" step="1" value="28">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Page Setting (collapse) -->
                    <div class="mb-4">
                        <div class="accordion" id="pageSettingsAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="pageSettingsHeading">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#pageSettingsCollapse" aria-expanded="false"
                                            aria-controls="pageSettingsCollapse">
                                        <span data-bs-toggle="tooltip" data-bs-placement="right"
                                              title="Настройки страницы: ширина, высота, поля и отступы. Влияют на отступы при печати и позиционирование контента."
                                              data-tooltip-ru="Настройки страницы: ширина, высота, поля и отступы. Влияют на отступы при печати и позиционирование контента."
                                              data-tooltip-en="Page settings: width, height, margins and padding. Affect print margins and content positioning.">
                                            Page Setting
                                        </span>
                                    </button>
                                </h2>
                                <div id="pageSettingsCollapse" class="accordion-collapse collapse"
                                     aria-labelledby="pageSettingsHeading" data-bs-parent="#pageSettingsAccordion">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="bodyWidth" class="form-label" data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        title="Ширина основного контента в процентах от ширины страницы. 102% - стандартное значение для PRL формы."
                                                        data-tooltip-ru="Ширина основного контента в процентах от ширины страницы. 102% - стандартное значение для PRL формы."
                                                        data-tooltip-en="Main content width as percentage of page width. 102% - standard value for PRL form.">
                                                    Width (%)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="bodyWidth" name="bodyWidth"
                                                           min="50" max="110" step="1" value="102">
                                                </div>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="bodyHeight" class="form-label" data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        title="Высота основного контента в процентах от высоты страницы. 86% - стандартное значение для PRL формы."
                                                        data-tooltip-ru="Высота основного контента в процентах от высоты страницы. 86% - стандартное значение для PRL формы."
                                                        data-tooltip-en="Main content height as percentage of page height. 86% - standard value for PRL form.">
                                                    Height (%)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="bodyHeight" name="bodyHeight"
                                                           min="50" max="100" step="1" value="86">
                                                </div>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="pageMargin" class="form-label" data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        title="Отступ от краев страницы при печати. Рекомендуемое значение: 5mm для PRL формы. Увеличьте, если контент обрезается принтером."
                                                        data-tooltip-ru="Отступ от краев страницы при печати. Рекомендуемое значение: 5mm для PRL формы. Увеличьте, если контент обрезается принтером."
                                                        data-tooltip-en="Margin from page edges when printing. Recommended value: 5mm for PRL form. Increase if content is cut off by the printer.">
                                                    Margin (mm)
                                                </label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" id="pageMargin" name="pageMargin"
                                                           placeholder="5mm 5mm 5mm 5mm" value="5mm 5mm 5mm 5mm">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Setting (collapse) -->
                    <div class="mb-4">
                        <div class="accordion" id="footerSettingsAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="footerSettingsHeading">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#footerSettingsCollapse" aria-expanded="false"
                                            aria-controls="footerSettingsCollapse">
                                        <span data-bs-toggle="tooltip" data-bs-placement="right"
                                              title="Настройки нижнего колонтитула формы. Колонтитул содержит номер формы, ревизию и общее количество компонентов."
                                              data-tooltip-ru="Настройки нижнего колонтитула формы. Колонтитул содержит номер формы, ревизию и общее количество компонентов."
                                              data-tooltip-en="Form footer settings. Footer contains form number, revision and total component count.">
                                            Footer Setting
                                        </span>
                                    </button>
                                </h2>
                                <div id="footerSettingsCollapse" class="accordion-collapse collapse"
                                     aria-labelledby="footerSettingsHeading" data-bs-parent="#footerSettingsAccordion">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="footerWidth" class="form-label" data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        title="Ширина колонтитула в процентах. 100% - стандартное значение для PRL формы."
                                                        data-tooltip-ru="Ширина колонтитула в процентах. 100% - стандартное значение для PRL формы."
                                                        data-tooltip-en="Footer width as percentage. 100% - standard value for PRL form.">
                                                    Width (%)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="footerWidth" name="footerWidth"
                                                           min="50" max="100" step="1" value="100">
                                                </div>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="footerFontSize" class="form-label" data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        title="Размер шрифта текста в колонтитуле. 10px - стандартное значение. Увеличьте для лучшей читаемости."
                                                        data-tooltip-ru="Размер шрифта текста в колонтитуле. 10px - стандартное значение. Увеличьте для лучшей читаемости."
                                                        data-tooltip-en="Footer text font size. 10px - standard value. Increase for better readability.">
                                                    Font Size (px)
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="footerFontSize" name="footerFontSize"
                                                           min="6" max="20" step="0.5" value="10">
                                                </div>
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="footerPadding" class="form-label" data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        title="Внутренние отступы колонтитула в формате CSS (вертикальный горизонтальный). Например: '3px 3px' означает 3px сверху/снизу и 3px слева/справа."
                                                        data-tooltip-ru="Внутренние отступы колонтитула в формате CSS (вертикальный горизонтальный). Например: '3px 3px' означает 3px сверху/снизу и 3px слева/справа."
                                                        data-tooltip-en="Footer inner padding in CSS format (vertical horizontal). Example: '3px 3px' means 3px top/bottom and 3px left/right.">
                                                    Padding
                                                </label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" id="footerPadding" name="footerPadding"
                                                           placeholder="3px 3px" value="3px 3px">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="resetPrintSettings()">Reset to Default</button>
                <button type="button" class="btn btn-primary" onclick="savePrintSettings()">Save Settings</button>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS для работы модального окна -->
<script>
    if (typeof window.bootstrapLoaded === 'undefined') {
        window.bootstrapLoaded = true;
        const script = document.createElement('script');
        script.src = "{{asset('assets/Bootstrap 5/bootstrap.bundle.min.js')}}";
        script.async = true;
        document.head.appendChild(script);
    }
</script>

<script>
    // Ключ для сохранения настроек печати
    const PRINT_SETTINGS_KEY = 'prlForm_print_settings';
    const TOOLTIP_LANG_KEY = 'prlForm_tooltip_lang';

    // Настройки по умолчанию
    const defaultSettings = {
        pageMargin: '5mm 5mm 5mm 5mm',
        bodyWidth: '102%',
        bodyHeight: '86%',
        containerMaxWidth: '1020px',
        tableFontSize: '0.875rem',
        footerWidth: '100%',
        footerFontSize: '10px',
        footerPadding: '3px 3px',
        prlTableRows: '19',
        prlRowHeight: '28'
    };

    // Загрузка настроек из localStorage
    function loadPrintSettings() {
        const saved = localStorage.getItem(PRINT_SETTINGS_KEY);
        if (saved) {
            try {
                return JSON.parse(saved);
            } catch (e) {
                console.error('Ошибка загрузки настроек:', e);
                return defaultSettings;
            }
        }
        return defaultSettings;
    }

    // Сохранение настроек в localStorage
    window.savePrintSettings = function() {
        try {
            const getValue = function(id, defaultValue, suffix = '') {
                const element = document.getElementById(id);
                if (element) {
                    return element.value + (suffix ? suffix : '');
                }
                return defaultValue;
            };

            const settings = {
                pageMargin: getValue('pageMargin', '5mm 5mm 5mm 5mm', ''),
                bodyWidth: getValue('bodyWidth', '102', '%'),
                bodyHeight: getValue('bodyHeight', '86', '%'),
                containerMaxWidth: getValue('containerMaxWidth', '1020', 'px'),
                tableFontSize: getValue('tableFontSize', '0.875', 'rem'),
                footerWidth: getValue('footerWidth', '100', '%'),
                footerFontSize: getValue('footerFontSize', '10', 'px'),
                footerPadding: getValue('footerPadding', '3px 3px', ''),
                prlTableRows: getValue('prlTableRows', '19', ''),
                prlRowHeight: getValue('prlRowHeight', '28', '') + 'px'
            };

            localStorage.setItem(PRINT_SETTINGS_KEY, JSON.stringify(settings));
            applyPrintSettings(settings);
            applyTableRowLimits(settings);

            // Убираем фокус с активного элемента перед закрытием модального окна
            if (document.activeElement && document.activeElement.blur) {
                document.activeElement.blur();
            }

            // Закрываем модальное окно
            const modal = bootstrap.Modal.getInstance(document.getElementById('printSettingsModal'));
            if (modal) {
                modal.hide();
            }

            if (typeof showNotification === 'function') showNotification('Settings saved successfully!', 'success'); else alert('Settings saved successfully!');
        } catch (e) {
            console.error('Ошибка сохранения настроек:', e);
            if (typeof showNotification === 'function') showNotification('Error saving settings', 'error'); else alert('Error saving settings');
        }
    };

    // Применение CSS переменных
    function applyPrintSettings(settings) {
        const root = document.documentElement;
        root.style.setProperty('--print-page-margin', settings.pageMargin || defaultSettings.pageMargin);
        root.style.setProperty('--print-body-width', settings.bodyWidth || defaultSettings.bodyWidth);
        root.style.setProperty('--print-body-height', settings.bodyHeight || defaultSettings.bodyHeight);
        root.style.setProperty('--container-max-width', settings.containerMaxWidth || defaultSettings.containerMaxWidth);
        root.style.setProperty('--table-font-size', settings.tableFontSize || defaultSettings.tableFontSize);
        root.style.setProperty('--prl-row-height', (settings.prlRowHeight || defaultSettings.prlRowHeight).replace(/px$/, '') + 'px');
        root.style.setProperty('--print-footer-width', settings.footerWidth || defaultSettings.footerWidth);
        root.style.setProperty('--print-footer-font-size', settings.footerFontSize || defaultSettings.footerFontSize);
        root.style.setProperty('--print-footer-padding', settings.footerPadding || defaultSettings.footerPadding);
    }

    // Загрузка настроек в форму
    function loadSettingsToForm(settings) {
        const elements = {
            'pageMargin': { suffix: '', default: '5mm 5mm 5mm 5mm' },
            'bodyWidth': { suffix: '', default: '102' },
            'bodyHeight': { suffix: '', default: '86' },
            'containerMaxWidth': { suffix: '', default: '1020' },
            'tableFontSize': { suffix: '', default: '0.875' },
            'footerWidth': { suffix: '', default: '100' },
            'footerFontSize': { suffix: '', default: '10' },
            'footerPadding': { suffix: '', default: '3px 3px' },
            'prlTableRows': { suffix: '', default: '19' },
            'prlRowHeight': { suffix: '', default: '28' }
        };

        Object.keys(elements).forEach(function(id) {
            const element = document.getElementById(id);
            if (element) {
                const value = settings[id] || elements[id].default;
                if (id === 'pageMargin' || id === 'footerPadding') {
                    element.value = value;
                } else if (id === 'tableFontSize') {
                    // Для font-size используем parseFloat, так как это может быть десятичное число
                    element.value = parseFloat(value) || parseFloat(elements[id].default);
                } else {
                    element.value = parseInt(value) || elements[id].default;
                }
            }
        });
    }

    // Применение ограничений строк таблицы - создание физических страниц
    function applyTableRowLimits(settings) {
        const prlMaxRows = parseInt(settings.prlTableRows) || 19;
        console.log('Применение ограничений строк PRL:', { prlMaxRows, settings });

        const allRowsContainer = document.querySelector('.all-rows-container');
        if (!allRowsContainer) {
            console.warn('Контейнер .all-rows-container не найден!');
            return;
        }

        // Удаляем все созданные ранее дополнительные страницы и их контейнеры
        // Находим оригинальный container-fluid (содержит первую страницу с data-page-index="1")
        let originalContainerFluid = null;
        document.querySelectorAll('.container-fluid').forEach(function(container) {
            if (container.querySelector('.data-page[data-page-index="1"]')) {
                originalContainerFluid = container;
            }
        });

        // Если не нашли, берем первый контейнер без класса dynamic-page-wrapper
        if (!originalContainerFluid) {
            originalContainerFluid = document.querySelector('.container-fluid:not(.dynamic-page-wrapper)')
                || document.querySelector('.container-fluid');
        }

        // Удаляем все container-fluid, которые были созданы динамически (помечены классом dynamic-page-wrapper)
        document.querySelectorAll('.container-fluid.dynamic-page-wrapper').forEach(function(container) {
            container.remove();
        });

        // Удаляем все созданные ранее страницы внутри оригинального контейнера (на случай, если они там остались)
        if (originalContainerFluid) {
            originalContainerFluid.querySelectorAll('.data-page[data-page-index]').forEach(function(page) {
                const pageIndex = page.getAttribute('data-page-index');
                if (pageIndex && parseInt(pageIndex) > 1) {
                    page.remove();
                }
            });
        }

        // Удаляем все пустые строки, созданные ранее
        document.querySelectorAll('.all-rows-container .data-row-prl.empty-row').forEach(function(row) {
            row.remove();
        });

        // Удаляем ранее добавленные клоны блока печатей
        document.querySelectorAll('.stamps-block-clone').forEach(function(block) {
            block.remove();
        });

        // Собираем все строки из контейнера
        const allRows = Array.from(allRowsContainer.querySelectorAll('.data-row-prl:not(.empty-row)'));

        // Разделяем на manual-row и data-rows
        const manualRows = allRows.filter(function(row) {
            return row.classList.contains('manual-row');
        });
        const dataRows = allRows.filter(function(row) {
            return !row.classList.contains('manual-row');
        });

        const hasManualRows = manualRows.length > 0;
        console.log('Найдено manual-row:', hasManualRows);
        console.log('Найдено строк с данными:', dataRows.length);

        let totalRows;
        let rowsToProcess;

        if (hasManualRows) {
            // Случай с manual-row: считаем все строки (manual + data)
            totalRows = allRows.length;
            rowsToProcess = allRows;
        } else {
            // Случай без manual-row: считаем только data-rows
            totalRows = dataRows.length;
            rowsToProcess = dataRows;
        }

        // Вычисляем количество страниц
        const totalPages = Math.max(1, Math.ceil(totalRows / prlMaxRows));
        console.log('Всего строк:', totalRows, ', Лимит на странице:', prlMaxRows, ', Создано страниц:', totalPages);

        // Находим элементы для копирования
        const originalHeader = document.querySelector('.header-page');
        const originalFooter = document.querySelector('footer');
        const containerFluid = originalContainerFluid || document.querySelector('.container-fluid');
        const stampsBlock = document.querySelector('.stamps-block');

        // Скрываем строки, которые не на первой странице
        rowsToProcess.forEach(function(row, index) {
            if (index < prlMaxRows) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });

        // Создаём дополнительные страницы (начиная со второй)
        for (let pageIndex = 1; pageIndex < totalPages; pageIndex++) {
            const startIndex = pageIndex * prlMaxRows;
            const endIndex = Math.min(startIndex + prlMaxRows, rowsToProcess.length);
            const pageRows = rowsToProcess.slice(startIndex, endIndex);

            // Пропускаем создание страницы, если нет строк для неё
            if (pageRows.length === 0) {
                console.warn('Пропущена пустая страница:', pageIndex + 1);
                continue;
            }

            // Создаём контейнер для новой страницы (как container-fluid)
            const pageContainer = document.createElement('div');
            pageContainer.className = 'container-fluid dynamic-page-wrapper';

            // Создаём новую страницу
            const pageDiv = document.createElement('div');
            pageDiv.className = 'page data-page';
            pageDiv.setAttribute('data-page-index', pageIndex + 1);
            pageDiv.style.pageBreakBefore = 'always';

            // Копируем header
            if (originalHeader) {
                const headerClone = originalHeader.cloneNode(true);
                pageDiv.appendChild(headerClone);
            }

            // Создаём контейнер для строк этой страницы (как all-rows-container)
            const rowsContainer = document.createElement('div');
            rowsContainer.className = 'all-rows-container';

            // Клонируем строки для этой страницы
            pageRows.forEach(function(row) {
                const rowClone = row.cloneNode(true);
                rowClone.style.display = '';
                rowsContainer.appendChild(rowClone);
            });

            // Добавляем пустые строки на последней странице, если нужно
            if (pageIndex === totalPages - 1) {
                const rowsOnLastPage = totalRows % prlMaxRows;
                const emptyRowsNeeded = rowsOnLastPage === 0 ? 0 : (prlMaxRows - rowsOnLastPage);

                if (emptyRowsNeeded > 0) {
                    for (let i = 0; i < emptyRowsNeeded; i++) {
                        const emptyRow = document.createElement('div');
                        emptyRow.className = 'row data-row-prl ms-3 empty-row';
                        emptyRow.style.width = '100%';
                        emptyRow.innerHTML = `
                            <div class="prl-col-fig border-l-b text-center align-content-center"><h6></h6></div>
                            <div class="prl-col-item border-l-b text-center align-content-center"><h6></h6></div>
                            <div class="prl-col-desc border-l-b text-center align-content-center"><h6></h6></div>
                            <div class="prl-col-part border-l-b text-center align-content-center"><h6></h6></div>
                            <div class="prl-col-qty border-l-b text-center align-content-center"><h6></h6></div>
                            <div class="prl-col-code border-l-b text-center align-content-center"><h6></h6></div>
                            <div class="prl-col-po border-l-b text-center align-content-center"><h6></h6></div>
                            <div class="prl-col-notes border-l-b-r text-center align-content-center"><h6></h6></div>
                        `;
                        rowsContainer.appendChild(emptyRow);
                    }
                    console.log('Добавлено пустых строк на последнюю страницу:', emptyRowsNeeded);
                }

                // Добавляем блок с печатями на последнюю страницу
                if (stampsBlock) {
                    const stampsClone = stampsBlock.cloneNode(true);
                    stampsClone.style.display = 'block';
                    stampsClone.classList.add('stamps-block-clone');
                    rowsContainer.appendChild(stampsClone);
                    console.log('Блок печатей добавлен на последнюю страницу');
            } else {
                    console.warn('Блок печатей не найден для последней страницы');
                }
            }

            pageDiv.appendChild(rowsContainer);

            // Копируем footer
            if (originalFooter) {
                const footerClone = originalFooter.cloneNode(true);
                pageDiv.appendChild(footerClone);
            }

            // Добавляем pageDiv в pageContainer
            pageContainer.appendChild(pageDiv);

            // Вставляем страницу после container-fluid
            if (containerFluid && containerFluid.parentNode) {
                containerFluid.parentNode.insertBefore(pageContainer, containerFluid.nextSibling);
            } else {
                document.body.appendChild(pageContainer);
            }
        }

        // Добавляем пустые строки на первую страницу, если это единственная страница и нужно
        if (totalPages === 1) {
            const rowsOnLastPage = totalRows % prlMaxRows;
            const emptyRowsNeeded = rowsOnLastPage === 0 ? 0 : (prlMaxRows - rowsOnLastPage);

            console.log('Расчет пустых строк для первой страницы:', {
                totalRows: totalRows,
                prlMaxRows: prlMaxRows,
                rowsOnLastPage: rowsOnLastPage,
                emptyRowsNeeded: emptyRowsNeeded
            });

            if (emptyRowsNeeded > 0 && allRowsContainer) {
                // Находим все видимые строки (не скрытые через display: none)
                const visibleRows = Array.from(allRowsContainer.querySelectorAll('.data-row-prl:not(.empty-row)')).filter(function(row) {
                    return row.style.display !== 'none';
                });

                console.log('Найдено видимых строк:', visibleRows.length);

                if (visibleRows.length > 0) {
                    const lastVisibleRow = visibleRows[visibleRows.length - 1];

                    for (let i = 0; i < emptyRowsNeeded; i++) {
                        const emptyRow = document.createElement('div');
                        emptyRow.className = 'row data-row-prl ms-3 empty-row';
                        emptyRow.style.width = '100%';
                        emptyRow.innerHTML = `
                            <div class="prl-col-fig border-l-b text-center align-content-center"><h6></h6></div>
                            <div class="prl-col-item border-l-b text-center align-content-center"><h6></h6></div>
                            <div class="prl-col-desc border-l-b text-center align-content-center"><h6></h6></div>
                            <div class="prl-col-part border-l-b text-center align-content-center"><h6></h6></div>
                            <div class="prl-col-qty border-l-b text-center align-content-center"><h6></h6></div>
                            <div class="prl-col-code border-l-b text-center align-content-center"><h6></h6></div>
                            <div class="prl-col-po border-l-b text-center align-content-center"><h6></h6></div>
                            <div class="prl-col-notes border-l-b-r text-center align-content-center"><h6></h6></div>
                        `;
                        // Вставляем после последней видимой строки
                        lastVisibleRow.insertAdjacentElement('afterend', emptyRow);
                    }
                    console.log('Добавлено пустых строк на первую страницу:', emptyRowsNeeded);
                } else {
                    console.warn('Не найдено видимых строк для добавления пустых строк');
                }
            }

            // Добавляем блок с печатями на первую страницу, если это единственная страница
            if (stampsBlock && allRowsContainer) {
                const stampsClone = stampsBlock.cloneNode(true);
                stampsClone.style.display = 'block';
                stampsClone.classList.add('stamps-block-clone');
                allRowsContainer.appendChild(stampsClone);
                console.log('Блок печатей добавлен на первую страницу');
            } else {
                if (!stampsBlock) {
                    console.warn('Блок печатей не найден для первой страницы');
                }
                if (!allRowsContainer) {
                    console.warn('Контейнер all-rows-container не найден');
                }
            }
        }

        console.log('Ограничения строк применены. Создано страниц:', totalPages);
    }

    // Сброс настроек к значениям по умолчанию
    window.resetPrintSettings = function() {
        if (confirm('Reset all print settings to default values?')) {
            localStorage.removeItem(PRINT_SETTINGS_KEY);
            loadSettingsToForm(defaultSettings);
            applyPrintSettings(defaultSettings);
            setTimeout(function() {
                applyTableRowLimits(defaultSettings);
            }, 50);
            if (typeof showNotification === 'function') showNotification('Settings reset to default values!', 'success'); else alert('Settings reset to default values!');
        }
    };

    // Функция переключения языка tooltips
    window.toggleTooltipLanguage = function() {
        const modal = document.getElementById('printSettingsModal');
        if (!modal) return;

        let currentLang = localStorage.getItem(TOOLTIP_LANG_KEY) || 'ru';
        currentLang = currentLang === 'ru' ? 'en' : 'ru';
        localStorage.setItem(TOOLTIP_LANG_KEY, currentLang);

        updateTooltipsLanguage(modal, currentLang);

        const langText = document.getElementById('langToggleText');
        if (langText) {
            langText.textContent = currentLang === 'ru' ? 'RUS' : 'US';
        }
    };

    // Функция обновления языка всех tooltips
    function updateTooltipsLanguage(container, lang) {
        const tooltipElements = container.querySelectorAll('[data-bs-toggle="tooltip"]');

        tooltipElements.forEach(function(el) {
            const existingTooltip = bootstrap.Tooltip.getInstance(el);
            if (existingTooltip) {
                existingTooltip.dispose();
            }

            const ruText = el.getAttribute('data-tooltip-ru');
            const enText = el.getAttribute('data-tooltip-en');

            if (lang === 'ru' && ruText) {
                el.setAttribute('title', ruText);
            } else if (lang === 'en' && enText) {
                el.setAttribute('title', enText);
            }

            new bootstrap.Tooltip(el);
        });
    }

    // Функция инициализации языка tooltips
    function initTooltipLanguage(modal) {
        const currentLang = localStorage.getItem(TOOLTIP_LANG_KEY) || 'ru';
        const langText = document.getElementById('langToggleText');
        if (langText) {
            langText.textContent = currentLang === 'ru' ? 'RUS' : 'US';
        }

        setTimeout(function() {
            updateTooltipsLanguage(modal, currentLang);
        }, 100);
    }

    // Инициализация при загрузке страницы
    document.addEventListener('DOMContentLoaded', function() {
        const settings = loadPrintSettings();
        applyPrintSettings(settings);
        loadSettingsToForm(settings);

        // Применяем ограничения строк при загрузке
        setTimeout(function() {
            applyTableRowLimits(settings);
        }, 300);

        // Загружаем настройки в форму при открытии модального окна
        const modal = document.getElementById('printSettingsModal');
        if (modal) {
            modal.addEventListener('show.bs.modal', function() {
                const currentSettings = loadPrintSettings();
                loadSettingsToForm(currentSettings);
                initTooltipLanguage(modal);
            });
        }
    });

    // Применяем ограничения строк перед печатью
    window.addEventListener('beforeprint', function() {
        const settings = loadPrintSettings();
        applyTableRowLimits(settings);
    });
</script>

<script src="{{ asset('js/main.js') }}"></script>
<!-- Общие модули -->
<script src="{{ asset('js/tdrs/forms/common/multi-page-handler.js') }}"></script>

<!-- Модули для PRL формы -->
<script src="{{ asset('js/tdrs/forms/prl/prl-row-manager.js') }}"></script>
<script src="{{ asset('js/tdrs/forms/prl/prl-form-main.js') }}"></script>
</body>
</html>
