@extends('admin.master')

@section('content')

    <style>
        .div_error {
            border: 1px solid red;
        }

        .select2-container .select2-selection--single {
            height: 38px;
            color: #444;
            text-decoration: none;
            border-radius: 4px;
            background-color: #fff;
            background-image: linear-gradient(top, #fff 0%, #eee 50%);
        }

        .select2-container--classic .select2-selection--single .select2-selection__arrow {
            height: 35px;
            border-left: none;
        }

        .select2-container--classic .select2-selection--single .select2-selection__rendered {
            line-height: 35px;
        }

    </style>

    <div class="container pl-3 pr-3 mt-2">
        <div class="card firm-border p-2 bg-white shadow">

            <form id="createForm" class="createForm" role="form" method="POST" action="{{route('admin-workorders.update', ['admin_workorder' => $current_wo->id]) }}" enctype="multipart/form-data">

                @csrf
                @method('PUT')

                <input type="text" hidden name="user_id" value="{{$user->id}}">

                <div class="tab-content">

                    <div class="active tab-pane" id="create_firms">
                        <div class="col-md-12">

                            <div class="card-body row" id="create_div_inputs">

                                <div class="form-group col-lg-3 mb-1">
                                    <label for="number_id">Workorder №</label>
                                    <input hidden type="text" name="number" id="number_id" class="form-control" value="{{$current_wo->number}}">
                                    <h4 style="color: darkblue"> {{$current_wo->number}}</h4>
                                </div>


                                <div class="form-group col-lg-3 mb-1">
                                    <label for="unit_id">Unit <a id="new_unit_create" href="{{route('unit.create')}}" class="pl-2"><img src="{{asset('img/plus.png')}}" width="22px" alt="" data-toggle="tooltip" data-placement="top" title="Add new unit"></a></label>
                                    <select name="unit_id" id="unit_id" class="form-control">
                                        <option hidden selected value="{{$current_wo->unit_id}}">{{$current_wo->unit->partnumber}}</option>
                                        @foreach ($units as $unit)
                                            <option value="{{$unit->id}}" data-lib="{{$unit->lib}}" data-description="{{$unit->description}}">{{$unit->partnumber}}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group col-lg-1 mb-1">
                                    <label for="lib">Library</label>
                                    <input type="text" name="manual" id="lib" maxlength="15" value="{{$current_wo->manual}}" class="form-control @error('lib') is-invalid @enderror">
                                </div>

                                <div class="form-group col-lg-3 mb-1">
                                    <label for="customer_id">Customer </label>
                                    <select name="customer_id" id="customer_id" class="form-control">
                                        <option hidden selected value="{{$current_wo->customer_id}}">{{$current_wo->customer->name}}</option>
                                        @foreach ($customers as $customer)
                                            <option value="{{$customer->id}}">{{$customer->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-lg-2 mb-1">
                                    <label for="instruction_id">Instruction </label>
                                    <select name="instruction_id" id="instruction_id" class="form-control">
                                        <option hidden selected value="{{$current_wo->instruction_id}}">{{$current_wo->instruction->name}}</option>
                                        @foreach ($instructions as $instruction)
                                            <option value="{{$instruction->id}}">{{$instruction->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-lg-3 mt-2">
                                    <label for="number_id">Serial number</label>
                                    <input type="text" name="serial_number" id="serial_number" value="{{$current_wo->serial_number}}" class="form-control @error('serial_number') is-invalid @enderror">
                                </div>
                                <div class="form-group col-lg-9 mt-2">
                                    <label for="unit_description">Description</label>
                                    <input type="text" name="description" id="unit_description" maxlength="100" value="{{$current_wo->description}}" class="form-control @error('description') is-invalid @enderror">
                                </div>


                                <div class=" col-12 border mb-1 mt-3 border-info rounded">
                                    <div class="card-header p-1">
                                        <h3 class="card-title text-info">Note</h3>
                                    </div>
                                    <div class="row">
                                            <textarea name="notes"
                                                      rows="7"
                                                      style="width: 100%; resize:none; padding: 10px; "
                                                      value="{{$current_wo->notes}}">{{$current_wo->notes}}
                                            </textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group container-fluid ">
                                <div class="card-body row ">
                                    <div class="col-lg-3 mb-1">
                                        <button id="ntSaveFormsSubmit" type="submit" class="btn btn-primary btn-block ntSaveFormsSubmit">Save</button>
                                    </div>
                                    <div class="col-lg-3 mb-1 ml-auto">
                                        <a href="{{ route('admin-workorders.index') }}" class="btn btn-secondary btn-block">Cancel</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

@endsection()

@section('scripts')


    <script>

        var selection = document.getElementById("unit_id");
        selection.onchange = function (event) {
            document.getElementById("lib").value = event.target.options[event.target.selectedIndex].dataset.lib;
            document.getElementById("unit_description").value = event.target.options[event.target.selectedIndex].dataset.description;
        };


        document.getElementById("ntSaveFormsSubmit").addEventListener("click", function (event) {
            form.submit();
            showLoadingSpinner()
        });

        $(document).ready(function () {
            $('#unit_id').select2({
                theme: "classic",
            });
        });
    </script>
@endsection
