@php
    $prefix = $prefix ?? 'unit';
@endphp

<div class="row g-3">
    <div class="col-12 col-md-6">
        <label for="{{ $prefix }}PartNumber" class="form-label">{{ __('Part Number') }}</label>
        <input id="{{ $prefix }}PartNumber"
               type="text"
               class="form-control"
               name="part_number"
               required
               maxlength="255">
    </div>

    <div class="col-12 col-md-6">
        <label for="{{ $prefix }}ManualId" class="form-label">{{ __('CMM') }}</label>
        <select id="{{ $prefix }}ManualId" class="form-select unit-manual-select" name="manual_id">
            <option value="">{{ __('Manual pending') }}</option>
            @foreach($manuals as $manual)
                <option value="{{ $manual->id }}">
                    {{ $manual->number ?: '-' }} {{ $manual->title }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-12 col-md-6">
        <label for="{{ $prefix }}Name" class="form-label">{{ __('Name') }}</label>
        <input id="{{ $prefix }}Name"
               type="text"
               class="form-control"
               name="name"
               maxlength="255">
    </div>

    <div class="col-12 col-md-6">
        <label for="{{ $prefix }}EffCode" class="form-label">{{ __('Eff Code') }}</label>
        <input id="{{ $prefix }}EffCode"
               type="text"
               class="form-control"
               name="eff_code"
               maxlength="255">
    </div>

    <div class="col-12">
        <label for="{{ $prefix }}Description" class="form-label">{{ __('Description') }}</label>
        <input id="{{ $prefix }}Description"
               type="text"
               class="form-control"
               name="description"
               maxlength="255">
    </div>

    <div class="col-12">
        <div class="form-check">
            <input id="{{ $prefix }}Verified"
                   type="checkbox"
                   class="form-check-input"
                   name="verified"
                   value="1"
                   checked>
            <label for="{{ $prefix }}Verified" class="form-check-label">{{ __('Verified') }}</label>
        </div>
    </div>
</div>
