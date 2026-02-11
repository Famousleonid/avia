<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Card </title>
    <link rel="stylesheet" href="{{asset('assets/Bootstrap 5/bootstrap.min.css')}}">

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: "Times New Roman", serif;
        }

        .container-fluid {
            max-width: 980px;
            height: auto;
            font-size: .9rem;
            /*transform: scale(0.8);*/
            transform-origin: top left;
            padding: 3px;
            margin-left: 10px;
            margin-right: 10px;
        }


        @media print {
            /* Задаем размер страницы Letter (8.5 x 11 дюймов) */
            @page {
                /*size: letter landscape;*/
                size: Letter landscape;
                margin: 2mm;
            }

            /* Убедитесь, что вся страница помещается на один лист */
            html, body {
                height: auto;
                width: auto;
                margin-left: 3px;
                padding: 0;
            }


            .container-fluid {
                max-height: calc(100vh - 20px); /* Оставляем место для футера */
                overflow: hidden;
                margin: 0 !important;
                padding: 3px !important;
            }

            /* Скрываем ненужные элементы при печати */
            .no-print, .mt-2{
                display: none;
            }
            /* Уменьшаем отступы между секциями */
            .row {
                margin-bottom: 0 !important;
            }
            /* Колонтитул внизу страницы */
            footer {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                width: 100%;
                text-align: center;
                font-size: 10px;
                background-color: #fff;
                padding: 2px 0;
                margin: 0;
            }

            /*!* Уменьшаем отступы в таблицах *!*/
            /*.div1, .div2, .div3, .div4, .div31, .div32, .div33, .div34, .div35, .div36 {*/
            /*    padding-top: 2px !important;*/
            /*}*/

            .border-r {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
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
        .border-r {
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
        .border-l-t {
            border-left: 1px solid black;
            border-top: 1px solid black;
        }
        .border-l {
            border-left: 1px solid black;
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
        .border-t {
            border-top: 1px solid black;

        }
        .border-tt-gr {
            border-top: 3px solid gray;

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
        .fs-9 {
            font-size: 0.9rem; /* или любое другое подходящее значение */
        }
        .fs-8 {
            font-size: 0.8rem; /* или любое другое подходящее значение */
        }
        .fs-7 {
            font-size: 0.7rem; /* или любое другое подходящее значение */
        }
        .fs-75 {
            font-size: 0.75rem; /* или любое другое подходящее значение */
        }
        .fs-4 {
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
        .page-break {
            page-break-after: always;
        }


        .parent {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            grid-template-rows: repeat(5, 1fr);
            gap: 0px;
        }

        .div1 {
            grid-column: span 2 / span 2;
            grid-row: span 2 / span 2;
        }

        .div2 {
            grid-column: span 4 / span 4;
            grid-column-start: 3;
        }

        .div3 {
            grid-column: span 4 / span 4;
            grid-column-start: 7;
        }

        .div4 {
            grid-column: span 2 / span 2;
            grid-row: span 2 / span 2;
            grid-column-start: 11;
        }

        .div5 {
            grid-column-start: 3;
            grid-row-start: 2;
        }

        .div6 {
            grid-column-start: 4;
            grid-row-start: 2;
        }

        .div7 {
            grid-column-start: 5;
            grid-row-start: 2;
        }

        .div8 {
            grid-column-start: 6;
            grid-row-start: 2;
        }

        .div9 {
            grid-column-start: 7;
            grid-row-start: 2;
        }

        .div10 {
            grid-column-start: 8;
            grid-row-start: 2;
        }

        .div11 {
            grid-column-start: 9;
            grid-row-start: 2;
        }

        .div12 {
            grid-column-start: 10;
            grid-row-start: 2;
        }
        .div13 {
            grid-column: span 2 / span 2;
        }

        .div14 {
            grid-column-start: 3;
        }

        .div15 {
            grid-column-start: 4;
        }

        .div16 {
            grid-column-start: 5;
        }

        .div17 {
            grid-column-start: 6;
        }

        .div18 {
            grid-column-start: 7;
        }

        .div19 {
            grid-column-start: 8;
        }

        .div20 {
            grid-column-start: 9;
        }

        .div21 {
            grid-column-start: 10;
        }

        .div22 {
            grid-column: span 2 / span 2;
            grid-column-start: 11;
        }
        .div31 {
            grid-column: span 2 / span 2;
            grid-row: span 2 / span 2;
        }

        .div32 {
            grid-row: span 2 / span 2;
            grid-column-start: 3;
        }

        .div33 {
            grid-row: span 2 / span 2;
            grid-column-start: 4;
        }

        .div34 {
            grid-column: span 3 / span 3;
            grid-column-start: 5;
        }

        .div35 {
            grid-column: span 3 / span 3;
            grid-column-start: 8;
        }

        .div36 {
            grid-column: span 2 / span 2;
            grid-row: span 2 / span 2;
            grid-column-start: 11;
        }

        .div37 {
            grid-column-start: 5;
            grid-row-start: 2;
        }

        .div38 {
            grid-column-start: 6;
            grid-row-start: 2;
        }

        .div39 {
            grid-column-start: 7;
            grid-row-start: 2;
        }

        .div40 {
            grid-column-start: 8;
            grid-row-start: 2;
        }

        .div41 {
            grid-column-start: 9;
            grid-row-start: 2;
        }

        .div42 {
            grid-column-start: 10;
            grid-row-start: 2;
        }
        .div51 {
            grid-column: span 2 / span 2;
        }

        .div52 {
            grid-column: span 9 / span 9;
            grid-column-start: 3;
        }

        .div53 {
            /*grid-column: span 2 / span 2;*/
            grid-column-start: 11;
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
    <div class="row " style="height: 80px">
        <div class="col-4">
            <div>
                <img src="{{ asset('img/icons/AT_logo-rb.svg') }}" alt="Logo"
                     style="width: 160px; margin: 0px 3px 0;">
            </div>


            <div class="d-flex fs-75 mt-2">
                <h7>UNIT:</h7>
                <div class="ms-1">
                    @foreach($manuals as $manual)
                        @if($manual->id == $current_wo->unit->manual_id)
                            <h7 class=""> {{$manual->title}}</h7>
                        @endif
                    @endforeach
                </div>
            </div>
            <div class="d-flex fs-75">
                <h7>AUTHORIZED OVERHAUL LIFE:</h7>
                <div class="ms-1">
                    @foreach($manuals as $manual)
                        @if($manual->id == $current_wo->unit->manual_id)
                            <h7 class=""> {{$manual->ovh_life}}</h7>
                        @endif
                    @endforeach
                </div>
            </div>

        </div>
        <div class="col-4">
            <h5 class="pt-1  text-black "><strong>LANDING GEAR LOG CARD </strong></h5>

            <div class="row">
                <div class="d-flex fs-75 mt-2">
                    <div class="col-3 "><h7>PART NO:</h7></div>
                    <div class="col"><h7 class="2 "> {{$current_wo->unit->part_number}}</h7></div>
                </div>
            </div>
            <div class="row">
                <div class="d-flex fs-75">
                    <div class="col-3 "><h7>SERIAL NO:</h7></div>
                    <div class="col"> <h7 class=" "> {{$current_wo->serial_number}}</h7></div>
                </div>
            </div>

        </div>
        <div class="col-4">
            <!-- Log Image -->
{{--            @foreach($manuals as $manual)--}}
{{--                @if($manual->id == $current_wo->unit->manual_id)--}}
{{--                    <div class=" " style="height: 70px; width: 160px;margin-left: 100px">--}}

{{--                        @if($manual->getFirstMediaThumbnailUrl('manuals_log') )--}}


{{--                            <img class="" src="{{ $manual->getFirstMediaThumbnailUrl('manuals_log') }}"--}}
{{--                                 width="160" height="70"--}}
{{--                                 alt="Image Log"/>--}}
{{--                        @endif--}}

{{--                    </div>--}}
{{--                @endif--}}
{{--            @endforeach--}}



            @foreach($manuals as $manual)
                @if($manual->id == $current_wo->unit->manual_id && $manual->hasMedia('manuals_log'))
                    <div class=" " style="height: 70px; width: 160px;margin-left: 100px">
                        <img class="" src="{{ $manual->getFirstMediaThumbnailUrl('manuals_log') }}"
                             width="160" height="70" alt="Image Log"/>
                    </div>
                @endif
            @endforeach

        </div>

    </div>


    <div class="border-l-t-r">
        <div class="text-center pt-2 fs-75" style="height: 32px">
            <strong>AIRCRAFT INSTALLATION RECORDS</strong>
        </div>
    </div>

    <div class="parent">
        <div class="div1 text-center fs-8 border-all pt-3">Aircraft Reg./Con.No.</div>
        <div class="div2 text-center fs-8 border-t-r  pt-1">FITTED TO AIRCRAFT</div>
        <div class="div3 text-center fs-8 border-t-r-b  pt-1">REMOVED FROM AIRCRAFT</div>
        <div class="div4 text-center fs-8 border-t-r-b pt-3">REASON FOR REMOVAL</div>
        <div class="div5 text-center fs-8 border-t-r-b pt-1">DATE</div>
        <div class="div6 text-center fs-8 border-t-r-b pt-1">C.S.O.</div>
        <div class="div7 text-center fs-8 border-t-r-b pt-1">C.S.N.</div>
        <div class="div8 text-center fs-8 border-t-r-b pt-1">A/F CYCLES</div>
        <div class="div9 text-center fs-8 border-r-b pt-1">DATE</div>
        <div class="div10 text-center fs-8 border-r-b pt-1">C.S.O.</div>
        <div class="div11 text-center fs-8 border-r-b pt-1">C.S.N.</div>
        <div class="div12 text-center fs-8 border-r-b pt-1">A/F CYCLES</div>

        @for($i=0; $i<6; $i++)
            <div class="div13 border-l-b-r" style="height: 27px"> </div>
            <div class="div14 border-b-r" > </div>
            <div class="div15 border-b-r" > </div>
            <div class="div16 border-b-r" > </div>
            <div class="div17 border-b-r" > </div>
            <div class="div18 border-b-r" > </div>
            <div class="div19 border-b-r" > </div>
            <div class="div20 border-b-r" > </div>
            <div class="div21 border-b-r" > </div>
            <div class="div22 border-b-r" > </div>
        @endfor
    </div>
    <div class="border-l-t-r mt-1 ">
        <div class="row">
            <div class="col-10 pt-1  fs-75 " style="text-align: center; padding-left: 28ch;">
                <strong>PRIMARY MEMBER RECORDS</strong>
            </div>
            <div class="col-2 text-center"><strong>W{{$current_wo->number}}</strong></div>
        </div>
    </div>


    <div class="parent">
        <div class="div31 text-center fs-8 border-all pt-3">DESCRIPTION</div>
        <div class="div32 text-center fs-8 border-t-r-b pt-3">PART NO.</div>
        <div class="div33 text-center fs-8 border-t-r-b pt-3">SERIAL NO.</div>
        <div class="div34 text-center fs-8 border-t-r-b pt-1">FITTER TO GEAR</div>
        <div class="div35 text-center fs-8 border-t-r-b pt-1">REMOVED FROM GEAR</div>
        <div class="div36 text-center fs-8 border-t-r-b pt-3">REASON FOR REMOVAL</div>
        <div class="div37 text-center fs-8 border-r-b pt-1">DATE.</div>
        <div class="div38 text-center fs-8 border-r-b pt-1">C.S.O.</div>
        <div class="div39 text-center fs-8 border-r-b pt-1">C.S.N</div>
        <div class="div40 text-center fs-8 border-r-b pt-1">DATE</div>
        <div class="div41 text-center fs-8 border-r-b pt-1">C.S.O.</div>
        <div class="div42 text-center fs-8 border-r-b pt-1">C.S.N.</div>

        @foreach($componentData as $item)
            @php
                $comp = $components->firstWhere('id', $item['component_id']);
                $hasSerialNumber = !empty($item['serial_number']);
                $hasAssySerialNumber = isset($item['assy_serial_number']) && !empty($item['assy_serial_number']);
                $hasAssyPartNumber = $comp && $comp->assy_part_number;
            @endphp

            <div class="div13 border-l-b-r text-start align-content-center ps-2 pt-1 fs-7" style="min-height: 30px" >
                {{$comp->name}}
{{--                {{ $comp ? $comp->name : '' }}--}}
{{--                @if($hasAssySerialNumber && !$hasSerialNumber)--}}
{{--                    , S/A--}}
{{--                @endif--}}
            </div>
            <div class="div14 border-b-r text-center  align-content-center pt-1 fs-7" style="line-height: 1.2">


                @if($hasAssySerialNumber && !$hasSerialNumber)
                    {{ $comp ? $comp->assy_part_number : '' }}
                @else
                    @if($hasAssySerialNumber && $hasSerialNumber)
                        {{ $comp ? $comp->part_number : '' }}
                        ({{ $comp->assy_part_number}})
                    @else
                        {{ $comp ? $comp->part_number : '' }}
                    @endif

                @endif
                </div>
<div class="div15 border-b-r  text-center align-content-center pt-1 fs-7" style="line-height: 1.2">
    @if($hasAssySerialNumber && !$hasSerialNumber)
        {{ $item['assy_serial_number'] }}
    @else
        @if($hasAssySerialNumber && $hasSerialNumber)
            {{ $item['serial_number']}} <br>
            ({{ $item['assy_serial_number']}})
        @else
            {{ $item['serial_number'] }}
        @endif

    @endif

{{--@if($hasAssySerialNumber && !$hasSerialNumber)--}}
{{--    {{ $item['assy_serial_number'] }}--}}
{{--@else--}}
{{--    {{ $item['serial_number'] }}--}}
{{--@endif--}}
</div>
<div class="div16 border-b-r" > </div>

{{--@if($hasAssyPartNumber && $hasAssySerialNumber && $hasSerialNumber)--}}
{{--<div class="div17 border-b-r text-center pt-1 fs-7" style="grid-column: span 5 / span 5;">--}}
{{--    {{__(' ASSY PN ')}} {{$comp->assy_part_number}}{{__('  ASSY SN ')}} {{$item['assy_serial_number'] ?? ''}}--}}
{{--</div>--}}
{{--<div class="div22 border-b-r text-center pt-1 fs-75" >--}}
{{--    @php--}}
{{--        $reasonCode = $codes->firstWhere('id', $item['reason']);--}}
{{--    @endphp--}}
{{--    {{ $reasonCode ? $reasonCode->name : $item['reason'] }}--}}
{{--</div>--}}
{{--@else--}}
<div class="div17 border-b-r" > </div>
<div class="div18 border-b-r" > </div>
<div class="div19 border-b-r" > </div>
<div class="div20 border-b-r" > </div>
<div class="div21 border-b-r" > </div>
<div class="div22 border-b-r text-center align-content-center pt-1 fs-75" >
    @php
        $reasonCode = $codes->firstWhere('id', $item['reason']);
    @endphp
    {{ $reasonCode ? $reasonCode->name : $item['reason'] }}
</div>
{{--@endif--}}

@endforeach

@for($i=0; $i<8-$log_count; $i++)
<div class="div13 border-l-b-r" style="height: 27px"></div>
<div class="div14 border-b-r" > </div>
<div class="div15 border-b-r" > </div>
<div class="div16 border-b-r" > </div>
<div class="div17 border-b-r" > </div>
<div class="div18 border-b-r" > </div>
<div class="div19 border-b-r" > </div>
<div class="div20 border-b-r" > </div>
<div class="div21 border-b-r" > </div>
<div class="div22 border-b-r" > </div>
@endfor

</div>

<div class="parent mt-2">
<div class="div51">NOTES:</div>
<div class="div52 fs-7">
<div>
1. For ultimate lives and/or inspection requirements, refer to Aircraft Airworthiness Data and to the
appropriate
@foreach($manuals as $manual)
    @if($manual->id == $current_wo->unit->manual_id)
        <h7 class=""> {{$manual->reg_sb}}</h7>
    @endif
@endforeach
Service Bulletin.
</div>
<div>
2. It is the Operator's responsibility to ensure these records are fully and accurately maintained.
</div>
<div>
3. Lives of primary members shall be maintained. Failure to comply may result in premature scrap.
</div>
<div>
4. Should a primary member be removed from the unit it must be suitably tagged to indicate consumed life.
</div>
<div>
5. If the Part No. is changed a new Log Card must be completed, transferring relevant information from the previous Card.
</div>

</div>
<div class="div53"></div>
</div>

</div>

<footer >
<div class="row" style="width: 100%; padding: 1px 1px;">
<div class="col-6 text-start">
{{__("Form #008")}}
</div>

<div class="col-6 text-end pe-4 ">
{{__('Rev#0, 15/Dec/2012   ')}}
</div>
</div>
</footer>


</body>


</html>
