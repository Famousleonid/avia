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
                    <div
                        class="tab-pane fade {{ $activeTool === $tool['key'] ? 'show active' : '' }}"
                        id="pane-{{ $tool['key'] }}"
                        role="tabpanel"
                        aria-labelledby="tab-{{ $tool['key'] }}"
                        tabindex="0"
                    >
                        @include($tool['view'], [
                            'tool' => $tool,
                            'workorderId' => $workorderId,
                            'prefillWorkorder' => $prefillWorkorder,
                            'prefillUserName' => $prefillUserName,
                        ])
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
@endsection
