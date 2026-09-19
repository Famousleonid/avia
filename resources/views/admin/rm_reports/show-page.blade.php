@extends('admin.master')

@section('style')
    <style>
        .rm-reports-page-shell {
            flex: 1 1 auto;
            width: 100%;
            height: 100%;
            min-height: 0;
            overflow: hidden;
            padding: .5rem;
        }
    </style>
@endsection

@section('content')
    <div class="rm-reports-page-shell">
        @include('admin.rm_reports.partial', [
            'current_wo' => $current_wo,
            'rm_reports' => $rm_reports,
            'assyOptions' => $assyOptions,
            'serviceBulletins' => $serviceBulletins,
        ])
    </div>
@endsection
