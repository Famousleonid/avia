<div class="main-ro-scroll">
    <table class="main-ro-table" aria-label="WO {{ $workorder->number }} synchronized Quantum RO">
        <thead>
        @foreach(['description' => 'Description', 'pn' => 'Part No.', 'serial' => 'Serial No.'] as $field => $label)
            <tr>
                <th scope="row">{{ $label }}</th>
                @foreach($matrix['columns'] as $column)
                    <th scope="col" @if($column['complete']) class="main-ro-part-complete" title="All listed processes returned" @endif>{{ $column[$field] ?: '—' }}</th>
                @endforeach
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
                        @foreach($row['pending'][$key] ?? [] as $position)
                            <span class="main-ro-entry main-ro-step-pending" title="Process {{ $position }}: not sent, RO not assigned" aria-label="Process {{ $position }}: not sent, RO not assigned">{{ $position }}</span>
                        @endforeach
                        </span>
                    </td>
                @endforeach
            </tr>
        @empty
            <tr><td class="text-center text-muted py-4">No synchronized RO lines or planned part processes for this workorder.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
