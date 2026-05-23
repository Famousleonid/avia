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
                <form action="{{ route('manual_processes.update', $manualProcess) }}" method="POST">
                    @csrf
                    @method('PUT')
                    @if(request('return_to'))
                        <input type="hidden" name="return_to" value="{{ request('return_to') }}">
                    @endif

                    <div class="mb-3">
                        <label for="process" class="form-label">Process</label>
                        <input type="text" class="form-control" id="process" name="process" value="{{ $process->process }}">
                    </div>

                    <div class="mb-3">
                        <label for="process_comment" class="form-label">Comment</label>
                        <textarea class="form-control" id="process_comment" name="process_comment" rows="4">{{ old('process_comment', $manualProcess->process_comment) }}</textarea>
                    </div>

                    <button type="submit" class="btn btn-outline-primary">Update</button>
                    <a href="{{ request('return_to', route('manuals.show', ['manual' => $manualId, 'tab' => 'processes'])) }}" class="btn btn-outline-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
@endsection
