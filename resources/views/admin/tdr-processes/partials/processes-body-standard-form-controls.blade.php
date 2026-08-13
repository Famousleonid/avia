@php
    $combinedFormProcessId = $combinedFormProcessId ?? null;
    $formParams = ['tdr_process' => $tdrProcessRow->id];
    if ($combinedFormProcessId !== null) {
        $formParams['process_id'] = $combinedFormProcessId;
    }
    $formParams = array_merge($formParams, $formRouteExtraParams);
@endphp

@if($canPrintForm)
    <div class="d-flex gap-1 justify-content-center align-items-center process-form-controls">
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
    </div>
@else
    <div class="d-flex gap-2 justify-content-center"></div>
@endif
