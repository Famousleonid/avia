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

        .container-fluid {
            max-width: 1020px;
            width: 100%;
            height: 99%;
            padding: 0;
            margin: 0;
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
                margin: 5mm 5mm 5mm 5mm;
            }
            /* Убираем фиксированное позиционирование */
            .header-page {
                position: static; /* Убираем фиксированное позиционирование */
                width: 100%;
                background-color: white;
            }

            /* Разрыв страницы после каждой страницы, кроме последней */
            .page:not(:last-of-type) {
                page-break-after: always;
            }


            .header-page {
                position: running(header); /* Заголовок будет повторяться на каждой странице */
            }
            /* Убедитесь, что вся страница помещается на один лист */
            html, body {
                height: 86%;
                width: 102%;
                margin: 0;
                padding: 0;
            }

            /* Контейнер использует всю ширину без дополнительных отступов */
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

            /* Скрываем ненужные элементы при печати */
            .no-print {
                display: none;
            }

            /* Колонтитул внизу страницы */
            footer {
                position: fixed;
                bottom: 0;
                width: 100%;
                text-align: center;
                font-size: 10px;
                background-color: #fff;
                padding: 3px 3px;
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
            }

            .row {
                margin-left: 10px !important;
                margin-right: 0 !important;
            }

            /* Убираем отступы от ms-2 и других классов */
            /*.ms-2 {*/
            /*    margin-left: 0 !important;*/
            /*}*/

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
            height: 40px; /* Фиксированная высота строки */
        }
        .details-cell {
            flex-grow: 1; /* Позволяет колонкам растягиваться и занимать доступное пространство */
            display: flex;
            justify-content: center; /* Центрирование содержимого по горизонтали */
            align-items: center; /* Центрирование содержимого по вертикали */
            border: 1px solid black; /* Границы для наглядности */
        }
        .check-icon {
            width: 24px; /* Меньший размер изображения */
            height: auto;
            margin: 0 5px; /* Отступы вокруг изображения */
        }

    </style>
</head>

<body>
<!-- Кнопка для печати -->
<div class="text-start m-3">
    <button class="btn btn-outline-primary no-print" onclick="window.print()">
        Print Form
    </button>
</div>

<div class="container-fluid">
    <!-- Данные (отображаются на каждой странице под верхней частью) -->
    @php
        $ordersParts = $ordersParts ?? [];
        $partsPerPage = 20; // Количество строк на странице (уменьшено для увеличенной высоты строк)
        $totalParts = count($ordersParts); // Общее количество строк данных
        $totalPages = ceil($totalParts / $partsPerPage); // Общее количество страниц
    @endphp

{{--{{$totalParts}} {{$totalPages}}--}}

    @if($totalParts > 0) <!-- Проверка, есть ли данные -->
    @for($page = 0; $page < $totalPages; $page++)
        <!-- Верхняя часть формы (дублируется на каждой странице) -->
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
                <div class="col-1 text-end"><h6><strong>P/N:</strong> </h6></div>
                <div class="col-4 ">
                    <div class="border-b">
                        <h6 class=""><strong> {{$current_wo->unit->part_number}}</strong></h6>
                    </div>
                </div>
                <div class="col-3 ">
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
                    <h5 class=" border-all text-center  " style="height: 40px;align-content: center">
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
                        <div class="col-5 border-b">
                            @foreach($manuals as $manual)
                                @if($manual->id == $current_wo->unit->manual_id)
                                    <h6 class=""><strong> {{substr($manual->number, 0, 8)}}</strong></h6>
                                @endif
                            @endforeach
                        </div>
                        <div class="col-3"></div>
                    </div>
                </div>
            </div>


            <div class="row mt-2 ms-3" style="width: 100%">
                <div class="col-5 ">
                    <div class="row">
                        <div class="col-1 border-l-t-b text-center align-content-center">
                            <h6 style="margin-top: 5px; font-size: 0.75rem;">FIG No.</h6>
                        </div>
                        <div class="col-2 border-l-t-b text-center align-content-center" >
                            <h6 style="margin-top: 5px; font-size: 0.75rem;">ITEM No.</h6></div>
                        <div class="col-9 border-l-t-b  text-center align-content-center">
                            <h6 style="font-size: 0.8rem; ">DESCRIPTION</h6>
                        </div>
                    </div>
                </div>
                <div class="col-7" >
                    <div class="row" style="height: 44px">
                        <div class="col-4 border-l-t-b text-center align-content-center ">
                            <h6 style="margin-top: 10px; font-size: 0.75rem;">PART NUMBER</h6>
                        </div>
                        <div class="col-1 border-l-t-b text-center align-content-center">
                            <h6 style="margin-top: 10px; font-size: 0.75rem;">QTY</h6>
                        </div>
                        <div class="col-1 border-l-t-b text-center align-content-center">
                            <h6 style="margin-top: 10px; font-size: 0.75rem;">CODE</h6>
                        </div>
                        <div class="col-2 border-l-t-b text-center align-content-center">
                            <h6 style="margin-top: 10px; font-size: 0.75rem;">PO No.</h6>
                        </div>
                        <div class="col-2 border-all text-center align-content-center">
                            <h6 style="margin-top: 10px; font-size: 0.75rem;">Notes</h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- Данные для текущей страницы -->
        <div class="page data-page" data-page-index="{{ $page }}">
            @php
                $rowIndex = 1;
            @endphp
            @for($i = $page * $partsPerPage; $i < ($page + 1) * $partsPerPage; $i++)
                @php
                    // Если данные существуют, выбираем ipl_num или assy_ipl_num
                    if ($i < $totalParts) {
                        $component = $ordersParts[$i]->orderComponent ?? $ordersParts[$i]->component;
                        if ($component) {
                            // Используем assy_ipl_num если он есть и не пустой, иначе ipl_num
                            $ipl_num = (isset($component->assy_ipl_num) && $component->assy_ipl_num !== null && $component->assy_ipl_num !== '') ? $component->assy_ipl_num : ($component->ipl_num ?? '');
                            $ipl_parts = explode('-', $ipl_num);
                            $first_part = $ipl_parts[0] ?? '';
                            $second_part = $ipl_parts[1] ?? '';

                            // Логика выбора IPL номера: используем assy_ipl_num если он есть, иначе ipl_num
                        } else {
                            $first_part = '';
                            $second_part = '';
                        }
                    } else {
                        // Если данных нет, заполняем пустыми значениями
                        $first_part = '';
                        $second_part = '';
                    }
                @endphp

                <div class="row data-row-prl ms-3" style="width: 100%" data-row-index="{{ $rowIndex }}">
                    <div class="col-5">
                        <div class="row" style="height: 40px">
                            <div class="col-1 border-l-b text-center pt-1 align-content-center">
                                <h6>{{ $first_part }} </h6>
                            </div>
                            <div class="col-2 border-l-b text-center pt-2 align-content-center">
                                <h6>{{ $second_part }}</h6>
                            </div>
{{--                            <div class="col-9 border-l-b text-center pt-1 align-content-center">--}}
{{--                                {{ $i < $totalParts ? ($component->name ?? '') : '' }}--}}
{{--                            </div>--}}
                            <div class="col-9 border-l-b text-center pt-1 align-content-center">
                                {{ $i < $totalParts ? ($component->assy_part_number ? $component->name . ' ASSY' : $component->name ?? '') : '' }}
                            </div>
                        </div>
                    </div>


                    <div class="col-7">
                        <div class="row" style="height: 40px">
                            <div class="col-4 border-l-b text-center pt-2 align-content-center">
                                @if($i < $totalParts && isset($component) && $component)
                                    <h6>
                                        {{ (!empty($component->assy_part_number)) ? $component->assy_part_number : $component->part_number }}
                                    </h6>
                                @else
                                    <h6> </h6>
                                @endif
                            </div>
                            <div class="col-1 border-l-b text-center pt-2 align-content-center">
                                <h6>{{ $i < $totalParts ? $ordersParts[$i]->qty : '' }}</h6>
                            </div>
                            <div class="col-1 border-l-b text-center pt-2 align-content-center">
                                <h6>{{ $i < $totalParts ? ($ordersParts[$i]->codes->code ?? '') : '' }}</h6>
                            </div>
                            <div class="col-2 border-l-b text-center pt-1 align-content-center">
                                @php
                                    $poRaw = $i < $totalParts ? ($ordersParts[$i]->po_num ?? '') : '';
                                    if (\Illuminate\Support\Str::startsWith($poRaw, 'Transfer from WO')) {
                                        // Оставляем только номер WO после "Transfer from WO"
                                        $poDisplay = trim(\Illuminate\Support\Str::after($poRaw, 'Transfer from WO'));
                                    } else {
                                        $poDisplay = $poRaw;
                                    }
                                @endphp
                                <h6>{{ $poDisplay }}</h6>
                            </div>
                            <div class="col-2 border-l-b-r text-center pt-1 align-content-center">
                                <h6>{{ $i < $totalParts ? $ordersParts[$i]->notes : '' }}</h6>
                            </div>
                        </div>
                    </div>
                </div>
                @php $rowIndex++; @endphp
            @endfor

                <!-- Проверка на последнюю страницу и добавление специального блока -->
                @if ($page == $totalPages - 1)
                    <div class="row mt-2" style="width: 100%">
                        <div class="col-8"></div>
                        <div class="col-1 border-l-t-b text-center align-content-center d-flex justify-content-center align-items-center" style="width: 48px; height: 46px">
                            <img src="{{ asset('img/icons/prod_st.png') }}" alt="stamp"
                                 style="width: 40px; max-height: 42px;">
                        </div>
                        <div class="col-1 border-all text-center align-content-center d-flex justify-content-center align-items-center" style="width: 48px; height: 46px">
                            <img src="{{ asset('img/icons/qual_st.png') }}" alt="stamp"
                                 style="width: 40px; max-height: 42px;">
                        </div>
                        <div class="col-2"></div>
                    </div>
                @endif
        </div>
    @endfor
    @else
        <!-- Если данных нет, выводим одну страницу с пустыми строками -->
        <div class="header-page">
            <div class="row">
                <div class="col-4">
                    <img src="{{ asset('img/icons/AT_logo-rb.svg') }}" alt="Logo"
                         style="width: 180px; margin: 6px 10px 0;">
                </div>
                <div class="col-8">
                    <h5 class="p-2 mt-3 text-black text-"><strong>PART REPLACEMENT LIST</strong></h5>
                </div>
            </div>
            <div class="row">
                <div class="col-1 text-end"><h6><strong>P/N:</strong> </h6></div>
                <div class="col-5 ">
                    <div class="border-b">
                        <h6 class=""><strong> {{$current_wo->unit->part_number}}</strong></h6>
                    </div>
                </div>
                <div class="col-3 ">
                    <div class="row ">
                        <div class="col-5 border-b">
                            <div class="d-flex ">
                                <h6 class=" "><strong>MFR: </strong></h6>
                                @foreach($manuals as $manual)
                                    @if($manual->id == $current_wo->unit->manual_id)
                                        <h6 class=" ms-2"><strong> {{$manual->builder->name}}</strong></h6>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                        <div class="col-5 border-b"> </div>
                    </div>
                </div>
                <div class="col-3">
                    <h5 class="p-1 border-all text-center">
                        <strong>{{__('WO No: W')}}{{$current_wo->number}}</strong>
                    </h5>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-6 ">
                    <div class="d-flex border-b">
                        <h6 class="ms-3 me-3"><strong>DESC: </strong></h6>
                        <div class="">
                            @foreach($manuals as $manual)
                                @if($manual->id == $current_wo->unit->manual_id)
                                    <h6 class=""><strong> {{$manual->title}}</strong></h6>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="col-6 ">
                    <div class="row">
                        <div class="col-2 border-b">
                            <h6 class="" ><strong>CMM: </strong></h6>
                        </div>
                        <div class="col-3 border-b">
                            @foreach($manuals as $manual)
                                @if($manual->id == $current_wo->unit->manual_id)
                                    <h6 class=""><strong> {{substr($manual->number, 0, 8)}}</strong></h6>
                                @endif
                            @endforeach
                        </div>
                        <div class="col-6"></div>
                    </div>
                </div>
            </div>

            <div class="row mt-4" style="width: 100%">
                <div class="col-5">
                    <div class="row">
                        <div class="col-1 border-l-t-b text-center align-content-center">
                            <h6 style="margin-top: 5px; font-size: 0.75rem;">FIG No.</h6>
                        </div>
                        <div class="col-2 border-l-t-b text-center align-content-center" >
                            <h6 style="margin-top: 5px; font-size: 0.75rem;">ITEM No.</h6></div>
                        <div class="col-9 border-l-t-b  text-center align-content-center">
                            <h6 style="font-size: 0.75rem;">DESCRIPTION</h6>
                        </div>
                    </div>
                </div>
                <div class="col-7" >
                    <div class="row" style="height: 53px">
                        <div class="col-4 border-l-t-b text-center align-content-center">
                            <h6 style="margin-top: 10px; font-size: 0.75rem;">PART NUMBER</h6>
                        </div>
                        <div class="col-1 border-l-t-b text-center align-content-center">
                            <h6 style="margin-top: 10px; font-size: 0.75rem;">QTY</h6>
                        </div>
                        <div class="col-1 border-l-t-b text-center align-content-center">
                            <h6 style="margin-top: 10px; font-size: 0.75rem;">CODE</h6>
                        </div>
                        <div class="col-2 border-l-t-b text-center align-content-center">
                            <h6 style="margin-top: 10px; font-size: 0.75rem;">PO No.</h6>
                        </div>
                        <div class="col-2 border-all text-center align-content-center">
                            <h6 style="margin-top: 10px; font-size: 0.75rem;">Notes</h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="page data-page">
            @php
                $rowIndex = 1;
            @endphp
            @for($i = 0; $i < $partsPerPage ; $i++)
                <div class="row data-row-prl empty-row" style="width: 100%" data-row-index="{{ $rowIndex }}">
                    <div class="col-5">
                        <div class="row" style="height: 40px">
                            <div class="col-1 border-l-b text-center align-content-center">
                                <h6></h6>
                            </div>
                            <div class="col-2 border-l-b text-center align-content-center">
                                <h6></h6>
                            </div>
                            <div class="col-9 border-l-b align-content-center">
                                <h6></h6>
                            </div>
                        </div>
                    </div>
                    <div class="col-7">
                        <div class="row" style="height: 40px">
                            <div class="col-4 border-l-b text-center align-content-center">
                                <h6></h6>
                            </div>
                            <div class="col-1 border-l-b text-center align-content-center">
                                <h6></h6>
                            </div>
                            <div class="col-1 border-l-b text-center align-content-center">
                                <h6></h6>
                            </div>
                            <div class="col-2 border-l-b text-center align-content-center">
                                <h6></h6>
                            </div>
                            <div class="col-2 border-l-b-r align-content-center">
                                <h6></h6>
                            </div>
                        </div>
                    </div>
                </div>
                @php $rowIndex++; @endphp
            @endfor
                <div class="row mt-2" style="width: 100%">
                    <div class="col-8"></div>
                    <div class="col-1 border-l-t-b text-center align-content-center d-flex justify-content-center align-items-center" style="width: 48px; height: 46px">
                        <img src="{{ asset('img/icons/prod_st.png') }}" alt="stamp"
                             style="width: 40px; max-height: 42px;">
                    </div>
                    <div class="col-1 border-all text-center align-content-center d-flex justify-content-center align-items-center" style="width: 48px; height: 46px">
                        <img src="{{ asset('img/icons/qual_st.png') }}" alt="stamp"
                             style="width: 40px; max-height: 42px;">
                    </div>
                    <div class="col-2"></div>
                </div>
        </div>

    @endif

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
<!-- Подключение библиотеки table-height-adjuster -->
<script src="{{ asset('js/table-height-adjuster.js') }}"></script>

<!-- Общие модули -->
<script src="{{ asset('js/tdrs/forms/common/multi-page-handler.js') }}"></script>

<!-- Модули для PRL формы -->
<script src="{{ asset('js/tdrs/forms/prl/prl-row-manager.js') }}"></script>
<script src="{{ asset('js/tdrs/forms/prl/prl-form-main.js') }}"></script>
</body>
</html>
