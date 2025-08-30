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
        .reason-select {
            min-width: 150px;
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

                <!-- Отладочная информация о группировке -->
{{--                <div style="background: #f0f0f0; padding: 10px; margin-bottom: 10px;">--}}
{{--                    <strong>DEBUG: Группировка компонентов:</strong><br>--}}
{{--                    @foreach($groupedComponents as $groupIndex => $group)--}}
{{--                        <strong>Группа {{ $groupIndex }} ({{ $group['ipl_group'] }}):</strong> {{ $group['count'] }} компонентов<br>--}}
{{--                        @foreach($group['components'] as $componentData)--}}
{{--                            &nbsp;&nbsp;- {{ $componentData['component']->ipl_num }} ({{ $componentData['component']->name }})<br>--}}
{{--                        @endforeach--}}
{{--                    @endforeach--}}
{{--                </div>--}}

                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th>Part Number / Assy PN</th>
                                <th>Select</th>
                                <th>Serial Number</th>
                                <th>ASSY Serial Number</th>
                                <th>Reason for Remove (Optional)</th>
{{--                                <th>Action</th>--}}
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($groupedComponents as $groupIndex => $group)
                                @foreach($group['components'] as $i => $componentData)
                                    @php
                                        $component = $componentData['component'];
                                        $reason = $componentData['reason_for_remove'];
                                    @endphp
                                    <tr>
                                        @if($i === 0)
                                            <td rowspan="{{ $group['count'] }}" class="align-middle">{{ $component->name }}</td>
                                        @endif
                                        <td>
                                            {{ $component->part_number }}
                                            @if($component->assy_part_number)
                                                / {{ $component->assy_part_number }}
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <input type="radio" name="selected_component[{{ $groupIndex }}]" value="{{ $component->id }}">
                                        </td>
                                        @if($i === 0)
                                                <td rowspan="{{ $group['count'] }}" class="align-middle">
                                                    <input type="text" class="form-control form-control-sm"
                                                           name="serial_numbers[{{ $groupIndex }}]"
                                                           value="{{ $component->serial_number ?? '' }}"
                                                           placeholder="Serial Number">
                                                </td>
                                                <td rowspan="{{ $group['count'] }}" class="align-middle">
                                                    @if($component->assy_part_number>null)
                                                        <input type="text" class="form-control form-control-sm"
                                                               name="assy_serial_numbers[{{ $groupIndex }}]"
                                                               value="{{ $component->assy_serial_number ?? '' }}"
                                                               placeholder="ASSY Serial Number">
                                                    @endif
                                                </td>
                                        @endif

                                        @if($i === 0)
                                            <td rowspan="{{ $group['count'] }}" class="align-middle">
                                                <select class="form-control form-control-sm reason-select" name="reasons[{{ $groupIndex }}]">
                                                    <option value="">Reason (Optional)</option>
                                                    @foreach($codes as $code)
                                                        <option value="{{ $code->id }}" 
                                                                @if($reason && $reason === $code->name) selected @endif>
                                                            {{ $code->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                        @endif
{{--                                            <td rowspan="{{ $group['count'] }}" class="align-middle">--}}
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
        // Создаем карту индексов групп для получения базовых номеров
        let groupMap = {};
        @foreach($groupedComponents as $groupIndex => $group)
            groupMap[{{ $groupIndex }}] = '{{ $group['ipl_group'] }}';
        @endforeach

        document.getElementById('createForm').addEventListener('submit', function(e) {
            let data = [];
            let allGroups = new Set();
            document.querySelectorAll('input[type=radio][name^="selected_component["]').forEach(function(radio) {
                let groupIndex = radio.name.match(/selected_component\[(.*)\]/)[1];
                allGroups.add(groupIndex);
            });

            document.querySelectorAll('input[type=radio]:checked').forEach(function(radio) {
                let groupIndex = radio.name.match(/selected_component\[(.*)\]/)[1];
                let ipl_group = groupMap[groupIndex]; // Получаем базовый номер группы
                let component_id = radio.value;
                let serial_number = document.querySelector('input[name="serial_numbers[' + groupIndex + ']"]').value;
                let assy_serial_number = '';
                let assyInput = document.querySelector('input[name="assy_serial_numbers[' + groupIndex + ']"]');
                if (assyInput) assy_serial_number = assyInput.value;
                
                // Получаем reason из dropdown
                let reasonSelect = document.querySelector('select[name="reasons[' + groupIndex + ']"]');
                let reason = reasonSelect ? reasonSelect.value : '';
                
                data.push({
                    component_id: component_id,
                    ipl_group: ipl_group, // Добавляем базовый номер группы
                    serial_number: serial_number,
                    assy_serial_number: assy_serial_number,
                    reason: reason
                });
            });

            // Проверяем, что выбран компонент в каждой группе
            if (data.length !== allGroups.size) {
                alert('Выберите компонент в каждой группе!');
                e.preventDefault();
                return false;
            }
            
            // Проверка reason необязательна - убираем валидацию

            let input = document.getElementById('component_data_input');
            input.value = JSON.stringify(data);
        });
    });
    </script>
@endsection
