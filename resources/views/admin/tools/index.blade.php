@extends('admin.master')

@section('content')
    <style>
        .tools-page {
            padding: 1rem;
        }

        .tools-shell {
            background: #fff;
            border: 1px solid rgba(52, 58, 64, 0.12);
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 16px 40px rgba(33, 37, 41, 0.07);
        }

        .tools-head {
            padding: 1rem 1rem 0;
            background:
                radial-gradient(circle at top left, rgba(255, 255, 255, 0.12), transparent 30%),
                linear-gradient(180deg, #707881 0%, #5e676f 100%);
            border-bottom: 1px solid rgba(33, 37, 41, 0.18);
        }

        .tools-title {
            margin: 0 0 .75rem;
            color: #f8f9fa;
            font-size: 1.4rem;
            font-weight: 700;
            letter-spacing: .01em;
        }

        .tools-head-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .tools-head-left {
            display: flex;
            align-items: center;
            gap: .85rem;
            flex-wrap: wrap;
        }

        .tools-back-btn {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            padding: .5rem .85rem;
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 10px;
            color: #fff;
            background: rgba(255, 255, 255, 0.10);
            text-decoration: none;
            font-size: .92rem;
            font-weight: 700;
            transition: background-color .15s ease, border-color .15s ease;
        }

        .tools-back-btn:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.16);
            border-color: rgba(255, 255, 255, 0.28);
        }

        .tools-tabs {
            gap: .35rem;
            border-bottom: 0;
            flex-wrap: wrap;
            padding-bottom: 0;
        }

        .tools-tabs .nav-link {
            border: 1px solid rgba(255, 255, 255, 0.16);
            border-bottom: 0;
            border-radius: 12px 12px 0 0;
            color: #eef2f5;
            background: rgba(255, 255, 255, 0.10);
            padding: .6rem .95rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .tools-tabs .nav-link.active {
            background: #fff;
            color: #0d6efd;
            border-color: rgba(13, 110, 253, 0.24);
        }

        .tools-pane {
            padding: 1rem;
            background: #fff;
        }

        .tools-workspace {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            gap: 1rem;
            align-items: start;
        }

        .tools-column {
            min-width: 0;
        }

        .tools-card {
            border: 1px solid rgba(52, 58, 64, 0.12);
            border-radius: 14px;
            background: #fff;
            overflow: hidden;
            margin-bottom: 1rem;
        }

        .tools-card.is-paper {
            background:
                linear-gradient(180deg, rgba(120, 93, 42, 0.06) 0%, rgba(255, 253, 244, 0.96) 12%, rgba(246, 238, 214, 0.98) 100%);
            border: 1px solid rgba(120, 93, 42, 0.22);
            box-shadow:
                inset 0 0 0 1px rgba(255, 255, 255, 0.28),
                0 12px 28px rgba(77, 57, 23, 0.10);
        }

        .tools-card-head {
            padding: .9rem 1rem;
            border-bottom: 1px solid rgba(52, 58, 64, 0.08);
            background: linear-gradient(180deg, rgba(248, 249, 250, 0.96) 0%, #fff 100%);
        }

        .tools-card.is-paper .tools-card-head {
            background: linear-gradient(180deg, rgba(160, 134, 86, 0.10) 0%, rgba(255, 250, 235, 0.72) 100%);
            border-bottom-color: rgba(120, 93, 42, 0.18);
        }

        .tools-card-title {
            margin: 0;
            color: #212529;
            font-size: 1rem;
            font-weight: 700;
        }

        .tools-card-subtitle {
            margin: .2rem 0 0;
            color: #6c757d;
            font-size: .875rem;
        }

        .tools-card-body {
            padding: 1rem;
        }

        .tools-reference-frame {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 240px;
            height: min(50vh, 500px);
            background:
                linear-gradient(180deg, rgba(255, 250, 235, 0.92) 0%, rgba(243, 234, 208, 0.98) 100%);
            border: 1px solid rgba(120, 93, 42, 0.16);
            border-radius: 12px;
            overflow: hidden;
            padding: .75rem;
        }

        .tools-reference-frame img {
            display: block;
            max-width: 100%;
            max-height: calc(min(50vh, 500px) - 1.5rem);
            width: auto;
            height: auto;
            object-fit: contain;
        }

        .tools-input-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .9rem 1rem;
        }

        .tools-field {
            min-width: 0;
        }

        .tools-field-label {
            display: block;
            margin-bottom: .35rem;
            color: #212529;
            font-size: .9rem;
            font-weight: 600;
        }

        .tools-field-hint {
            margin-top: .35rem;
            color: #6c757d;
            font-size: .78rem;
            line-height: 1.35;
        }

        .tools-result-inline {
            display: flex;
            align-items: center;
            min-height: calc(2.25rem + 2px);
            padding: .375rem .75rem;
            border: 1px solid #c7d0d9;
            border-radius: .375rem;
            background: #f8f9fa;
            color: #0d6efd;
            font-size: 1rem;
            font-weight: 700;
        }

        .tools-result-error {
            margin-top: .35rem;
            color: #b42318;
            font-size: .8rem;
            font-weight: 600;
            line-height: 1.35;
            min-height: 1.1rem;
        }

        .tools-page .form-control {
            background: #e1e5e9 !important;
            color: #1f252b !important;
            border: 1px solid #aab3bc !important;
            box-shadow: none;
        }

        .tools-page .form-control::placeholder {
            color: #8a94a0 !important;
            opacity: 1;
        }

        .tools-page .form-control:focus {
            background: #eef1f4 !important;
            color: #1f252b !important;
            border-color: #0d6efd !important;
            box-shadow: 0 0 0 .2rem rgba(13, 110, 253, 0.12) !important;
        }

        .tools-print-sheet {
            background:
                linear-gradient(180deg, rgba(120, 93, 42, 0.06) 0%, rgba(255, 253, 244, 0.96) 12%, rgba(246, 238, 214, 0.98) 100%);
            border: 1px solid rgba(120, 93, 42, 0.22);
            border-radius: 14px;
            padding: 1.1rem;
            box-shadow:
                inset 0 0 0 1px rgba(255, 255, 255, 0.28),
                0 16px 34px rgba(77, 57, 23, 0.12);
        }

        .tools-print-header {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            flex-wrap: wrap;
            gap: 1rem;
            padding-bottom: .85rem;
            margin-bottom: 1rem;
            border-bottom: 1px solid rgba(52, 58, 64, 0.12);
        }

        .tools-print-title {
            margin: 0;
            color: #212529;
            font-size: 1.05rem;
            font-weight: 800;
        }

        .tools-print-subtitle,
        .tools-print-meta {
            margin: .2rem 0 0;
            color: #6c757d;
            font-size: .82rem;
        }

        .tools-print-wo-line {
            margin: 0;
            color: #39424c;
            font-size: 1rem;
            font-weight: 600;
            letter-spacing: .01em;
            white-space: nowrap;
        }

        .tools-print-wo-line strong {
            color: #18212a;
            font-size: 1.08rem;
            font-weight: 800;
        }

        .tools-print-meta-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 220px));
            gap: .85rem 1rem;
            margin-bottom: 1rem;
        }

        .tools-print-meta-field {
            min-width: 0;
        }

        .tools-print-meta-label {
            display: block;
            margin-bottom: .35rem;
            color: #6c757d;
            font-size: .78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .tools-print-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 1rem;
        }

        .tools-print-header-main {
            display: flex;
            align-items: baseline;
            flex-wrap: wrap;
            gap: .85rem 1.25rem;
            min-width: 0;
        }

        .tools-formula-sheet {
            display: grid;
            gap: .6rem;
            margin: .35rem 0 1rem;
            padding: .85rem 1rem;
            border: 1px solid rgba(120, 93, 42, 0.18);
            border-radius: 12px;
            background: rgba(255, 252, 241, 0.72);
        }

        .tools-formula-line {
            color: #2f3842;
            font-size: .98rem;
            font-weight: 700;
            letter-spacing: .01em;
        }

        .tools-empty-card {
            background: linear-gradient(180deg, #434b54 0%, #2f363d 100%);
            border-color: rgba(255, 255, 255, 0.12);
            box-shadow:
                inset 0 0 0 1px rgba(255, 255, 255, 0.04),
                0 10px 24px rgba(0, 0, 0, 0.20);
        }

        .tools-empty-pane {
            background: linear-gradient(180deg, #5a636d 0%, #434b53 100%);
        }

        .tools-empty-title {
            color: #fff;
            font-size: 1rem;
            font-weight: 800;
        }

        .tools-empty-text {
            color: rgba(255, 255, 255, 0.82);
            margin-top: .5rem;
        }

        .tools-preview-stage {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 150px;
            gap: 1rem;
            align-items: center;
            min-height: 420px;
        }

        .tools-preview-svg-wrap {
            min-width: 0;
            overflow: hidden;
        }

        .tools-preview-svg {
            display: block;
            width: 100%;
            max-width: 100%;
            height: auto;
        }

        .tools-preview-side {
            display: grid;
            gap: .75rem;
        }

        .tools-preview-chip {
            border: 1px solid rgba(52, 58, 64, 0.12);
            border-radius: 12px;
            padding: .7rem .8rem;
            background: #f8f9fa;
        }

        .tools-preview-chip-value {
            color: #212529;
            font-size: 1.05rem;
            font-weight: 800;
        }

        @media (max-width: 1199.98px) {
            .tools-input-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }

            .tools-preview-stage {
                grid-template-columns: 1fr;
                min-height: 0;
            }

            .tools-preview-side {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .tools-preview-svg {
                min-width: 0;
            }
        }

        @media (max-width: 991.98px) {
            .tools-workspace {
                grid-template-columns: 1fr;
            }

            .tools-input-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .tools-print-meta-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 767.98px) {
            .tools-page,
            .tools-head,
            .tools-pane,
            .tools-card-body {
                padding: .75rem;
            }

            .tools-tabs {
                flex-wrap: nowrap;
                overflow-x: auto;
                overflow-y: hidden;
            }

            .tools-reference-frame {
                min-height: 200px;
                height: min(34vh, 320px);
            }

            .tools-reference-frame img {
                max-height: calc(min(34vh, 320px) - 1.5rem);
            }

            .tools-input-grid {
                grid-template-columns: 1fr;
            }

            .tools-preview-side {
                grid-template-columns: 1fr;
            }

            .tools-print-header {
                flex-direction: column;
            }

            .tools-print-wo-line {
                white-space: normal;
            }
        }

        @media print {
            @page {
                size: portrait;
                margin: 10mm;
            }

            body * {
                visibility: hidden;
            }

            .tools-print-sheet,
            .tools-print-sheet * {
                visibility: visible;
            }

            .tools-print-sheet {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                border: 0;
                box-shadow: none;
                padding: 0;
            }

            .tools-print-actions {
                display: none !important;
            }

            .tools-preview-stage {
                grid-template-columns: 1fr !important;
                gap: 1rem;
            }

            .tools-preview-side {
                display: flex;
                justify-content: center;
            }

            .tools-preview-chip {
                min-width: 220px;
            }
        }
    </style>

    <div class="tools-page">
        <div class="tools-shell">
            <div class="tools-head">
                <div class="tools-head-row">
                    <div class="tools-head-left">
                        @if(!empty($workorderId))
                            <a href="{{ route('mains.show', $workorderId) }}" class="tools-back-btn" onclick="showLoadingSpinner()">
                                <i class="bi bi-arrow-left"></i>
                                <span>Back to WO</span>
                            </a>
                        @endif
                        <h1 class="tools-title">Tools</h1>
                    </div>
                </div>

                <ul class="nav nav-tabs tools-tabs" id="tools-tab" role="tablist">
                    @foreach($tools as $tool)
                        <li class="nav-item" role="presentation">
                            <button
                                class="nav-link {{ $activeTool === $tool['key'] ? 'active' : '' }}"
                                id="tab-{{ $tool['key'] }}"
                                data-bs-toggle="tab"
                                data-bs-target="#pane-{{ $tool['key'] }}"
                                type="button"
                                role="tab"
                                aria-controls="pane-{{ $tool['key'] }}"
                                aria-selected="{{ $activeTool === $tool['key'] ? 'true' : 'false' }}"
                            >
                                {{ $tool['label'] }}
                            </button>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="tab-content" id="tools-tab-content">
                @forelse($tools as $tool)
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

                    <div
                        class="tab-pane fade {{ $activeTool === $tool['key'] ? 'show active' : '' }}"
                        id="pane-{{ $tool['key'] }}"
                        role="tabpanel"
                        aria-labelledby="tab-{{ $tool['key'] }}"
                        tabindex="0"
                    >
                        <div class="tools-pane">
                            <div class="tools-workspace js-tool-workspace"
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
                                                    WO <strong>{{ $prefillWorkorder ?: '—' }}</strong>
                                                    &nbsp;&nbsp;&nbsp;
                                                    Technician: <strong>{{ $prefillUserName ?: '—' }}</strong>
                                                </p>
                                            </div>
                                            <div>
                                                <p class="tools-print-meta">{{ $tool['print_subtitle'] }}</p>
                                                <p class="tools-print-meta">Calculated L: <strong class="js-result-l-inline">{{ number_format($defaultL, 3) }}</strong></p>
                                            </div>
                                        </div>

                                        <div class="tools-formula-sheet">
                                            <div class="tools-formula-line">L = √(R out² - (D/2)²) - R in</div>
                                            <div class="tools-formula-line">M = √(R out² - (D/2)²) - √(R in² - (D/2)²)</div>
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

                                                    <text
                                                        x="370"
                                                        y="250"
                                                        text-anchor="middle"
                                                        font-size="24"
                                                        font-weight="700"
                                                        fill="#212529"
                                                    >
                                                        L = <tspan class="js-svg-l">{{ number_format($defaultL, 3) }}</tspan>
                                                    </text>

                                                    <text
                                                        x="600"
                                                        y="240"
                                                        text-anchor="middle"
                                                        font-size="20"
                                                        font-weight="700"
                                                        fill="#212529"
                                                    >
                                                        R in = <tspan class="js-svg-r-in">{{ number_format($rIn, 3) }}</tspan>
                                                    </text>

                                                    <text
                                                        x="370"
                                                        y="422"
                                                        text-anchor="middle"
                                                        font-size="24"
                                                        font-weight="700"
                                                        fill="#212529"
                                                    >
                                                        M = <tspan class="js-svg-m">
                                                            {{ number_format((($rOut * $rOut) >= (($d / 2) * ($d / 2)) && ($rIn * $rIn) >= (($d / 2) * ($d / 2))) ? max(sqrt(($rOut * $rOut) - (($d / 2) * ($d / 2))) - sqrt(($rIn * $rIn) - (($d / 2) * ($d / 2))), 0) : 0, 3) }}
                                                        </tspan>
                                                    </text>

                                                </svg>
                                            </div>

                                            <div class="tools-preview-side">
                                            </div>
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
                    </div>
                @empty
                    <div class="tools-pane tools-empty-pane">
                        <div class="tools-card tools-empty-card">
                            <div class="tools-card-body">
                                <div class="tools-empty-title">No tools configured</div>
                                <div class="tools-empty-text">
                                    @if($currentManualNumber)
                                        No tools are assigned to manual {{ $currentManualNumber }}.
                                    @else
                                        No tools are available for this context yet.
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.js-tool-workspace').forEach(function (workspace) {
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
                        errorMessage = 'D must be less than or equal to 2 × R out.';
                    } else if (!isInnerChordValid) {
                        errorMessage = 'D must be less than or equal to 2 × R in.';
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

                    lTargets.forEach(function (target) {
                        target.textContent = formatted;
                    });

                    inlineTargets.forEach(function (target) {
                        target.textContent = formatted;
                    });

                    svgTargets.forEach(function (target) {
                        target.textContent = formatted;
                    });

                    svgMTargets.forEach(function (target) {
                        target.textContent = formattedM;
                    });

                    svgRInTargets.forEach(function (target) {
                        target.textContent = formattedRIn;
                    });

                    errorTargets.forEach(function (target) {
                        target.textContent = errorMessage;
                    });
                };

                const saveInputs = function () {
                    if (!workorderId || !toolKey || !saveUrl) {
                        return;
                    }

                    const payload = {
                        workorder_id: workorderId,
                        tool_key: toolKey,
                        input_values: getValues(),
                    };

                    fetch(saveUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify(payload),
                    }).catch(function () {
                        // Silent fail for now; the UI should remain usable even if save fails.
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
@endsection
