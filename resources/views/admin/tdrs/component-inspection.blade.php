@extends('admin.master')

@section('content')
    <style>
        /* Ваши стили */
    </style>
    <div class="container mt-3">
        <div class="card bg-gradient">
            <div class="card-header">
                <h4 class="text-primary">{{__('Add Component Inspection')}}</h4>
                <h4 class="text-primary"> {{__('Work Order')}} {{$current_wo->number}}</h4>
            </div>
            <div class="card-body">
                <form id="createForm" class="createForm" role="form" method="POST"
                      action="{{route('admin.tdrs.store')}}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="workorder_id" value="{{$current_wo->id }}">

                    <!-- Только содержимое componentGroup -->
                    <div class="form-group d-flex">
                        <label for="component_id" class="form-label me-2">Component</label>
                        <select name="component_id" id="component_id" class="form-control" style="width: 300px">
                            <option selected value="">---</option>
                            @foreach($components as $component)
                                <option value="{{ $component->id }}"
                                        data-has_assy_part_number="{{ $component->assy_part_number ? 'true' : 'false' }}"
                                        data-title="{{$component->name}}">
                                    {{$component->part_number}} ({{ $component->name }})
                                </option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-link" data-bs-toggle="modal"
                                data-bs-target="#addComponentModal">{{ __('Add Component') }}
                        </button>
                    </div>

                    <!-- Остальные поля component inspection -->
                    <!-- ... -->

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
    @include('admin.tdrs.partials.component-modal')

    <script>
        // JavaScript специфичный для component inspection
    </script>
@endsection
