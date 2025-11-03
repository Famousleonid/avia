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

                        <div class=" " style=" height: 40px; width: 250px;">
                            <div class=" mt-1 d-flex">
                                @if($trainings && $trainings->date_training && $user->id == $user_wo)
                                    @php
                                        $trainingDate = \Carbon\Carbon::parse($trainings->date_training);
                                        $monthsDiff = $trainingDate->diffInMonths(now());
                                        $daysDiff = $trainingDate->diffInDays(now());
                                        $isThisMonth = $trainingDate->isCurrentMonth();
                                        $isThisYear = $trainingDate->isCurrentYear();
                                    @endphp
                                    @if($monthsDiff<=12)
                                        <div class="d-flex justify-content-center">
                                            <div class=" pb-1 " style="color: lawngreen;">
                                                @if($monthsDiff == 0  && $user->id == $user_wo)
                                                    @if($isThisMonth)
                                                        Last training this month
                                                        <p>{{ $trainingDate->format('M d, Y') }}</p>
                                                    @else
                                                        {{--                                                    Last training {{ $monthsDiff }} months ago ({{ $trainingDate->format('M d,--}}
                                                        {{--                                                    Y') }})--}}
                                                        Last training for this unit
                                                        <p>{{ $trainingDate->format('M d, Y') }} </p>

                                                    @endif
                                                @elseif($monthsDiff == 1)
                                                    @if($user->id == $user_wo)
                                                        Last training {{ $monthsDiff }} month ago
                                                        <p>{{ $trainingDate->format('M d, Y') }}</p>
                                                    @endif
                                                @else
                                                    @if($monthsDiff >= 6 && $user->id == $user_wo)

                                                            Last training {{ $monthsDiff }} months ago
                                                            <p>{{$trainingDate->format('M d, Y') }}</p>

                                                    @else
                                                        Last training {{ $monthsDiff }} months ago
                                                        <p>{{ $trainingDate->format('M d, Y') }}</p>
                                                    @endif
                                                @endif
                                            </div>
                                            @if($monthsDiff >= 6 && $user->id == $user_wo)
                                                <div class="text-center ms-2" style="height: 40px; width: 40px">
                                                    <button class="btn btn-success btn-sm" title="{{ __('Update to Today') }}" onclick="updateTrainingToToday({{
                                                    $manual_id }}, '{{ $trainings->date_training }}')">
                                                        <i class="bi bi-calendar-check" style="font-size: 28px;"></i>
{{--                                                        Update to Today--}}
                                                    </button>
                                                </div>
                                            @endif
                                        </div>

                                    @else
                                        <div class=" " style="color: red; ">
                                            Last training {{ $monthsDiff }} months ago ({{ $trainingDate->format('M d, Y') }}). Need Update
                                            @if($user->id == $user_wo)
                                                <div class="ms-2">
                                                    <button class="btn btn-warning btn-sm" title="{{ __('Update to Today') }}" onclick="updateTrainingToToday({{
                                                    $manual_id }}, '{{ $trainings->date_training }}')">
                                                        <i class="bi bi-calendar-check" style="font-size: 28px;" ></i>
{{--                                                        Update to Today--}}
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                @else
                                    @if($user->id == $user_wo)
                                        <div class="d-flex " >
                                            <div class="" style="color: red;">
                                                There are no trainings
                                                <p>for this unit.</p>
                                            </div>
                                                <div class="ms-2">
                                                    <button class="fs-75 btn btn-primary btn-sm" title="{{ __('Create Trainings') }}" onclick="createTrainings({{
                                                    $manual_id }})">
                                                        <i class="bi bi-plus-circle" style="font-size: 28px;"></i>
                                                    </button>
                                            </div>
                                        </div>

                                    @endif
                                @endif
                            </div>
                        </div>

                        <div class="me-2 ms-2">
                            <a href="{{ route('mains.show', $current_wo->id) }}" class="btn
                                            btn-outline-success " title="{{ __('WO Tasks') }}"
                               onclick="showLoadingSpinner()">
                                <i class="bi bi-list-task " style="font-size: 28px;"></i>

                            </a>
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
                        <div class="me-2">
                            <a href="{{route('extra_processes.show_all',['id'=>$current_wo->id])}}"
                               class="btn fs-8 btn-outline-primary " style="height: 55px;width: 140px" onclick="showLoadingSpinner
                                       ()">
                                {{__('Extra Component Processes')}}
                            </a>
                        </div>
                        <div>
                            <a href="{{route('log_card.show',['id' => $current_wo->id])}}"
                               class="btn  fs-8 btn-outline-primary " style="min-height: 55px;width: 55px"
                               onclick="showLoadingSpinner
                                   ()">
                                {{__('Log Card')}}
                            </a>
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

                        <div class="ms-5">
                            @if($current_wo->instruction_id == 1 )
                                <div class="me-1 ">
                                    <a href="{{ route('ndt-cad-csv.index', $current_wo->id) }}"
                                       class="btn fs-8 btn-outline-success" style="min-height: 55px; width: 90px">
                                        {{--                                    <i class="bi bi-gear"></i> --}}
                                        STD Processes
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="ms-3">
                        <div class="d-flex ">

{{--                            <div class=" " style=" height: 40px; width: 300px;margin-top: -7px">--}}
{{--                                <div class=" mt-1 ">--}}
{{--                                    @if($trainings && $trainings->date_training && $user->id == $user_wo)--}}
{{--                                        @php--}}
{{--                                            $trainingDate = \Carbon\Carbon::parse($trainings->date_training);--}}
{{--                                            $monthsDiff = $trainingDate->diffInMonths(now());--}}
{{--                                            $daysDiff = $trainingDate->diffInDays(now());--}}
{{--                                            $isThisMonth = $trainingDate->isCurrentMonth();--}}
{{--                                            $isThisYear = $trainingDate->isCurrentYear();--}}
{{--                                        @endphp--}}
{{--                                        @if($monthsDiff<=12)--}}
{{--                                            <div class="">--}}
{{--                                                <div class=" pb-1" style="color: lawngreen; margin-top: -7px">--}}
{{--                                                    @if($monthsDiff == 0  && $user->id == $user_wo)--}}
{{--                                                        @if($isThisMonth)--}}
{{--                                                            Last training this month ({{ $trainingDate->format('M d') }})--}}
{{--                                                        @else--}}
{{--                                                            --}}{{--                                                    Last training {{ $monthsDiff }} months ago ({{ $trainingDate->format('M d,--}}
{{--                                                            --}}{{--                                                    Y') }})--}}
{{--                                                            Last training was {{ $trainingDate->format('M d, Y') }}--}}
{{--                                                        @endif--}}
{{--                                                    @elseif($monthsDiff == 1)--}}
{{--                                                        @if($user->id == $user_wo)--}}
{{--                                                            Last training {{ $monthsDiff }} month ago ({{ $trainingDate->format('M d') }})--}}
{{--                                                        @endif--}}
{{--                                                    @else--}}
{{--                                                        @if($user->id == $user_wo)--}}
{{--                                                            Last training {{ $monthsDiff }} months ago ({{ $trainingDate->format('M d') }})--}}
{{--                                                        @endif--}}
{{--                                                    @endif--}}
{{--                                                </div>--}}
{{--                                                @if($monthsDiff >= 6 && $user->id == $user_wo)--}}
{{--                                                    <div class="text-center ms-2">--}}
{{--                                                        <button class="btn btn-success btn-sm" onclick="updateTrainingToToday({{ $manual_id }}, '{{ $trainings->date_training }}')">--}}
{{--                                                            <i class="bi bi-calendar-check"></i> Update to Today--}}
{{--                                                        </button>--}}
{{--                                                    </div>--}}
{{--                                                @endif--}}
{{--                                            </div>--}}

{{--                                        @else--}}
{{--                                            <div class=" " style="color: red;  margin-top: -7px">--}}
{{--                                                Last training {{ $monthsDiff }} months ago ({{ $trainingDate->format('M d, Y') }}). Need Update--}}
{{--                                                @if($user->id == $user_wo)--}}
{{--                                                    <div class="ms-2">--}}
{{--                                                        <button class="btn btn-warning btn-sm" onclick="updateTrainingToToday({{ $manual_id }}, '{{ $trainings->date_training }}')">--}}
{{--                                                            <i class="bi bi-calendar-check"></i> Update to Today--}}
{{--                                                        </button>--}}
{{--                                                    </div>--}}
{{--                                                @endif--}}
{{--                                            </div>--}}
{{--                                        @endif--}}
{{--                                    @else--}}
{{--                                        @if($user->id == $user_wo)--}}
{{--                                            <div class="" style="color: red; margin-top: -7px">--}}
{{--                                                There are no trainings for this unit.--}}
{{--                                                <div class="pt-1">--}}
{{--                                                    <button class="fs-75 btn btn-primary btn-sm" onclick="createTrainings({{--}}
{{--                                                    $manual_id }})">--}}
{{--                                                        <i class="bi bi-plus-circle"></i> Create Trainings--}}
{{--                                                    </button>--}}
{{--                                                </div>--}}
{{--                                            </div>--}}
{{--                                        @endif--}}
{{--                                    @endif--}}
{{--                                </div>--}}
{{--                            </div>--}}

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
                                        $csv_media = $manual->getMedia('csv_files')->first(function($media) {
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

                                                <div>
                                                    <a href="{{route('tdrs.inspection',['workorder_id' => $current_wo->id])}}"
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
                                <a href="{{ route('tdrs.woProcessForm', ['id'=> $current_wo->id]) }}"
                                   class="btn fs-8 btn-outline-warning me-3 formLink "
                                   target="_blank"
                                   id="#" style=" height: 55px; width: 100px">
                                    {{--                                                                                <i class="bi bi-file-earmark-excel"> WO Process Sheet </i>--}}
                                    WO Process Sheet
                                </a>

                                <a href="{{ route('tdrs.tdrForm', ['id'=> $current_wo->id]) }}"
                                   class="btn fs-8 btn-outline-warning me-1 formLink "
                                   target="_blank"
                                   id="#" style=" height: 55px; width: 60px">
                                    TDR Form
                                </a>
                                @if(count($processParts)==0)
                                    <a href="{{ route('tdrs.specProcessFormEmp', ['id'=> $current_wo->id]) }}"
                                       class="btn fs-8 btn-outline-warning me-1 formLink "
                                       target="_blank"
                                       id="#" style=" height: 55px; width: 60px">
                                        SP Form
                                    </a>
                                @else
                                    <a href="{{ route('tdrs.specProcessForm', ['id'=> $current_wo->id]) }}"
                                       class="btn fs-8 btn-outline-warning me-1 formLink "
                                       target="_blank"
                                       id="#" style=" height: 55px;width: 60px">
                                        SP Form
                                    </a>
                                @endif

                                <a href="{{ route('rm_reports.rmRecordForm', ['id'=> $current_wo->id]) }}"
                                   class="btn fs-8 btn-outline-warning me-1 formLink "
                                   target="_blank"
                                   id="#" style=" height: 55px; width: 60px">
                                        R&M Form
                                </a>

                                <a href="{{ route('tdrs.prlForm', ['id'=> $current_wo->id]) }}"
                                   class="btn fs-8 btn-outline-warning me-1 formLink align-content-center "
                                   target="_blank"
                                   id="#" style=" height: 55px; width: 55px">
                                    {{--                                        <i class="bi bi-file-earmark-excel"> PRL </i>--}}
                                    PRL
                                </a>
                            @endif


                        </div>

                        <! --- STD Processes --- ->
                        <div class="d-flex">
                            @if($current_wo->instruction_id == 1 && $hasNdtComponents)
                                <div class="me-1">
                                    <a href="{{ route('tdrs.ndtStd', ['workorder_id' => $current_wo->id]) }}"
                                       class="btn fs-8 btn-outline-warning" style="min-height: 55px; width: 55px"
                                       target="_blank">
                                        NDT STD
                                    </a>
                                </div>
                            @endif

                            @if($current_wo->instruction_id == 1 && $hasCadComponents)
                                <div class="me-1 ">
                                    <a href="{{ route('tdrs.cadStd', ['workorder_id' => $current_wo->id]) }}"
                                       class="btn fs-8 btn-outline-warning" style="min-height: 55px; width: 55px"
                                       target="_blank">
                                        CAD STD
                                    </a>
                                </div>
                            @endif

                            @if($current_wo->instruction_id == 1 && $hasStressComponents)
                                <div class="me-1 ">
                                    <a href="{{ route('tdrs.stressStd', ['workorder_id' => $current_wo->id]) }}"
                                       class="btn fs-8 btn-outline-warning" style="min-height: 55px; width: 60px"
                                       target="_blank">
                                        Stress STD
                                    </a>
                                </div>
                            @endif

                            @if($hasPaintComponents)
                                <div class="me-1 ">
                                    <a href="{{ route('tdrs.paintStd', ['workorder_id' => $current_wo->id]) }}"
                                       class="btn fs-8 btn-outline-warning" style="min-height: 55px; width: 55px"
                                       target="_blank">
                                        Paint STD
                                    </a>
                                </div>
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
                                                    @if($condition->id == $tdr->conditions_id)
                                                        {{$condition ->name}}
                                                    @endif
                                                @endforeach

                                                @if($tdr->component)
                                                    <fs-8 class="" style="color: #5897fb">(scrap)</fs-8>

                                                    {{ $tdr->component->name }}
                                                    @if ($tdr->qty == 1)
                                                        ({{ $tdr->component->ipl_num }})
                                                    @else
                                                         ({{ $tdr->component->ipl_num }}, {{$tdr->qty}} pcs)
                                                    @endif
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
                                                        <button class="btn btn-outline-info btn-sm" style="height: 32px"
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

                                            <td class="d-flex justify-content-center" style="width: 150px">


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

        // Функции для работы с тренировками
        function createTrainings(manualId) {
            if (confirm('Create new trainings for this unit?')) {
                // Перенаправляем на страницу создания тренировок с предзаполненным manual_id и URL возврата
                const currentUrl = window.location.href;
                window.location.href = `{{ route('trainings.create') }}?manual_id=${manualId}&return_url=${encodeURIComponent(currentUrl)}`;
            }
        }

        function updateTrainings(manualId, lastTrainingDate) {
            if (confirm('Update trainings for this unit? This will create missing trainings based on the last training date.')) {
                // Используем ту же логику, что и в trainings.index
                const lastTraining = new Date(lastTrainingDate);
                const lastTrainingYear = lastTraining.getFullYear();
                const lastTrainingWeek = getWeekNumber(lastTraining);
                const currentYear = new Date().getFullYear();
                const currentDate = new Date();

                let trainingData = {
                    manuals_id: [],
                    date_training: [],
                    form_type: []
                };

                // Генерируем данные для создания тренингов за следующие годы
                for (let year = lastTrainingYear + 1; year <= currentYear; year++) {
                    const trainingDate = getDateFromWeekAndYear(lastTrainingWeek, year);

                    // Проверяем, что дата тренировки не в будущем
                    if (trainingDate <= currentDate) {
                        trainingData.manuals_id.push(manualId);
                        trainingData.date_training.push(trainingDate.toISOString().split('T')[0]);
                        trainingData.form_type.push('112');
                    }
                }

                if (trainingData.manuals_id.length === 0) {
                    alert('No missing trainings to create. All possible training dates are in the future.');
                    return;
                }

                // Проверяем, сколько лет пропущено
                const yearsMissed = currentYear - lastTrainingYear;
                if (yearsMissed > 3) {
                    const warningMessage = `WARNING: ${yearsMissed} years have passed since last training!\n\n` +
                        `This will create ${trainingData.manuals_id.length} training records.\n\n` +
                        `Are you sure you want to create trainings for such a long period?\n\n` +
                        `Consider if this is correct or if you need to create new initial training instead.`;

                    if (!confirm(warningMessage)) {
                        return;
                    }
                }

                // Подготовка сообщения для подтверждения
                let confirmationMessage = "Will create trainings:\n";
                trainingData.manuals_id.forEach((id, index) => {
                    const year = lastTrainingYear + index + 1;
                    const dateStr = trainingData.date_training[index];
                    confirmationMessage += `\nTraining for ${year}:\n`;
                    confirmationMessage += `Date: ${dateStr}\n`;
                    confirmationMessage += `Form: 112\n`;
                });

                // Добавляем информацию о форме 132
                confirmationMessage += `\nNote: Form 132 will be created only if it doesn't exist for this unit.`;

                if (confirm(confirmationMessage + "\nContinue?")) {
                    fetch('{{ route('trainings.createTraining') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(trainingData)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            let message = `Trainings processed!\nCreated: ${data.created}`;
                            if (data.skipped > 0) {
                                message += `\nSkipped (already exist): ${data.skipped}`;
                            }
                            alert(message);
                            location.reload();
                        } else {
                            alert('Error creating trainings: ' + (data.message || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred: ' + error.message);
                    });
                }
            }
        }

        // Вспомогательные функции для расчета дат
        function getWeekNumber(d) {
            const oneJan = new Date(d.getFullYear(), 0, 1);
            const numberOfDays = Math.floor((d - oneJan) / (24 * 60 * 60 * 1000));
            return Math.ceil((numberOfDays + oneJan.getDay() + 1) / 7);
        }

        function getDateFromWeekAndYear(week, year) {
            const firstJan = new Date(year, 0, 1);
            const days = (week - 1) * 7 - firstJan.getDay() + 1;
            return new Date(year, 0, 1 + days);
        }

        // Функция обновления тренировки на сегодняшнюю дату
        function updateTrainingToToday(manualId, lastTrainingDate, autoUpdate = false) {
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            // Если сегодня пятница - используем сегодня, иначе последнюю прошедшую пятницу
            let trainingDate;
            if (today.getDay() === 5) { // 5 = пятница
                trainingDate = today;
            } else {
                // Находим последнюю прошедшую пятницу
                const dayOfWeek = today.getDay();
                let daysToSubtract;
                if (dayOfWeek === 0) { // Воскресенье - пятница была вчера (1 день назад)
                    daysToSubtract = 1;
                } else if (dayOfWeek === 6) { // Суббота - пятница была вчера (1 день назад)
                    daysToSubtract = 1;
                } else { // Понедельник-четверг - пятница была (dayOfWeek + 2) дней назад
                    daysToSubtract = dayOfWeek + 2;
                }
                trainingDate = new Date(today);
                trainingDate.setDate(today.getDate() - daysToSubtract);
            }

            const todayStr = trainingDate.toISOString().split('T')[0];
            const lastTraining = new Date(lastTrainingDate);
            const monthsDiff = Math.floor((today - lastTraining) / (1000 * 60 * 60 * 24 * 30));

            // Если автоматическое обновление, не показываем подтверждение
            if (!autoUpdate) {
                const confirmationMessage = `Update training to today's date?\n\n` +
                    `Last training: ${lastTrainingDate} (${monthsDiff} months ago)\n` +
                    `New training date: ${todayStr}\n\n` +
                    `This will create a new training record and update the training status.`;

                if (!confirm(confirmationMessage)) {
                    return;
                }
            }

            const trainingData = {
                manuals_id: [manualId],
                date_training: [todayStr],
                form_type: ['112']
            };

            fetch('{{ route('trainings.updateToToday') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(trainingData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (!autoUpdate) {
                        alert(`Training updated to today!\nCreated: ${data.created} training record(s)`);
                    }
                    location.reload();
                } else {
                    if (!autoUpdate) {
                        alert('Error updating training: ' + (data.message || 'Unknown error'));
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (!autoUpdate) {
                    alert('An error occurred: ' + error.message);
                }
            });
        }

        // Предложение обновить тренинг при загрузке страницы, если больше 12 месяцев
        @if($trainings && $trainings->date_training && $user->id == $user_wo)
            @php
                $trainingDate = \Carbon\Carbon::parse($trainings->date_training);
                $monthsDiff = $trainingDate->diffInMonths(now());
            @endphp
            @if($monthsDiff > 12)
                document.addEventListener('DOMContentLoaded', function() {
                    // Предлагаем обновить тренинг на сегодняшнюю дату
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);

                    let trainingDateStr;
                    if (today.getDay() === 5) {
                        trainingDateStr = today.toISOString().split('T')[0];
                    } else {
                        const dayOfWeek = today.getDay();
                        let daysToSubtract;
                        if (dayOfWeek === 0 || dayOfWeek === 6) {
                            daysToSubtract = 1;
                        } else {
                            daysToSubtract = dayOfWeek + 2;
                        }
                        const lastFriday = new Date(today);
                        lastFriday.setDate(today.getDate() - daysToSubtract);
                        trainingDateStr = lastFriday.toISOString().split('T')[0];
                    }

                    const confirmationMessage = `Last training was ${monthsDiff} months ago ({{ $trainings->date_training }}).\n\n` +
                        `Would you like to update training to ${trainingDateStr}?\n\n` +
                        `This will create a new training record and update the training status.`;

                    if (confirm(confirmationMessage)) {
                        updateTrainingToToday({{ $manual_id }}, '{{ $trainings->date_training }}', false);
                    }
                });
            @endif
        @endif

    </script>

@endsection
