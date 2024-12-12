@extends('admin.master')


@section('content')

    <style>
        .json-field {
            white-space: pre-wrap;
            word-wrap: break-word;
        }
    </style>
    <div class="container-fluid pl-3 pr-3 pt-2">
        <div class="card shadow firm-border bg-white mt-2">
            <div class="card-header row">
                <div class="col-6"><h3 class="card-title text-bold">list of logs ( {{count($acts)}} )</h3></div>
            </div>
            @php
                use App\Models\User;
                use App\Models\Workorder;
            @endphp
            <div class="card-body">
                <div class="box-body table-responsive">
                    @if(count($acts))
                        <table id="customers-list" class="table-sm table-bordered table-striped table-hover " style="width:100%;">
                            <thead>
                            <tr>
                                <th>Technic</th>
                                <th>Model</th>
                                <th>Event</th>
                                <th>Name</th>
                                <th>Json</th>
                                <th hidden>Date create</th>
                                <th>Date create</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($acts as $act)
                                <tr>
                                    <td>
                                        @php
                                            $user = \App\Models\User::find($act->causer_id);
                                        @endphp
                                        @if ($user)
                                            {{ $user->name }}
                                        @else
                                            User not found for ID: {{ $act->causer_id }}
                                        @endif
                                    </td>
                                    <td>{{ collect(explode('\\', $act->subject_type))->last()   }}</td>
                                    <td>{{$act->event}}</td>


                                    <td class="text-center">
                                        @php
                                            $subject = null;
                                            switch ($act->subject_type) {
                                                case 'App\Models\User':
                                                    $subject = User::find($act->subject_id);
                                                    break;
                                                case 'App\Models\Workorder':
                                                    $subject = Workorder::find($act->subject_id);
                                                    break;
                                            }
                                        @endphp
                                        {{ $subject ? $subject : 'Not Found' }}
                                    </td>
                                    <td class="json-field">{{$act->properties}}</td>
                                    <td hidden>{{$act->created_at}}</td>
                                    <td>{{$act->created_at->format('d.m.Y')}}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @else
                        <p>No logs</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @include('components.delete')

@endsection

@section('scripts')
    <script>
        let userTable = $('#customers-list').DataTable({
            "AutoWidth": true,
            "scrollY": "550px",
            "scrollCollapse": true,
            "paging": false,
            "order": [[6, 'desc']],
            "info": false,
            "columnDefs": [
                {"width": "10%", "targets": 0},
                {"width": "5%", "targets": 1},
                {"width": "5%", "targets": 2},
                {"width": "5%", "targets": 3},
                {"width": "5%", "targets": 4},
                {"width": "50%", "targets": 5},
                {"width": "15%", "targets": 6},
            ],
        });


        $('#confirmDelete').on('show.bs.modal', function (e) {

            let message = $(e.relatedTarget).attr('data-message');
            $(this).find('.modal-body p').text(message);
            let $title = $(e.relatedTarget).attr('data-title');
            $(this).find('.modal-title').text($title);
            let form = $(e.relatedTarget).closest('form');
            $(this).find('.modal-footer #buttonConfirm').data('form', form);
        });

        $('#confirmDelete').find('.modal-footer #buttonConfirm').on('click', function () {
            $(this).data('form').submit();
        });

    </script>
@endsection
