@forelse($ecRows as $ecProcess)
    @php
        $tdr = $ecProcess->tdr;
        $workorder = $tdr?->workorder;
        $component = $tdr?->component ?? $tdr?->orderComponent;
        $description = trim((string) ($component?->name ?: $ecProcess->description ?: $tdr?->description));
        $manual = $workorder?->unit?->manual;
        $endAssyPartNumber = $manual?->unit_name_training;
        $applicability = $manual?->plane?->type;
        $workorderNumber = $workorder?->number ?: '';
    @endphp
    <tr>
        <td>{{ $description !== '' ? $description : '-' }}</td>
        <td class="text-center">{{ $endAssyPartNumber ?: '' }}</td>
        <td class="text-center">{{ $component?->part_number ?: '' }}</td>
        <td class="text-center">{{ $applicability ?: '' }}</td>
        <td style="min-width:170px">
            <input type="text" class="form-control form-control-sm dir-input ec-conc mb-1"
                   data-id="{{ $ecProcess->id }}" data-field="concession_number"
                   aria-label="Approval No. for WO {{ $workorderNumber }}"
                   value="{{ $ecProcess->concession_number }}" placeholder="Approval No.">
            <input type="date" class="form-control form-control-sm dir-input ec-conc mb-1"
                   data-id="{{ $ecProcess->id }}" data-field="concession_date"
                   aria-label="Approval Date for WO {{ $workorderNumber }}"
                   value="{{ $ecProcess->concession_date?->format('Y-m-d') }}">
            <input type="text" class="form-control form-control-sm dir-input ec-conc"
                   data-id="{{ $ecProcess->id }}" data-field="concession_oem"
                   aria-label="Approval OEM for WO {{ $workorderNumber }}"
                   value="{{ $ecProcess->concession_oem }}" placeholder="OEM">
        </td>
        <td class="text-center">{{ $ecProcess->date_start?->format('d/M/Y') ?: '' }}</td>
        <td class="text-center">{{ $ecProcess->date_finish?->format('d/M/Y') ?: '' }}</td>
        <td class="text-center">{{ $workorderNumber }}</td>
        <td>{{ $ecProcess->notes ?: '' }}</td>
    </tr>
@empty
    <tr>
        <td colspan="9" class="text-center text-secondary py-4">No EC rows found.</td>
    </tr>
@endforelse
