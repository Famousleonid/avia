@extends('mobile.master')

@section('style')
    <style>
        .table-wrapper {
            height: calc(100vh - 140px);
            overflow-y: auto;
            overflow-x: hidden;
        }

        .table th, .table td {
            text-overflow: ellipsis;
            overflow: hidden;
            vertical-align: middle;
        }

        .table th:nth-child(1), .table td:nth-child(1) {
            width: 1%;
            white-space: nowrap;
        }

        .table th:nth-child(2), .table td:nth-child(2) {
            max-width: 15vw;
            width: 15vw;
        }

        .table th:nth-child(4), .table td:nth-child(4) {
            max-width: 40vw;
            width: 40vw;
        }

        .editable-description {
            background-color: #1e1e1e;
            color: #f1f1f1;
            border: none;
        }

        .btn-clear {
            background: none;
            border: none;
            cursor: pointer;
        }
        .editable-description {
            background-color: #1e1e1e !important;
            color: #ffffff !important;
            border: none;
            outline: none;
            min-height: 24px;
            padding: 4px;
        }

        .editable-description:focus {
            background-color: #292929 !important;
            color: #ffffff !important;
        }
        .text-size {
            font-size: 0.75rem;
            line-height: 2;
        }
    </style>
@endsection

@section('content')


    <div class="card shadow">
        <div class="card-header py-2 px-3 shadow">
            <div class="d-flex align-items-center flex-wrap gap-3">
                <span class="text-primary mb-0 text-size">
                    {{ __('Materials') }}
                    (<span class="text-success">{{ $materials->count() }}</span>)
                </span>

                <!-- Поиск с инпутом, спиннером и крестиком -->
                <div class="position-relative ms-2 mt-2 mt-sm-0 flex-grow-1" style="max-width: 100%;">
                    <input id="searchInput" type="text" class="form-control form-control-sm pe-5" placeholder="Search...">

                    <div id="searchSpinner"
                         class="spinner-border spinner-border-sm text-primary"
                         role="status"
                         style="position: absolute;
                            top: 50%;
                            right: 2.3rem;
                            transform: translateY(-50%);
                            display: none;
                            z-index: 1;">
                    </div>

                    <button type="button"
                            class="btn-clear text-secondary"
                            onclick="searchInput.value=''; searchInput.dispatchEvent(new Event('input'))"
                            style="position: absolute;
                               top: 50%;
                               right: 0.5rem;
                               transform: translateY(-50%);">
                        <i class="bi bi-x-circle"></i>
                    </button>
                </div>
            </div>
        </div>

        @if(count($materials))
            <div class="table-wrapper me-3 p-2 pt-0">
                <table id="cmmTable" class="table table-sm table-hover table-striped table-bordered">
                    <thead class="bg-gradient">
                    <tr>
                        <th class="text-primary text-size">{{ __('Code') }}</th>
                        <th class="text-primary text-size">{{ __('Material') }}</th>
                        <th class="text-primary text-size">{{ __('Specification') }}</th>
                        <th class="text-primary text-size">{{ __('Description') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($materials as $material)
                        <tr data-id="{{ $material->id }}">
                            <td class="text-size">{{ $material->code }}</td>
                            <td class="text-size" title="{{ $material->material }}">{{ \Illuminate\Support\Str::limit($material->material, 12) }}</td>
                            <td class="text-size" title="{{ $material->specification }}">{{ \Illuminate\Support\Str::limit($material->specification, 12) }}</td>
                            <td contenteditable="true" class="editable-description">{{ $material->description }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="px-3">Materials not created</p>
        @endif
    </div>
@endsection


@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const table = document.getElementById('cmmTable');
            const searchInput = document.getElementById('searchInput');
            const spinner = document.getElementById('searchSpinner');
            let debounceTimeout;

            // Поиск с дебаунсом и спиннером
            searchInput.addEventListener('input', () => {
                const filter = searchInput.value.toLowerCase();

                clearTimeout(debounceTimeout);
                spinner.style.display = 'inline-block';

                debounceTimeout = setTimeout(() => {
                    const rows = table.querySelectorAll('tbody tr');

                    if (filter.length < 2) {
                        rows.forEach(row => row.style.display = '');
                        spinner.style.display = 'none';
                        return;
                    }

                    rows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        row.style.display = text.includes(filter) ? '' : 'none';
                    });

                    spinner.style.display = 'none';
                }, 200);
            });

            // Inline update for description
            document.querySelectorAll('.editable-description').forEach(cell => {
                cell.addEventListener('blur', function () {
                    const newValue = this.innerText.trim();
                    const row = this.closest('tr');
                    const id = row.dataset.id;

                    fetch(`/mobile/materials/${id}/update-description`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ description: newValue })
                    })
                        .then(r => r.ok ? r.json() : Promise.reject(r))
                        .then(() => {
                            this.style.backgroundColor = '#2e7d32';
                            setTimeout(() => this.style.backgroundColor = '#1e1e1e', 500);
                        })
                        .catch(err => {
                            alert('Ошибка при сохранении');
                            console.error(err);
                        });
                });
            });
        });
    </script>
@endsection

