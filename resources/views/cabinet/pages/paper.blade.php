@extends('cabinet.master')

@section('content')

    <style>
        .container-80vh {
            height: 80vh;
        }

        .row-100h {
            height: 100%;
        }

        .split-window {
            height: calc(100% - 10px); /* Учитываем отступы сверху и снизу */
            width: calc(50% - 20px); /* Учитываем отступы с каждой стороны */
            padding: 5px;
            box-sizing: border-box; /* Учитываем padding в ширину и высоту */
        }

        .left-window {
            background-color: white;
        }

        .right-window {
            background-color: white;
            margin-left: 15px;
        }

        @media (max-width: 768px) {
            .split-window {
                width: calc(100% - 10px); /* Учитываем отступы с каждой стороны */
                height: calc(50% - 20px); /* Учитываем отступы сверху и снизу */
            }

            .right-window {
                margin-left: 0;
                margin-top: 15px;
            }
        }
    </style>


    <section class="container-fluid pl-1 pr-1 boss-window">
        <div class="card firm-border px-2  shadow">
            <div class="card-body p-0 pt-2">
                <div class="row ">
                    <div class="ml-2 col-6  pt-1">
                        <span class="h5">Workorder: </span> <span class="text-primary h5" id="orders_count" style="display:inline-block; min-width:4ch">w{{$current_workorder->number }}</span>
                    </div>
                </div>
            </div>

            <div class="container-fluid d-flex justify-content-center align-items-center container-80vh">
                <div class="row no-gutters w-100 row-100h">
                    <div class="d-flex split-window left-window d-flex align-items-center justify-content-center shadow-lg">
                        <h1>Left Window</h1>
                    </div>
                    <div class="d-flex split-window right-window d-flex align-items-center justify-content-center shadow-lg">
                        <h1>Right Window</h1>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {

        });
    </script>
@endsection
