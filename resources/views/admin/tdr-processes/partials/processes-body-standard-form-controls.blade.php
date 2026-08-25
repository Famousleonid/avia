@php
    $combinedFormProcessId = $combinedFormProcessId ?? null;
    $formParams = ['tdr_process' => $tdrProcessRow->id];
    if ($combinedFormProcessId !== null) {
        $formParams['process_id'] = $combinedFormProcessId;
    }
    $formParams = array_merge($formParams, $formRouteExtraParams);
@endphp

{{-- слева с отступом: кнопки Form стоят ровно по вертикали вне зависимости от наличия кнопки doc --}}
<div class="d-flex gap-1 justify-content-start align-items-center process-form-controls ps-2">
    @if($canPrintForm)
        <input type="checkbox"
               class="form-check-input mt-0 combined-form-select"
               data-tdr-process-id="{{ $tdrProcessRow->id }}"
               data-process-name-id="{{ $tdrProcessRow->process_names_id }}"
               @if($combinedFormProcessId !== null) data-process-id="{{ $combinedFormProcessId }}" @endif
               aria-label="{{ __('Select process for combined form') }}"
               title="{{ __('Select process for combined form') }}">
        <select class="form-select form-select-sm vendor-select"
                style="width: 85px"
                data-tdr-process-id="{{ $tdrProcessRow->id }}"
                @if($combinedFormProcessId !== null) data-process="{{ $combinedFormProcessId }}" @endif>
            <option value="">Select Vendor</option>
            @foreach($vendors as $vendor)
                <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
            @endforeach
        </select>
        <a href="{{ route('tdr-processes.show', $formParams) }}"
           class="btn btn-sm btn-outline-primary form-link d-inline-flex align-items-center justify-content-center"
           style="width: 60px"
           data-tdr-process-id="{{ $tdrProcessRow->id }}"
           @if($combinedFormProcessId !== null) data-process="{{ $combinedFormProcessId }}" @endif
           target="_blank">{{ __('Form') }}</a>
    @endif
    @include('admin.tdr-processes.partials.processes-body-document-controls')
</div>
