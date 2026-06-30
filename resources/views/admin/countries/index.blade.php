@extends('admin.master')

@section('content')
    <style>
        .library-countries-page {
            flex: 1 1 auto;
            height: 100%;
            min-height: 0;
            display: flex;
            flex-direction: column;
        }

        .library-countries-page .card {
            flex: 1 1 auto;
            min-height: 0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border-radius: 6px;
        }

        .library-countries-page .card-body {
            flex: 1 1 auto;
            min-height: 0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .library-countries-page .table-responsive {
            flex: 1 1 0;
            min-height: 0;
            overflow: auto;
        }

        .library-countries-page thead th {
            position: sticky;
            top: 0;
            z-index: 2;
        }

        .country-actions {
            width: 92px;
        }
    </style>

    <div class="container-fluid library-countries-page">
        <div class="card shadow">
            <div class="card-header">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <h5 class="mb-0 text-primary">
                        {{ __('Countries') }}
                        <span class="text-success">({{ $countries->total() }})</span>
                    </h5>

                    <button type="button"
                            class="btn btn-outline-primary btn-sm"
                            data-bs-toggle="modal"
                            data-bs-target="#createCountryModal">
                        <i class="bi bi-plus-lg me-1"></i>{{ __('Add Country') }}
                    </button>
                </div>
            </div>

            <div class="card-body">
                <form method="GET" action="{{ route('library.countries.index') }}" class="row g-2 align-items-end mb-3">
                    <div class="col-12 col-md-8 col-xl-6">
                        <label for="countrySearch" class="form-label small text-muted mb-1">{{ __('Search') }}</label>
                        <input id="countrySearch"
                               type="search"
                               class="form-control form-control-sm"
                               name="q"
                               value="{{ $q }}"
                               placeholder="Country name or ISO code"
                               autocomplete="off"
                               autocorrect="off"
                               spellcheck="false">
                    </div>
                    <div class="col-12 col-md-auto">
                        <button class="btn btn-outline-primary btn-sm" type="submit">{{ __('Search') }}</button>
                        @if($q !== '')
                            <a class="btn btn-outline-secondary btn-sm" href="{{ route('library.countries.index') }}">{{ __('Clear') }}</a>
                        @endif
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-hover align-middle mb-0">
                        <thead class="bg-gradient">
                        <tr>
                            <th class="text-primary">ID</th>
                            <th class="text-primary">{{ __('Country') }}</th>
                            <th class="text-primary">{{ __('ISO') }}</th>
                            <th class="text-primary text-center">{{ __('Active') }}</th>
                            <th class="text-primary text-center">{{ __('Profiles') }}</th>
                            <th class="text-primary">{{ __('Sort') }}</th>
                            <th class="text-primary text-center country-actions">{{ __('Actions') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($countries as $country)
                            <tr>
                                <td>{{ $country->id }}</td>
                                <td>{{ $country->name }}</td>
                                <td>{{ $country->alpha2 }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $country->active ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $country->active ? __('Yes') : __('No') }}
                                    </span>
                                </td>
                                <td class="text-center">{{ $country->marketing_profiles_count }}</td>
                                <td>{{ $country->sort_order }}</td>
                                <td class="text-center">
                                    <button type="button"
                                            class="btn btn-outline-primary btn-sm"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editCountryModal"
                                            data-country-id="{{ $country->id }}"
                                            data-country-name="{{ $country->name }}"
                                            data-country-alpha2="{{ $country->alpha2 }}"
                                            data-country-sort-order="{{ $country->sort_order }}"
                                            data-country-active="{{ $country->active ? '1' : '0' }}"
                                            title="{{ __('Edit') }}">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button type="button"
                                            class="btn btn-outline-danger btn-sm"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteCountryModal"
                                            data-country-id="{{ $country->id }}"
                                            data-country-name="{{ $country->name }}"
                                            title="{{ $country->marketing_profiles_count > 0 ? __('Country is used by company profiles') : __('Delete') }}"
                                            @disabled($country->marketing_profiles_count > 0)>
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">{{ __('No countries found') }}</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $countries->links() }}
                </div>
            </div>
        </div>
    </div>

    @php($indexQ = $q)

    <div class="modal fade" id="createCountryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" method="POST" action="{{ route('library.countries.store') }}">
                @csrf
                <input type="hidden" name="index_q" value="{{ $indexQ }}">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Add Country') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @include('admin.countries.partials.form', ['prefix' => 'create', 'country' => null])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-outline-primary btn-sm">{{ __('Save') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="editCountryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" id="editCountryForm" method="POST" action="#">
                @csrf
                @method('PUT')
                <input type="hidden" name="index_q" value="{{ $indexQ }}">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Edit Country') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @include('admin.countries.partials.form', ['prefix' => 'edit', 'country' => null])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-outline-primary btn-sm">{{ __('Update') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="deleteCountryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" id="deleteCountryForm" method="POST" action="#">
                @csrf
                @method('DELETE')
                <input type="hidden" name="index_q" value="{{ $indexQ }}">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Delete Country') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">{{ __('Delete') }} <strong id="deleteCountryName"></strong>?</p>
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
            const editModal = document.getElementById('editCountryModal');
            const editForm = document.getElementById('editCountryForm');
            const deleteModal = document.getElementById('deleteCountryModal');
            const deleteForm = document.getElementById('deleteCountryForm');
            const deleteName = document.getElementById('deleteCountryName');

            editModal?.addEventListener('show.bs.modal', (event) => {
                const button = event.relatedTarget;
                editForm.action = @js(route('library.countries.update', ':id')).replace(':id', button?.dataset.countryId || '');
                document.getElementById('editName').value = button?.dataset.countryName || '';
                document.getElementById('editAlpha2').value = button?.dataset.countryAlpha2 || '';
                document.getElementById('editSortOrder').value = button?.dataset.countrySortOrder || 0;
                document.getElementById('editActive').checked = button?.dataset.countryActive === '1';
            });

            deleteModal?.addEventListener('show.bs.modal', (event) => {
                const button = event.relatedTarget;
                const id = button?.dataset.countryId;
                deleteForm.action = @js(route('library.countries.destroy', ':id')).replace(':id', id);
                deleteName.textContent = button?.dataset.countryName || '';
            });
        });
    </script>
@endsection
