@extends('admin.master')

@section('content')
    <style>
        .table-wrapper {
            height: calc(100vh - 120px);
            overflow-y: auto;
            overflow-x: hidden;
        }

        .text-truncate {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>

    <div class="container">
        <div class="card shadow  ">
            <div class="card-header">

                <div class="d-flex justify-content-between">
                    <h3>{{__('Manage CMMs')}}</h3>
                    <a href="{{ route('manuals.create') }}" class="btn btn-primary ">{{ __('Add CMM') }}</a>
                </div>

            </div>

            <div class="card-body table-wrapper table-responsive">

                <table id="cmmTable" class="table table-sm table-bordered table-striped fixed-header">
                    <thead>
                    <tr>
                        <th class="col-1 py-1 ">{{__('Number')}}</th>
                        <th class="col-2 py-1 ">{{__('Title')}}</th>
                        <th class="col-3 py-1 ">{{__('Units PN')}}</th>
                        <th class="col-1 py-1 ">{{__('Unit Image')}}</th>
                        <th class="col-2 py-1 ">{{__('Revision Date')}}</th>
                        <th class="col-1 py-1 ">{{__('Library')}}</th>
                        <th class="col-2 py-1 ">{{__('Action')}}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($cmms as $cmm)
                        <tr>
                            <td class="py-1 ">{{$cmm->number}}</td>
                            <td class="py-1 text-truncate" title="{{$cmm->title}}">{{$cmm->title}}</td>
                            <td class="py-1 text-truncate" title="{{$cmm->unit_name}}">{{$cmm->unit_name}}</td>
                            <td class="py-1 ">
                                <a href="#" data-bs-toggle="modal" data-bs-target="#imageModal{{$cmm->id}}">
                                    <img src="{{ asset('img/noimage.png') }}" style="width: 36px; cursor: pointer;" alt="Img">
                                </a>
                            </td>
                            <td class="py-1 ">{{$cmm->revision_date}}</td>
                            <td class="py-1 ">{{$cmm->lib}}</td>
                            <td class="py-1 ">
                                <a href="{{ route('manuals.edit', $cmm->id) }}" class="btn btn-primary btn-sm">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <form action="{{ route('manuals.destroy', $cmm->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?');">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection

@section('scripts')

    <script>

        document.addEventListener('DOMContentLoaded', function() {
            $('#cmmTable').DataTable({
                fixedHeader: true,
                searching: true,
                ordering: true,
                paging: false,
                info: false,
                columnDefs: [
                    {
                        targets: [3, 6], // 4 & 7 column
                        className: 'text-center'
                    }
                ],
                columns: [
                    { width: "10%" },                                    // "Number"
                    { width: "20%" },                                    // "Title"
                    { width: "20%", orderable: false },                  // "Units PN"
                    { width: "15%", searchable:false, orderable: false}, // "Unit Image"
                    { width: "15%" },                                    // "Revision Date"
                    { width: "10%" },                                    // "Library"
                    { width: "10%" , searchable:false, orderable: false} // "Action"
                ]
            });
        });
    </script>

@endsection
