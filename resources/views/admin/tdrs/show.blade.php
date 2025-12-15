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
        .fs-8 {
            font-size: 0.8rem;
        }
        .fs-7 {
            font-size: 0.7rem;
        }

        .fs-75 {
            font-size: 0.75rem;
        }


    </style>

    @if($current_wo->unit->manuals->builder )

        <div class="card bg-gradient">
            <div class="card-header  m-1 shadow">

                <div class="d-flex  text-center">
                    <div style="width: 150px;">
                        <h5 class="text-primary  ps-1">{{__('Work Order')}}
                            <a class="text-success-emphasis " href="#" data-bs-toggle="modal"
                               data-bs-target=#infoModal{{$current_wo->number}}>{{$current_wo->number}}
                            </a>
                        </h5>
                    </div>


                    <div class="ps-2 d-flex" >

                        <div class=" ms-4">
                            <a href="{{ route('mains.show', $current_wo->id) }}" class="btn
                                            btn-outline-success " title="{{ __('WO Tasks') }}"
                               onclick="showLoadingSpinner()">
                                <i class="bi bi-list-task " style="font-size: 28px;"></i>

                            </a>
                        </div>
                        <div class="me-2 position-relative">
                            <button class="btn  btn-outline-warning ms-2 open-pdf-modal text-center"
                                    title="{{ __('PDF Library') }}"
                                    style="height: 55px;width: 55px"
                                    data-id="{{ $current_wo->id }}"
                                    data-number="{{ $current_wo->number }}" >
                                <i class="bi bi-file-earmark-pdf" style="font-size: 28px; "></i>
                                {{--                                {{__('PDF Library')}}--}}
                            </button>
                            {{-- Badge with count of uploaded PDF files --}}
                            <span id="pdfCountBadge"
                                  class="badge bg-warning rounded-pill position-absolute d-none"
                                  style="top: -5px; right: -5px; min-width: 22px; height: 22px;
                                         display: flex; align-items: center; justify-content: center;color: black;
                                         font-size: 0.7rem; padding: 0 5px;">
                            </span>
                        </div>


                        @if(count($processParts))
                            <div class="me-2">
                                <a href="{{route('tdrs.processes',['workorder_id' => $current_wo->id])}}"
                                   class="btn fs-8 btn-outline-primary " style="height: 55px;width: 100px"
                                   onclick="showLoadingSpinner()">
                                    {{__('Component Processes')}}
                                </a>
                            </div>
                        @endif
                        <div class="me-2" style="position: relative;">
                            @php
                                $extraProcessesCount = \App\Models\ExtraProcess::where('workorder_id', $current_wo->id)
                                    ->distinct('component_id')
                                    ->count('component_id');
                            @endphp
                            <a href="{{route('extra_processes.show_all',['id'=>$current_wo->id])}}"
                               class="btn fs-8 btn-outline-primary " style="height: 55px;width: 140px" onclick="showLoadingSpinner
                                       ()">
                                {{__('Extra Component Processes')}}
                            </a>
                            @if($extraProcessesCount > 0)
                                <span class="badge bg-success rounded-pill" style="position: absolute; top: -5px; right: -5px;
                                min-width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; padding: 0 5px;">
{{--                                    {{ $extraProcessesCount }}--}}
{{--                                    {{__(' * ')}}--}}
                                    <i class="bi bi-brightness-high-fill"></i>
                                </span>
                            @endif
                        </div>
                        <div>
                            @foreach($manuals as $manual)
                                @if($manual->id == $manual_id)
                                    @foreach($planes as $plane)
                                        @if($plane->id == $manual->planes_id)
                                            @if(!str_contains($plane->type, 'ATR'))
                                                <a href="{{route('log_card.show',['id' => $current_wo->id])}}"
                                                      class="btn  fs-8 btn-outline-primary " style="min-height: 55px;width: 55px"
                                                      onclick="showLoadingSpinner ()">
                                                    {{__('Log Card')}}
                                                </a>
                                            @endif
                                        @endif
                                    @endforeach
                                @endif
                            @endforeach

                        </div>
                        <div>
                            <a href="{{route('wo_bushings.show',['wo_bushing' => $current_wo->id])}}"
                               class="btn  fs-8 btn-outline-primary ms-2" style="min-height: 55px;width: 85px"
                               onclick="showLoadingSpinner
                                   ()">
                                {{__('Bushing Processes')}}
                            </a>
                        </div>
                        <div>
                            <a href="{{route('rm_reports.show',['rm_report' => $current_wo->id])}}"
                               class="btn  fs-8 btn-outline-primary ms-2 " style="height: 55px;width: 150px"
                               onclick="showLoadingSpinner
                                   ()">
                                 {{__('Repair & Modification Record')}}
                            </a>
                        </div>

                        <div class="ms-5 d-flex">
                            @if($current_wo->instruction_id == 1 )
                                <div class="me-1 ">
                                    <a href="{{ route('ndt-cad-csv.index', $current_wo->id) }}"
                                       class="btn fs-8 btn-outline-success" style="min-height: 55px; width: 110px">
                                        STD Processes
                                    </a>
                                </div>
                            @endif
                            <div class="ms-2">
                                <a href="{{ route('transfers.show', $current_wo->id) }}"
                                   class="btn fs-8 btn-outline-info"
                                   style="min-height: 55px; width: 110px">
                                    Transfers
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="ms-3">
                        <div class="d-flex ">


                            @php
                                $manual = null;
                                $hasNdtComponents = false;
                                $hasCadComponents = false;
                                $hasStressComponents = false;
                                $hasPaintComponents = false;

                                // Проверяем наличие NDT компонентов в таблице ndt_cad_csv
                                if ($current_wo && $current_wo->ndtCadCsv) {
                                    $ndtComponents = $current_wo->ndtCadCsv->ndt_components;
                                    $hasNdtComponents = !empty($ndtComponents) && is_array($ndtComponents) && count($ndtComponents) > 0;

                                    $cadComponents = $current_wo->ndtCadCsv->cad_components;
                                    $hasCadComponents = !empty($cadComponents) && is_array($cadComponents) && count($cadComponents) > 0;

                                    $stressComponents = $current_wo->ndtCadCsv->stress_components;
                                    $hasStressComponents = !empty($stressComponents) && is_array($stressComponents) && count
                                    ($stressComponents) > 0;

                                    $paintComponents = $current_wo->ndtCadCsv->paint_components;
                                    $hasPaintComponents = !empty($paintComponents) && is_array($paintComponents) && count
                                    ($paintComponents) > 0;

                                }

                                // Оставляем старую логику для совместимости (если нужно)
                                $hasNdtCsv = false;
                                $hasCadCsv = false;
                                $hasStressCsv = false;
                                $hasPaintCsv = false;

                                if ($current_wo && $current_wo->unit && $current_wo->unit->manuals) {
                                    $manual = $current_wo->unit->manuals;
                                    try {
                                        $csv_media = $manual->getMedia('csv_files')->first(function($media) {
                                            return $media->getCustomProperty('process_type') === 'ndt';
                                        });
                                        $hasNdtCsv = $csv_media !== null;
                                    } catch (\Exception $e) {
                                        \Log::error('Error checking NDT CSV:', [
                                            'message' => $e->getMessage(),
                                            'workorder_id' => $current_wo->id ?? null,
                                            'unit_id' => $current_wo->unit->id ?? null,
                                            'manual_id' => $current_wo->unit->manual_id ?? null
                                        ]);
                                    }

                                    try {
                                        $cad_media = $manual->getMedia('csv_files')->first(function($media) {
                                            return $media->getCustomProperty('process_type') === 'cad';
                                        });
                                        $hasCadCsv = $cad_media !== null;
                                    } catch (\Exception $e) {
                                        \Log::error('Error checking CAD CSV:', [
                                            'message' => $e->getMessage(),
                                            'workorder_id' => $current_wo->id ?? null,
                                            'unit_id' => $current_wo->unit->id ?? null,
                                            'manual_id' => $current_wo->unit->manual_id ?? null
                                        ]);
                                    }
                                    try {
                                        $stress_media = $manual->getMedia('csv_files')->first(function($media) {
                                            return $media->getCustomProperty('process_type') === 'stress';
                                        });
                                        $hasStressCsv = $stress_media !== null;
                                    } catch (\Exception $e) {
                                        \Log::error('Error checking Stress CSV:', [
                                            'message' => $e->getMessage(),
                                            'workorder_id' => $current_wo->id ?? null,
                                            'unit_id' => $current_wo->unit->id ?? null,
                                            'manual_id' => $current_wo->unit->manual_id ?? null
                                        ]);
                                    }
                                    try {
                                        $paint_media = $manual->getMedia('csv_files')->first(function($media) {
                                            return $media->getCustomProperty('process_type') === 'paint';
                                        });
                                        $hasPaintCsv = $paint_media !== null;
                                    } catch (\Exception $e) {
                                        \Log::error('Error checking Paint CSV:', [
                                            'message' => $e->getMessage(),
                                            'workorder_id' => $current_wo->id ?? null,
                                            'unit_id' => $current_wo->unit->id ?? null,
                                            'manual_id' => $current_wo->unit->manual_id ?? null
                                        ]);
                                    }
                                }
                            @endphp




                        </div>

                        <!--  WO INfo -->
                        <div class="modal fade" id="infoModal{{$current_wo->number}}" tabindex="-1"
                             role="dialog" aria-labelledby="infoModalLabel{{$current_wo->number}}" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content bg-gradient" style="width: 800px">
                                    <div class="modal-header">
                                        <div>
                                            <h4 class="modal-title">{{__('Work order ')}}{{$current_wo->number}}</h4>
                                        </div>
                                        <button type="button" class="btn-close pb-2" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="d-flex">
                                            @php
                                                $manual = $current_wo->unit->manual; // Вызываем метод в единственном числе
                                            @endphp
                                            @if ($current_wo->unit && $current_wo->unit->manuals)
                                                <div>
                                                    <div class="m-2">
                                                        <a href="{{ $current_wo->unit->manuals->getFirstMediaBigUrl('manuals') }}" data-fancybox="gallery">
                                                            <img class="" src="{{ $current_wo->unit->manuals->getFirstMediaThumbnailUrl('manuals')}}"
                                                                 width="150" height="150" alt="Image"/>
                                                        </a>
                                                    </div>
                                                </div>
                                            @else
                                                {{-- TODO: не находит картинку --}}
                                                <p>Manual1 not found</p>
                                            @endif
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

{{--                                                <div>--}}
{{--                                                    <a href="{{route('tdrs.inspection',['workorder_id' => $current_wo->id])}}"--}}
{{--                                                       class="btn btn-outline-primary " style="height: 40px" onclick="showLoadingSpinner()">--}}
{{--                                                        {{__('Add Unit Inspection')}}--}}
{{--                                                    </a>--}}
{{--                                                </div>--}}
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
                                                        <form action="{{ route('tdrs.destroy', $part->id) }}" method="POST"
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
                        <div class="modal fade" id="inspectModal{{$current_wo->number}}" tabindex="-1" role="dialog"
                             aria-labelledby="orderModalLabel{{$current_wo->number}}" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content bg-gradient">
                                    <div class="modal-header">
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
                                                <th class="text-primary  bg-gradient ">{{__('Delete')}}</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($inspectsUnit as $unit)
                                                <tr>
                                                    <td class="p-3"> {{$unit->conditions->name}} </td>
                                                    <td class="p-3">
                                                        <!-- Кнопка удаления -->
                                                        <form action="{{ route('tdrs.destroy', $unit->id) }}" method="POST"
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
                                                    <th class="text-primary  bg-gradient ">{{__('Conditions')}}<i class="bi  ms-1"></i></th>
                                                    <th class="text-primary  bg-gradient ">{{__('Delete')}}<i class="bi  ms-1"></i></th>
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
                                                            <form action="{{ route('tdrs.destroy', $part->id) }}" method="POST"
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
            </div>
            <! --- Header end --- ->

            <! --- Body --- ->


            <div class="mb-1 mt-2 d-flex " style="margin-left: 60px">


                        <div class=" d-flex " style=" ; width: 380px">

                            @if(count($tdrs))
                                <div class="d-flex" style="width: 200px">
                                    <x-paper-button
                                        text="WO Process Sheet"
                                        href="{{ route('tdrs.woProcessForm', ['id'=> $current_wo->id]) }}"
                                        target="_blank"
                                        color="outline-info"
                                    />
                                </div>


                                <x-paper-button
                                    text="TDR Form"
                                    href="{{ route('tdrs.tdrForm', ['id'=> $current_wo->id]) }}"
                                    target="_blank"
                                />
                                @if(count($processParts)==0)
                                    <x-paper-button
                                        text="SP Form"
                                        href="{{ route('tdrs.specProcessFormEmp', ['id'=> $current_wo->id]) }}"
                                        target="_blank"
                                    />

                                @else
                                    <x-paper-button
                                        text="SP Form"
                                        href="{{ route('tdrs.specProcessForm', ['id'=> $current_wo->id]) }}"
                                        target="_blank"
                                    />

                                @endif
                                <x-paper-button
                                    text="R&M Form"
                                    href="{{ route('rm_reports.rmRecordForm', ['id'=> $current_wo->id]) }}"
                                    target="_blank"
                                />

                                @if (count($prl_parts) > 0)
                                    <div class="position-relative d-inline-block">
                                        <x-paper-button
                                            text="PRL"
                                            href="{{ route('tdrs.prlForm', ['id' => $current_wo->id]) }}"
                                            target="_blank"
                                        />

                                        <span class="badge bg-success rounded-pill"
                                              style="position: absolute; top: -5px; left: 2px; min-width: 20px; height: 20px;
                                              display: flex; align-items: center; justify-content: center; font-size: 0.7rem; padding: 0 5px;">
                                         {{ count($prl_parts) }}
                                        </span>
                                    </div>
                                @endif

                            @endif

                        </div>

                        <! --- STD Processes --- ->
                        <div class="d-flex ms-5">
                            @if($current_wo->instruction_id == 1 && $hasNdtComponents)
                                <div class="me-1">
                                    <x-paper-button
                                        text="NDT STD"
                                        href="{{ route('tdrs.ndtStd', ['workorder_id' => $current_wo->id]) }}"
                                        target="_blank"
                                        color="outline-primary"
                                    />
                                </div>
                            @endif

                            @if($current_wo->instruction_id == 1 && $hasCadComponents)
                                <div class="me-1 ">
                                    <x-paper-button
                                        text="CAD STD"
                                        href="{{ route('tdrs.cadStd', ['workorder_id' => $current_wo->id]) }}"
                                        target="_blank"
                                        color="outline-primary"
                                    />

                                </div>
                            @endif

                            @if($current_wo->instruction_id == 1 && $hasStressComponents)
                                <div class="me-1 ">
                                    <x-paper-button
                                        text="Stress STD"
                                        href="{{ route('tdrs.stressStd', ['workorder_id' => $current_wo->id]) }}"
                                        target="_blank"
                                        color="outline-primary"
                                    />
                                </div>
                            @endif

                            @if($hasPaintComponents)
                                <div class="me-1 ">
                                    <x-paper-button
                                        text="Paint STD"
                                        href="{{ route('tdrs.paintStd', ['workorder_id' => $current_wo->id]) }}"
                                        target="_blank"
                                        color="outline-primary"
                                    />

                                </div>
                            @endif
                        </div>

                <div class="d-flex ms-5">
                    @if($log_card)

                        <x-paper-button
                            text="Log Card"
                            href="{{ route('log_card.logCardForm', ['id'=> $current_wo->id]) }}"
                            target="_blank"
                            color="outline-primary"
                        />
                    @endif
                     @if($woBushing)

                            <x-paper-button
                                text="Bushing SP Form"

                                href="{{ route('wo_bushings.specProcessForm', $woBushing->id) }}}"
                                target="_blank"
                                color="outline-primary"
                            />
                     @endif

                </div>


                </div>



                <div class="d-flex justify-content-center">

                    <div class="me-3" style="width: 450px"> <!- Inspection Unit ->
                        <div class="table-wrapper me3 p-2">
                            <table id="tdr_inspect_Table" class="display table table-sm
                                        table-hover table-striped align-middle table-bordered bg-gradient">
                                <thead>
                                <tr>
                                    <th class=" text-primary text-center  " style="width: 300px;">{{__('Teardown
                                    Inspection')
                                    }}</th>
                                    <th class=" text-primary text-center " style="width: 150px;">
                                        <a href="{{ route('tdrs.inspection.unit', ['workorder_id' => $current_wo->id]) }}"
                                           class="btn btn-outline-info btn-sm" style="height: 32px">
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
                                                class="text-center fs-8">


                                                @foreach($conditions as $condition)
                                                    @if($condition->id == $tdr->conditions_id  )
                                                        {{$condition ->name}}

                                                    @endif

                                                @endforeach

                                                @if($tdr->component_id)
                                                    <fs-8 class="" style="color: #5897fb">(scrap)</fs-8>
                                                    {{ $tdr->component->name }}
                                                    @if ($tdr->qty == 1)
                                                        ({{ $tdr->component->ipl_num }})
                                                    @else
                                                         ({{ $tdr->component->ipl_num }}, {{$tdr->qty}} pcs)
                                                    @endif
                                                @endif
                                                    @if($tdr->description)
                                                        ({{$tdr->description}})
                                                    @endif
                                            </td>
                                            <td class="p-2 text-center">

                                                @foreach($inspectsUnit as $unit)<!-- inspection unit delete -->

                                                @if($unit->id == $tdr->id)
                                                    <form action="{{ route('tdrs.destroy', $unit->id) }}" method="POST"
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
                                                        <button class="btn btn-outline-info btn-sm" style="min-height: 32px"
                                                                href="#"
                                                                data-bs-toggle="modal" data-bs-target="#orderModal{{$current_wo->number}}">
                                                            {{ __('Ordered Parts') }}</button>
                                                    @endif
                                                @endif

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
                                    <th class=" text-center  text-primary " style="width: 60px">{{__('EC')}}</th>
                                    <th class=" text-primary text-center" style="width: 150px"> {{__('Action')}}
                                        <a href="{{ route('tdrs.inspection.component', ['workorder_id' => $current_wo->id])}}"
                                           class="btn btn-outline-info btn-sm ms-3" style="height: 32px">
                                            {{ __('Add') }}
                                        </a>
                                    </th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($tdrs as $tdr)
                                    @if($tdr->use_tdr == true and $tdr->use_process_forms == true)
                                        <tr>
                                            <td class="text-center"> <!-- IPL Number -->
                                                {{ $tdr->component->ipl_num ?? '' }}
                                            </td>
                                            <td class="text-center"><!--  Part Description -->
                                                {{ $tdr->component->name ?? '' }}
                                            </td>
                                            <td class="text-center"><!--  Part Number -->
                                                {{ $tdr->component->part_number ?? '' }}
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
                                            <td class="text-center" style="width: 60px"> <!--  EC -->
                                                @if(empty($tdr_proc))
                                                    No
                                                @else
                                                    @php
                                                        $found = false;
                                                    @endphp
                                                    @foreach($tdr_proc as $tdr_ec)
                                                        @if($tdr_ec->tdrs_id == $tdr->id)
                                                            @php
                                                                $found = true;
                                                            @endphp
                                                            Yes
                                                            @break
                                                        @endif
                                                    @endforeach
                                                    @if(!$found)
                                                        No
                                                    @endif
                                                @endif
                                            </td>

                                            <td class="d-flex justify-content-center " style="width: 150px; align-content: center">


                                                <a href="{{ route('tdr-processes.processes',['tdrId'=>$tdr->id])}}"
                                                   class="btn btn-outline-primary btn-sm me-2">
                                                    <i class="bi bi-bar-chart-steps" title="Component Processes"></i>
                                                </a>
                                                <a href="{{ route('tdrs.edit',['id' => $tdr->id]) }}"
                                                   class="btn btn-outline-primary btn-sm me-2">
                                                    <i class="bi bi-pencil-square" title="Component Inspection Edit"></i>
                                                </a>
                                                <form action="{{ route('tdrs.destroy', ['tdr' => $tdr->id]) }}" method="POST"
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
                    <img class="" src="{{ $current_wo->unit->manuals->getFirstMediaBigUrl('manuals') }}"
                         width="200" alt="Image"/>

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

            fetch('{{ route('workorders.inspection', $current_wo->id) }}', {
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

    <!-- PDF Library Modal -->
    <div class="modal fade" id="pdfModal" tabindex="-1" aria-labelledby="pdfModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content" style="background-color: #343A40">
                <div class="modal-header">
                    <h5 class="modal-title" id="pdfModalLabel">PDF Library - Workorder W<span
                            id="pdfModalWorkorderNumber"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Upload Section -->
                    <div class="mb-4">
                        <div class="card bg-dark border-secondary" >
                            <div class="card-body d-flex" >
                                <h6 class="text-primary mb-3 me-4">Upload PDF Files</h6>
                                <form id="pdfUploadForm" enctype="multipart/form-data">
                                    <div class="ms-3">
                                        <div class="d-flex">
                                            <label for="pdfDocumentName" class="form-label  me-2" >
                                                Document Name</label>
                                            <input type="text" class="form-control" id="pdfDocumentName" name="document_name"
                                                   placeholder="Enter document name (optional)" style="width: 400px"
                                                   maxlength="255">
{{--                                            <small class="text-muted ms-2">Optional: Enter a name for the document</small>--}}
                                        </div>
                                        <div class="input-group mt-2 ms-4 d-flex " style="height: 40px">
                                            <input type="file" class="form-control" id="pdfFileInput" name="pdf" accept=".pdf"
                                                   style="width: 385px"
                                                      required>
                                            <button class="btn btn-primary" type="submit" id="uploadPdfBtn">
                                                <i class="bi bi-upload"></i> Upload
                                            </button>
                                            <small class="text-muted ms-3">Max size: 10MB. Upload one file at a time.</small>
                                        </div>

                                    </div>


                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- PDF List -->
                    <div id="pdfListContainer" class="row g-3">
                        <!-- PDFs will be loaded here -->
                    </div>

                    <!-- PDF Viewer Modal -->
                    <div class="modal fade" id="pdfViewerModal" tabindex="-1" aria-labelledby="pdfViewerModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-xl">
                            <div class="modal-content bg-dark">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="pdfViewerModalLabel">PDF Viewer</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body p-0" style="min-height: 600px; position: relative;">
                                    <iframe id="pdfViewerFrame" src="" style="width: 100%; height: 600px; border: none; position: relative;"></iframe>
                                </div>
                                <div class="modal-footer">
                                    <a id="pdfDownloadLink" href="#" class="btn btn-primary" download>
                                        <i class="bi bi-download"></i> Download
                                    </a>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirm Delete PDF Modal -->
    <div class="modal fade" id="confirmDeletePdfModal" tabindex="-1" aria-labelledby="confirmDeletePdfLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmDeletePdfLabel">Confirm Deletion</h5>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this PDF file?
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button id="confirmPdfDeleteBtn" class="btn btn-danger">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1055">
        <div id="pdfDeletedToast" class="toast bg-success text-white" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-body">
                PDF deleted successfully.
            </div>
        </div>
    </div>

    <script>
        // ==================== PDF Library ====================

        // Обновление бейджа с количеством PDF
        async function updatePdfCountBadge(workorderId) {
            const pdfBadge = document.getElementById('pdfCountBadge');
            if (!pdfBadge) return;

            try {
                const response = await fetch(`/workorders/${workorderId}/pdfs`);
                if (!response.ok) return;
                const data = await response.json();

                const pdfCount = Array.isArray(data.pdfs) ? data.pdfs.length : 0;
                if (pdfCount > 0) {
                    pdfBadge.textContent = pdfCount;
                    pdfBadge.classList.remove('d-none');
                } else {
                    pdfBadge.textContent = '';
                    pdfBadge.classList.add('d-none');
                }
            } catch (e) {
                console.error('Failed to load PDF count', e);
            }
        }

        // Открытие модального окна PDF библиотеки
        document.querySelectorAll('.open-pdf-modal').forEach(button => {
            button.addEventListener('click', async function () {
                const workorderId = this.dataset.id;
                const workorderNumber = this.dataset.number;
                window.currentPdfWorkorderId = workorderId;
                window.currentPdfWorkorderNumber = workorderNumber;

                document.getElementById('pdfModalWorkorderNumber').textContent = workorderNumber;

                await loadPdfLibrary(workorderId);
                new bootstrap.Modal(document.getElementById('pdfModal')).show();
            });
        });

        // Инициализация бейджа при загрузке страницы
        document.addEventListener('DOMContentLoaded', function () {
            updatePdfCountBadge({{ $current_wo->id }});
        });

        // Очистка при закрытии основного модального окна PDF Library
        const pdfModal = document.getElementById('pdfModal');
        if (pdfModal) {
            pdfModal.addEventListener('hidden.bs.modal', function () {
                // Очищаем iframe просмотра PDF, если он был открыт
                const iframe = document.getElementById('pdfViewerFrame');
                if (iframe && iframe.src && iframe.src !== 'about:blank') {
                    iframe.src = 'about:blank';
                }
                // Очищаем ссылку на скачивание
                const downloadLink = document.getElementById('pdfDownloadLink');
                if (downloadLink) {
                    downloadLink.href = '#';
                    downloadLink.download = '';
                }
            });
        }

        // Загрузка списка PDF файлов
        async function loadPdfLibrary(workorderId) {
            const container = document.getElementById('pdfListContainer');
            if (typeof showLoadingSpinner === 'function') showLoadingSpinner();

            try {
                const response = await fetch(`/workorders/${workorderId}/pdfs`);
                if (!response.ok) throw new Error('Response not ok');
                const data = await response.json();

                // Update badge with count of uploaded PDFs
                const pdfBadge = document.getElementById('pdfCountBadge');
                if (pdfBadge) {
                    const pdfCount = Array.isArray(data.pdfs) ? data.pdfs.length : 0;
                    if (pdfCount > 0) {
                        pdfBadge.textContent = pdfCount;
                        pdfBadge.classList.remove('d-none');
                    } else {
                        pdfBadge.textContent = '';
                        pdfBadge.classList.add('d-none');
                    }
                }

                if (data.pdfs.length === 0) {
                    container.innerHTML = '<div class="col-12"><p class="text-muted text-center">No PDF files uploaded yet.</p></div>';
                    if (typeof hideLoadingSpinner === 'function') hideLoadingSpinner();
                    return;
                }

                let html = '';
                data.pdfs.forEach(pdf => {
                    const fileSize = formatFileSize(pdf.size);
                    const uploadDate = new Date(pdf.created_at).toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });

                    const displayName = pdf.name && pdf.name !== pdf.file_name ? pdf.name : pdf.file_name;
                    const displayTitle = pdf.name && pdf.name !== pdf.file_name ? `${pdf.name} (${pdf.file_name})` : pdf.file_name;

                    html += `
                        <div class="col-md-6 col-lg-4 col-xl-3">
                            <div class="card bg-secondary border-primary pdf-card" data-pdf-id="${pdf.id}">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="card-title text-truncate mb-0" style="max-width: 150px;"
                                        title="${displayTitle}">
                                            <i class="bi bi-file-earmark-pdf text-danger"></i> ${displayName}
                                        </h6>
                                        <button class="btn btn-sm btn-danger delete-pdf-btn" data-id="${pdf.id}" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                    <p class="text-muted small mb-2">
                                        <i class="bi bi-file-text"></i> ${fileSize}<br>
                                        <i class="bi bi-calendar"></i> ${uploadDate}
                                    </p>
                                    <div class="d-grid gap-2">
                                        <button class="btn btn-sm btn-primary view-pdf-btn"
                                                data-url="${pdf.url}"
                                                data-download="${pdf.download_url}"
                                                data-name="${displayName}">
                                            <i class="bi bi-eye"></i> View PDF
                                        </button>
                                        <a href="${pdf.download_url}" class="btn btn-sm btn-outline-success" download>
                                            <i class="bi bi-download"></i> Download
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });

                container.innerHTML = html;
                bindPdfButtons();
            } catch (e) {
                console.error('Load PDF error', e);
                container.innerHTML = '<div class="col-12"><div class="alert alert-danger">Failed to load PDF files</div></div>';
            } finally {
                if (typeof hideLoadingSpinner === 'function') hideLoadingSpinner();
            }
        }

        // Форматирование размера файла
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }

        // Привязка обработчиков кнопок PDF
        function bindPdfButtons() {
            // Кнопки просмотра
            document.querySelectorAll('.view-pdf-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    const pdfUrl = this.dataset.url;
                    const downloadUrl = this.dataset.download;
                    const pdfName = this.dataset.name;

                    const iframe = document.getElementById('pdfViewerFrame');
                    iframe.src = pdfUrl;
                    document.getElementById('pdfDownloadLink').href = downloadUrl;
                    document.getElementById('pdfDownloadLink').download = pdfName;
                    document.getElementById('pdfViewerModalLabel').textContent = pdfName;

                    new bootstrap.Modal(document.getElementById('pdfViewerModal')).show();
                });
            });

            // Очистка iframe при закрытии модального окна просмотра PDF
            const pdfViewerModal = document.getElementById('pdfViewerModal');
            if (pdfViewerModal) {
                pdfViewerModal.addEventListener('hidden.bs.modal', function () {
                    const iframe = document.getElementById('pdfViewerFrame');
                    if (iframe) {
                        iframe.src = 'about:blank';
                    }
                    const downloadLink = document.getElementById('pdfDownloadLink');
                    if (downloadLink) {
                        downloadLink.href = '#';
                        downloadLink.download = '';
                    }
                });
            }

            // Кнопки удаления
            document.querySelectorAll('.delete-pdf-btn').forEach(btn => {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();

                    const pdfId = this.dataset.id;
                    const pdfCard = this.closest('.pdf-card');

                    window.pendingPdfDelete = {pdfId, pdfCard};
                    new bootstrap.Modal(document.getElementById('confirmDeletePdfModal')).show();
                });
            });
        }

        // Загрузка PDF файлов
        document.getElementById('pdfUploadForm').addEventListener('submit', async function (e) {
            e.preventDefault();

            const workorderId = window.currentPdfWorkorderId;
            if (!workorderId) {
                alert('Workorder ID missing');
                return;
            }

            const fileInput = document.getElementById('pdfFileInput');
            if (!fileInput.files.length) {
                alert('Please select a PDF file');
                return;
            }

            const documentName = document.getElementById('pdfDocumentName').value.trim();

            const formData = new FormData();
            formData.append('pdf', fileInput.files[0]);
            if (documentName) {
                formData.append('document_name', documentName);
            }

            if (typeof showLoadingSpinner === 'function') showLoadingSpinner();
            const uploadBtn = document.getElementById('uploadPdfBtn');
            uploadBtn.disabled = true;

            try {
                const response = await fetch(`/workorders/pdf/${workorderId}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: formData
                });

                if (!response.ok) {
                    const error = await response.json();
                    throw new Error(error.message || 'Upload failed');
                }

                const data = await response.json();

                // Перезагружаем список PDF
                await loadPdfLibrary(workorderId);

                // Очищаем форму
                fileInput.value = '';
                document.getElementById('pdfDocumentName').value = '';

                // Показываем уведомление об успехе
                const toast = new bootstrap.Toast(document.getElementById('pdfDeletedToast'));
                document.querySelector('#pdfDeletedToast .toast-body').textContent =
                    `PDF file uploaded successfully.`;
                toast.show();
            } catch (err) {
                console.error('Upload error:', err);
                alert('Upload failed: ' + err.message);
            } finally {
                if (typeof hideLoadingSpinner === 'function') hideLoadingSpinner();
                uploadBtn.disabled = false;
            }
        });

        // Подтверждение удаления PDF
        document.getElementById('confirmPdfDeleteBtn').addEventListener('click', async function () {
            const {pdfId, pdfCard} = window.pendingPdfDelete || {};
            if (!pdfId) return;

            if (typeof showLoadingSpinner === 'function') showLoadingSpinner();

            try {
                const response = await fetch(`/workorders/pdf/delete/${pdfId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (response.ok) {
                    pdfCard.style.transition = 'opacity 0.3s ease';
                    pdfCard.style.opacity = '0';
                    setTimeout(() => {
                        pdfCard.remove();
                        // Перезагружаем список, если он пуст
                        const container = document.getElementById('pdfListContainer');
                        if (container.querySelectorAll('.pdf-card').length === 0) {
                            loadPdfLibrary(window.currentPdfWorkorderId);
                        }
                    }, 300);

                    // Обновляем бейдж количества PDF после удаления
                    if (window.currentPdfWorkorderId) {
                        updatePdfCountBadge(window.currentPdfWorkorderId);
                    }

                    const toast = new bootstrap.Toast(document.getElementById('pdfDeletedToast'));
                    document.querySelector('#pdfDeletedToast .toast-body').textContent = 'PDF deleted successfully.';
                    toast.show();
                } else {
                    alert('Failed to delete PDF');
                }
            } catch (err) {
                console.error('Delete error:', err);
                alert('Server error');
            } finally {
                if (typeof hideLoadingSpinner === 'function') hideLoadingSpinner();
                bootstrap.Modal.getInstance(document.getElementById('confirmDeletePdfModal')).hide();
                window.pendingPdfDelete = null;
            }
        });
    </script>


@endsection
