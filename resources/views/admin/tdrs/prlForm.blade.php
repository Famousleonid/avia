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
            max-width: 920px;

            height: 99%;
            padding: 5px;
            margin-left: 30px;
            margin-right: 5px;
        }

        @media print {
            /* Задаем размер страницы Letter (8.5 x 11 дюймов) */
            @page {
                size: letter;
                margin: 2mm;
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
                width: 98%;
                margin-left: 3px;
                padding: 0;
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
                width: 800px;
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
            height: 36px; /* Фиксированная высота строки */
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
        $partsPerPage = 28; // Количество строк на странице
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
                         style="width: 180px; margin: 6px 10px 0;">
                </div>
                <div class="col-8">
                    <h2 class="p-2 mt-3 text-black text-"><strong>PART REPLACEMENT LIST</strong></h2>
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
                        <h6 class="ms-4 me-3"><strong>DESC: </strong></h6>
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
                                    <h6 class=""><strong> {{$manual->number}}</strong></h6>
                                @endif
                            @endforeach
                        </div>
                        <div class="col-6"></div>
                    </div>
                </div>
            </div>
            <div class="row mt-4 " style="width: 1020px">
                <div class="col-5">
                    <div class="row">
                        <div class="col-1 border-l-t-b text-center align-content-center">
                            <h6 style="margin-left: -8px;margin-top: 5px">FIG No.</h6>
                        </div>
                        <div class="col-2 border-l-t-b" >
                            <h6 style="margin-top: 5px">ITEM No.</h6></div>
                        <div class="col-9 border-l-t-b  text-center align-content-center">DESCRIPTION</div>
                    </div>
                </div>
                <div class="col-7" >
                    <div class="row" style="height: 53px">
                        <div class="col-4 border-l-t-b text-center align-content-center">
                            <h6 style="margin-top: 10px">PART NUMBER</h6>
                        </div>
                        <div class="col-1 border-l-t-b  align-content-center">
                            <h6 style="margin-left: -7px ;margin-top: 10px">QTY</h6>
                        </div>
                        <div class="col-1 border-l-t-b  align-content-center">
                            <h6 style="margin-left: -10px ;margin-top: 10px">CODE</h6>
                        </div>
                        <div class="col-2 border-l-t-b text-center align-content-center">
                            <h6 style="margin-top: 10px">PO No.</h6>
                        </div>
                        <div class="col-2 border-all text-center align-content-center">
                            <h6 style="margin-top: 10px">Notes</h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Данные для текущей страницы -->
        <div class="page data-page">
            @for($i = $page * $partsPerPage; $i < ($page + 1) * $partsPerPage; $i++)
                @php
                    // Если данные существуют, разделяем ipl_num
                    if ($i < $totalParts) {
                        $ipl_num = $ordersParts[$i]->component->ipl_num ?? '';
                        $ipl_parts = explode('-', $ipl_num);
                        $first_part = $ipl_parts[0] ?? '';
                        $second_part = $ipl_parts[1] ?? '';
                    } else {
                        // Если данных нет, заполняем пустыми значениями
                        $first_part = '';
                        $second_part = '';
                    }
                @endphp

                <div class="row" style="width: 1020px">
                    <div class="col-5">
                        <div class="row" style="height: 36px">
                            <div class="col-1 border-l-b text-center pt-1 align-content-center">
                                <h6>{{ $first_part }}</h6>
                            </div>
                            <div class="col-2 border-l-b text-center pt-2 align-content-center">
                                <h6>{{ $second_part }}</h6>
                            </div>
                            <div class="col-9 border-l-b text-center pt-1 align-content-center">
                                {{ $i < $totalParts ? $ordersParts[$i]->component->name : '' }}
                            </div>
                        </div>
                    </div>
                    <div class="col-7">
                        <div class="row" style="height: 36px">
                            <div class="col-4 border-l-b text-center pt-2 align-content-center">
                                <h6>{{ $i < $totalParts ? $ordersParts[$i]->component->part_number : '' }}</h6>
                            </div>
                            <div class="col-1 border-l-b text-center pt-2 align-content-center">
                                <h6 style="margin-left: -7px">{{ $i < $totalParts ? $ordersParts[$i]->qty : '' }}</h6>
                            </div>
                            <div class="col-1 border-l-b text-center pt-2 align-content-center">
                                <h6 style="margin-left: -10px">{{ $i < $totalParts ? ($ordersParts[$i]->codes->code ?? '') : '' }}</h6>
                            </div>
                            <div class="col-2 border-l-b text-center pt-1 align-content-center">
                                <h6>{{ $i < $totalParts ? $ordersParts[$i]->po_number : '' }}</h6>
                            </div>
                            <div class="col-2 border-l-b-r text-center pt-1 align-content-center">
                                <h6>{{ $i < $totalParts ? $ordersParts[$i]->notes : '' }}</h6>
                            </div>
                        </div>
                    </div>
                </div>
            @endfor

                <!-- Проверка на последнюю страницу и добавление специального блока -->
                @if ($page == $totalPages - 1)
                    <div class="row mt-2">
                        <div class="col-8"></div>
                        <div class="col-1 border-l-t-b" style="width: 48px; height: 46px">
                            <img src="{{ asset('img/icons/prod_st.png') }}" alt="stamp"
                                 style="width: 42px; margin-left: -8px">
                        </div>
                        <div class="col-1 border-all" style="width: 48px; height: 46px">
                            <img src="{{ asset('img/icons/qual_st.png') }}" alt="stamp"
                                 style="width: 42px; margin-left: -10px; margin-top: 1px">
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
                    <h2 class="p-2 mt-3 text-black text-"><strong>PART REPLACEMENT LIST</strong></h2>
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
                        <h6 class="ms-4 me-3"><strong>DESC: </strong></h6>
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
                                    <h6 class=""><strong> {{$manual->number}}</strong></h6>
                                @endif
                            @endforeach
                        </div>
                        <div class="col-6"></div>
                    </div>
                </div>
            </div>
            <div class="row mt-4 " style="width: 1020px">
                <div class="col-5">
                    <div class="row">
                        <div class="col-1 border-l-t-b text-center align-content-center">
                            <h6 style="margin-left: -8px;margin-top: 5px">FIG No.</h6>
                        </div>
                        <div class="col-2 border-l-t-b" >
                            <h6 style="margin-top: 5px">ITEM No.</h6></div>
                        <div class="col-9 border-l-t-b  text-center align-content-center">DESCRIPTION</div>
                    </div>
                </div>
                <div class="col-7" >
                    <div class="row" style="height: 53px">
                        <div class="col-4 border-l-t-b text-center align-content-center">
                            <h6 style="margin-top: 10px">PART NUMBER</h6>
                        </div>
                        <div class="col-1 border-l-t-b  align-content-center">
                            <h6 style="margin-left: -7px ;margin-top: 10px">QTY</h6>
                        </div>
                        <div class="col-1 border-l-t-b  align-content-center">
                            <h6 style="margin-left: -10px ;margin-top: 10px">CODE</h6>
                        </div>
                        <div class="col-2 border-l-t-b text-center align-content-center">
                            <h6 style="margin-top: 10px">PO No.</h6>
                        </div>
                        <div class="col-2 border-all text-center align-content-center">
                            <h6 style="margin-top: 10px">Notes</h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="page data-page">
            @for($i = 0; $i < $partsPerPage ; $i++)
                <div class="row" style="width: 1020px">
                    <div class="col-5">
                        <div class="row" style="height: 36px">
                            <div class="col-1 border-l-b align-content-center">
                                <h6></h6>
                            </div>
                            <div class="col-2 border-l-b">
                                <h6></h6>
                            </div>
                            <div class="col-9 border-l-b align-content-center">
                                <h6></h6>
                            </div>
                        </div>
                    </div>
                    <div class="col-7">
                        <div class="row" style="height: 36px">
                            <div class="col-4 border-l-b text-center align-content-center">
                                <h6></h6>
                            </div>
                            <div class="col-1 border-l-b align-content-center">
                                <h6 style="margin-left: -7px"></h6>
                            </div>
                            <div class="col-1 border-l-b align-content-center">
                                <h6 style="margin-left: -10px"></h6>
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
            @endfor
                <div class="row mt-2">
                    <div class="col-8"></div>
                    <div class="col-1 border-l-t-b" style="width: 48px; height: 46px">
                        <img src="{{ asset('img/icons/prod_st.png') }}" alt="stamp"
                             style="width: 42px; margin-left: -8px">
                    </div>
                    <div class="col-1 border-all" style="width: 48px; height: 46px">
                        <img src="{{ asset('img/icons/qual_st.png') }}" alt="stamp"
                             style="width: 42px; margin-left: -10px; margin-top: 1px">
                    </div>
                    <div class="col-2"></div>
                </div>
        </div>

    @endif

    <footer>
        <div class="row" style="width: 100%; padding: 5px 0;">
            <div class="col-6 text-start">
                {{__("Form #028")}}
            </div>
            <div class="col-6 text-end pe-4 ">
                {{__('Rev#0, 15/Dec/2012   ')}}
            </div>
        </div>
    </footer>
</div>
</body>
</html>
