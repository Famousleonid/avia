{{--component.blade --}}

@extends('mobile.master')

@section('style')
    <style>
        .gradient-pane {
            background: #343A40;
            color: #f8f9fa;
        }

        .text-format {
            font-size: .75rem;
            line-height: 1;
        }

        .component-list-wrapper {
            max-height: calc(100vh - 210px);
            overflow: auto;
        }

        .picker-list {
            max-height: calc(100vh - 130px);
            overflow: auto;
        }

        .components-list-container {
            overflow-y: auto;
            overflow-x: hidden;
            -webkit-overflow-scrolling: touch;
            flex: 1 1 0;
            min-height: 0;
        }

        /* Smooth scrollbar for components list */
        .components-list-container::-webkit-scrollbar { width: 6px; }
        .components-list-container::-webkit-scrollbar-track { background: rgba(255, 255, 255, 0.1); }
        .components-list-container::-webkit-scrollbar-thumb { background: rgba(13, 202, 240, 0.5); border-radius: 3px; }
        .components-list-container::-webkit-scrollbar-thumb:hover { background: rgba(13, 202, 240, 0.7); }

        .picker-item { border-bottom: 1px solid rgba(255, 255, 255, .15); }
        .picker-item:hover { background: rgba(13, 202, 240, .08); }

        .mini-muted { font-size: .8rem; color: rgba(255, 255, 255, .6); }

        /* Offcanvas above modal */
        .offcanvas { z-index: 2000 !important; }
        .offcanvas-backdrop { z-index: 1990 !important; }

        .component-row {
            display: grid;
            grid-template-columns: 44px 1fr 46px;
            gap: 10px;
            align-items: center;
            width: 100%;
        }

        .component-avatar {
            width: 40px;
            height: 40px;
            border-radius: 999px;
            object-fit: cover;
            display: block;
            border: 2px solid rgba(13, 202, 240, 0.3);
            cursor: pointer;
            transition: all 0.2s;
        }

        .component-avatar:hover {
            border-color: rgba(13, 202, 240, 0.8);
            transform: scale(1.05);
        }

        .component-title { line-height: 1.1; margin-bottom: 2px; }
        .component-meta { line-height: 1.1; }

        /* Чтобы длинные штуки не ломали сетку */
        .break-anywhere { overflow-wrap: anywhere; word-break: break-word; }

        /* Кнопка камеры одинаковая высота/ширина */
        .btn-camera {
            width: 40px;
            height: 40px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-width: 1px;
        }

        /* ============================================================
           CAMERA OVERLAY (100% camera-only)
        ============================================================ */
        #cameraOverlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.92);
            z-index: 9999;
            display: none;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 14px;
        }

        #cameraOverlay.is-open { display: flex; }

        #cameraVideo {
            width: 100%;
            max-width: 520px;
            border-radius: 18px;
            border: 1px solid rgba(13,202,240,.35);
            background: #000;
        }

        #cameraCanvas { display:none; }

        #cameraTopBar, #cameraBottomBar {
            width: 100%;
            max-width: 520px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        #cameraTopBar { margin-bottom: 10px; }
        #cameraBottomBar { margin-top: 12px; }

        #cameraHint {
            color: rgba(255,255,255,.65);
            font-size: .85rem;
            line-height: 1.2;
        }

        .camera-round {
            width: 62px;
            height: 62px;
            border-radius: 999px;
            border: 2px solid rgba(255,255,255,.85);
            background: rgba(255,255,255,.12);
            display:flex;
            align-items:center;
            justify-content:center;
        }
        .camera-round:active { transform: scale(.98); }

        .camera-dot {
            width: 44px;
            height: 44px;
            border-radius: 999px;
            background: rgba(13,202,240,.9);
        }

        .js-component-edit-link:hover { text-decoration: underline !important; }
        .js-component-edit-link:active { opacity: .8; }

    </style>
@endsection

@section('content')

    <div class="container-fluid d-flex flex-column bg-dark p-0" style="height: calc(100vh - 80px); max-height: calc(100vh - 80px); padding-top: 60px; overflow: hidden;">

        <div id="block-info" class="rounded-3 border border-info gradient-pane shadow-sm flex-shrink-0" style="margin: 5px; padding: 3px;">
            <div class="d-flex  align-items-center w-100 fw-bold fs-2 ms-3">
                <div class="d-flex align-items-center">
                    @if(!$workorder->isDone())
                        <span class="text-info">W {{ $workorder->number }}</span>
                    @else
                        <span class="text-secondary">{{ $workorder->number }}</span>
                    @endif
                </div>
                <div class="d-flex align-items-center ms-3">
                    @if($workorder->approve_at)
                        <img src="{{ asset('img/ok.png') }}" width="20"
                             title="{{ $workorder->approve_at->format('d.m.Y') }} {{ $workorder->approve_name }}">
                    @else
                        <img src="{{ asset('img/icon_no.png') }}" width="12">
                    @endif
                </div>
                <div class="d-flex align-items-center ms-auto ">
                    @if($workorder->open_at)
                        <span class="text-secondary fw-normal fs-6 me-4">Open at: {{ $workorder->open_at->format('d-M-Y') }}</span>
                    @else
                        <span class="text-secondary fw-normal fs-6 me-4">Open at: - null - </span>
                    @endif
                </div>
            </div>
        </div>

        <hr class="border-secondary opacity-50 my-1 flex-shrink-0">

        <div class="row g-0 flex-grow-1 d-flex flex-column" style="background-color:#343A40; min-height: 0;">
            <div class="col-12 p-0 d-flex flex-column" style="min-height: 0; flex: 1 1 0;">

                <div class="bg-dark py-2 px-3 d-flex justify-content-between align-items-center border-bottom mt-1 flex-shrink-0">
                    <div class="d-flex align-items-center gap-2">
                        <h6 class="mb-0 text-primary">{{ __('Components') }}</h6>
                        <span class="text-info">({{ $components->count() }})</span>
                    </div>

                    <button class="btn btn-success btn-sm text-format" id="openAddComponentBtn">
                        {{ __('Add Component') }}
                    </button>
                </div>

                @if($components->isEmpty())
                    <div class="text-center text-muted small py-3 flex-shrink-0">
                        {{ __('COMPONENTS NOT CREATED') }}
                    </div>
                @else

                    <div class="list-group components-list-container d-flex flex-column">
                        @foreach($components as $component)

                            @php
                                $tdrsDetails = $tdrsDetailsByComponent[$component->id] ?? collect();
                            @endphp

                            @if(!$component) @continue @endif

                            <div class="list-group-item bg-transparent text-light border-secondary">
                                <div class="component-row">

                                    {{-- LEFT --}}
                                    <div class="">
                                        @if($component->getFirstMediaBigUrl('components'))
                                            <a href="{{ $component->getFirstMediaBigUrl('components') }}"
                                               data-fancybox="component-{{ $component->id }}"
                                               class="d-inline-block">
                                                <img class="component-avatar"
                                                     src="{{ $component->getFirstMediaThumbnailUrl('components') ?: $component->getFirstMediaBigUrl('components') }}"
                                                     alt="{{ $component->name ?? 'Component' }}"
                                                     width="40"
                                                     height="40">
                                            </a>
                                        @else
                                            <img class="component-avatar opacity-50"
                                                 src="{{ asset('img/noimage.png') }}"
                                                 alt="No image"
                                                 width="40"
                                                 height="40">
                                        @endif
                                    </div>

                                    {{-- CENTER: info --}}
                                    <div class="break-anywhere">

                                        <a href="#"
                                           class="fw-semibold text-info text-decoration-none js-component-edit-link break-anywhere"
                                           data-component-id="{{ $component->id }}"
                                           data-name="{{ e($component->name) }}"
                                           data-ipl="{{ e($component->ipl_num) }}"
                                           data-part="{{ e($component->part_number) }}"
                                           data-eff="{{ e($component->eff_code) }}"
                                           data-is-bush="{{ $component->is_bush ? 1 : 0 }}"
                                           data-bush-ipl="{{ e($component->bush_ipl_num) }}"
                                           title="Edit component">
                                            {{ $component->name ?? ('#'.$component->id) }}
                                        </a>

                                        <div class="small text-secondary component-meta">
                                            <span class="me-2"><span class="text-muted">IPL:</span> {{ $component->ipl_num ?? '—' }}</span>
                                            <span class="me-2"><span class="text-muted">P/N:</span> {{ $component->part_number ?? '—' }}</span>

                                            @if(!empty($component->is_bush))
                                                <span class="badge bg-info text-dark ms-1">BUSH</span>
                                            @endif
                                        </div>

                                        {{-- Codes with details --}}
                                        <div class="small mt-1">
                                            @if($tdrsDetails->isNotEmpty())
                                                @foreach($tdrsDetails as $tdrDetail)
                                                    <div class="d-flex align-items-center gap-1 mb-1">
                                                        <span class="text-white fw-bold">{{ $tdrDetail['code_name'] ?? '—' }}</span>
                                                        @if($tdrDetail['qty'] && $tdrDetail['qty'] > 1)
                                                            <span class="text-muted">Qty: {{ $tdrDetail['qty'] }}</span>
                                                        @endif
                                                        @if($tdrDetail['necessaries_name'])
                                                            <span class="text-muted">→ {{ $tdrDetail['necessaries_name'] }}</span>
                                                            @if($tdrDetail['serial_number'])
                                                                <span class="text-muted">(SN: {{ $tdrDetail['serial_number'] }})</span>
                                                            @endif
                                                        @endif
                                                    </div>
                                                @endforeach
                                            @else
                                                <span class="text-muted">Code: —</span>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- RIGHT: camera only --}}
                                    <div class="text-end">
                                        <button type="button"
                                                class="btn btn-outline-info btn-sm btn-camera js-component-camera"
                                                data-component-id="{{ $component->id }}"
                                                data-component-name="{{ $component->name ?? ('#'.$component->id) }}"
                                                title="Update photo">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                                                <path d="M10.5 8.5a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0z"/>
                                                <path d="M2 4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-1.172a2 2 0 0 1-1.414-.586l-.828-.828A2 2 0 0 0 9.172 2H6.828a2 2 0 0 0-1.414.586l-.828.828A2 2 0 0 1 3.172 4H2zm12 1a1 1 0 0 1 1 1v6a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1h1.172a3 3 0 0 0 2.121-.879l.828-.828A1 1 0 0 1 6.828 3h2.344a1 1 0 0 1 .707.293l.828.828A3 3 0 0 0 12.828 5H14z"/>
                                            </svg>
                                        </button>
                                    </div>

                                </div>
                            </div>

                        @endforeach
                    </div>
                @endif

            </div>
        </div>
    </div>
    </div>

    {{-- MODAL add component --}}
    <div class="modal fade" id="addComponentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen-sm-down">
            <div class="modal-content bg-dark text-light">

                <form id="componentAttachForm"
                      method="POST"
                      action="{{ route('mobile.workorders.components.attach') }}">
                    @csrf

                    <div class="modal-header">
                        <h5 class="modal-title">Add Component</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <input type="hidden" name="workorder_id" value="{{ $workorder->id }}">
                    <input type="hidden" name="component_id" id="component_id" value="">

                    <div class="modal-body">

                        {{-- picker button --}}
                        <div class="mb-2">
                            <label class="form-label mb-2">Choose component</label>

                            <button type="button"
                                    class="btn btn-outline-light w-100 text-start"
                                    data-open-picker>
                                <span id="pickedComponentText" class="text-muted">Tap to choose…</span>
                            </button>
                        </div>

                        <hr class="border-secondary opacity-50">

                        {{-- Code Inspection --}}
                        <div class="mb-3">
                            <label class="form-label mb-2">Code Inspection <span class="text-danger">*</span></label>
                            <select class="form-select" id="code_id" name="code_id" required>
                                <option value="">Select code...</option>
                                @foreach($codes as $code)
                                    <option value="{{ $code->id }}" data-code-name="{{ strtolower($code->name) }}">
                                        {{ $code->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Qty field --}}
                        <div class="mb-3 d-none" id="qty_container">
                            <label class="form-label mb-2">Quantity <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="qty" name="qty" min="1" value="1">
                        </div>

                        {{-- Necessaries --}}
                        <div class="mb-3 d-none" id="necessaries_container">
                            <label class="form-label mb-2">Necessary Action</label>
                            <select class="form-select" id="necessaries_id" name="necessaries_id">
                                <option value="">Select necessary...</option>
                                @foreach($necessaries as $necessary)
                                    <option value="{{ $necessary->id }}" data-necessary-name="{{ strtolower($necessary->name) }}">
                                        {{ $necessary->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Serial Number --}}
                        <div class="mb-3 d-none" id="serial_container">
                            <label class="form-label mb-2">Serial Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="serial_number" name="serial_number" placeholder="Enter serial number">
                        </div>

                        <hr class="border-secondary opacity-50">

                        {{-- TDR flags --}}
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="form-check mt-1 ms-2">
                                <input class="form-check-input" type="checkbox" id="use_tdr" name="use_tdr">
                                <label class="form-check-label" for="use_tdr">Use TDR</label>
                            </div>
                            <div class="form-check me-2">
                                <input class="form-check-input" type="checkbox" id="use_log_card" name="use_log_card">
                                <label class="form-check-label" for="use_log_card">Use Log Card</label>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Save</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>

                </form>
            </div>
        </div>
    </div>

    {{-- OFFCANVAS PICKER --}}
    <div class="offcanvas offcanvas-bottom bg-dark text-light"
         tabindex="-1"
         id="componentsPicker"
         style="height: 100vh;">
        <div class="offcanvas-header border-bottom border-secondary">
            <h5 class="offcanvas-title">Select component</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>

        <div class="offcanvas-body p-0 d-flex flex-column" style="height: calc(100vh - 57px);">
            <div class="p-3 border-bottom border-secondary">
                <div class="d-flex gap-2 mb-2">
                    <input type="text" class="form-control" id="componentsFilter"
                           placeholder="Search IPL / P/N / Name…">
                    <button type="button"
                            class="btn btn-outline-info btn-sm"
                            id="btnToggleCreateInPicker"
                            title="Create new component">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                        </svg>
                    </button>
                </div>

                {{-- Create form in picker --}}
                <div id="createComponentInPicker" class="border border-info rounded p-3 d-none" style="background: rgba(13, 202, 240, 0.05);">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="fw-semibold text-info small">Create New Component</div>
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-sm" id="btnHideCreateInPicker">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8 2.146 2.854Z"/>
                            </svg>
                        </button>
                    </div>

                    <div class="row g-2">
                        <div class="col-12 col-md-6">
                            <label class="form-label small mb-1">IPL Number <span class="text-danger">*</span></label>
                            <input type="text" id="picker_ipl_num" class="form-control form-control-sm" placeholder="Enter IPL" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small mb-1">Part Number <span class="text-danger">*</span></label>
                            <input type="text" id="picker_part_number" class="form-control form-control-sm" placeholder="Enter P/N" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small mb-1">Component Name <span class="text-danger">*</span></label>
                            <input type="text" id="picker_name" class="form-control form-control-sm" placeholder="Enter name" required>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="picker_is_bush">
                                <label class="form-check-label small" for="picker_is_bush">Is Bush Component</label>
                            </div>
                        </div>
                        <div class="col-12 d-none" id="picker_bush_container">
                            <label class="form-label small mb-1">Bush IPL Number</label>
                            <input type="text" id="picker_bush_ipl_num" class="form-control form-control-sm" placeholder="Enter bush IPL">
                        </div>
                        <div class="col-12">
                            <label class="form-label small mb-1">Photo <span class="text-muted">(optional)</span></label>
                            <input type="file" id="picker_photo" accept="image/*" class="form-control form-control-sm">
                        </div>
                        <div class="col-12">
                            <button type="button" class="btn btn-info btn-sm w-100" id="btnCreateInPicker">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" class="me-1">
                                    <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                                </svg>
                                Create & Select
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="picker-list flex-grow-1" id="componentsList" style="overflow-y: auto;">
                @foreach($manualComponents as $c)
                    @php
                        $text = trim(($c->ipl_num ?? '—').' | '.($c->part_number ?? '—').' | '.($c->name ?? ('#'.$c->id)));
                    @endphp
                    <button type="button"
                            class="w-100 text-start px-3 py-1 bg-dark text-light border-0 picker-item component-pick"
                            data-id="{{ $c->id }}"
                            data-text="{{ $text }}">
                        <div class="fw-semibold text-info">
                            {{ $c->name ?? ('#'.$c->id) }}
                            @if($c->is_bush)
                                <span class="badge bg-info text-dark ms-2">BUSH</span>
                            @endif
                        </div>
                        <div class="small text-secondary">
                            <span class="me-2"><span class="text-muted">IPL:</span> {{ $c->ipl_num ?? '—' }}</span>
                            <span class="me-2"><span class="text-muted">P/N:</span> {{ $c->part_number ?? '—' }}</span>
                            @if($c->eff_code)
                                <span class="me-2"><span class="text-muted">EFF:</span> {{ $c->eff_code }}</span>
                            @endif
                            @if($c->is_bush && $c->bush_ipl_num)
                                <span class="me-2"><span class="text-muted">Bush IPL:</span> {{ $c->bush_ipl_num }}</span>
                            @endif
                        </div>
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    {{-- hidden upload form (kept) --}}
    <form id="component-photo-upload-form"
          data-url-template="{{ route('mobile.components.updatePhoto', ['component' => ':id']) }}"
          method="POST"
          enctype="multipart/form-data"
          style="display:none;"></form>

    {{-- CAMERA OVERLAY (camera-only, no gallery) --}}
    <div id="cameraOverlay" aria-hidden="true">
        <div id="cameraTopBar">
            <div id="cameraHint">Camera only • back camera</div>
            <button type="button" class="btn btn-outline-light btn-sm" id="cameraCloseBtn">Close</button>
        </div>

        <video id="cameraVideo" autoplay muted playsinline></video>
        <canvas id="cameraCanvas"></canvas>

        <div id="cameraBottomBar">
            <div class="mini-muted" id="cameraMeta">—</div>

            <button type="button" class="camera-round" id="cameraShutterBtn" title="Take photo">
                <div class="camera-dot"></div>
            </button>

            <div class="mini-muted" style="text-align:right;" id="cameraStatus"></div>
        </div>
    </div>


    {{-- Modal EDIT --}}
    <div class="modal fade" id="componentEditModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen-sm-down">
            <div class="modal-content bg-dark text-light border border-info">
                <div class="modal-header">
                    <h5 class="modal-title" id="componentEditModalTitle">Edit component</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <form id="componentEditForm" method="POST" action="#">
                    @csrf
                    <input type="hidden" name="_method" value="PATCH">
                    <input type="hidden" id="edit_component_id" value="">

                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label small mb-1">Name</label>
                            <input type="text" class="form-control form-control-sm" name="name" id="edit_name">
                        </div>

                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small mb-1">IPL</label>
                                <input type="text" class="form-control form-control-sm" name="ipl_num" id="edit_ipl">
                            </div>
                            <div class="col-6">
                                <label class="form-label small mb-1">P/N</label>
                                <input type="text" class="form-control form-control-sm" name="part_number" id="edit_part">
                            </div>
                        </div>

                        <div class="mt-2">
                            <label class="form-label small mb-1">EFF</label>
                            <input type="text" class="form-control form-control-sm" name="eff_code" id="edit_eff">
                        </div>

                        <hr class="border-secondary opacity-50 my-2">

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_is_bush" name="is_bush" value="1">
                            <label class="form-check-label small" for="edit_is_bush">Is Bush Component</label>
                        </div>

                        <div class="mt-2" id="edit_bush_wrap" style="display:none;">
                            <label class="form-label small mb-1">Bush IPL</label>
                            <input type="text" class="form-control form-control-sm" name="bush_ipl_num" id="edit_bush_ipl">
                        </div>

                        <div class="small text-danger mt-2 d-none" id="edit_error_box"></div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-info btn-sm w-100" id="btnComponentEditSave">Save</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm w-100" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>

            </div>
        </div>
    </div>


@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {

            // ================= Fancybox =================
            Fancybox.bind('[data-fancybox^="component-"]', {
                Toolbar: ["zoom", "fullscreen", "close"],
                dragToClose: true,
                placeFocusBack: false,
                trapFocus: false,
            });

            // ================= Add Component Modal =================
            const addComponentModal = document.getElementById('addComponentModal');
            document.getElementById('openAddComponentBtn')?.addEventListener('click', () => {
                if (!addComponentModal) return;
                bootstrap.Modal.getOrCreateInstance(addComponentModal).show();
            });

            // Code selection logic
            const codeSelect = document.getElementById('code_id');
            const qtyContainer = document.getElementById('qty_container');
            const necessariesContainer = document.getElementById('necessaries_container');
            const serialContainer = document.getElementById('serial_container');
            const qtyInput = document.getElementById('qty');
            const necessariesSelect = document.getElementById('necessaries_id');
            const serialInput = document.getElementById('serial_number');

            addComponentModal?.addEventListener('hidden.bs.modal', () => {
                const form = document.getElementById('componentAttachForm');
                if (!form) return;

                form.reset();
                qtyContainer?.classList.add('d-none');
                necessariesContainer?.classList.add('d-none');
                serialContainer?.classList.add('d-none');
                if (qtyInput) qtyInput.required = false;
                if (serialInput) serialInput.required = false;
                if (necessariesSelect) necessariesSelect.required = false;

                document.getElementById('component_id').value = '';
                const picked = document.getElementById('pickedComponentText');
                if (picked) {
                    picked.textContent = 'Tap to choose…';
                    picked.classList.add('text-muted');
                }
            });

            function handleCodeChange() {
                const selectedOption = codeSelect?.options[codeSelect.selectedIndex];
                if (!selectedOption || !selectedOption.value) {
                    qtyContainer?.classList.add('d-none');
                    necessariesContainer?.classList.add('d-none');
                    serialContainer?.classList.add('d-none');
                    if (qtyInput) qtyInput.required = false;
                    if (serialInput) serialInput.required = false;
                    if (necessariesSelect) necessariesSelect.required = false;
                    return;
                }

                const codeName = selectedOption.dataset.codeName?.toLowerCase() || '';

                qtyContainer?.classList.add('d-none');
                necessariesContainer?.classList.add('d-none');
                serialContainer?.classList.add('d-none');

                if (qtyInput) qtyInput.required = false;
                if (serialInput) serialInput.required = false;
                if (necessariesSelect) {
                    necessariesSelect.required = false;
                    necessariesSelect.value = '';
                }
                if (serialInput) serialInput.value = '';

                if (codeName.includes('missing')) {
                    qtyContainer?.classList.remove('d-none');
                    if (qtyInput) qtyInput.required = true;
                } else {
                    necessariesContainer?.classList.remove('d-none');
                    if (necessariesSelect) necessariesSelect.required = true;
                }
            }
            codeSelect?.addEventListener('change', handleCodeChange);

            necessariesSelect?.addEventListener('change', () => {
                const selectedOption = necessariesSelect?.options[necessariesSelect.selectedIndex];
                if (!selectedOption || !selectedOption.value) {
                    serialContainer?.classList.add('d-none');
                    qtyContainer?.classList.add('d-none');
                    if (serialInput) serialInput.required = false;
                    if (qtyInput) qtyInput.required = false;
                    return;
                }

                const necessaryName = selectedOption.dataset.necessaryName?.toLowerCase() || '';

                serialContainer?.classList.add('d-none');
                qtyContainer?.classList.add('d-none');
                if (serialInput) { serialInput.required = false; serialInput.value = ''; }
                if (qtyInput) qtyInput.required = false;

                if (necessaryName.includes('order') && necessaryName.includes('new')) {
                    qtyContainer?.classList.remove('d-none');
                    if (qtyInput) qtyInput.required = true;
                } else if (necessaryName.includes('repair')) {
                    serialContainer?.classList.remove('d-none');
                    if (serialInput) serialInput.required = true;
                }
            });

            // pick component from list
            const hiddenId = document.getElementById('component_id');
            const pickedText = document.getElementById('pickedComponentText');
            document.addEventListener('click', (e) => {
                const btn = e.target.closest('.component-pick');
                if (!btn) return;

                if (hiddenId) hiddenId.value = btn.dataset.id || '';
                if (pickedText) {
                    pickedText.classList.remove('text-muted');
                    pickedText.textContent = btn.dataset.text || '';
                }

                const offEl = document.getElementById('componentsPicker');
                if (offEl) bootstrap.Offcanvas.getOrCreateInstance(offEl).hide();
            });

            // filter
            const filter = document.getElementById('componentsFilter');
            filter?.addEventListener('input', () => {
                const q = filter.value.trim().toLowerCase();
                document.querySelectorAll('#componentsList .component-pick').forEach((item) => {
                    const text = (item.dataset.text || '').toLowerCase();
                    item.style.display = text.includes(q) ? '' : 'none';
                });
            });

            // Toggle create form in picker
            const createInPicker = document.getElementById('createComponentInPicker');
            const btnToggleCreateInPicker = document.getElementById('btnToggleCreateInPicker');
            const btnHideCreateInPicker = document.getElementById('btnHideCreateInPicker');

            btnToggleCreateInPicker?.addEventListener('click', () => {
                createInPicker?.classList.toggle('d-none');
                if (!createInPicker?.classList.contains('d-none')) {
                    createInPicker.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            });

            btnHideCreateInPicker?.addEventListener('click', () => {
                createInPicker?.classList.add('d-none');
                document.getElementById('picker_ipl_num').value = '';
                document.getElementById('picker_part_number').value = '';
                document.getElementById('picker_name').value = '';
                document.getElementById('picker_is_bush').checked = false;
                document.getElementById('picker_bush_ipl_num').value = '';
                document.getElementById('picker_photo').value = '';
                document.getElementById('picker_bush_container').classList.add('d-none');
            });

            // Toggle bush field in picker
            const pickerIsBush = document.getElementById('picker_is_bush');
            const pickerBushContainer = document.getElementById('picker_bush_container');
            const pickerBushInput = document.getElementById('picker_bush_ipl_num');

            pickerIsBush?.addEventListener('change', () => {
                if (pickerIsBush.checked) {
                    pickerBushContainer?.classList.remove('d-none');
                    pickerBushInput.required = true;
                } else {
                    pickerBushContainer?.classList.add('d-none');
                    pickerBushInput.required = false;
                    pickerBushInput.value = '';
                }
            });

            function escapeHtml(str) {
                return String(str ?? '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');
            }

            // Create component in picker
            document.getElementById('btnCreateInPicker')?.addEventListener('click', async () => {
                const iplNum = document.getElementById('picker_ipl_num')?.value?.trim();
                const partNumber = document.getElementById('picker_part_number')?.value?.trim();
                const name = document.getElementById('picker_name')?.value?.trim();
                const isBush = document.getElementById('picker_is_bush')?.checked;
                const bushIpl = isBush ? (document.getElementById('picker_bush_ipl_num')?.value?.trim() || '') : '';
                const photo = document.getElementById('picker_photo')?.files?.[0];

                if (!iplNum || !partNumber || !name) {
                    if (typeof showErrorMessage === 'function') showErrorMessage('Please fill in all required fields (IPL Number, Part Number, Component Name)');
                    else alert('Please fill in all required fields');
                    return;
                }

                if (isBush && !bushIpl) {
                    if (typeof showErrorMessage === 'function') showErrorMessage('Please enter Bush IPL Number');
                    else alert('Please enter Bush IPL Number');
                    return;
                }

                const url = "{{ route('mobile.components.quickStore') }}";
                const fd = new FormData();
                fd.append('workorder_id', "{{ $workorder->id }}");
                fd.append('ipl_num', iplNum);
                fd.append('part_number', partNumber);
                fd.append('name', name);
                fd.append('is_bush', isBush ? '1' : '0');
                if (isBush) fd.append('bush_ipl_num', bushIpl);
                if (photo) fd.append('photo', photo);

                try {
                    if (typeof showLoadingSpinner === 'function') showLoadingSpinner();

                    const res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                        body: fd,
                    });

                    const json = await res.json();
                    if (!res.ok || !json?.ok) {
                        if (typeof showErrorMessage === 'function') showErrorMessage(json?.message || 'Create failed');
                        else alert(json?.message || 'Create failed');
                        return;
                    }

                    if (hiddenId) hiddenId.value = json.item.id;
                    if (pickedText) {
                        pickedText.classList.remove('text-muted');
                        pickedText.textContent = json.item.text;
                    }

                    const list = document.getElementById('componentsList');
                    if (list) {
                        const b = document.createElement('button');
                        b.type = 'button';
                        b.className = 'w-100 text-start px-3 py-1 bg-dark text-light border-0 picker-item component-pick';
                        b.dataset.id = json.item.id;
                        b.dataset.text = json.item.text;

                        const bushBadge = json.item.is_bush ? '<span class="badge bg-info text-dark ms-2">BUSH</span>' : '';
                        const bushLine = (json.item.is_bush && json.item.bush_ipl_num)
                            ? `<span class="me-2"><span class="text-muted">Bush IPL:</span> ${escapeHtml(json.item.bush_ipl_num)}</span>`
                            : '';

                        b.innerHTML = `
                    <div class="fw-semibold text-info">${escapeHtml(json.item.name)} ${bushBadge}</div>
                    <div class="small text-secondary">
                        <span class="me-2"><span class="text-muted">IPL:</span> ${escapeHtml(json.item.ipl_num || '—')}</span>
                        <span class="me-2"><span class="text-muted">P/N:</span> ${escapeHtml(json.item.part_number || '—')}</span>
                        ${bushLine}
                    </div>
                `;
                        list.prepend(b);
                    }

                    createInPicker?.classList.add('d-none');
                    document.getElementById('picker_ipl_num').value = '';
                    document.getElementById('picker_part_number').value = '';
                    document.getElementById('picker_name').value = '';
                    document.getElementById('picker_is_bush').checked = false;
                    document.getElementById('picker_bush_ipl_num').value = '';
                    document.getElementById('picker_photo').value = '';
                    pickerBushContainer?.classList.add('d-none');

                    const offEl = document.getElementById('componentsPicker');
                    if (offEl) bootstrap.Offcanvas.getOrCreateInstance(offEl).hide();

                    if (typeof showSuccessMessage === 'function') showSuccessMessage('Component created and selected successfully');

                } catch (e) {
                    console.error(e);
                    if (typeof showErrorMessage === 'function') showErrorMessage('Create failed');
                    else alert('Create failed');
                } finally {
                    if (typeof hideLoadingSpinner === 'function') hideLoadingSpinner();
                }
            });

            // open picker
            document.addEventListener('click', (e) => {
                const btn = e.target.closest('[data-open-picker]');
                if (!btn) return;

                const el = document.getElementById('componentsPicker');
                if (!el) return;
                bootstrap.Offcanvas.getOrCreateInstance(el).show();
            });

            // Reset create form when picker closes
            const componentsPicker = document.getElementById('componentsPicker');
            componentsPicker?.addEventListener('hidden.bs.offcanvas', () => {
                if (!createInPicker) return;
                createInPicker.classList.add('d-none');

                document.getElementById('picker_ipl_num').value = '';
                document.getElementById('picker_part_number').value = '';
                document.getElementById('picker_name').value = '';
                const pickerIsBush = document.getElementById('picker_is_bush');
                if (pickerIsBush) pickerIsBush.checked = false;
                document.getElementById('picker_bush_ipl_num').value = '';
                document.getElementById('picker_photo').value = '';
                const pickerBushContainer = document.getElementById('picker_bush_container');
                if (pickerBushContainer) pickerBushContainer.classList.add('d-none');
            });

            // ============================================================
            // CAMERA ONLY (NO GALLERY) + NO DELAY + HAPTIC
            // ============================================================
            const overlay = document.getElementById('cameraOverlay');
            const video = document.getElementById('cameraVideo');
            const canvas = document.getElementById('cameraCanvas');
            const btnClose = document.getElementById('cameraCloseBtn');
            const btnShutter = document.getElementById('cameraShutterBtn');
            const metaEl = document.getElementById('cameraMeta');
            const statusEl = document.getElementById('cameraStatus');

            const uploadForm = document.getElementById('component-photo-upload-form');

            let camStream = null;
            let activeComponentId = null;
            let activeComponentName = null;
            let activeBtn = null;
            let isBusy = false;

            function haptic(type = 'light') {
                // Android/Chrome: работает. iOS Safari: чаще всего нет — будет no-op.
                if (!navigator.vibrate) return;
                const map = { light: 10, medium: 20, heavy: 30 };
                navigator.vibrate(map[type] || 10);
            }

            function setOverlay(open) {
                if (!overlay) return;
                overlay.classList.toggle('is-open', !!open);
                overlay.setAttribute('aria-hidden', open ? 'false' : 'true');
                document.body.style.overflow = open ? 'hidden' : '';
            }

            async function startCamera() {
                if (camStream) stopCamera();

                if (!navigator.mediaDevices?.getUserMedia) {
                    statusEl.textContent = 'Camera API not supported';
                    return;
                }

                statusEl.textContent = 'Opening camera…';
                metaEl.textContent = activeComponentName ? `Component: ${activeComponentName}` : '—';

                try {
                    // 100% camera-only: только getUserMedia, без input type=file
                    camStream = await navigator.mediaDevices.getUserMedia({
                        video: { facingMode: { ideal: 'environment' } },
                        audio: false
                    });

                    video.srcObject = camStream;

                    // без setTimeout: ждём готовности метаданных
                    await new Promise((resolve) => {
                        const onReady = () => {
                            video.removeEventListener('loadedmetadata', onReady);
                            resolve();
                        };
                        video.addEventListener('loadedmetadata', onReady, { once: true });
                    });

                    await video.play();
                    statusEl.textContent = 'Ready';

                } catch (e) {
                    console.error(e);
                    statusEl.textContent = 'Camera blocked / no permission';
                    stopCamera();
                }
            }

            function stopCamera() {
                try { video.pause(); } catch (e) {}
                if (video) video.srcObject = null;
                if (camStream) {
                    camStream.getTracks()?.forEach(t => t.stop());
                    camStream = null;
                }
                statusEl.textContent = '';
            }

            async function captureBlob() {
                if (!video?.videoWidth || !video?.videoHeight) throw new Error('Camera not ready');

                const w = video.videoWidth;
                const h = video.videoHeight;

                canvas.width = w;
                canvas.height = h;

                const ctx = canvas.getContext('2d');
                ctx.drawImage(video, 0, 0, w, h);

                const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.92));
                if (!blob) throw new Error('Capture failed');
                return blob;
            }

            async function uploadCaptured(blob) {
                if (!uploadForm) throw new Error('Upload form not found');
                const template = uploadForm.dataset.urlTemplate;
                if (!template) throw new Error('Upload URL template missing');

                const url = template.replace(':id', activeComponentId);

                const fd = new FormData();
                fd.append('photo', blob, `component_${activeComponentId}_${Date.now()}.jpg`);
                fd.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');

                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: fd
                });

                const json = await res.json().catch(() => ({}));
                if (!res.ok || !json?.ok) throw new Error(json?.message || 'Upload failed');
                return json;
            }

            function updateAvatar(componentId, componentName, data) {
                if (!activeBtn) return;
                const row = activeBtn.closest('.component-row');
                const left = row?.querySelector('div:first-child');
                if (!left) return;

                if (!data?.thumb_url || !data?.big_url) return;

                left.innerHTML = `
            <a href="${data.big_url}" data-fancybox="component-${componentId}" class="d-inline-block">
                <img class="component-avatar"
                     src="${data.thumb_url}"
                     alt="${escapeHtml(componentName || 'Component')}"
                     width="40" height="40">
            </a>
        `;

                // rebinding Fancybox
                if (window.Fancybox) {
                    Fancybox.bind(`[data-fancybox="component-${componentId}"]`, {
                        Toolbar: ["zoom", "fullscreen", "close"],
                        dragToClose: true,
                        placeFocusBack: false,
                        trapFocus: false,
                    });
                }
            }

            async function openCameraFor(btn) {
                if (isBusy) return;

                activeBtn = btn;
                activeComponentId = btn.dataset.componentId;
                activeComponentName = btn.dataset.componentName || '';

                if (!activeComponentId) return;

                haptic('light');
                setOverlay(true);
                await startCamera();
            }

            async function takeAndUpload() {
                if (isBusy) return;
                if (!activeComponentId) return;

                isBusy = true;
                haptic('medium');

                try {
                    statusEl.textContent = 'Capturing…';
                    const blob = await captureBlob();

                    statusEl.textContent = 'Uploading…';
                    if (typeof showLoadingSpinner === 'function') showLoadingSpinner();

                    const data = await uploadCaptured(blob);

                    updateAvatar(activeComponentId, activeComponentName, data);

                    if (typeof showSuccessMessage === 'function') showSuccessMessage('Photo updated');
                    statusEl.textContent = 'Done';
                    haptic('heavy');

                    // закрываем сразу, без лишних окон
                    stopCamera();
                    setOverlay(false);

                } catch (e) {
                    console.error(e);
                    if (typeof showErrorMessage === 'function') showErrorMessage(e.message || 'Camera/Upload error');
                    else alert(e.message || 'Camera/Upload error');
                    statusEl.textContent = 'Error';
                } finally {
                    if (typeof hideLoadingSpinner === 'function') hideLoadingSpinner();
                    isBusy = false;
                }
            }

            // click on camera icon => open overlay camera
            document.addEventListener('click', (e) => {
                const btn = e.target.closest('.js-component-camera');
                if (!btn) return;
                e.preventDefault();
                openCameraFor(btn);
            });

            // close
            btnClose?.addEventListener('click', () => {
                haptic('light');
                stopCamera();
                setOverlay(false);
            });

            // shutter
            btnShutter?.addEventListener('click', (e) => {
                e.preventDefault();
                takeAndUpload();
            });

            // tap outside video to close (optional, safe)
            overlay?.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    stopCamera();
                    setOverlay(false);
                }
            });

            // safety: stop camera if page hidden
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    stopCamera();
                    setOverlay(false);
                }
            });

            document.addEventListener('click', (e) => {
                const link = e.target.closest('.js-component-edit-link');
                if (!link) return;
                if (navigator.vibrate) navigator.vibrate(20);
            });


            // =================== EDIT MODAL (load + save via AJAX) ===================
            // =================== EDIT MODAL (all in blade) ===================
            const editModalEl = document.getElementById('componentEditModal');
            const editForm = document.getElementById('componentEditForm');
            const editTitle = document.getElementById('componentEditModalTitle');
            const editErr = document.getElementById('edit_error_box');

            const editId = document.getElementById('edit_component_id');
            const editName = document.getElementById('edit_name');
            const editIpl = document.getElementById('edit_ipl');
            const editPart = document.getElementById('edit_part');
            const editEff = document.getElementById('edit_eff');
            const editIsBush = document.getElementById('edit_is_bush');
            const editBushWrap = document.getElementById('edit_bush_wrap');
            const editBushIpl = document.getElementById('edit_bush_ipl');

            function haptic(type='light'){
                if (!navigator.vibrate) return;
                const map = { light: 10, medium: 20, heavy: 30 };
                navigator.vibrate(map[type] || 10);
            }

            function showEditError(msg){
                if (!editErr) return;
                editErr.textContent = msg || 'Error';
                editErr.classList.remove('d-none');
            }

            function clearEditError(){
                if (!editErr) return;
                editErr.textContent = '';
                editErr.classList.add('d-none');
            }

            function toggleBushUi(){
                if (!editBushWrap) return;
                editBushWrap.style.display = editIsBush?.checked ? '' : 'none';
                if (!editIsBush?.checked && editBushIpl) editBushIpl.value = '';
            }

            editIsBush?.addEventListener('change', toggleBushUi);

            function setEditAction(componentId){
                editForm.action = `{{ route('mobile.components.update', ['component' => ':id']) }}`.replace(':id', componentId);
            }

            function updateRowFromJson(componentId, item){
                const link = document.querySelector(`.js-component-edit-link[data-component-id="${componentId}"]`);
                const row = link?.closest('.component-row');
                if (!row) return;

                // имя
                link.textContent = item.name || ('#' + componentId);

                // обновим data-атрибуты, чтобы следующий edit открывался с актуальными данными
                link.dataset.name = item.name || '';
                link.dataset.ipl = item.ipl_num || '';
                link.dataset.part = item.part_number || '';
                link.dataset.eff = item.eff_code || '';
                link.dataset.isBush = item.is_bush ? '1' : '0';
                link.dataset.bushIpl = item.bush_ipl_num || '';

                // meta
                const meta = row.querySelector('.component-meta');
                if (meta) {
                    const bushBadge = item.is_bush ? ' <span class="badge bg-info text-dark ms-1">BUSH</span>' : '';
                    meta.innerHTML = `
            <span class="me-2"><span class="text-muted">IPL:</span> ${item.ipl_num || '—'}</span>
            <span class="me-2"><span class="text-muted">P/N:</span> ${item.part_number || '—'}</span>
            ${bushBadge}
        `;
                }
            }

// open edit on click name
            document.addEventListener('click', (e) => {
                const link = e.target.closest('.js-component-edit-link');
                if (!link) return;

                e.preventDefault();
                clearEditError();
                haptic('light');

                const id = link.dataset.componentId;
                if (!id) return;

                if (editTitle) editTitle.textContent = `Edit: ${link.dataset.name || ('#'+id)}`;

                if (editId) editId.value = id;
                if (editName) editName.value = link.dataset.name || '';
                if (editIpl) editIpl.value = link.dataset.ipl || '';
                if (editPart) editPart.value = link.dataset.part || '';
                if (editEff) editEff.value = link.dataset.eff || '';

                const isBush = (link.dataset.isBush === '1');
                if (editIsBush) editIsBush.checked = isBush;
                if (editBushIpl) editBushIpl.value = link.dataset.bushIpl || '';
                toggleBushUi();

                setEditAction(id);

                bootstrap.Modal.getOrCreateInstance(editModalEl).show();
            });

            // submit edit form ajax
            editForm?.addEventListener('submit', async (e) => {
                e.preventDefault();

                const id = editId?.value;
                if (!id) return;

                clearEditError();

                // показываем спиннер
                if (typeof showLoadingSpinner === 'function') showLoadingSpinner();

                try {
                    haptic('medium');

                    const fd = new FormData(editForm);

                    // PATCH через _method (на всякий)
                    if (!fd.get('_method')) fd.set('_method', 'PATCH');

                    // checkbox: если не отмечен — Laravel не получит поле → ставим 0
                    if (!editIsBush?.checked) fd.set('is_bush', '0');

                    // если вдруг где-то прилетает manual_id — вычищаем, чтобы не триггерить чужую валидацию
                    fd.delete('manual_id');

                    const res = await fetch(editForm.action, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        body: fd
                    });

                    // важное: ответ может быть НЕ JSON (например редирект/HTML)
                    const contentType = res.headers.get('content-type') || '';
                    let payload = null;

                    if (contentType.includes('application/json')) {
                        payload = await res.json();
                    } else {
                        const text = await res.text(); // чтобы не зависнуть
                        throw new Error('Server returned non-JSON response');
                    }

                    if (!res.ok || !payload?.ok) {
                        const msg =
                            payload?.message ||
                            (payload?.errors ? Object.values(payload.errors).flat().join(' ') : 'Save failed');
                        throw new Error(msg);
                    }

                    updateRowFromJson(id, payload.item);

                    if (typeof showSuccessMessage === 'function') showSuccessMessage('Component updated');
                    haptic('heavy');

                    bootstrap.Modal.getOrCreateInstance(editModalEl).hide();

                } catch (err) {
                    console.error(err);
                    showEditError(err?.message || 'Save error');
                    if (typeof showErrorMessage === 'function') showErrorMessage(err?.message || 'Save error');
                } finally {
                    safeHideSpinner();
                }
            });



        });
    </script>
@endsection
