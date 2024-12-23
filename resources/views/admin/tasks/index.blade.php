@extends('admin.master')

@section('content')



    <div class="container-fluid pl-3 pr-3 pt-2">

        <div class="card shadow firm-border bg-white mt-2">

            <div class="card-header">
                <h3 class="card-title text-bold">list of techniks ( {{count($tasks)}} )</h3>
                <span class="text-danger">&nbsp;&nbsp;&nbsp; RED name </span><span>this is an unconfirmed email</span>

                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip" title="Collapse">
                        <i class="fas fa-minus"></i></button>
                </div>
            </div>

            <div class="card-body">
                <div class="box-body table-responsive">

                    @if(count($tasks))

                        <table id="task-list" class="table-sm table-bordered table-striped table-hover " style="width:100%;">

                            <thead>
                            <tr>
                                <th class="text-center" data-orderable="false">№</th>
                                <th>Name</th>
                                <th class="text-center" data-orderable="false">Edit</th>
                                <th style="width: 100px" class="text-center">Create Date</th>
                                <th class="text-center" data-orderable="false">Delete</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($tasks as $task)
                                <tr>
                                    <td class="text-center">{{$loop->iteration}}</td>
                                    <td>{{$task->name}}</td>
                                    <td class="text-center">
                                        <a href="{{route('task.edit', ['task' => $task->id]) }}"><img src="{{asset('img/set.png')}}" width="30" alt=""></a>
                                    </td>
                                    @if($task->created_at)
                                        <td class="text-center"><span style="display: none">{{$task->created_at->format('Ymd')}}</span>{{$task->created_at->format('d.m.Y')}}</td>
                                    @else
                                        <td class="text-center"><span style="display: none">{{$task->created_at}}</span>{{$task->created_at}}</td>
                                    @endif
                                    <td class="text-center">
                                        <div>
                                            <form action="{{route('task.destroy', ['task' => $task->id])}}" method="post">
                                                @csrf
                                                @method('DELETE')

                                                <button class="btn btn-xs btn-danger" type="button" data-toggle="modal" data-target="#confirm-delete" data-title="Delete User" data-message="Are you sure you want to delete user {{$task->name}} ?">
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
                        <p>No user created</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @include('components.delete')

@endsection

@section('scripts')
    <script>
        let userTable = $('#task-list').DataTable({
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
            let form = $(e.relatedTarget).closest('form');
            $(this).find('.modal-footer #confirm').data('form', form);
        });
        $('#confirm-delete').find('.modal-footer #confirm').on('click', function () {
            $(this).data('form').submit();
        });

    </script>
@endsection
