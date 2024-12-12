@extends('mobile.master')

@section('content')

    <style>
        .table-responsive {
            overflow-x: auto;
        }

        #top-row > div {
            display: flex;
            align-items: center; /* Центрирует содержимое по вертикали */
        }

        @media (max-width: 768px) {
            #top-row > div {
                flex: 0 0 50%; /* Занимает ровно половину ширины экрана */
                max-width: 50%; /* Ограничивает максимальную ширину */
            }
        }

        #mobile-materials th.material-column, #mobile-materials td.material-column {
            width: 100px; /* Задает желаемую ширину */
            max-width: 100px; /* Ограничивает максимальную ширину */
        }

    </style>

    <section class="container-fluid  mt-1">
        <div class="firm-border bg-white">
            <div class="table-responsive">

                <div class="row" id="top-row">
                    <div class="col-6" id="material-count">
                        <div>
                            <span>All materials: <span class="text-primary" id="orders_count">{{count($materials)}}</span></span>
                        </div>
                    </div>
                    <div class="col-5" id="material-search">
                        <!-- Здесь будет строка поиска DataTables -->
                    </div>
                </div>

                @if(count($materials))

                    <table data-page-length='25' id="mobile-materials" class="display table-sm table-bordered table-striped table-hover " style="width:100%;">
                        <thead>
                        <tr>
                            <th class="">Code</th>
                            <th class="material-column">Material</th>
                            <th class="">Specification</th>
                        </tr>
                        </thead>
                        <tbody>

                        @foreach ($materials as $material)

                            <tr>
                                <td class="">{{$material->Code}}</td>
                                <td class="material-column">{{$material->Material}}</td>
                                <td class="">{{$material->Specification}}</td>

                            </tr>

                        @endforeach

                        </tbody>
                    </table>
                @else
                    <p>Materials not created</p>
                @endif
            </div>
        </div>
    </section>



@endsection

@section('scripts')

    <script>


        document.addEventListener('DOMContentLoaded', function () {

            let mainTable = $('#mobile-materials').DataTable({
                'responsive': true,
                'autoWidth': false,
                "paging": false,
                "info": false,
                "order": [[0, 'asc']],
                "ordering": false,

            });

            $('.dataTables_filter').appendTo('#material-search');

        });
    </script>

@endsection


