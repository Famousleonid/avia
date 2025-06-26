@extends('admin.master')

@section('content')
    <style>
        .container {
            max-width: 1200px;
        }
        .component-group {
            margin-bottom: 30px;
        }
        .group-header {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        .group-selector {
            margin-bottom: 10px;
        }
        /*.table th {*/
        /*    background-color: #e9ecef;*/
        /*}*/
        .component-item {
            border-bottom: 1px solid #dee2e6;
        }
        .component-group-row {
            border-bottom: 1px solid #dee2e6;
        }
        .serial-number-input {
            min-width: 120px;
            font-size: 0.875rem;
        }
        .table td {
            vertical-align: middle;
        }
        .component-radio {
            margin: 0;
        }
        .align-middle {
            vertical-align: middle !important;
        }
    </style>

    <div class="container mt-3">
        <div class="card bg-gradient">
            <div class="card-header">
                <h4 class="text-primary">{{__('WO')}} {{$current_wo->number}} {{__('Create Log Card')}}</h4>
            </div>
        </div>

        <div class="card-body">
            <form id="createForm" class="createForm" role="form" method="POST" action="{{route('log_card.store')}}"
                  enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="workorder_id" value="{{ $current_wo->id }}">
                <input type="hidden" name="manual_id" value="{{ $current_wo->unit->manual_id }}">

                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr class="text-center">
                                <th>Description</th>
                                <th>Part Number</th>
                                <th>Select</th>
                                <th>Serial Number</th>
                                <th>Reason for Remove</th>
{{--                                <th>Action</th>--}}
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($components->groupBy('name') as $desc => $group)
                                @foreach($group as $i => $component)
                                    <tr>
                                        @if($i === 0)
                                            <td rowspan="{{ $group->count() }}" class="align-middle">{{ $desc }}</td>
                                        @endif
                                        <td class="text-center">{{ $component->part_number }}</td>
                                        <td class="text-center">
                                            <input type="radio" name="selected_component[{{ $desc }}]" value="{{ $component->id }}">
                                        </td>
                                        @if($i === 0)
                                            <td rowspan="{{ $group->count() }}" class="align-middle">
                                                <input type="text" class="form-control form-control-sm"
                                                    name="serial_numbers[{{ $desc }}]"
                                                    value="{{ $component->serial_number ?? '' }}"
                                                    placeholder="serial number">
                                            </td>
                                                <td rowspan="{{ $group->count() }}" class="align-middle">
                                                    @php
                                                        $tdr = $tdrs->where('component_id', $component->id)->first();
                                                        $reason = '';
                                                        if ($tdr) {
                                                            if ($tdr->codes_id == $code->id) $reason = 'Missing';
                                                            elseif ($tdr->necessaries_id == $necessary->id) $reason =
                                                            $tdr->codes->name;
                                                        }
                                                    @endphp
                                                    @if($reason)
                                                        <span class="">{{
                                                         $reason }}</span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
{{--                                            <td rowspan="{{ $group->count() }}" class="align-middle">--}}
{{--                                                <a href="{{ route('components.edit', $component->id) }}" class="btn btn-sm btn-primary">--}}
{{--                                                    <i class="fas fa-edit"></i> Edit--}}
{{--                                                </a>--}}
{{--                                            </td>--}}
                                        @endif
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Create Log Card
                    </button>
                    <a href="{{ route('log_card.show', $current_wo->id) }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // JavaScript для функциональности radio buttons
        document.addEventListener('DOMContentLoaded', function() {
            // Можно добавить дополнительную логику для radio buttons если нужно
            console.log('Log Card form loaded');
        });
    </script>
@endsection
