@extends('cabinet.master')

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

            <form id="createForm" class="createForm" role="form" method="post" action="{{route('workorder.store')}}" enctype="multipart/form-data">
                @csrf

                <div class="tab-content">

                    <div class="active tab-pane" id="create_firms">
                        <div class="col-md-12">

                            <div class="card-header row">

                                <span class="text-info text-bold" style="font-size: 1.2rem">Create workorder&nbsp;&nbsp;</span>
                                <span class="text-info text-bold" style="font-size: 1.2rem">for user: &nbsp;&nbsp;</span>
                                <span class="text-indigo text-bold" style="font-size: 1.2rem">{{$current_user->name}}</span>


                            </div>

                            <div class="card-body row" id="create_div_inputs">

                                <div class="form-group col-lg-3 mb-1">
                                    <label for="number_id">Workorder № <span style="color:red; font-size: x-small">(required)</span></label>

                                    <input type="number" name="number" id="number_id" class="form-control @error('number') is-invalid @enderror" placeholder="Enter workorder number ">
                                </div>


                                <div id="div_select_unit" class="form-group col-lg-3 mb-1">
                                    <label for="unit_id">Unit <span style="color:red; font-size: x-small">(required)</span><a id="new_unit_create" href="{{route('unit.create')}}" class="pl-2"><img src="{{asset('img/plus.png')}}" width="22px" alt="" data-toggle="tooltip" data-placement="top" title="Add new unit"></a></label>
                                    <select name="unit_id" id="unit_id" class="form-control">
                                        <option disabled selected value=""> -- select an option --</option>
                                        @foreach ($units as $unit)
                                            <option value="{{$unit->id}}" data-lib="{{$unit->lib}}" data-description="{{$unit->description}}">{{$unit->partnumber}}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group col-lg-1 mb-1">
                                    <label for="lib">Library</label>
                                    <input type="text" name="manual" id="lib" maxlength="15" value="" class="form-control @error('lib') is-invalid @enderror" placeholder="">
                                </div>

                                <div class="form-group col-lg-3 mb-1">
                                    <label for="customer_id">Customer <span style="color:red; font-size: x-small">(required)</span></label>
                                    <select name="customer_id" id="customer_id" class="form-control">
                                        <option disabled selected value> -- select an option --</option>
                                        @foreach ($customers as $customer)
                                            <option value="{{$customer->id}}">{{$customer->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-lg-2 mb-1">
                                    <label for="instruction_id">Instruction <span style="color:red; font-size: x-small">(required)</span></label>
                                    <select name="instruction_id" id="instruction_id" class="form-control">
                                        <option disabled selected value> -- select an option --</option>
                                        @foreach ($instructions as $instruction)
                                            <option value="{{$instruction->id}}">{{$instruction->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-lg-3 mt-2">
                                    <label for="serial_number">Serial number</label>
                                    <input type="text" name="serial_number" id="serial_number" class="form-control @error('serial_number') is-invalid @enderror" placeholder="S/N">
                                </div>
                                <div class="form-group col-lg-3 mt-2">
                                    <label for="user_id">Technik</label>
                                    <select name="user_id" id="user_id" class="form-control">
                                        <option selected value="{{$current_user->id}}"> {{$current_user->name}}</option>
                                        @foreach ($users as $user)
                                            <option value="{{$user->id}}">{{$user->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-lg-3 mt-2">
                                    <label for="place">Place</label>
                                    <input type="text" name="place" id="place" class="form-control @error('place') is-invalid @enderror" placeholder="A1-D2-C3">
                                </div>
                                <div class="form-group col-lg-3 mt-2">
                                    <label for="date_created_at">Date of creation</label>
                                    <input type="date" name="created_at" id="date_created_at" value="{{ date('Y-m-d') }}" class="form-control @error('created_at') is-invalid @enderror">
                                </div>
                                <div class="form-group col-lg-12 mt-2">
                                    <label for="unit_description">Description</label>
                                    <input type="text" name="description" id="unit_description" maxlength="30" value="" class="form-control @error('description') is-invalid @enderror" placeholder="">
                                </div>

                            </div>

                            <div class="form-group container-fluid ">
                                <div class="card-body row ">
                                    <div class="col-lg-3 mb-1">
                                        <button id="ntSaveFormsSubmit" type="submit" class="btn btn-primary btn-block ntSaveFormsSubmit">Save</button>
                                    </div>
                                    <div class="col-lg-3 mb-1 ml-auto">
                                        <a href="{{ route('cabinet.index')}}" class="btn btn-secondary btn-block">Cancel</a>
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

        let selection = document.getElementById("unit_id");
        selection.onchange = function (event) {
            document.getElementById("lib").value = event.target.options[event.target.selectedIndex].dataset.lib;
            document.getElementById("unit_description").value = event.target.options[event.target.selectedIndex].dataset.description;
        };

        function check1() {
            if ($('#number_id').val() == "") {
                $(('#number_id')).addClass('is-invalid');
                setTimeout("$(('#number_id')).removeClass('is-invalid')", 3000);
                return false;
            } else {
                return true
            }
        }

        function check2() {
            if ($('#unit_id').val() == null) {
                $(('#div_select_unit')).addClass('div_error');
                setTimeout("$(('#div_select_unit')).removeClass('div_error')", 3500);
                return false;
            } else {
                return true;
            }
        }

        function check3() {
            if ($('#customer_id').val() == null) {
                $(('#customer_id')).addClass('is-invalid');
                setTimeout("$(('#customer_id')).removeClass('is-invalid')", 4500);
                return false;
            } else {
                return true
            }
        }

        function check4() {
            if ($('#instruction_id').val() == null) {
                $(('#instruction_id')).addClass('is-invalid');
                setTimeout("$(('#instruction_id')).removeClass('is-invalid')", 4500);
                return false;
            } else {
                return true
            }
        }

        document.getElementById("ntSaveFormsSubmit").addEventListener("click", function (event) {
            let form = document.getElementById("createForm");

            check1();
            check2();
            check3();
            check4();

            if (!(check1() && check2() && check3() && check4())) {
                event.preventDefault();

            } else {
                form.submit();
                showLoadingSpinner()
            }
        });

        $(document).ready(function () {
            $('#unit_id').select2({
                placeholder: 'Select an unit',
                theme: "classic",
            });
        });
    </script>
@endsection
