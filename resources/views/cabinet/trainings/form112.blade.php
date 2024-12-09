<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form 112</title>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC"
        crossorigin="anonymous">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: "Times New Roman", serif;
        }

        .container-fluid {
            max-width: 920px;
            height: 100%;
            padding: 5px;
            margin-left: 10px;
            margin-right: 10px;
        }

        @media print {
            /* Задаем размер страницы Letter (8.5 x 11 дюймов) */
            @page {
                size: letter;
                margin: 2mm;
            }

            /* Убедитесь, что вся страница помещается на один лист */
            html, body {
                height: 85%;
                width: 105%;
                margin-left: 10px;
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
                width: 920px;
                text-align: center;
                font-size: 10px;
                background-color: #fff;
                padding: 5px 10px;
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
            border: 3px solid black;
        }

        .border-l-t-r {
            border-left: 1px solid black;
            border-top: 1px solid black;
            border-right: 1px solid black;
        }
        .border-t-r {
            border-top: 1px solid black;
            border-right: 1px solid black;
        }
        .border-t-b {
            border-top: 1px solid black;
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

    </style>
</head>

<body>
<!-- Кнопка для печати -->
<div class="text-start m-3">
    <button class="btn btn-primary no-print" onclick="window.print()">Печать
        формы
    </button>

</div>

<div class="container-fluid">
    <div class="row">
        <img src="{{ asset('image/AT_logo-rb.svg') }}" alt="Logo"
             style="width: 210px; margin: 6px 10px 0;">
    </div>

    <div class="row justify-content-center">
        <div class="col-6 border-all-b border-dark text-center">
            <h2 class="pt-2 pb-2 text-black"><strong>The Job Training
                    Record</strong></h2>
        </div>
    </div>

    <div class="row mt-3" style="width: 900px">
        <div class="col-3 text-black border-bottom border-dark">
            <h4><strong>For the Week of:</strong></h4>
        </div>
        <div class="col-3 text-black text-center border-bottom border-dark">
            <h5>
                <strong>{{ \Carbon\Carbon::parse($training->date_training)
                ->subDay(4)->format('M-d-Y') }}</strong>
            </h5>
        </div>
        <div class="col-1 border-bottom border-dark">-</div>
        <div class="col-3 text-black text-left border-bottom border-dark">
            <h5>
                <strong>{{ \Carbon\Carbon::parse($training->date_training)->format('M-d-Y') }}</strong>
            </h5>
        </div>
        <div class="col-2 border-bottom border-dark"></div>
    </div>

    <div class="row mt-3" style="width: 900px">
        <div class="col-4 text-black">
            <h5><strong>Trainee (Please print name):</strong></h5>
        </div>
        <div class="col-4 text-black text-center border-bottom border-dark">
            <h5><strong>{{ $training->user->name }}</strong></h5>
        </div>
        <div class="col-4 border-bottom border-dark"></div>
    </div>

    @php
        $earliestTrainingDate = $training->manual->trainings()->where('form_type', 112)->min('date_training');
    @endphp


    <div class="row mt-2">
        <div class="col-1 border-l-t-r pt-4 topic-header">
            <h6><strong>Topic</strong></h6>
        </div>
        <div class="col-10 border-bottom pt-4 topic-content text-center">
            <h5>
                <strong>{{ $training->manual->title }}: {{
                $training->manual->unit_name_training }}</strong>
            </h5>
        </div>
        <div class="col-2 border-l-t-r pt-1 text-center hrs-topic">
            <h6><strong>Hrs on Topic</strong></h6>
        </div>
        <div class="col-1 border-t-r pt-1 text-center trainer-init-1">
            <h6><strong>Trainers Initials</strong></h6>
        </div>
    </div>

    <div class="row ">
        <div class="col-9 border-all pt-1 text-left topic-content-2">
            <h6>1. Introduction, Description and Operation;</h6>
            <h6>2. Testing and Fault Isolation;</h6>
        </div>
        <div class="col-2 border-t-b pt-3 text-center hrs-topic-1">
            <h5>{{ $training->form_type == 112 && $training->date_training == $earliestTrainingDate ? $training->manual->units_tr : 2 }}</h5>
        </div>
        <div class="col-1 border-all pt-3 text-center trainer-init">
            <h5>{{ __('V.N.') }}</h5>
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-1 border-l-t-r pt-4 topic-header">
            <h6><strong>Topic</strong></h6>
        </div>
        <div class="col-10 border-bottom pt-4 topic-content text-center">
            <h5>
                <strong>{{ $training->manual->title }}: {{
                $training->manual->unit_name_training }}</strong>
            </h5>
        </div>
        <div class="col-2  pt-1 text-center hrs-topic">
            <h6><strong> </strong></h6>
        </div>
        <div class="col-1  pt-1 text-center trainer-init-1">
            <h6><strong></strong></h6>
        </div>
    </div>

    <div class="row ">
        <div class="col-9 border-all pt-1 text-left topic-content-2">
            <h6>3. Disassembly; </h6>
            <h6>4. Cleaning;</h6>
            <h6>5. Check;</h6>
        </div>
        <div class="col-2 border-t-b pt-4 text-center hrs-topic-1">
            <h5>{{ $training->form_type == 112 && $training->date_training == $earliestTrainingDate ? $training->manual->units_tr : 2 }}</h5>
        </div>
        <div class="col-1 border-all pt-4 text-center trainer-init">
            <h5>{{ __('V.N.') }}</h5>
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-1 border-l-t-r pt-4 topic-header">
            <h6><strong>Topic</strong></h6>
        </div>
        <div class="col-10 border-bottom pt-4 topic-content text-center">
            <h5>
                <strong>{{ $training->manual->title }}: {{
                $training->manual->unit_name_training }}</strong>
            </h5>
        </div>
        <div class="col-2  pt-1 text-center hrs-topic">
            <h6><strong></strong></h6>
        </div>
        <div class="col-1  pt-1 text-center trainer-init-1">
            <h6><strong></strong></h6>
        </div>
    </div>

    <div class="row ">
        <div class="col-9 border-all pt-1 text-left topic-content-2">
            <h6>6. Fits and Clearance;</h6>
            <h6>7. Repair;</h6>
        </div>
        <div class="col-2 border-t-b pt-3 text-center hrs-topic">
            <h5>{{ $training->form_type == 112 && $training->date_training == $earliestTrainingDate ? $training->manual->units_tr : 2 }}</h5>
        </div>
        <div class="col-1 border-all pt-3 text-center trainer-init">
            <h5>{{ __('V.N.') }}</h5>
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-1 border-l-t-r pt-4 topic-header">
            <h6><strong>Topic</strong></h6>
        </div>
        <div class="col-10 border-bottom pt-4 topic-content text-center">
            <h5>
                <strong>{{ $training->manual->title }}: {{
                $training->manual->unit_name_training }}</strong>
            </h5>
        </div>

    </div>

    <div class="row ">
        <div class="col-9 border-all pt-1 text-left topic-content-2">
            <h6>8. Service Bulletins;</h6>
            <h6>9. Assembly;</h6>
        </div>
        <div class="col-2 border-t-b pt-3 text-center hrs-topic">
            <h5>{{ $training->form_type == 112 && $training->date_training == $earliestTrainingDate ? $training->manual->units_tr : 2 }}</h5>
        </div>
        <div class="col-1 border-all pt-3 text-center trainer-init">
            <h5>{{ __('V.N.') }}</h5>
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-1 border-l-t-r pt-4 topic-header">
            <h6><strong>Topic</strong></h6>
        </div>
        <div class="col-10 border-bottom pt-4 topic-content text-center">
            <h5>
                <strong>{{ $training->manual->title }}: {{
                $training->manual->unit_name_training }}</strong>
            </h5>
        </div>

    </div>

    <div class="row ">
        <div class="col-9 border-all pt-1 text-left topic-content-2">
            <h6>10. Special Tools, Fixtures and Equipment;</h6>
            <h6>11. Final Check.</h6>
        </div>
        <div class="col-2 border-t-b pt-3 text-center hrs-topic">
            <h5>{{ $training->form_type == 112 && $training->date_training == $earliestTrainingDate ? $training->manual->units_tr : 2 }}</h5>
        </div>
        <div class="col-1 border-all pt-3 text-center trainer-init">
            <h5>{{ __('V.N.') }}</h5>
        </div>
    </div>
    <div class="mb-3">
    <!-- Повторяем для других блоков... -->
   <h6>*Please use another sheet if necessary.</h6>
        @if($showImage === 'true')
        <div class=" pb-5 mt-n5 text-center align-items-start"
             style="height: 1px">

            <img src="{{ asset('storage/avatars/sign/' . Auth::user()->sign) }}"
                 alt="Sign" class="  ms-4" style="width: 200px">
        </div>
        @endif
    </div>


    <div class="row ps-2 pe-2 mt-4" style="width: 910px">
        <div class="col-8 text-black text-center border-bottom border-dark
    pt-3"></div>

        <div class="col-4  text-center border-bottom border-dark">
            <strong>{{ \Carbon\Carbon::parse($training->date_training)
    ->format('M-d-Y') }}</strong>
        </div>

        @if($showImage === 'true')
            @if(Auth::user()->role !== null && Auth::user()->role->name !==
            'Component Technician')
                <div class="col-8 text-center align-items-start" style="height: 1px">
                    <img src="{{ asset('storage/image/sign/sign_vn.png') }}"
                         alt="Sign" class="pb-5 ps-lg-1 ms-4" style="width:
                             200px">
                </div>
            @endif
        @endif

        <div class="d-flex row justify-contend-between">
            <div class="col-9">
                Trainee signature

            </div>
            <div class="col-1">Date</div>

        </div>

    </div>

    <div class="row ps-2 pe-2 mt-4" style="width: 910px">



                <div class="col-8 border-bottom border-dark "></div>

        <div class="col-4  text-center border-bottom border-dark">
            <strong>{{ \Carbon\Carbon::parse($training->date_training)->format('M-d-Y') }}</strong>
        </div>
        <div class="d-flex row justify-contend-between">
            <div class="col-9">
                Quality Assurance signature
            </div>
            <div class="col-1">Date</div>

        </div>

    </div>
    <div class="row  mt-3 border border-dark" style="width: 900px">
        <div class="col-11" style="height: 60px"></div>
    </div>


        <footer >
            <div class="row" style="width: 900px">
                <div class="col-6 text-start">
                    {{__("Form #112")}}
                </div>
                <div class="col-6 text-end pe-4 ">
                   {{__('Rev#0, 15/Dec/2012   ')}}
                </div>
            </div>

        </footer>

    <!-- Скрипт для печати -->
    <script>
        function printForm() {
            window.print();
        }
    </script>
</div>
</body>
</html>
