@php
    $quantumRoQty = $quantumRoQty ?? static function ($value): string {
        if ($value === null || $value === '') {
            return '--';
        }

        return rtrim(rtrim(number_format((float) $value, 4, '.', ''), '0'), '.');
    };
@endphp

@php
    $quantumRoQty = $quantumRoQty ?? static function ($value): string {
        if ($value === null || $value === '') {
            return '--';
        }

        return rtrim(rtrim(number_format((float) $value, 4, '.', ''), '0'), '.');
    };
@endphp

@forelse($quantumUnparsedRows as $line)
    <tr
        data-quantum-line-id="{{ $line->id }}"
        data-quantum-wo="{{ trim((string) $line->wo_number) }}"
        data-quantum-ro="{{ trim((string) $line->ro_number) }}"
    >
        <td>
            <span class="badge {{ $line->apply_status === 'error' ? 'text-bg-danger' : 'text-bg-warning' }}">
                {{ $line->apply_status ?: 'pending' }}
            </span>
        </td>
        <td class="quantum-buffer-message">@include('admin.vendor_tracking.partials.quantum_message', ['line' => $line])</td>
        <td>{{ $line->ro_number ?: '--' }}</td>
        <td>{{ $line->wo_number ?: '--' }}</td>
        <td>{{ $line->vendor_name ?: '--' }}</td>
        <td>{{ $line->pn ?: '--' }}</td>
        <td>{{ $line->description ?: '--' }}</td>
        <td>{{ $line->class ?: '--' }}</td>
        <td><code>{{ $line->bom_ref ?: '--' }}</code></td>
        <td>{{ format_project_date($line->out_date) ?? '--' }}</td>
        <td>{{ format_project_date($line->returned_date) ?? '--' }}</td>
        <td class="text-nowrap">qty - {{ $quantumRoQty($line->qty_repair) }} / {{ $quantumRoQty($line->qty_reserved) }} / {{ $quantumRoQty($line->qty_repaired) }}</td>
        <td class="quantum-buffer-action-col">
            <button
                type="button"
                class="btn btn-outline-secondary btn-sm js-quantum-dismiss-row"
                data-dismiss-url="{{ route('vendor-tracking.quantum-lines.dismiss', ['quantumRoLine' => $line->id]) }}"
                title="Dismiss this unresolved row"
            >
                <i class="bi bi-check2"></i>
            </button>
        </td>
    </tr>
@empty
    <tr class="js-quantum-unparsed-empty">
        <td colspan="13" class="text-center text-muted py-4">No unresolved Quantum RO rows.</td>
    </tr>
@endforelse
