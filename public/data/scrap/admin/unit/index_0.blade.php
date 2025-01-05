@extends('resources.views.admin.master')

@section('content')

    <div class="container pl-3 pr-3 pt-2">

        <div class="card shadow firm-border bg-white mt-2">

            <div class="card-header row">
                <div class="col-3">
                    <h3 class="card-title text-bold">list of units ( {{count($units)}} )</h3>
                </div>
                <div class="col-4">
                    <a id="admin_new_unit_create" href={{route('unit.create')}} class=""><img
                                src="{{asset('img/plus.png')}}" width="40px" alt="" data-toggle="tooltip"
                                data-placement="top" title="Add new unit"></a>
                </div>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip"
                            title="Collapse">
                        <i class="fas fa-minus"></i></button>
                </div>
            </div>

            <div class="card-body">
                <div class="box-body table-responsive">

                    @if(count($units))

                        <table id="unit-list" class="table-sm table-bordered table-striped table-hover "
                               style="width:100%;">

                            <thead>
                            <tr>
                                <th class="text-center" data-orderable="false">№</th>
                                <th>PartNumber</th>
                                <th>Description</th>
                                <th>lib</th>
                                <th>Aircraft</th>
                                <th>Manufacturer</th>
                                <th class="text-center" data-orderable="false">Edit</th>
                                <th class="text-center" data-orderable="false">Delete</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($units as $unit)
                                <tr>
                                    <td class="text-center">{{$loop->iteration}}</td>
                                    <td class="">{{$unit->partnumber}}</td>
                                    <td>{{$unit->description}}</td>
                                    <td>{{$unit->lib}}</td>
                                    <td>{{$unit->aircraft}}</td>
                                    <td>{{$unit->manufacturer}}</td>
                                    <td class="text-center">
                                        <a href="{{route('unit.edit', ['unit' => $unit->id]) }}"><img
                                                    src="{{asset('img/set.png')}}" width="30" alt=""></a>
                                    </td>

                                    <td class="text-center">
                                        <div>
                                            <form action="{{route('unit.destroy', ['unit' => $unit->id])}}"
                                                  method="post">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-xs btn-danger" type="button" data-toggle="modal"
                                                        data-target="#confirmDelete"
                                                        data-title="Delete Unit"
                                                        data-message="Are you sure you want to delete unit p/n: {{$unit->partnumber}} ?">
                                                    <i class="fa fa-trash-o"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>

                                </tr>
                            @endforeach

                            </tbody>
                        </table>
                    @else
                        <p>No component created</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @include('resources.views.components.delete')

@endsection

@section('scripts')
    <script>
        let userTable = $('#unit-list').DataTable({
            "AutoWidth": true,
            "scrollY": "600px",
            "scrollCollapse": true,
            "paging": false,
            "ordering": true,
            "info": false,
        });
        // delete form confirm

        $('#confirmDelete').on('show.bs.modal', function (e) {

            $message = $(e.relatedTarget).attr('data-message');
            $(this).find('.modal-body p').text($message);
            $title = $(e.relatedTarget).attr('data-title');
            $(this).find('.modal-title').text($title);
            let form = $(e.relatedTarget).closest('form');
            $(this).find('.modal-footer #confirm').data('form', form);
        });
        $('#confirmDelete').find('.modal-footer #confirm').on('click', function () {
            $(this).data('form').submit();
        });

    </script>
@endsection
