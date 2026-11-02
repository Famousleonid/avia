<tr class="{{ str_contains($row['cells'][0]['style'] ?? '', 'border-left:') ? 'guide-grid-row' : '' }}">
    @foreach($row['cells'] as $cell)
        <td colspan="{{ $cell['colspan'] }}" rowspan="{{ $cell['rowspan'] }}" style="{{ $cell['style'] }}">
            @if($canEdit)
                <textarea rows="1" wrap="{{ $sheet['name'] === 'Items' ? 'off' : 'soft' }}" maxlength="4000" spellcheck="false" data-guide-cell data-sheet="{{ $s }}" data-address="{{ $cell['address'] }}" aria-label="{{ $sheet['name'] }} {{ $cell['address'] }}">{{ $cell['value'] }}</textarea>
            @endif
            <span class="guide-cell-text {{ $canEdit ? 'guide-print-value' : '' }}">{{ $cell['value'] }}</span>
        </td>
    @endforeach
</tr>
