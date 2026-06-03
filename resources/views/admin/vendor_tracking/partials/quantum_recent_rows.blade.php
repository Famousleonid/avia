@foreach($quantumRecentRows as $line)
    @php
        $status = $line->apply_status ?: 'pending';
        $woNumberDigits = preg_replace('/\D+/', '', (string) $line->wo_number);
        $isOldWoNotFound = (
            $status === 'WO not found: old'
            || (
                in_array($status, ['N/A', 'unresolved'], true)
                && $woNumberDigits !== ''
                && (int) $woNumberDigits < 107000
                && (
                    str_contains((string) $line->apply_message, 'Workorder not found')
                    || str_contains((string) $line->apply_message, 'WO not found')
                )
            )
        );
        $isWoNotFound = (
            ! $isOldWoNotFound
            && in_array($status, ['N/A', 'unresolved'], true)
            && (
                str_contains((string) $line->apply_message, 'Workorder not found')
                || str_contains((string) $line->apply_message, 'WO not found')
            )
        );
        $statusLabel = $isOldWoNotFound ? 'WO not found: old' : ($isWoNotFound ? 'WO not found' : $status);
        $statusClass = match ($statusLabel) {
            'applied' => 'text-bg-success',
            'error' => 'text-bg-danger',
            'unresolved' => 'text-bg-warning',
            'WO not found' => 'text-bg-secondary',
            'WO not found: old' => 'text-bg-secondary',
            default => 'text-bg-secondary',
        };
        $seenAt = $line->last_seen_at ?: ($line->updated_at ?: ($line->first_seen_at ?: $line->created_at));
        $target = trim((string) ($line->applied_target_table ?? '')) !== '' && ! empty($line->applied_target_id)
            ? ($line->applied_target_table . ' #' . $line->applied_target_id)
            : '--';
    @endphp
    <tr data-quantum-line-id="{{ $line->id }}">
        <td>
            @if($seenAt)
                <span class="d-block">{{ format_project_date($seenAt) ?? '--' }}</span>
                <span class="d-block small text-muted">{{ $seenAt->format('H:i') }}</span>
            @else
                --
            @endif
        </td>
        <td><span class="badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
        <td>{{ $line->ro_number ?: '--' }}</td>
        <td>{{ $line->wo_number ?: '--' }}</td>
        <td>{{ $line->vendor_name ?: '--' }}</td>
        <td>{{ $line->pn ?: '--' }}</td>
        <td>{{ $line->class ?: '--' }}</td>
        <td><code>{{ $line->bom_ref ?: '--' }}</code></td>
        <td>{{ format_project_date($line->out_date) ?? '--' }}</td>
        <td>{{ format_project_date($line->returned_date) ?? '--' }}</td>
        <td>{{ $target }}</td>
        <td class="quantum-buffer-message">{{ $line->apply_message ?: 'Not parsed yet.' }}</td>
    </tr>
@endforeach
