@extends('admin.master')

@section('style')
    <style>
        .dir-description-cell { max-width: 360px; white-space: normal; line-height: 1.35; }
        .dir-vendor-media-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: .85rem; }
        .dir-vendor-media-item { border: 1px solid rgba(255, 255, 255, 0.1); border-radius: .65rem; padding: .75rem; background: rgba(255, 255, 255, 0.04); }
        .dir-vendor-media-thumb { width: 100%; height: 120px; object-fit: cover; border-radius: .5rem; margin-bottom: .6rem; }
        .dir-vendor-media-link { display: block; text-decoration: none; color: inherit; }
        .dir-vendor-media-link:hover { color: inherit; }
    </style>
@endsection

@section('content')
    @php
        $isVendorDirectory = $slug === 'vendors';
    @endphp

    <div class="card border-0 dir-page">
        <div class="card-header p-0 mx-3 bg-transparent border-0 dir-topbar">
            <div class="dir-topbar px-3 py-1">
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-lg-3">
                        <h5 class="mb-0 text-primary">
                            {{ __($cfg['title']) }}
                            <span class="text-secondary">(</span>
                            <span class="text-success">{{ $items->total() }}</span>
                            <span class="text-secondary">)</span>
                        </h5>
                    </div>

                    <div class="col-12 col-lg-6">
                        <div class="d-flex justify-content-lg-center">
                            <div class="position-relative w-100" style="max-width:420px;">
                                <input id="dirSearchInput" type="text" class="form-control pe-5 dir-search" placeholder="Search..." value="{{ $q ?? '' }}">
                                <button type="button" class="btn position-absolute top-50 end-0 translate-middle-y me-2 p-0 border-0 bg-transparent text-secondary" title="Clear" onclick="const i=document.getElementById('dirSearchInput'); i.value=''; i.dispatchEvent(new Event('input')); i.focus();">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-3">
                        <div class="d-flex justify-content-lg-end">
                            <button class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#dirCreateModal">
                                <i class="bi bi-plus-circle me-1"></i>{{ __('Add') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body pt-1 m-0">
            @if($items->count())
                <div class="dir-panel">
                    <div class="table-responsive">
                        <table id="dirTable" class="table table-sm table-hover table-bordered mb-0 align-middle shadow-lg dir-table">
                            <thead>
                            <tr>
                                @foreach($cfg['fields'] as $field => $label)
                                    <th class="text-primary sortable px-2" data-sort-field="{{ $field }}" data-direction="asc" style="{{ $field === 'print_form' ? 'width:8%; min-width:90px;' : 'min-width:140px;' }}">
                                        {{ __($label) }}
                                        <i class="bi bi-chevron-expand ms-1"></i>
                                    </th>
                                @endforeach
                                @if($isVendorDirectory)
                                    <th class="text-primary text-center" style="width:130px; min-width:130px;">{{ __('Files') }}</th>
                                @endif
                                <th class="text-primary text-center" style="{{ $isVendorDirectory ? 'width:170px;' : 'width:140px;' }}">{{ __('Action') }}</th>
                            </tr>
                            </thead>
                            <tbody id="dirTbody">
                            @foreach($items as $item)
                                <tr data-row data-id="{{ $item->id }}" @if($isVendorDirectory) data-media-count="{{ (int)($item->media_count ?? 0) }}" @endif
                                    @foreach($cfg['fields'] as $field => $label)
                                        @php
                                            $rawValue = $item->{$field} ?? '';
                                            $metaRow = $cfg['fieldsMeta'][$field] ?? [];
                                            $typeRow = $metaRow['type'] ?? 'text';
                                            if (in_array($typeRow, ['boolean', 'checkbox'], true)) {
                                                $rawValue = $item->{$field} ? 1 : 0;
                                            }
                                        @endphp
                                        data-{{ $field }}="{{ e((string)$rawValue) }}"
                                    @endforeach
                                >
                                    @foreach($cfg['fields'] as $field => $label)
                                        @php
                                            $meta = $cfg['fieldsMeta'][$field] ?? [];
                                            $type = $meta['type'] ?? 'text';
                                            $val = $item->{$field} ?? '';
                                            $display = $val;
                                            if ($type === 'select') {
                                                $opts = $meta['options'] ?? [];
                                                $display = $opts[$val] ?? $val;
                                            }
                                            if ($type === 'boolean' || $type === 'checkbox') {
                                                $display = $val ? 'Yes' : 'No';
                                            }
                                        @endphp
                                        <td class="px-2 {{ $field === 'print_form' ? 'text-center' : '' }}" title="{{ (string)$display }}">
                                            @if($type === 'boolean' || $type === 'checkbox')
                                                <button type="button" class="btn btn-sm js-dir-toggle {{ $val ? 'btn-success' : 'btn-outline-secondary' }}" data-id="{{ $item->id }}" data-field="{{ $field }}" data-value="{{ $val ? 1 : 0 }}">{{ $val ? 'Yes' : 'No' }}</button>
                                            @elseif($field === 'description')
                                                <div class="dir-description-cell">{{ $display !== '' && $display !== null ? \Illuminate\Support\Str::limit((string)$display, 140) : '--' }}</div>
                                            @else
                                                {{ $display !== '' && $display !== null ? $display : '--' }}
                                            @endif
                                        </td>
                                    @endforeach
                                    @if($isVendorDirectory)
                                        <td class="text-center">
                                            <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#dirVendorModal" onclick="dirOpenVendorAssets(this.closest('tr'))">
                                                <i class="bi bi-images me-1"></i><span class="js-dir-media-count">{{ (int)($item->media_count ?? 0) }}</span>
                                            </button>
                                        </td>
                                    @endif
                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                            @if($isVendorDirectory)
                                                <button class="btn btn-outline-primary btn-sm btn-icon me-2" data-bs-toggle="modal" data-bs-target="#dirVendorModal" onclick="dirOpenVendorAssets(this.closest('tr'))" title="View and edit"><i class="bi bi-pencil-square"></i></button>
                                            @else
                                                <button class="btn btn-outline-primary btn-sm btn-icon me-2" data-bs-toggle="modal" data-bs-target="#dirEditModal" onclick="dirOpenEdit(this.closest('tr'))" title="Edit"><i class="bi bi-pencil-square"></i></button>
                                            @endif
                                            <button class="btn btn-outline-danger btn-sm btn-icon" data-bs-toggle="modal" data-bs-target="#dirDeleteModal" onclick="dirOpenDelete(this.closest('tr'))" title="Delete"><i class="bi bi-trash"></i></button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div id="dirNoResults" class="d-none p-4 text-center mt-2">
                        <div class="text-secondary mb-2"><i class="bi bi-search" style="font-size: 1.6rem;"></i></div>
                        <div class="fw-semibold">No results</div>
                        <div class="small opacity-75">Try another search term.</div>
                    </div>
                </div>
            @else
                <div class="alert alert-secondary mb-0">No records.</div>
            @endif
        </div>
    </div>

    <div class="modal fade" id="dirCreateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-gray shadow">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Add') }} {{ __($cfg['title']) }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="dirCreateForm" method="POST" action="{{ $cfg['baseUrl'] }}">
                        @csrf
                        @foreach($cfg['fields'] as $field => $label)
                            @php
                                $meta = $cfg['fieldsMeta'][$field] ?? [];
                                $rules = $meta['rules'] ?? [];
                                $required = in_array('required', $rules, true);
                                $type = $meta['type'] ?? 'text';
                            @endphp
                            <div class="mb-3">
                                <label class="form-label" for="dirCreate_{{ $field }}">{{ __($label) }}</label>
                                @if($type === 'select')
                                    <select id="dirCreate_{{ $field }}" name="{{ $field }}" class="form-select" @if($required) required @endif>
                                        <option value="">{{ $meta['placeholder'] ?? '-- Select --' }}</option>
                                        @foreach(($meta['options'] ?? []) as $val => $text)
                                            <option value="{{ $val }}" @selected((string)old($field) === (string)$val)>{{ $text }}</option>
                                        @endforeach
                                    </select>
                                @elseif($type === 'boolean' || $type === 'checkbox')
                                    <div class="form-check form-switch">
                                        <input type="hidden" name="{{ $field }}" value="0">
                                        <input class="form-check-input" type="checkbox" id="dirCreate_{{ $field }}" name="{{ $field }}" value="1" @checked(old($field))>
                                        <label class="form-check-label" for="dirCreate_{{ $field }}">Yes</label>
                                    </div>
                                @elseif($type === 'textarea')
                                    <textarea id="dirCreate_{{ $field }}" name="{{ $field }}" class="form-control" rows="5" @if($required) required @endif>{{ old($field) }}</textarea>
                                @else
                                    <input type="{{ $type === 'number' ? 'number' : 'text' }}" id="dirCreate_{{ $field }}" name="{{ $field }}" class="form-control" value="{{ old($field) }}" @if($type === 'number') step="1" @endif @if($required) required @endif>
                                @endif
                                @error($field)
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        @endforeach
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="dirEditModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-gray shadow">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Edit') }} {{ __($cfg['title']) }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="dirEditForm" method="POST" action="">
                        @csrf
                        @method('PUT')
                        <input type="hidden" id="dirEditId" name="id">
                        @foreach($cfg['fields'] as $field => $label)
                            @php
                                $meta = $cfg['fieldsMeta'][$field] ?? [];
                                $rules = $meta['rules'] ?? [];
                                $required = in_array('required', $rules, true);
                                $type = $meta['type'] ?? 'text';
                            @endphp
                            <div class="mb-3">
                                <label class="form-label" for="dirEdit_{{ $field }}">{{ __($label) }}</label>
                                @if($type === 'select')
                                    <select id="dirEdit_{{ $field }}" name="{{ $field }}" class="form-select" @if($required) required @endif>
                                        <option value="">{{ $meta['placeholder'] ?? '-- Select --' }}</option>
                                        @foreach(($meta['options'] ?? []) as $val => $text)
                                            <option value="{{ $val }}">{{ $text }}</option>
                                        @endforeach
                                    </select>
                                @elseif($type === 'boolean' || $type === 'checkbox')
                                    <div class="form-check form-switch">
                                        <input type="hidden" name="{{ $field }}" value="0">
                                        <input class="form-check-input" type="checkbox" id="dirEdit_{{ $field }}" name="{{ $field }}" value="1">
                                        <label class="form-check-label" for="dirEdit_{{ $field }}">Yes</label>
                                    </div>
                                @elseif($type === 'textarea')
                                    <textarea id="dirEdit_{{ $field }}" name="{{ $field }}" class="form-control" rows="5" @if($required) required @endif></textarea>
                                @else
                                    <input type="{{ $type === 'number' ? 'number' : 'text' }}" id="dirEdit_{{ $field }}" name="{{ $field }}" class="form-control" @if($type === 'number') step="1" @endif @if($required) required @endif>
                                @endif
                            </div>
                        @endforeach
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-save2 me-1"></i>Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="dirDeleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-gray shadow">
                <div class="modal-header">
                    <h5 id="dirDeleteTitle" class="modal-title">{{ __('Delete') }} {{ __($cfg['title']) }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning mb-3">Are you sure you want to delete this record?</div>
                    <form id="dirDeleteForm" method="POST" action="">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" id="dirDeleteId" name="id">
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger"><i class="bi bi-trash3 me-1"></i>Delete</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if($isVendorDirectory)
        <div class="modal fade" id="dirVendorModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content modal-gray shadow">
                    <div class="modal-header">
                        <h5 class="modal-title" id="dirVendorModalTitle">Vendor</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-4">
                            <div class="col-lg-5">
                                <div class="mb-3">
                                    <label class="form-label" for="dirVendorName">Name</label>
                                    <input type="text" id="dirVendorName" class="form-control">
                                </div>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" role="switch" id="dirVendorTrusted">
                                    <label class="form-check-label" for="dirVendorTrusted">Trusted vendor</label>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="dirVendorDescription">Description</label>
                                    <textarea id="dirVendorDescription" class="form-control" rows="9" placeholder="Vendor notes..."></textarea>
                                </div>
                                <div id="dirVendorStatus" class="small text-muted"></div>
                            </div>
                            <div class="col-lg-7">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                                    <div>
                                        <div class="fw-semibold">Files</div>
                                        <div class="small text-muted">See, add and delete vendor images or PDF files.</div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <input type="file" id="dirVendorMediaInput" class="d-none" accept=".jpg,.jpeg,.png,.webp,.pdf" multiple>
                                        <button type="button" class="btn btn-outline-info btn-sm" id="dirVendorMediaUploadBtn"><i class="bi bi-upload me-1"></i>Add files</button>
                                    </div>
                                </div>
                                <div id="dirVendorMediaList" class="dir-vendor-media-grid"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="dirVendorSaveBtn">Save</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@section('scripts')
    <script>
        (function () {
            const DIR = @json($cfg);
            const IS_VENDOR_DIR = DIR.key === 'vendors';
            const table = document.getElementById('dirTable');
            const tbody = document.getElementById('dirTbody');
            const searchInput = document.getElementById('dirSearchInput');
            const noResults = document.getElementById('dirNoResults');
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const vendorShowUrlTemplate = IS_VENDOR_DIR ? @json(route('vendors.show', ['vendor' => '__VENDOR__'])) : '';
            const vendorMetaUrlTemplate = IS_VENDOR_DIR ? @json(route('vendors.meta.update', ['vendor' => '__VENDOR__'])) : '';
            const vendorMediaUploadUrlTemplate = IS_VENDOR_DIR ? @json(route('vendors.media.upload', ['vendor' => '__VENDOR__'])) : '';
            const vendorMediaDestroyUrlTemplate = IS_VENDOR_DIR ? @json(route('vendors.media.destroy', ['vendor' => '__VENDOR__', 'media' => '__MEDIA__'])) : '';
            const vendorModalEl = IS_VENDOR_DIR ? document.getElementById('dirVendorModal') : null;
            const vendorModal = vendorModalEl ? bootstrap.Modal.getOrCreateInstance(vendorModalEl) : null;
            const vendorModalTitle = document.getElementById('dirVendorModalTitle');
            const vendorNameInput = document.getElementById('dirVendorName');
            const vendorTrustedInput = document.getElementById('dirVendorTrusted');
            const vendorDescriptionInput = document.getElementById('dirVendorDescription');
            const vendorStatus = document.getElementById('dirVendorStatus');
            const vendorMediaList = document.getElementById('dirVendorMediaList');
            const vendorMediaInput = document.getElementById('dirVendorMediaInput');
            const vendorMediaUploadBtn = document.getElementById('dirVendorMediaUploadBtn');
            const vendorSaveBtn = document.getElementById('dirVendorSaveBtn');
            let currentVendorRow = null;

            if (!table || !tbody || !searchInput || !noResults) {
                return;
            }
            tbody.addEventListener('click', (event) => {
                const toggleBtn = event.target.closest('.js-dir-toggle');
                if (toggleBtn) {
                    return;
                }

                const tr = event.target.closest('tr[data-row]');
                if (!tr) {
                    return;
                }

                tbody.querySelectorAll('tr.is-active').forEach(row => row.classList.remove('is-active'));
                tr.classList.add('is-active');
            });

            async function toggleBoolean(btn) {
                const id = btn.dataset.id;
                const field = btn.dataset.field;
                if (!id || !field) {
                    return;
                }

                const oldText = btn.textContent;
                btn.disabled = true;
                btn.textContent = '...';

                try {
                    const response = await fetch(`${DIR.toggleUrl}/${id}/${field}`, {
                        method: 'PATCH',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        }
                    });

                    if (!response.ok) {
                        throw new Error('Toggle failed');
                    }

                    const data = await response.json();
                    const isYes = !!data.value;
                    btn.textContent = isYes ? 'Yes' : 'No';
                    btn.classList.remove('btn-success', 'btn-outline-secondary');
                    btn.classList.add(isYes ? 'btn-success' : 'btn-outline-secondary');
                    btn.dataset.value = isYes ? '1' : '0';

                    const tr = btn.closest('tr[data-row]');
                    if (tr) {
                        tr.dataset[field] = isYes ? '1' : '0';
                    }
                } catch (error) {
                    btn.textContent = oldText;
                    alert('Unable to update value.');
                } finally {
                    btn.disabled = false;
                }
            }

            tbody.addEventListener('click', async (event) => {
                const btn = event.target.closest('.js-dir-toggle');
                if (!btn) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();
                await toggleBoolean(btn);
            });

            function applyFilter() {
                const filter = (searchInput.value || '').trim().toLowerCase();
                let visible = 0;

                tbody.querySelectorAll('tr[data-row]').forEach(row => {
                    const text = (row.textContent || '').toLowerCase();
                    const show = text.includes(filter);
                    row.style.display = show ? '' : 'none';
                    if (show) {
                        visible++;
                    }
                });

                const total = tbody.querySelectorAll('tr[data-row]').length;
                const showNo = total > 0 && visible === 0;
                table.classList.toggle('d-none', showNo);
                noResults.classList.toggle('d-none', !showNo);
            }

            function sortBy(field, th) {
                const dir = th.dataset.direction === 'asc' ? 'desc' : 'asc';
                th.dataset.direction = dir;

                table.querySelectorAll('th.sortable').forEach(header => {
                    if (header !== th) {
                        header.dataset.direction = 'asc';
                    }
                });

                table.querySelectorAll('th.sortable i').forEach(icon => {
                    icon.className = 'bi bi-chevron-expand ms-1';
                });

                const icon = th.querySelector('i');
                if (icon) {
                    icon.className = dir === 'asc' ? 'bi bi-arrow-up ms-1' : 'bi bi-arrow-down ms-1';
                }

                const rows = Array.from(tbody.querySelectorAll('tr[data-row]'));
                rows.sort((a, b) => {
                    const av = (a.dataset[field] || '').trim();
                    const bv = (b.dataset[field] || '').trim();
                    const an = Number(av);
                    const bn = Number(bv);
                    const bothNumeric = av !== '' && bv !== '' && !Number.isNaN(an) && !Number.isNaN(bn);

                    if (bothNumeric) {
                        return dir === 'asc' ? an - bn : bn - an;
                    }

                    const result = av.localeCompare(bv, undefined, { sensitivity: 'base', numeric: true });
                    return dir === 'asc' ? result : -result;
                });

                rows.forEach(row => tbody.appendChild(row));
                applyFilter();
            }

            table.querySelectorAll('th.sortable').forEach(th => {
                th.addEventListener('click', () => {
                    const field = th.dataset.sortField || DIR.firstField;
                    sortBy(field, th);
                });
            });

            searchInput.addEventListener('input', applyFilter);

            window.dirOpenEdit = function (tr) {
                const id = tr?.dataset?.id;
                if (!id) {
                    return;
                }

                document.getElementById('dirEditId').value = id;
                document.getElementById('dirEditForm').action = DIR.baseUrl + '/' + id;

                for (const field of Object.keys(DIR.fields || {})) {
                    const el = document.getElementById('dirEdit_' + field);
                    if (!el) {
                        continue;
                    }

                    const meta = DIR.fieldsMeta && DIR.fieldsMeta[field] ? DIR.fieldsMeta[field] : {};
                    const type = meta.type || 'text';
                    const value = tr.dataset[field] ?? '';

                    if (type === 'boolean' || type === 'checkbox') {
                        el.checked = ['1', 'true', 'yes', 'on'].includes(String(value).toLowerCase());
                    } else {
                        el.value = value;
                    }
                }
            };

            window.dirOpenDelete = function (tr) {
                const id = tr?.dataset?.id;
                if (!id) {
                    return;
                }

                document.getElementById('dirDeleteId').value = id;
                document.getElementById('dirDeleteForm').action = DIR.baseUrl + '/' + id;

                const name = (tr.dataset[DIR.firstField] || '').trim();
                document.getElementById('dirDeleteTitle').innerText = 'Delete ' + (DIR.title || 'Record') + (name ? ' (' + name + ')' : '');
            };

            function buildVendorUrl(template, vendorId, mediaId) {
                return template.replace('__VENDOR__', String(vendorId)).replace('__MEDIA__', String(mediaId ?? ''));
            }

            function setVendorStatus(message, isError = false) {
                if (!vendorStatus) {
                    return;
                }

                vendorStatus.textContent = message || '';
                vendorStatus.classList.toggle('text-danger', Boolean(isError));
                vendorStatus.classList.toggle('text-muted', !isError);
            }

            function escapeHtml(value) {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function truncateText(value, limit = 140) {
                const text = String(value || '');
                return text.length <= limit ? text : text.slice(0, limit) + '...';
            }

            function renderVendorMedia(media) {
                if (!vendorMediaList) {
                    return;
                }

                if (!Array.isArray(media) || !media.length) {
                    vendorMediaList.innerHTML = '<div class="small text-muted">No files uploaded yet.</div>';
                    return;
                }

                const vendorId = currentVendorRow?.dataset?.id || '';
                vendorMediaList.innerHTML = media.map(item => {
                    const preview = item.is_image && item.thumb_url
                        ? `<img src="${escapeHtml(item.thumb_url)}" alt="${escapeHtml(item.file_name)}" class="dir-vendor-media-thumb">`
                        : `<div class="dir-vendor-media-thumb d-flex align-items-center justify-content-center bg-secondary-subtle text-dark fw-semibold">${item.mime_type === 'application/pdf' ? 'PDF' : 'FILE'}</div>`;

                    return `
                        <div class="dir-vendor-media-item">
                            <a href="${escapeHtml(item.view_url)}" class="dir-vendor-media-link" data-fancybox="dir-vendor-media-${vendorId}" data-caption="${escapeHtml(item.file_name)}">
                                ${preview}
                            </a>
                            <div class="small fw-semibold text-break mb-1">${escapeHtml(item.file_name)}</div>
                            <div class="small text-muted mb-2">${escapeHtml(item.created_at || '')}</div>
                            <div class="d-grid gap-2">
                                <a href="${escapeHtml(item.view_url)}" class="btn btn-outline-light btn-sm" data-fancybox="dir-vendor-media-${vendorId}" data-caption="${escapeHtml(item.file_name)}">Open</a>
                                <button type="button" class="btn btn-outline-danger btn-sm js-dir-vendor-media-delete" data-media-id="${item.id}">Delete</button>
                            </div>
                        </div>
                    `;
                }).join('');
            }

            function updateVendorRow(row, vendor) {
                if (!row || !vendor) {
                    return;
                }

                row.dataset.name = vendor.name || '';
                row.dataset.is_trusted = vendor.is_trusted ? '1' : '0';
                row.dataset.description = vendor.description || '';
                row.dataset.mediaCount = String(vendor.media_count ?? row.dataset.mediaCount ?? 0);

                const cells = row.querySelectorAll('td');
                const nameCell = cells[0];
                const trustedCell = cells[1];
                const descriptionCell = cells[2];
                const filesCell = cells[3];

                if (nameCell) {
                    nameCell.textContent = vendor.name || '--';
                    nameCell.title = vendor.name || '';
                }

                if (trustedCell) {
                    trustedCell.title = vendor.is_trusted ? 'Yes' : 'No';
                    trustedCell.innerHTML = `
                        <button type="button" class="btn btn-sm js-dir-toggle ${vendor.is_trusted ? 'btn-success' : 'btn-outline-secondary'}" data-id="${row.dataset.id}" data-field="is_trusted" data-value="${vendor.is_trusted ? '1' : '0'}">
                            ${vendor.is_trusted ? 'Yes' : 'No'}
                        </button>
                    `;
                }

                if (descriptionCell) {
                    descriptionCell.title = vendor.description || '';
                    descriptionCell.innerHTML = `<div class="dir-description-cell">${escapeHtml(truncateText(vendor.description || '')) || '--'}</div>`;
                }

                if (filesCell) {
                    const countEl = filesCell.querySelector('.js-dir-media-count');
                    if (countEl) {
                        countEl.textContent = String(vendor.media_count ?? 0);
                    }
                }
            }

            async function loadVendor(vendorId) {
                const response = await fetch(buildVendorUrl(vendorShowUrlTemplate, vendorId), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const data = await response.json();
                if (!response.ok || data.success === false) {
                    throw new Error('Failed to load vendor');
                }

                return data.vendor;
            }

            window.dirOpenVendorAssets = async function (tr) {
                if (!IS_VENDOR_DIR || !tr?.dataset?.id) {
                    return;
                }

                currentVendorRow = tr;
                if (vendorModalTitle) {
                    vendorModalTitle.textContent = tr.dataset.name || 'Vendor';
                }
                if (vendorNameInput) {
                    vendorNameInput.value = tr.dataset.name || '';
                }
                if (vendorTrustedInput) {
                    vendorTrustedInput.checked = ['1', 'true'].includes(String(tr.dataset.is_trusted || '').toLowerCase());
                }
                if (vendorDescriptionInput) {
                    vendorDescriptionInput.value = tr.dataset.description || '';
                }
                if (vendorMediaList) {
                    vendorMediaList.innerHTML = '<div class="small text-muted">Loading files...</div>';
                }
                setVendorStatus('Loading vendor details...');

                try {
                    const vendor = await loadVendor(tr.dataset.id);
                    if (vendorModalTitle) {
                        vendorModalTitle.textContent = vendor.name || 'Vendor';
                    }
                    if (vendorNameInput) {
                        vendorNameInput.value = vendor.name || '';
                    }
                    if (vendorTrustedInput) {
                        vendorTrustedInput.checked = Boolean(vendor.is_trusted);
                    }
                    if (vendorDescriptionInput) {
                        vendorDescriptionInput.value = vendor.description || '';
                    }
                    renderVendorMedia(vendor.media || []);
                    setVendorStatus('Vendor details loaded.');
                } catch (error) {
                    if (vendorMediaList) {
                        vendorMediaList.innerHTML = '<div class="small text-danger">Failed to load files.</div>';
                    }
                    setVendorStatus('Failed to load vendor details.', true);
                }

                vendorModal?.show();
            };

            vendorSaveBtn?.addEventListener('click', async function () {
                if (!currentVendorRow?.dataset?.id) {
                    return;
                }

                const vendorId = currentVendorRow.dataset.id;
                vendorSaveBtn.disabled = true;
                setVendorStatus('Saving vendor...');

                try {
                    const response = await fetch(buildVendorUrl(vendorMetaUrlTemplate, vendorId), {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({
                            name: vendorNameInput?.value?.trim() || '',
                            is_trusted: vendorTrustedInput?.checked ? 1 : 0,
                            description: vendorDescriptionInput?.value || '',
                        }),
                    });

                    const data = await response.json();
                    if (!response.ok || data.success === false) {
                        throw new Error('Failed to save vendor');
                    }

                    updateVendorRow(currentVendorRow, data.vendor);
                    if (vendorModalTitle) {
                        vendorModalTitle.textContent = data.vendor?.name || 'Vendor';
                    }
                    setVendorStatus('Vendor saved.');
                } catch (error) {
                    setVendorStatus('Failed to save vendor.', true);
                } finally {
                    vendorSaveBtn.disabled = false;
                }
            });

            vendorMediaUploadBtn?.addEventListener('click', function () {
                if (!currentVendorRow?.dataset?.id) {
                    return;
                }

                vendorMediaInput?.click();
            });

            vendorMediaInput?.addEventListener('change', async function () {
                if (!currentVendorRow?.dataset?.id || !vendorMediaInput.files?.length) {
                    return;
                }

                const vendorId = currentVendorRow.dataset.id;
                const formData = new FormData();
                Array.from(vendorMediaInput.files).forEach(file => {
                    formData.append('files[]', file);
                });
                formData.append('_token', csrf);

                vendorMediaUploadBtn.disabled = true;
                setVendorStatus('Uploading files...');

                try {
                    const response = await fetch(buildVendorUrl(vendorMediaUploadUrlTemplate, vendorId), {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: formData,
                    });

                    const data = await response.json();
                    if (!response.ok || data.success === false) {
                        throw new Error('Failed to upload files');
                    }

                    const mediaCount = Array.isArray(data.media) ? data.media.length : 0;
                    renderVendorMedia(data.media || []);
                    currentVendorRow.dataset.mediaCount = String(mediaCount);
                    currentVendorRow.querySelector('.js-dir-media-count')?.replaceChildren(document.createTextNode(String(mediaCount)));
                    setVendorStatus('Files uploaded.');
                } catch (error) {
                    setVendorStatus('Failed to upload files.', true);
                } finally {
                    vendorMediaInput.value = '';
                    vendorMediaUploadBtn.disabled = false;
                }
            });

            vendorMediaList?.addEventListener('click', async function (event) {
                const deleteBtn = event.target.closest('.js-dir-vendor-media-delete');
                if (!deleteBtn || !currentVendorRow?.dataset?.id) {
                    return;
                }

                const vendorId = currentVendorRow.dataset.id;
                deleteBtn.disabled = true;
                setVendorStatus('Deleting file...');

                try {
                    const response = await fetch(buildVendorUrl(vendorMediaDestroyUrlTemplate, vendorId, deleteBtn.dataset.mediaId), {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    const data = await response.json();
                    if (!response.ok || data.success === false) {
                        throw new Error('Failed to delete file');
                    }

                    const mediaCount = Array.isArray(data.media) ? data.media.length : 0;
                    renderVendorMedia(data.media || []);
                    currentVendorRow.dataset.mediaCount = String(mediaCount);
                    currentVendorRow.querySelector('.js-dir-media-count')?.replaceChildren(document.createTextNode(String(mediaCount)));
                    setVendorStatus('File deleted.');
                } catch (error) {
                    deleteBtn.disabled = false;
                    setVendorStatus('Failed to delete file.', true);
                }
            });

            applyFilter();
        })();
    </script>
@endsection
