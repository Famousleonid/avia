@extends('admin.master')

@section('content')

    <style>

        /* ----------------------------------- Select 2 Dark Theme -------------------------------------*/

        html[data-bs-theme="dark"] .select2-selection--single {
            background-color: #121212 !important;
            color: gray !important;
            height: 38px !important;
            border: 1px solid #495057 !important;
            align-items: center !important;
            border-radius: 8px;
        }

        html[data-bs-theme="dark"] .select2-container .select2-selection__rendered {
            color: white;
            line-height: 2.2 !important;
        }

        html[data-bs-theme="dark"] .select2-search--dropdown .select2-search__field {
            background-color: #343A40 !important;
        }

        html[data-bs-theme="dark"] .select2-container--default .select2-selection--single .select2-selection__rendered {
            padding-right: 25px;
        }

        html[data-bs-theme="dark"] .select2-container .select2-dropdown {
            max-height: 40vh !important;
            overflow-y: auto !important;
            border: 1px solid #ccc !important;
            border-radius: 8px;
            color: white;
            background-color: #121212 !important;
        }

        html[data-bs-theme="light"] .select2-container .select2-dropdown {
            max-height: 40vh !important;
            overflow-y: auto !important;

        }

        html[data-bs-theme="dark"] .select2-container .select2-results__option:hover {
            background-color: #6ea8fe;
            color: #000000;

        }

        .select2-container .select2-selection__clear {
            position: absolute !important;
            right: 10px !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            z-index: 1;

        }

        /* -------------------------------------------------------------------------------------------*/

        .checkbox-wo {
            font-size: 1rem;
        }

        .checkbox-wo input[type="checkbox"] {
            width: 20px;
            height: 20px;
            margin-right: 1px;
        }


    </style>

    <div class="container pl-3 pr-3 mt-5">
        <div class="card  p-2 shadow bg-gradient">

            <form id="createForm" class="createForm" role="form" method="POST" action="{{route('admin.workorders.update',['workorder' => $current_wo->id])}}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="tab-content">

                    <div class="active tab-pane" id="create_firms">
                        <div class="col-md-12">

                            <div class="card-header row">
                                <p class="text-bold">Edit workorder number: ( &nbsp;&nbsp;
                                    <span class="text-info" style="font-size: 1.2rem">{{$current_wo->number }}</span>&nbsp;&nbsp;&nbsp;)
                                </p>
                            </div>

                            <div class="card-body row" id="create_div_inputs">

                                <div class="col-lg-9 row">

                                    <div class="row ">
                                        <div class="form-group col-lg-4 mb-1">
                                            <label class="mb-1" for="number_id">Workorder № </label>
                                            <input type="text" id="number" name="number" value="{{ old('number', $current_wo->number) }}" class="form-control" readonly>
                                        </div>

                                        <div class="form-group col-lg-4 mb-1">
                                            <label for="unit_id">Unit
                                                <a id="new_unit_create" class="ms-3" data-bs-toggle="modal" data-bs-target="#addUnitModal">
                                                    <img class="mb-1" src="{{asset('img/plus.png')}}" width="20px" alt="" data-toggle="tooltip" data-placement="top" title="Add new unit">
                                                </a>
                                            </label>
                                            <select name="unit_id" id="unit_id" class="form-control">
                                                <option selected value="{{ old('unit_id', $current_wo->unit_id) }}">{{ old('part_number', $current_wo->unit->part_number) }}</option>
                                                @foreach ($units as $unit)
                                                    <option value="{{$unit->id}}">{{$unit->part_number}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group col-lg-4 mb-1">
                                            <label class="mb-1" for="customer_id">Customer</label>
                                            <select name="customer_id" id="customer_id" class="form-select">
                                                <option selected value="{{ old('customer_id', $current_wo->customer_id) }}">{{ old('name', $current_wo->customer->name) }}</option>
                                                @foreach ($customers as $customer)
                                                    <option value="{{$customer->id}}">{{$customer->name}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="form-group col-lg-4 mb-1">
                                            <label class="mb-1" for="instruction_id">Instruction </label>
                                            <select name="instruction_id" id="instruction_id" class="form-select">
                                                <option selected value="{{ old('instruction_id', $current_wo->instruction_id) }}">{{ old('instruction_id', $current_wo->instruction->name) }}</option>
                                                @foreach ($instructions as $instruction)
                                                    <option value="{{$instruction->id}}">{{$instruction->name}}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="form-group col-lg-4 mt-2">
                                            <label for="number_id">Serial number</label>
                                            <input type="text" name="serial_number" id="serial_number" value="{{old('serial_number', $current_wo->serial_number)}}" class="form-control">
                                        </div>

                                        <div class="form-group col-lg-4 mt-2">
                                            <label for="unit_description">Description</label>
                                            <input type="text" name="description" id="wo_description" value={{old('description', $current_wo->description)}} class="form-control @error('description') is-invalid @enderror">
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="form-group col-lg-4 mt-2">
                                            <label for="unit_amdt">Amdt</label>
                                            <input type="text" name="amdt" id="wo_amdt" maxlength="30" value="{{ old('amdt', $current_wo->amdt) }}" class="form-control @error('amdt') is-invalid @enderror">
                                        </div>

                                        <div class="form-group col-lg-4 mt-2">
                                            <label for="unit_place">Place</label>
                                            <input type="text" name="place" id="wo_place" maxlength="30" value={{old('place', $current_wo->place)}} class="form-control @error ('place') is-invalid @enderror" >
                                        </div>

                                        <div class="form-group col-lg-4 mt-2">
                                            <label for="unit_open_at">Open date</label>
                                            <input type="date" name="open_at" id="open_at" value="{{ old('open_at', $open_at) }}" class="form-control @error('open_at') is-invalid @enderror">
                                            @error('open_at')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                    </div>

                                    <div class="row ">

                                        <div class="form-group col-lg-4 offset-4 mt-2">
                                            <label for="instruction_id">Technik</label>
                                            <select name="user_id" id="user_id" class="form-select">
                                                <option selected value="{{ old('user_id', $current_wo->user_id) }}">{{ old('user_id', $current_wo->user->name) }}</option>
                                                @foreach ($users as $user)
                                                    <option value="{{ $user->id }}"
                                                            @if(isset($currentUser) && $user->id == $currentUser->id) selected @endif>
                                                        {{ $user->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                    </div>

                                </div>

                                <div class="col-lg-3 row">

                                    <div class="form-group">
                                        <label class="checkbox-wo mb-2"><input type="checkbox" name="part_missing" {{ $current_wo->part_missing ? 'checked' : '' }}>___ Parts Missing</label><br>
                                    </div>
                                    <div class="form-group">
                                        <label class="checkbox-wo mb-2"><input type="checkbox" name="external_damage" {{ $current_wo->external_damage ? 'checked' : '' }}>___ External damage</label><br>
                                    </div>
                                    <div class="form-group">
                                        <label class="checkbox-wo mb-2"><input type="checkbox" name="received_disassembly" {{ $current_wo->received_disassembly ? 'checked' : '' }}>___ Received disassembly</label><br>
                                    </div>
                                    <div class="form-group">
                                        <label class="checkbox-wo mb-2"><input type="checkbox" name="disassembly_upon_arrival" {{ $current_wo->disassembly_upon_arrival ? 'checked' : '' }}>___ Disassembly upon arrival</label><br>
                                    </div>
                                    <div class="form-group">
                                        <label class="checkbox-wo mb-2"><input type="checkbox" name="nameplate_missing" {{ $current_wo->nameplate_missing ? 'checked' : '' }}>___ Nameplate missing</label><br>
                                    </div>
                                    <div class="form-group">
                                        <label class="checkbox-wo mb-2"><input type="checkbox" name="preliminary_test_false" {{ $current_wo->preliminary_test_false ? 'checked' : '' }}>___ Preliminary test false</label><br>
                                    </div>
                                    <div class="form-group">
                                        <label class="checkbox-wo mb-2"><input type="checkbox" name="extra_parts" {{ $current_wo->extra_parts ? 'checked' : '' }}>___ Extra parts</label><br>
                                    </div>

                                </div>

                            </div>


                            <div class="form-group container-fluid ">
                                <div class="card-body row ">
                                    <div class="col col-lg-1  mb-1">
                                        <button id="ntSaveFormsSubmit" type="submit" class="btn btn-outline-primary btn-block ntSaveFormsSubmit"> Update</button>
                                    </div>
                                    <div class="col col-lg-1 offset-6 mb-1 ">
                                        <a href="{{ route('admin.workorders.index') }}" class="btn btn-outline-secondary btn-block"> Cancel </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>




    <!-- Модальное окно add Unit -->
    <div class="modal fade" id="addUnitModal" tabindex="-1" aria-labelledby="addUnitLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUnitLabel">Add Unit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="cmmSelect" class="form-label">CMM</label>
                        <select class="form-select" id="cmmSelect" name="cmmSelect">
                            <option value="">{{ __('Select CMM') }}</option>
                            @foreach($manuals as $manual)
                                <option value="{{ $manual->id }}"> ({{ $manual->number }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="pnInputs">
                        <div class="input-group mb-2 pn-field">
                            <input type="text" class="form-control"
                                   placeholder="Enter PN" style="width: 200px;"
                                   name="pn[]">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close
                    </button>
                    <button type="button" id="createUnitBtn" class="btn btn-outline-primary"> Add Unit
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection()

@section('scripts')


    <script>

        window.addEventListener('load', function () {

            // --------------------------------- Select 2 --------------------------------------------------------

            $(document).ready(function () {
                $('#unit_id').select2({
                    placeholder: '---',
                    theme: 'bootstrap-5',
                    allowClear: true
                });
            });
            $(function () {
                applyTheme();
            });

            function applyTheme() {
                const isDark = document.documentElement.getAttribute('data-bs-theme');
                const selectContainer = $('.select2-container');
                if (isDark === 'dark') {
                    selectContainer.addClass('select2-dark').removeClass('select2-light');
                    $('.select2-container .select2-dropdown').addClass('select2-dark').removeClass('select2-light');
                } else {
                    selectContainer.addClass('select2-light').removeClass('select2-dark');
                    $('.select2-container .select2-dropdown').addClass('select2-light').removeClass('select2-dark');
                }
            }

            // -----------------------------------------------------------------------------------------------------


        });


    </script>
@endsection
