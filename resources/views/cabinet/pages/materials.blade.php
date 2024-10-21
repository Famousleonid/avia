@extends('cabinet.master')

@section('content')

    <section class="container pl-3 pr-3 mt-2">
        <div class="card firm-border p-2 bg-white shadow">
            <div class="card-body p-0 pt-2">
                <div class="box-body ">
                    <div class="row">
                        <div class="h4 ml-3">
                            <span>All materials: <span class="text-primary" id="orders_count">{{count($materials)}}</span></span>
                        </div>
                    </div>
                    @if(count($materials))
                        <table data-page-length='25' id="show-materials" class="display table-sm table-bordered table-striped table-hover " style="width:100%;">
                            <thead>
                            <tr>
                                <th class="" style="width: 180px;" data-sort="true">CML Code</th>
                                <th class="text-center" data-orderable="false">Material</th>
                                <th>Specification</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($materials as $material)
                                <tr>
                                    <td class="">{{$material->code}}</td>
                                    <td class="">{{$material->material}}</td>
                                    <td class="">{{$material->specification}}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @else
                        <p>Materials not created</p>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection

@section('scripts')

    <script>

        document.addEventListener('DOMContentLoaded', function () {
            $('#show-materials').DataTable({
                "AutoWidth": true,
                "scrollY": "600px",
                "scrollX": false,
                "scrollCollapse": true,
                "paging": false,
                "info": false,
                "order": [[0, 'asc']],
            });
        });

    </script>

@endsection


