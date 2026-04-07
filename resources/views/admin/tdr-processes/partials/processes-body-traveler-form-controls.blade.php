{{-- Vendor + Rep + Traveler link (одна зона для всего блока Traveler) --}}
<div class="d-flex flex-wrap gap-1 justify-content-center align-items-center">
    <input type="text" class="form-control form-control-sm travel-repair-num" style="width:108px" placeholder="{{ __('Rep.#') }}" maxlength="64">
    <select class="form-select form-select-sm travel-vendor-select" style="max-width:100px">
        <option value="">{{ __('Vendor') }}</option>
        @foreach($vendors as $vendor)
            <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
        @endforeach
    </select>
    <a href="{{ route('tdr-processes.travelForm', ['id' => $current_tdr->id]) }}" class="btn btn-sm btn-outline-primary travel-form-link" target="_blank">{{ __('Traveler') }}</a>
</div>
