@extends('layouts.main_dlb')

@section('content')

    <style>
        .card {
            max-width: 450px;
        }
    </style>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h4>{{ __('Select Unit') }}</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('training.store') }}">
                    @csrf
                    <div class="form-group mt-2">
                        <label for="manuals_id">{{ __('Unit PN') }}</label>
                        <select id="manuals_id" name="manuals_id" class="form-control" required>
                            <option value="">{{ __('Select Unit PN') }}</option>
                            @foreach ($manuals as $manual)
                                <option value="{{ $manual->id }}">{{ $manual->title }} ({{ $manual->units_pn }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group mt-3">
                        <label for="date_training">{{ __('First Training Date') }}</label>
                        <input type="date" id="date_training" name="date_training" class="form-control" required>
                    </div>



                    <button type="submit" class="btn btn-primary mt-3">{{ __('Add Unit') }}</button>
                </form>
            </div>
        </div>
    </div>

@endsection
