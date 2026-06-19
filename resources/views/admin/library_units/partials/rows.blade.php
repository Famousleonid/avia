@forelse($units as $unit)
    @php
        $manualLabel = $unit->manual
            ? trim(($unit->manual->number ?: '-') . ' ' . $unit->manual->title)
            : 'Manual pending';
        $unitEditPayload = [
            'id' => $unit->id,
            'part_number' => $unit->part_number,
            'name' => $unit->name,
            'description' => $unit->description,
            'eff_code' => $unit->eff_code,
            'manual_id' => $unit->manual_id,
            'verified' => (bool) $unit->verified,
        ];
    @endphp
    <tr class="library-unit-row" data-unit='@json($unitEditPayload)' title="Edit" tabindex="0">
        <td>{{ $unit->id }}</td>
        <td class="unit-pn">{{ $unit->part_number }}</td>
        <td>{{ $unit->name ?: '-' }}</td>
        <td>{{ $unit->description ?: '-' }}</td>
        <td class="{{ $unit->manual ? '' : 'text-warning' }}">{{ $manualLabel }}</td>
        <td class="text-center">
            <span class="badge {{ $unit->verified ? 'text-bg-success' : 'text-bg-secondary' }}">
                {{ $unit->verified ? 'Yes' : 'No' }}
            </span>
        </td>
        <td class="text-center">{{ $unit->workorders_count }}</td>
        <td>{{ format_project_date($unit->created_at) ?? '-' }}</td>
        <td class="text-center">
            <button type="button"
                    class="btn btn-outline-danger btn-sm"
                    data-bs-toggle="modal"
                    data-bs-target="#deleteUnitModal"
                    data-unit-id="{{ $unit->id }}"
                    data-unit-label="{{ $unit->part_number }}"
                    data-workorders-count="{{ $unit->workorders_count }}"
                    title="Delete">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    </tr>
@empty
    @if($showEmpty ?? false)
        <tr>
            <td colspan="9" class="text-center unit-muted py-4">{{ __('No units found') }}</td>
        </tr>
    @endif
@endforelse
