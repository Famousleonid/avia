<div class="row g-3">
    <div class="col-12">
        <label for="{{ $prefix }}Name" class="form-label">{{ __('Type of Business') }}</label>
        <input id="{{ $prefix }}Name"
               name="name"
               type="text"
               class="form-control"
               value="{{ $companyType?->name }}"
               required
               maxlength="255"
               autocomplete="off"
               autocorrect="on"
               autocapitalize="words"
               spellcheck="true">
    </div>
    <div class="col-12">
        <label for="{{ $prefix }}SortOrder" class="form-label">{{ __('Sort') }}</label>
        <input id="{{ $prefix }}SortOrder"
               name="sort_order"
               type="number"
               class="form-control"
               value="{{ $companyType?->sort_order ?? 0 }}"
               min="0"
               max="65535"
               autocomplete="off"
               spellcheck="false">
    </div>
</div>
