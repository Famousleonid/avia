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
                <input type="hidden" name="component_data" id="component_data_input">

                <!-- Отладочная информация о компонентах -->
                <div style="background: #f0f0f0; padding: 10px; margin-bottom: 10px;">
                    <strong>DEBUG: Всего компонентов: {{ $components->count() }}</strong><br>
                    @foreach($components as $comp)
                        <span style="margin-right: 10px;">ID: {{ $comp->id }}, Name: {{ $comp->name }}, Log_card: {{ $comp->log_card }}</span>
                    @endforeach
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th>Part Number / Assy PN</th>
                                <th>Select</th>
                                <th>Serial Number</th>
                                <th>ASSY Serial Number</th>
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
                                        <td>
                                            {{ $component->part_number }}
                                            @if($component->assy_part_number)
                                                / {{ $component->assy_part_number }}
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <input type="radio" name="selected_component[{{ $desc }}]" value="{{ $component->id }}">
                                        </td>
                                        @if($i === 0)
                                                <td rowspan="{{ $group->count() }}" class="align-middle">
                                                    <input type="text" class="form-control form-control-sm"
                                                           name="serial_numbers[{{ $desc }}]"
                                                           value="{{ $component->serial_number ?? '' }}"
                                                           placeholder="Serial Number">
                                                </td>
                                                <td rowspan="{{ $group->count() }}" class="align-middle">
                                                    @if($component->assy_part_number>null)
                                                        <input type="text" class="form-control form-control-sm"
                                                               name="assy_serial_numbers[{{ $desc }}]"
                                                               value="{{ $component->assy_serial_number ?? '' }}"
                                                               placeholder="ASSY Serial Number">
                                                    @endif
                                                </td>
                                        @endif

                                        <!-- Определение reason для каждого компонента -->
                                        @php
                                            $tdr = $tdrs->where('component_id', $component->id)->first();
                                            $reason = '';



                                            if ($tdr) {
                                                // Проверяем codes (Missing)
                                                if ($tdr->codes && $tdr->codes->name === 'Missing') {
                                                    $reason = 'Missing';
                                                }
                                                // Проверяем necessary (Order New)
                                                if ($tdr->necessaries && $tdr->necessaries->name === 'Order New') {
                                                    // Если necessary = "Order New", то берем значение из codes
                                                    if ($tdr->codes) {
                                                        $reason = $tdr->codes->name;
                                                    }
                                                }
                                            }


                                        @endphp

                                        <td class="align-middle">
                                            @if($reason)
                                                <span class="reason-badge">{{ $reason }}</span>
                                            @else
                                                <span class="text-muted reason-badge"></span>
                                            @endif
                                        </td>
{{--                                            <td rowspan="{{ $group->count() }}" class="align-middle">--}}
{{--                                                <a href="{{ route('components.edit', $component->id) }}" class="btn btn-sm btn-primary">--}}
{{--                                                    <i class="fas fa-edit"></i> Edit--}}
{{--                                                </a>--}}
{{--                                            </td>--}}
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
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('createForm').addEventListener('submit', function(e) {
            let data = [];
            let allGroups = new Set();
            document.querySelectorAll('input[type=radio][name^="selected_component["]').forEach(function(radio) {
                let group = radio.name.match(/selected_component\[(.*)\]/)[1];
                allGroups.add(group);
            });

            document.querySelectorAll('input[type=radio]:checked').forEach(function(radio) {
                let group = radio.name.match(/selected_component\[(.*)\]/)[1];
                let component_id = radio.value;
                let serial_number = document.querySelector('input[name="serial_numbers[' + group + ']"]').value;
                let assy_serial_number = '';
                let assyInput = document.querySelector('input[name="assy_serial_numbers[' + group + ']"]');
                if (assyInput) assy_serial_number = assyInput.value;
                let reason = '';
                let reasonCell = radio.closest('tr').querySelector('.reason-badge');
                if (reasonCell) reason = reasonCell.textContent.trim();
                data.push({
                    component_id: component_id,
                    serial_number: serial_number,
                    assy_serial_number: assy_serial_number,
                    reason: reason
                });
            });

            if (data.length !== allGroups.size) {
                alert('Выберите компонент в каждой группе!');
                e.preventDefault();
                return false;
            }

            let input = document.getElementById('component_data_input');
            input.value = JSON.stringify(data);
        });
    });
    </script>
@endsection
