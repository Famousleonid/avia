@extends('admin.master')

@section('content')

    <div class="container-fluid pl-3 pr-3 pt-2 row justify-content-center">
        <div class="card shadow firm-border bg-white mt-2 col-8">

            <div class="card-header">
                <h3 class="card-title text-bold">Create of customer</h3>
            </div>

            <form action="{{route('cabinet.customer.store')}}">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" placeholder="Enter name of ...">
                    </div>
                    <br>
                </div>
                <div class="d-flex row">
                    <div class="card-footer col-10">
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                    <div class="card-footer col-2 justify-content-end">
                        <a href="{{route('cabinet.customer.index')}}" class="btn btn-info btn-block">Return to customer list</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

@endsection

