@extends('admin.master')

@section('title', 'View CSV File')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-file-earmark-text"></i>
                            CSV File: {{ $csvFile->file_name }}
                        </h5>
                        <div>
                            <a href="{{ route('components.index') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-arrow-left"></i> Back to Components
                            </a>
                            <a href="{{ $csvFile->getUrl() }}" class="btn btn-outline-primary btn-sm" download>
                                <i class="bi bi-download"></i> Download CSV
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6>File Information</h6>
                            <ul class="list-unstyled">
                                <li><strong>Manual:</strong> {{ $manual->number }} - {{ $manual->title }}</li>
                                <li><strong>File Name:</strong> {{ $csvFile->file_name }}</li>
                                <li><strong>File Size:</strong> {{ number_format($csvFile->size / 1024, 2) }} KB</li>
                                <li><strong>Upload Date:</strong> {{ $csvFile->created_at->format('Y-m-d H:i:s') }}</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>CSV Summary</h6>
                            <ul class="list-unstyled">
                                <li><strong>Total Rows:</strong> {{ count($csvData) + 1 }}</li>
                                <li><strong>Data Rows:</strong> {{ count($csvData) }}</li>
                                <li><strong>Columns:</strong> {{ count($headers) }}</li>
                            </ul>
                        </div>
                    </div>

                    @if(!empty($headers) && !empty($csvData))
                        <div class="table-responsive">
                            <table class="table table-sm table-striped table-bordered">
                                <thead class="table-dark">
                                    <tr>
                                        @foreach($headers as $header)
                                            <th class="text-center">{{ $header }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($csvData as $rowIndex => $row)
                                        <tr>
                                            @foreach($headers as $colIndex => $header)
                                                <td class="text-center">
                                                    @if(isset($row[$colIndex]))
                                                        @if(in_array($header, ['log_card', 'repair', 'is_bush']))
                                                            @if($row[$colIndex] == '1' || $row[$colIndex] == 'true')
                                                                <span class="badge bg-success">Yes</span>
                                                            @else
                                                                <span class="badge bg-secondary">No</span>
                                                            @endif
                                                        @else
                                                            {{ $row[$colIndex] ?: '-' }}
                                                        @endif
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            No data found in CSV file or file is empty.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
