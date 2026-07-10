@extends('admin.master')

@section('content')
    <style>
        .table-wrapper {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            position: relative;
        }

        .table th, .table td {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            min-width: 80px;
            max-width: 190px;
            padding-left: 10px;
        }

        .table th:nth-child(1), .table td:nth-child(1) {
            min-width: 80px;
            max-width: 90px;
        }

        .table th:nth-child(2), .table td:nth-child(2) {
            min-width: 50px;
            max-width: 250px;
        }

        .table th:nth-child(3), .table td:nth-child(3) {
            min-width: 50px;
            max-width: 250px;
        }

        .table th:nth-child(6), .table td:nth-child(6) {
            min-width: 50px;
            max-width: 70px;
        }

        .table thead th {
            position: sticky;
            height: 32px;
            top: -1px;
            vertical-align: middle;
            border-top: 1px;
            z-index: 1020;
        }

        @media (max-width: 1200px) {
            .table th:nth-child(5), .table td:nth-child(5),
            .table th:nth-child(2), .table td:nth-child(2),
            .table th:nth-child(3), .table td:nth-child(3) {
                display: none;
            }
        }

        .table th.sortable {
            cursor: pointer;
        }

        .manual-soft-deleted-row > td,
        .manual-soft-deleted-row > th {
            background: rgba(220, 53, 69, 0.16) !important;
            color: var(--bs-secondary-color);
        }

        .manual-soft-deleted-row .manual-number {
            text-decoration: line-through;
            text-decoration-thickness: 1px;
        }

        .clearable-input {
            position: relative;
            width: 400px;
        }

        .clearable-input .form-control {
            padding-right: 2.5rem;
        }

        .clearable-input .btn-clear {
            position: absolute;
            right: 0.5rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            line-height: 1;
        }

        .clearable-input .form-control {
            padding-right: 2rem;
        }

        .js-table-hidden {
            visibility: hidden;
        }

        .dir-header .dir-title {
            margin: 0;
            line-height: 1.1;
        }

        .dir-header .form-control-sm {
            height: 32px;
        }

        .cmm-thumb-link {
            display: inline-block;
        }

        .cmm-thumb-wrap {
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            overflow: hidden;
            flex: 0 0 40px;
        }

        .cmm-thumb-img {
            width: 40px !important;
            height: 40px !important;
            min-width: 40px !important;
            min-height: 40px !important;
            max-width: 40px !important;
            max-height: 40px !important;
            object-fit: cover !important;
            border-radius: 50% !important;
            display: block;
        }

        .table-loading-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.78);
            z-index: 30;
            transition: opacity .15s ease;
        }

        html[data-bs-theme="dark"] .table-loading-overlay {
            background: rgba(20, 20, 20, 0.68);
        }

        .table-loading-overlay.d-none {
            display: none !important;
        }

        #createCmmOffcanvas,
        #editCmmOffcanvas {
            --bs-offcanvas-width: min(980px, 100vw);
            top: .75rem;
            bottom: 4vh;
            height: auto;
            max-height: calc(100vh - .75rem - 4vh);
            display: flex;
            flex-direction: column;
        }

        #editCmmOffcanvas {
            --bs-offcanvas-width: min(1240px, 100vw);
        }

        #createCmmOffcanvas .offcanvas-body,
        #editCmmOffcanvas .offcanvas-body {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
        }

        #createCmmOffcanvas form,
        #editCmmOffcanvas form {
            min-height: 100%;
            display: flex;
            flex-direction: column;
        }

        .cmm-drawer-main {
            flex: 1 1 auto;
            min-height: 0;
        }

        .cmm-drawer-right {
            border-left: 1px solid rgba(255, 255, 255, .08);
        }

        .cmm-drawer-units,
        .cmm-drawer-users {
            max-height: 42vh;
            overflow-y: auto;
            min-height: 0;
        }

        .cmm-form-footer {
            margin-top: auto;
            border-top: 0;
            background: transparent;
        }

        .cmm-form-footer > .d-flex {
            align-items: center;
        }

        body:has(#createCmmOffcanvas.show) #aiAssistantWidget,
        body:has(#editCmmOffcanvas.show) #aiAssistantWidget {
            display: none !important;
        }

        @media (max-width: 991.98px) {
            .cmm-drawer-right {
                border-left: 0;
                border-top: 1px solid rgba(255, 255, 255, .08);
                padding-top: 1rem;
            }
        }
    </style>

    <div class="card dir-page">
        <div class="card-header dir-header shadow-sm">
            <div class="d-flex w-100 align-items-center justify-content-between gap-2 flex-wrap">

                <h5 class="text-primary dir-title">
                    {{ __('Manage CMMs') }} (
                    <span class="text-success">{{ $cmms->count() }}</span>
                    )
                </h5>

                <div class="clearable-input">
                    <input id="searchInput"
                           type="text"
                           class="form-control form-control-sm w-100"
                           placeholder="Search...">
                    <button class="btn-clear text-secondary"
                            id="clearSearchBtn"
                            type="button"
                            title="Clear">
                        <i class="bi bi-x-circle"></i>
                    </button>
                </div>

                <button type="button"
                        class="btn btn-outline-info btn-sm me-5"
                        data-bs-toggle="offcanvas"
                        data-bs-target="#createCmmOffcanvas"
                        aria-controls="createCmmOffcanvas">
                    {{ __('Add CMM') }}
                </button>
            </div>
        </div>

        @if(count($cmms))
            <div class="table-wrapper me-3 p-0" id="tableWrapper">

                <div id="tableLoading" class="table-loading-overlay">
                    <div class="text-center">
                        <div class="spinner-border text-warning-emphasis" role="status"></div>
                    </div>
                </div>

                <table id="cmmTable" class="table table-sm table-hover align-middle table-bordered js-table-hidden dir-table">
                    <thead class="bg-gradient">
                    <tr>
                        <th class="text-primary sortable bg-gradient" data-direction="asc">
                            {{ __('Number') }}<i class="bi bi-chevron-expand ms-1"></i>
                        </th>
                        <th class="text-primary sortable bg-gradient">
                            {{ __('Title') }}<i class="bi bi-chevron-expand ms-1"></i>
                        </th>
                        <th class="text-primary sortable bg-gradient">
                            {{ __('Components PN') }}<i class="bi bi-chevron-expand ms-1"></i>
                        </th>
                        <th class="text-primary text-center bg-gradient">{{ __('Image') }}</th>
                        <th class="text-primary text-center bg-gradient">{{ __('Rev.Date') }}</th>
                        <th class="text-primary text-center sortable bg-gradient" data-direction="asc">
                            {{ __('Lib') }} <i class="bi bi-chevron-expand ms-1"></i>
                        </th>
                        <th class="text-primary text-center bg-gradient">
                            @role('Admin')
                                <label class="d-inline-flex align-items-center justify-content-center gap-1 mb-1 small text-nowrap"
                                       title="{{ __('Show all, including soft-deleted manuals') }}">
                                    <input type="checkbox"
                                           class="form-check-input m-0"
                                           id="showDeletedManualsCheckbox"
                                           @checked($showDeleted ?? false)>
                                    <span>{{ __('All') }}</span>
                                </label>
                                <br>
                            @endrole
                            {{ __('Action') }}
                        </th>
                    </tr>
                    </thead>

                    <tbody>
                    @foreach($cmms as $cmm)
                        @php
                            $isDeletedManual = method_exists($cmm, 'trashed') && $cmm->trashed();
                        @endphp
                        <tr @class(['manual-soft-deleted-row' => $isDeletedManual])>
                            <td>
                                @if($isDeletedManual)
                                    <span class="manual-number">{{ $cmm->number }}</span>
                                    <span class="badge text-bg-danger ms-1"
                                          title="{{ __('Deleted at') }} {{ format_project_date($cmm->deleted_at) }}">
                                        {{ __('Soft deleted') }}
                                    </span>
                                @else
                                    <a href="{{ route('manuals.show', ['manual' => $cmm->id]) }}">
                                        {{ $cmm->number }}
                                    </a>
                                @endif
                            </td>

                            <td title="{{ $cmm->title }}">{{ $cmm->title }}</td>
                            <td title="{{ $cmm->unit_name }}">{{ $cmm->unit_name }}</td>

                            <td class="text-center">
                                @php
                                    $manualThumb = $cmm->getFirstMediaThumbnailUrl('manuals') ?: asset('img/noimage.png');
                                    $manualBig = $cmm->getFirstMediaBigUrl('manuals') ?: $manualThumb;
                                @endphp

                                <a href="{{ $manualBig }}" data-fancybox="gallery" class="cmm-thumb-link">
                                    <span class="cmm-thumb-wrap">
                                        <img
                                            src="{{ $manualThumb }}"
                                            onerror="this.onerror=null;this.src='{{ asset('img/noimage.png') }}';if(this.closest('a')){this.closest('a').setAttribute('href','{{ asset('img/noimage.png') }}');}"
                                            class="cmm-thumb-img"
                                            alt="Image"
                                        >
                                    </span>
                                </a>
                            </td>

                            <td class="text-center">{{ format_project_date($cmm->revision_date) ?? '-' }}</td>
                            <td class="text-center">{{ $cmm->lib }}</td>

                            <td class="text-center">
                                @if($isDeletedManual)
                                    <span class="badge bg-secondary me-1"
                                          title="{{ __('Deleted at') }} {{ format_project_date($cmm->deleted_at) }}">
                                        {{ __('Deleted') }} {{ format_project_date($cmm->deleted_at) }}
                                    </span>
                                @else
                                    <button type="button"
                                       class="btn btn-outline-primary btn-sm open-edit-cmm-drawer"
                                       data-manual-url="{{ route('manuals.edit', ['manual' => $cmm->id]) }}"
                                       data-update-url="{{ route('manuals.update', ['manual' => $cmm->id]) }}">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                @endif

                                @role('Admin')
                                @unless($isDeletedManual)
                                    <form id="deleteForm_{{ $cmm->id }}"
                                          action="{{ route('manuals.destroy', ['manual' => $cmm->id]) }}"
                                          method="POST"
                                          style="display:inline;">
                                        @csrf
                                        @method('DELETE')

                                        <button class="btn btn-sm btn-outline-danger"
                                                type="button"
                                                name="btn_delete"
                                                data-bs-toggle="modal"
                                                data-bs-target="#useConfirmDelete"
                                                data-title="Delete Confirmation row {{ $cmm->number }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endunless

                                @if(auth()->user()?->isSystemAdmin())
                                    <form id="forceDeleteForm_{{ $cmm->id }}"
                                          action="{{ route('manuals.force-destroy', ['manual' => $cmm->id]) }}"
                                          method="POST"
                                          style="display:inline;">
                                        @csrf
                                        @method('DELETE')

                                        <button class="btn btn-sm btn-danger"
                                                type="button"
                                                name="btn_force_delete"
                                                title="{{ __('Permanently delete') }}"
                                                data-bs-toggle="modal"
                                                data-bs-target="#useConfirmDelete"
                                                data-title="Permanent delete {{ $cmm->number }}">
                                            <i class="bi bi-x-octagon"></i>
                                        </button>
                                    </form>
                                @endif
                                @endrole
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-3">
                <p class="mb-0">Manuals not created</p>
            </div>
        @endif
    </div>

    <div class="offcanvas offcanvas-end text-bg-dark" tabindex="-1" id="createCmmOffcanvas" aria-labelledby="createCmmOffcanvasLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title text-info" id="createCmmOffcanvasLabel">{{ __('Create new CMM') }}</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="{{ __('Close') }}"></button>
        </div>
        <div class="offcanvas-body">
            <form method="POST" action="{{ route('manuals.store') }}" enctype="multipart/form-data" id="createCmmDrawerForm" novalidate>
                @csrf
                <div id="createCmmErrors" class="alert alert-danger d-none"></div>
                <div class="row g-3 cmm-drawer-main">
                    <div class="col-12 col-lg-8">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="create_cmm_number">{{ __('CMM No:') }}</label>
                                <input id="create_cmm_number" type="text" class="form-control" name="number" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="create_cmm_lib">{{ __('Library No:') }}</label>
                                <input id="create_cmm_lib" type="text" class="form-control" name="lib" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="create_cmm_title">{{ __('Description') }}</label>
                                <input id="create_cmm_title" type="text" class="form-control cmm-title-input" name="title" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="create_cmm_unit_name">{{ __('Component Part No:') }}</label>
                                <input id="create_cmm_unit_name" type="text" class="form-control" name="unit_name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="create_cmm_planes_id">{{ __('AirCraft Type') }}</label>
                                <select id="create_cmm_planes_id" name="planes[]" class="form-select" multiple size="4" required
                                        title="{{ __('Ctrl+click — select several') }}">
                                    @foreach ($planes as $plane)
                                        <option value="{{ $plane->id }}">{{ $plane->type }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="create_cmm_unit_name_training">{{ __('Component Training Part No:') }}</label>
                                <input id="create_cmm_unit_name_training" type="text" class="form-control" name="unit_name_training">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="create_cmm_builders_id">{{ __('MFR') }}</label>
                                <select id="create_cmm_builders_id" name="builders_id" class="form-select" required>
                                    <option value="">{{ __('Select MFR') }}</option>
                                    @foreach ($builders as $builder)
                                        <option value="{{ $builder->id }}">{{ $builder->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="create_cmm_training_hours">{{ __('Component First Training (hh)') }}</label>
                                <input id="create_cmm_training_hours" type="text" class="form-control" name="training_hours">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="create_cmm_scopes_id">{{ __('Scope') }}</label>
                                <select id="create_cmm_scopes_id" name="scopes_id" class="form-select" required>
                                    <option value="">{{ __('Select Scope') }}</option>
                                    @foreach ($scopes as $scope)
                                        <option value="{{ $scope->id }}">{{ $scope->scope }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="create_cmm_ovh_life">{{ __('Overhaul Life') }}</label>
                                <input id="create_cmm_ovh_life" type="text" class="form-control" name="ovh_life">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="create_cmm_reg_sb">{{ __('Inspection Req.SB') }}</label>
                                <input id="create_cmm_reg_sb" type="text" class="form-control" name="reg_sb">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="create_cmm_revision_date">{{ __('Revision Date') }}</label>
                                <input id="create_cmm_revision_date" type="date" class="form-control" name="revision_date" required>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-4 cmm-drawer-right">
                        <div class="mb-3">
                            <label class="form-label">{{ __('Image component:') }}</label>
                            <input type="file" name="img" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Log Card Image:') }}</label>
                            <input type="file" name="log_img" class="form-control">
                        </div>
                        <hr class="opacity-25">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="text-uppercase small text-secondary">{{ __('Components') }}</div>
                            <button class="btn btn-outline-primary btn-sm add-cmm-unit" type="button" data-target="#createCmmUnitInputs">{{ __('Add') }}</button>
                        </div>
                        <div id="createCmmUnitInputs" class="cmm-drawer-units">
                            <div class="input-group mb-2 unit-field">
                                <input type="text" class="form-control" placeholder="Enter Unit PN" name="units[]">
                                <input type="text" class="form-control unit-name-input" placeholder="Enter Unit Name" name="unit_names[]">
                                <button class="btn btn-outline-danger removeUnitField" type="button" aria-label="{{ __('Remove') }}">&times;</button>
                            </div>
                        </div>
                        <small class="text-muted">{{ __('Add component PN / Name pairs.') }}</small>
                    </div>
                </div>
                <div class="cmm-form-footer mt-3 pt-2">
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="offcanvas">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary" id="createCmmSubmitBtn">{{ __('Add CMM') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="offcanvas offcanvas-end text-bg-dark" tabindex="-1" id="editCmmOffcanvas" aria-labelledby="editCmmOffcanvasLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title text-info" id="editCmmOffcanvasLabel">{{ __('Edit CMM') }}</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="{{ __('Close') }}"></button>
        </div>
        <div class="offcanvas-body">
            <form method="POST" enctype="multipart/form-data" id="editCmmDrawerForm" novalidate>
                @csrf
                @method('PUT')
                <div id="editCmmErrors" class="alert alert-danger d-none"></div>
                <div class="row g-3 cmm-drawer-main">
                    <div class="col-12 col-xl-6">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="edit_cmm_number">{{ __('CMM No:') }}</label>
                                <input id="edit_cmm_number" type="text" class="form-control" name="number" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="edit_cmm_lib">{{ __('Library No:') }}</label>
                                <input id="edit_cmm_lib" type="text" class="form-control" name="lib" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="edit_cmm_title">{{ __('Description') }}</label>
                                <input id="edit_cmm_title" type="text" class="form-control cmm-title-input" name="title" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="edit_cmm_unit_name">{{ __('Component Part No:') }}</label>
                                <input id="edit_cmm_unit_name" type="text" class="form-control" name="unit_name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="edit_cmm_planes_id">{{ __('AirCraft Type') }}</label>
                                <select id="edit_cmm_planes_id" name="planes[]" class="form-select" multiple size="4" required
                                        title="{{ __('Ctrl+click — select several') }}">
                                    @foreach ($planes as $plane)
                                        <option value="{{ $plane->id }}">{{ $plane->type }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="edit_cmm_unit_name_training">{{ __('Component Training Part No:') }}</label>
                                <input id="edit_cmm_unit_name_training" type="text" class="form-control" name="unit_name_training">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="edit_cmm_builders_id">{{ __('MFR') }}</label>
                                <select id="edit_cmm_builders_id" name="builders_id" class="form-select" required>
                                    <option value="">{{ __('Select MFR') }}</option>
                                    @foreach ($builders as $builder)
                                        <option value="{{ $builder->id }}">{{ $builder->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="edit_cmm_training_hours">{{ __('Component First Training (hh)') }}</label>
                                <input id="edit_cmm_training_hours" type="text" class="form-control" name="training_hours">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="edit_cmm_scopes_id">{{ __('Scope') }}</label>
                                <select id="edit_cmm_scopes_id" name="scopes_id" class="form-select" required>
                                    <option value="">{{ __('Select Scope') }}</option>
                                    @foreach ($scopes as $scope)
                                        <option value="{{ $scope->id }}">{{ $scope->scope }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="edit_cmm_ovh_life">{{ __('Overhaul Life') }}</label>
                                <input id="edit_cmm_ovh_life" type="text" class="form-control" name="ovh_life">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="edit_cmm_reg_sb">{{ __('Inspection Req.SB') }}</label>
                                <input id="edit_cmm_reg_sb" type="text" class="form-control" name="reg_sb">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="edit_cmm_revision_date">{{ __('Revision Date') }}</label>
                                <input id="edit_cmm_revision_date" type="date" class="form-control" name="revision_date" required>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-xl-3 cmm-drawer-right">
                        <div class="mb-3">
                            <label class="form-label">{{ __('Image component:') }}</label>
                            <input type="file" name="img" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Log Card Image:') }}</label>
                            <input type="file" name="log_img" class="form-control">
                        </div>
                        <hr class="opacity-25">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="text-uppercase small text-secondary">{{ __('Components') }}</div>
                            <button class="btn btn-outline-primary btn-sm add-cmm-unit" type="button" data-target="#editCmmUnitInputs">{{ __('Add') }}</button>
                        </div>
                        <div id="editCmmUnitInputs" class="cmm-drawer-units"></div>
                        <small class="text-muted">{{ __('Add component PN / Name pairs.') }}</small>
                    </div>
                    @if(auth()->user()?->roleIs('Admin'))
                        <div class="col-12 col-xl-3 cmm-drawer-right">
                            <div class="text-uppercase small text-secondary mb-2">{{ __('Users with access') }}</div>
                            <div class="cmm-drawer-users small" id="editCmmPermittedUsers">
                                @foreach(($users ?? collect()) as $u)
                                    <label class="d-flex align-items-center gap-2 mb-2" style="white-space: nowrap; cursor: pointer;">
                                        <input type="checkbox" name="permitted_user_ids[]" value="{{ $u->id }}">
                                        <span class="text-truncate">{{ $u->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <small class="text-muted d-block mt-2">{{ __('By default, users have no permissions. Admin selects allowed users here.') }}</small>
                        </div>
                    @endif
                </div>
                <div class="cmm-form-footer mt-3 pt-2">
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="offcanvas">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary" id="editCmmSubmitBtn">{{ __('Update') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @include('components.delete')
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const table = document.getElementById('cmmTable');
            const tbody = table ? table.querySelector('tbody') : null;
            const searchInput = document.getElementById('searchInput');
            const clearBtn = document.getElementById('clearSearchBtn') ||
                document.querySelector('.clearable-input .btn-clear');
            const loading = document.getElementById('tableLoading');
            const showDeletedCheckbox = document.getElementById('showDeletedManualsCheckbox');

            if (showDeletedCheckbox) {
                showDeletedCheckbox.addEventListener('change', function () {
                    const url = new URL(window.location.href);
                    if (this.checked) {
                        url.searchParams.set('with_deleted', '1');
                    } else {
                        url.searchParams.delete('with_deleted');
                    }
                    window.location.href = url.toString();
                });
            }

            const STORAGE_KEY = 'cmm_search';
            const rows = tbody ? Array.from(tbody.querySelectorAll('tr')) : [];
            const headers = document.querySelectorAll('.sortable');

            let inputTimer = null;

            function showGlobalSpinner() {
                if (typeof showLoadingSpinner === 'function') showLoadingSpinner();
            }

            function hideGlobalSpinner() {
                if (typeof hideLoadingSpinner === 'function') hideLoadingSpinner();
            }

            function showTableLoader() {
                if (loading) loading.classList.remove('d-none');
                if (table) table.style.visibility = 'hidden';
            }

            function hideTableLoader() {
                if (loading) loading.classList.add('d-none');
                if (table) table.style.visibility = 'visible';
            }

            function cacheRowSearchText() {
                rows.forEach(row => {
                    row.dataset.searchText = (row.textContent || '').toLowerCase();
                });
            }

            function applySearch(raw) {
                const filter = (raw || '').trim().toLowerCase();

                rows.forEach(row => {
                    const text = row.dataset.searchText || '';
                    row.style.display = !filter || text.includes(filter) ? '' : 'none';
                });
            }

            function saveSearchValue(value) {
                const v = (value || '').trim();
                if (v) window.UserScopedStorage.setItem(STORAGE_KEY, v);
                else window.UserScopedStorage.removeItem(STORAGE_KEY);
            }

            function getActualSearchValue() {
                const fromStorage = (window.UserScopedStorage.getItem(STORAGE_KEY) || '').trim();
                const fromInput = (searchInput?.value || '').trim();
                return fromStorage || fromInput;
            }

            function restoreAndApplyAsync() {
                showTableLoader();

                requestAnimationFrame(() => {
                    requestAnimationFrame(() => {
                        const value = getActualSearchValue();

                        if (searchInput) {
                            searchInput.value = value;
                        }

                        saveSearchValue(value);
                        applySearch(value);
                        hideTableLoader();
                    });
                });
            }

            function sortTableByColumn(header) {
                showTableLoader();

                requestAnimationFrame(() => {
                    const columnIndex = Array.from(header.parentNode.children).indexOf(header);
                    const direction = header.dataset.direction === 'asc' ? 'desc' : 'asc';
                    header.dataset.direction = direction;

                    const sortedRows = rows.slice().sort((a, b) => {
                        const aText = (a.cells[columnIndex]?.innerText || '').trim();
                        const bText = (b.cells[columnIndex]?.innerText || '').trim();

                        return direction === 'asc'
                            ? aText.localeCompare(bText, undefined, { numeric: true, sensitivity: 'base' })
                            : bText.localeCompare(aText, undefined, { numeric: true, sensitivity: 'base' });
                    });

                    sortedRows.forEach(row => tbody.appendChild(row));
                    hideTableLoader();
                });
            }

            cacheRowSearchText();
            restoreAndApplyAsync();

            window.addEventListener('pageshow', function (event) {
                if (event.persisted) {
                    restoreAndApplyAsync();
                }
            });

            headers.forEach(header => {
                header.addEventListener('click', () => {
                    sortTableByColumn(header);
                });
            });

            if (searchInput) {
                searchInput.addEventListener('input', () => {
                    const value = searchInput.value.trim();
                    saveSearchValue(value);

                    clearTimeout(inputTimer);
                    showTableLoader();

                    inputTimer = setTimeout(() => {
                        applySearch(value);
                        hideTableLoader();
                    }, 50);
                });
            }

            if (clearBtn) {
                clearBtn.addEventListener('click', (e) => {
                    e.preventDefault();

                    if (searchInput) {
                        searchInput.value = '';
                    }

                    saveSearchValue('');
                    showTableLoader();

                    requestAnimationFrame(() => {
                        applySearch('');
                        hideTableLoader();
                    });
                });
            }

            function cmmCsrfToken() {
                return document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
            }

            function setDrawerErrors(box, data, fallback) {
                if (!box) return;
                const errors = data?.errors
                    ? Object.values(data.errors).flat()
                    : (data?.message ? [data.message] : []);
                const messages = errors.length ? errors : (fallback ? [fallback] : []);
                box.classList.toggle('d-none', messages.length === 0);
                box.innerHTML = messages.map(message => `<div>${message}</div>`).join('');
            }

            function setSubmitting(button, text, busy) {
                if (!button) return;
                if (busy) {
                    button.dataset.originalText = button.innerHTML;
                    button.disabled = true;
                    button.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span>${text}`;
                } else {
                    button.disabled = false;
                    if (button.dataset.originalText) button.innerHTML = button.dataset.originalText;
                }
            }

            function escapeAttr(value) {
                return String(value || '')
                    .replace(/&/g, '&amp;')
                    .replace(/"/g, '&quot;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;');
            }

            function unitRowHtml(partNumber = '', name = '') {
                return `
                    <div class="input-group mb-2 unit-field">
                        <input type="text" class="form-control" placeholder="Enter Unit PN" name="units[]" value="${escapeAttr(partNumber)}">
                        <input type="text" class="form-control unit-name-input" placeholder="Enter Unit Name" name="unit_names[]" value="${escapeAttr(name)}" data-user-edited="${name ? '1' : '0'}">
                        <button class="btn btn-outline-danger removeUnitField" type="button" aria-label="{{ __('Remove') }}">&times;</button>
                    </div>
                `;
            }

            function syncDrawerUnitNames(form) {
                const title = (form.querySelector('.cmm-title-input')?.value || '').trim();
                form.querySelectorAll('.unit-name-input').forEach(input => {
                    if (input.dataset.userEdited !== '1') input.value = title;
                });
            }

            document.querySelectorAll('.add-cmm-unit').forEach(button => {
                button.addEventListener('click', function () {
                    const target = document.querySelector(this.dataset.target);
                    if (!target) return;
                    target.insertAdjacentHTML('beforeend', unitRowHtml());
                    syncDrawerUnitNames(this.closest('form'));
                });
            });

            document.addEventListener('click', function (event) {
                if (event.target.classList.contains('removeUnitField')) {
                    event.preventDefault();
                    event.target.closest('.unit-field')?.remove();
                }
            });

            document.querySelectorAll('.cmm-title-input').forEach(input => {
                input.addEventListener('input', function () {
                    syncDrawerUnitNames(this.closest('form'));
                });
            });

            document.addEventListener('input', function (event) {
                if (event.target.classList.contains('unit-name-input')) {
                    event.target.dataset.userEdited = '1';
                }
            });

            async function submitCmmDrawer(form, submitBtn, errorsBox, successMessage) {
                form.querySelectorAll('.unit-field').forEach(field => {
                    const partNumberInput = field.querySelector('input[name="units[]"]');
                    if (partNumberInput && partNumberInput.value.trim() === '') field.remove();
                });

                setDrawerErrors(errorsBox, {}, '');
                setSubmitting(submitBtn, '{{ __('Saving...') }}', true);

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        headers: {
                            'X-CSRF-TOKEN': cmmCsrfToken(),
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        credentials: 'same-origin',
                    });
                    const data = await response.json().catch(() => ({}));

                    if (!response.ok || !data.success) {
                        setDrawerErrors(errorsBox, data, '{{ __('Failed to submit.') }}');
                        return;
                    }

                    bootstrap.Offcanvas.getOrCreateInstance(form.closest('.offcanvas')).hide();
                    if (typeof showNotification === 'function') showNotification(data.message || successMessage, 'success');
                    window.location.reload();
                } catch (error) {
                    setDrawerErrors(errorsBox, {}, '{{ __('Failed to submit.') }}');
                } finally {
                    setSubmitting(submitBtn, '{{ __('Saving...') }}', false);
                    hideGlobalSpinner();
                }
            }

            const createForm = document.getElementById('createCmmDrawerForm');
            if (createForm && !createForm.dataset.bound) {
                createForm.dataset.bound = '1';
                createForm.addEventListener('submit', function (event) {
                    event.preventDefault();
                    submitCmmDrawer(
                        createForm,
                        document.getElementById('createCmmSubmitBtn'),
                        document.getElementById('createCmmErrors'),
                        '{{ __('CMM created successfully') }}'
                    );
                });
            }

            const editOffcanvas = document.getElementById('editCmmOffcanvas');
            const editForm = document.getElementById('editCmmDrawerForm');
            const editErrors = document.getElementById('editCmmErrors');
            const editSubmit = document.getElementById('editCmmSubmitBtn');
            const editUnitInputs = document.getElementById('editCmmUnitInputs');
            const editUsers = document.getElementById('editCmmPermittedUsers');

            function setEditValue(name, value) {
                const field = editForm?.querySelector(`[name="${name}"]`);
                if (field) field.value = value || '';
            }

            function fillEditDrawer(manual) {
                setEditValue('number', manual.number);
                setEditValue('lib', manual.lib);
                setEditValue('title', manual.title);
                setEditValue('unit_name', manual.unit_name);
                // multi-select: tick every plane of the CMM (plane_ids), not just the primary
                (function () {
                    const sel = document.getElementById('edit_cmm_planes_id');
                    if (!sel) return;
                    const ids = (manual.plane_ids && manual.plane_ids.length ? manual.plane_ids : [manual.planes_id])
                        .filter(v => v != null).map(String);
                    [...sel.options].forEach(o => { o.selected = ids.includes(o.value); });
                })();
                setEditValue('unit_name_training', manual.unit_name_training);
                setEditValue('builders_id', manual.builders_id);
                setEditValue('training_hours', manual.training_hours);
                setEditValue('scopes_id', manual.scopes_id);
                setEditValue('ovh_life', manual.ovh_life);
                setEditValue('reg_sb', manual.reg_sb);
                setEditValue('revision_date', manual.revision_date);

                if (editUnitInputs) {
                    const units = Array.isArray(manual.units) && manual.units.length ? manual.units : [{ part_number: '', name: manual.title || '' }];
                    editUnitInputs.innerHTML = units.map(unit => unitRowHtml(unit.part_number, unit.name)).join('');
                }

                if (editUsers) {
                    const selected = (manual.permitted_user_ids || []).map(id => String(id));
                    editUsers.querySelectorAll('input[type="checkbox"]').forEach(input => {
                        input.checked = selected.includes(String(input.value));
                    });
                }
            }

            document.addEventListener('click', async function (event) {
                const button = event.target.closest('.open-edit-cmm-drawer');
                if (!button || !editOffcanvas || !editForm) return;
                event.preventDefault();

                editForm.action = button.dataset.updateUrl || '';
                setDrawerErrors(editErrors, {}, '');
                setSubmitting(editSubmit, '{{ __('Loading...') }}', true);
                bootstrap.Offcanvas.getOrCreateInstance(editOffcanvas).show();

                try {
                    const response = await fetch(button.dataset.manualUrl, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        credentials: 'same-origin',
                    });
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok || !data.success) {
                        setDrawerErrors(editErrors, data, '{{ __('Failed to load CMM.') }}');
                        return;
                    }
                    fillEditDrawer(data.manual || {});
                } catch (error) {
                    setDrawerErrors(editErrors, {}, '{{ __('Failed to load CMM.') }}');
                } finally {
                    setSubmitting(editSubmit, '{{ __('Loading...') }}', false);
                    hideGlobalSpinner();
                }
            });

            if (editForm && !editForm.dataset.bound) {
                editForm.dataset.bound = '1';
                editForm.addEventListener('submit', function (event) {
                    event.preventDefault();
                    submitCmmDrawer(
                        editForm,
                        editSubmit,
                        editErrors,
                        '{{ __('Manual updated successfully') }}'
                    );
                });
            }

            const modal = document.getElementById('useConfirmDelete');
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
            let deleteForm = null;

            if (modal && confirmDeleteBtn) {
                modal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    deleteForm = button ? button.closest('form') : null;

                    const title = button ? button.getAttribute('data-title') : null;
                    const modalTitle = modal.querySelector('#confirmDeleteLabel');

                    if (modalTitle) {
                        modalTitle.textContent = title || 'Delete Confirmation';
                    }
                });

                confirmDeleteBtn.addEventListener('click', function () {
                    showGlobalSpinner();
                    if (deleteForm) deleteForm.submit();
                });
            }
        });
    </script>
@endsection
