@php
    $defaults = collect($tool['inputs'])->mapWithKeys(function ($input) {
        return [$input['key'] => (float) ($input['default'] ?? 0)];
    });
    $rOut = $defaults['r_out'] ?? 0;
    $rIn = $defaults['r_in'] ?? 0;
    $d = $defaults['d'] ?? 0;
    $defaultL = (($rOut * $rOut) >= (($d / 2) * ($d / 2)))
        ? sqrt(($rOut * $rOut) - (($d / 2) * ($d / 2))) - $rIn
        : 0;
    $defaultL = max($defaultL, 0);
@endphp

<div class="tools-pane">
    <div class="tools-workspace js-sleeve37-tool-workspace"
         data-default-l="{{ number_format($defaultL, 3, '.', '') }}"
         data-tool-key="{{ $tool['key'] }}"
         data-workorder-id="{{ $workorderId ?? '' }}"
         data-save-url="{{ route('tools.save') }}">
        <div class="tools-column">
            <section class="tools-card is-paper">
                <div class="tools-card-head">
                    <h2 class="tools-card-title">Reference Drawing</h2>
                    <p class="tools-card-subtitle">{{ $tool['label'] }}</p>
                </div>
                <div class="tools-card-body">
                    <div class="tools-reference-frame">
                        <img src="{{ $tool['image'] }}" alt="{{ $tool['label'] }}">
                    </div>
                </div>
            </section>

            <section class="tools-card">
                <div class="tools-card-body">
                    <div class="tools-input-grid">
                        @foreach($tool['inputs'] as $input)
                            <div class="tools-field">
                                <label class="tools-field-label" for="{{ $tool['key'] }}-{{ $input['key'] }}">
                                    {{ $input['label'] }}
                                </label>
                                <input
                                    type="number"
                                    class="form-control js-tool-input"
                                    id="{{ $tool['key'] }}-{{ $input['key'] }}"
                                    data-key="{{ $input['key'] }}"
                                    value="{{ $input['default'] }}"
                                    step="{{ $input['step'] }}"
                                >
                            </div>
                        @endforeach
                        <div class="tools-field">
                            <label class="tools-field-label" for="{{ $tool['key'] }}-result-l">
                                L
                            </label>
                            <div class="tools-result-inline js-result-l" id="{{ $tool['key'] }}-result-l">
                                {{ number_format($defaultL, 3) }}
                            </div>
                            <div class="tools-result-error js-result-error"></div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <div class="tools-column">
            <section class="tools-print-sheet js-print-sheet">
                <div class="tools-print-header">
                    <div class="tools-print-header-main">
                        <h2 class="tools-print-title">{{ $tool['print_title'] }}</h2>
                        <p class="tools-print-wo-line">
                            WO <strong>{{ $prefillWorkorder ?: '---' }}</strong>
                            &nbsp;&nbsp;&nbsp;
                            Technician: <strong>{{ $prefillUserName ?: '---' }}</strong>
                        </p>
                    </div>
                    <div>
                        <p class="tools-print-meta">{{ $tool['print_subtitle'] }}</p>
                        <p class="tools-print-meta">Calculated L: <strong class="js-result-l-inline">{{ number_format($defaultL, 3) }}</strong></p>
                    </div>
                </div>

                <div class="tools-formula-sheet">
                    <div class="tools-formula-line">L = &radic;(R out&sup2; - (D/2)&sup2;) - R in</div>
                    <div class="tools-formula-line">M = &radic;(R out&sup2; - (D/2)&sup2;) - &radic;(R in&sup2; - (D/2)&sup2;)</div>
                </div>

                <div class="tools-preview-stage">
                    <div class="tools-preview-svg-wrap">
                        <svg class="tools-preview-svg" viewBox="0 0 860 520" xmlns="http://www.w3.org/2000/svg" aria-label="Sleeve print preview">
                            <defs>
                                <marker id="arrow-end-{{ $tool['key'] }}" markerWidth="8" markerHeight="8" refX="7" refY="4" orient="auto">
                                    <path d="M 0 0 L 8 4 L 0 8 z" fill="#495057"/>
                                </marker>
                                <marker id="arrow-start-{{ $tool['key'] }}" markerWidth="8" markerHeight="8" refX="1" refY="4" orient="auto">
                                    <path d="M 8 0 L 0 4 L 8 8 z" fill="#495057"/>
                                </marker>
                            </defs>

                            <rect x="0" y="0" width="860" height="520" fill="#ffffff"/>

                            <text x="430" y="58" text-anchor="middle" font-size="24" font-weight="700" fill="#212529">
                                {{ $tool['label'] }}
                            </text>
                            <path
                                d="M 220 150
                                   L 520 150
                                   Q 485 260 520 370
                                   L 220 370
                                   Z"
                                fill="none"
                                stroke="#6c757d"
                                stroke-width="4"
                                stroke-linejoin="round"
                                stroke-linecap="round"
                            />

                            <line x1="220" y1="260" x2="360" y2="260" stroke="#343a40" stroke-width="1.4" marker-start="url(#arrow-start-{{ $tool['key'] }})"/>
                            <line x1="380" y1="260" x2="500" y2="260" stroke="#343a40" stroke-width="1.4" marker-end="url(#arrow-end-{{ $tool['key'] }})"/>

                            <line x1="510" y1="260" x2="640" y2="260" stroke="#343a40" stroke-width="1.4" marker-end="url(#arrow-end-{{ $tool['key'] }})"/>
                            <line x1="510" y1="260" x2="535" y2="248" stroke="#343a40" stroke-width="1.2"/>
                            <line x1="510" y1="260" x2="535" y2="272" stroke="#343a40" stroke-width="1.2"/>

                            <line x1="220" y1="370" x2="220" y2="448" stroke="#343a40" stroke-width="1.1"/>
                            <line x1="520" y1="370" x2="520" y2="448" stroke="#343a40" stroke-width="1.1"/>
                            <line x1="220" y1="430" x2="520" y2="430" stroke="#343a40" stroke-width="1.4" marker-start="url(#arrow-start-{{ $tool['key'] }})" marker-end="url(#arrow-end-{{ $tool['key'] }})"/>

                            <text x="370" y="250" text-anchor="middle" font-size="24" font-weight="700" fill="#212529">
                                L = <tspan class="js-svg-l">{{ number_format($defaultL, 3) }}</tspan>
                            </text>

                            <text x="600" y="240" text-anchor="middle" font-size="20" font-weight="700" fill="#212529">
                                R in = <tspan class="js-svg-r-in">{{ number_format($rIn, 3) }}</tspan>
                            </text>

                            <text x="370" y="422" text-anchor="middle" font-size="24" font-weight="700" fill="#212529">
                                M = <tspan class="js-svg-m">
                                    {{ number_format((($rOut * $rOut) >= (($d / 2) * ($d / 2)) && ($rIn * $rIn) >= (($d / 2) * ($d / 2))) ? max(sqrt(($rOut * $rOut) - (($d / 2) * ($d / 2))) - sqrt(($rIn * $rIn) - (($d / 2) * ($d / 2))), 0) : 0, 3) }}
                                </tspan>
                            </text>
                        </svg>
                    </div>

                    <div class="tools-preview-side"></div>
                </div>

                <div class="tools-print-actions">
                    <button type="button" class="btn btn-primary js-print-tool">
                        <i class="bi bi-printer me-2"></i>Print
                    </button>
                </div>
            </section>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.js-sleeve37-tool-workspace').forEach(function (workspace) {
            const inputs = Array.from(workspace.querySelectorAll('.js-tool-input'));
            const lTargets = Array.from(workspace.querySelectorAll('.js-result-l'));
            const inlineTargets = Array.from(workspace.querySelectorAll('.js-result-l-inline'));
            const svgTargets = Array.from(workspace.querySelectorAll('.js-svg-l'));
            const svgMTargets = Array.from(workspace.querySelectorAll('.js-svg-m'));
            const svgRInTargets = Array.from(workspace.querySelectorAll('.js-svg-r-in'));
            const errorTargets = Array.from(workspace.querySelectorAll('.js-result-error'));
            const printButton = workspace.querySelector('.js-print-tool');
            const rOutInput = workspace.querySelector('.js-tool-input[data-key="r_out"]');
            const rInInput = workspace.querySelector('.js-tool-input[data-key="r_in"]');
            const dInput = workspace.querySelector('.js-tool-input[data-key="d"]');
            const workorderId = Number.parseInt(workspace.dataset.workorderId || '0', 10) || 0;
            const toolKey = workspace.dataset.toolKey || '';
            const saveUrl = workspace.dataset.saveUrl || '';
            let saveTimer = null;

            const parseValue = function (input) {
                const raw = String(input.value || '').trim().replace(',', '.');
                const value = Number.parseFloat(raw);

                return Number.isFinite(value) ? value : 0;
            };

            const formatValue = function (value) {
                return value.toFixed(3);
            };

            const getValues = function () {
                return Object.fromEntries(inputs.map(function (input) {
                    return [input.dataset.key, parseValue(input)];
                }));
            };

            const calculateL = function (values) {
                const rOut = Number(values?.r_out || 0);
                const rIn = Number(values?.r_in || 0);
                const d = Number(values?.d || 0);
                const underRoot = (rOut * rOut) - Math.pow(d / 2, 2);

                if (underRoot < 0) {
                    return 0;
                }

                return Math.max(Math.sqrt(underRoot) - rIn, 0);
            };

            const calculateM = function (values) {
                const rOut = Number(values?.r_out || 0);
                const rIn = Number(values?.r_in || 0);
                const d = Number(values?.d || 0);
                const outerRoot = (rOut * rOut) - Math.pow(d / 2, 2);
                const innerRoot = (rIn * rIn) - Math.pow(d / 2, 2);

                if (outerRoot < 0 || innerRoot < 0) {
                    return 0;
                }

                return Math.max(Math.sqrt(outerRoot) - Math.sqrt(innerRoot), 0);
            };

            const render = function () {
                const values = getValues();
                const rOut = values.r_out || 0;
                const rIn = values.r_in || 0;
                const d = values.d || 0;
                const isRadiusValid = rOut === 0 ? true : rIn < rOut;
                const isOuterChordValid = rOut === 0 ? true : d <= (rOut * 2);
                const isInnerChordValid = rIn === 0 ? true : d <= (rIn * 2);
                const isChordValid = isOuterChordValid && isInnerChordValid;
                const l = (isRadiusValid && isChordValid) ? calculateL(values) : 0;
                const m = (isRadiusValid && isChordValid) ? calculateM(values) : 0;
                const formatted = formatValue(l);
                const formattedM = formatValue(m);
                const formattedRIn = formatValue(rIn);
                let errorMessage = '';

                if (!isRadiusValid) {
                    errorMessage = 'R in must be less than R out.';
                } else if (!isOuterChordValid) {
                    errorMessage = 'D must be less than or equal to 2 x R out.';
                } else if (!isInnerChordValid) {
                    errorMessage = 'D must be less than or equal to 2 x R in.';
                }

                if (rInInput) {
                    rInInput.classList.toggle('is-invalid', !isRadiusValid);
                    rInInput.setCustomValidity(isRadiusValid ? '' : 'R in must be less than R out');
                    rInInput.title = isRadiusValid ? '' : 'R in must be less than R out';
                }

                if (rOutInput) {
                    rOutInput.classList.toggle('is-invalid', !isRadiusValid);
                }

                if (dInput) {
                    dInput.classList.toggle('is-invalid', !isChordValid);
                    dInput.setCustomValidity(errorMessage && !isRadiusValid ? '' : (isChordValid ? '' : errorMessage));
                    dInput.title = isChordValid ? '' : errorMessage;
                }

                lTargets.forEach(function (target) { target.textContent = formatted; });
                inlineTargets.forEach(function (target) { target.textContent = formatted; });
                svgTargets.forEach(function (target) { target.textContent = formatted; });
                svgMTargets.forEach(function (target) { target.textContent = formattedM; });
                svgRInTargets.forEach(function (target) { target.textContent = formattedRIn; });
                errorTargets.forEach(function (target) { target.textContent = errorMessage; });
            };

            const saveInputs = function () {
                if (!workorderId || !toolKey || !saveUrl) {
                    return;
                }

                fetch(saveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({
                        workorder_id: workorderId,
                        tool_key: toolKey,
                        input_values: getValues(),
                    }),
                }).catch(function () {
                    // Keep the calculator usable if autosave is temporarily unavailable.
                });
            };

            const queueSave = function () {
                if (!workorderId || !toolKey || !saveUrl) {
                    return;
                }

                window.clearTimeout(saveTimer);
                saveTimer = window.setTimeout(saveInputs, 350);
            };

            inputs.forEach(function (input) {
                input.addEventListener('input', render);
                input.addEventListener('input', queueSave);
                input.addEventListener('change', render);
                input.addEventListener('change', queueSave);
                input.addEventListener('keyup', render);
            });

            printButton?.addEventListener('click', function () {
                window.print();
            });

            render();
        });
    });
</script>
