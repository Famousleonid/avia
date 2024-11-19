<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form 132</title>
    <link rel="stylesheet" href="{{asset('css/bootstrap.min.css')}}">

{{--    <link--}}
{{--        href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css"--}}
{{--        rel="stylesheet"--}}
{{--        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC"--}}
{{--        crossorigin="anonymous">--}}

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
                /*margin: 6mm;*/
                margin-left: 15mm;
                margin-top: 10mm;
                margin-right: 6mm;
                margin-bottom: 10mm;
            }

            /* Убедитесь, что вся страница помещается на один лист */
            html, body {
                /*height: 100%;*/
                width: 105%;
                margin-left: 1px;
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
                /*background-color: #fff;*/
                padding: 1px 20px;
                min-height: 10px;
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

        .border-l {
            border-left: 1px solid black;
        }
        .border-l-r {
            border-left: 1px solid black;
            border-right: 1px solid black;
        }

        .border-r {
            border-right: 1px solid black;
        }

        .border-l-t-r {
            border-left: 1px solid black;
            border-top: 1px solid black;
            border-right: 1px solid black;
        }

        .border-l-t {
            border-left: 1px solid black;
            border-top: 1px solid black;

        }

        .border-l-b-r {
            border-left: 1px solid black;
            border-bottom: 1px solid black;
            border-right: 1px solid black;
        }

        .border-l-b {
            border-left: 1px solid black;
            border-bottom: 1px solid black;
        }
        .border-b {

            border-bottom: 1px solid black;
        }
        .border-l-t-b {
            border-left: 1px solid black;
            border-top: 1px solid black;
            border-bottom: 1px solid black;
        }

        .border-t-r {
            border-top: 1px solid black;
            border-right: 1px solid black;
        }

        .border-t {
            border-top: 1px solid black;

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

        .hrs-topic-1 {
            width: 98px;
        }

        .trainer-init-1 {
            width: 99px;
        }


    </style>
</head>

<body>
<!-- Кнопка для печати -->
<div class="text-start m-2">
    <button class="btn btn-primary no-print" onclick="window.print()">Печать
        формы
    </button>

</div>

<div class="container-fluid">
    <div class="row">
        <img src="{{ asset('image/AT_logo-rb.svg') }}" alt="Logo"
             style="width: 210px; margin: 2px 2px 0;">
    </div>

    <div class="row justify-content-center">
        <div class="col-11 text-center">
            <h3 class="  text-black"><strong>TRAINEE OBJECTIVES
                    ASSESSMENT FORM

                </strong></h3>
            <h3 class="  text-black"><strong>
                    (FOR USE BY THE ASSESSOR)
                </strong></h3>

        </div>
    </div>

    <div class="row " style="width: 900px">
        <div class="col-6 text-black">
            <h5><strong>Trainee Name:</strong></h5>
        </div>
        <div class="col-6 text-black text-center border-bottom border-dark">
            <h5><strong>{{ $training->user->name }}</strong></h5>
        </div>
    </div>
    <div class="row mt-1" style="width: 900px">
        <div class="col-6 text-black">
            <h5><strong>Trainee Position:</strong></h5>
        </div>
        <div class="col-6 text-black text-center border-bottom border-dark">
            <h5><strong>{{ $training->user->role->name }}</strong></h5>
        </div>
    </div>
    <div class="row mt-1" style="width: 900px">
        <div class="col-6 ">
            <h6 class="pt-3"><strong>Area of Training:</strong></h6>
            <h6 class="mt-n2" style="font-size: 0.85rem;">(e.g. P/N,
                responsibility, etc..)</h6>
        </div>
        <div class="col-6 text-black text-center  border-bottom border-dark">
            <h5 class="mt-1"><strong>{{ $training->manual->title
            }}</strong></h5>
            <h5 class="mt-n2"><strong>{{ $training->manual->units_pn }}</strong>
            </h5>
        </div>
    </div>


    <div class="row mt-2 border-all" style="width: 920px">
        <div class="col-7 text-center ">
            <h5 class="pt-4"><strong>USE REPORTS & INDICATIONS</strong></h5>
        </div>
        <div class="col-5 text-center border-l ">
            <h6 class=""><strong>PERFORMANCE ASSESSMENT</strong></h6>
            <div class="row">
                <div class="col-7 text-center border-t-r">
                    <h6 class=""><strong>ATTEMPTS</strong></h6>
                    <div class="row">
                        <div class="col-4 pt-1 text-center
                        border-t"><h6><strong>1st</strong></h6></div>
                        <div class="col-4  pt-1 text-center
                        border-l-t"><h6><strong>2st</strong></h6></div>
                        <div class="col-4 pt-1 text-center
                        border-l-t"><h6><strong>3st</strong></h6></div>
                    </div>
                </div>
                <div class="col-5 border-t">
                    <h6 class=""><strong>RESULT *</strong></h6>
                    <div class="row">
                        <div class="col-6  pt-1 text-center
                        border-t"><h6><strong>U</strong></h6></div>
                        <div class="col-6 pt-1 text-center
                        border-l-t"><h6><strong>S</strong></h6></div>
                    </div>

                </div>
            </div>

        </div>
    </div>
    <div class="row border-l-b-r" style="width: 920px">
        <div class="col-7 ">
            Trainee demonstrate knowledge of description and operation of
            aircraft component
        </div>
        <div class="col-5">
            <div class="row">
                <div class="col-7 text-center border-l">
                    <div class="row">
                        <div class="col-4 text-center pt-2
                        ">

                            <img src="{{ asset('storage/image/forms/check.svg')
                            }}" alt="check" class="4"
                               style="width: 40px">
                        </div>
                        <div class="col-4 text-center  border-l pt-2
                        "><h3></h3></div>
                        <div class="col-4 text-center  border-l pt-2
                        "><h3></h3></div>
                    </div>
                </div>
                <div class="col-5 border-l">
                    <div class="row">
                        <div class="col-6 text-center pt-2
                        "><h3></h3></div>
                        <div class="col-6 text-center  border-l pt-2
                        "><img src="{{ asset('storage/image/forms/check.svg')
                            }}" alt="check" class="4"
                               style="width: 40px"></div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <div class="row border-l-b-r" style="width: 920px">
        <div class="col-7 ">
            Trainee reads the available reports and indications (maintenance
            task)
        </div>
        <div class="col-5">
            <div class="row">
                <div class="col-7 text-center border-l">
                    <div class="row">
                        <div class="col-4 text-center pt-2
                        "><img src="{{ asset('storage/image/forms/check.svg')
                            }}" alt="check" class="4"
                               style="width: 40px"></div>
                        <div class="col-4 text-center  border-l pt-2
                        "><h3></h3></div>
                        <div class="col-4 text-center  border-l pt-2
                        "><h3></h3></div>
                    </div>
                </div>
                <div class="col-5 border-l">
                    <div class="row">
                        <div class="col-6 text-center pt-2
                        "><h3></h3></div>
                        <div class="col-6 text-center  border-l pt-2
                        "><img src="{{ asset('storage/image/forms/check.svg')
                            }}" alt="check" class="4"
                               style="width: 40px"></div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <div class="row border-l-b-r" style="width: 920px">
        <div class="col-7 ">
            Trainee interprets the reports and indications correctly (Opens
            proper Manuals/takes right actions to start the process)
        </div>
        <div class="col-5">
            <div class="row">
                <div class="col-7 text-center border-l">
                    <div class="row">
                        <div class="col-4 text-center pt-2
                        "><img src="{{ asset('storage/image/forms/check.svg')
                            }}" alt="check" class="4"
                               style="width: 40px"></div>
                        <div class="col-4 text-center  border-l pt-2
                        "><h3></h3></div>
                        <div class="col-4 text-center  border-l pt-2
                        "><h3></h3></div>
                    </div>
                </div>
                <div class="col-5 border-l">
                    <div class="row">
                        <div class="col-6 text-center pt-2
                        "><h3></h3></div>
                        <div class="col-6 text-center  border-l pt-2
                        "><img src="{{ asset('storage/image/forms/check.svg')
                            }}" alt="check" class="4"
                               style="width: 40px"></div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <div class="row border-l-b-r" style="width: 920px">
        <div class="col-7 pt-3">
            <h6><strong>FIND & USE COMPONENT DOCUMENTATION</strong></h6>
        </div>
        <div class="col-5">
            <div class="row">
                <div class="col-7 text-center border-l">
                    <h6 class="text-center pt-1"><strong>ATTEMPTS</strong></h6>
                    <div class="row">
                        <div class="col-4 pt-1 text-center
                        border-t"><h6><strong>1st</strong></h6></div>
                        <div class="col-4 pt-1 text-center
                        border-l-t"><h6><strong>2st</strong></h6></div>
                        <div class="col-4 pt-1 text-center
                        border-l-t"><h6><strong>3st</strong></h6></div>
                    </div>
                </div>
                <div class="col-5  border-l">
                    <h6 class="text-center pt-1"><strong>RESULT *</strong></h6>
                    <div class="row">
                        <div class="col-6 text-center pt-1
                        border-t"><h6><strong>U</strong></h6></div>
                        <div class="col-6 text-center pt-1
                        border-l-t"><h6><strong>S</strong></h6></div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <div class="row border-l-b-r" style="width: 920px">
        <div class="col-7 ">
            Trainee finds proper troubleshooting procedure, if necessary
        </div>
        <div class="col-5">
            <div class="row">
                <div class="col-7 text-center border-l">
                    <div class="row">
                        <div class="col-4 text-center pt-2
                        "><img src="{{ asset('storage/image/forms/check.svg')
                            }}" alt="check" class="4"
                               style="width: 40px"></div>
                        <div class="col-4 text-center  border-l pt-2
                        "><h3></h3></div>
                        <div class="col-4 text-center  border-l pt-2
                        "><h3></h3></div>
                    </div>
                </div>
                <div class="col-5 border-l">
                    <div class="row">
                        <div class="col-6 text-center pt-2
                        "><h3></h3></div>
                        <div class="col-6 text-center  border-l pt-2
                        "><img src="{{ asset('storage/image/forms/check.svg')
                            }}" alt="check" class="4"
                               style="width: 40px"></div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <div class="row border-l-b-r" style="width: 920px">
        <div class="col-7 ">
            Trainee makes the correct interpretation on CMM, SB, AD and other
            related procedures (this shows in the actions the trainee takes)
        </div>
        <div class="col-5">
            <div class="row">
                <div class="col-7 text-center border-l">
                    <div class="row">
                        <div class="col-4 text-center pt-2
                        "><img src="{{ asset('storage/image/forms/check.svg')
                            }}" alt="check" class="4"
                               style="width: 40px">
                        </div>
                        <div class="col-4 text-center  border-l pt-2
                        "><h3></h3></div>
                        <div class="col-4 text-center  border-l pt-2
                        "><h3></h3></div>
                    </div>
                </div>
                <div class="col-5 border-l">
                    <div class="row">
                        <div class="col-6 text-center pt-2
                        "><h3></h3></div>
                        <div class="col-6 text-center  border-l pt-2 ">
                            <img src="{{ asset('storage/image/forms/check.svg')
                            }}" alt="check" class="4"
                                 style="width: 40px">
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <div class="row border-l-b-r" style="width: 920px">
        <div class="col-7 pt-3">
            <h6><strong>CORRECTLY PERFORM ACTIONS</strong></h6>
        </div>
        <div class="col-5">
            <div class="row">
                <div class="col-7 text-center border-l">
                    <h6 class="text-center pt-1"><strong>ATTEMPTS</strong></h6>
                    <div class="row">
                        <div class="col-4 pt-1 text-center
                        border-t"><h6><strong>1st</strong></h6></div>
                        <div class="col-4 pt-1 text-center
                        border-l-t"><h6><strong>2st</strong></h6></div>
                        <div class="col-4 pt-1 text-center
                        border-l-t"><h6><strong>3st</strong></h6></div>
                    </div>
                </div>
                <div class="col-5  border-l">
                    <h6 class="text-center pt-1"><strong>RESULT *</strong></h6>
                    <div class="row">
                        <div class="col-6 text-center pt-1
                        border-t"><h6><strong>U</strong></h6></div>
                        <div class="col-6 text-center pt-1
                        border-l-t"><h6><strong>S</strong></h6></div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <div class="row border-l-b-r" style="width: 920px">
        <div class="col-7 ">
            Trainee follows the procedure steps
        </div>
        <div class="col-5">
            <div class="row">
                <div class="col-7 text-center border-l">
                    <div class="row">
                        <div class="col-4 text-center pt-2
                        "><img src="{{ asset('storage/image/forms/check.svg')
                            }}" alt="check" class="4"
                               style="width: 40px"></div>
                        <div class="col-4 text-center  border-l pt-2
                        "><h3></h3></div>
                        <div class="col-4 text-center  border-l pt-2
                        "><h3></h3></div>
                    </div>
                </div>
                <div class="col-5 border-l">
                    <div class="row">
                        <div class="col-6 text-center pt-2
                        "><h3></h3></div>
                        <div class="col-6 text-center  border-l pt-2
                        "><img src="{{ asset('storage/image/forms/check.svg')
                            }}" alt="check" class="4"
                               style="width: 40px"></div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <div class="row border-l-b-r" style="width: 920px">
        <div class="col-7 ">
            Trainee make sure that actions are properly done
        </div>
        <div class="col-5">
            <div class="row">
                <div class="col-7 text-center border-l">
                    <div class="row">
                        <div class="col-4 text-center pt-2
                        "><img src="{{ asset('storage/image/forms/check.svg')
                            }}" alt="check" class="4"
                               style="width: 40px">
                        </div>
                        <div class="col-4 text-center  border-l pt-2
                        "><h3></h3></div>
                        <div class="col-4 text-center  border-l pt-2
                        "><h3></h3></div>
                    </div>
                </div>
                <div class="col-5 border-l">
                    <div class="row">
                        <div class="col-6 text-center pt-2
                        "><h3></h3></div>
                        <div class="col-6 text-center  border-l pt-2 ">
                            <img src="{{ asset('storage/image/forms/check.svg')
                            }}" alt="check" class="4"
                                 style="width: 40px">
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <div class="row border-l-b-r" style="width: 920px">
        <div class="col-7 ">
            Trainee uses required tooling
        </div>
        <div class="col-5">
            <div class="row">
                <div class="col-7 text-center border-l">
                    <div class="row">
                        <div class="col-4 text-center pt-2
                        "><img src="{{ asset('storage/image/forms/check.svg')
                            }}" alt="check" class="4"
                               style="width: 40px">
                        </div>
                        <div class="col-4 text-center  border-l pt-2
                        "><h3></h3></div>
                        <div class="col-4 text-center  border-l pt-2
                        "><h3></h3></div>
                    </div>
                </div>
                <div class="col-5 border-l">
                    <div class="row">
                        <div class="col-6 text-center pt-2
                        "><h3></h3></div>
                        <div class="col-6 text-center  border-l pt-2 ">
                            <img src="{{ asset('storage/image/forms/check.svg')
                            }}" alt="check" class="4"
                                 style="width: 40px">
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <div class="row border-l-b-r" style="width: 920px">
        <div class="col-7 pt-3">
            <h6><strong>OPERATE IN COMPLIANCE WITH ENVIRONMENT</strong></h6>
        </div>
        <div class="col-5">
            <div class="row">
                <div class="col-7 text-center border-l">
                    <h6 class="text-center pt-1"><strong>ATTEMPTS</strong></h6>
                    <div class="row">
                        <div class="col-4 pt-1 text-center
                        border-t"><h6><strong>1st</strong></h6></div>
                        <div class="col-4 pt-1 text-center
                        border-l-t"><h6><strong>2st</strong></h6></div>
                        <div class="col-4 pt-1 text-center
                        border-l-t"><h6><strong>3st</strong></h6></div>
                    </div>
                </div>
                <div class="col-5  border-l">
                    <h6 class="text-center pt-1"><strong>RESULT *</strong></h6>
                    <div class="row">
                        <div class="col-6 text-center pt-1
                        border-t"><h6><strong>U</strong></h6></div>
                        <div class="col-6 text-center pt-1
                        border-l-t"><h6><strong>S</strong></h6></div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <div class="row border-l-b-r" style="width: 920px">
        <div class="col-7 ">
            Trainee scans the environment before starting the task to ensure
            safety
        </div>
        <div class="col-5">
            <div class="row">
                <div class="col-7 text-center border-l">
                    <div class="row">
                        <div class="col-4 text-center pt-2
                        "><img src="{{ asset('storage/image/forms/check.svg')
                            }}" alt="check" class="4"
                               style="width: 40px"></div>
                        <div class="col-4 text-center  border-l pt-2
                        "><h3></h3></div>
                        <div class="col-4 text-center  border-l pt-2
                        "><h3></h3></div>
                    </div>
                </div>
                <div class="col-5 border-l">
                    <div class="row">
                        <div class="col-6 text-center pt-2
                        "><h3></h3></div>
                        <div class="col-6 text-center  border-l pt-2
                        "><img src="{{ asset('storage/image/forms/check.svg')
                            }}" alt="check" class="4"
                               style="width: 40px"></div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <div class="row border-l-b-r" style="width: 920px">
        <div class="col-7 ">
            Trainee reads/interprets safety warnings correctly
        </div>
        <div class="col-5">
            <div class="row">
                <div class="col-7 text-center border-l">
                    <div class="row">
                        <div class="col-4 text-center pt-2
                        "><img src="{{ asset('storage/image/forms/check.svg')
                            }}" alt="check" class="4"
                               style="width: 40px">
                        </div>
                        <div class="col-4 text-center  border-l pt-2
                        "><h3></h3></div>
                        <div class="col-4 text-center  border-l pt-2
                        "><h3></h3></div>
                    </div>
                </div>
                <div class="col-5 border-l">
                    <div class="row">
                        <div class="col-6 text-center pt-2
                        "><h3></h3></div>
                        <div class="col-6 text-center  border-l pt-2 ">
                            <img src="{{ asset('storage/image/forms/check.svg')
                            }}" alt="check" class="4"
                                 style="width: 40px">
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <div class="row border-l-b-r" style="width: 920px">
        <div class="col-7 ">
            Trainee informs people of his/her work, if necessary
        </div>
        <div class="col-5">
            <div class="row">
                <div class="col-7 text-center border-l">
                    <div class="row">
                        <div class="col-4 text-center pt-2
                        "><img src="{{ asset('storage/image/forms/check.svg')
                            }}" alt="check" class="4"
                               style="width: 40px">
                        </div>
                        <div class="col-4 text-center  border-l pt-2
                        "><h3></h3></div>
                        <div class="col-4 text-center  border-l pt-2
                        "><h3></h3></div>
                    </div>
                </div>
                <div class="col-5 border-l">
                    <div class="row">
                        <div class="col-6 text-center pt-2
                        "><h3></h3></div>
                        <div class="col-6 text-center  border-l pt-2 ">
                            <img src="{{ asset('storage/image/forms/check.svg')
                            }}" alt="check" class="4"
                                 style="width: 40px">
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <div class="row border-l-b-r" style="width: 920px">
        <div class="col-7 ">
            Trainee continuously scans environment during task performance
        </div>
        <div class="col-5">
            <div class="row">
                <div class="col-7 text-center border-l">
                    <div class="row">
                        <div class="col-4 text-center pt-2
                        "><img src="{{ asset('storage/image/forms/check.svg')
                            }}" alt="check" class="4"
                               style="width: 40px">
                        </div>
                        <div class="col-4 text-center  border-l pt-2
                        "><h3></h3></div>
                        <div class="col-4 text-center  border-l pt-2
                        "><h3></h3></div>
                    </div>
                </div>
                <div class="col-5 border-l">
                    <div class="row">
                        <div class="col-6 text-center pt-2
                        "><h3></h3></div>
                        <div class="col-6 text-center  border-l pt-2 ">
                            <img src="{{ asset('storage/image/forms/check.svg')
                            }}" alt="check" class="4"
                                 style="width: 40px">
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <div class="row border-l-b-r" style="width: 920px">
        <div class="col-7 ">
            Trainee reacts properly to changes during task performance to ensure
            safety
        </div>
        <div class="col-5">
            <div class="row">
                <div class="col-7 text-center border-l">
                    <div class="row">
                        <div class="col-4 text-center pt-2
                        "><img src="{{ asset('storage/image/forms/check.svg')
                            }}" alt="check" class="4"
                               style="width: 40px">
                        </div>
                        <div class="col-4 text-center  border-l pt-2
                        "><h3></h3></div>
                        <div class="col-4 text-center  border-l pt-2
                        "><h3></h3></div>
                    </div>
                </div>
                <div class="col-5 border-l">
                    <div class="row">
                        <div class="col-6 text-center pt-2
                        "><h3></h3></div>
                        <div class="col-6 text-center  border-l pt-2 ">
                            <img src="{{ asset('storage/image/forms/check.svg')
                            }}" alt="check" class="4"
                                 style="width: 40px">
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

{{--    <p style="height: 40px"></p>--}}


    <div class="row border-all " style="width: 920px">
        <div class="col-7 pt-3">
            <h6><strong>SYSTEM INTERACTION</strong></h6>
        </div>
        <div class="col-5">
            <div class="row">
                <div class="col-7 text-center border-l">
                    <h6 class="text-center pt-1"><strong>ATTEMPTS</strong></h6>
                    <div class="row">
                        <div class="col-4 pt-1 text-center
                        border-t"><h6><strong>1st</strong></h6></div>
                        <div class="col-4 pt-1 text-center
                        border-l-t"><h6><strong>2st</strong></h6></div>
                        <div class="col-4 pt-1 text-center
                        border-l-t"><h6><strong>3st</strong></h6></div>
                    </div>
                </div>
                <div class="col-5  border-l">
                    <h6 class="text-center pt-1"><strong>RESULT *</strong></h6>
                    <div class="row">
                        <div class="col-6 text-center pt-1
                        border-t"><h6><strong>U</strong></h6></div>
                        <div class="col-6 text-center pt-1
                        border-l-t"><h6><strong>S</strong></h6></div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <div class="row border-l-b-r" style="width: 920px">
        <div class="col-7 ">
            Trainee ‘analyses’ the consequences of other systems before
            performing an action (trainee can do this him/herself or by
            asking the assessor or a knowledgeable colleague)
        </div>
        <div class="col-5">
            <div class="row">
                <div class="col-7 text-center border-l">
                    <div class="row">
                        <div class="col-4 text-center pt-2
                        "><img src="{{ asset('storage/image/forms/check.svg')
                            }}" alt="check" class="4"
                               style="width: 40px; height: 62px"></div>
                        <div class="col-4 text-center  border-l pt-2
                        "><h3></h3></div>
                        <div class="col-4 text-center  border-l pt-2
                        "><h3></h3></div>
                    </div>
                </div>
                <div class="col-5 border-l">
                    <div class="row">
                        <div class="col-6 text-center pt-2
                        "><h3></h3></div>
                        <div class="col-6 text-center  border-l pt-2
                        "><img src="{{ asset('storage/image/forms/check.svg')
                            }}" alt="check" class="4"
                               style="width: 40px; height: 62px"></div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <div class="row border-l-b-r" style="width: 920px">
        <div class="col-7 ">
            Trainee takes other components into account when acting on a
            component
        </div>
        <div class="col-5">
            <div class="row">
                <div class="col-7 text-center border-l">
                    <div class="row">
                        <div class="col-4 text-center pt-2
                        "><img src="{{ asset('storage/image/forms/check.svg')
                            }}" alt="check" class="4"
                               style="width: 40px">
                        </div>
                        <div class="col-4 text-center  border-l pt-2
                        "><h3></h3></div>
                        <div class="col-4 text-center  border-l pt-2
                        "><h3></h3></div>
                    </div>
                </div>
                <div class="col-5 border-l">
                    <div class="row">
                        <div class="col-6 text-center pt-2
                        "><h3></h3></div>
                        <div class="col-6 text-center  border-l pt-2 ">
                            <img src="{{ asset('storage/image/forms/check.svg')
                            }}" alt="check" class="4"
                                 style="width: 40px">
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <div class="row border-l-b-r" style="width: 920px">
        <div class="col-7 pt-3">
            <h6><strong>PERFORMS COMPONENT FINAL / CLOSE-UP</strong></h6>
        </div>
        <div class="col-5">
            <div class="row">
                <div class="col-7 text-center border-l">
                    <h6 class="text-center pt-1"><strong>ATTEMPTS</strong></h6>
                    <div class="row">
                        <div class="col-4 pt-1 text-center
                        border-t"><h6><strong>1st</strong></h6></div>
                        <div class="col-4 pt-1 text-center
                        border-l-t"><h6><strong>2st</strong></h6></div>
                        <div class="col-4 pt-1 text-center
                        border-l-t"><h6><strong>3st</strong></h6></div>
                    </div>
                </div>
                <div class="col-5  border-l">
                    <h6 class="text-center pt-1"><strong>RESULT *</strong></h6>
                    <div class="row">
                        <div class="col-6 text-center pt-1
                        border-t"><h6><strong>U</strong></h6></div>
                        <div class="col-6 text-center pt-1
                        border-l-t"><h6><strong>S</strong></h6></div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <div class="row border-l-b-r" style="width: 920px">
        <div class="col-7 ">
            Trainee restores the component to the airworthy condition and
            complete WO package (or appropriate condition depending on the
            circumstances)
        </div>
        <div class="col-5">
            <div class="row">
                <div class="col-7 text-center border-l">
                    <div class="row">
                        <div class="col-4 text-center pt-2
                        "><img src="{{ asset('storage/image/forms/check.svg')
                            }}" alt="check" class="4"
                               style="width: 40px"></div>
                        <div class="col-4 text-center  border-l pt-2
                        "><h3></h3></div>
                        <div class="col-4 text-center  border-l pt-2
                        "><h3></h3></div>
                    </div>
                </div>
                <div class="col-5 border-l">
                    <div class="row">
                        <div class="col-6 text-center pt-2
                        "><h3></h3></div>
                        <div class="col-6 text-center  border-l pt-2
                        "><img src="{{ asset('storage/image/forms/check.svg')
                            }}" alt="check" class="4"
                               style="width: 40px"></div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    @if(Auth::user()->role?->name === 'Shop Certifying Authority (SCA)')
        <div class="row border-l-b-r" style="width: 920px">
            <div class="col-7 pt-3">
                <h6><strong>REPORTS IN MAINTENACE RECORDS/FINAL RELEASE</strong>
                </h6>
            </div>
            <div class="col-5">
                <div class="row">
                    <div class="col-7 text-center border-l">
                        <h6 class="text-center pt-1"><strong>ATTEMPTS</strong>
                        </h6>
                        <div class="row">
                            <div class="col-4 pt-1 text-center
                        border-t"><h6><strong>1st</strong></h6></div>
                            <div class="col-4 pt-1 text-center
                        border-l-t"><h6><strong>2st</strong></h6></div>
                            <div class="col-4 pt-1 text-center
                        border-l-t"><h6><strong>3st</strong></h6></div>
                        </div>
                    </div>
                    <div class="col-5  border-l">
                        <h6 class="text-center pt-1"><strong>RESULT *</strong>
                        </h6>
                        <div class="row">
                            <div class="col-6 text-center pt-1
                        border-t"><h6><strong>U</strong></h6></div>
                            <div class="col-6 text-center pt-1
                        border-l-t"><h6><strong>S</strong></h6></div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
        <div class="row border-l-b-r" style="width: 920px">
            <div class="col-7 ">
                Trainee fills the proper field in the maintenance record
            </div>
            <div class="col-5">
                <div class="row">
                    <div class="col-7 text-center border-l">
                        <div class="row">
                            <div class="col-4 text-center pt-2
                        "><img src="{{ asset('storage/image/forms/check.svg')
                            }}" alt="check" class="4"
                               style="width: 40px"></div>
                            <div class="col-4 text-center  border-l pt-2
                        "><h3></h3></div>
                            <div class="col-4 text-center  border-l pt-2
                        "><h3></h3></div>
                        </div>
                    </div>
                    <div class="col-5 border-l">
                        <div class="row">
                            <div class="col-6 text-center pt-2
                        "><h3></h3></div>
                            <div class="col-6 text-center  border-l pt-2
                        "><img src="{{ asset('storage/image/forms/check.svg')
                            }}" alt="check" class="4"
                               style="width: 40px"></div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
        <div class="row border-l-b-r" style="width: 920px">
            <div class="col-7 ">
                Trainee uses proper references and descriptions in the
                maintenance record
            </div>
            <div class="col-5">
                <div class="row">
                    <div class="col-7 text-center border-l">
                        <div class="row">
                            <div class="col-4 text-center pt-2
                        "><img src="{{ asset('storage/image/forms/check.svg')
                            }}" alt="check" class="4"
                               style="width: 40px">
                            </div>
                            <div class="col-4 text-center  border-l pt-2
                        "><h3></h3></div>
                            <div class="col-4 text-center  border-l pt-2
                        "><h3></h3></div>
                        </div>
                    </div>
                    <div class="col-5 border-l">
                        <div class="row">
                            <div class="col-6 text-center pt-2
                        "><h3></h3></div>
                            <div class="col-6 text-center  border-l pt-2 ">
                                <img src="{{ asset('storage/image/forms/check.svg')
                            }}" alt="check" class="4"
                                     style="width: 40px">
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    @elseif(Auth::user()->role?->name === 'Team Leader')
        <div class="row border-l-b-r" style="width: 920px">
            <div class="col-7 pt-3">
                <h6><strong>SUPERVISION OF MAINTENANCE WORK</strong></h6>
            </div>
            <div class="col-5">
                <div class="row">
                    <div class="col-7 text-center border-l">
                        <h6 class="text-center pt-1"><strong>ATTEMPTS</strong>
                        </h6>
                        <div class="row">
                            <div class="col-4 pt-1 text-center
                        border-t"><h6><strong>1st</strong></h6></div>
                            <div class="col-4 pt-1 text-center
                        border-l-t"><h6><strong>2st</strong></h6></div>
                            <div class="col-4 pt-1 text-center
                        border-l-t"><h6><strong>3st</strong></h6></div>
                        </div>
                    </div>
                    <div class="col-5  border-l">
                        <h6 class="text-center pt-1"><strong>RESULT *</strong>
                        </h6>
                        <div class="row">
                            <div class="col-6 text-center pt-1
                        border-t"><h6><strong>U</strong></h6></div>
                            <div class="col-6 text-center pt-1
                        border-l-t"><h6><strong>S</strong></h6></div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
        <div class="row border-l-b-r" style="width: 920px">
            <div class="col-7 ">
                Trainee verify the all maintenance tasks performed and
                maintenance records completed by Component Technician as
                specified in appropriate technical data.
            </div>
            <div class="col-5">
                <div class="row">
                    <div class="col-7 text-center border-l">
                        <div class="row">
                            <div class="col-4 text-center pt-2
                        "><img src="{{ asset('storage/image/forms/check.svg')
                            }}" alt="check" class="4"
                               style="width: 40px"></div>
                            <div class="col-4 text-center  border-l pt-2
                        "><h3></h3></div>
                            <div class="col-4 text-center  border-l pt-2
                        "><h3></h3></div>
                        </div>
                    </div>
                    <div class="col-5 border-l">
                        <div class="row">
                            <div class="col-6 text-center pt-2
                        "><h3></h3></div>
                            <div class="col-6 text-center  border-l pt-2
                        "><img src="{{ asset('storage/image/forms/check.svg')
                            }}" alt="check" class="4"
                               style="width: 40px"></div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
        <div class="row border-l-b-r" style="width: 920px">
            <div class="col-7 ">
                Trainee uses proper references and descriptions when
                supervising the Component Technician work.
            </div>
            <div class="col-5">
                <div class="row">
                    <div class="col-7 text-center border-l">
                        <div class="row">
                            <div class="col-4 text-center pt-2
                        "><img src="{{ asset('storage/image/forms/check.svg')
                            }}" alt="check" class=""
                               style="width: 40px">
                            </div>
                            <div class="col-4 text-center  border-l pt-2
                        "><h3></h3></div>
                            <div class="col-4 text-center  border-l pt-2
                        "><h3></h3></div>
                        </div>
                    </div>
                    <div class="col-5 border-l">
                        <div class="row">
                            <div class="col-6 text-center pt-2
                        "><h3></h3></div>
                            <div class="col-6 text-center  border-l pt-2 ">
                                <img src="{{ asset('storage/image/forms/check.svg')
                            }}" alt="check" class="4"
                                     style="width: 40px">
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="row border-l-b-r" style="width: 920px">
        <div class="col-7 ">
            <div class="row">
                <div class="col-2" style="font-size: 0.85rem;">1st attempt</div>
                <div class="col-10   text-center">
                    <h6>Found satisfactory </h6>
                </div>
            </div>
        </div>
        <div class="col-5">
            <div class="row">
                <div class="col-7 ">
                    <div class="row">
                        <div class="col-4"></div>
                        <div class="col-4"></div>
                        <div class="col-4 border-l " style="height: 40px">
                            <h6 class="ps-3 pt-2"> Vadym</h6>
                        </div>
                    </div>
                </div>
                <div class="col-5 ">
                    <div class="row">
                        <div class="col-12 text-center  ">
                            <div class="d-flex ">
                                <h6 class="pe-4 pt-2"> Nechyporenko</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row border-l-b-r" style="width: 920px">
        <div class="col-7 ">
            <div class="row">
                <div class="col-2" style="font-size: 0.85rem;">2st attempt</div>
                <div class="col-10  pt-3 text-center">
                    <h6></h6>
                </div>
            </div>
        </div>
        <div class="col-5">
            <div class="row">
                <div class="col-7 ">
                    <div class="row">
                        <div class="col-4"></div>
                        <div class="col-4"></div>
                        <div class="col-4 border-l " style="height: 48px">
                        </div>
                    </div>
                </div>
                <div class="col-5 ">
                    <div class="row">
                        <div class="col-12 text-center pt-3 ">
                            <h6></h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row border-l-b-r" style="width: 920px">
        <div class="col-7 ">
            <div class="row">
                <div class="col-2" style="font-size: 0.85rem;">3st attempt</div>
                <div class="col-10  pt-3 text-center">
                    <h6></h6>
                </div>
            </div>
        </div>
        <div class="col-5">
            <div class="row">
                <div class="col-7 ">
                    <div class="row">
                        <div class="col-4"></div>
                        <div class="col-4"></div>
                        <div class="col-4 border-l " style="height: 48px">
                        </div>
                    </div>
                </div>
                <div class="col-5 ">
                    <div class="row">
                        <div class="col-12 text-center pt-3 ">
                            <h6></h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row border-l-b-r" style="width: 920px">
        <div class="col-7 ">
            <div class="row">
                <div class="col-10 pt-2" style=" height: 36px">RESULT OF THE ASSESSMENT</div>
                <div class="col-2  pt-3 text-center">
                    <h6></h6>
                </div>
            </div>
        </div>
        <div class="col-5">
            <div class="row">
                <div class="col-7 ">
                    <div class="row">
                        <div class="col-4"></div>
                        <div class="col-4"></div>
                        <div class="col-4 border-l " style="height: 36px" >
                            <div class="d-flex ">
                               <h6 class="pe-2 pt-2">Succeeded</h6>

                                <img src="{{asset
                                ('/storage/image/forms/check-square.svg')}}"
                                     alt="">
                            </div>

                        </div>
                    </div>
                </div>
                <div class="col-5 ">
                    <div class="row">
                        <div class="col-2"></div>
                        <div class="col-10 text-center  ">
                            <div class="d-flex ">
                                <h6 class="ps-2 pe-2 pt-2">Remedial</h6>
                                <img src="{{asset('/storage/image/forms/square.svg')}}" alt="">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <div class="row border-l-r" style="width: 920px">
        <div class="col-7 pt-2" style="height: 36px">
            TRAINEE NAME:
        </div>
        <div class="col-5 border-l text-center pt-1">
            {{ $training->user->name }}
        </div>
    </div>
    <div class="row border-l-r" style="width: 920px">
        <div class="col-10 text-center">
            @if($showImage === 'true')
                <div class="ps-5" style="height: 1px">
                    <img src="{{ asset('storage/avatars/sign/' . Auth::user()->sign) }}"
                         alt="Sign_user" class="pb-3  " style="width: 175px">
                </div>
            @endif
        </div>
        <div class="col-7 pt-3 border-b border-t" >
            TRAINEE SIGNATURE:
        </div>
        <div class="col-5 border-b border-t">
            <div class="row">
                <div class="col-7" >
                    <div class="row ">
                        <div class="col-4 border-r"></div>
                        <div class="col-4  pt-3" >DATE:</div>
                        <div class="col-4 border-l" style="height: 60px"></div>
                    </div>
                </div>
                <div class="col-5">
                    <div class="row">
                        <div class="col-10 pt-3">
                            {{ \Carbon\Carbon::parse($training->date_training)->format('M-d-Y') }}
                        </div>
                        <div class="col-2 pt-2">

                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div class="row border-l-r" style="width: 920px">
        <div class="col-7 pt-1" style="height: 36px">
            ASSESSOR NAME:
        </div>
        <div class="col-5 border-l text-center pt-1">
            Vadym Nechyporenko
        </div>
    </div>
    <div class="row border-l-r" style="width: 920px">
        <div class="col-10 text-center">
            @if($showImage === 'true')
                @if(Auth::user()->role !== null && Auth::user()->role->name !==
           'Component Technician')
                <div class="ps-5 " style="height: 1px">
                    <img src="{{ asset('storage/image/sign/sign_vn.png') }}"
                         alt="Sign" class="" style="width: 150px">
                </div>
            @endif
            @endif
        </div>
        <div class="col-7 pt-3 border-b border-t" >
            ASSESSOR SIGNATURE:
        </div>
        <div class="col-5 border-b border-t" style="height:60px">
            <div class="row">
                <div class="col-7" >
                    <div class="row ">
                        <div class="col-4 border-r" style="height: 60px"></div>
                        <div class="col-4  pt-3" >DATE:</div>
                        <div class="col-4 border-l" ></div>
                    </div>
                </div>
                <div class="col-5">
                    <div class="row">
                        <div class="col-10 pt-3">
                            {{ \Carbon\Carbon::parse($training->date_training)->format('M-d-Y') }}
                        </div>
                        <div class="col-2 pt-2">

                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

<div class="pt-2">
    NOTE: * The trainee will be required to demonstrate a comprehensive
    understanding of the maintenance manuals and other data related to the
    aircraft component and practical skills to perform maintenance task on
    which training is being given.
</div>

</div>





<footer>
    <div class="row" style="width: 900px">
        <div class="col-6 text-start">
            {{__("Form #132")}}
        </div>
        <div class="col-6 text-end pe-4 ">
            {{__('Rev#0, 30/Nov/2018   ')}}
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
