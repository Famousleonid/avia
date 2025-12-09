@extends('admin.master')


@section('content')

    <div class="container pl-3 pr-3 pt-2">
        <div class="card shadow firm-border bg-white mt-2">
            <div class="card-header row">
                <div class="col-6"><h3 class="card-title text-bold">list of customers ( {{count($customers)}} )</h3></div>
                <div class="col-5"><a id="admin_new_firm_create" href={{route('customers.create')}} class=""><img src="{{asset('img/plus.png')}}" width="30px" alt="" data-toggle="tooltip" data-placement="top" title="Add new customer"></a></div>
                <div class="col-1 card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip" title="Collapse"><i class="fas fa-minus"></i></button>
                </div>
            </div>
            <div class="card-body">
                <div class="box-body table-responsive">
                    @if(count($customers))
                        <table id="customers-list" class="table-sm table-bordered table-striped table-hover " style="width:100%;">
                            <thead>
                            <tr>
                                <th class="text-center" data-orderable="false">№</th>
                                <th>Name</th>
                                <th class="text-center" data-orderable="false">Edit</th>
                                <th class="text-center" data-orderable="false">Delete</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($customers as $customer)
                                <tr>
                                    <td class="text-center">{{$loop->iteration}}</td>
                                    <td>{{$customer->name}}</td>
                                    <td class="text-center">
                                        <a href="{{route('customers.edit', ['customer' => $customer->id]) }}"><img src="{{asset('img/set.png')}}" width="25" alt=""></a>
                                    </td>
                                    <td class="text-center">
                                        <div>
                                            <form action="{{route('customers.destroy', ['customer' => $customer->id])}}" method="post">
                                                @csrf
                                                @method('DELETE')

                                                <button class="btn btn-xs btn-danger" type="button" data-toggle="modal" data-target="#confirmDelete" data-title="Delete Customer" data-message="Are you sure you want to delete customer: {{$customer->name}} ?">
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
                        <p>No customers created</p>
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
            "scrollY": "500px",
            "scrollCollapse": true,
            "paging": false,
            "ordering": true,
            "info": false,
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
