@extends('admin.master')

@section('content')
    <style>
        .library-business-types-page {
            flex: 1 1 auto;
            height: 100%;
            min-height: 0;
            display: flex;
            flex-direction: column;
        }

        .library-business-types-page .card {
            flex: 1 1 auto;
            min-height: 0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border-radius: 6px;
        }

        .library-business-types-page .card-body {
            flex: 1 1 auto;
            min-height: 0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .library-business-types-page .table-responsive {
            flex: 1 1 0;
            min-height: 0;
            overflow: auto;
        }

        .library-business-types-page thead th {
            position: sticky;
            top: 0;
            z-index: 2;
        }

        .business-type-actions {
            width: 92px;
        }
    </style>

    <div class="container-fluid library-business-types-page">
        <div class="card shadow">
            <div class="card-header">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <h5 class="mb-0 text-primary">
                        {{ __('Type of Business') }}
                        <span class="text-success">({{ $companyTypes->total() }})</span>
                    </h5>

                    <button type="button"
                            class="btn btn-outline-primary btn-sm"
                            data-bs-toggle="modal"
                            data-bs-target="#createBusinessTypeModal">
                        <i class="bi bi-plus-lg me-1"></i>{{ __('Add Type') }}
                    </button>
                </div>
            </div>

            <div class="card-body">
                <form method="GET" action="{{ route('library.type-of-business.index') }}" class="row g-2 align-items-end mb-3">
                    <div class="col-12 col-md-8 col-xl-6">
                        <label for="businessTypeSearch" class="form-label small text-muted mb-1">{{ __('Search') }}</label>
                        <input id="businessTypeSearch"
                               type="search"
                               class="form-control form-control-sm"
                               name="q"
                               value="{{ $q }}"
                               placeholder="Type of Business"
                               autocomplete="off"
                               autocorrect="off"
                               spellcheck="false">
                    </div>
                    <div class="col-12 col-md-auto">
                        <button class="btn btn-outline-primary btn-sm" type="submit">{{ __('Search') }}</button>
                        @if($q !== '')
                            <a class="btn btn-outline-secondary btn-sm" href="{{ route('library.type-of-business.index') }}">{{ __('Clear') }}</a>
                        @endif
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-hover align-middle mb-0">
                        <thead class="bg-gradient">
                        <tr>
                            <th class="text-primary">ID</th>
                            <th class="text-primary">{{ __('Type of Business') }}</th>
                            <th class="text-primary text-center">{{ __('Profiles') }}</th>
                            <th class="text-primary">{{ __('Sort') }}</th>
                            <th class="text-primary text-center business-type-actions">{{ __('Actions') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($companyTypes as $companyType)
                            <tr>
                                <td>{{ $companyType->id }}</td>
                                <td>{{ $companyType->name }}</td>
                                <td class="text-center">{{ $companyType->profiles_count }}</td>
                                <td>{{ $companyType->sort_order }}</td>
                                <td class="text-center">
                                    <button type="button"
                                            class="btn btn-outline-primary btn-sm"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editBusinessTypeModal"
                                            data-business-type-id="{{ $companyType->id }}"
                                            data-business-type-name="{{ $companyType->name }}"
                                            data-business-type-sort-order="{{ $companyType->sort_order }}"
                                            title="{{ __('Edit') }}">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button type="button"
                                            class="btn btn-outline-danger btn-sm"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteBusinessTypeModal"
                                            data-business-type-id="{{ $companyType->id }}"
                                            data-business-type-name="{{ $companyType->name }}"
                                            title="{{ $companyType->profiles_count > 0 ? __('Type of Business is used by company profiles') : __('Delete') }}"
                                            @disabled($companyType->profiles_count > 0)>
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">{{ __('No types of business found') }}</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $companyTypes->links() }}
                </div>
            </div>
        </div>
    </div>

    @php($indexQ = $q)

    <div class="modal fade" id="createBusinessTypeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" method="POST" action="{{ route('library.type-of-business.store') }}">
                @csrf
                <input type="hidden" name="index_q" value="{{ $indexQ }}">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Add Type of Business') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @include('admin.marketing_company_types.partials.form', ['prefix' => 'create', 'companyType' => null])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-outline-primary btn-sm">{{ __('Save') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="editBusinessTypeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" id="editBusinessTypeForm" method="POST" action="#">
                @csrf
                @method('PUT')
                <input type="hidden" name="index_q" value="{{ $indexQ }}">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Edit Type of Business') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @include('admin.marketing_company_types.partials.form', ['prefix' => 'edit', 'companyType' => null])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-outline-primary btn-sm">{{ __('Update') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="deleteBusinessTypeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" id="deleteBusinessTypeForm" method="POST" action="#">
                @csrf
                @method('DELETE')
                <input type="hidden" name="index_q" value="{{ $indexQ }}">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Delete Type of Business') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">{{ __('Delete') }} <strong id="deleteBusinessTypeName"></strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-outline-danger btn-sm">{{ __('Delete') }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const editModal = document.getElementById('editBusinessTypeModal');
            const editForm = document.getElementById('editBusinessTypeForm');
            const deleteModal = document.getElementById('deleteBusinessTypeModal');
            const deleteForm = document.getElementById('deleteBusinessTypeForm');
            const deleteName = document.getElementById('deleteBusinessTypeName');

            editModal?.addEventListener('show.bs.modal', (event) => {
                const button = event.relatedTarget;
                editForm.action = @js(route('library.type-of-business.update', ['companyType' => ':id'])).replace(':id', button?.dataset.businessTypeId || '');
                document.getElementById('editName').value = button?.dataset.businessTypeName || '';
                document.getElementById('editSortOrder').value = button?.dataset.businessTypeSortOrder || 0;
            });

            deleteModal?.addEventListener('show.bs.modal', (event) => {
                const button = event.relatedTarget;
                const id = button?.dataset.businessTypeId;
                deleteForm.action = @js(route('library.type-of-business.destroy', ['companyType' => ':id'])).replace(':id', id);
                deleteName.textContent = button?.dataset.businessTypeName || '';
            });
        });
    </script>
@endsection
