<div class="bushing-partial w-100"
     data-has-wo-bushing="{{ $woBushing ? '1' : '0' }}"
     data-has-bushings="{{ $bushings->flatten()->count() > 0 ? '1' : '0' }}"
     data-wo-bushing-id="{{ $woBushing ? $woBushing->id : '' }}"
     data-edit-url="{{ $woBushing ? route('wo_bushings.edit', $woBushing->id) : '' }}"
     data-spec-form-url="{{ $woBushing ? route('wo_bushings.specProcessForm', $woBushing->id) : '' }}">
    @include('admin.wo_bushings.partials.bushing-content')
</div>
