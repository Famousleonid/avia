@extends('admin.master')

@section('title', 'Управление компонентами - Workorder #' . $workorder->number)

@section('content')
    <style>
        .container {
            max-width: 1300px;
        }
        .text-center {
            text-align: center;
            align-content: center;
        }

        /* Уменьшаем размер шрифта в таблицах на 10% */
        .table {
            font-size: 0.9em; /* 90% от базового размера = уменьшение на 10% */
        }

        .table th,
        .table td {
            font-size: inherit;
        }

        /* Select2 Theme Support */
        .select2-container--bootstrap-5 .select2-selection {
            background-color: #ffffff;
            border-color: #ced4da;
            color: #212529;
        }

        .select2-container--bootstrap-5 .select2-selection--single {
            height: calc(1.5em + 0.75rem + 2px);
            padding: 0.375rem 0.75rem;
        }

        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
            color: #212529 !important;
            padding-left: 0;
            padding-right: 0;
        }

        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__placeholder {
            color: #6c757d;
        }
        
        /* Убеждаемся, что выбранный текст виден */
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered .select2-selection__choice {
            color: #212529 !important;
        }
        
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered .select2-selection__choice__display {
            color: #212529 !important;
        }
        
        /* Специально для дропдауна выбора компонента */
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
            color: #212529 !important;
        }
        
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered * {
            color: #212529 !important;
        }
        
        /* Принудительно устанавливаем цвет для всех элементов внутри выбранного текста */
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered span {
            color: #212529 !important;
        }

        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
            height: calc(1.5em + 0.75rem);
        }

        .select2-container--bootstrap-5 .select2-dropdown {
            background-color: #ffffff;
            border-color: #ced4da;
        }

        .select2-container--bootstrap-5 .select2-results__option {
            background-color: #ffffff;
            color: #212529;
        }

        .select2-container--bootstrap-5 .select2-results__option--highlighted {
            background-color: #0d6efd;
            color: #ffffff;
        }

        .select2-container--bootstrap-5 .select2-results__option--selected {
            background-color: #6c757d;
            color: #ffffff;
        }

        .select2-container--bootstrap-5 .select2-search--dropdown .select2-search__field {
            background-color: #ffffff;
            border-color: #ced4da;
            color: #212529;
        }
        
        /* Light mode for regular select elements */
        .form-control {
            background-color: #ffffff;
            border-color: #ced4da;
            color: #212529;
        }
        
        .form-control:focus {
            background-color: #ffffff;
            border-color: #86b7fe;
            color: #212529;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        
        .form-control option {
            background-color: #ffffff;
            color: #212529;
        }

        /* Dark mode specific overrides */
        @media (prefers-color-scheme: dark) {
            .select2-container--bootstrap-5 .select2-selection {
                background-color: #212529;
                border-color: #495057;
                color: #ffffff;
            }
            
            .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
                color: #ffffff !important;
            }
            
            .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered .select2-selection__choice {
                color: #ffffff !important;
            }
            
            .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered .select2-selection__choice__display {
                color: #ffffff !important;
            }
            
            /* Специально для дропдауна выбора компонента в темной теме */
            .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
                color: #ffffff !important;
            }
            
            .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered * {
                color: #ffffff !important;
            }
            
            .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered span {
                color: #ffffff !important;
            }

            .select2-container--bootstrap-5 .select2-dropdown {
                background-color: #212529;
                border-color: #495057;
            }

            .select2-container--bootstrap-5 .select2-results__option {
                background-color: #212529;
                color: #ffffff;
            }

            .select2-container--bootstrap-5 .select2-search--dropdown .select2-search__field {
                background-color: #212529;
                border-color: #495057;
                color: #ffffff;
            }

            /* Dark mode for regular select elements */
            .form-control {
                background-color: #212529;
                border-color: #495057;
                color: #ffffff;
            }

            .form-control:focus {
                background-color: #212529;
                border-color: #80bdff;
                color: #ffffff;
                box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
            }

            .form-control option {
                background-color: #212529;
                color: #ffffff;
            }
        }
    </style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class=" d-flex justify-content-between">
                        <h3 class="card-title">
                            Modification of NDT/CAD list Processes for W{{ $workorder->number }}
                        </h3>
                        <div class="card-tools">
                            <a href="{{ route('tdrs.show', $workorder->id) }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Workorder
                            </a>
                        </div>
                    </div>

                </div>
                <div class="card-body">
                    <!-- NDT Components Tab -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex justify-content-between">

                                        <h4 class="card-title">NDT list</h4>

                                        <div class="card-tools">
                                            <button type="button" class="btn btn-primary btn-sm" onclick="$('#addNdtModal').modal('show')">
                                                <i class="fas fa-plus"></i> Add
                                            </button>
                                            <button type="button" class="btn btn-info btn-sm" onclick="$('#importNdtModal').modal('show')">
                                                <i class="fas fa-upload"></i> Upload CSV
                                            </button>
                                        </div>
                                    </div>

                                </div>
                                <div class="card-body">
                                    @if(empty($modCsv->ndt_components))
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle"></i>
                                            <strong>Information:</strong> No CSV file with NDT list components was found for this
                                            manual.
                                            You can add components manually or upload a CSV file.
                                        </div>
                                    @endif
                                    <div class="table-responsive">
                                        <table class="table table-striped" id="ndtTable">
                                            <thead>
                                                <tr>
                                                    <th>№</th>
                                                    <th>IPL №</th>
                                                    <th>Part №</th>
                                                    <th>Description</th>
                                                    <th>QTY</th>
                                                    <th>Process</th>
                                                    <th>Delete</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php
                                                    $sortedNdtComponents = collect($modCsv->ndt_components ?? [])->sortBy(function($component) {
                                                        $parts = explode('-', $component['ipl_num'] ?? '');
                                                        $first = (int)($parts[0] ?? 0);
                                                        $second = (int)($parts[1] ?? 0);
                                                        return [$first, $second];
                                                    })->values();
                                                @endphp
                                                @forelse($sortedNdtComponents as $index => $component)
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>{{ $component['ipl_num'] ?? '' }}</td>
                                                    <td>{{ $component['part_number'] ?? '' }}</td>
                                                    <td>{{ $component['description'] ?? '' }}</td>
                                                    <td>{{ $component['qty'] ?? 1 }}</td>
                                                    <td>{{ $component['process'] ?? '' }}</td>
                                                    <td>
                                                        <button type="button" class="btn btn-danger btn-sm remove-ndt"
                                                                data-index="{{ $index }}">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                                @empty
                                                <tr>
                                                    <td colspan="7" class="text-center">No NDT list of components</td>
                                                </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- CAD Components Tab -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex justify-content-between">
                                        <h4 class="card-title">CAD list</h4>
                                        <div class="card-tools">
                                            <button type="button" class="btn btn-primary btn-sm" onclick="$('#addCadModal').modal('show')">
                                                <i class="fas fa-plus"></i> Add
                                            </button>
                                            <button type="button" class="btn btn-info btn-sm" onclick="$('#importCadModal').modal('show')">
                                                <i class="fas fa-upload"></i> Upload CSV
                                            </button>
                                        </div>
                                    </div>

                                </div>
                                <div class="card-body">
                                    @if(empty($modCsv->cad_components))
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle"></i>
                                            <strong>Information:</strong> No CSV file with CAD list components was found for this manual.
                                            You can add components manually or upload a CSV file.
                                        </div>
                                    @endif
                                    <div class="table-responsive">
                                        <table class="table table-striped" id="cadTable">
                                            <thead>
                                                <tr>
                                                    <th>№</th>
                                                    <th>IPL №</th>
                                                    <th>Part №</th>
                                                    <th>Description</th>
                                                    <th>Qty</th>
                                                    <th>Process</th>
                                                    <th>Delete</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php
                                                    $sortedCadComponents = collect($modCsv->cad_components ?? [])->sortBy(function($component) {
                                                        $parts = explode('-', $component['ipl_num'] ?? '');
                                                        $first = (int)($parts[0] ?? 0);
                                                        $second = (int)($parts[1] ?? 0);
                                                        return [$first, $second];
                                                    })->values();
                                                @endphp
                                                @forelse($sortedCadComponents as $index => $component)
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>{{ $component['ipl_num'] ?? '' }}</td>
                                                    <td>{{ $component['part_number'] ?? '' }}</td>
                                                    <td>{{ $component['description'] ?? '' }}</td>
                                                    <td>{{ $component['qty'] ?? 1 }}</td>
                                                    <td>{{ $component['process'] ?? '' }}</td>
                                                    <td>
                                                        <button type="button" class="btn btn-danger btn-sm remove-cad"
                                                                data-index="{{ $index }}">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                                @empty
                                                <tr>
                                                    <td colspan="7" class="text-center">No CAD list of components</td>
                                                </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add NDT Component Modal -->
<div class="modal fade" id="addNdtModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header d-flex justify-content-between">
                <h5 class="modal-title">Select a component for NDT</h5>
                <button type="button" class="close" onclick="$('#addNdtModal').modal('hide')">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group mb-3">
                    <label for="ndt_component_select">Select component:</label>
                    <select id="ndt_component_select" class="form-control select2" style="width: 100%;">
                        <option value="">Search for component...</option>
                    </select>
                </div>
                <div class="d-flex justify-content-around">
                    <div class="form-group mb-3">
                        <label for="ndt_qty">QTY:</label>
                        <input type="number" class="form-control" id="ndt_qty" value="1" min="1">
                    </div>
                    <div class="form-group mb-3">
                        <label for="ndt_process">Process:</label>
                        <input type="text" class="form-control" id="ndt_process" placeholder="Enter the process...">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="$(this).closest('.modal').modal('hide')">Cancel</button>
                <button type="button" class="btn btn-primary" id="addNdtComponentBtn">Add</button>
            </div>
        </div>
    </div>
</div>

<!-- Add CAD Component Modal -->
<div class="modal fade" id="addCadModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header d-flex justify-content-between">
                <h5 class="modal-title">Select a component for CAD</h5>
                <button type="button" class="close" onclick="$(this).closest('.modal').modal('hide')">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group mb-3">
                    <label for="cad_component_select">Select component:</label>
                    <select id="cad_component_select" class="form-control select2" style="width: 100%;">
                        <option value="">Search for component...</option>
                    </select>
                </div>
                <div class="d-flex justify-content-around">
                <div class="form-group mb-3 me-1">
                    <label for="cad_qty">QTY:</label>
                    <input type="number" class="form-control " id="cad_qty" value="1" min="1" style="width: 60px;">
                </div>
                <div class="form-group mb-3">
                    <label for="cad_process">Process:</label>
                    <select id="cad_process" class="form-control">
                        <option value="">Select a process...</option>
                    </select>
                </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="$(this).closest('.modal').modal('hide')">Cancel</button>
                <button type="button" class="btn btn-primary" id="addCadComponentBtn">Add</button>
            </div>
        </div>
    </div>
</div>

<!-- Import NDT CSV Modal -->
<div class="modal fade" id="importNdtModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import components for NDT from CSV</h5>
                <button type="button" class="close" onclick="$(this).closest('.modal').modal('hide')">
                    <span>&times;</span>
                </button>
            </div>
            <form id="importNdtForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="ndt_csv_file">CSV файл *</label>
                        <input type="file" class="form-control" id="ndt_csv_file" name="csv_file" accept=".csv" required>
                        <small class="form-text text-muted">
                            The file must contain the columns: ITEM No., PART No., DESCRIPTION, QTY, PROCESS No.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="$(this).closest('.modal').modal('hide')
                    ">Cancel</button>
                    <button type="submit" class="btn btn-primary">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Import CAD CSV Modal -->
<div class="modal fade" id="importCadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import components for CAD from CSV</h5>
                <button type="button" class="close" onclick="$(this).closest('.modal').modal('hide')">
                    <span>&times;</span>
                </button>
            </div>
            <form id="importCadForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="cad_csv_file">CSV файл *</label>
                        <input type="file" class="form-control" id="cad_csv_file" name="csv_file" accept=".csv" required>
                        <small class="form-text text-muted">
                            The file must contain the columns: ITEM No., PART No., DESCRIPTION, QTY, PROCESS No.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="$(this).closest('.modal').modal('hide')
                    ">Cancel</button>
                    <button type="submit" class="btn btn-primary">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    const workorderId = {{ $workorder->id }};
    console.log('ModCsv page loaded, workorderId:', workorderId);
    // Use server-generated route to avoid prefix mismatches (e.g., /admin)
    const componentsSearchUrl = "{{ route('mod-csv.components.search', ['workorderId' => $workorder->id]) }}";
    console.log('Components search URL:', componentsSearchUrl);

    // Store selected component data
    let selectedNdtComponent = null;
    let selectedCadComponent = null;
    let cadProcesses = [];
    let currentNdtComponents = @json($modCsv->ndt_components ?? []);
    let currentCadComponents = @json($modCsv->cad_components ?? []);

    // Функция для обновления таблицы NDT
    function updateNdtTable(components) {
        const tbody = $('#ndtTable tbody');
        tbody.empty();

        if (components && components.length > 0) {
            // Сортируем компоненты по ipl_num
            const sortedComponents = components.sort((a, b) => {
                const aParts = (a.ipl_num || '').split('-');
                const bParts = (b.ipl_num || '').split('-');

                // Сравниваем первую часть (до -)
                const aFirst = parseInt(aParts[0]) || 0;
                const bFirst = parseInt(bParts[0]) || 0;

                if (aFirst !== bFirst) {
                    return aFirst - bFirst;
                }

                // Если первая часть одинаковая, сравниваем вторую часть (после -)
                const aSecond = parseInt(aParts[1]) || 0;
                const bSecond = parseInt(bParts[1]) || 0;

                return aSecond - bSecond;
            });

            sortedComponents.forEach((component, index) => {
                const row = `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${component.ipl_num || ''}</td>
                        <td>${component.part_number || ''}</td>
                        <td>${component.description || ''}</td>
                        <td>${component.qty || 1}</td>
                        <td>${component.process || ''}</td>
                        <td>
                            <button type="button" class="btn btn-danger btn-sm remove-ndt"
                                    data-index="${index}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
                tbody.append(row);
            });
        } else {
            tbody.append('<tr><td colspan="7" class="text-center">No components for NDT</td></tr>');
        }

        console.log('NDT Table updated with', components.length, 'components');
        console.log('NDT Components:', components);

        // Обновляем текущий список компонентов
        currentNdtComponents = components;
    }

    // Функция для обновления таблицы CAD
    function updateCadTable(components) {
        const tbody = $('#cadTable tbody');
        tbody.empty();

        if (components && components.length > 0) {
            // Сортируем компоненты по ipl_num
            const sortedComponents = components.sort((a, b) => {
                const aParts = (a.ipl_num || '').split('-');
                const bParts = (b.ipl_num || '').split('-');

                // Сравниваем первую часть (до -)
                const aFirst = parseInt(aParts[0]) || 0;
                const bFirst = parseInt(bParts[0]) || 0;

                if (aFirst !== bFirst) {
                    return aFirst - bFirst;
                }

                // Если первая часть одинаковая, сравниваем вторую часть (после -)
                const aSecond = parseInt(aParts[1]) || 0;
                const bSecond = parseInt(bParts[1]) || 0;

                return aSecond - bSecond;
            });

            sortedComponents.forEach((component, index) => {
                const row = `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${component.ipl_num || ''}</td>
                        <td>${component.part_number || ''}</td>
                        <td>${component.description || ''}</td>
                        <td>${component.qty || 1}</td>
                        <td>${component.process || ''}</td>
                        <td>
                            <button type="button" class="btn btn-danger btn-sm remove-cad"
                                    data-index="${index}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
                tbody.append(row);
            });
        } else {
            tbody.append('<tr><td colspan="7" class="text-center">No components for CAD</td></tr>');
        }

        // Обновляем текущий список компонентов
        currentCadComponents = components;
    }

    // Initialize Select2 for NDT component selection
    function initNdtSelect2() {
        console.log('Initializing NDT Select2');
        console.log('Select2 available:', typeof $.fn.select2 !== 'undefined');
        console.log('Components search URL:', componentsSearchUrl);

        // Destroy existing Select2 if it exists
        if ($('#ndt_component_select').hasClass('select2-hidden-accessible')) {
            $('#ndt_component_select').select2('destroy');
        }

        // First load all components
        $.ajax({
            url: componentsSearchUrl,
            dataType: 'json',
            data: { search: '' },
            beforeSend: function() {
                $('#ndt_component_select').html('<option value="">Loading components...</option>');
            },
            success: function(data) {
                if (!data.success) {
                    console.error('Server returned error:', data.message);
                    $('#ndt_component_select').html('<option value="">Error loading components</option>');
                    return;
                }

                const components = data.components || [];
                console.log('Loaded NDT components:', components.length);

                // Clear existing options
                $('#ndt_component_select').empty();
                // Add empty option to enable placeholder and avoid auto-select of first item
                $('#ndt_component_select').append(new Option('Search for component...', '', false, false));

                // Add all components as options
                components.forEach(function(component) {
                    const optionId = `${component.ipl_num || ''}|${component.part_number || ''}|${component.name || ''}`;
                    const optionText = `${component.ipl_num || ''} - ${component.part_number || ''} - ${component.name || ''}`;

                    const option = new Option(optionText, optionId, false, false);
                    option.dataset.component = JSON.stringify(component);
                    $('#ndt_component_select').append(option);
                });

                // Initialize Select2 with all options loaded
                $('#ndt_component_select').select2({
                    placeholder: 'Search for component...',
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $('#addNdtModal'),
                    theme: 'bootstrap-5'
                });

                console.log('NDT Select2 initialized with', components.length, 'components');
            },
            error: function(xhr, status, error) {
                console.error('Error loading NDT components:', xhr.responseText);
                $('#ndt_component_select').html('<option value="">Error loading components</option>');
                alert('Ошибка загрузки компонентов. Проверьте консоль для подробностей.');
            }
        });
    }

    // Initialize Select2 for CAD component selection
    function initCadSelect2() {
        // Destroy existing Select2 if it exists
        if ($('#cad_component_select').hasClass('select2-hidden-accessible')) {
            $('#cad_component_select').select2('destroy');
        }

        // First load all components
        $.ajax({
            url: componentsSearchUrl,
            dataType: 'json',
            data: { search: '' },
            beforeSend: function() {
                $('#cad_component_select').html('<option value="">Loading components...</option>');
            },
            success: function(data) {
                if (!data.success) {
                    console.error('Server returned error:', data.message);
                    $('#cad_component_select').html('<option value="">Error loading components</option>');
                    return;
                }

                const components = data.components || [];
                console.log('Loaded CAD components:', components.length);

                // Clear existing options
                $('#cad_component_select').empty();
                // Add empty option to enable placeholder and avoid auto-select of first item
                $('#cad_component_select').append(new Option('Search for component...', '', false, false));

                // Add all components as options
                components.forEach(function(component) {
                    const optionId = `${component.ipl_num || ''}|${component.part_number || ''}|${component.name || ''}`;
                    const optionText = `${component.ipl_num || ''} - ${component.part_number || ''} - ${component.name || ''}`;

                    const option = new Option(optionText, optionId, false, false);
                    option.dataset.component = JSON.stringify(component);
                    $('#cad_component_select').append(option);
                });

                // Initialize Select2 with all options loaded
                $('#cad_component_select').select2({
                    placeholder: 'Search for component...',
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $('#addCadModal'),
                    theme: 'bootstrap-5'
                });

                console.log('CAD Select2 initialized with', components.length, 'components');
            },
            error: function(xhr, status, error) {
                console.error('Error loading CAD components:', xhr.responseText);
                $('#cad_component_select').html('<option value="">Error loading components</option>');
                alert('Ошибка загрузки компонентов. Проверьте консоль для подробностей.');
            }
        });
    }

    // Test button clicks
    $('button[onclick*="addNdtModal"]').on('click', function() {
        console.log('NDT Add button clicked');
    });

    $('button[onclick*="addCadModal"]').on('click', function() {
        console.log('CAD Add button clicked');
    });

    // Check if modals exist
    console.log('NDT Modal exists:', $('#addNdtModal').length > 0);
    console.log('CAD Modal exists:', $('#addCadModal').length > 0);

    // Initialize Select2 when modals open
    $('#addNdtModal').on('show.bs.modal', function() {
        console.log('NDT Modal opening, workorderId:', workorderId);
        
        // Check if the select element exists
        if ($('#ndt_component_select').length === 0) {
            console.error('NDT component select element not found!');
            return;
        }
        
        // Destroy existing Select2 if it exists
        if ($('#ndt_component_select').hasClass('select2-hidden-accessible')) {
            $('#ndt_component_select').select2('destroy');
        }
        
        // Small delay to ensure modal is fully shown
        setTimeout(function() {
            initNdtSelect2();
        }, 100);
    });

    $('#addCadModal').on('show.bs.modal', function() {
        console.log('CAD Modal opening, workorderId:', workorderId);
        
        // Check if the select element exists
        if ($('#cad_component_select').length === 0) {
            console.error('CAD component select element not found!');
            return;
        }
        
        // Destroy existing Select2 if it exists
        if ($('#cad_component_select').hasClass('select2-hidden-accessible')) {
            $('#cad_component_select').select2('destroy');
        }
        
        // Small delay to ensure modal is fully shown
        setTimeout(function() {
            initCadSelect2();
            loadCadProcesses();
        }, 100);
    });

    // Load CAD processes
    function loadCadProcesses() {
        console.log('Loading CAD processes for workorder:', workorderId);
        $.ajax({
            url: `{{ route('mod-csv.cad-processes', ['workorderId' => $workorder->id]) }}`,
            dataType: 'json',
            success: function(data) {
                console.log('Loaded CAD processes:', data);
                console.log('CAD processes count:', data.processes ? data.processes.length : 0);
                cadProcesses = data.processes || [];

                // Clear existing options
                $('#cad_process').empty();
                $('#cad_process').append('<option value="">Select a process...</option>');

                // Add processes
                if (data.processes && data.processes.length > 0) {
                    data.processes.forEach(function(process) {
                        console.log('Adding CAD process:', process);
                        $('#cad_process').append(new Option(process.name, process.id, false, false));
                    });
                } else {
                    console.log('No CAD processes found for this manual');
                    $('#cad_process').append('<option value="" disabled>There are no CAD processes for this manual.</option>');
                }

                console.log('CAD Process options added:', data.processes ? data.processes.length : 0);
            },
            error: function(xhr, status, error) {
                console.error('Error loading CAD processes:', xhr);
                console.error('Status:', status);
                console.error('Error:', error);
                console.error('Response:', xhr.responseText);
            }
        });
    }

    // Handle NDT component selection
    $(document).on('select2:select', '#ndt_component_select', function (e) {
        const data = e.params.data;
        const selectedOption = $(this).find('option:selected');
        let componentData = {};

        try {
            const componentStr = selectedOption.data('component');
            if (typeof componentStr === 'string') {
                componentData = JSON.parse(componentStr);
            } else if (typeof componentStr === 'object') {
                componentData = componentStr;
            }
        } catch (error) {
            console.error('Error parsing component data:', error);
            // Fallback: parse from data.id
            const parts = data.id.split('|');
            componentData = {
                ipl_num: parts[0] || '',
                part_number: parts[1] || '',
                name: parts[2] || '',
                units_assy: 1
            };
        }

        selectedNdtComponent = {
            ipl_num: componentData.ipl_num || '',
            part_number: componentData.part_number || '',
            name: componentData.name || '',
            units_assy: componentData.units_assy || 1,
            process: '',
            qty: 1
        };

        // Предзаполняем количество из units_assy
        $('#ndt_qty').val(selectedNdtComponent.units_assy);

        console.log('NDT Component selected:', selectedNdtComponent);
    });

    // Handle CAD component selection
    $(document).on('select2:select', '#cad_component_select', function (e) {
        const data = e.params.data;
        const selectedOption = $(this).find('option:selected');
        let componentData = {};

        try {
            const componentStr = selectedOption.data('component');
            if (typeof componentStr === 'string') {
                componentData = JSON.parse(componentStr);
            } else if (typeof componentStr === 'object') {
                componentData = componentStr;
            }
        } catch (error) {
            console.error('Error parsing component data:', error);
            // Fallback: parse from data.id
            const parts = data.id.split('|');
            componentData = {
                ipl_num: parts[0] || '',
                part_number: parts[1] || '',
                name: parts[2] || '',
                units_assy: 1
            };
        }

        selectedCadComponent = {
            ipl_num: componentData.ipl_num || '',
            part_number: componentData.part_number || '',
            name: componentData.name || '',
            units_assy: componentData.units_assy || 1,
            process: '',
            qty: 1
        };

        // Предзаполняем количество из units_assy
        $('#cad_qty').val(selectedCadComponent.units_assy);

        console.log('CAD Component selected:', selectedCadComponent);
    });

    // Add NDT Component button
    $('#addNdtComponentBtn').on('click', function() {
        if (!selectedNdtComponent) {
            alert('Пожалуйста, выберите компонент');
            return;
        }

        // Проверка на дублирование
        const isDuplicate = currentNdtComponents.some(component =>
            component.ipl_num === selectedNdtComponent.ipl_num &&
            component.part_number === selectedNdtComponent.part_number
        );

        if (isDuplicate) {
            if (!confirm(`Component ${selectedNdtComponent.ipl_num} (${selectedNdtComponent.part_number}) already exists in the list. Add another one?`)) {
                return;
            }
        }

        const qty = $('#ndt_qty').val() || 1;
        const process = $('#ndt_process').val() || '';

        $.ajax({
            url: `{{ route('mod-csv.ndt.add', ['workorderId' => $workorder->id]) }}`,
            method: 'POST',
            data: {
                ipl_num: selectedNdtComponent.ipl_num,
                part_number: selectedNdtComponent.part_number,
                description: selectedNdtComponent.name,
                qty: qty,
                process: process
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    updateNdtTable(response.components);
                    $('#addNdtModal').modal('hide');
                    // Reset form
                    $('#ndt_component_select').val('').trigger('change');
                    $('#ndt_qty').val(1);
                    $('#ndt_process').val('');
                    selectedNdtComponent = null;
                } else {
                    alert('Ошибка: ' + response.message);
                }
            },
            error: function(xhr) {
                alert('Ошибка: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'));
            }
        });
    });

    // Add CAD Component button
    $('#addCadComponentBtn').on('click', function() {
        if (!selectedCadComponent) {
            alert('Пожалуйста, выберите компонент');
            return;
        }

        // Проверка на дублирование
        const isDuplicate = currentCadComponents.some(component =>
            component.ipl_num === selectedCadComponent.ipl_num &&
            component.part_number === selectedCadComponent.part_number
        );

        if (isDuplicate) {
            if (!confirm(`Component ${selectedCadComponent.ipl_num} (${selectedCadComponent.part_number}) already exists in the list. Add another one?`)) {
                return;
            }
        }

        const qty = $('#cad_qty').val() || 1;
        const processId = $('#cad_process').val() || '';
        const processName = $('#cad_process option:selected').text() || '';

        $.ajax({
            url: `{{ route('mod-csv.cad.add', ['workorderId' => $workorder->id]) }}`,
            method: 'POST',
            data: {
                ipl_num: selectedCadComponent.ipl_num,
                part_number: selectedCadComponent.part_number,
                description: selectedCadComponent.name,
                qty: qty,
                process: processName
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    updateCadTable(response.components);
                    $('#addCadModal').modal('hide');
                    // Reset form
                    $('#cad_component_select').val('').trigger('change');
                    $('#cad_qty').val(1);
                    $('#cad_process').val('');
                    selectedCadComponent = null;
                } else {
                    alert('Ошибка: ' + response.message);
                }
            },
            error: function(xhr) {
                alert('Ошибка: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'));
            }
        });
    });


    // Import NDT CSV
    $('#importNdtForm').on('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        formData.append('type', 'ndt');

        $.ajax({
            url: `{{ route('mod-csv.import', ['workorderId' => $workorder->id]) }}`,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    updateNdtTable(response.components);
                    $('#importNdtModal').modal('hide');
                } else {
                    alert('Ошибка: ' + response.message);
                }
            },
            error: function(xhr) {
                alert('Ошибка: ' + xhr.responseJSON.message);
            }
        });
    });

    // Import CAD CSV
    $('#importCadForm').on('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        formData.append('type', 'cad');

        $.ajax({
            url: `{{ route('mod-csv.import', ['workorderId' => $workorder->id]) }}`,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    updateCadTable(response.components);
                    $('#importCadModal').modal('hide');
                } else {
                    alert('Ошибка: ' + response.message);
                }
            },
            error: function(xhr) {
                alert('Ошибка: ' + xhr.responseJSON.message);
            }
        });
    });

    // Remove NDT Component
    $(document).on('click', '.remove-ndt', function() {
        const index = $(this).data('index');
        const iplNum = $(this).closest('tr').find('td:nth-child(2)').text(); // ipl_num
        const partNumber = $(this).closest('tr').find('td:nth-child(3)').text(); // part_number
        console.log('NDT Remove clicked, index:', index, 'ipl_num:', iplNum, 'part_number:', partNumber);

        if (confirm(`Вы уверены, что хотите удалить компонент ${iplNum} (${partNumber})?`)) {
            console.log('Sending AJAX request to remove NDT component by ipl_num:', iplNum);
            $.ajax({
                url: `{{ route('mod-csv.ndt.remove', ['workorderId' => $workorder->id]) }}`,
                method: 'POST',
                data: {
                    ipl_num: iplNum,
                    part_number: partNumber
                },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    console.log('NDT Remove response:', response);
                    if (response.success) {
                        console.log('Remaining components after removal:', response.components);
                        updateNdtTable(response.components);
                    } else {
                        alert('Ошибка: ' + response.message);
                    }
                },
                error: function(xhr) {
                    console.log('NDT Remove error:', xhr);
                    alert('Ошибка: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'));
                }
            });
        }
    });

    // Remove CAD Component
    $(document).on('click', '.remove-cad', function() {
        const index = $(this).data('index');
        const iplNum = $(this).closest('tr').find('td:nth-child(2)').text(); // ipl_num
        const partNumber = $(this).closest('tr').find('td:nth-child(3)').text(); // part_number
        console.log('CAD Remove clicked, index:', index, 'ipl_num:', iplNum, 'part_number:', partNumber);

        if (confirm(`Вы уверены, что хотите удалить компонент ${iplNum} (${partNumber})?`)) {
            console.log('Sending AJAX request to remove CAD component by ipl_num:', iplNum);
            $.ajax({
                url: `{{ route('mod-csv.cad.remove', ['workorderId' => $workorder->id]) }}`,
                method: 'POST',
                data: {
                    ipl_num: iplNum,
                    part_number: partNumber
                },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    console.log('CAD Remove response:', response);
                    if (response.success) {
                        console.log('Remaining components after removal:', response.components);
                        updateCadTable(response.components);
                    } else {
                        alert('Ошибка: ' + response.message);
                    }
                },
                error: function(xhr) {
                    console.log('CAD Remove error:', xhr);
                    alert('Ошибка: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'));
                }
            });
        }
    });
});


    // Global error handler
    window.onerror = function(msg, url, lineNo, columnNo, error) {
        console.error('JavaScript Error:', msg, 'at', url, ':', lineNo, ':', columnNo);
        console.error('Error object:', error);
        return false;
    };

    // Unhandled promise rejection handler
    window.addEventListener('unhandledrejection', function(event) {
        console.error('Unhandled promise rejection:', event.reason);
    });
</script>
@endsection



