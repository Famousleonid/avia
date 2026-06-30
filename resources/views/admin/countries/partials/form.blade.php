@php
    $active = $country?->active ?? true;
@endphp

<div class="row g-3">
    <div class="col-12">
        <label for="{{ $prefix }}Name" class="form-label">{{ __('Country') }}</label>
        <input id="{{ $prefix }}Name"
               name="name"
               type="text"
               class="form-control"
               value="{{ $country?->name }}"
               required
               maxlength="255"
               autocomplete="off"
               autocorrect="on"
               autocapitalize="words"
               spellcheck="true">
    </div>
    <div class="col-12 col-md-6">
        <label for="{{ $prefix }}Alpha2" class="form-label">{{ __('ISO Alpha-2') }}</label>
        <input id="{{ $prefix }}Alpha2"
               name="alpha2"
               type="text"
               class="form-control text-uppercase"
               value="{{ $country?->alpha2 }}"
               required
               maxlength="2"
               autocomplete="off"
               autocorrect="off"
               autocapitalize="characters"
               spellcheck="false">
    </div>
    <div class="col-12 col-md-6">
        <label for="{{ $prefix }}SortOrder" class="form-label">{{ __('Sort') }}</label>
        <input id="{{ $prefix }}SortOrder"
               name="sort_order"
               type="number"
               class="form-control"
               value="{{ $country?->sort_order ?? 0 }}"
               min="0"
               max="65535"
               autocomplete="off"
               spellcheck="false">
    </div>
    <div class="col-12">
        <label class="d-flex align-items-center gap-2 mb-0">
            <input id="{{ $prefix }}Active"
                   name="active"
                   class="form-check-input mt-0"
                   type="checkbox"
                   value="1"
                   @checked($active)>
            <span>{{ __('Active') }}</span>
        </label>
    </div>
</div>
