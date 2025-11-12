@extends('admin.master')

@section('content')
    <style>
        .container {
            max-width: 850px;
        }

        /* ----------------------------------- Select 2 Dark Theme -------------------------------------*/

        html[data-bs-theme="dark"] .select2-selection--single {
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
    </style>

    <div class="container mt-3">
        <div class="card bg-gradient">
            <div class="card-header">
                <div class="d-flex justify-content-between">
                    <h4 class="text-primary">{{__('Edit Extra Component')}}</h4>
                    <h4 class="text-primary"> {{__('Work Order')}} {{$current_wo->number}}</h4>
                </div>
            </div>
            <div class="card-body">
                <form id="editComponentForm" role="form" method="POST" action="{{route('extra_processes.update_component', $extra_process->id)}}" class="editComponentForm">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="workorder_id" value="{{$current_wo->id }}">

                    <div class="form-group d-flex mb-3">
                        <label for="i_component_id" class="form-label pe-2">Component</label>

                        <select name="component_id" id="i_component_id" class="form-control" style="width: 300px" required>
                            <option value="">---</option>
                            @foreach($components as $component)
                                <option value="{{ $component->id }}"
                                        data-has_assy="{{ $component->assy_part_number ? 'true' : 'false' }}"
                                        data-title="{{ $component->name }}"
                                        {{ $extra_process->component_id == $component->id ? 'selected' : '' }}>
                                    {{ $component->ipl_num }} : {{ $component->part_number }} - {{ $component->name }}
                                </option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-link" data-bs-toggle="modal"
                                data-bs-target="#addComponentModal">{{ __('Add Component') }}
                        </button>
                    </div>

                    <div class="form-group mb-3">
                        <div class="d-flex justify-content-around">
                            <div>
                                <label for="serial_num" class="form-label">Serial Number</label>
                                <input type="text" name="serial_num" id="serial_num" class="form-control" style="width: 250px" value="{{ $extra_process->serial_num ?? '' }}">
                            </div>
                            <div>
                                <label for="qty" class="form-label">Quantity</label>
                                <input type="number" name="qty" id="qty" class="form-control" style="width: 100px" value="{{ $extra_process->qty ?? 1 }}"
                                       min="1" required>
                            </div>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-outline-primary mt-3">{{ __('Save') }}</button>
                        <a href="{{ route('extra_processes.show_all', ['id' => $current_wo->id]) }}"
                           class="btn btn-outline-secondary mt-3">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal - Add component -->
    <div class="modal fade" id="addComponentModal" tabindex="-1" aria-labelledby="addComponentModalLabel"
         aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content bg-gradient">
                <div class="modal-header">
                    <h5 class="modal-title" id="addComponentModalLabel">{{ __('Add Component') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <form action="{{ route('components.storeFromExtra') }}" method="POST" id="addComponentForm">
                    @csrf

                    <div class="modal-body">
                        <input type="hidden" name="manual_id" value="{{$current_wo->unit->manual_id}}">
                        <input type="hidden" name="current_wo" value="{{$current_wo->id}}">
                        <div class="form-group">
                            <label for="name">{{ __('Name') }}</label>
                            <input id='name' type="text" class="form-control" name="name" required>
                        </div>
                        <div class="d-flex">

                            <div class="d-flex">
                                <div class="m-3">
                                    <div class="">
                                        <label for="ipl_num">{{ __('IPL Number') }}</label>
                                        <input id='ipl_num' type="text" class="form-control" name="ipl_num"
                                               pattern="^\d+-\d+[A-Za-z]?$"
                                               title="The format should be: number-number (for example: 1-200A, 1001-100, 5-398B)"
                                               required>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-12 mt-2">
                                        <div class="form-group">
                                            <strong>{{__('Image:')}}</strong>
                                            <input type="file" name="img" class="form-control" placeholder="Image">
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <label for="part_number">{{ __('Part Number') }}</label>
                                        <input id='part_number' type="text" class="form-control"
                                               name="part_number" required>
                                    </div>

                                </div>

                                <div class="m-3">
                                    <div class="">
                                        <label for="assy_ipl_num">{{ __('Assembly IPL Number') }}</label>
                                        <input id='assy_ipl_num' type="text" class="form-control" name="assy_ipl_num"
                                               pattern="^\d+-\d+[A-Za-z]?$"
                                               title="The format should be: number-number (for example: 1-200A, 1001-100, 5-398B)"
                                        >
                                    </div>

                                    <div class=" col-xs-12 col-sm-12 col-md-12 mt-2" >
                                        <div class="form-group">
                                            <strong>{{__(' Assy Image:')}}</strong>
                                            <input type="file" name="assy_img" class="form-control" placeholder="Image">
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <label for="assy_part_number">{{ __(' Assembly Part Number') }}</label>
                                        <input id='assy_part_number' type="text" class="form-control"
                                               name="assy_part_number" >
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox"  id="log_card" name="log_card">
                                <label class="form-check-label" for="log_card">
                                    Log Card
                                </label>
                            </div>
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">Save Component</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        // Инициализация select2 для выбора компонента
        $(document).ready(function() {
            // Инициализация select2 для выбора компонента
            $('#i_component_id').select2({
                placeholder: 'Select a component',
                allowClear: true,
                width: '100%',
                language: {
                    noResults: function() {
                        return "No components found";
                    }
                }
            });
        });

        // Обработка отправки формы
        document.getElementById('editComponentForm').addEventListener('submit', function (event) {
            event.preventDefault();

            const componentId = document.querySelector('select[name="component_id"]').value;
            const serial_num = document.querySelector('input[name="serial_num"]').value;
            const qty = document.querySelector('input[name="qty"]').value;

            if (!componentId) {
                alert('Please select a component.');
                return;
            }

            const formData = new FormData();
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
            formData.append('_method', 'PUT');
            formData.append('component_id', componentId);
            formData.append('serial_num', serial_num);
            formData.append('qty', qty);

            const requestBody = {
                component_id: componentId,
                serial_num: serial_num,
                qty: qty
            };

            fetch(`{{ route('extra_processes.update_component', $extra_process->id) }}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-HTTP-Method-Override': 'PUT'
                },
                body: JSON.stringify(requestBody)
            })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(errorData => {
                            throw new Error(errorData.message || `HTTP ${response.status}: ${response.statusText}`);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.message) {
                        alert(data.message);
                    }
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error updating component: ' + error.message);
                });
        });

        // Обработка успешного добавления компонента
        document.getElementById('addComponentForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch("{{ route('components.storeFromExtra') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: formData
            })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(errorData => {
                            throw new Error(errorData.message || `HTTP ${response.status}: ${response.statusText}`);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Закрываем модальное окно
                        const modalEl = document.getElementById('addComponentModal');
                        const modalInstance = bootstrap.Modal.getInstance(modalEl);
                        modalInstance.hide();

                        // Показываем сообщение об успехе
                        alert('Component created successfully!');

                        // Перезагружаем страницу для обновления списка компонентов
                        window.location.reload();
                    } else {
                        alert(data.message || 'Error creating component');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error creating component: ' + error.message);
                });
        });
    </script>
@endsection

