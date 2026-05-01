{{-- One Traveler form link for the whole grouped block. Vendor and RO are not required here. --}}
<div class="d-flex justify-content-center align-items-center">
    <a href="{{ route('tdr-processes.travelForm', ['id' => $current_tdr->id, 'traveler_group' => $travelerGroup ?? null]) }}" class="btn btn-sm btn-outline-primary travel-form-link" target="_blank">{{ __('Form') }}</a>
</div>
