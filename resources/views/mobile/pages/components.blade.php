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
        .components-list-container::-webkit-scrollbar {
            width: 6px;
        }

        .components-list-container::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }

        .components-list-container::-webkit-scrollbar-thumb {
            background: rgba(13, 202, 240, 0.5);
            border-radius: 3px;
        }

        .components-list-container::-webkit-scrollbar-thumb:hover {
            background: rgba(13, 202, 240, 0.7);
        }

        .picker-item {
            border-bottom: 1px solid rgba(255, 255, 255, .15);
        }

        .picker-item:hover {
            background: rgba(13, 202, 240, .08);
        }

        .mini-muted {
            font-size: .8rem;
            color: rgba(255, 255, 255, .6);
        }

        /* Offcanvas above modal */
        .offcanvas {
            z-index: 2000 !important;
        }

        .offcanvas-backdrop {
            z-index: 1990 !important;
        }

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

        .component-title {
            line-height: 1.1;
            margin-bottom: 2px;
        }

        .component-meta {
            line-height: 1.1;
        }

        /* Чтобы длинные штуки не ломали сетку */
        .break-anywhere {
            overflow-wrap: anywhere;
            word-break: break-word;
        }

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
                                        <div class="fw-semibold text-info">
                                            {{ $component->name ?? ('#'.$component->id) }}
                                        </div>

                                        <div class="small text-secondary">
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
                                                class="btn btn-outline-info btn-sm btn-camera"
                                                data-component-id="{{ $component->id }}"
                                                data-component-name="{{ $component->name ?? ('#'.$component->id) }}"
                                                data-bs-toggle="modal"
                                                data-bs-target="#componentPhotoModal"
                                                title="Update photo">
                                            {{-- SVG camera --}}
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

    {{-- MODAL --}}
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

                            {{-- кнопка выбора --}}
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

                        {{-- Qty field (for Missing or Order New) --}}
                        <div class="mb-3 d-none" id="qty_container">
                            <label class="form-label mb-2">Quantity <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="qty" name="qty" min="1" value="1">
                        </div>

                        {{-- Necessaries select (for other codes) --}}
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

                        {{-- Serial Number (for Repair) --}}
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

    {{-- MODAL FOR COMPONENT PHOTO --}}
    <div class="modal fade" id="componentPhotoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header">
                    <h5 class="modal-title" id="componentPhotoModalTitle">Update Component Photo</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <div id="componentPhotoPreview" class="mb-3">
                            <img id="componentPhotoPreviewImg"
                                 src="{{ asset('img/noimage.png') }}"
                                 alt="Preview"
                                 class="rounded-circle"
                                 style="width: 120px; height: 120px; object-fit: cover; border: 2px solid #0dcaf0;">
                        </div>
                        <div class="small text-muted mb-3" id="componentPhotoName"></div>
                    </div>

                    <form id="componentPhotoForm">
                        <input type="hidden" id="componentPhotoId" name="component_id" value="">
                        <div class="mb-3">
                            <label for="componentPhotoInput" class="form-label">Select Photo</label>
                            <input type="file"
                                   class="form-control"
                                   id="componentPhotoInput"
                                   name="photo"
                                   accept="image/*"
                                   required>
                            <div class="form-text text-muted">Photo will replace existing one (max 5MB)</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="btnSaveComponentPhoto">Save Photo</button>
                </div>
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

@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {

            Fancybox.bind('[data-fancybox^="component-"]', {
                Toolbar: ["zoom", "fullscreen", "close"],
                dragToClose: true,
                placeFocusBack: false,
                trapFocus: false,
            });

            // open modal
            const addComponentModal = document.getElementById('addComponentModal');
            document.getElementById('openAddComponentBtn')?.addEventListener('click', () => {
                if (!addComponentModal) return;
                new bootstrap.Modal(addComponentModal).show();
            });

            // Reset form when modal closes
            addComponentModal?.addEventListener('hidden.bs.modal', () => {
                const form = document.getElementById('componentAttachForm');
                if (form) {
                    form.reset();
                    // Reset all dynamic fields
                    qtyContainer?.classList.add('d-none');
                    necessariesContainer?.classList.add('d-none');
                    serialContainer?.classList.add('d-none');
                    qtyInput.required = false;
                    serialInput.required = false;
                    necessariesSelect.required = false;
                    document.getElementById('component_id').value = '';
                    document.getElementById('pickedComponentText').textContent = 'Tap to choose…';
                    document.getElementById('pickedComponentText').classList.add('text-muted');
                }
            });

            // Code selection logic
            const codeSelect = document.getElementById('code_id');
            const qtyContainer = document.getElementById('qty_container');
            const necessariesContainer = document.getElementById('necessaries_container');
            const serialContainer = document.getElementById('serial_container');
            const qtyInput = document.getElementById('qty');
            const necessariesSelect = document.getElementById('necessaries_id');
            const serialInput = document.getElementById('serial_number');

            function handleCodeChange() {
                const selectedOption = codeSelect?.options[codeSelect.selectedIndex];
                if (!selectedOption || !selectedOption.value) {
                    // Reset all
                    qtyContainer?.classList.add('d-none');
                    necessariesContainer?.classList.add('d-none');
                    serialContainer?.classList.add('d-none');
                    qtyInput.required = false;
                    serialInput.required = false;
                    necessariesSelect.required = false;
                    return;
                }

                const codeName = selectedOption.dataset.codeName?.toLowerCase() || '';

                // Hide all first
                qtyContainer?.classList.add('d-none');
                necessariesContainer?.classList.add('d-none');
                serialContainer?.classList.add('d-none');
                qtyInput.required = false;
                serialInput.required = false;
                necessariesSelect.required = false;
                necessariesSelect.value = '';
                serialInput.value = '';

                // If Missing - show qty
                if (codeName.includes('missing')) {
                    qtyContainer?.classList.remove('d-none');
                    qtyInput.required = true;
                } else {
                    // For other codes - show necessaries select
                    necessariesContainer?.classList.remove('d-none');
                    necessariesSelect.required = true;
                }
            }

            codeSelect?.addEventListener('change', handleCodeChange);

            // Handle necessaries change
            necessariesSelect?.addEventListener('change', () => {
                const selectedOption = necessariesSelect?.options[necessariesSelect.selectedIndex];
                if (!selectedOption || !selectedOption.value) {
                    serialContainer?.classList.add('d-none');
                    qtyContainer?.classList.add('d-none');
                    serialInput.required = false;
                    qtyInput.required = false;
                    return;
                }

                const necessaryName = selectedOption.dataset.necessaryName?.toLowerCase() || '';

                // Hide both first
                serialContainer?.classList.add('d-none');
                qtyContainer?.classList.add('d-none');
                serialInput.required = false;
                qtyInput.required = false;
                serialInput.value = '';

                // If Order New - show qty
                if (necessaryName.includes('order') && necessaryName.includes('new')) {
                    qtyContainer?.classList.remove('d-none');
                    qtyInput.required = true;
                }
                // If Repair - show serial number
                else if (necessaryName.includes('repair')) {
                    serialContainer?.classList.remove('d-none');
                    serialInput.required = true;
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
                if (offEl) {
                    const off = bootstrap.Offcanvas.getInstance(offEl) || new bootstrap.Offcanvas(offEl);
                    off.hide();
                }
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
                // Reset form
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

            // Create component in picker
            document.getElementById('btnCreateInPicker')?.addEventListener('click', async () => {
                const iplNum = document.getElementById('picker_ipl_num')?.value?.trim();
                const partNumber = document.getElementById('picker_part_number')?.value?.trim();
                const name = document.getElementById('picker_name')?.value?.trim();
                const isBush = document.getElementById('picker_is_bush')?.checked;
                const bushIpl = isBush ? (document.getElementById('picker_bush_ipl_num')?.value?.trim() || '') : '';
                const photo = document.getElementById('picker_photo')?.files?.[0];

                if (!iplNum || !partNumber || !name) {
                    showErrorMessage('Please fill in all required fields (IPL Number, Part Number, Component Name)');
                    return;
                }

                if (isBush && !bushIpl) {
                    showErrorMessage('Please enter Bush IPL Number');
                    return;
                }

                const url = "{{ route('mobile.components.quickStore') }}";
                const fd = new FormData();
                fd.append('workorder_id', "{{ $workorder->id }}");
                fd.append('ipl_num', iplNum);
                fd.append('part_number', partNumber);
                fd.append('name', name);
                fd.append('is_bush', isBush ? '1' : '0');
                if (isBush) {
                    fd.append('bush_ipl_num', bushIpl);
                }
                if (photo) {
                    fd.append('photo', photo);
                }

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
                        showErrorMessage(json?.message || 'Create failed');
                        return;
                    }

                    // Select the new component
                    const hiddenId = document.getElementById('component_id');
                    const pickedText = document.getElementById('pickedComponentText');

                    if (hiddenId) hiddenId.value = json.item.id;
                    if (pickedText) {
                        pickedText.classList.remove('text-muted');
                        pickedText.textContent = json.item.text;
                    }

                    // Add to picker list (at the top)
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

                    // Close create form
                    createInPicker?.classList.add('d-none');

                    // Reset form
                    document.getElementById('picker_ipl_num').value = '';
                    document.getElementById('picker_part_number').value = '';
                    document.getElementById('picker_name').value = '';
                    document.getElementById('picker_is_bush').checked = false;
                    document.getElementById('picker_bush_ipl_num').value = '';
                    document.getElementById('picker_photo').value = '';
                    pickerBushContainer?.classList.add('d-none');

                    // Close picker
                    const offEl = document.getElementById('componentsPicker');
                    if (offEl) {
                        const off = bootstrap.Offcanvas.getInstance(offEl);
                        off?.hide();
                    }

                    // Show success message
                    showSuccessMessage('Component created and selected successfully');

                } catch (e) {
                    console.error(e);
                    showErrorMessage('Create failed');
                } finally {
                    if (typeof hideLoadingSpinner === 'function') hideLoadingSpinner();
                }
            });


        });

        document.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-open-picker]');
            if (!btn) return;

            const el = document.getElementById('componentsPicker');
            if (!el) return;

            const off = bootstrap.Offcanvas.getOrCreateInstance(el);
            off.show();
        });

        // Reset create form when picker closes
        const componentsPicker = document.getElementById('componentsPicker');
        componentsPicker?.addEventListener('hidden.bs.offcanvas', () => {
            const createInPicker = document.getElementById('createComponentInPicker');
            if (createInPicker) {
                createInPicker.classList.add('d-none');
                // Reset form
                document.getElementById('picker_ipl_num').value = '';
                document.getElementById('picker_part_number').value = '';
                document.getElementById('picker_name').value = '';
                const pickerIsBush = document.getElementById('picker_is_bush');
                if (pickerIsBush) pickerIsBush.checked = false;
                document.getElementById('picker_bush_ipl_num').value = '';
                document.getElementById('picker_photo').value = '';
                const pickerBushContainer = document.getElementById('picker_bush_container');
                if (pickerBushContainer) pickerBushContainer.classList.add('d-none');
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

        // Component Photo Modal
        const componentPhotoModal = document.getElementById('componentPhotoModal');
        const componentPhotoInput = document.getElementById('componentPhotoInput');
        const componentPhotoPreviewImg = document.getElementById('componentPhotoPreviewImg');
        const componentPhotoId = document.getElementById('componentPhotoId');
        const componentPhotoName = document.getElementById('componentPhotoName');
        const componentPhotoModalTitle = document.getElementById('componentPhotoModalTitle');
        let currentComponentData = null;

        // Open modal with component data
        componentPhotoModal?.addEventListener('show.bs.modal', (e) => {
            const button = e.relatedTarget;
            if (!button) return;

            const componentId = button.dataset.componentId;
            const componentName = button.dataset.componentName;

            currentComponentData = {
                id: componentId,
                name: componentName,
                currentPhoto: button.closest('.component-row')?.querySelector('img')?.src
            };

            componentPhotoId.value = componentId;
            componentPhotoName.textContent = componentName;
            componentPhotoModalTitle.textContent = `Update Photo: ${componentName}`;

            // Show current photo if exists
            if (currentComponentData.currentPhoto && !currentComponentData.currentPhoto.includes('noimage.png') && !currentComponentData.currentPhoto.includes('no-image.png')) {
                componentPhotoPreviewImg.src = currentComponentData.currentPhoto;
            } else {
                componentPhotoPreviewImg.src = "{{ asset('img/noimage.png') }}";
            }

            // Reset form
            componentPhotoInput.value = '';
        });

        // Preview photo before upload
        componentPhotoInput?.addEventListener('change', (e) => {
            const file = e.target.files?.[0];
            if (!file) return;

            if (!file.type.startsWith('image/')) {
                showErrorMessage('Please select an image file');
                e.target.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = (event) => {
                componentPhotoPreviewImg.src = event.target.result;
            };
            reader.readAsDataURL(file);
        });

        // Save photo
        document.getElementById('btnSaveComponentPhoto')?.addEventListener('click', async () => {
            const file = componentPhotoInput?.files?.[0];
            if (!file) {
                showErrorMessage('Please select a photo');
                return;
            }

            const formData = new FormData();
            formData.append('photo', file);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');

            const componentId = componentPhotoId.value;
            if (!componentId) {
                showErrorMessage('Component ID is missing');
                return;
            }

            const url = `{{ route('mobile.components.updatePhoto', ['component' => ':id']) }}`.replace(':id', componentId);

            try {
                if (typeof showLoadingSpinner === 'function') showLoadingSpinner();

                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: formData,
                });

                const data = await response.json();

                if (!response.ok || !data?.ok) {
                    showErrorMessage(data?.message || 'Failed to update photo');
                    return;
                }

                // Update photo in the list
                const cameraBtn = document.querySelector(`[data-component-id="${componentId}"]`);
                const componentRow = cameraBtn?.closest('.component-row');
                if (componentRow && data.thumb_url && data.big_url) {
                    const avatarContainer = componentRow.querySelector('div:first-child');

                    if (avatarContainer) {
                        // Create new link with image
                        const link = document.createElement('a');
                        link.href = data.big_url;
                        link.setAttribute('data-fancybox', `component-${componentId}`);
                        link.className = 'd-inline-block';

                        const newImg = document.createElement('img');
                        newImg.className = 'component-avatar';
                        newImg.src = data.thumb_url;
                        newImg.alt = currentComponentData?.name || 'Component';
                        newImg.width = 40;
                        newImg.height = 40;

                        link.appendChild(newImg);
                        avatarContainer.innerHTML = '';
                        avatarContainer.appendChild(link);

                        // Rebind Fancybox for new image
                        if (typeof Fancybox !== 'undefined') {
                            Fancybox.bind(link, {
                                Toolbar: ["zoom", "fullscreen", "close"],
                                dragToClose: true,
                                placeFocusBack: false,
                                trapFocus: false,
                            });
                        }
                    }
                }

                // Close modal
                const modal = bootstrap.Modal.getInstance(componentPhotoModal);
                modal?.hide();

                // Show success message
                showSuccessMessage('Photo updated successfully');

            } catch (error) {
                console.error('Error updating photo:', error);
                showErrorMessage('Failed to update photo');
            } finally {
                if (typeof hideLoadingSpinner === 'function') hideLoadingSpinner();
            }
        });

        // Reset modal on close
        componentPhotoModal?.addEventListener('hidden.bs.modal', () => {
            componentPhotoInput.value = '';
            componentPhotoId.value = '';
            currentComponentData = null;
        });
    </script>
@endsection
