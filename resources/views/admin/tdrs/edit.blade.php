@extends('admin.master')

@section('content')
    <style>
        .container {
            max-width: 700px;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-control {
            width: 100%;
        }
    </style>

    <div class="container mt-3">
        <div class="card bg-gradient">
            <div class="card-header my-1 shadow ">
               <div class="d-flex justify-content-between">
                   <h4 class="text-primary">{{ __('Edit') }}</h4>
                   <h4>{{ __('Work Order:') }} {{ $current_tdr->workorder->number }}</h4>
               </div>
                <div class="d-flex justify-content-between">
                    <div class="ms-2">
                        <h6 style="margin-left: 64px">{{ __('Component:') }} {{ $current_tdr->component->name }}</h6>
                        <h6 style="margin-left: 64px">{{ __('PN:') }} {{ $current_tdr->component->part_number }}</h6>
                        <h6 style="margin-left: 64px">{{ __('IPL:') }} {{ $current_tdr->component->ipl_num }}</h6>
                    </div>
                    <div class="me-4" >
                        @foreach($manuals as $manual)
                            @if($manual->id == $current_tdr->workorder->unit->manual_id)
                                <div style="margin-left: 23px">{{__('Unit')}} {{ $manual->title }}</div>
                                <div style="margin-left: 30px">{{__('PN:')}} {{ $current_tdr->workorder->unit->part_number }}
                                    {{__('SN:')}} {{ $current_tdr->serial_number }}
                                </div>
                                <div style="margin-left: 13px">{{__('CMM:')}} {{ $manual->number }}</div>
                            @endif
                        @endforeach
                    </div>
                </div>


            </div>

            <div class="card-body">
                <form id="editForm" class="editForm" role="form" method="POST"
                      action="{{ route('tdrs.update', $current_tdr->id) }}"
                      enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <input type="hidden" name="workorder_id" value="{{ $current_tdr->workorder->id }}">
                    <input type="hidden" name="use_process_forms" value="{{ $current_tdr->use_process_forms }}">

                    <div class="form-group d-flex justify-content-center">
                        <div class="m-2" style="width: 250px">
                            <label for="serial_number">{{ __('Serial Number') }}</label>
                            <input id="serial_number" type="text" value="{{ $current_tdr->serial_number }}"
                                   class="form-control" name="serial_number">
                        </div>
                        <div class="mt-2" style="width: 250px">
                            @if($current_tdr->assy_serial_number != null)
                                <div id="assy_serial_number_container">
                                    <label for="assy_serial_number">{{ __('Assy Serial Number') }}</label>
                                    <input id="assy_serial_number" type="text" value="{{ $current_tdr->assy_serial_number }}"
                                           class="form-control" name="assy_serial_number">
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="d-flex justify-content-center">
                        <div class="form-group mt-2">
                            <label for="codes_id" class="form-label pe-2">Code Inspection</label>
                            <select name="codes_id" id="codes_id" class="form-control" style="width: 250px">
                                @foreach($codes as $code)
                                    <option value="{{ $code->id }}" {{ $code->id == $current_tdr->codes_id ? 'selected' : '' }}>
                                        {{ $code->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mt-2" id="necessary">
                            <label for="necessaries_id" class="form-label pe-2">Necessary to Do</label>
                            <select name="necessaries_id" id="necessaries_id" class="form-control" style="width: 250px">
                                @foreach($necessaries as $necessary)
                                    <option value="{{ $necessary->id }}" {{ $necessary->id == $current_tdr->necessaries_id ? 'selected' : '' }}>
                                        {{ $necessary->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div >

                    </div>
                    <div class="mt-2 " style="width: 500px;margin-left: 70px">
                        <label for="description">{{ __('Description') }}</label>
                        <input id="description" type="text" value="{{ $current_tdr->description }}"
                               class="form-control mt-1" name="description">
                    </div>
                    <div class="text-end mt-2">
                        <button type="submit" class="btn btn-outline-primary mt-3">{{ __('Update') }}</button>
                        <a href="{{ route('tdrs.show', ['id'=>$current_tdr->workorder->id]) }}"
                        class="btn btn-outline-secondary mt-3" >{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
