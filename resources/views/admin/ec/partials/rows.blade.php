@forelse($ecRows as $ecProcess)
    @php
        $tdr = $ecProcess->tdr;
        $workorder = $tdr?->workorder;
        $component = $tdr?->component ?? $tdr?->orderComponent;
        $description = trim((string) ($component?->name ?: $ecProcess->description ?: $tdr?->description));
        $manual = $workorder?->unit?->manual;
        $endAssyPartNumber = $manual?->unit_name_training;
        $applicability = $manual?->plane?->type;
    @endphp
    <tr>
        <td>{{ $description !== '' ? $description : '-' }}</td>
        <td class="text-center">{{ $endAssyPartNumber ?: '' }}</td>
        <td class="text-center">{{ $component?->part_number ?: '' }}</td>
        <td class="text-center">{{ $applicability ?: '' }}</td>
        <td>
            <input
                type="text"
                class="form-control form-control-sm dir-input ec-approval-input"
                aria-label="Approval No. for WO {{ $workorder?->number }}"
            >
        </td>
        <td class="text-center">{{ $ecProcess->date_start?->format('d/M/Y') ?: '' }}</td>
        <td class="text-center">{{ $ecProcess->date_finish?->format('d/M/Y') ?: '' }}</td>
        <td class="text-center">{{ $workorder?->number ?: '' }}</td>
        <td>{{ $ecProcess->notes ?: '' }}</td>
    </tr>
@empty
    <tr>
        <td colspan="9" class="text-center text-secondary py-4">No EC rows found.</td>
    </tr>
@endforelse
