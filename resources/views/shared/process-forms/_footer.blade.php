{{--
    Футер формы процесса.
    Переменные: $process_name
    Опционально: $totalQty — для extra_processes (сумма qty из table_data)
--}}
@php
    $totalQty = $totalQty ?? null;
    if ($totalQty === null && isset($table_data) && ($module ?? '') === 'extra_processes') {
        $totalQty = 0;
        foreach ($table_data as $d) {
            $totalQty += (int)(isset($d['extra_process']) ? ($d['extra_process']->qty ?? 0) : ($d['qty'] ?? 0));
        }
    }
@endphp
<footer>
    <div class="row fs-85" style="width: 100%; padding: 5px 0;">
        <div class="{{ $totalQty !== null ? 'col-4' : 'col-6' }} text-start">
            {{ __('Form #') }} {{ $process_name->form_number ?? 'EXTRA-001' }}
        </div>
        @if($totalQty !== null)
        <div class="col-4 text-center"></div>
        @endif
        <div class="{{ $totalQty !== null ? 'col-4' : 'col-6' }} text-end pe-4">
            {{ __('Rev#0, 15/Dec/2012   ') }}
            @if($totalQty !== null && $totalQty > 0)
            <p class="mb-0"><strong>Total qty: {{ $totalQty }}</strong></p>
            @endif
        </div>
    </div>
</footer>
