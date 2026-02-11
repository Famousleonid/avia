@extends('admin.master')

@php use Carbon\Carbon; @endphp

@section('content')
    <style>
        .table-container {
            overflow-x: auto;
            max-height: 80vh;
            overflow-y: auto;
        }

        .table th, .table td {
            white-space: nowrap;
            vertical-align: middle;
            padding: 8px;
        }

        /* Sticky колонки - светлая тема */
        .table th:first-child,
        .table td:first-child {
            position: sticky;
            left: 0;
            background-color: #fff;
            z-index: 10;
            min-width: 200px;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }

        .table thead th:first-child {
            background-color: #f8f9fa;
            z-index: 30;
        }

        .table th:nth-child(2),
        .table td:nth-child(2) {
            position: sticky;
            left: 200px;
            background-color: #fff;
            z-index: 10;
            min-width: 150px;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            white-space: normal;
            line-height: 1.4;
        }

        .table thead th:nth-child(2) {
            background-color: #f8f9fa;
            z-index: 30;
        }

        .table thead th {
            background-color: #f8f9fa;
            position: sticky;
            top: 0;
            z-index: 20;
        }

        /* Темная тема - переопределение цветов */
        [data-bs-theme="dark"] .table th:first-child,
        [data-bs-theme="dark"] .table td:first-child {
            background-color: #212529 !important;
            color: #fff !important;
        }

        [data-bs-theme="dark"] .table thead th:first-child {
            background-color: #495057 !important;
            color: #fff !important;
        }

        [data-bs-theme="dark"] .table th:nth-child(2),
        [data-bs-theme="dark"] .table td:nth-child(2) {
            background-color: #212529 !important;
            color: #fff !important;
        }

        [data-bs-theme="dark"] .table thead th:nth-child(2) {
            background-color: #495057 !important;
            color: #fff !important;
        }

        [data-bs-theme="dark"] .table thead th {
            background-color: #495057 !important;
            color: #fff !important;
        }

        .table th.user-column {
            min-width: 120px;
        }
    </style>

    <div class="container">
        <div class="card shadow">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3>PART NUMBER APPROVED PERSONNEL</h3>
                    </div>
                    <div>
                        <a href="#" class="btn btn-primary">
                            CSV Trainings
                        </a>
                    </div>
                </div>
            </div>

            @if(config('app.debug') && isset($manuals) && isset($users))
                <div class="card-body border-bottom">
                    <small class="text-muted">
                        Manuals: {{ $manuals->count() }}, Users: {{ $users->count() }}
                    </small>
                </div>
            @endif

            <div class="card-body">
                @if(isset($error))
                    <div class="alert alert-danger text-center">
                        <p><strong>Error:</strong> {{ $error }}</p>
                    </div>
                @endif

                @if(!isset($manuals) || !isset($users) || $manuals->isEmpty() || $users->isEmpty())
                    <div class="alert alert-info text-center">
                        @if(!isset($manuals) || $manuals->isEmpty())
                            <p>No manuals with unit_name_training found.</p>
                        @endif
                        @if(!isset($users) || $users->isEmpty())
                            <p>No users with stamp found.</p>
                        @endif
                    </div>
                @else
                    <div class="table-container">
                        <table class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th class="text-center align-middle">Unit Description</th>
                                    <th class="text-center align-middle">PART NUMBER APPROVED</th>
                                    @foreach($users as $user)
                                        <th class="text-center align-middle user-column">{{ $user->name ?? 'N/A' }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($manuals as $manual)
                                    <tr>
                                        <td class="text-center">{{ $manual->title ?? 'N/A' }}</td>
                                        <td class="text-center">
                                            @php
                                                $partNumber = $manual->unit_name_training ?? 'N/A';
                                                if ($partNumber !== 'N/A' && strlen($partNumber) > 40) {
                                                    $targetPos = 40;

                                                    // Ищем запятую после 40-го символа
                                                    $commaAfter = strpos($partNumber, ',', $targetPos);

                                                    // Ищем последнюю запятую до 40-го символа
                                                    $commaBefore = strrpos(substr($partNumber, 0, $targetPos), ',');

                                                    // Выбираем ближайшую запятую
                                                    $commaPos = false;
                                                    if ($commaAfter !== false && $commaBefore !== false) {
                                                        // Выбираем ближайшую к 40-му символу
                                                        $commaPos = (($commaAfter - $targetPos) < ($targetPos - $commaBefore))
                                                            ? $commaAfter
                                                            : $commaBefore;
                                                    } elseif ($commaAfter !== false) {
                                                        $commaPos = $commaAfter;
                                                    } elseif ($commaBefore !== false) {
                                                        $commaPos = $commaBefore;
                                                    }

                                                    // Если нашли запятую, разделяем
                                                    if ($commaPos !== false) {
                                                        $firstPart = trim(substr($partNumber, 0, $commaPos + 1));
                                                        $secondPart = trim(substr($partNumber, $commaPos + 1));
                                                        echo $firstPart . '<br>' . $secondPart;
                                                    } else {
                                                        // Если запятой нет, просто переносим на 40-м символе
                                                        echo substr($partNumber, 0, 40) . '<br>' . substr($partNumber, 40);
                                                    }
                                                } else {
                                                    echo $partNumber;
                                                }
                                            @endphp
                                        </td>
                                        @foreach($users as $user)
                                            <td class="text-center">
                                                @if(isset($trainingDates[$manual->id][$user->id]))
                                                    {{ Carbon::parse($trainingDates[$manual->id][$user->id])->format('M-d-Y') }}
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
