@extends('admin.master_tdr')

@section('content')

    {{--        @if(count($tdrs))--}}

    <div class="container">

        <div class="table-wrapper me-3 p-2">
            <table id="componentTable" class="display table table-sm table-hover table-striped align-middle table-bordered">
                <thead class="bg-gradient">
                <tr>
                    <th class="text-center  sortable">{{__('IPL Number')}} <i class="bi bi-chevron-expand ms-1"></i></th>
                    <th class="text-center  sortable">{{__('Part
                                Dscription')}} <i class="bi bi-chevron-expand ms-1"></i></th>
                    <th class="text-center sortable ">{{__('Part number')}} <i class="bi bi-chevron-expand ms-1"></i></th>
                    <th class="text-center  sortable">{{__('Serial number')}} <i class="bi bi-chevron-expand ms-1"></i></th>
                    <th class=" text-center " style="width:
                                120px">{{__('Condition ')}}</th>
                    <th class=" text-center " style="width:
                                120px">{{__('Necessary')}}</th>
                    <th class=" text-center " style="width:
                                120px">{{__('Code')}}</th>
                    <th class=" text-center " style="width:
                                120px">{{__('Use TDR')}}</th>
                    <th class=" text-center " style="width:
                                120px">{{__('Use Processes')}}</th>
                    <th class="text-center ">Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach($tdrs as $tdr)
                    <tr>
                        <td
                            class="text-center">{{$tdr->component->part_number}}</td>
                    </tr>


                @endforeach
                </tbody>
            </table>
        </div>

    </div>

    {{--            @else--}}
    {{--                <H5 CLASS="text-center">{{__('WorkOrder NOT complete')}}</H5>--}}
    {{--        @endif--}}


    {{--    <script>--}}
    {{--        document.getElementById('updateWorkOrderForm').addEventListener('submit', function (e) {--}}
    {{--            e.preventDefault();--}}
    {{--            const formData = new FormData(this);--}}

    {{--            fetch('{{ route('admin.workorders.inspection', $current_wo->id) }}', {--}}
    {{--                method: 'POST',--}}
    {{--                headers: {--}}
    {{--                    'X-CSRF-TOKEN': '{{ csrf_token() }}'--}}
    {{--                },--}}
    {{--                body: formData--}}
    {{--            })--}}

    {{--        .then(response => response.json())--}}
    {{--                .then(data => {--}}
    {{--                    if (data.success) {--}}
    {{--                        alert('Work Order updated successfully!');--}}
    {{--                        location.reload();--}}
    {{--                    } else {--}}
    {{--                        alert('Failed to update Work Order.');--}}
    {{--                    }--}}
    {{--                })--}}
    {{--                .catch(error => {--}}
    {{--                    console.error('Error:', error);--}}
    {{--                    alert('An error occurred.');--}}
    {{--                });--}}
    {{--        });--}}

    {{--    </script>--}}

@endsection
