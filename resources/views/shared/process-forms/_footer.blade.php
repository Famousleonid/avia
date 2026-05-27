{{--
    Footer for process forms.
    Variables: $process_name
--}}
<footer>
    <div class="row fs-85" style="width: 100%; padding: 5px 0;">
        <div class="col-6 text-start">
            {{ __('Form #') }} {{ $process_name->form_number ?? 'EXTRA-001' }}
        </div>
        <div class="col-6 text-end pe-4">
            @php
                $sheetTitle = (string) ($process_name->process_sheet_name ?? $process_name->name ?? '');
            @endphp
            @if($sheetTitle === 'SILVER PLATING')
                {{ __('Rev#0, 12/May/2026   ') }}
            @else
                {{ __('Rev#0, 15/Dec/2012   ') }}
            @endif
        </div>
    </div>
</footer>
