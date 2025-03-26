@extends('admin.master')

@section('content')
    <style>
        /* Ваши стили */
    </style>
    <div class="container mt-3">
        <div class="card bg-gradient">
            <div class="card-header">
                <h4 class="text-primary">{{__('Add Unit Inspection')}}</h4>
                <h4 class="text-primary"> {{__('Work Order')}} {{$current_wo->number}}</h4>
            </div>
            <div class="card-body">
                <form id="createForm" class="createForm" role="form" method="POST"
                      action="{{route('admin.tdrs.store')}}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="workorder_id" value="{{$current_wo->id }}">
                    <input type="hidden" name="use_tdr" value="true">

                    <!-- Только содержимое unitGroup -->
                    <div class="form-group m-2">
                        <label for="u_conditions_id" class="form-label pe-2">Condition</label>
                        <select name="conditions_id" id="u_conditions_id" class="form-control" style="width:575px">
                            <option selected value="">---</option>
                            @foreach($unit_conditions as $unit_condition)
                                @if($unit_condition->name != 'PARTS MISSING UPON ARRIVAL AS INDICATED ON PARTS LIST')
                                    <option value="{{ $unit_condition->id }}"
                                            data-title="{{$unit_condition->name}}">
                                        {{$unit_condition->name}}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-link" data-bs-toggle="modal"
                                data-bs-target="#addConditionModal">{{ __('Add Condition') }}
                        </button>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-outline-primary mt-3">{{ __('Save') }}</button>
                        <a href="{{ route('admin.tdrs.show', ['tdr'=>$current_wo->id]) }}"
                           class="btn btn-outline-secondary mt-3">{{ __('Cancel') }} </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Модальные окна -->
    @include('admin.tdrs.partials.condition-modal')

    <script>
        // JavaScript специфичный для unit inspection
    </script>
@endsection
