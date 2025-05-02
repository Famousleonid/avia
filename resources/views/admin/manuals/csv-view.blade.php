@extends('admin.layouts.app')

@section('content')
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h4>CSV файл для Manual: {{ $manual->number }} - {{ $manual->title }}</h4>
                <div class="float-end">
                    <a href="{{ route('admin.manuals.csv.download', $manual) }}" class="btn btn-primary">
                        <i class="fas fa-download"></i> Скачать CSV
                    </a>
                    <a href="{{ route('admin.manuals.edit', $manual) }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Назад
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                @foreach($headers as $header)
                                    <th>{{ $header }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($records as $record)
                                <tr>
                                    @foreach($headers as $header)
                                        <td>{{ $record[$header] }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection 