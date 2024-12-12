@extends('admin.master')

@section('content')

    <section class="container content-header firm-border shadow bg-white mt-3">
        <div class="container-fluid">
            <div class="card-header">
                <h3 class="card-title text-bold">Editing "{{$customer->name}}"</h3>
            </div>

            <form role="form" method="post" action="{{route('cabinet.customer.update',['id' => $customer->id])}}">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="form-group">
                        <div class="form-group">
                            <label class="col-sm-1 col-form-label">Name</label>
                            <div class="col-sm-11"><input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{$customer->name}}"></div>
                        </div>
                    </div>
                </div>
                <div class="card-footer row">
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                    <div class="col-md-3 ml-auto">
                        <a href="{{route('cabinet.customer.index')}}" class="btn btn-info btn-block">Return to customer list</a>
                    </div>
                </div>
            </form>

        </div>
    </section>

@endsection


