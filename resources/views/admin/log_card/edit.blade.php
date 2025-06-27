@extends('admin.master')

@section('content')
<style>
    .container { max-width: 1200px; }
    .serial-number-input { min-width: 120px; font-size: 0.875rem; }
    .table td { vertical-align: middle; }
    .component-radio { margin: 0; }
    .align-middle { vertical-align: middle !important; }
</style>

<div class="container mt-3">
    <div class="card bg-gradient">
        <div class="card-header">
            <h4 class="text-primary">{{__('WO')}} {{$current_wo->number}} {{__('Edit Log Card')}}</h4>
        </div>
    </div>

    <div class="card-body">
        <form id="editForm" method="POST" action="{{ route('log_card.update', $log_card->id) }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="workorder_id" value="{{ $current_wo->id }}">
            <input type="hidden" name="component_data" id="component_data_input">

            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Part Number</th>
                            <th>Select</th>
                            <th>Serial Number</th>
                            <th>Reason for Remove</th>
{{--                            <th>Action</th>--}}
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $selected = [];
                            if (!empty($componentData)) {
                                foreach ($componentData as $item) {
                                    $selected[$item['component_id']] = $item;
                                }
                            }
                        @endphp
                        @foreach($components->groupBy('name') as $desc => $group)
                            @foreach($group as $i => $component)
                                @php
                                    $isChecked = isset($selected[$component->id]);
                                    // Найти выбранный компонент в группе
                                    $selectedComponent = null;
                                    foreach ($group as $comp) {
                                        if (isset($selected[$comp->id])) {
                                            $selectedComponent = $selected[$comp->id];
                                            break;
                                        }
                                    }
                                    $serialValue = $selectedComponent['serial_number'] ?? '';
                                @endphp
                                <tr>
                                    @if($i === 0)
                                        <td rowspan="{{ $group->count() }}" class="align-middle">{{ $desc }}</td>
                                    @endif
                                    <td>{{ $component->part_number }}</td>
                                    <td class="text-center">
                                        <input type="radio" name="selected_component[{{ $desc }}]" value="{{ $component->id }}"
                                            {{ $isChecked ? 'checked' : '' }}>
                                    </td>
                                    @if($i === 0)
                                        <td rowspan="{{ $group->count() }}" class="align-middle">
                                            <input type="text" class="form-control form-control-sm"
                                                name="serial_numbers[{{ $desc }}]"
                                                value="{{ $serialValue }}"
                                                placeholder="Введите серийный номер">
                                        </td>
                                        <td rowspan="{{ $group->count() }}" class="align-middle">
                                            @php
                                                $tdr = $tdrs->where('component_id', $component->id)->first();
                                                $reason = '';
                                                if ($tdr && $tdr->codes && $tdr->codes->name) {
                                                    $reason = $tdr->codes->name;
                                                }
                                            @endphp
                                            @if($reason)
                                                <span class="reason-badge">{{ $reason }}</span>
                                            @else
                                                <span class="text-muted reason-badge"></span>
                                            @endif
{{--                                        </td>--}}
{{--                                        <td rowspan="{{ $group->count() }}" class="align-middle">--}}
{{--                                            <a href="{{ route('components.edit', $component->id) }}" class="btn btn-sm btn-primary">--}}
{{--                                                <i class="fas fa-edit"></i> Edit--}}
{{--                                            </a>--}}
{{--                                        </td>--}}
                                    @endif
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Update Log Card
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
    document.getElementById('editForm').addEventListener('submit', function(e) {
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
            let reason = '';
            let reasonCell = radio.closest('tr').querySelector('.reason-badge');
            if (reasonCell) reason = reasonCell.textContent.trim();
            data.push({
                component_id: component_id,
                serial_number: serial_number,
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
