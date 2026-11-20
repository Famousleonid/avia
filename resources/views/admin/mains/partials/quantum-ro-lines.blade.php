@php
    $emptyColumnCount = max(0, 7 - count($matrix['columns']));
    $displayColumnCount = 1 + count($matrix['columns']) + $emptyColumnCount;
@endphp
<div class="main-ro-scroll">
    @foreach($lines->filter(fn ($line) => $line->apply_status === 'unresolved' && str_starts_with((string) $line->apply_message, 'TDR S/N mismatch:')) as $conflict)
        <div class="alert alert-warning mb-2" role="alert">{{ $conflict->apply_message }}</div>
    @endforeach
    <table class="main-ro-table" aria-label="WO {{ $workorder->number }} synchronized Quantum RO">
        <thead>
        @foreach(['description' => 'Description', 'pn' => 'Part No.', 'serial' => 'Serial No.'] as $field => $label)
            <tr>
                <th scope="row">{{ $label }}</th>
                @foreach($matrix['columns'] as $column)
                    <th scope="col" @if($column['complete']) class="main-ro-part-complete" title="All listed processes returned" @endif>{{ $column[$field] ?: '—' }}</th>
                @endforeach
                @for($blank = 0; $blank < $emptyColumnCount; $blank++)
                    <th class="main-ro-empty-column" aria-hidden="true"></th>
                @endfor
            </tr>
        @endforeach
        </thead>
        <tbody>
        @forelse($matrix['rows'] as $row)
            <tr>
                <th scope="row">{{ $row['label'] }}</th>
                @foreach($matrix['columns'] as $key => $column)
                    <td data-ro-pn="{{ $column['pn'] }}" data-ro-serial="{{ $column['serial'] }}" data-ro-ref="{{ $row['ref'] }}">
                        <span class="main-ro-orders">
                        @foreach($row['cells'][$key] ?? [] as $line)
                            <span class="main-ro-entry">
                                <button type="button" class="main-ro-number {{ $line->returned_date ? 'main-ro-returned' : 'main-ro-pending' }}" aria-expanded="false"
                                        aria-label="Details for {{ $line->ro_number ?: 'RO' }} — {{ $line->returned_date ? 'Returned' : 'Not returned' }}">{{ $line->ro_number ?: '—' }}</button>
                                <template class="main-ro-details">
                                    <div class="main-ro-popover">
                                        <strong>{{ $line->ro_number ?: '—' }}</strong>
                                        <div>{{ $line->vendor_name ?: '—' }}</div>
                                        <div class="mt-2">Sent: <span>@projectDate($line->out_date)</span></div>
                                        <div>Returned: <span>@projectDate($line->returned_date)</span></div>
                                    </div>
                                </template>
                            </span>
                        @endforeach
                        @foreach($row['internal'][$key] ?? [] as $internal)
                            <span class="main-ro-entry">
                                <button type="button" class="main-ro-number {{ $internal['returned'] ? 'main-ro-returned' : 'main-ro-pending' }}" aria-expanded="false"
                                        aria-label="Details for AT — {{ $internal['returned'] ? 'Returned' : 'Not returned' }}">AT</button>
                                <template class="main-ro-details">
                                    <div class="main-ro-popover">
                                        <strong>AT</strong>
                                        <div>AVIATECHNIK — {{ $internal['process'] }}</div>
                                        <div class="mt-2">Sent: <span>@projectDate($internal['sent'])</span></div>
                                        <div>Returned: <span>@projectDate($internal['returned'])</span></div>
                                    </div>
                                </template>
                            </span>
                        @endforeach
                        @foreach($row['pending'][$key] ?? [] as $position)
                            <span class="main-ro-entry main-ro-step-pending" title="Process {{ $position }}: not sent, RO not assigned" aria-label="Process {{ $position }}: not sent, RO not assigned">{{ $position }}</span>
                        @endforeach
                        </span>
                    </td>
                @endforeach
                @for($blank = 0; $blank < $emptyColumnCount; $blank++)
                    <td class="main-ro-empty-column" aria-hidden="true"></td>
                @endfor
            </tr>
        @empty
            <tr><td colspan="{{ $displayColumnCount }}" class="text-center text-muted py-4">No synchronized RO lines or planned part processes for this workorder.</td></tr>
        @endforelse
            <tr class="main-ro-empty-row" aria-hidden="true">
                @for($cell = 0; $cell < $displayColumnCount; $cell++)
                    <td></td>
                @endfor
            </tr>
        </tbody>
    </table>
</div>
