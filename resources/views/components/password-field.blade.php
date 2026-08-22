@props([
    'id',
    'name',
    'label',
    'autocomplete',
    'required' => false,
    'policy' => false,
    'placeholder' => null,
    'labelClass' => 'form-label',
    'showLabel' => true,
])

@php
    $errorId = $id . '-error';
    $requirementsId = $policy ? $id . '-requirements' : null;
    $describedBy = collect([$requirementsId, $errors->has($name) ? $errorId : null])->filter()->implode(' ');
@endphp

@if($showLabel)
    <label for="{{ $id }}" class="{{ $labelClass }}">{{ $label }}</label>
@endif
<div class="input-group">
    <input id="{{ $id }}"
           type="password"
           name="{{ $name }}"
           class="form-control @error($name) is-invalid @enderror"
           autocomplete="{{ $autocomplete }}"
           maxlength="{{ \App\Support\UserPasswordPolicy::maximum() }}"
           @if($required) required @endif
           @if($placeholder) placeholder="{{ $placeholder }}" @endif
           @if($policy)
               minlength="{{ \App\Support\UserPasswordPolicy::minimum() }}"
               pattern=".*@.*"
               data-password-policy-input
           @endif
           @if($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif>
    <button type="button"
            class="btn btn-outline-secondary"
            data-password-toggle="{{ $id }}"
            data-password-label="{{ strtolower($label) }}"
            aria-controls="{{ $id }}"
            aria-pressed="false"
            aria-label="Show {{ strtolower($label) }}">
        <i class="bi bi-eye" aria-hidden="true"></i>
        <span class="visually-hidden">Show password</span>
    </button>
</div>

@error($name)
<div id="{{ $errorId }}" class="invalid-feedback d-block" role="alert">{{ $message }}</div>
@enderror

@if($policy)
    <x-password-requirements :input-id="$id" />
@endif
