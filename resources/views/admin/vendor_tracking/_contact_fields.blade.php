<div class="row g-2 mb-3" data-vendor-contact-fields>
    <div class="col-12">
        <label class="form-label small mb-1" for="{{ $prefix }}-address">Address</label>
        <textarea id="{{ $prefix }}-address" data-vendor-field="address" class="form-control form-control-sm" rows="3" maxlength="1500"></textarea>
    </div>
    @foreach(['contact_name' => ['Contact name', 'text', 255], 'phone' => ['Phone', 'tel', 80], 'email' => ['Email', 'email', 255], 'vendor_number' => ['Vendor number', 'text', 100], 'ship_via' => ['Default ship via', 'text', 100], 'terms' => ['Default terms', 'text', 100]] as $field => [$label, $type, $length])
    <div class="col-12 col-sm-6">
        <label class="form-label small mb-1" for="{{ $prefix }}-{{ $field }}">{{ $label }}</label>
        <input id="{{ $prefix }}-{{ $field }}" data-vendor-field="{{ $field }}" type="{{ $type }}" class="form-control form-control-sm" maxlength="{{ $length }}">
    </div>
    @endforeach
</div>
