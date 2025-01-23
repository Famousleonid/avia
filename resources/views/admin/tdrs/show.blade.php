@extends('admin.master')

@section('content')
    <style>
        /*.container {*/
        /*    max-width: 1100px;*/
        /*}*/
    </style>

    @if($current_wo->unit->manuals->builder )
        <div class="card bg-gradient">
            <div class="card-header  m-1 shadow">

                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="text-primary  ps-4">{{__('Work Order')}}
                            <span
                                class="text-success
                            ps-3 ">{{$current_wo->number}}
                </span></h5>

                        <div class="d-flex pt-2">
                            <div style="width: 150px">{{'Component Name: '}}</div>
                            <div style="width: 150px">{{$current_wo->description}}</div>
                        </div>
                        <div class="d-flex ">
                            <div class="text-end pe-3" style="width: 150px">{{'Part Number: '}}</div>
                            <div style="width: 150px">{{$current_wo->unit->part_number}}</div>
                        </div>
                        <div class="d-flex ">
                            <div class="text-end pe-3" style="width: 150px">{{'Serial Number: '}}</div>
                            <div style="width: 150px">{{$current_wo->serial_number}}</div>
                        </div>
                        <div class="d-flex ">
                            <div class="text-end pe-3" style="width: 150px">{{'Instruction: '}}</div>
                            <div style="width: 150px">{{$current_wo->instruction->name}}</div>
                        </div>
                        <div class="d-flex ">
                            <div class="text-end pe-3" style="width: 150px">{{'CMM: '}}</div>
                            <div style="width: 150px">{{$current_wo->unit->manuals->number}}</div>
                        </div>
                        <div class="d-flex ">
                            <div class="text-end pe-3" style="width: 150px">{{'MFR: '}}</div>
                            <div style="width: 150px">{{$current_wo->unit->manuals->builder->name}}</div>
                        </div>

                        <div class="d-flex ">
                            <div class="text-end pe-3" style="width: 150px">{{'Lib: '}}</div>
                            <div style="width: 150px">{{$current_wo->unit->manuals->lib}}</div>
                        </div>

                    </div>
                    <div class="m-2">

                        <a href="{{ $current_wo->unit->manuals->getBigImageUrl('manuals') }}" data-fancybox="gallery">
                        <img class="" src="{{ $current_wo->unit->manuals->getBigImageUrl('manuals')}}"
                             width="200" height="200" alt="Image"/>
                        </a>
                    </div>
                    <div class=" ps-1 ">

                        <div class="d-flex justify-content-between ">

                            <div class=" ">
                                <button class="btn btn-outline-primary  "
                                        style="height: 40px; width: 280px"
                                        data-bs-toggle="modal"
                                        data-bs-target="#addWoInspectModal">
                                    <h5>{{__('WO Inspection')}}</h5>
                                </button>
                                <div class="d-flex ps-2  pt-1">
                                    <div style="width: 250px">{{'Parts Missing  '}}</div>
                                    <div style="width: 50px">
                                        @if($current_wo->part_missing)
                                            <i class="bi bi-check-square"></i>
                                        @else
                                            <i class="bi bi-square"></i>
                                        @endif
                                    </div>
                                </div>
                                <div class="d-flex ps-2">
                                    <div style="width: 250px">{{'External Damage  '}}</div>
                                    <div style="width: 50px">
                                        @if($current_wo->external_damage)
                                            <i class="bi bi-check-square"></i>
                                        @else
                                            <i class="bi bi-square"></i>
                                        @endif
                                    </div>
                                </div>
                                <div class="d-flex ps-2">
                                    <div style="width: 250px">{{'Received Disassembly  '}}</div>
                                    <div style="width: 50px">
                                        @if($current_wo->received_disassembly)
                                            <i class="bi bi-check-square"></i>
                                        @else
                                            <i class="bi bi-square"></i>
                                        @endif
                                    </div>

                                </div>
                                <div class="d-flex  ps-2">
                                    <div style="width: 250px">{{'Disassembly Upon Arrival  '}}</div>
                                    <div style="width: 50px">
                                        @if($current_wo->disassembly_upon_arrival)
                                            <i class="bi bi-check-square"></i>
                                        @else
                                            <i class="bi bi-square"></i>
                                        @endif
                                    </div>
                                </div>
                                <div class="d-flex ps-2 ">
                                    <div style="width: 250px">{{'Name Plate Missing  '}}</div>
                                    <div style="width: 50px">
                                        @if($current_wo->nameplate_missing)
                                            <i class="bi bi-check-square"></i>
                                        @else
                                            <i class="bi bi-square"></i>
                                        @endif
                                    </div>
                                </div>
                                <div class="d-flex ps-2">
                                    <div style="width: 250px">{{'Preliminary Test - False  '}}</div>
                                    <div style="width: 50px">
                                        @if($current_wo->preliminary_test_false)
                                            <i class="bi bi-check-square"></i>
                                        @else
                                            <i class="bi bi-square"></i>
                                        @endif
                                    </div>
                                </div>
                                <div class="d-flex ps-2">
                                    <div style="width: 250px">{{'Extra Parts  '}}</div>
                                    <div style="width: 50px">
                                        @if($current_wo->extra_parts)
                                            <i class="bi bi-check-square"></i>
                                        @else
                                            <i class="bi bi-square"></i>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="">






                            </div>
                        </div>

                    </div>

                    <div>
                        <div class="d-flex ">
                            <div style="width: 600px">
                                <a href="{{route('admin.tdrs.inspection',
                                ['workorder_id' => $current_wo->id])}}"
                                   class="btn
                                btn-outline-primary "
                                   style="height: 40px">
                                    {{__('Add Unit Inspection')}}
                                </a>

{{--                                <button class="btn btn-outline-primary btn-sm" style="height: 40px" data-bs-toggle="modal"--}}
{{--                                        data-bs-target="#createModal">{{ __('Add Component') }}</button>--}}
                            </div>

                        </div>
                    </div>
                </div>

            </div> <! --- Header end --- ->

            <! --- Body --- ->

            {{--        @if(count($tdrs))--}}

            <div class="">
{{$current_wo->id}} - {{count($tdrs)}}

                <div class="d-flex justify-content-between">
                    <div style="width: 300px">
                        <div class="table-wrapper me3 p-2">
                            <table id="tdr_inspect_Table" class="display table table-sm
                    table-hover table-striped align-middle
                    table-bordered bg-gradient">
                                <thead>
                                <tr>
                                    <th class="text-center">{{__('Teardown
                                    Inspection')}}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($tdrs as $tdr)
                                    @if($tdr->use_tdr == true and $tdr->use_process_forms != true)
                                            <tr>
                                                <td
                                                    class="text-center">
                                                    @foreach($conditions as $condition)
                                                        @if($condition->id == $tdr->conditions_id)
                                                            {{$condition ->name}}
                                                        @endif
                                                    @endforeach

                                                    @foreach($components as $component)
                                                        @if($component->id == $tdr->component_id)
                                                            {{$component -> name}} ({{$component -> ipl_num}})
                                                        @endif
                                                    @endforeach

                                                </td>
                                            </tr>

                                    @endif
                                @endforeach

                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div>
                        <div class="table-wrapper me-3 p-2">
                            <table id="tdr_process_Table" class="display table table-sm
                    table-hover table-striped align-middle
                    table-bordered">
                                <thead class="bg-gradient">
                                <tr>
                                    <th class="text-center  sortable">{{__('IPL Number')}} <i class="bi bi-chevron-expand ms-1"></i></th>
                                    <th class="text-center  sortable">{{__('Part
                                Description')}} <i class="bi bi-chevron-expand ms-1"></i></th>
                                    <th class="text-center sortable ">{{__('Part number')}} <i class="bi bi-chevron-expand ms-1"></i></th>
                                    <th class="text-center  sortable">{{__('Serial number')}} <i class="bi bi-chevron-expand ms-1"></i></th>
                                    <th class=" text-center " style="width:
                                300px">{{__('Condition ')}}</th>
                                    <th class=" text-center " style="width:
                                200px">{{__('Necessary')}}</th>
                                    <th class=" text-center " style="width:
                                120px">{{__('Code')}}</th>
{{--                                    <th class=" text-center " style="width:--}}
{{--                                120px">{{__('Use TDR')}}</th>--}}
{{--                                    <th class=" text-center " style="width:--}}
{{--                                120px">{{__('Use Processes')}}</th>--}}
                                    <th class="text-center ">Action</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($tdrs as $tdr)
                                    @if($tdr->use_tdr == true and $tdr->use_process_forms == true)

                                            <tr>
                                                <td  class="text-center"> <!-- IPL Number -->
                                                    @foreach($components as $component)
                                                        @if($component->id == $tdr->component_id)
                                                            {{$component -> ipl_num}}
                                                        @endif
                                                    @endforeach
                                                </td>
                                                <td class="text-center"><!--  Part Description -->
                                                    @foreach($components as $component)
                                                        @if($component->id == $tdr->component_id)
                                                            {{$component -> name}}
                                                        @endif
                                                    @endforeach
                                                </td>
                                                <td class="text-center"><!--  Part Number -->
                                                    @foreach($components as $component)
                                                        @if($component->id == $tdr->component_id)
                                                            {{$component ->part_number}}
                                                        @endif
                                                    @endforeach
                                                </td>
                                                <td class="text-center"> <!--  Serial Number -->
                                                    {{$tdr->serial_number}}
                                                </td>
                                                <td class="text-center"><!--  Condition -->
                                                    @foreach($conditions as $condition)
                                                        @if($condition->id == $tdr->conditions_id)
                                                            {{$condition ->name}}
                                                        @endif
                                                    @endforeach
                                                </td>
                                                <td class="text-center"><!--  Necessary -->
                                                    @foreach($necessaries as $necessary)
                                                        @if($necessary->id == $tdr->necessaries_id)
                                                            {{$necessary ->name}}
                                                        @endif
                                                    @endforeach
                                                </td>
                                                <td class="text-center"><!--  Code -->
                                                    @foreach($codes as $code)
                                                        @if($code->id == $tdr->codes_id)
                                                            {{$code ->name}}
                                                        @endif
                                                    @endforeach
                                                </td>

                                                <td class="text-center">
                                                    <a href="{{ route('admin.tdrs.edit',['tdr' => $tdr->id]) }}"
                                                       class="btn btn-outline-primary btn-sm">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </a>

                                                </td>

                                            </tr>

                                    @endif




                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>




            </div>

            {{--            @else--}}
            {{--                <H5 CLASS="text-center">{{__('WorkOrder NOT complete')}}</H5>--}}
            {{--        @endif--}}

        </div>


    @else

        <!-- Manual data Not COMPLETE  -->
        <div>
            <H5 CLASS=" m-3">{{__('MANUAL ')}} {{$current_wo->unit->manuals->number}} {{__('NOT COMPLETE')}}</H5>
            <div class="d-flex border " style="width: 500px">
                <div class="m-3">
                    <img class="" src="{{ $current_wo->unit->manuals->getBigImageUrl('manuals') }}"
                         width="200"  alt="Image"/>

                </div>
                <div CLASS="text-center m-3 " style="width: 250px">
                    <p><strong>{{ __('CMM:') }}</strong> {{ $current_wo->unit->manuals->number }}</p>
                    <p><strong>{{ __('Description:') }}</strong>
                        {{ $current_wo->unit->manuals->title }} </p>
                    <p><strong>{{ __('Revision Date:')}}</strong> {{ $current_wo->unit->manuals->revision_date }}</p>
                    <p><strong>{{ __('AirCraft Type:')}}</strong>
                        {{ $planes[$current_wo->unit->manuals->planes_id] ?? 'N/A' }}</p>
                    <p><strong>{{ __('MFR:') }}</strong> {{$builders[$current_wo->unit->manuals->builders_id] ?? 'N/A' }}</p>
                    <p><strong>{{ __('Scope:') }}</strong> {{$scopes[$current_wo->unit->manuals->scopes_id] ?? 'N/A' }}</p>
                    <p><strong>{{ __('Library:') }}</strong> {{$current_wo->unit->manuals->lib }}</p>
                </div>
            </div>
        </div>

    @endif



    <!-- Модальное окно WO Inspection  -->
    <div class="modal fade" id="addWoInspectModal" tabindex="-1" aria-labelledby="addUnitLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content bg-gradient">
                <div class="modal-header">
                    <h5 class="modal-title" id="addInspectLabel">
                        {{'Inspection '}} {{'Work Order '}}
                        <span class="text-success">
                           {{$current_wo->number}}
                       </span>

                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <div class="modal-body">

                    <div>
                        <form id="updateWorkOrderForm">
                            @csrf
                            <div class="form-check">
                                <label class="form-check-label" for="part_missing">Parts Missing</label>
                                <input class="form-check-input" type="checkbox" name="part_missing" id="part_missing" {{ $current_wo->part_missing ? 'checked' : '' }}>
                            </div>
                            <div class="form-check">
                                <label class="form-check-label" for="external_damage">External Damage</label>
                                <input class="form-check-input" type="checkbox" name="external_damage" id="external_damage" {{ $current_wo->external_damage ? 'checked' : '' }}>
                            </div>
                            <div class="form-check">
                                <label class="form-check-label" for="received_disassembly">Received Disassembly</label>
                                <input class="form-check-input" type="checkbox" name="received_disassembly" id="received_disassembly" {{ $current_wo->received_disassembly ? 'checked' : '' }}>
                            </div>
                            <div class="form-check">
                                <label class="form-check-label" for="disassembly_upon_arrival">Disassembly Upon Arrival</label>
                                <input class="form-check-input" type="checkbox" name="disassembly_upon_arrival" id="disassembly_upon_arrival" {{ $current_wo->disassembly_upon_arrival ? 'checked' : '' }}>
                            </div>
                            <div class="form-check">
                                <label class="form-check-label" for="nameplate_missing">Name Plate Missing</label>
                                <input class="form-check-input" type="checkbox" name="nameplate_missing" id="nameplate_missing" {{ $current_wo->nameplate_missing ? 'checked' : '' }}>
                            </div>
                            <div class="form-check">
                                <label class="form-check-label" for="preliminary_test_false">Preliminary Test - False</label>
                                <input class="form-check-input" type="checkbox" name="preliminary_test_false" id="preliminary_test_false" {{ $current_wo->preliminary_test_false ? 'checked' : '' }}>
                            </div>
                            <div class="form-check ">
                                <label class="form-check-label" for="extra_parts">Extra Parts</label>
                                <input class="form-check-input" type="checkbox" name="extra_parts" id="extra_parts" {{ $current_wo->extra_parts ? 'checked' : '' }}>
                            </div>
                            <div class="modal-footer mt-3" style="height: 60px">
                                <button type="submit" class="btn btn-outline-primary">{{ __('Save') }}</button>
                            </div>
                        </form>

                    </div>


                </div>
            </div>
        </div>
    </div>







    <script>
        document.getElementById('updateWorkOrderForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('{{ route('admin.workorders.inspection', $current_wo->id) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: formData
            })

                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Work Order updated successfully!');
                        location.reload();
                    } else {
                        alert('Failed to update Work Order.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred.');
                });
        });

    </script>

@endsection
