@php
    $option = is_array($kitPrlOption ?? null) ? $kitPrlOption : (array) ($kitPrlOption ?? []);
    $componentId = (int) ($option['component_id'] ?? 0);
    $controllerCrossedOut = ! empty($option['controller_crossed_out']);
    $manualCrossedOut = ! empty($option['manual_crossed_out']);
    $crossedOut = ! empty($option['crossed_out']);
    $interactive = ($kitInteractive ?? false) && ! $controllerCrossedOut && $componentId > 0;
    $showControllerLock = ($kitInteractive ?? false) && $controllerCrossedOut;
    $partNumberMode = ! empty($kitPrlPartNumberMode);
    $classes = trim(implode(' ', array_filter([
        'kit-prl-option-line',
        $kitPrlExtraClass ?? '',
        $crossedOut ? 'kit-prl-option-crossed-out' : '',
        $partNumberMode && $crossedOut ? 'prl-part-number-crossed-out' : '',
        $interactive ? 'kit-prl-manual-toggle' : '',
        $showControllerLock ? 'kit-prl-option-locked' : '',
    ])));
@endphp
<span class="{{ $classes }}"
      data-kit-prl-component-id="{{ $componentId }}"
      data-kit-prl-manual-crossed-out="{{ $manualCrossedOut ? '1' : '0' }}"
      data-kit-prl-controller-crossed-out="{{ $controllerCrossedOut ? '1' : '0' }}"
      data-kit-prl-option-label="{{ $kitPrlLabel ?? '' }}"
      @if($interactive) role="button" tabindex="0" @endif
      @if($partNumberMode) data-prl-component-id="{{ $componentId }}" @endif
      @if($partNumberMode && $crossedOut) data-prl-part-number-crossed-out="1" @endif
      @if($interactive) data-kit-prl-toggle-url="{{ route('tdrs.kit-crossouts.update', ['workorder' => $current_wo->id, 'component' => $componentId]) }}" @endif
      @if(!empty($option['crossout_reason'])) title="{{ $option['crossout_reason'] }}" @elseif($kitInteractive ?? false) title="{{ $controllerCrossedOut ? 'Automatically crossed out; change the source selection to restore it.' : 'Click to cross out or restore this KIT position.' }}" @endif>{{ $kitPrlDisplayValue ?? '' }}</span>
