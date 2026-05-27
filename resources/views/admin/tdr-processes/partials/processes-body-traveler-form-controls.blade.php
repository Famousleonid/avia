{{-- One Traveler form link for the whole grouped block. Vendor is appended from this select in JS. --}}
<div class="d-flex flex-column gap-1 justify-content-center align-items-stretch">
    <select class="form-select form-select-sm vendor-select traveler-vendor-select"
            data-tdr-process-id="{{ $tdrProcessRow->id ?? '' }}"
            aria-label="{{ __('Vendor') }}">
        <option value="">{{ __('No vendor') }}</option>
        @foreach($vendors as $vendor)
            <option value="{{ $vendor->id }}" @selected((int) ($tdrProcessRow->vendor_id ?? 0) === (int) $vendor->id)>
                {{ $vendor->name }}
            </option>
        @endforeach
    </select>
    <a href="{{ route('tdr-processes.travelForm', ['id' => $current_tdr->id, 'traveler_group' => $travelerGroup ?? null]) }}"
       class="btn btn-sm btn-outline-primary travel-form-link"
       data-tdr-process-id="{{ $tdrProcessRow->id ?? '' }}"
       target="_blank">{{ __('Form') }}</a>
</div>
