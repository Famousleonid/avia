@extends('admin.master')

@section('title', 'Управление компонентами NDT/CAD - Workorder #' . $workorder->number)

<style>
    .container {
        max-width: 1080px;
    }
    .text-center {
        text-align: center;
        align-content: center;
    }
    .card{
        max-width: 1060px;
    }

    html[data-bs-theme="dark"]  .select2-selection--single {
        background-color: #121212 !important;
        color: gray !important;
        height: 38px !important;
        border: 1px solid #495057 !important;
        align-items: center !important;
        border-radius: 8px;
    }

    html[data-bs-theme="dark"] .select2-container .select2-selection__rendered {
        color: #999999;
        line-height: 2.2 !important;
    }

    html[data-bs-theme="dark"] .select2-search--dropdown .select2-search__field  {
        background-color: #343A40 !important;
    }

    html[data-bs-theme="dark"] .select2-container--default .select2-selection--single .select2-selection__rendered {
        padding-right: 25px;
    }

    html[data-bs-theme="dark"] .select2-container .select2-dropdown {
        max-height: 40vh !important;
        overflow-y: auto !important;
        border: 1px solid #ccc !important;
        border-radius: 8px;
        color: white;
        background-color: #121212 !important;
    }

    html[data-bs-theme="light"] .select2-container .select2-dropdown {
        max-height: 40vh !important;
        overflow-y: auto !important;

    }

    html[data-bs-theme="dark"] .select2-container .select2-results__option:hover {
        background-color: #6ea8fe;
        color: #000000;

    }
    .select2-container .select2-selection__clear {
        position: absolute !important;
        right: 10px !important;
        top: 50% !important;
        transform: translateY(-50%) !important;
        z-index: 1;
    }


/*!* Стили для Select2 в модальных окнах *!*/
/*.select2-container--default .select2-dropdown {*/
/*    z-index: 9999 !important;*/
/*}*/

/*.select2-container--default .select2-selection--single {*/
/*    height: 38px !important;*/
/*    border: 1px solid #ced4da !important;*/
/*    border-radius: 0.375rem !important;*/
/*}*/

/*.select2-container--default .select2-selection--single .select2-selection__rendered {*/
/*    color: #999999;*/
/*    line-height: 36px !important;*/
/*    padding-left: 12px !important;*/
/*}*/

/*.select2-container--default .select2-selection--single .select2-selection__arrow {*/
/*    height: 36px !important;*/
/*}*/

/* Убеждаемся, что dropdown отображается поверх модального окна */
.modal .select2-container {
    z-index: 9999 !important;
}
</style>


@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between">
                        <h3 class="card-title">
                            Modification of STD list Processes for W{{ $workorder->number }}
                        </h3>
                        <div class="card-tools">
                            <a href="{{ route('tdrs.show', ['id'=>$workorder->id]) }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to TDR
                            </a>
                        </div>
                    </div>

                </div>

                <div class="card-body">
                    @include('admin.ndt-cad-csv.partial', compact('workorder', 'ndtCadCsv'))
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
