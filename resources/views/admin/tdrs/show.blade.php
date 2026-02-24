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

        /* Фиксация шапки таблицы Inspection Unit */
        #tdr_inspect_Table thead {
            position: sticky;
            top: 0;
            z-index: 10;
        }

        #tdr_inspect_Table thead th {
            background-color: #030334 !important;
            box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.1);
        }

        #tdr__Table thead th {
            background-color: #030334 !important;
            box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.1);
        }

        /* Order Modal: высота 70vh, фиксированная шапка таблицы, прокрутка */
        .order-modal .modal-dialog {
            max-height: 70vh;
        }
        .order-modal .modal-content {
            max-height: 70vh;
            display: flex;
            flex-direction: column;
        }
        .order-modal .modal-header {
            flex-shrink: 0;
        }
        .order-modal .order-modal-table-wrapper {
            overflow-y: auto;
            flex: 1;
            min-height: 0;
        }
        .order-modal .order-modal-table thead {
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .order-modal .order-modal-table thead th {
            background-color: #030334 !important;
            box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.1);
        }

        .img-icon:hover {
            cursor: pointer;
        }

    </style>

    @if($current_wo->unit->manuals->builder )

        <div class="card bg-gradient">
            <div class="card-header  m-1 shadow">

                <div class="d-flex  text-center">
                    <div class="" style="width: 100px;">
                        <h5 class="text-success-emphasis  ps-1">{{__('WO')}}
                            <a class="text-success-emphasis " href="{{ route('mains.show', $current_wo->id) }}"
                                {{$current_wo->number}}>{{$current_wo->number}}
                            </a>
                        </h5>
                    </div>


                    <div class="ps-2 d-flex">

                        <div class="me-2 position-relative">
                            <button class="btn  btn-outline-warning ms-2 open-pdf-modal text-center"
                                    title="{{ __('PDF Library') }}"
                                    style="height: 55px;width: 55px ;align-content: center"
                                    data-id="{{ $current_wo->id }}"
                                    data-number="{{ $current_wo->number }}">
                                <i class="bi bi-file-earmark-pdf" style="font-size: 26px; "></i>
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
                                   class="btn fs-8 btn-outline-primary "
                                   style="height: 55px;width: 100px; align-content: center;line-height: 1rem"
                                   onclick="showLoadingSpinner()">
                                    {{__('All Parts Processes')}}
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
                               class="btn fs-8 btn-outline-primary " style="height: 55px;width: 100px;align-content: center;
                               line-height: 1rem">
                                {{__('Extra Parts Processes')}}
                            </a>
                            @if($extraProcessesCount > 0)
                                <span class="badge bg-success rounded-pill" style="position: absolute; top: -5px; right: -5px;
                                min-width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; padding: 0 5px;">
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
                                                   class="btn  fs-8 btn-outline-primary " style="min-height: 55px;width:
                                                      55px ; align-content: center;line-height: 1rem"
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
                               class="btn  fs-8 btn-outline-primary ms-2"
                               style="min-height: 55px;width: 85px ; align-content: center;line-height: 1rem"
                               onclick="showLoadingSpinner
                                   ()">
                                {{__('Bushing Processes')}}
                            </a>
                        </div>
                        <div>
                            <a href="{{route('rm_reports.show',['rm_report' => $current_wo->id])}}"
                               class="btn  fs-8 btn-outline-primary ms-2 "
                               style="height: 55px;width: 150px; align-content: center;line-height: 1rem"
                               onclick="showLoadingSpinner
                                   ()">
                                {{__('Repair & Modification Record')}}
                            </a>
                        </div>

                        <div class="ms-5 d-flex">
                            @if($current_wo->instruction_id == 1 )
                                <div class="me-1 ">
                                    <a href="{{ route('ndt-cad-csv.index', $current_wo->id) }}"
                                       class="btn fs-8 btn-outline-success" style="min-height: 55px; width: 90px;
                                       align-content: center;line-height: 1rem">
                                        STD Processes
                                    </a>
                                </div>
                            @endif
                            @if($hasTransfers)
                                <div class="ms-2">
                                    <a href="{{ route('transfers.show', $current_wo->id) }}"
                                       class="btn fs-8 btn-outline-info"
                                       style="min-height: 55px; width: 110px">
                                        Transfers
                                    </a>
                                </div>
                            @endif
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
                                        <button type="button" class="btn-close pb-2" data-bs-dismiss="modal"
                                                aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="d-flex">
                                            @php
                                                $manual = $current_wo->unit->manual; // Вызываем метод в единственном числе
                                            @endphp
                                            @if ($current_wo->unit && $current_wo->unit->manuals)
                                                <div>
                                                    <div class="m-2">
                                                        <a href="{{ $current_wo->unit->manuals->getFirstMediaBigUrl('manuals') }}"
                                                           data-fancybox="gallery">
                                                            <img class=""
                                                                 src="{{ $current_wo->unit->manuals->getFirstMediaThumbnailUrl('manuals')}}"
                                                                 width="150" height="150" alt="Image"/>
                                                        </a>
                                                    </div>
                                                </div>
                                            @else
                                                {{-- TODO: не находит картинку --}}
                                                <p>Manual not found</p>
                                            @endif
                                            <div>
                                                <div class="d-flex pt-2">
                                                    <div style="width: 150px">{{'Component Name: '}}</div>
                                                    <div style="width: 150px">{{$current_wo->description}}</div>
                                                </div>
                                                <div class="d-flex ">
                                                    <div class="text-end pe-3"
                                                         style="width: 150px">{{'Part Number: '}}</div>
                                                    <div style="width: 150px">{{$current_wo->unit->part_number}}</div>
                                                </div>
                                                <div class="d-flex ">
                                                    <div class="text-end pe-3"
                                                         style="width: 150px">{{'Serial Number: '}}</div>
                                                    <div style="width: 150px">{{$current_wo->serial_number}}</div>
                                                </div>
                                                <div class="d-flex ">
                                                    <div class="text-end pe-3"
                                                         style="width: 150px">{{'Instruction: '}}</div>
                                                    <div style="width: 150px">{{$current_wo->instruction->name}}</div>
                                                </div>
                                                <div class="d-flex ">
                                                    <div class="text-end pe-3" style="width: 150px">{{'CMM: '}}</div>
                                                    <div
                                                        style="width: 150px">{{$current_wo->unit->manuals->number}}</div>
                                                </div>
                                                <div class="d-flex ">
                                                    <div class="text-end pe-3" style="width: 150px">{{'MFR: '}}</div>
                                                    <div
                                                        style="width: 150px">{{$current_wo->unit->manuals->builder->name}}</div>
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
                             role="dialog" aria-labelledby="missingModalLabel{{$current_wo->number}}"
                             aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content bg-gradient" style="width: 1000px">
                                    <div class="modal-header">
                                        <div>
                                            <div class="d-flex justify-content-between" style="width: 600px">
                                                <h4 class="modal-title">{{__('Work order ')}}{{$current_wo->number}}</h4>
                                                <button type="button" class="btn-close pb-2" data-bs-dismiss="modal"
                                                        aria-label="Close"></button>
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
                                        <table
                                            class="display table table-cm table-hover table-striped align-middle table-bordered">
                                            <thead class="bg-gradient">
                                            <tr>
                                                <th class="text-primary bg-gradient">{{__('IPL')}}<i class="ms-1"></i>
                                                </th>
                                                <th class="text-primary bg-gradient">{{__('Part Description')}}<i
                                                        class="ms-1"></i></th>
                                                <th class="text-primary bg-gradient">{{__('Part Number')}}<i
                                                        class="ms-1"></i></th>
                                                <th class="text-primary bg-gradient">{{__('QTY')}}<i class="ms-1"></i>
                                                </th>
                                                <th class="text-primary bg-gradient">{{__('Delete')}}<i
                                                        class="ms-1"></i></th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($missingParts as $part)
                                                @php
                                                    $currentComponent = $part->orderComponent ?? $part->component;
                                                @endphp
                                                <tr>
                                                    <td class="p-3"> {{$currentComponent->ipl_num ?? ''}} </td>
                                                    <td class="p-3"> {{$currentComponent->name ?? ''}} </td>
                                                    <td class="p-3"> {{$currentComponent->part_number ?? ''}} </td>
                                                    <td class="p-3"> {{$part->qty}} </td>
                                                    <td class="p-3">
                                                        <!-- Кнопка удаления -->
                                                        <form action="{{ route('tdrs.destroy', $part->id) }}"
                                                              method="POST"
                                                              onsubmit="return confirm('Are you sure you want to delete this item?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit"
                                                                    class="btn btn-danger btn-sm">{{__('Delete')}}</button>
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
                                        <button type="button" class="btn-close pb-2" data-bs-dismiss="modal"
                                                aria-label="Close"></button>
                                    </div>
                                    <div class="table-wrapper">
                                        <table
                                            class="display table table-cm table-hover table-striped align-middle table-bordered">
                                            <thead class="">
                                            <tr>
                                                <th class="text-primary text-center">{{__('Teardown Inspection')}}</th>
                                                <th class="text-primary  bg-gradient ">{{__('Delete')}}</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($inspectsUnit as $unit)
                                                @php
                                                    // Отладочная информация
                                                    $hasConditions = $unit->conditions !== null;
                                                    $conditionName = $hasConditions ? $unit->conditions->name : 'N/A';
                                                    $unitComponentId = $unit->component_id;
                                                    $unitCodesId = $unit->codes_id;
                                                    $unitConditionsId = $unit->conditions_id;
                                                @endphp
                                                <tr>
                                                    <td class="p-3">
                                                        {{ $conditionName }}
                                                        @if($unitComponentId)
                                                            <small class="text-muted">(Component
                                                                ID: {{ $unitComponentId }}, Codes: {{ $unitCodesId }},
                                                                Conditions: {{ $unitConditionsId }})</small>
                                                        @endif
                                                    </td>
                                                    <td class="p-3">
                                                        <!-- Кнопка удаления -->
                                                        <form action="{{ route('tdrs.destroy', $unit->id) }}"
                                                              method="POST"
                                                              onsubmit="return confirm('Are you sure you want to delete this item?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit"
                                                                    class="btn btn-danger btn-sm">{{__('Delete')}}</button>
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
                        <div class="modal fade order-modal" id="orderModal{{$current_wo->number}}" tabindex="-1"
                             role="dialog" aria-labelledby="orderModalLabel{{$current_wo->number}}" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content bg-gradient" style="width: 700px">
                                    <div class="modal-header " style="width: 700px">

                                        <h4 class="modal-title me-4">{{__('Work order W')}}{{$current_wo->number}}</h4>
                                        <h4 class="modal-title ms-5 ">{{__('Ordered Parts  ')}}</h4>
                                        <button type="button" class="btn-close pb-2 text-end" data-bs-dismiss="modal"
                                                aria-label="Close"></button>


                                    </div>
                                    @if(count($ordersPartsNew))
                                        <div class="table-wrapper order-modal-table-wrapper">
                                            <table
                                                class="display table table-cm table-hover table-striped align-middle table-bordered order-modal-table">
                                                <thead class="bg-gradient">
                                                <tr>
                                                    <th class="text-primary  bg-gradient "
                                                        data-direction="asc">{{__('IPL')}}<i class="  ms-1"></i></th>
                                                    <th class="text-primary  bg-gradient "
                                                        data-direction="asc">{{__('Part Description') }}<i
                                                            class="  ms-1"></i></th>
                                                    <th class="text-primary  bg-gradient " style="width: 250px;"
                                                        data-direction="asc">{{__('Part Number')}}<i class="  ms-1"></i>
                                                    </th>
                                                    <th class="text-primary  bg-gradient "
                                                        data-direction="asc">{{__('QTY')}}<i class="bi  ms-1"></i></th>
                                                    <th class="text-primary  bg-gradient ">{{__('Conditions')}}<i
                                                            class="bi  ms-1"></i></th>
                                                    <th class="text-primary  bg-gradient ">{{__('Delete')}}<i
                                                            class="bi  ms-1"></i></th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @foreach($ordersPartsNew as $part)
                                                    <tr>

                                                        <td class="p-3"
                                                            style="width: 150px"> {{$part->orderComponent->ipl_num ?? ''}} </td>

                                                        <td class="p-3"
                                                            style="width: 250px"> {{$part->orderComponent->name ?? ''}} </td>
                                                        <td class="p-3"
                                                            style="width: 250px;"> {{$part->orderComponent->part_number ?? ''}} </td>
                                                        <td class="p-3"> {{$part->qty}} </td>
                                                        <td class="p-3"> {{$part->codes->name}} </td>
                                                        <td class="p-3">
                                                            <!-- Кнопка удаления -->
                                                            <form action="{{ route('tdrs.destroy', $part->id) }}"
                                                                  method="POST"
                                                                  onsubmit="return confirm('Are you sure you want to delete this item?');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit"
                                                                        class="btn btn-danger btn-sm">{{__('Delete')}}</button>
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
                                        <button type="button" class="btn-close pb-2" data-bs-dismiss="modal"
                                                aria-label="Close"></button>
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

            <div class="mb-1 mt-2 d-flex " style="margin-left: 60px;">


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
                        @if(!$hasProcessFormTdrs)
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

            <div class="d-flex justify-content-center" style="height: 75vh">

                <!- Inspection Unit ->

                <div class="me-3" style="width: 450px; max-height: 70vh; overflow-y: auto;">
                    <div class="table-wrapper me-3 p-2">
                        <table id="tdr_inspect_Table" class="display table table-sm
                                        table-hover table-striped align-middle table-bordered bg-gradient">
                            <thead>
                            <tr>
                                <th class=" text-primary text-center  " style="width: 300px;">
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#unitInspectionModal">
                                        {{__('Teardown Inspection')}}
                                    </a>
                                </th>
                                <th class=" text-primary text-center " style="width: 50px;">
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            {{-- Специальная строка для "PARTS MISSING UPON ARRIVAL" - показывается один раз, если есть записи с missing --}}
                            @if($hasMissingParts && $missingCondition)
                                <tr>
                                    <td class="text-center fs-8">
                                        {{ $missingCondition->name }}
                                    </td>
                                    <td class="text-center img-icon">
                                        <img src="{{ asset('img/missing.gif')}}" alt="missing"
                                             style="width: 50px;" class="" data-bs-toggle="modal"
                                             data-bs-target="#missingModal{{$current_wo->number}}"
                                        >
                                    </td>

                                </tr>
                            @endif
                            {{-- Unit Inspections (только component_id = null) --}}
                            @foreach($inspectsUnit->whereNull('component_id') as $tdr)
                                <tr>
                                    <td
                                        class="text-center fs-8">
                                        @php
                                            $conditionName = null;
                                            if ($tdr->conditions)  {
                                                $conditionName = $tdr->conditions->name;
                                            } else  {
                                                foreach($conditions as $condition) {
                                                    if ($condition->id == $tdr->conditions_id) {
                                                        $conditionName = $condition->name;
                                                        break;
                                                    }
                                                }
                                            }
                                            // Проверяем, является ли имя condition одним из "note 1", "note 2" и т.д.
                                            $isNoteCondition = $conditionName && preg_match('/^note\s+\d+$/i', $conditionName);
                                        @endphp

                                        @if(!$isNoteCondition)
{{--                                             Для обычных conditions показываем имя --}}
                                            @if($tdr->conditions)
                                                @if(empty($tdr->conditions->name))
                                                    {{ __('(No name)') }}
                                                @else
                                                    {{ $tdr->conditions->name }}
                                                @endif
                                            @else
                                                @foreach($conditions as $condition)
                                                    @if($condition->id == $tdr->conditions_id)
                                                        @if(empty($condition->name))
                                                            {{ __('(No name)') }}
                                                        @else
                                                            {{ $condition->name }}
                                                        @endif
                                                    @endif
                                                @endforeach
                                            @endif

                                        @endif
                                        @if($tdr->description)
                                            @if($isNoteCondition)
{{--                                                 Для note conditions показываем description без скобок--}}
                                                {{ $tdr->description }}
                                            @else
{{--                                                 Для обычных conditions показываем description в скобках--}}
                                                ({{$tdr->description}})
                                            @endif
                                        @endif
                                    </td>

                                    <td class="p-0 text-center img-icon">
                                        <div class="p-1">
                                            <form action="{{ route('tdrs.destroy', $tdr->id) }}" method="POST"
                                                  onsubmit="return confirm('Are you sure you want to delete this item?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm ">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            {{-- Строка "Ordered Parts" (компоненты с Order New, не Missing) — одна строка с бейджем суммы qty --}}
                            @if($hasOrderedParts ?? false)
                                <tr>
                                    <td class="text-center ">
                                        <span class="position-relative d-inline-block mt-2">
                                            Ordered Parts
                                            <sup class="badge bg-primary rounded-pill position-absolute" style="top: -0.5em;
                                            right: -0.3; font-size: 0.65em;">{{ $orderedPartsCount ?? 0 }}</sup>
                                        </span>
                                    </td>
                                    <td class="p-0 text-center img-icon">
                                        <img src="{{ asset('img/scrap.gif')}}" alt="order"
                                             style="width: 55px;" class=""
                                             data-bs-toggle="modal"
                                             data-bs-target="#orderModal{{$current_wo->number}}">
                                    </td>
                                </tr>
                            @endif
                            </tbody>
                        </table>
                    </div>
                </div>

                <!- Inspection Component ->

                <div class="me-3" >
                    <div class="table-wrapper me-3 p-2" style=" max-height: 60vh; overflow-y: auto;">
                        <table id="tdr_process_Table"
                               class="display table table-sm table-hover table-striped align-middle table-bordered">
                            <thead class="bg-gradient">
                            <tr>
                                <th class="text-center text-primary sortable"style="width: 60px">{{__('IPL')}} </th>
                                <th class=" text-center  text-primary sortable"style="width: 200px">{{__('Description')}} </th>
                                <th class=" text-center text-primary sortable "style="width: 120px">{{__('P/N')}} </th>
                                <th class=" text-center   text-primary sortable"style="width: 120px">{{__('S/N')}}</th>
                                <th class=" text-center  text-primary " style="width: 200px">{{__('Necessary')}}</th>
                                <th class=" text-center  text-primary " style="width: 150px">{{__('Code')}}</th>
                                <th class=" text-center  text-primary " style="width: 60px">{{__('EC')}}</th>
                                <th class=" text-primary text-center" style="width: 150px;"> {{__('Action')}}
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

                                        <td class="text-center">
                                            <div class="d-flex justify-content-center">
                                            <a href="{{ route('tdr-processes.processes',['tdrId'=>$tdr->id])}}"
                                               class="btn btn-outline-primary btn-sm me-2">
                                                <i class="bi bi-bar-chart-steps" title="Component Processes"></i>
                                            </a>
                                            <a href="{{ route('tdrs.edit',['id' => $tdr->id]) }}"
                                               class="btn btn-outline-primary btn-sm me-2">
                                                <i class="bi bi-pencil-square" title="Component Inspection Edit"></i>
                                            </a>
                                            <form action="{{ route('tdrs.destroy', ['tdr' => $tdr->id]) }}"
                                                  method="POST"
                                                  onsubmit="return confirm('Are you sure you want to delete this item?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                            </div>
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
                    <p>
                        <strong>{{ __('MFR:') }}</strong> {{$builders[$current_wo->unit->manuals->builders_id] ?? 'N/A' }}
                    </p>
                    <p><strong>{{ __('Scope:') }}</strong> {{$scopes[$current_wo->unit->manuals->scopes_id] ?? 'N/A' }}
                    </p>
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
                        <form id="updateWorkOrderForm"
                              data-inspection-route="{{ route('workorders.inspection', $current_wo->id) }}">
                            @csrf
                            <div class="form-check">
                                <label class="form-check-label" for="part_missing">Parts Missing</label>
                                <input class="form-check-input" type="checkbox" name="part_missing"
                                       id="part_missing" {{ $current_wo->part_missing ? 'checked' : '' }}>
                            </div>
                            <div class="form-check">
                                <label class="form-check-label" for="external_damage">External Damage</label>
                                <input class="form-check-input" type="checkbox" name="external_damage"
                                       id="external_damage" {{ $current_wo->external_damage ? 'checked' : '' }}>
                            </div>
                            <div class="form-check">
                                <label class="form-check-label" for="received_disassembly">Received Disassembly</label>
                                <input class="form-check-input" type="checkbox" name="received_disassembly"
                                       id="received_disassembly" {{ $current_wo->received_disassembly ? 'checked' : '' }}>
                            </div>
                            <div class="form-check">
                                <label class="form-check-label" for="disassembly_upon_arrival">Disassembly Upon
                                    Arrival</label>
                                <input class="form-check-input" type="checkbox" name="disassembly_upon_arrival"
                                       id="disassembly_upon_arrival" {{ $current_wo->disassembly_upon_arrival ? 'checked' : '' }}>
                            </div>
                            <div class="form-check">
                                <label class="form-check-label" for="nameplate_missing">Name Plate Missing</label>
                                <input class="form-check-input" type="checkbox" name="nameplate_missing"
                                       id="nameplate_missing" {{ $current_wo->nameplate_missing ? 'checked' : '' }}>
                            </div>
                            <div class="form-check">
                                <label class="form-check-label" for="preliminary_test_false">Preliminary Test -
                                    False</label>
                                <input class="form-check-input" type="checkbox" name="preliminary_test_false"
                                       id="preliminary_test_false" {{ $current_wo->preliminary_test_false ? 'checked' : '' }}>
                            </div>
                            <div class="form-check ">
                                <label class="form-check-label" for="extra_parts">Extra Parts</label>
                                <input class="form-check-input" type="checkbox" name="extra_parts"
                                       id="extra_parts" {{ $current_wo->extra_parts ? 'checked' : '' }}>
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


    <!-- JavaScript модули для tdrs.show -->
    <script>
        // Устанавливаем workorder ID для использования в модулях
        window.currentWorkorderId = {{ $current_wo->id }};
    </script>
    <script src="{{ asset('js/tdrs/show/workorder-form-handler.js') }}"></script>
    <script src="{{ asset('js/tdrs/show/pdf-badge-handler.js') }}"></script>
    <script src="{{ asset('js/tdrs/show/pdf-library-handler.js') }}"></script>
    <script src="{{ asset('js/tdrs/show/pdf-viewer-handler.js') }}"></script>
    <script src="{{ asset('js/tdrs/show/pdf-upload-handler.js') }}"></script>
    <script src="{{ asset('js/tdrs/show/pdf-delete-handler.js') }}"></script>
    <script src="{{ asset('js/tdrs/show/show-main.js') }}"></script>

    <!-- Unit Inspection Modal Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const selectAllCheckbox = document.getElementById('selectAllConditions');
            const conditionCheckboxes = document.querySelectorAll('.condition-checkbox');
            const saveBtn = document.getElementById('saveUnitInspectionsBtn');
            const form = document.getElementById('unitInspectionForm');

            // Handle modal chain: Unit Inspection -> Manage Condition
            const manageConditionBtn = document.querySelector('[data-bs-target="#manageConditionModal"]');
            const unitInspectionModal = document.getElementById('unitInspectionModal');
            const manageConditionModal = document.getElementById('manageConditionModal');

            if (manageConditionBtn && unitInspectionModal && manageConditionModal) {
                manageConditionBtn.addEventListener('click', function () {
                    // Store reference to unit inspection modal
                    manageConditionModal.dataset.returnToModal = 'unitInspectionModal';
                });

                // When manage condition modal closes, reopen unit inspection modal if needed
                manageConditionModal.addEventListener('hidden.bs.modal', function () {
                    if (manageConditionModal.dataset.returnToModal === 'unitInspectionModal') {
                        const unitModal = new bootstrap.Modal(unitInspectionModal);
                        unitModal.show();
                        delete manageConditionModal.dataset.returnToModal;
                    }
                });
            }

            // Select All functionality
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function () {
                    conditionCheckboxes.forEach(checkbox => {
                        checkbox.checked = this.checked;
                    });
                });
            }

            // Update Select All checkbox state
            conditionCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function () {
                    const allChecked = Array.from(conditionCheckboxes).every(cb => cb.checked);
                    const someChecked = Array.from(conditionCheckboxes).some(cb => cb.checked);
                    if (selectAllCheckbox) {
                        selectAllCheckbox.checked = allChecked;
                        selectAllCheckbox.indeterminate = someChecked && !allChecked;
                    }
                });
            });

            // Save button handler
            if (saveBtn) {
                saveBtn.addEventListener('click', function () {
                    const formData = new FormData(form);
                    const workorderId = formData.get('workorder_id');

                    // Prepare data for AJAX request
                    const conditionsData = {};
                    conditionCheckboxes.forEach(checkbox => {
                        if (checkbox.checked) {
                            const conditionId = checkbox.getAttribute('data-condition-id');
                            const notesInput = document.querySelector(`input[name="conditions[${conditionId}][notes]"]`);
                            const tdrIdInput = document.querySelector(`input[name="conditions[${conditionId}][tdr_id]"]`);

                            conditionsData[conditionId] = {
                                selected: true,
                                notes: notesInput ? notesInput.value : '',
                                tdr_id: tdrIdInput ? tdrIdInput.value : null
                            };
                        }
                    });

                    // Show loading state
                    saveBtn.disabled = true;
                    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> {{ __('Saving...') }}';

                    // Get CSRF token
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                        formData.get('_token') ||
                        '{{ csrf_token() }}';

                    // Send AJAX request
                    fetch('{{ route("tdrs.store.unit-inspections") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            workorder_id: workorderId,
                            conditions: conditionsData
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Close modal
                                const modal = bootstrap.Modal.getInstance(document.getElementById('unitInspectionModal'));
                                if (modal) {
                                    modal.hide();
                                }

                                // Reload page to show updated data
                                window.location.reload();
                            } else {
                                showNotification(data.message || '{{ __("An error occurred while saving.") }}', 'error');
                                saveBtn.disabled = false;
                                saveBtn.innerHTML = '<i class="fas fa-save"></i> {{ __('Save') }}';
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showNotification('{{ __("An error occurred while saving.") }}', 'error');
                            saveBtn.disabled = false;
                            saveBtn.innerHTML = '<i class="fas fa-save"></i> {{ __('Save') }}';
                        });
                });
            }
        });
    </script>

    <!-- Manage Condition Modal Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

            // Edit Condition
            document.querySelectorAll('.edit-condition-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    const conditionId = this.getAttribute('data-condition-id');
                    const row = document.querySelector(`tr[data-condition-id="${conditionId}"]`);

                    // Hide edit button, show save/cancel
                    this.closest('.btn-group').classList.add('d-none');
                    row.querySelector('.save-cancel-group').classList.remove('d-none');

                    // Show input, hide display
                    row.querySelector('.condition-name-display').classList.add('d-none');
                    row.querySelector('.condition-name-edit').classList.remove('d-none');
                    row.querySelector('.condition-name-edit').focus();
                });
            });

            // Cancel Edit
            document.querySelectorAll('.cancel-edit-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    const conditionId = this.getAttribute('data-condition-id');
                    const row = document.querySelector(`tr[data-condition-id="${conditionId}"]`);

                    // Restore original value
                    const input = row.querySelector('.condition-name-edit');
                    input.value = input.getAttribute('data-original-name');

                    // Hide input, show display
                    input.classList.add('d-none');
                    row.querySelector('.condition-name-display').classList.remove('d-none');

                    // Show edit button, hide save/cancel
                    row.querySelector('.save-cancel-group').classList.add('d-none');
                    row.querySelector('.btn-group').classList.remove('d-none');
                });
            });

            // Save Condition
            document.querySelectorAll('.save-condition-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    const conditionId = this.getAttribute('data-condition-id');
                    const row = document.querySelector(`tr[data-condition-id="${conditionId}"]`);
                    const input = row.querySelector('.condition-name-edit');
                    const newName = input.value.trim();

                    // Disable button during save
                    this.disabled = true;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> {{ __("Saving...") }}';

                    fetch(`/admin/conditions/${conditionId}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            name: newName,
                            unit: 1
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Update display
                                row.querySelector('.condition-name-display').textContent = newName || '{{ __("(No name)") }}';
                                input.setAttribute('data-original-name', newName);

                                // Hide input, show display
                                input.classList.add('d-none');
                                row.querySelector('.condition-name-display').classList.remove('d-none');

                                // Show edit button, hide save/cancel
                                row.querySelector('.save-cancel-group').classList.add('d-none');
                                row.querySelector('.btn-group').classList.remove('d-none');

                                // Update button data attribute
                                const editBtn = row.querySelector('.edit-condition-btn');
                                editBtn.setAttribute('data-condition-name', newName);

                                // Reload page to update unit inspection modal
                                window.location.reload();
                            } else {
                                showNotification(data.message || '{{ __("An error occurred while saving.") }}', 'error');
                                this.disabled = false;
                                this.innerHTML = '<i class="fas fa-check"></i> {{ __("Save") }}';
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showNotification('{{ __("An error occurred while saving.") }}', 'error');
                            this.disabled = false;
                            this.innerHTML = '<i class="fas fa-check"></i> {{ __("Save") }}';
                        });
                });
            });

            // Delete Condition
            document.querySelectorAll('.delete-condition-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    const conditionId = this.getAttribute('data-condition-id');
                    const conditionName = this.getAttribute('data-condition-name');

                    if (!confirm(`{{ __("Are you sure you want to delete condition") }} "${conditionName}"?`)) {
                        return;
                    }

                    // Disable button during delete
                    this.disabled = true;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> {{ __("Deleting...") }}';

                    fetch(`/admin/conditions/${conditionId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Remove row
                                const row = document.querySelector(`tr[data-condition-id="${conditionId}"]`);
                                if (row) {
                                    row.remove();
                                }

                                // Reload page to update unit inspection modal
                                window.location.reload();
                            } else {
                                showNotification(data.message || '{{ __("An error occurred while deleting.") }}', 'error');
                                this.disabled = false;
                                this.innerHTML = '<i class="fas fa-trash"></i> {{ __("Delete") }}';
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showNotification('{{ __("An error occurred while deleting.") }}', 'error');
                            this.disabled = false;
                            this.innerHTML = '<i class="fas fa-trash"></i> {{ __("Delete") }}';
                        });
                });
            });

            // Add Condition from Manage Modal
            const addConditionFormFromManage = document.getElementById('addConditionFormFromManage');
            if (addConditionFormFromManage) {
                addConditionFormFromManage.addEventListener('submit', function (e) {
                    e.preventDefault();

                    const formData = new FormData(this);
                    const submitBtn = this.querySelector('button[type="submit"]');
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> {{ __("Saving...") }}';

                    fetch('{{ route("conditions.store") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: formData
                    })
                        .then(response => {
                            // Check if response is JSON
                            const contentType = response.headers.get('content-type');
                            if (contentType && contentType.includes('application/json')) {
                                return response.json();
                            } else {
                                // If redirect, treat as success
                                return {success: true};
                            }
                        })
                        .then(data => {
                            if (data.success || data === undefined) {
                                // Close add modal
                                const addModal = bootstrap.Modal.getInstance(document.getElementById('addConditionModalFromManage'));
                                if (addModal) {
                                    addModal.hide();
                                }

                                // Reload page to update both modals
                                window.location.reload();
                            } else {
                                showNotification(data.message || '{{ __("An error occurred while saving.") }}', 'error');
                                submitBtn.disabled = false;
                                submitBtn.innerHTML = '{{ __("Save Condition") }}';
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            // Even if error, reload to show updated list
                            window.location.reload();
                        });
                });
            }
        });
    </script>

    <!-- PDF Library Modal -->
    <div class="modal fade" id="pdfModal" tabindex="-1" aria-labelledby="pdfModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content" style="background-color: #343A40">
                <div class="modal-header">
                    <h5 class="modal-title" id="pdfModalLabel">PDF Library - Workorder W<span
                            id="pdfModalWorkorderNumber"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Upload Section -->
                    <div class="mb-4">
                        <div class="card bg-dark border-secondary">
                            <div class="card-body d-flex">
                                <h6 class="text-primary mb-3 me-4">Upload PDF Files</h6>
                                <form id="pdfUploadForm" enctype="multipart/form-data">
                                    <div class="ms-3">
                                        <div class="d-flex">
                                            <label for="pdfDocumentName" class="form-label  me-2">
                                                Document Name</label>
                                            <input type="text" class="form-control" id="pdfDocumentName"
                                                   name="document_name"
                                                   placeholder="Enter document name (optional)" style="width: 400px"
                                                   maxlength="255">
                                            {{--                                            <small class="text-muted ms-2">Optional: Enter a name for the document</small>--}}
                                        </div>
                                        <div class="input-group mt-2 ms-4 d-flex " style="height: 40px">
                                            <input type="file" class="form-control" id="pdfFileInput" name="pdf"
                                                   accept=".pdf"
                                                   style="width: 385px"
                                                   required>
                                            <button class="btn btn-primary" type="submit" id="uploadPdfBtn">
                                                <i class="bi bi-upload"></i> Upload
                                            </button>
                                            <small class="text-muted ms-3">Max size: 10MB. Upload one file at a
                                                time.</small>
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
                    <div class="modal fade" id="pdfViewerModal" tabindex="-1" aria-labelledby="pdfViewerModalLabel"
                         aria-hidden="true">
                        <div class="modal-dialog modal-xl">
                            <div class="modal-content bg-dark">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="pdfViewerModalLabel">PDF Viewer</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                            aria-label="Close"></button>
                                </div>
                                <div class="modal-body p-0" style="min-height: 600px; position: relative;">
                                    <iframe id="pdfViewerFrame" src=""
                                            style="width: 100%; height: 600px; border: none; position: relative;"></iframe>
                                </div>
                                <div class="modal-footer">
                                    <a id="pdfDownloadLink" href="#" class="btn btn-primary" download>
                                        <i class="bi bi-download"></i> Download
                                    </a>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close
                                    </button>
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
    <div class="modal fade" id="confirmDeletePdfModal" tabindex="-1" aria-labelledby="confirmDeletePdfLabel"
         aria-hidden="true">
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
        <div id="pdfDeletedToast" class="toast bg-success text-white" role="alert" aria-live="assertive"
             aria-atomic="true">
            <div class="toast-body">
                PDF deleted successfully.
            </div>
        </div>
    </div>

    <!-- Modal - Unit Inspection -->
    <div class="modal fade" id="unitInspectionModal" tabindex="-1" aria-labelledby="unitInspectionModalLabel"
         aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content bg-gradient">
                <div class="modal-header">
                    <h5 class="modal-title" id="unitInspectionModalLabel">
                        <i class="fas fa-clipboard-check"></i> {{ __('Teardown Inspection') }}
                        - {{ __('Work Order') }} {{ $current_wo->number }}
                    </h5>
                    <div class="ms-auto me-2">
                        @admin
                        <button type="button" class="btn btn-outline-warning btn-sm" data-bs-toggle="modal"
                                data-bs-target="#manageConditionModal" data-bs-dismiss="modal">
                            <i class="fas fa-cog"></i> {{ __('Manage Condition') }}
                        </button>
                        @endadmin
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="unitInspectionForm">
                        @csrf
                        <input type="hidden" name="workorder_id" value="{{ $current_wo->id }}">

                        <div class="table-responsive" style="max-height: 60vh; overflow-y: auto;">
                            <table class="table table-hover table-bordered">
                                <thead class="table-primary" style="position: sticky; top: 0; z-index: 10;">
                                <tr>
                                    <th class="text-center" style="width: 50px;">
                                        <input type="checkbox" id="selectAllConditions" title="{{ __('Select All') }}">
                                    </th>
                                    <th class="text-center">{{ __('Condition') }}</th>
                                    <th class="text-center" style="width: 300px;">{{ __('Notes') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @php
                                    // Получаем существующие unit inspections для данного workorder
                                    $existingInspections = [];
                                    foreach($tdrs as $tdr) {
                                        if($tdr->use_tdr == true && $tdr->use_process_forms != true && $tdr->conditions_id) {
                                            $existingInspections[$tdr->conditions_id] = [
                                                'id' => $tdr->id,
                                                'description' => $tdr->description ?? ''
                                            ];
                                        }
                                    }
                                @endphp
                                @foreach($unit_conditions as $unit_condition)
                                    @if($unit_condition->name != 'PARTS MISSING UPON ARRIVAL AS INDICATED ON PARTS LIST')
                                        @php
                                            $isChecked = isset($existingInspections[$unit_condition->id]);
                                            $existingDescription = $isChecked ? $existingInspections[$unit_condition->id]['description'] : '';
                                            $existingTdrId = $isChecked ? $existingInspections[$unit_condition->id]['id'] : null;
                                        @endphp
                                        <tr>
                                            <td class="text-center align-middle">
                                                <input type="checkbox"
                                                       class="form-check-input condition-checkbox"
                                                       name="conditions[{{ $unit_condition->id }}][selected]"
                                                       value="1"
                                                       data-condition-id="{{ $unit_condition->id }}"
                                                    {{ $isChecked ? 'checked' : '' }}>
                                                @if($existingTdrId)
                                                    <input type="hidden"
                                                           name="conditions[{{ $unit_condition->id }}][tdr_id]"
                                                           value="{{ $existingTdrId }}">
                                                @endif
                                            </td>
                                            <td class="align-middle">
                                                <label for="condition_{{ $unit_condition->id }}"
                                                       style="cursor: pointer; margin: 0;">
                                                    @if(empty($unit_condition->name))
                                                        {{ __('(No name)') }}
                                                    @else
                                                        {{ $unit_condition->name }}
                                                    @endif
                                                </label>
                                            </td>
                                            <td class="align-middle">
                                                <input type="text"
                                                       class="form-control form-control-sm condition-notes"
                                                       name="conditions[{{ $unit_condition->id }}][notes]"
                                                       id="condition_{{ $unit_condition->id }}"
                                                       value="{{ $existingDescription }}"
                                                       placeholder="{{ __('Enter notes...') }}">
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary"
                            data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="button" class="btn btn-outline-primary" id="saveUnitInspectionsBtn">
                        <i class="fas fa-save"></i> {{ __('Save') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal - Manage Condition -->
    <div class="modal fade" id="manageConditionModal" tabindex="-1" aria-labelledby="manageConditionModalLabel"
         aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content bg-gradient">
                <div class="modal-header">
                    <h5 class="modal-title" id="manageConditionModalLabel">
                        <i class="fas fa-cog"></i> {{ __('Manage Condition') }} - {{ __('Unit Conditions') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        @admin
                        <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal"
                                data-bs-target="#addConditionModalFromManage">
                            <i class="fas fa-plus"></i> {{ __('Add Condition') }}
                        </button>
                        @endadmin
                    </div>

                    <div class="table-responsive" style="max-height: 60vh; overflow-y: auto;">
                        <table class="table table-hover table-bordered">
                            <thead class="table-primary" style="position: sticky; top: 0; z-index: 10;">
                            <tr>
                                <th class="text-center">{{ __('Condition Name') }}</th>
                                <th class="text-center" style="width: 150px;">{{ __('Actions') }}</th>
                            </tr>
                            </thead>
                            <tbody id="manageConditionsTableBody">
                            @foreach($unit_conditions as $unit_condition)
                                <tr data-condition-id="{{ $unit_condition->id }}">
                                    <td class="align-middle">
                                        <span class="condition-name-display">
                                            @if(empty($unit_condition->name))
                                                {{ __('(No name)') }}
                                            @else
                                                {{ $unit_condition->name }}
                                            @endif
                                        </span>
                                        <input type="text"
                                               class="form-control form-control-sm condition-name-edit d-none"
                                               value="{{ $unit_condition->name }}"
                                               data-original-name="{{ $unit_condition->name }}">
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="btn-group" role="group">
                                            <button type="button"
                                                    class="btn btn-outline-warning btn-sm edit-condition-btn"
                                                    data-condition-id="{{ $unit_condition->id }}"
                                                    data-condition-name="{{ $unit_condition->name }}">
                                                <i class="fas fa-edit"></i> {{ __('Edit') }}
                                            </button>
                                            @if($unit_condition->name != 'PARTS MISSING UPON ARRIVAL AS INDICATED ON PARTS LIST')
                                                <button type="button"
                                                        class="btn btn-outline-danger btn-sm delete-condition-btn"
                                                        data-condition-id="{{ $unit_condition->id }}"
                                                        data-condition-name="{{ $unit_condition->name }}">
                                                    <i class="fas fa-trash"></i> {{ __('Delete') }}
                                                </button>
                                            @endif
                                        </div>
                                        <div class="btn-group d-none save-cancel-group" role="group">
                                            <button type="button"
                                                    class="btn btn-outline-success btn-sm save-condition-btn"
                                                    data-condition-id="{{ $unit_condition->id }}">
                                                <i class="fas fa-check"></i> {{ __('Save') }}
                                            </button>
                                            <button type="button"
                                                    class="btn btn-outline-secondary btn-sm cancel-edit-btn"
                                                    data-condition-id="{{ $unit_condition->id }}">
                                                <i class="fas fa-times"></i> {{ __('Cancel') }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary"
                            data-bs-dismiss="modal">{{ __('Close') }}</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal - Add Condition (from Manage) -->
    <div class="modal fade" id="addConditionModalFromManage" tabindex="-1"
         aria-labelledby="addConditionModalFromManageLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content bg-gradient">
                <div class="modal-header">
                    <h5 class="modal-title" id="addConditionModalFromManageLabel">{{ __('Add Condition') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addConditionFormFromManage">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="unit" value="1">
                        <input type="hidden" name="workorder_id" value="{{ $current_wo->id }}">
                        <div class="form-group">
                            <label for="conditionName">{{ __('Name') }} <small class="text-muted">({{ __('Optional') }}
                                    )</small></label>
                            <input id="conditionName" type="text" class="form-control" name="name"
                                   placeholder="{{ __('Leave empty to create condition with notes only') }}">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary"
                                data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-outline-primary">{{ __('Save Condition') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection
