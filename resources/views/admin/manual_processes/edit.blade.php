@extends('admin.master')

@section('content')
    <style>
        .container {
            max-width: 800px;
        }
    </style>
    <div class="container mt-3">
        <div class="card bg-gradient">
            <div class="card-header">
                <div class="d-flex justify-content-between">
                    <h4 class="text-primary">{{ __('Edit Manual Process: ') }}
                        {{$processNames -> name}}
                    </h4>
                </div>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.manual_processes.update', ['manual_process' => $manualProcess->id]) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="process" class="form-label">Process</label>
                        <input type="text" class="form-control" id="process" name="process" value="{{ $process->process }}">
                    </div>

                    <button type="submit" class="btn btn-outline-primary">Update</button>
                    <a href="{{ route('admin.processes.edit',['process' => $manualId]) }}" class="btn
                    btn-outline-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
@endsection
