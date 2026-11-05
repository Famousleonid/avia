<div class="bushing-partial w-100"
     data-has-wo-bushing="{{ $woBushing ? '1' : '0' }}"
     data-has-bushings="{{ $bushings->flatten()->count() > 0 ? '1' : '0' }}"
     data-wo-bushing-id="{{ $woBushing ? $woBushing->id : '' }}"
     data-edit-url="{{ $woBushing ? route('wo_bushings.edit', $woBushing->id) : '' }}"
     data-spec-form-url="{{ ($woBushing && ($hasBushingSpecProcessBatches ?? false)) ? route('wo_bushings.specProcessForm', $woBushing->id) : '' }}">
    @include('admin.wo_bushings.partials.bushing-content')
</div>
@include('admin.wo_bushings.partials.selection-assets')
{{-- This table is refreshed in already-open TDR pages after deployment. Load its own handlers too. --}}
<script src="{{ asset('js/bushing-attachments.js') }}?v={{ filemtime(public_path('js/bushing-attachments.js')) }}"></script>
