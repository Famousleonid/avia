@extends('admin.master')

@section('content')
    <style>
        /*.container {*/
        /*    max-width: 900px;*/
        /*}*/
    </style>

    <div class="card bg-gradient">
        <div class="card-header  m-1 shadow">
            <h5 class="text-primary ps-4">{{__('Work Order')}} <span class="text-success ps-3">{{$current_wo->number}}
                </span></h5>
            <div class="d-flex justify-content-between">
                <div>
                    <div class="d-flex ">
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
                <div class=" ps-1 pt-1 pb-1">
{{--                    <h5 class="border-bottom pb\-2 ps-3 ">WO Inspection</h5>--}}

                    <div class="d-flex justify-content-between ">

                        <div class="mt-1 ">
                            <button class="btn btn-outline-primary mb-3 " style="height: 40px; width: 280px"
                                                   data-bs-toggle="modal"
                                                   data-bs-target="#addWoInspectModal">
                                        <h5>{{__('WO Inspection')}}</h5>
                            </button>
                            <div class="d-flex ps-2  pt-2">
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


                        </div>
                        <div class="">
                            <div class="d-flex pt-5">
                                <div style="width: 250px">{{'Disassembly Upon Arrival  '}}</div>
                                <div style="width: 50px">
                                    @if($current_wo->disassembly_upon_arrival)
                                        <i class="bi bi-check-square"></i>
                                    @else
                                        <i class="bi bi-square"></i>
                                    @endif
                                </div>
                            </div>
                            <div class="d-flex ">
                                <div style="width: 250px">{{'Name Plate Missing  '}}</div>
                                <div style="width: 50px">
                                    @if($current_wo->nameplate_missing)
                                        <i class="bi bi-check-square"></i>
                                    @else
                                        <i class="bi bi-square"></i>
                                    @endif
                                </div>
                            </div>
                            <div class="d-flex ">
                                <div style="width: 250px">{{'Preliminary Test - False  '}}</div>
                                <div style="width: 50px">
                                    @if($current_wo->preliminary_test_false)
                                        <i class="bi bi-check-square"></i>
                                    @else
                                        <i class="bi bi-square"></i>
                                    @endif
                                </div>
                            </div>
                            <div class="d-flex ">
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
                    </div>

                </div>


                <div>
                    <div class="d-flex ">
                        <div style="width: 100px">{{' '}}</div>

                    </div>
                </div>

            </div>
        </div>



    </div>

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
