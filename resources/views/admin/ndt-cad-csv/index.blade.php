@extends('admin.master')

@section('title', 'Управление компонентами NDT/CAD - Workorder #' . $workorder->number)

<style>
    .container {
        max-width: 10800px;
    }
    .text-center {
        text-align: center;
        align-content: center;
    }
    .card{
        max-width: 1050px;
    }

    html[data-bs-theme="dark"]  .select2-selection--single {
        background-color: #121212 !important;
        color: gray !important;
        height: 38px !important;
        border: 1px solid #495057 !important;
        align-items: center !important;
        border-radius: 8px;
    }

    html[data-bs-theme="dark"] .select2-container .select2-selection__rendered {
        color: #999999;
        line-height: 2.2 !important;
    }

    html[data-bs-theme="dark"] .select2-search--dropdown .select2-search__field  {
        background-color: #343A40 !important;
    }

    html[data-bs-theme="dark"] .select2-container--default .select2-selection--single .select2-selection__rendered {
        padding-right: 25px;
    }

    html[data-bs-theme="dark"] .select2-container .select2-dropdown {
        max-height: 40vh !important;
        overflow-y: auto !important;
        border: 1px solid #ccc !important;
        border-radius: 8px;
        color: white;
        background-color: #121212 !important;
    }

    html[data-bs-theme="light"] .select2-container .select2-dropdown {
        max-height: 40vh !important;
        overflow-y: auto !important;

    }

    html[data-bs-theme="dark"] .select2-container .select2-results__option:hover {
        background-color: #6ea8fe;
        color: #000000;

    }
    .select2-container .select2-selection__clear {
        position: absolute !important;
        right: 10px !important;
        top: 50% !important;
        transform: translateY(-50%) !important;
        z-index: 1;
    }


/*!* Стили для Select2 в модальных окнах *!*/
/*.select2-container--default .select2-dropdown {*/
/*    z-index: 9999 !important;*/
/*}*/

/*.select2-container--default .select2-selection--single {*/
/*    height: 38px !important;*/
/*    border: 1px solid #ced4da !important;*/
/*    border-radius: 0.375rem !important;*/
/*}*/

/*.select2-container--default .select2-selection--single .select2-selection__rendered {*/
/*    color: #999999;*/
/*    line-height: 36px !important;*/
/*    padding-left: 12px !important;*/
/*}*/

/*.select2-container--default .select2-selection--single .select2-selection__arrow {*/
/*    height: 36px !important;*/
/*}*/

/* Убеждаемся, что dropdown отображается поверх модального окна */
.modal .select2-container {
    z-index: 9999 !important;
}
</style>


@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        Modification of NDT/CAD list Processes for W{{ $workorder->number }}
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('tdrs.show', ['tdr'=>$workorder->id]) }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Workorder
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Навигация по вкладкам -->
                    <ul class="nav nav-tabs" id="componentTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="ndt-tab" data-bs-toggle="tab" data-bs-target="#ndt-pane" type="button" role="tab">
                                NDT  <span class="badge bg-primary ms-2" id="ndt-count">{{ count($ndtCadCsv->ndt_components ?? []) }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="cad-tab" data-bs-toggle="tab" data-bs-target="#cad-pane" type="button" role="tab">
                                CAD  <span class="badge bg-success ms-2" id="cad-count">{{ count($ndtCadCsv->cad_components ??
                                 []) }}</span>
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="componentTabsContent">
                        <!-- NDT Компоненты -->
                        <div class="tab-pane fade show active" id="ndt-pane" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5>NDT List</h5>
                                <div>
                                    <button type="button" class="btn btn-success btn-sm" onclick="showAddNdtModal()">
                                        <i class="fas fa-plus"></i> Add
                                    </button>
                                    <button type="button" class="btn btn-info btn-sm" onclick="importNdtFromCsv()">
                                        <i class="fas fa-file-import"></i> Upload CSV
                                    </button>
                                    <button type="button" class="btn btn-warning btn-sm" onclick="reloadFromManual('ndt')">
                                        <i class="fas fa-sync"></i> Reload CSV
                                    </button>
{{--                                    <button type="button" class="btn btn-secondary btn-sm" onclick="forceLoadFromManual('ndt')">--}}
{{--                                        <i class="fas fa-download"></i> Принудительная загрузка--}}
{{--                                    </button>--}}
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="ndt-table">
                                    <thead>
                                        <tr>
                                            <th>IPL №</th>
                                            <th>Part Number</th>
                                            <th>Description</th>
                                            <th>Process</th>
                                            <th>QTY</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                     <tbody id="ndt-tbody">
                                         @php
                                             $ndtComponents = $ndtCadCsv->ndt_components ?? [];
                                             $sortedNdtComponents = collect($ndtComponents)->sortBy('ipl_num', SORT_NATURAL)->values();
                                         @endphp
                                         @forelse($sortedNdtComponents as $displayIndex => $component)
                                         @php
                                             // Находим оригинальный индекс в исходном массиве
                                             $originalIndex = array_search($component, $ndtComponents);
                                         @endphp
                                         <tr data-index="{{ $originalIndex }}" data-display-index="{{ $displayIndex }}">
                                             <td>{{ $component['ipl_num'] }}</td>
                                             <td>{{ $component['part_number'] }}</td>
                                             <td>{{ $component['description'] }}</td>
                                             <td>{{ $component['process'] }}</td>
                                             <td>{{ $component['qty'] }}</td>
                                             <td>
                                                 <button class="btn btn-sm btn-danger" onclick="removeNdtComponent({{ $originalIndex }})">
                                                     <i class="fas fa-trash"></i>
                                                 </button>
                                             </td>
                                         </tr>
                                         @empty
                                         <tr>
                                             <td colspan="6" class="text-center text-muted">Нет NDT компонентов</td>
                                         </tr>
                                         @endforelse
                                     </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- CAD Компоненты -->
                        <div class="tab-pane fade" id="cad-pane" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5>CAD Компоненты</h5>
                                <div>
                                    <button type="button" class="btn btn-success btn-sm" onclick="showAddCadModal()">
                                        <i class="fas fa-plus"></i> Добавить компонент
                                    </button>
                                    <button type="button" class="btn btn-info btn-sm" onclick="importCadFromCsv()">
                                        <i class="fas fa-file-import"></i> Импорт из CSV
                                    </button>
                                    <button type="button" class="btn btn-warning btn-sm" onclick="reloadFromManual('cad')">
                                        <i class="fas fa-sync"></i> Перезагрузить из Manual
                                    </button>
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="forceLoadFromManual('cad')">
                                        <i class="fas fa-download"></i> Принудительная загрузка
                                    </button>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="cad-table">
                                    <thead>
                                        <tr>
                                            <th>IPL №</th>
                                            <th>Part Number</th>
                                            <th>Описание</th>
                                            <th>Процесс</th>
                                            <th>Количество</th>
                                            <th>Действия</th>
                                        </tr>
                                    </thead>
                                     <tbody id="cad-tbody">
                                         @php
                                             $cadComponents = $ndtCadCsv->cad_components ?? [];
                                             $sortedCadComponents = collect($cadComponents)->sortBy('ipl_num', SORT_NATURAL)->values();
                                         @endphp
                                         @forelse($sortedCadComponents as $displayIndex => $component)
                                         @php
                                             // Находим оригинальный индекс в исходном массиве
                                             $originalIndex = array_search($component, $cadComponents);
                                         @endphp
                                         <tr data-index="{{ $originalIndex }}" data-display-index="{{ $displayIndex }}">
                                             <td>{{ $component['ipl_num'] }}</td>
                                             <td>{{ $component['part_number'] }}</td>
                                             <td>{{ $component['description'] }}</td>
                                             <td>{{ $component['process'] }}</td>
                                             <td>{{ $component['qty'] }}</td>
                                             <td>
                                                 <button class="btn btn-sm btn-danger" onclick="removeCadComponent({{ $originalIndex }})">
                                                     <i class="fas fa-trash"></i>
                                                 </button>
                                             </td>
                                         </tr>
                                         @empty
                                         <tr>
                                             <td colspan="6" class="text-center text-muted">Нет CAD компонентов</td>
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

<!-- Модальное окно для добавления/редактирования NDT компонента -->
<div class="modal fade" id="ndtModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ndtModalTitle">Добавить NDT компонент</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="ndtForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="ndtComponent" class="form-label">Выбрать компонент *</label>
                        <select class="form-control select2" id="ndtComponent" name="component_id" required>
                            <option value="">Выберите компонент...</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="ndtQty" class="form-label">QTY *</label>
                        <input type="number" class="form-control" id="ndtQty" name="qty" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="ndtProcess" class="form-label">Process *</label>
                        <input type="text" class="form-control" id="ndtProcess" name="process" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Модальное окно для добавления/редактирования CAD компонента -->
<div class="modal fade" id="cadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cadModalTitle">Добавить CAD компонент</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="cadForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="cadComponent" class="form-label">Выбрать компонент *</label>
                        <select class="form-control select2" id="cadComponent" name="component_id" required>
                            <option value="">Выберите компонент...</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="cadQty" class="form-label">QTY *</label>
                        <input type="number" class="form-control" id="cadQty" name="qty" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="cadProcess" class="form-label">Process *</label>
                        <select class="form-control select2" id="cadProcess" name="process" required>
                            <option value="">Выберите процесс...</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Модальное окно для импорта CSV -->
<div class="modal fade" id="csvImportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Импорт компонентов из CSV</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="csvImportForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="csvType" class="form-label">Тип компонентов *</label>
                        <select class="form-control" id="csvType" required>
                            <option value="">Выберите тип</option>
                            <option value="ndt">NDT</option>
                            <option value="cad">CAD</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="csvFile" class="form-label">CSV файл *</label>
                        <input type="file" class="form-control" id="csvFile" accept=".csv,.txt" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">Импортировать</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Глобальная функция для ожидания загрузки jQuery
window.waitForJQuery = function(callback) {
    if (typeof $ !== 'undefined') {
        callback();
    } else {
        setTimeout(function() {
            window.waitForJQuery(callback);
        }, 100);
    }
};

const workorderId = {{ $workorder->id }};
let ndtComponents = @json($ndtCadCsv->ndt_components ?? []);
let cadComponents = @json($ndtCadCsv->cad_components ?? []);
let allComponents = [];
let cadProcesses = [];

// Определяем функции сразу в глобальной области видимости
window.showAddNdtModal = function() {
    console.log('showAddNdtModal called');
    // Простая проверка jQuery
    if (typeof $ !== 'undefined') {
        $('#ndtForm')[0].reset();
        $('#ndtComponent').val('').trigger('change');

        // Инициализируем Select2 для модального окна
        if (typeof $.fn.select2 !== 'undefined') {
            $('#ndtComponent').select2({
                placeholder: 'Выберите компонент...',
                allowClear: true,
                width: '100%',
                dropdownParent: $('#ndtModal')
            });
        }

        $('#ndtModal').modal('show');
    } else {
        console.log('jQuery not loaded yet, using fallback');
        // Fallback без jQuery
        document.getElementById('ndtForm').reset();
        document.getElementById('ndtModal').style.display = 'block';
        document.getElementById('ndtModal').classList.add('show');
    }
};

window.showAddCadModal = function() {
    console.log('showAddCadModal called');
    // Простая проверка jQuery
    if (typeof $ !== 'undefined') {
        $('#cadForm')[0].reset();
        $('#cadComponent').val('').trigger('change');
        $('#cadProcess').val('').trigger('change');

        // Инициализируем Select2 для модального окна
        if (typeof $.fn.select2 !== 'undefined') {
            $('#cadComponent').select2({
                placeholder: 'Выберите компонент...',
                allowClear: true,
                width: '100%',
                dropdownParent: $('#cadModal')
            });
            $('#cadProcess').select2({
                placeholder: 'Выберите процесс...',
                allowClear: true,
                width: '100%',
                dropdownParent: $('#cadModal')
            });
        }

        $('#cadModal').modal('show');
    } else {
        console.log('jQuery not loaded yet, using fallback');
        // Fallback без jQuery
        document.getElementById('cadForm').reset();
        document.getElementById('cadModal').style.display = 'block';
        document.getElementById('cadModal').classList.add('show');
    }
};

// Загрузка данных при инициализации - ждем загрузки jQuery
function initializeWhenReady() {
    if (typeof $ !== 'undefined') {
        console.log('Document ready, initializing...');
        console.log('jQuery version:', $.fn.jquery);
        console.log('Bootstrap modal available:', typeof $.fn.modal);

        loadComponents();
        loadCadProcesses();

        // Инициализация Select2 (если доступен)
        if (typeof $.fn.select2 !== 'undefined') {
            // Инициализируем Select2 для обычных элементов (не в модальных окнах)
            // Модальные окна будут инициализированы отдельно при открытии
            console.log('Select2 available, will be initialized for modals');
        } else {
            console.log('Select2 not available, using regular select');
        }

        console.log('Initialization complete');

        // Обработчики для автоматического заполнения полей
        $('#ndtComponent').on('change', function() {
            const selectedOption = $(this).find('option:selected');
            if (selectedOption.val()) {
                $('#ndtQty').val(selectedOption.data('units-assy') || 1);
            }
        });

        $('#cadComponent').on('change', function() {
            const selectedOption = $(this).find('option:selected');
            if (selectedOption.val()) {
                $('#cadQty').val(selectedOption.data('units-assy') || 1);
            }
        });

        // Обработчики форм
        $('#ndtForm').on('submit', function(e) {
            e.preventDefault();

            const selectedComponent = $('#ndtComponent option:selected');
            if (!selectedComponent.val()) {
                alert('Пожалуйста, выберите компонент');
                return;
            }

            const data = {
                component_id: selectedComponent.val(),
                ipl_num: selectedComponent.data('ipl-num'),
                part_number: selectedComponent.data('part-number'),
                description: selectedComponent.data('description'),
                process: $('#ndtProcess').val(),
                qty: parseInt($('#ndtQty').val()),
                _token: $('meta[name="csrf-token"]').attr('content')
            };

            $.post(`/admin/${workorderId}/ndt-cad-csv/add-ndt`, data).done(function(response) {
                if (response.success) {
                    $('#ndtModal').modal('hide');
                    location.reload();
                } else {
                    alert('Ошибка: ' + response.message);
                }
            });
        });

        $('#cadForm').on('submit', function(e) {
            e.preventDefault();

            const selectedComponent = $('#cadComponent option:selected');
            if (!selectedComponent.val()) {
                alert('Пожалуйста, выберите компонент');
                return;
            }

            if (!$('#cadProcess').val()) {
                alert('Пожалуйста, выберите процесс');
                return;
            }

            const data = {
                component_id: selectedComponent.val(),
                ipl_num: selectedComponent.data('ipl-num'),
                part_number: selectedComponent.data('part-number'),
                description: selectedComponent.data('description'),
                process: $('#cadProcess').val(),
                qty: parseInt($('#cadQty').val()),
                _token: $('meta[name="csrf-token"]').attr('content')
            };

            $.post(`/admin/${workorderId}/ndt-cad-csv/add-cad`, data).done(function(response) {
                if (response.success) {
                    $('#cadModal').modal('hide');
                    location.reload();
                } else {
                    alert('Ошибка: ' + response.message);
                }
            });
        });

        $('#csvImportForm').on('submit', function(e) {
            e.preventDefault();
            const formData = new FormData();
            formData.append('type', $('#csvType').val());
            formData.append('csv_file', $('#csvFile')[0].files[0]);
            formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

            $.ajax({
                url: `/admin/${workorderId}/ndt-cad-csv/import`,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        $('#csvImportModal').modal('hide');
                        alert(`Успешно импортировано ${response.count} компонентов`);
                        location.reload();
                    } else {
                        alert('Ошибка: ' + response.message);
                    }
                }
            });
        });
    } else {
        // Если jQuery еще не загружен, ждем
        setTimeout(initializeWhenReady, 100);
    }
}

// Запускаем инициализацию
initializeWhenReady();

// Проверяем, что функции определены
console.log('showAddNdtModal defined:', typeof window.showAddNdtModal);
console.log('showAddCadModal defined:', typeof window.showAddCadModal);

function loadComponents() {
    $.get(`/admin/${workorderId}/ndt-cad-csv/components`)
        .done(function(response) {
            if (response.success) {
                allComponents = response.components;
                updateComponentDropdowns();
            }
        })
        .fail(function(xhr) {
            console.error('Ошибка загрузки компонентов:', xhr.responseText);
        });
}

function loadCadProcesses() {
    $.get(`/admin/${workorderId}/ndt-cad-csv/cad-processes`)
        .done(function(response) {
            if (response.success) {
                cadProcesses = response.processes;
                updateCadProcessDropdown();
            }
        })
        .fail(function(xhr) {
            console.error('Ошибка загрузки CAD процессов:', xhr.responseText);
        });
}

function updateComponentDropdowns() {
    // Сортируем компоненты по ipl_num
    const sortedComponents = allComponents.sort(function(a, b) {
        return a.ipl_num.localeCompare(b.ipl_num, undefined, {numeric: true, sensitivity: 'base'});
    });
    
    // Обновляем NDT dropdown
    $('#ndtComponent').empty().append('<option value="">Выберите компонент...</option>');
    sortedComponents.forEach(function(component) {
        $('#ndtComponent').append(`<option value="${component.id}" data-ipl-num="${component.ipl_num}" data-part-number="${component.part_number}" data-description="${component.name}" data-units-assy="${component.units_assy}">${component.ipl_num} : ${component.part_number} - ${component.name}</option>`);
    });
    
    // Обновляем CAD dropdown
    $('#cadComponent').empty().append('<option value="">Выберите компонент...</option>');
    sortedComponents.forEach(function(component) {
        $('#cadComponent').append(`<option value="${component.id}" data-ipl-num="${component.ipl_num}" data-part-number="${component.part_number}" data-description="${component.name}" data-units-assy="${component.units_assy}">${component.ipl_num} : ${component.part_number} - ${component.name}</option>`);
    });
    
    // Обновляем Select2 если он инициализирован
    if (typeof $.fn.select2 !== 'undefined') {
        $('#ndtComponent').trigger('change.select2');
        $('#cadComponent').trigger('change.select2');
    }
}

function updateCadProcessDropdown() {
    $('#cadProcess').empty().append('<option value="">Выберите процесс...</option>');
    cadProcesses.forEach(function(process) {
        $('#cadProcess').append(`<option value="${process.process}">${process.process}</option>`);
    });

    // Обновляем Select2 если он инициализирован
    if (typeof $.fn.select2 !== 'undefined') {
        $('#cadProcess').trigger('change.select2');
    }
}

// Функции перенесены в глобальную область видимости в конце файла

// Обработчики форм перенесены в initializeWhenReady()
</script>

@endsection

<script>
// Определяем остальные функции в глобальной области видимости
window.removeNdtComponent = function(index) {
    console.log('Удаление NDT компонента с индексом:', index);
    console.log('Текущие NDT компоненты:', ndtComponents);

    if (confirm('Вы уверены, что хотите удалить этот компонент?')) {
        if (typeof $ !== 'undefined') {
            $.post(`/admin/${workorderId}/ndt-cad-csv/remove-ndt`, {
                index: index,
                _token: $('meta[name="csrf-token"]').attr('content')
            }).done(function(response) {
                console.log('Ответ сервера:', response);
                if (response.success) {
                    location.reload();
                } else {
                    alert('Ошибка: ' + response.message);
                }
            }).fail(function(xhr) {
                console.error('Ошибка AJAX:', xhr.responseText);
                alert('Ошибка при удалении компонента');
            });
        } else {
            console.log('jQuery not loaded yet, using fallback');
            // Fallback без jQuery - просто перезагружаем страницу
            location.reload();
        }
    }
};

window.removeCadComponent = function(index) {
    console.log('Удаление CAD компонента с индексом:', index);
    console.log('Текущие CAD компоненты:', cadComponents);

    if (confirm('Вы уверены, что хотите удалить этот компонент?')) {
        if (typeof $ !== 'undefined') {
            $.post(`/admin/${workorderId}/ndt-cad-csv/remove-cad`, {
                index: index,
                _token: $('meta[name="csrf-token"]').attr('content')
            }).done(function(response) {
                console.log('Ответ сервера:', response);
                if (response.success) {
                    location.reload();
                } else {
                    alert('Ошибка: ' + response.message);
                }
            }).fail(function(xhr) {
                console.error('Ошибка AJAX:', xhr.responseText);
                alert('Ошибка при удалении компонента');
            });
        } else {
            console.log('jQuery not loaded yet, using fallback');
            // Fallback без jQuery - просто перезагружаем страницу
            location.reload();
        }
    }
};

window.reloadFromManual = function(type) {
    if (confirm(`Вы уверены, что хотите перезагрузить ${type.toUpperCase()} компоненты из Manual CSV? Это заменит все существующие данные.`)) {
        if (typeof $ !== 'undefined') {
            $.post(`/admin/${workorderId}/ndt-cad-csv/reload-from-manual`, {
                type: type,
                _token: $('meta[name="csrf-token"]').attr('content')
            }).done(function(response) {
                if (response.success) {
                    alert(`Успешно перезагружено ${response.count} компонентов`);
                    location.reload();
                } else {
                    alert('Ошибка: ' + response.message);
                }
            });
        } else {
            console.log('jQuery not loaded yet, using fallback');
            // Fallback без jQuery - просто перезагружаем страницу
            location.reload();
        }
    }
};

window.forceLoadFromManual = function(type) {
    if (confirm(`Принудительно загрузить ${type.toUpperCase()} компоненты из Manual CSV?`)) {
        if (typeof $ !== 'undefined') {
            $.post(`/admin/${workorderId}/ndt-cad-csv/force-load-from-manual`, {
                type: type,
                _token: $('meta[name="csrf-token"]').attr('content')
            }).done(function(response) {
                if (response.success) {
                    alert(`Успешно загружено ${response.count} компонентов`);
                    location.reload();
                } else {
                    alert('Ошибка: ' + response.message);
                }
            });
        } else {
            console.log('jQuery not loaded yet, using fallback');
            // Fallback без jQuery - просто перезагружаем страницу
            location.reload();
        }
    }
};

window.importNdtFromCsv = function() {
    if (typeof $ !== 'undefined') {
        $('#csvType').val('ndt');
        $('#csvImportModal').modal('show');
    } else {
        console.log('jQuery not loaded yet, using fallback');
        document.getElementById('csvType').value = 'ndt';
        document.getElementById('csvImportModal').style.display = 'block';
        document.getElementById('csvImportModal').classList.add('show');
    }
};

window.importCadFromCsv = function() {
    if (typeof $ !== 'undefined') {
        $('#csvType').val('cad');
        $('#csvImportModal').modal('show');
    } else {
        console.log('jQuery not loaded yet, using fallback');
        document.getElementById('csvType').value = 'cad';
        document.getElementById('csvImportModal').style.display = 'block';
        document.getElementById('csvImportModal').classList.add('show');
    }
};

console.log('All global functions defined');
</script>
