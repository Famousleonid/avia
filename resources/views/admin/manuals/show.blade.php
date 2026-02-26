@extends('admin.master')

@section('content')
    <style>
        /* Общие настройки таблиц */
        .table{
            align-content: center;
        }
        /* Фиксированная раскладка — ширины колонок берутся из th/col/CSS */
        #nav-components .table,
        #nav-parts .table,
        #nav-processes .table {
            table-layout: fixed;
        }

        /* Ширина таблицы во вкладке Components */
        #nav-components .table {
            width: 100%;
        }
        /* Колонки Components: # | Components PN | EFF Code | Action */
        #nav-components .table th:nth-child(1),
        #nav-components .table td:nth-child(1) { width: 50px; }
        #nav-components .table th:nth-child(2),
        #nav-components .table td:nth-child(2) { width: 200px; }
        #nav-components .table th:nth-child(3),
        #nav-components .table td:nth-child(3) { width: 80px; }
        #nav-components .table th:nth-child(4),
        #nav-components .table td:nth-child(4) { width: 100px; }

        /* Ширина таблицы во вкладке Parts */
        #nav-parts .table {
            width: 100%;
        }
        /* Колонки Parts: IPL Number | ASSy IPL | Part Number | ASSy Part Number | Name | QTY | Name | Action */
        #nav-parts .table th:nth-child(1),
        #nav-parts .table td:nth-child(1) { width: 110px; }
        #nav-parts .table th:nth-child(2),
        #nav-parts .table td:nth-child(2) { width: 130px; }
        #nav-parts .table th:nth-child(3),
        #nav-parts .table td:nth-child(3) { width: 140px; }
        #nav-parts .table th:nth-child(4),
        #nav-parts .table td:nth-child(4) { width: 140px; }
        #nav-parts .table th:nth-child(5),
        #nav-parts .table td:nth-child(5) { width: 350px; }
        #nav-parts .table th:nth-child(6),
        #nav-parts .table td:nth-child(6) { width: 70px; }
        #nav-parts .table th:nth-child(7),
        #nav-parts .table td:nth-child(7) { width: 100px; }
        #nav-parts .table th:nth-child(8),
        #nav-parts .table td:nth-child(8) { width: 100px; }

        #nav-processes .table {
            width: 100%;
        }
        #nav-processes .table th:nth-child(1),
        #nav-processes .table td:nth-child(1) { width: 60px; }
        #nav-processes .table th:nth-child(2),
        #nav-processes .table td:nth-child(2) { width: 170px; }
        #nav-processes .table th:nth-child(3),
        #nav-processes .table td:nth-child(3) { width: 560px; }
        #nav-processes .table th:nth-child(4),
        #nav-processes .table td:nth-child(4) { width: 110px; }

        .card shadow {
            max-width: 1200px;
        }

        .card-header{
            display: flex;
        }
        .card-header h5{
            font-size: 12px;
        }
        .card-body{
            height: 80vh;
            /*overflow-y: auto;*/
            /*overflow-x: hidden;*/
            font-size: 14px;

        }
        .card .btn i.bi {
            font-size: 14px;
        }

        /* Parts tab table: fixed header + scrollable body */
        #nav-parts .parts-table-container {
            height: 70vh;
            overflow: auto;
            font-size: 12px;


        }

        #nav-parts table thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            font-size: 12px;
            color: grey;
        }
        #nav-components .component-table-container {
            height: 70vh;
            overflow: auto;
            font-size: 12px;



        }

        .badge{
            font-size: 14px;
        }

        #nav-components table thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            font-size: 12px;
            color: grey;

        }
        #nav-processes .process-table-container {
            height: 70vh;
            overflow: auto;
            font-size: 12px;



        }

        #nav-processes table thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            font-size: 12px;
            color: grey;

        }
        #nav-std .std-table-container {
            max-height: 70vh;
            overflow: auto;
            font-size: 12px;
        }
        #nav-std table thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            font-size: 12px;
            color: grey;
        }
        /* Просмотр STD CSV в модалке: прокрутка + фиксированный заголовок */
        .std-csv-view-table-wrap {
            max-height: 70vh;
            overflow: auto;
        }
        .std-csv-view-thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            /*background: var(--bs-light) !important;*/
            background: #c2c2d9 !important;
            white-space: nowrap;
            font-size: 12px;
        }
        #nav-tab .nav-link:focus,
        #nav-tab .nav-link:focus-visible {
            outline: none;
            box-shadow: none;
        }

    </style>
    <div class="card shadow">
        <div class="card-header m-2 ">
            <div class="me-2 d-flex ">
                <a href="{{ $cmm->getFirstMediaBigUrl('manuals') }}" data-fancybox="gallery">
                    <img class="rounded-circle" src="{{ $cmm->getFirstMediaThumbnailUrl('manuals') }}" width="40" height="40"
                         alt="Image"/>
                </a>

                <div class="ms-3">
                    <h5 class="ms-2 "><strong class="text-secondary">{{__('CMM:')}}</strong> {{ $cmm->number }}</h5>
                    <h5 class="ms-2"><strong class="text-secondary">{{__('Description:')}}</strong> {{ $cmm->title }}</h5>
                </div>
            </div>
            <div class="ms-3">
                <h5 class="ms-2"><strong class="text-secondary">{{__('Component PNs:')}}</strong> {{ $cmm->unit_name_training }}</h5>
                <div class="d-flex">
                    <h5 class="ms-2"><strong class="text-secondary">{{__('Revision Date:')}}</strong> {{ $cmm->revision_date }}</h5>
                        <h5 class="ms-4"><strong class="text-secondary">{{__('Lib:')}}</strong> {{ $cmm->lib }}</h5>
                </div>
            </div>
            <div class="ms-3 me-5">
                <h5 class="ms-2"><strong class="text-secondary">{{__('AirCraft Type:')}}</strong>
                        @foreach($planes as $plane)
                            @if($plane->id == $cmm->planes_id )
                                {{$plane->type}}
                            @endif
                        @endforeach
                </h5>
                <h5 class="ms-2"><strong class="text-secondary">{{__('MFR:')}}</strong>
                        @foreach($builders as $builder)
                            @if($builder->id == $cmm->builders_id )
                                {{$builder->name}}
                            @endif
                        @endforeach
                </h5>
            </div>
        </div>

        <div class="card-body">
            <nav>
                <div class="d-flex justify-content-between align-items-center">
                    <div class="nav nav-tabs" id="nav-tab" role="tablist">
                        <button class="nav-link active" id="nav-components-tab" data-bs-toggle="tab" data-bs-target="#nav-components"
                                type="button" role="tab" aria-controls="nav-components" aria-selected="true">Components</button>
                        <button class="nav-link" id="nav-parts-tab" data-bs-toggle="tab" data-bs-target="#nav-parts"
                                type="button" role="tab" aria-controls="nav-parts" aria-selected="false">Parts</button>
                        <button class="nav-link" id="nav-processes-tab" data-bs-toggle="tab" data-bs-target="#nav-processes"
                                type="button" role="tab" aria-controls="nav-processes" aria-selected="false">Processes</button>
                        <button class="nav-link" id="nav-std-tab" data-bs-toggle="tab" data-bs-target="#nav-std"
                                type="button" role="tab" aria-controls="nav-std" aria-selected="false">STD Processes</button>
                    </div>
                    <div class="ms-3 d-flex align-items-center gap-2" id="nav-tab-actions">
                        <button type="button"
                                class="btn btn-outline-primary btn-sm"
                                data-tab-target="#nav-components"
                                data-bs-toggle="modal"
                                data-bs-target="#addUnitModal">
                            {{ __('Add Component') }}
                        </button>
                        <div class="d-none" data-tab-target="#nav-parts">
                            <input type="text" style="width: 260px"
                                   id="parts-search"
                                   class="form-control form-control-sm"
                                   placeholder="Search parts...">
                        </div>
                        <a href="{{ route('components.create', ['manual_id' => $cmm->id, 'redirect' => request()->fullUrl().'#nav-parts']) }}"
                           class="btn btn-outline-primary btn-sm d-none"
                           data-tab-target="#nav-parts">
                            {{ __('Add Parts') }}
                        </a>
                        <button type="button"
                                class="btn btn-outline-success btn-sm d-none"
                                data-tab-target="#nav-parts"
                                data-bs-toggle="modal"
                                data-bs-target="#uploadCsvModal">
                            <i class="bi bi-upload"></i> {{__('Upload CSV')}}
                        </button>
                        <a href="{{ route('processes.create', ['manual_id' => $cmm->id, 'return_to' => route('manuals.show', $cmm) . '#nav-processes']) }}"
                           class="btn btn-outline-primary btn-sm d-none"
                           data-tab-target="#nav-processes">
                            {{ __('Add Process') }}
                        </a>
                        <button type="button" class="btn btn-outline-primary btn-sm d-none"
                                data-tab-target="#nav-std"
                                data-bs-toggle="modal"
                                data-bs-target="#stdCsvUploadModal">
                            <i class="fas fa-upload"></i> {{__('Add CSV Files')}}
                        </button>
                    </div>
                </div>
            </nav>
            <div class="tab-content" id="nav-tabContent">
                <div class="tab-pane  justify-content-start fade show active" id="nav-components" role="tabpanel"
                     aria-labelledby="nav-components-tab" tabindex="0">
                    <div class=" component-table-container m-2">
                        <table class="table table-hover table-bordered">
                            <thead class="bg-gradient">
                            <tr>
                                <th class="text-center bg-gradient" scope="col">#</th>
                                <th class="text-center bg-gradient" scope="col">Components PN</th>
                                <th class="text-center bg-gradient" scope="col">EFF Code</th>
                                <th class="text-center bg-gradient" scope="col">Action</th>
                            </tr>
                            </thead>
                            <tbody class="text-center ">
                            @php
                            $i=1
                            @endphp

                            @foreach($units as $u)
                            <tr>
                                <td class="align-content-center">{{$i++}}</td>
                                <td class="align-content-center @if(!$u->verified) text-danger fw-bold @endif">
                                    {{$u->part_number}}
                                </td>
                                <td class="align-content-center"> {{$u->eff_code}}</td>
                                <td class="align-content-center">
                                    <button type="button"
                                            class="btn btn-outline-primary btn-sm btn-edit-unit"
                                            data-unit-id="{{ $u->id }}"
                                            data-unit-part-number="{{ $u->part_number }}"
                                            data-unit-eff-code="{{ $u->eff_code }}"
                                            data-unit-verified="{{ $u->verified ? 1 : 0 }}"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editUnitModal">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <form action="{{ route('units.destroySingle', $u->id) }}"
                                          method="POST"
                                          style="display:inline-block;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="btn btn-outline-danger btn-sm"
                                                onclick="return confirm('Вы уверены, что хотите удалить этот компонент?');">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>


                </div>
                <div class="tab-pane fade" id="nav-parts" role="tabpanel" aria-labelledby="nav-parts-tab" tabindex="0">
                    <div class="parts-table-container m-2">
                        <table class="table table-hover table-bordered">
                            <thead class="bg-gradient">
                            <tr>
                                <th class="text-center bg-gradient align-content-center" >IPL Number</th>
                                <th class="text-center bg-gradient align-content-center" > ASSy IPL Number</th>
                                <th class="text-center bg-gradient align-content-center" >Part Number</th>
                                <th class="text-center bg-gradient align-content-center" > ASSy Part Number</th>
                                <th class="text-center bg-gradient align-content-center" >Name</th>
                                <th class="text-center bg-gradient align-content-center" >QTY </th>
                                <th class="text-center bg-gradient align-content-center" >Image</th>
                                <th class="text-center bg-gradient align-content-center" >Action</th>
                            </tr>
                            </thead>
                            <tbody class="text-center" >
                            @foreach($parts as $p)
                                <tr>
                                    <td class="align-content-center">{{$p->ipl_num}}</td>
                                    <td class="align-content-center"> {{$p->assy_ipl_num}} </td>
                                    <td class="align-content-center"> {{$p->part_number}} </td>
                                    <td class="align-content-center" >{{$p->assy_part_number}} </td>
                                    <td class="align-content-center text-start ps-4">{{$p->name}} </td>
                                    <td class="align-content-center">{{$p->units_assy}} </td>
                                    <td class="align-content-center">
                                        @if($p->getMedia('components')->isNotEmpty())
                                            <a href="{{ $p->getFirstMediaBigUrl('components') }}" data-fancybox="gallery">
                                                <img class="rounded-circle" src="{{ $p->getFirstMediaThumbnailUrl('components') }}" width="40" height="40" alt="IMG"/>
                                            </a>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button type="button"
                                                class="btn btn-outline-primary btn-sm btn-edit-part"
                                                data-part-id="{{ $p->id }}"
                                                data-ipl-num="{{ $p->ipl_num }}"
                                                data-assy-ipl-num="{{ $p->assy_ipl_num ?? '' }}"
                                                data-part-number="{{ $p->part_number }}"
                                                data-assy-part-number="{{ $p->assy_part_number ?? '' }}"
                                                data-name="{{ $p->name }}"
                                                data-units-assy="{{ $p->units_assy ?? '' }}"
                                                data-eff-code="{{ $p->eff_code ?? '' }}"
                                                data-log-card="{{ $p->log_card ? '1' : '0' }}"
                                                data-repair="{{ $p->repair ? '1' : '0' }}"
                                                data-is-bush="{{ $p->is_bush ? '1' : '0' }}"
                                                data-bush-ipl-num="{{ $p->bush_ipl_num ?? '' }}"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editPartModal">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <form action="{{ route('components.destroy', $p->id) }}"
                                              method="POST"
                                              style="display:inline-block;"
                                              class="form-destroy-part">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="redirect" value="{{ request()->fullUrl().'#nav-parts' }}">
                                            <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Вы уверены, что хотите удалить эту запчасть (part)?');">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                        </div>

                </div>
                <div class="tab-pane fade" id="nav-processes" role="tabpanel" aria-labelledby="nav-processes-tab" tabindex="0">
                    <div class="process-table-container m-2">
                        <table class="table table-hover table-bordered ">
                            <thead class="bg-gradient">
                            <tr>
                                <th class="text-center bg-gradient" scope="col">#</th>
                                <th class="text-center bg-gradient" scope="col">Process Name</th>
                                <th class="text-center bg-gradient" scope="col">Processes</th>
                                <th class="text-center bg-gradient" scope="col">Action</th>
                            </tr>
                            </thead>
                            <tbody class="text-center">
                            @php $i=1 @endphp
                            @foreach($manualProcesses as $mp)
                                <tr >
                                    <td class="align-content-center">{{$i++}}</td>
                                    <td class="align-content-center"> {{$mp->process->process_name->name}} </td>
                                    <td class="align-content-center text-start ps-3"> {{$mp->process->process}} </td>
                                    <td class="align-content-center">
                                        <a href="{{ route('manual_processes.edit', $mp) }}?return_to={{ urlencode(route('manuals.show', $cmm) . '#nav-processes') }}" class="btn btn-outline-primary btn-sm" title="{{ __('Edit') }}">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <form action="{{ route('manual_processes.destroy', $mp) }}?return_to={{ urlencode(route('manuals.show', $cmm) . '#nav-processes') }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Are you sure you want to delete this process?') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="return_to" value="{{ route('manuals.show', $cmm) . '#nav-processes' }}">
                                            <button type="submit" class="btn btn-outline-danger btn-sm" title="{{ __('Delete') }}">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>


                </div>
                <div class="tab-pane fade" id="nav-std" role="tabpanel" aria-labelledby="nav-std-tab" tabindex="0">
                    <div class="std-table-container m-2">
                        @php
                            $stdProcessTypes = ['ndt', 'cad', 'stress', 'paint'];
                            $stdCsvFiles = $cmm->getMedia('csv_files')->filter(function ($m) use ($stdProcessTypes) {
                                return in_array($m->getCustomProperty('process_type'), $stdProcessTypes, true);
                            });
                        @endphp
                        <table class="table table-hover table-bordered">
                            <thead class="bg-gradient">
                            <tr>
                                <th class="text-center bg-gradient" scope="col">#</th>
                                <th class="text-center bg-gradient" scope="col">{{ __('File name') }}</th>
                                <th class="text-center bg-gradient" scope="col">{{ __('Process Type') }}</th>
                                <th class="text-center bg-gradient" scope="col">{{ __('Action') }}</th>
                            </tr>
                            </thead>
                            <tbody class="text-center" id="std-csv-tbody">
                            @php $stdIdx = 1; @endphp
                            @foreach($stdCsvFiles as $csvFile)
                                <tr data-process-type="{{ $csvFile->getCustomProperty('process_type') }}">
                                    <td class="align-content-center">{{ $stdIdx++ }}</td>
                                    <td class="align-content-center">{{ $csvFile->file_name }}</td>
                                    <td class="align-content-center">
                                        <span class="badge bg-secondary">{{ $csvFile->getCustomProperty('process_type') ?: '—' }}</span>
                                    </td>
                                    <td class="align-content-center">
                                        <button type="button" class="btn btn-sm btn-outline-info me-1 std-csv-view-btn"
                                                data-file-id="{{ $csvFile->id }}"
                                                data-file-name="{{ $csvFile->file_name }}">
                                            <i class="bi bi-view-list"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                                onclick="deleteStdCsvFile('{{ route('manuals.csv.delete', ['manual' => $cmm->id, 'file' => $csvFile->id]) }}', this)">
                                            <i class="bi bi-trash"></i>
{{--                                            {{ __('Delete') }}--}}
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                            @if($stdCsvFiles->isEmpty())
                                <tr id="std-csv-empty-row">
                                    <td colspan="4" class="text-muted">{{ __('No STD process files. Use "Add CSV Files" to upload NDT, CAD, Stress Relief or Paint CSV.') }}</td>
                                </tr>
                            @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>


        </div>

    </div>

    {{-- Модальное окно редактирования Part (Component) — все поля как в Add Parts --}}
    <div class="modal fade" id="editPartModal" tabindex="-1" aria-labelledby="editPartModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editPartModalLabel">{{ __('Edit Part') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editPartForm">
                        <div class="mb-3">
                            <label for="edit-part-name" class="form-label">{{ __('Name') }}</label>
                            <input type="text" class="form-control" id="edit-part-name" name="name" required>
                        </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit-part-ipl-num" class="form-label">{{ __('IPL Number') }}</label>
                                <input type="text" class="form-control" id="edit-part-ipl-num" name="ipl_num"
                                       pattern="^\d+-\d+[A-Za-z]?$"
                                       title="Формат: число-число (например 1-200A)" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('Image') }}</label>
                                <input type="file" class="form-control" name="img" accept="image/*">
                            </div>
                            <div class="mb-3">
                                <label for="edit-part-part-number" class="form-label">{{ __('Part Number') }}</label>
                                <input type="text" class="form-control" id="edit-part-part-number" name="part_number" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit-part-eff-code" class="form-label">{{ __('EFF Code') }}</label>
                                <input type="text" class="form-control" id="edit-part-eff-code" name="eff_code" placeholder="{{ __('optional') }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit-part-assy-ipl-num" class="form-label">{{ __('Assembly IPL Number') }}</label>
                                <input type="text" class="form-control" id="edit-part-assy-ipl-num" name="assy_ipl_num"
                                       pattern="^$|^\d+-\d+[A-Za-z]?$"
                                       title="Формат: число-число или пусто">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('Assy Image') }}</label>
                                <input type="file" class="form-control" name="assy_img" accept="image/*">
                            </div>
                            <div class="mb-3">
                                <label for="edit-part-assy-part-number" class="form-label">{{ __('Assembly Part Number') }}</label>
                                <input type="text" class="form-control" id="edit-part-assy-part-number" name="assy_part_number">
                            </div>
                            <div class="mb-3">
                                <label for="edit-part-units-assy" class="form-label">{{ __('Units per Assy') }}</label>
                                <input type="text" class="form-control" id="edit-part-units-assy" name="units_assy">
                            </div>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-3 mt-2">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit-part-log-card" name="log_card" value="1">
                            <label class="form-check-label" for="edit-part-log-card">{{ __('Log Card') }}</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit-part-repair" name="repair" value="1">
                            <label class="form-check-label" for="edit-part-repair">{{ __('Repair') }}</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit-part-is-bush" name="is_bush" value="1">
                            <label class="form-check-label" for="edit-part-is-bush">{{ __('Is Bush') }}</label>
                        </div>
                        <div class="form-group" id="edit-part-bush-ipl-container" style="display: none;">
                            <label for="edit-part-bush-ipl-num" class="form-label me-2">{{ __('Initial Bushing IPL Number') }}</label>
                            <input type="text" class="form-control d-inline-block" id="edit-part-bush-ipl-num" name="bush_ipl_num"
                                   pattern="^\d+-\d+[A-Za-z]?$" style="width: 120px;">
                        </div>
                    </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                    <button type="button" class="btn btn-primary" id="modal-btn-update-part" data-part-id="">{{ __('Save') }}</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Модальное окно загрузки STD Processes CSV (NDT, CAD, Stress, Paint) --}}
    <div class="modal fade" id="stdCsvUploadModal" tabindex="-1" aria-labelledby="stdCsvUploadModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="stdCsvUploadModalLabel">{{ __('Add STD Process CSV') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="stdCsvUploadForm" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label for="stdCsvProcessType" class="form-label">{{ __('Process Type') }}</label>
                            <select id="stdCsvProcessType" name="process_type" class="form-control" required>
                                <option value="">{{ __('Select Process Type') }}</option>
                                <option value="ndt">{{ __('NDT') }}</option>
                                <option value="cad">{{ __('CAD') }}</option>
                                <option value="stress">{{ __('Stress Relief') }}</option>
                                <option value="paint">{{ __('Paint') }}</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="stdCsvFileInput" class="form-label">{{ __('CSV File') }}</label>
                            <input type="file" id="stdCsvFileInput" name="csv_file" class="form-control" accept=".csv,.txt" required>
                            <small class="text-muted">{{ __('Select CSV or TXT file') }}</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="button" class="btn btn-primary" id="btn-std-csv-upload">{{ __('Upload') }}</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Модальное окно просмотра STD Process CSV (в том же окне, прокрутка, фиксированный заголовок) --}}
    <div class="modal fade" id="stdCsvViewModal" tabindex="-1" aria-labelledby="stdCsvViewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="stdCsvViewModalLabel">{{ __('STD Process file') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div id="stdCsvViewLoading" class="text-center py-5 text-muted">{{ __('Loading...') }}</div>
                    <div id="stdCsvViewTableWrap" class="std-csv-view-table-wrap" style="display: none;">
                        <table class="table table-bordered table-striped table-sm mb-0">
                            <thead class="table-light std-csv-view-thead">
                                <tr id="stdCsvViewTheadRow"></tr>
                            </thead>
                            <tbody id="stdCsvViewTbody"></tbody>
                        </table>
                    </div>
                    <div id="stdCsvViewError" class="alert alert-danger m-3" style="display: none;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="uploadCsvModal" tabindex="-1" aria-labelledby="uploadCsvModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadCsvModalLabel">
                        {{__('Upload Parts CSV')}}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <form action="{{ route('components.upload-csv') }}" method="POST" enctype="multipart/form-data" id="csvUploadForm">
                                @csrf

                                {{-- фиксируем manual --}}
                                <input type="hidden" name="manual_id" value="{{ $cmm->id }}">

                                {{-- КУДА вернуться после успешной загрузки --}}
                                <input type="hidden" name="redirect"
                                       value="{{ request()->fullUrl().'#nav-parts' }}">

                                <div class="mb-3">
                                    <label class="form-label">{{__('CMM')}}</label>
                                    <input type="text" class="form-control"
                                           value="{{ $cmm->number }} - {{ $cmm->title }}"
                                           disabled>
                                </div>
                                <div class="mb-3">
                                    <label for="csv_file" class="form-label">{{__('Select CSV File')}}</label>
                                    <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv" required>
                                </div>

                                <div class="mb-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-upload"></i> {{__('Upload Parts')}}
                                    </button>
                                </div>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">{{__('CSV Format Requirements')}}</h6>
                                </div>
                                <div class="card-body">
                                    <p class="small text-muted mb-2">{{__('Your CSV file should have the following columns:')}}</p>
                                    <ul class="small text-muted">
                                        <li><strong>part_number</strong> - {{__('Part number (required)')}}</li>
                                        <li><strong>assy_part_number</strong> - {{__('Assembly part number (optional)')}}</li>
                                        <li><strong>name</strong> - {{__('Part name (required)')}}</li>
                                        <li><strong>ipl_num</strong> - {{__('IPL number (required)')}}</li>
                                        <li><strong>assy_ipl_num</strong> - {{__('Assembly IPL number (optional)')}}</li>
                                        <li><strong>log_card</strong> - {{__('Log card (0 or 1, optional)')}}</li>
                                        <li><strong>repair</strong> - {{__('Repair flag (0 or 1, optional)')}}</li>
                                        <li><strong>is_bush</strong> - {{__('Is bushing (0 or 1, optional)')}}</li>
                                        <li><strong>bush_ipl_num</strong> - {{__('Bushing IPL number (optional)')}}</li>
                                    </ul>
                                    <div class="alert alert-info mt-3 mb-0">
                                        <small><i class="bi bi-info-circle"></i> <strong>{{__('Note:')}}</strong> {{__
                                            ('Exact duplicate parts will be automatically skipped. Multiple components with the
                                            same part_number but different IPL numbers are allowed in the same manual. Uploaded CSV files will be saved and can be viewed later.')}}</small>
                                    </div>
                                    <div class="mt-2">
                                        <a href="{{ route('components.download-csv-template') }}" class="btn btn-outline-secondary btn-sm">
                                            <i class="bi bi-download"></i> {{__('Download Template')}}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // STD Processes CSV: просмотр в модалке (то же окно, прокрутка, фиксированный заголовок)
        function openStdCsvView(fileId, fileName) {
            var modal = document.getElementById('stdCsvViewModal');
            var titleEl = document.getElementById('stdCsvViewModalLabel');
            var loadingEl = document.getElementById('stdCsvViewLoading');
            var tableWrap = document.getElementById('stdCsvViewTableWrap');
            var errorEl = document.getElementById('stdCsvViewError');
            var theadRow = document.getElementById('stdCsvViewTheadRow');
            var tbody = document.getElementById('stdCsvViewTbody');

            if (titleEl) titleEl.textContent = fileName || '{{ __("STD Process file") }}';
            if (loadingEl) { loadingEl.style.display = 'block'; }
            if (tableWrap) { tableWrap.style.display = 'none'; }
            if (errorEl) { errorEl.style.display = 'none'; errorEl.textContent = ''; }
            if (theadRow) theadRow.innerHTML = '';
            if (tbody) tbody.innerHTML = '';

            var dataUrl = '{{ route("manuals.csv.data", ["manual" => $cmm->id, "file" => "__ID__"]) }}'.replace('__ID__', fileId);

            fetch(dataUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (loadingEl) loadingEl.style.display = 'none';
                    if (!data.success) {
                        if (errorEl) { errorEl.textContent = data.error || '{{ __("Error loading file") }}'; errorEl.style.display = 'block'; }
                        return;
                    }
                    var headers = data.headers || [];
                    var records = data.records || [];
                    headers.forEach(function (h) {
                        var th = document.createElement('th');
                        th.textContent = h;
                        th.scope = 'col';
                        if (theadRow) theadRow.appendChild(th);
                    });
                    records.forEach(function (row) {
                        var tr = document.createElement('tr');
                        row.forEach(function (cell) {
                            var td = document.createElement('td');
                            td.textContent = cell;
                            tr.appendChild(td);
                        });
                        if (tbody) tbody.appendChild(tr);
                    });
                    if (tableWrap) tableWrap.style.display = 'block';
                })
                .catch(function (err) {
                    if (loadingEl) loadingEl.style.display = 'none';
                    if (errorEl) { errorEl.textContent = err.message || '{{ __("Error loading file") }}'; errorEl.style.display = 'block'; }
                });

            (new bootstrap.Modal(modal)).show();
        }

        // STD Processes CSV: удаление файла
        function deleteStdCsvFile(url, buttonEl) {
            if (!confirm('{{ __("Are you sure you want to delete this file?") }}')) return;
            fetch(url, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
                .then(function (response) {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(function (data) {
                    if (data.success) {
                        var tbody = document.getElementById('std-csv-tbody');
                        var tr = buttonEl.closest('tr');
                        if (tr) tr.remove();
                        if (tbody && tbody.querySelectorAll('tr').length === 0) {
                            var emptyRow = document.createElement('tr');
                            emptyRow.id = 'std-csv-empty-row';
                            emptyRow.innerHTML = '<td colspan="4" class="text-muted">{{ __("No STD process files. Use \"Add CSV Files\" to upload NDT, CAD, Stress Relief or Paint CSV.") }}</td>';
                            tbody.appendChild(emptyRow);
                        }
                    } else {
                        throw new Error(data.error || '{{ __("Error deleting file") }}');
                    }
                })
                .catch(function (err) {
                    console.error(err);
                    showNotification(err.message || '{{ __("Error deleting file") }}', 'error');
                });
        }

        document.addEventListener('DOMContentLoaded', function () {
            // STD Processes CSV: загрузка файла
            // Просмотр STD CSV по клику (в т.ч. для динамически добавленных строк)
            document.addEventListener('click', function (e) {
                var btn = e.target.closest('.std-csv-view-btn');
                if (!btn) return;
                e.preventDefault();
                var fileId = btn.getAttribute('data-file-id');
                var fileName = btn.getAttribute('data-file-name') || '';
                if (fileId) openStdCsvView(fileId, fileName);
            });

            var btnStdCsvUpload = document.getElementById('btn-std-csv-upload');
            if (btnStdCsvUpload) {
                btnStdCsvUpload.addEventListener('click', function () {
                    var fileInput = document.getElementById('stdCsvFileInput');
                    var processTypeSelect = document.getElementById('stdCsvProcessType');
                    if (!fileInput || !fileInput.files.length) {
                        showNotification('{{ __("Please select a file") }}', 'warning');
                        return;
                    }
                    if (!processTypeSelect || !processTypeSelect.value) {
                        showNotification('{{ __("Please select a process type") }}', 'warning');
                        return;
                    }
                    var formData = new FormData();
                    formData.append('csv_file', fileInput.files[0]);
                    formData.append('process_type', processTypeSelect.value);
                    formData.append('_token', '{{ csrf_token() }}');

                    fetch('{{ route("manuals.csv.store", ["manual" => $cmm->id]) }}', {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                    })
                        .then(function (response) {
                            if (!response.ok) throw new Error('Network response was not ok');
                            return response.json();
                        })
                        .then(function (data) {
                            if (data.success && data.file) {
                                var tbody = document.getElementById('std-csv-tbody');
                                var emptyRow = document.getElementById('std-csv-empty-row');
                                if (emptyRow) emptyRow.remove();
                                var existing = tbody && tbody.querySelector('tr[data-process-type="' + data.file.process_type + '"]');
                                if (existing) existing.remove();
                                var count = tbody ? tbody.querySelectorAll('tr').length : 0;
                                var tr = document.createElement('tr');
                                tr.setAttribute('data-process-type', data.file.process_type);
                                var deleteUrl = '{{ route("manuals.csv.delete", ["manual" => $cmm->id, "file" => "__ID__"]) }}'.replace('__ID__', data.file.id);
                                var fileName = (data.file.name || '').replace(/"/g, '&quot;');
                                tr.innerHTML =
                                    '<td class="align-content-center">' + (count + 1) + '</td>' +
                                    '<td class="align-content-center">' + (data.file.name || '') + '</td>' +
                                    '<td class="align-content-center"><span class="badge bg-secondary">' + (data.file.process_type || '') + '</span></td>' +
                                    '<td class="align-content-center">' +
                                    '<button type="button" class="btn btn-sm btn-outline-info me-1 std-csv-view-btn" data-file-id="' + data.file.id + '" data-file-name="' + fileName + '"><i class="bi bi-view-list"></i></button> ' +
                                    '<button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteStdCsvFile(\'' + deleteUrl.replace(/'/g, "\\'") + '\', this)"><i class="bi bi-trash"></i></button>' +
                                    '</td>';
                                if (tbody) tbody.appendChild(tr);
                                document.getElementById('stdCsvUploadForm').reset();
                                var modal = bootstrap.Modal.getInstance(document.getElementById('stdCsvUploadModal'));
                                if (modal) modal.hide();
                                showNotification('{{ __("File uploaded successfully") }}', 'success');
                            } else {
                                throw new Error(data.error || '{{ __("Error uploading file") }}');
                            }
                        })
                        .catch(function (err) {
                            console.error(err);
                            showNotification(err.message || '{{ __("Error uploading file") }}', 'error');
                        });
                });
            }

            // Поиск по Parts
            const input = document.getElementById('parts-search');
            const table = document.querySelector('#nav-parts table');

            if (input && table) {
                const rows = table.querySelectorAll('tbody tr');

                input.addEventListener('input', function () {
                    const query = this.value.trim().toLowerCase();

                    rows.forEach(function (row) {
                        const text = row.innerText.toLowerCase();
                        row.style.display = text.includes(query) ? '' : 'none';
                    });
                });
            }

            // Переключение кнопок "Add ..." в навигации вкладок
            const navTabs = document.querySelectorAll('#nav-tab .nav-link');
            const actions = document.querySelectorAll('#nav-tab-actions [data-tab-target]');

            function updateTabActions(activeTarget) {
                actions.forEach(function (btn) {
                    const target = btn.getAttribute('data-tab-target');
                    btn.classList.toggle('d-none', target !== activeTarget);
                });
            }

            // Начальное состояние (активна вкладка Components)
            let activeTab = document.querySelector('#nav-tab .nav-link.active');

            // Если пришли с якорем (#nav-parts, #nav-processes, ...), переключаем вкладку
            const hash = window.location.hash;
            if (hash) {
                const targetTab = document.querySelector(`#nav-tab .nav-link[data-bs-target="${hash}"]`);
                if (targetTab) {
                    const tabInstance = new bootstrap.Tab(targetTab);
                    tabInstance.show();
                    activeTab = targetTab;
                }
            }

            if (activeTab) {
                updateTabActions(activeTab.getAttribute('data-bs-target'));
            }

            // Обновляем при переключении вкладок (Bootstrap event)
            navTabs.forEach(function (tab) {
                tab.addEventListener('shown.bs.tab', function (event) {
                    const target = event.target.getAttribute('data-bs-target');
                    updateTabActions(target);
                });
            });

            // Создание Unit (Component) через модальное окно + AJAX
            const modalAddUnitBtn = document.getElementById('modal-btn-add-unit');
            const modalPartInput  = document.getElementById('modal-unit-part-number');
            const modalEffInput   = document.getElementById('modal-unit-eff-code');

            if (modalAddUnitBtn && modalPartInput) {
                modalAddUnitBtn.addEventListener('click', function () {
                    const partNumber = modalPartInput.value.trim();
                    const effCode    = modalEffInput ? modalEffInput.value.trim() : null;
                    const manualId   = this.getAttribute('data-manual-id');

                    if (!partNumber) {
                        showNotification('Введите Component PN', 'warning');
                        return;
                    }

                    fetch('{{ route('units.store') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({
                            manual_id: manualId,
                            part_number: partNumber,
                            eff_code: effCode || null,
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data && data.id) {
                                // Закрываем модалку и обновляем страницу, чтобы увидеть новый компонент
                                const addUnitModalEl = document.getElementById('addUnitModal');
                                const modalInstance = bootstrap.Modal.getInstance(addUnitModalEl);
                                if (modalInstance) {
                                    modalInstance.hide();
                                }
                                location.reload();
                            } else {
                                showNotification('Ошибка при создании компонента', 'error');
                            }
                        })
                        .catch(() => showNotification('Server error', 'error'));
                });
            }

            // Редактирование Unit (Component) через модальное окно + AJAX
            const editButtons = document.querySelectorAll('.btn-edit-unit');
            const editPartInput = document.getElementById('edit-unit-part-number');
            const editEffInput  = document.getElementById('edit-unit-eff-code');
            const modalUpdateBtn = document.getElementById('modal-btn-update-unit');
            const editVerifiedCheckbox = document.getElementById('edit-unit-verified');

            editButtons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const unitId = this.getAttribute('data-unit-id');
                    const partNumber = this.getAttribute('data-unit-part-number') || '';
                    const effCode = this.getAttribute('data-unit-eff-code') || '';
                    const verified = this.getAttribute('data-unit-verified') === '1';

                    if (editPartInput) editPartInput.value = partNumber;
                    if (editEffInput)  editEffInput.value  = effCode;
                    if (editVerifiedCheckbox) editVerifiedCheckbox.checked = verified;
                    if (modalUpdateBtn) modalUpdateBtn.setAttribute('data-unit-id', unitId);
                });
            });

            if (modalUpdateBtn && editPartInput) {
                modalUpdateBtn.addEventListener('click', function () {
                    const unitId = this.getAttribute('data-unit-id');
                    const partNumber = editPartInput.value.trim();
                    const effCode    = editEffInput ? editEffInput.value.trim() : null;
                    const verified   = editVerifiedCheckbox && editVerifiedCheckbox.checked ? 1 : 0;

                    if (!partNumber) {
                        showNotification('Введите Component PN', 'warning');
                        return;
                    }

                    fetch('{{ url('/units') }}/' + unitId + '/single', {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({
                            part_number: partNumber,
                            eff_code: effCode || null,
                            verified: verified,
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data && data.success) {
                                const editUnitModalEl = document.getElementById('editUnitModal');
                                const modalInstance = bootstrap.Modal.getInstance(editUnitModalEl);
                                if (modalInstance) {
                                    modalInstance.hide();
                                }
                                location.reload();
                            } else {
                                showNotification('Ошибка при обновлении компонента', 'error');
                            }
                        })
                        .catch(() => showNotification('Server error', 'error'));
                });
            }

            // Редактирование Part (Component) — вкладка Parts (все поля как в Add Parts)
            const editPartButtons = document.querySelectorAll('.btn-edit-part');
            const editPartModalBtn = document.getElementById('modal-btn-update-part');
            const editPartForm = document.getElementById('editPartForm');
            const editPartBushContainer = document.getElementById('edit-part-bush-ipl-container');
            const editPartIsBush = document.getElementById('edit-part-is-bush');

            function setEditPartBushVisibility() {
                if (editPartBushContainer) {
                    editPartBushContainer.style.display = editPartIsBush && editPartIsBush.checked ? 'block' : 'none';
                }
            }

            if (editPartIsBush) {
                editPartIsBush.addEventListener('change', setEditPartBushVisibility);
            }

            editPartButtons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const id = this.getAttribute('data-part-id');
                    document.getElementById('edit-part-ipl-num').value = this.getAttribute('data-ipl-num') || '';
                    document.getElementById('edit-part-assy-ipl-num').value = this.getAttribute('data-assy-ipl-num') || '';
                    document.getElementById('edit-part-part-number').value = this.getAttribute('data-part-number') || '';
                    document.getElementById('edit-part-assy-part-number').value = this.getAttribute('data-assy-part-number') || '';
                    document.getElementById('edit-part-name').value = this.getAttribute('data-name') || '';
                    document.getElementById('edit-part-units-assy').value = this.getAttribute('data-units-assy') || '';
                    document.getElementById('edit-part-eff-code').value = this.getAttribute('data-eff-code') || '';
                    document.getElementById('edit-part-log-card').checked = this.getAttribute('data-log-card') === '1';
                    document.getElementById('edit-part-repair').checked = this.getAttribute('data-repair') === '1';
                    document.getElementById('edit-part-is-bush').checked = this.getAttribute('data-is-bush') === '1';
                    document.getElementById('edit-part-bush-ipl-num').value = this.getAttribute('data-bush-ipl-num') || '';
                    setEditPartBushVisibility();
                    if (editPartModalBtn) editPartModalBtn.setAttribute('data-part-id', id);
                });
            });

            if (editPartModalBtn && editPartForm) {
                editPartModalBtn.addEventListener('click', function () {
                    const partId = this.getAttribute('data-part-id');
                    const iplNum = document.getElementById('edit-part-ipl-num').value.trim();
                    const partNumber = document.getElementById('edit-part-part-number').value.trim();
                    const name = document.getElementById('edit-part-name').value.trim();
                    if (!iplNum || !partNumber || !name) {
                        showNotification('Заполните обязательные поля: IPL Number, Part Number, Name', 'warning');
                        return;
                    }
                    const formData = new FormData(editPartForm);
                    formData.append('_token', '{{ csrf_token() }}');
                    formData.append('_method', 'PATCH');
                    fetch('{{ url('/components') }}/' + partId + '/single', {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: formData,
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data && data.success) {
                                const modalEl = document.getElementById('editPartModal');
                                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                                if (modalInstance) modalInstance.hide();
                                location.reload();
                            } else {
                                showNotification(data && data.message ? data.message : 'Ошибка при обновлении запчасти', 'error');
                            }
                        })
                        .catch(function (err) {
                            showNotification('Ошибка при обновлении запчасти', 'error');
                        });
                });
            }
        });
    </script>

@endsection
