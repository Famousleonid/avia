@php
    $option = is_array($kitPrlOption ?? null) ? $kitPrlOption : (array) ($kitPrlOption ?? []);
    $componentId = (int) ($option['component_id'] ?? 0);
    $controllerCrossedOut = ! empty($option['controller_crossed_out']);
    $manualCrossedOut = ! empty($option['manual_crossed_out']);
    $crossedOut = ! empty($option['crossed_out']);
    $sharedBushing = !empty($option['bushing_selection']);
    $interactive = ($kitInteractive ?? false) && (! $controllerCrossedOut || $sharedBushing) && $componentId > 0;
    $showControllerLock = ($kitInteractive ?? false) && $controllerCrossedOut;
    $partNumberMode = ! empty($kitPrlPartNumberMode);
    $sourceReason = $crossedOut ? trim((string) ($option['crossout_reason'] ?? '')) : '';
    $crossoutTitle = collect([
        $sourceReason,
        $manualCrossedOut ? 'Manually crossed out' : null,
    ])->filter()->unique()->implode('; ');
    $idleTitle = ($kitInteractive ?? false) ? 'Click to cross out or restore this KIT position.' : '';
    $optionTitle = $crossedOut
        ? 'Crossed out: '.($crossoutTitle ?: 'Automatically excluded by selection rules')
        : $idleTitle;
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
      @if($sharedBushing) data-bushing-selection-id="{{ $componentId }}" @endif
      data-kit-prl-manual-crossed-out="{{ $manualCrossedOut ? '1' : '0' }}"
      data-kit-prl-controller-crossed-out="{{ $controllerCrossedOut ? '1' : '0' }}"
      data-kit-prl-option-label="{{ $kitPrlLabel ?? '' }}"
      data-kit-prl-source-reason="{{ $sourceReason }}"
      data-kit-prl-idle-title="{{ $idleTitle }}"
      @if($interactive) role="button" tabindex="0" @endif
      @if($partNumberMode) data-prl-component-id="{{ $componentId }}" @endif
      @if($partNumberMode && $crossedOut) data-prl-part-number-crossed-out="1" @endif
      @if($interactive) data-kit-prl-toggle-url="{{ route('tdrs.kit-crossouts.update', ['workorder' => $current_wo->id, 'component' => $componentId]) }}" @endif
      title="{{ $optionTitle }}">{{ $kitPrlDisplayValue ?? '' }}@if(($kitPrlShowEffectivity ?? false) && !empty($option['effectivity_label'])) — {{ $option['effectivity_label'] }}@endif</span>
