@extends('admin.master')


@section('content')




    <div class="container-fluid pl-3 pr-3 pt-2 row justify-content-center">

        <div class="card shadow firm-border bg-white mt-2 col-8">

            <!-- Main content -->

            <div class="card-header">
                <h3 class="card-title text-bold">Create of technik</h3>
            </div>

            <form role="form" method="post" action="{{route('techniks.store')}}" enctype="multipart/form-data">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" placeholder="Enter name of ...">
                    </div>
                    <div class="form-group ">
                        <label for="email" class="text-primary">Email *</label>
                        <input type="text" name="email" id="email" class="form-control @error('email') is-invalid @enderror" placeholder="Enter email of ...">
                    </div>
                    <div class="form-group">
                        <label for="stamp">stamp</label>
                        <input type="text" name="stamp" id="stamp" class="form-control @error('stamp') is-invalid @enderror" placeholder="Enter stamp of ...">
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="text" name="phone" id="phone" class="form-control @error('phone') is-invalid @enderror">
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_admin" value="1" id="admin">
                        <span class="text-bold">Admin</span>
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="role" value="1" id="role">
                        <span class="text-bold">Team leader</span>
                        </label>
                    </div>
                    <br>

                    <div class="row">

                        <div class="form-group col-6">
                            <label for="password" class="text-primary">Password *</label>
                            <input type="text" name="password" id="password" class="form-control @error('password') is-invalid @enderror">
                        </div>

                        <div class="form-group col-6">
                            <label for="avatar" class="text-primary ml-5">Avatar
                                <input type="file" name="avatar" id="input_avatar" class="form-control" hidden>
                                <img class="img-circle" id="img_avatar" src="{{ asset('img/noimage2.png') }}" width="150" alt="User profile picture">
                            </label>

                        </div>


                    </div>


                </div>
                <!-- /.card-body -->
                <div class="d-flex row">
                    <div class="card-footer col-10">
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                    <div class="card-footer col-2 justify-content-end">
                        <a href="{{route('admin.index')}}" class="btn btn-info btn-block">Cancel</a>
                    </div>
                </div>


            </form>

            <!-- /.content -->
        </div>
    </div>






@endsection
@section('scripts')

    <script>
        $('#img_avatar').click(function () {
            $('#input_avatar').trigger('click');
        });
        $('#img_avatar').hover(function () {
            $(this).css('cursor', 'pointer');
        });

    </script>
@endsection
