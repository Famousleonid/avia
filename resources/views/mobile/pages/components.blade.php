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
            border-width: 2px;
        }

    </style>
@endsection

@section('content')

    <div class="container-fluid d-flex flex-column bg-dark p-0"
         style="min-height: calc(100vh - 80px); padding-top: 60px;">

        <div id="block-info" class="rounded-3 border border-info gradient-pane shadow-sm" style="margin: 5px; padding: 3px;">
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

        <hr class="border-secondary opacity-50 my-1">

        <div class="row g-0 flex-grow-1" style="background-color:#343A40;">
            <div class="col-12 p-0">

                <div class="bg-dark py-2 px-3 d-flex justify-content-between align-items-center border-bottom mt-1">
                    <div class="d-flex align-items-center gap-2">
                        <h6 class="mb-0 text-primary">{{ __('Components') }}</h6>
                        <span class="text-info">({{ $components->count() }})</span>
                    </div>

                    <button class="btn btn-success btn-sm text-format" id="openAddComponentBtn">
                        {{ __('Add Component') }}
                    </button>
                </div>

                @if($components->isEmpty())
                    <div class="text-center text-muted small py-3">
                        {{ __('COMPONENTS NOT CREATED') }}
                    </div>
                @else


                    <div class="list-group">
                        @foreach($components as $component)

                            @php
                                $codeName = $codeNamesByComponent[$component->id] ?? null;
                            @endphp

                            @if(!$component) @continue @endif

                            <div class="list-group-item bg-transparent text-light border-secondary">
                                <div class="component-row">

                                    {{-- LEFT --}}
                                    <div class="">
                                        @if($component->getFirstMediaBigUrl('components'))
                                            <a href="{{ $component->getFirstMediaBigUrl('components') }}"
                                               data-fancybox="component-{{ $component->id }}">
                                                <img class="rounded-circle"
                                                     src="{{ $component->getFirstMediaThumbnailUrl('components') ?: $component->getFirstMediaBigUrl('components') }}"
                                                     alt="Img" width="40" height="40">
                                            </a>
                                        @else
                                            <img class="rounded-circle opacity-50"
                                                 src="{{ asset('img/no-image.png') }}"
                                                 alt="No image" width="40" height="40">
                                        @endif
                                    </div>

                                    {{-- CENTER: info --}}
                                    <div class="break-anywhere">
                                        <div class="fw-semibold text-info">
                                            {{ $component->name ?? ('#'.$component->id) }}
                                        </div>

                                        <div class="small text-secondary">
                                            <span class="me-2"><span class="text-muted">IPL:</span> {{ $component->ipl_num ?? '—' }}</span><span class="me-2">
                                            <span class="text-muted">P/N:</span> {{ $component->part_number ?? '—' }}</span>

                                            @if(!empty($component->is_bush))
                                                <span class="badge bg-info text-dark ms-1">BUSH</span>
                                            @endif
                                        </div>

                                        <div class="small text-secondary">
                                            <span class="text-muted">Code:</span> {{ $codeName ?: '—' }}
                                        </div>
                                    </div>

                                    {{-- RIGHT: camera only --}}
                                    <div class="text-end">
                                        <a href="#"
                                           class="btn btn-outline-info btn-sm p-2"
                                           title="Add photos">
                                            {{-- SVG camera --}}
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                                                <path d="M10.5 8.5a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0z"/>
                                                <path d="M2 4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-1.172a2 2 0 0 1-1.414-.586l-.828-.828A2 2 0 0 0 9.172 2H6.828a2 2 0 0 0-1.414.586l-.828.828A2 2 0 0 1 3.172 4H2zm12 1a1 1 0 0 1 1 1v6a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1h1.172a3 3 0 0 0 2.121-.879l.828-.828A1 1 0 0 1 6.828 3h2.344a1 1 0 0 1 .707.293l.828.828A3 3 0 0 0 12.828 5H14z"/>
                                            </svg>
                                        </a>
                                    </div>

                                </div>
                            </div>

                        @endforeach
                    </div>
            </div>
            @endif

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

                            {{-- заголовок + кнопка в одной линии --}}
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <label class="form-label mb-0">Choose component</label>

                                <button type="button"
                                        class="btn btn-outline-info btn-sm"
                                        id="btnShowCreateComponent">
                                    + Add new component
                                </button>
                            </div>

                            {{-- кнопка выбора --}}
                            <button type="button"
                                    class="btn btn-outline-light w-100 text-start"
                                    data-open-picker>
                                <span id="pickedComponentText" class="text-muted">Tap to choose…</span>
                            </button>

                        </div>

                        <hr class="border-secondary opacity-50">

                        {{-- create new --}}
                        <div id="createComponentBox" class="border rounded p-3 d-none">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="fw-semibold">Create new component</div>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnHideCreateComponent">Close</button>
                            </div>

                            <div class="mb-2">
                                <label class="form-label">IPL Number</label>
                                <input type="text" name="new_ipl_num" class="form-control">
                            </div>

                            <div class="mb-2">
                                <label class="form-label">Part Number</label>
                                <input type="text" name="new_part_number" class="form-control">
                            </div>

                            {{--                            <div class="mb-2">--}}
                            {{--                                <label class="form-label">EFF Code (optional)</label>--}}
                            {{--                                <input type="text" name="new_eff_code" class="form-control">--}}
                            {{--                            </div>--}}

                            <div class="mb-2">
                                <label class="form-label">Component Name</label>
                                <input type="text" name="new_name" class="form-control">
                            </div>

                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="new_is_bush" name="new_is_bush">
                                <label class="form-check-label" for="new_is_bush">is_bush</label>
                            </div>

                            <div class="mt-2 d-none" id="new_bush_container">
                                <label class="form-label">bush_ipl_num</label>
                                <input type="text" class="form-control" name="new_bush_ipl_num" id="new_bush_ipl_num">
                            </div>

                            <div class="mt-2">
                                <label class="form-label">Component Photo (optional)</label>
                                <input type="file" name="new_photo" accept="image/*" class="form-control">
                            </div>

                            <button type="button" class="btn btn-info w-100 mt-3" id="btnCreateComponent">
                                Create & Select
                            </button>
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

        <div class="offcanvas-body p-0">
            <div class="p-3 border-bottom border-secondary">
                <input type="text" class="form-control" id="componentsFilter"
                       placeholder="Search IPL / P/N / Name…">
            </div>

            <div class="picker-list" id="componentsList">
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
            document.getElementById('openAddComponentBtn')?.addEventListener('click', () => {
                const el = document.getElementById('addComponentModal');
                if (!el) return;
                new bootstrap.Modal(el).show();
            });

            // show/hide create box
            const createBox = document.getElementById('createComponentBox');
            document.getElementById('btnShowCreateComponent')?.addEventListener('click', () => createBox?.classList.remove('d-none'));
            document.getElementById('btnHideCreateComponent')?.addEventListener('click', () => createBox?.classList.add('d-none'));

            // new_is_bush toggle -> show bush field
            const newIsBush = document.getElementById('new_is_bush');
            const newBushContainer = document.getElementById('new_bush_container');
            const newBushInput = document.getElementById('new_bush_ipl_num');

            function toggleNewBush() {
                if (!newIsBush || !newBushContainer || !newBushInput) return;
                if (newIsBush.checked) {
                    newBushContainer.classList.remove('d-none');
                    newBushInput.required = true;
                } else {
                    newBushContainer.classList.add('d-none');
                    newBushInput.required = false;
                    newBushInput.value = '';
                }
            }

            newIsBush?.addEventListener('change', toggleNewBush);
            toggleNewBush();

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

            // quick create -> add + select
            document.getElementById('btnCreateComponent')?.addEventListener('click', async () => {
                const url = "{{ route('mobile.components.quickStore') }}";

                const fd = new FormData();
                fd.append('workorder_id', "{{ $workorder->id }}");
                fd.append('ipl_num', document.querySelector('[name="new_ipl_num"]')?.value || '');
                fd.append('part_number', document.querySelector('[name="new_part_number"]')?.value || '');
                fd.append('eff_code', document.querySelector('[name="new_eff_code"]')?.value || '');
                fd.append('name', document.querySelector('[name="new_name"]')?.value || '');

                const isBush = document.getElementById('new_is_bush')?.checked ? '1' : '0';
                fd.append('is_bush', isBush);
                if (isBush === '1') {
                    fd.append('bush_ipl_num', document.getElementById('new_bush_ipl_num')?.value || '');
                }

                const photo = document.querySelector('[name="new_photo"]');
                if (photo?.files?.[0]) fd.append('photo', photo.files[0]);

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
                        alert(json?.message || 'Create failed');
                        return;
                    }

                    // select new component
                    if (hiddenId) hiddenId.value = json.item.id;
                    if (pickedText) {
                        pickedText.classList.remove('text-muted');
                        pickedText.textContent = json.item.text;
                    }

                    // add to picker (top)
                    const list = document.getElementById('componentsList');
                    if (list) {
                        const b = document.createElement('button');
                        b.type = 'button';
                        b.className = 'w-100 text-start px-3 py-3 bg-dark text-light border-0 picker-item component-pick';
                        b.dataset.id = json.item.id;
                        b.dataset.text = json.item.text;

                        const bushBadge = json.item.is_bush ? '<span class="badge bg-info text-dark ms-2">BUSH</span>' : '';
                        const bushLine = (json.item.is_bush && json.item.bush_ipl_num)
                            ? `<span class="me-2"><span class="text-muted">Bush IPL:</span> ${escapeHtml(json.item.bush_ipl_num)}</span>`
                            : '';

                        b.innerHTML = `
                    <div class="fw-semibold text-info">${escapeHtml(json.item.name)} ${bushBadge}</div>
                    <div class="small text-secondary">
                        ${escapeHtml(json.item.text)}
                        ${bushLine}
                    </div>
                `;
                        list.prepend(b);
                    }

                    createBox?.classList.add('d-none');

                } catch (e) {
                    console.error(e);
                    alert('Create failed');
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

        function escapeHtml(str) {
            return String(str ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }
    </script>
@endsection
