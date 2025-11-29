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
                            <a href="{{ route('components.csv-components') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-arrow-left"></i> Back to CSV Components
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    @if(!empty($headers) && !empty($csvData))
                        <style>
                            .csv-table-wrapper {
                                height: calc(100vh - 250px);
                                overflow-y: auto;
                                overflow-x: auto;
                            }
                            
                            .csv-table-wrapper table thead th {
                                position: sticky;
                                top: 0;
                                z-index: 10;
                                background-color: #212529 !important;
                            }
                        </style>
                        <div class="csv-table-wrapper">
                            <table class="table table-sm table-striped table-bordered mb-0">
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
                                                                <span class="">No</span>
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
                        <div class="alert alert-warning m-3">
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
