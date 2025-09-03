@extends('admin.master')

@section('content')
    <style>
        /* Стили для длинного текста процесса */
        .process-text-long {
            font-size: 0.65rem;
            line-height: 0.9;
            letter-spacing: -0.3px;
            transform: scale(0.9);
            transform-origin: left;
        }
    </style>
    <div class="container mt-3">
        <div class="card bg-gradient">
            <div class="card-header">
                <div class="d-flex justify-content-between">
                    <h4 class="text-primary">{{ __('Extra Process Form') }}</h4>
                    <h4 class="pe-3">{{ __('W') }}{{ $current_wo->number }}</h4>
                </div>
                <div class="d-flex justify-content-between">
                    <div>
                        <strong>Component:</strong> {{ $component->name }}<br>
                        <strong>IPL:</strong> {{ $component->ipl_num }}<br>
                        <strong>Part Number:</strong> {{ $component->part_number }}
                    </div>
                </div>
            </div>
            <div class="card-body">
                @if(isset($process_name) && isset($process_components))
                    <div class="row">
                        <div class="col-md-12">
                            <h5 class="text-primary">{{ $process_name->name }} Process Form</h5>
                            
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Process</th>
                                            <th>Description</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($process_components as $process)
                                            <tr>
                                                <td @if(strlen($process->process) > 40) class="process-text-long" @endif>{{ $process->process }}</td>
                                                <td>{{ $process->description ?? 'No description available' }}</td>
                                                <td>
                                                    <span class="badge bg-success">Active</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="mt-3">
                                <h6>Process Details:</h6>
                                <p><strong>Process Name:</strong> {{ $process_name->name }}</p>
                                <p><strong>Work Order:</strong> {{ $current_wo->number }}</p>
                                <p><strong>Component:</strong> {{ $component->name }}</p>
                                <p><strong>Manual ID:</strong> {{ $manual_id }}</p>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="alert alert-warning">
                        <strong>No process information available.</strong><br>
                        This extra process record does not contain valid process data.
                    </div>
                @endif
                
                <div class="mt-3">
                    <a href="{{ route('extra_processes.processes', ['workorderId' => $current_wo->id, 'componentId' => $component->id]) }}"
                       class="btn btn-outline-secondary">{{ __('Back to Processes') }}</a>
                    <a href="{{ route('extra_processes.show_all', ['id' => $current_wo->id]) }}"
                       class="btn btn-outline-primary">{{ __('Back to All Components') }}</a>
                </div>
            </div>
        </div>
    </div>
@endsection 