@extends('admin.master')

@section('content')



    <div class="container-fluid pl-3 pr-3 pt-2">

        <div class="card shadow firm-border bg-white mt-2">

            <div class="card-header">
                <h3 class="card-title text-bold">list of components ( {{count($components)}} )</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip" title="Collapse">
                        <i class="fas fa-minus"></i></button>
                </div>
            </div>

            <div class="card-body">
                <div class="box-body table-responsive">

                    @if(count($components))

                        <table id="user-list" class="table-sm table-bordered table-striped table-hover " style="width:100%;">

                            <thead>
                            <tr>
                                <th class="text-center" data-orderable="false">№</th>
                                <th>Name</th>
                                <th>PartNumber</th>
                                <th class="text-center" data-orderable="false">Edit</th>
                                <th class="text-center" data-orderable="false">Delete</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($components as $component)
                                <tr>
                                    <td class="text-center">{{$loop->iteration}}</td>
                                    <td class="text-primary">{{$component->name}}</td>
                                    <td>{{$component->partnumber}}</td>
                                    <td class="text-center">
                                        <a href="{{route('component.edit', ['component' => $component->id]) }}"><img src="{{asset('img/set.png')}}" width="30" alt=""></a>
                                    </td>

                                    <td class="text-center">
                                        <div>
                                            <form action="{{route('component.destroy', ['component' => $component->id])}}" method="post">
                                                @csrf
                                                @method('DELETE')

                                                <button class="btn btn-xs btn-danger" type="button" data-toggle="modal" data-target="#confirm-delete" data-title="Delete User" data-message="Are you sure you want to delete user {{$component->name}} ?">
                                                    <i class="fa fa-trash-o"></i>&nbsp;&nbsp;Delete
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

  @include('components.delete')

@endsection

@section('scripts')
    <script>
        let userTable = $('#user-list').DataTable({
            "AutoWidth": true,
            "scrollY": "600px",
            "scrollCollapse": true,
            "paging": false,
            "ordering": true,
            "info": false,
        });
            // delete form confirm

            $('#confirm-delete').on('show.bs.modal', function (e) {

                $message = $(e.relatedTarget).attr('data-message');
                $(this).find('.modal-body p').text($message);
                $title = $(e.relatedTarget).attr('data-title');
                $(this).find('.modal-title').text($title);

                // Pass form reference to modal for submission on yes/ok
                var form = $(e.relatedTarget).closest('form');

                console.log(form)

                $(this).find('.modal-footer #confirm').data('form', form);
            });
            $('#confirm-delete').find('.modal-footer #confirm').on('click', function () {
                $(this).data('form').submit();
            });

    </script>
@endsection
