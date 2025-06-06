@extends('admin.master')

@section('content')
    <style>
        /*.container {*/
        /*    max-width: 1100px;*/
        /*}*/
        .text-center {
            text-align: center;
            align-content: center;
        }
    </style>

    @if($current_wo->unit->manuals->builder )

        <div class="card bg-gradient">
            <div class="card-header  m-1 shadow">

                <div class="d-flex ">
                    <div style="width: 300px;">
                        <h5 class="text-primary  ps-4">{{__('Work Order')}}
                            <a class="text-success-emphasis  ps-4" href="#" data-bs-toggle="modal"
                               data-bs-target = #infoModal{{$current_wo->number}}>{{$current_wo->number}}
                            </a>
                        </h5>
                        <div class="modal fade" id="infoModal{{$current_wo->number}}" tabindex="-1"
                             role="dialog" aria-labelledby="infoModalLabel{{$current_wo->number}}" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content bg-gradient" style="width: 800px">
                                    <div class="modal-header">
                                        <div>
                                            <h4 class="modal-title" >{{__('Work order ')}}{{$current_wo->number}}</h4>
                                        </div>
                                        <button type="button" class="btn-close pb-2"  data-bs-dismiss="modal" aria-label="Close"> </button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="d-flex">
                                            <div>
                                                <div class="m-2">
                                                    <a href="{{ $current_wo->unit->manuals->getBigImageUrl('manuals') }}" data-fancybox="gallery">
                                                        <img class="" src="{{ $current_wo->unit->manuals->getBigImageUrl('manuals')}}"
                                                             width="150" height="150" alt="Image"/>
                                                    </a>
                                                </div>
                                            </div>
                                            <div>
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
                                            <div>
                                                <div class="d-flex ps-2">
                                                    <div style="width: 200px">{{'Parts Missing  '}}</div>
                                                    <div style="width: 30px">
                                                        @if($current_wo->part_missing)
                                                            <i class="bi bi-check-square"></i>
                                                        @else
                                                            <i class="bi bi-square"></i>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="d-flex ps-2">
                                                    <div style="width: 200px">{{'External Damage  '}}</div>
                                                    <div style="width: 30px">
                                                        @if($current_wo->external_damage)
                                                            <i class="bi bi-check-square"></i>
                                                        @else
                                                            <i class="bi bi-square"></i>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="d-flex ps-2">
                                                    <div style="width: 200px">{{'Received Disassembly  '}}</div>
                                                    <div style="width: 30px">
                                                        @if($current_wo->received_disassembly)
                                                            <i class="bi bi-check-square"></i>
                                                        @else
                                                            <i class="bi bi-square"></i>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="d-flex  ps-2">
                                                    <div style="width: 200px">{{'Disassembly Upon Arrival  '}}</div>
                                                    <div style="width: 30px">
                                                        @if($current_wo->disassembly_upon_arrival)
                                                            <i class="bi bi-check-square"></i>
                                                        @else
                                                            <i class="bi bi-square"></i>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="d-flex ps-2 ">
                                                    <div style="width: 200px">{{'Name Plate Missing  '}}</div>
                                                    <div style="width: 30px">
                                                        @if($current_wo->nameplate_missing)
                                                            <i class="bi bi-check-square"></i>
                                                        @else
                                                            <i class="bi bi-square"></i>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="d-flex ps-2">
                                                    <div style="width: 200px">{{'Preliminary Test - False  '}}</div>
                                                    <div style="width: 30px">
                                                        @if($current_wo->preliminary_test_false)
                                                            <i class="bi bi-check-square"></i>
                                                        @else
                                                            <i class="bi bi-square"></i>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="d-flex ps-2">
                                                    <div style="width: 200px">{{'Extra Parts  '}}</div>
                                                    <div style="width: 30px">
                                                        @if($current_wo->extra_parts)
                                                            <i class="bi bi-check-square"></i>
                                                        @else
                                                            <i class="bi bi-square"></i>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="ps-2 d-flex" style="width: 300px;">
{{--                        <div class="me-2" >--}}
{{--                            <a href="{{route('admin.tdrs.inspection',['workorder_id' => $current_wo->id])}}"--}}
{{--                               class="btn  btn-outline-primary " style="height: 60px;align-content: center"--}}
{{--                               onclick="showLoadingSpinner()">--}}
{{--                                {{__('Add Inspection')}}--}}
{{--                            </a>--}}
{{--                        </div>--}}
                        @if(count($processParts))
                            <div class="me-2" >
                                <a href="{{route('admin.tdrs.processes',['workorder_id' => $current_wo->id])}}"
                                   class="btn  btn-outline-primary " style="height: 60px;width: 150px" onclick="showLoadingSpinner
                                   ()">
                                    {{__('WO Component Processes')}}
                                </a>
                            </div>
                        @endif

                        <div>

                        </div>
                    </div>

                    <div class="ms-3" >
                        <div class="d-flex ">
{{--                            <div class="me-2">--}}
{{--                                @if(count($inspectsUnit)>0)--}}
{{--                                    <button class="btn btn-outline-info btn-sm" style="height: 40px"--}}
{{--                                            data-bs-toggle="modal"--}}
{{--                                            data-bs-target="#inspectModal{{$current_wo->number}}">--}}
{{--                                        {{ __('Inspect Unit') }}</button>--}}
{{--                                @endif--}}
{{--                            </div>--}}
{{--                            <div class="me-2">--}}
{{--                                  @if($current_wo->part_missing )--}}
{{--                                    <button class="btn btn-outline-info btn-sm" style="height: 40px"--}}
{{--                                            data-bs-toggle="modal"--}}
{{--                                            data-bs-target="#missingModal{{$current_wo->number}}">--}}
{{--                                        {{ __('Missing Part') }}</button>--}}
{{--                                  @endif--}}
{{--                            </div>--}}
{{--                            <div class="me-2">--}}
{{--                                @if($current_wo->new_parts)--}}
{{--                                    <button class="btn btn-outline-info btn-sm" style="height: 40px" href="#"--}}
{{--                                            data-bs-toggle="modal" data-bs-target="#orderModal{{$current_wo->number}}">--}}
{{--                                        {{ __('Ordered Parts') }}</button>--}}

{{--                                @endif--}}
{{--                            </div>--}}
                            <div class=" d-flex justify-content-between" style=" height: 40px; width: 450px">
                                @if(count($tdrs))

                                    <a href="{{ route('admin.tdrs.tdrForm', ['id'=> $current_wo->id]) }}"
                                       class="btn btn-outline-warning mb-1 formLink "
                                       target="_blank"
                                       id="#" style=" height: 40px">
                                        <i class="bi bi-file-earmark-excel"> TDR Form</i>
                                    </a>

                                    <a href="{{ route('admin.tdrs.specProcessForm', ['id'=> $current_wo->id]) }}"
                                       class="btn btn-outline-warning  formLink "
                                       target="_blank"
                                       id="#" style=" height: 40px">
                                        <i class="bi bi-file-earmark-excel"> Special Process Form</i>
                                    </a>

                                    <a href="{{ route('admin.tdrs.prlForm', ['id'=> $current_wo->id]) }}"
                                       class="btn btn-outline-warning mb-1 formLink "
                                       target="_blank"
                                       id="#" style=" height: 40px">
                                        <i class="bi bi-file-earmark-excel"> PRL  </i>
                                    </a>
                                @endif
                                </div>

                                @php
    $manual = $current_wo->unit->manuals;
    $hasNdtCsv = false;
    if ($manual) {
        $hasNdtCsv = $manual->getMedia('csv_files')->first(function($media) {
            return $media->getCustomProperty('process_type') === 'ndt';
        });
    }
@endphp

@if($current_wo->instruction_id == 1 && $hasNdtCsv)
    <div class="me-2">
        <a href="{{ route('admin.tdrs.ndtStd', ['workorder_id' => $current_wo->id]) }}"
           class="btn btn-outline-warning" style="height: 40px"
           target="_blank">
            NDT STD
        </a>
    </div>
@endif

                        </div>

                        <!--  Missing Modal -->
                        <div class="modal fade" id="missingModal{{$current_wo->number}}" tabindex="-1"
                             role="dialog" aria-labelledby="missingModalLabel{{$current_wo->number}}" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content bg-gradient" style="width: 1000px">
                                    <div class="modal-header">
                                        <div>
                                            <div class="d-flex justify-content-between" style="width: 600px">
                                                <h4 class="modal-title">{{__('Work order ')}}{{$current_wo->number}}</h4>
                                                <button type="button" class="btn-close pb-2" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>

                                            <div class="d-flex justify-content-between">
                                                <h4 class="modal-title">{{__('Parts Missing ')}}</h4>

                                                <div>
                                                    <a href="{{route('admin.tdrs.inspection',['workorder_id' => $current_wo->id])}}"
                                                       class="btn btn-outline-primary " style="height: 40px" onclick="showLoadingSpinner()">
                                                        {{__('Add Unit Inspection')}}
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="table-wrapper">
                                        <table class="display table table-cm table-hover table-striped align-middle table-bordered">
                                            <thead class="bg-gradient">
                                            <tr>
                                                <th class="text-primary bg-gradient">{{__('IPL')}}<i class="ms-1"></i></th>
                                                <th class="text-primary bg-gradient">{{__('Part Description')}}<i class="ms-1"></i></th>
                                                <th class="text-primary bg-gradient">{{__('Part Number')}}<i class="ms-1"></i></th>
                                                <th class="text-primary bg-gradient">{{__('QTY')}}<i class="ms-1"></i></th>
                                                <th class="text-primary bg-gradient">{{__('Delete')}}<i class="ms-1"></i></th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($missingParts as $part)
                                                <tr>
                                                    <td class="p-3"> {{$part->component->ipl_num ?? ''}} </td>
                                                    <td class="p-3"> {{$part->component->name ?? ''}} </td>
                                                    <td class="p-3"> {{$part->component->part_number ?? ''}} </td>
                                                    <td class="p-3"> {{$part->qty}} </td>
                                                    <td class="p-3">
                                                        <!-- Кнопка удаления -->
                                                        <form action="{{ route('admin.tdrs.destroy', $part->id) }}" method="POST"
                                                              onsubmit="return confirm('Are you sure you want to delete this item?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-danger btn-sm">{{__('Delete')}}</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                </div>
                            </div>
                        </div>
                        <!-- Inspect Modal -->
                        <div class="modal fade" id="inspectModal{{$current_wo->number}}" tabindex="-1"  role="dialog"
                             aria-labelledby="orderModalLabel{{$current_wo->number}}" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content bg-gradient">
                                    <div class="modal-header" >
                                        <div>
                                            <h5 class="modal-title">{{__('Work order ')}}{{$current_wo->number}}</h5>
                                            <h5 class="modal-title">{{__('Inspections  ')}}</h5>
                                        </div>
                                        <button type="button" class="btn-close pb-2" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="table-wrapper">
                                        <table class="display table table-cm table-hover table-striped align-middle table-bordered">
                                            <thead class="">
                                            <tr>
                                                <th class="text-primary text-center">{{__('Teardown Inspection')}}</th>
                                                <th class="text-primary  bg-gradient " >{{__('Delete')}}</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($inspectsUnit as $unit)
                                                <tr>
                                                    <td class="p-3"> {{$unit->conditions->name}} </td>
                                                    <td class="p-3">
                                                        <!-- Кнопка удаления -->
                                                        <form action="{{ route('admin.tdrs.destroy', $unit->id) }}" method="POST"
                                                              onsubmit="return confirm('Are you sure you want to delete this item?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-danger btn-sm">{{__('Delete')}}</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!--  Ordered Modal -->
                        <div class="modal fade" id="orderModal{{$current_wo->number}}" tabindex="-1"
                             role="dialog" aria-labelledby="orderModalLabel{{$current_wo->number}}" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content bg-gradient" style="width: 700px">
                                    <div class="modal-header" style="width: 700px">
                                        <div>
                                            <h4 class="modal-title">{{__('Work order ')}}{{$current_wo->number}}</h4>
                                            <h4 class="modal-title">{{__('Ordered Parts  ')}}</h4>
                                        </div>
                                        <button type="button" class="btn-close pb-2" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    @if(count($ordersPartsNew))
                                        <div class="table-wrapper">
                                            <table class="display table table-cm table-hover table-striped align-middle table-bordered">
                                                <thead class="bg-gradient">
                                                <tr>
                                                    <th class="text-primary  bg-gradient " data-direction="asc">{{__('IPL')}}<i class="  ms-1"></i></th>
                                                    <th class="text-primary  bg-gradient " data-direction="asc">{{__('Part Description') }}<i class="  ms-1"></i></th>
                                                    <th class="text-primary  bg-gradient " style="width: 250px;" data-direction="asc">{{__('Part Number')}}<i class="  ms-1"></i></th>
                                                    <th class="text-primary  bg-gradient " data-direction="asc">{{__('QTY')}}<i class="bi  ms-1"></i></th>
                                                    <th class="text-primary  bg-gradient " >{{__('Conditions')}}<i class="bi  ms-1"></i></th>
                                                    <th class="text-primary  bg-gradient " >{{__('Delete')}}<i class="bi  ms-1"></i></th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @foreach($ordersPartsNew as $part)
                                                    <tr>

                                                        <td class="p-3" style="width: 150px"> {{$part->orderComponent->ipl_num ?? ''}} </td>

                                                        <td class="p-3" style="width: 250px"> {{$part->orderComponent->name ?? ''}} </td>
                                                        <td class="p-3" style="width: 250px;"> {{$part->orderComponent->part_number ?? ''}} </td>
                                                        <td class="p-3"> {{$part->qty}} </td>
                                                        <td class="p-3"> {{$part->codes->name}} </td>
                                                        <td class="p-3">
                                                            <!-- Кнопка удаления -->
                                                            <form action="{{ route('admin.tdrs.destroy', $part->id) }}" method="POST"
                                                                  onsubmit="return confirm('Are you sure you want to delete this item?');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-danger btn-sm">{{__('Delete')}}</button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                       <h5 class="text-center mt-3 mb-3 text-primary">{{__('No Ordered Parts')}}</h5>
                                    @endif


                                </div>
                            </div>
                        </div>
                        <!--  Forms Modal -->
                        <div class="modal fade" id="formsModal{{$current_wo->number}}" tabindex="-1" role="dialog"
                             aria-labelledby="formsModallabel{{$current_wo->number}}" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content bg-gradient">
                                    <div class="modal-header">
                                        <div>
                                            <h4 class="modal-title">{{__('Work order ')}}{{$current_wo->number}}</h4>
                                            <h4 class="modal-title">{{__('Forms  ')}}</h4>
                                        </div>
                                        <button type="button" class="btn-close pb-2" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div> <! --- Header end --- ->

            <! --- Body --- ->

            {{--        @if(count($tdrs))--}}

            <div class="">
{{--                WorkOrder ID :{{$current_wo->id}}. Count TDR Records: {{count($tdrs)}}--}}
                <div class="d-flex justify-content-center">

                    <div class="me-3" style="width: 450px"> <!-  Inspection Unit  ->
                        <div class="table-wrapper me3 p-2">
                            <table id="tdr_inspect_Table" class="display table table-sm
                                        table-hover table-striped align-middle table-bordered bg-gradient">
                                <thead>
                                <tr>
                                    <th class=" text-primary text-center  " style="width: 400px;">{{__('Teardown
                                    Inspection')
                                    }}</th>
                                    <th class=" text-primary text-center " style="width: 150px;">

{{--                                        <a href="{{ route('admin.tdrs.unit-inspection', ['workorder_id' => $current_wo->id,--}}
{{--                                        'type' => 'unit']) }}"--}}
{{--                                           class="btn btn-outline-info btn-sm" style="height: 32px"  >--}}
{{--                                            {{ __('Add_D') }}--}}
{{--                                        </a>--}}
                                        <a href="{{ route('admin.tdrs.inspection.unit', ['workorder_id' => $current_wo->id]) }}"
                                           class="btn btn-outline-info btn-sm" style="height: 32px"  >
                                            {{ __('Add') }}
                                        </a>

                                    </th>

                                </tr>
                                </thead>
                                <tbody>



                                @foreach($tdrs as $tdr)
                                    @if($tdr->use_tdr == true and $tdr->use_process_forms != true)
                                            <tr>
                                                <td
                                                    class="text-center fs-7">
                                                    @foreach($conditions as $condition)
                                                        @if($condition->id == $tdr->conditions_id)
                                                            {{$condition ->name}}
                                                        @endif
                                                    @endforeach

                                                    @foreach($components as $component)
                                                        @if($component->id == $tdr->component_id)
                                                               <fs-6 class="" style="color: #5897fb">(scrap)</fs-6>

                                                            {{$component -> name}}
                                                            @if ($tdr->qty == 1)
                                                                ({{$component -> ipl_num}})
                                                            @else
                                                                 ({{$component -> ipl_num}}, {{$tdr->qty}} pcs)
                                                            @endif

                                                        @endif
                                                    @endforeach
                                                </td>
                                                <td class="p-2 text-center">

                                                    @foreach($inspectsUnit as $unit)<!-- inspection unit delete -->

                                                        @if($unit->id == $tdr->id)
                                                            <form action="{{ route('admin.tdrs.destroy', $unit->id) }}" method="POST"
                                                                  onsubmit="return confirm('Are you sure you want to delete this item?');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-outline-danger btn-sm ">
                                                                    {{--                                                            {__('Delete')}}--}}
                                                                    <i class="bi bi-trash"></i>
                                                                </button>
                                                            </form>

                                                       @endif
                                                    @endforeach

                                                        @if($tdr->conditions->name == 'PARTS MISSING UPON ARRIVAL AS INDICATED ON PARTS LIST')
                                                            <button class="btn btn-outline-info btn-sm" style="height: 32px"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#missingModal{{$current_wo->number}}">
                                                                {{ __('Missing Part') }}</button>
                                                        @else
                                                            @if($tdr->necessaries_id == $necessary->id)
                                                                <button class="btn btn-outline-info btn-sm" style="height: 32px"
                                                                        href="#"
                                                                           data-bs-toggle="modal" data-bs-target="#orderModal{{$current_wo->number}}">
                                                                    {{ __('Ordered Parts') }}</button>
                                                            @endif
                                                        @endif


{{--{{$tdr->necessaries}}--}}

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
                            <table id="tdr_process_Table" class="display table table-sm table-hover table-striped align-middle table-bordered">
                                <thead class="bg-gradient">
                                <tr>
                                    <th class="text-center text-primary sortable">{{__('IPL Number')}} </th>
                                    <th class=" text-center  text-primary sortable">{{__('Part Description')}} </th>
                                    <th class=" text-center text-primary sortable ">{{__('Part number')}} </th>
                                    <th class=" text-center   text-primary sortable">{{__('Serial number')}}</th>
                                    <th class=" text-center  text-primary " style="width: 200px">{{__('Necessary')}}</th>
                                    <th class=" text-center  text-primary " style="width: 120px">{{__('Code')}}</th>
                                    <th class=" text-primary text-center">
                                        Action
{{--                                        <a href="{{ route('admin.tdrs.component-inspection', ['workorder_id' => $current_wo->id,--}}
{{--                                        'type' => 'component']) }}"--}}
{{--                                           class="btn btn-outline-info btn-sm" style="height: 32px"  >--}}
{{--                                            {{ __('Add_D') }}--}}
{{--                                        </a>--}}
                                        <a href="{{ route('admin.tdrs.inspection.component', ['workorder_id' => $current_wo->id])
                                         }}"
                                           class="btn btn-outline-info btn-sm" style="height: 32px"  >
                                            {{ __('Add') }}
                                        </a>
                                    </th>
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

                                                <td class="d-flex justify-content-center" style="width: 100px">
                                                    <a href="{{ route('admin.tdrs.edit',['tdr' => $tdr->id]) }}"
                                                       class="btn btn-outline-primary btn-sm me-1">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </a>
                                                    <form action="{{ route('admin.tdrs.destroy', ['tdr' => $tdr->id]) }}" method="POST"
                                                          onsubmit="return confirm('Are you sure you want to delete this item?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-outline-danger btn-sm">
                                                            <i class="bi bi-trash"></i>
{{--                                                            {{__('Delete')}}--}}
                                                        </button>
                                                    </form>

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
                headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'
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
