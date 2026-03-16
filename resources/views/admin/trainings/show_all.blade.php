@extends('admin.master')

@php use Carbon\Carbon; @endphp

@section('content')
    <style>
        .table-container {
            overflow-x: auto;
            overflow-y: auto;
            max-height: 80vh;
            position: relative;
        }

        .training-table {
            width: max-content;
            min-width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .training-table th,
        .training-table td {
            white-space: nowrap;
            vertical-align: middle;
            padding: 8px 10px;
            border: 1px solid #495057;
            background: #212529;
            color: #f8f9fa;
            position: relative;
        }

        .training-table thead th {
            position: sticky;
            top: 0;
            z-index: 20;
            background: linear-gradient(180deg, #334a66 0%, #14161a 100%);
            color: #7db8ff;
            font-size: 13px;
            font-weight: 600;
            height: 34px;
            padding: 6px 10px;
        }

        /* первый столбец всегда зафиксирован */
        .training-table th.col-unit,
        .training-table td.col-unit {
            position: sticky !important;
            left: 0;
            min-width: 260px;
            max-width: 260px;
            width: 220px;
            z-index: 30;
            background: #212529 !important;
            box-shadow: 2px 0 6px rgba(0, 0, 0, 0.18);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;

        }

        /* ВАЖНО:
           у шапки первого столбца такой же фон, как у остальных th */
        .training-table thead th.col-unit {
            z-index: 50;
            background: linear-gradient(180deg, #334a66 0%, #14161a 100%) !important;
            color: #7db8ff !important;
        }

        /* второй столбец перекрывает первый */
        .training-table th.col-part,
        .training-table td.col-part {
            position: sticky !important;
            left: 0;
            min-width: 260px;
            max-width: 260px;
            width: 260px;
            z-index: 40;
            background: #2b3035 !important;
            box-shadow: 4px 0 12px rgba(0, 0, 0, 0.28);
        }

        .training-table thead th.col-part {
            z-index: 60;
            background: linear-gradient(180deg, #334a66 0%, #14161a 100%) !important;
            color: #7db8ff !important;
        }

        .training-table td.col-part {
            white-space: normal;
            line-height: 1.35;
        }

        .training-table th.user-column,
        .training-table td.user-column {
            min-width: 120px;
            width: 120px;
            z-index: 1;
        }

        .training-table tbody tr:nth-child(even) td {
            background-color: #252b31;
        }

        .training-table tbody tr:nth-child(even) td.col-unit {
            background: #212529 !important;
        }

        .training-table tbody tr:nth-child(even) td.col-part {
            background: #2b3035 !important;
        }

        .training-table .text-muted {
            color: #adb5bd !important;
        }

        .training-date-old {
            color: #dc3545 !important;
            font-weight: 700;
        }

        .training-date-fresh {
            color: #f8f9fa !important;
        }

        .training-table tbody td.col-unit,
        .training-table tbody td.col-part {
            background-clip: padding-box;
        }
    </style>

    <div class="container">
        <div class="card shadow">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">PART NUMBER APPROVED PERSONNEL</h5>
                    </div>
                    <div>
                        <a href="#" class="btn btn-primary">
                            CSV Trainings
                        </a>
                    </div>
                </div>
            </div>

            @if(config('app.debug') && isset($manuals) && isset($users))
                <div class="card-body border-bottom p-1">
                    <small class="text-muted">
                        Manuals: {{ $manuals->count() }}, Users: {{ $users->count() }}
                    </small>
                </div>
            @endif

            <div class="card-body">
                @php
                    $filteredUsers = $users->filter(function ($user) {
                        return !in_array(mb_strtolower(trim($user->name)), ['manager', 'admin', 'user']);
                    });

                    $oneYearAgo = now()->subYear()->startOfDay();
                @endphp

                @if(isset($error))
                    <div class="alert alert-danger text-center">
                        <p class="mb-0"><strong>Error:</strong> {{ $error }}</p>
                    </div>
                @endif

                @if(!isset($manuals) || !isset($users) || $manuals->isEmpty() || $users->isEmpty())
                    <div class="alert alert-info text-center">
                        @if(!isset($manuals) || $manuals->isEmpty())
                            <p class="mb-1">No manuals with unit_name_training found.</p>
                        @endif

                        @if(!isset($users) || $users->isEmpty())
                            <p class="mb-0">No users with stamp found.</p>
                        @endif
                    </div>
                @else
                    <div class="table-container">
                        <table class="table training-table table-bordered table-hover dir-table align-middle">
                            <thead>
                            <tr>
                                <th class="text-center align-middle col-unit">
                                    Unit Description
                                </th>

                                <th class="text-center align-middle col-part">
                                    PART NUMBER APPROVED
                                </th>

                                @foreach($filteredUsers as $user)
                                    <th class="text-center align-middle user-column">
                                        {{ $user->name ?? 'N/A' }}
                                    </th>
                                @endforeach
                            </tr>
                            </thead>

                            <tbody>
                            @foreach($manuals as $manual)
                                <tr>
                                    <td class="text-center col-unit">
                                        {{ $manual->title ?? 'N/A' }}
                                    </td>

                                    <td class="text-center col-part">
                                        @php
                                            $partNumber = $manual->unit_name_training ?? 'N/A';
                                        @endphp

                                        @if($partNumber !== 'N/A' && strlen($partNumber) > 40)
                                            @php
                                                $targetPos = 40;
                                                $commaAfter = strpos($partNumber, ',', $targetPos);
                                                $commaBefore = strrpos(substr($partNumber, 0, $targetPos), ',');

                                                $commaPos = false;

                                                if ($commaAfter !== false && $commaBefore !== false) {
                                                    $commaPos = (($commaAfter - $targetPos) < ($targetPos - $commaBefore))
                                                        ? $commaAfter
                                                        : $commaBefore;
                                                } elseif ($commaAfter !== false) {
                                                    $commaPos = $commaAfter;
                                                } elseif ($commaBefore !== false) {
                                                    $commaPos = $commaBefore;
                                                }

                                                if ($commaPos !== false) {
                                                    $firstPart = trim(substr($partNumber, 0, $commaPos + 1));
                                                    $secondPart = trim(substr($partNumber, $commaPos + 1));
                                                } else {
                                                    $firstPart = substr($partNumber, 0, 40);
                                                    $secondPart = substr($partNumber, 40);
                                                }
                                            @endphp

                                            {{ $firstPart }}<br>{{ $secondPart }}
                                        @else
                                            {{ $partNumber }}
                                        @endif
                                    </td>

                                    @foreach($filteredUsers as $user)
                                        <td class="text-center user-column">
                                            @if(isset($trainingDates[$manual->id][$user->id]))
                                                @php
                                                    $trainDate = Carbon::parse($trainingDates[$manual->id][$user->id]);
                                                    $isOldTraining = $trainDate->lt($oneYearAgo);
                                                @endphp

                                                <span class="{{ $isOldTraining ? 'training-date-old' : 'training-date-fresh' }}">
                                                    {{ $trainDate->format('M-d-Y') }}
                                                </span>
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
                @endif
            </div>
        </div>
    </div>
@endsection
