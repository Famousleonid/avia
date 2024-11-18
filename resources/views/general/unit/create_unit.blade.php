@extends('cabinet.master')

@section('content')


    <div class="container pl-3 pr-3 mt-2">
        <div class="card firm-border p-2 bg-white shadow">

            <form id="createForm" class="" method="post" action="{{route('unit.store')}}"
            @csrf
            <div class="card">
                <div class="col-md-12">

                    <div class="card-header row">
                        <span class="text-info text-bold" style="font-size: 1.2rem">Create Unit&nbsp;&nbsp;</span>
                    </div>

                    <div class="card-body " id="create_div_inputs">

                        <div class="form-group row ">
                            <label for="partnumber" class="col-sm-2 col-form-label">Part number</label> <span class="col-sm-1 pt-3 pl-0" style="color:red; font-size: x-small">(required)</span>
                            <div class="col-sm-9"><input id="partnumber" type="text" name="partnumber" class="form-control" placeholder="e.g. 4259A0000-02" @error('purtnumber') is-invalid @enderror"></div>
                        </div>
                        <div class="form-group row ">
                            <label for="description" class="col-sm-2 col-form-label">Description</label> <span class="col-sm-1 pt-3 pl-0" style="color:red; font-size: x-small">(required)</span>
                            <div class="col-sm-9"><input id="description" type="text" name="description" class="form-control" placeholder="e.g. MLG Shock Strut LH" @error('description') is-invalid @enderror"></div>
                        </div>
                        <div class="form-group row ">
                            <label for="manufacturer" class="col-sm-3 col-form-label">Manufacturer</label>
                            <div class="col-sm-9"><input id="manufacturer" type="text" name="manufacturer" class="form-control" placeholder="e.g. LIEBHERR or SAFRAN LS or GOODRICH or ..." @error('manufacturer') is-invalid @enderror"></div>
                        </div>
                        <div class="form-group row ">
                            <label for="lib" class="col-sm-2 col-form-label">Lib</label> <span class="col-sm-1 pt-3 pl-0" style="color:red; font-size: x-small">(required)</span>
                            <div class="col-sm-9"><input id="lib" type="text" name="lib" class="form-control" placeholder="e.g. 295" @error('lib') is-invalid @enderror"></div>
                        </div>
                        <div class="form-group row ">
                            <label for="aircraft" class="col-sm-3 col-form-label">Aircraft</label>
                            <div class="col-sm-9"><input id="aircraft" type="text" name="aircraft" class="form-control" placeholder="e.g. ERJ-190/195" @error('aircraft') is-invalid @enderror"></div>
                        </div>


                        <div class="form-group container-fluid mt-5">
                            <form action="{{route('unit.store')}}" method="post">
                                @csrf
                                <div class="row ">
                                    <div class="col-lg-3 mt-1">
                                        <button id="" type="submit" class="btn btn-primary btn-block ntSaveFormsSubmit">Save</button>
                                    </div>
                                    <div class="col-lg-3 mt-1 ml-auto">
                                        <a href="{{route('workorder.create') }}" class="btn btn-secondary btn-block">Cancel</a>
                                    </div>
                                </div>
                            </form>
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

        function check1() {
            let aa = $('#number_id').val();
            if ($('#number_id').val() == null) {
                $(('#number_id')).addClass('is-invalid');
                setTimeout("$(('#number_id')).removeClass('is-invalid')", 3000);
                return false;
            } else {
                return true
            }
        }

        function check2() {
            let aa = $('#unit_id').val()
            if ($('#unit_id').val() == null) {
                $(('#unit_id')).addClass('is-invalid');
                setTimeout("$(('#unit_id')).removeClass('is-invalid')", 3500);
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
            }
        });

    </script>
@endsection
