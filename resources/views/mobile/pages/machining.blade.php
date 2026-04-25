@extends('mobile.master')

@section('style')
    <style>
        .machining-mobile-wrap {
            padding: 0;
        }
        .machining-mobile-card {
            background: rgba(20, 24, 28, .9);
            border: 1px solid rgba(255, 255, 255, .12);
            border-radius: .35rem;
            padding: .25rem;
            margin: 0;
        }
        .machining-mobile-table {
            color: #e9ecef;
            font-size: .78rem;
            margin-bottom: 0;
            table-layout: fixed;
            width: 100%;
        }
        .machining-mobile-table thead th {
            color: #9fb0c0;
            font-size: .7rem;
            white-space: nowrap;
            padding: .25rem .35rem;
        }
        .machining-mobile-col-queue {
            text-align: center;
            width: 44px;
            max-width: 44px;
        }
        .machining-mobile-table td, .machining-mobile-table th {
            vertical-align: middle;
            padding: .25rem .35rem;
        }
        .machining-mobile-wo-btn {
            font-weight: 600;
            min-width: 3.5rem;
        }
    </style>
@endsection

@section('content')
    @php
        $woList = $woList ?? collect();
    @endphp

    <div class="container-fluid machining-mobile-wrap">
        <div class="machining-mobile-card">
            <div class="table-responsive">
                <table class="table table-dark table-striped table-sm machining-mobile-table">
                    <colgroup>
                        <col class="machining-mobile-col-queue">
                        <col>
                    </colgroup>
                    <thead>
                    <tr>
{{--                        <th class="machining-mobile-col-queue">Queue</th>--}}
{{--                        <th>WO</th>--}}
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($woList as $entry)
                        @php
                            /** @var \App\Models\Workorder $wo */
                            $wo = $entry->workorder;
                        @endphp
                        <tr>
                            <td class="machining-mobile-col-queue text-info">{{ $entry->queue_display }}</td>
                            <td>
                                <a href="{{ route('mobile.machining.workorder', $wo) }}"
                                   class="btn btn-sm btn-outline-info machining-mobile-wo-btn w-100">{{ $wo->number }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="text-center text-secondary py-3">No machining workorders.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
