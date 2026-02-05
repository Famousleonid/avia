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
            width: 680px;
        }
        /* Колонки Components: # | Components PN | EFF Code | Action */
        #nav-components .table th:nth-child(1),
        #nav-components .table td:nth-child(1) { width: 50px; }
        #nav-components .table th:nth-child(2),
        #nav-components .table td:nth-child(2) { width: 220px; }
        #nav-components .table th:nth-child(3),
        #nav-components .table td:nth-child(3) { width: 120px; }
        #nav-components .table th:nth-child(4),
        #nav-components .table td:nth-child(4) { width: 120px; }

        /* Ширина таблицы во вкладке Parts */
        #nav-parts .table {
            width: 1200px;
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
        #nav-parts .table td:nth-child(5) { width: 220px; }
        #nav-parts .table th:nth-child(6),
        #nav-parts .table td:nth-child(6) { width: 70px; }
        #nav-parts .table th:nth-child(7),
        #nav-parts .table td:nth-child(7) { width: 220px; }
        #nav-parts .table th:nth-child(8),
        #nav-parts .table td:nth-child(8) { width: 100px; }

        #nav-processes .table {
            width: 900px;
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
        .card-body{
            height: 80vh;
            /*overflow-y: auto;*/
            /*overflow-x: hidden;*/
        }

        /* Parts tab table: fixed header + scrollable body */
        #nav-parts .parts-table-container {
            height: 60vh;
            overflow: auto;
        }

        #nav-parts table thead th {
            position: sticky;
            top: 0;
            z-index: 2;
        }
        #nav-components .component-table-container {
            height: 60vh;
            overflow: auto;
        }

        #nav-components table thead th {
            position: sticky;
            top: 0;
            z-index: 2;
        }
        #nav-processes .process-table-container {
            height: 60vh;
            overflow: auto;
        }

        #nav-processes table thead th {
            position: sticky;
            top: 0;
            z-index: 2;
        }

    </style>
    <div class="card shadow">
        <div class="card-header m-2 justify-content-between">
            <div class="me-2 d-flex ">
                <a href="{{ $cmm->getFirstMediaBigUrl('manuals') }}" data-fancybox="gallery">
                    <img class="rounded-circle" src="{{ $cmm->getFirstMediaThumbnailUrl('manuals') }}" width="60" height="60"
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
                <div class="nav nav-tabs" id="nav-tab" role="tablist">
                    <button class="nav-link active" id="nav-components-tab" data-bs-toggle="tab" data-bs-target="#nav-components"
                            type="button" role="tab" aria-controls="nav-components" aria-selected="true">Components</button>
                    <button class="nav-link" id="nav-parts-tab" data-bs-toggle="tab" data-bs-target="#nav-parts"
                            type="button" role="tab" aria-controls="nav-parts" aria-selected="false">Parts</button>
                    <button class="nav-link" id="nav-processes-tab" data-bs-toggle="tab" data-bs-target="#nav-processes"
                            type="button" role="tab" aria-controls="nav-processes" aria-selected="false">Processes</button>
                    <button class="nav-link" id="nav-disabled-tab" data-bs-toggle="tab" data-bs-target="#nav-disabled"
                            type="button" role="tab" aria-controls="nav-disabled" aria-selected="false" disabled>Disabled </button>
                </div>
            </nav>
            <div class="tab-content" id="nav-tabContent">
                <div class="tab-pane  justify-content-start fade show active" id="nav-components" role="tabpanel"
                     aria-labelledby="nav-home-tab"
                     tabindex="0">

                    <div class="m-2 text-end" style="width: 680px;">
                        <a href="#" class="btn btn-outline-primary " style="height: 40px">
                            {{__('Add Component')}}
                        </a>
                    </div>
                    <div class=" component-table-container">
                        <table class="table table-hover table-bordered">
                            <thead class="bg-gradient">
                            <tr>
                                <th class="text-center bg-gradient" scope="col">#</th>
                                <th class="text-center bg-gradient" scope="col">Components PN</th>
                                <th class="text-center bg-gradient" scope="col">EFF Code</th>
                                <th class="text-center bg-gradient" scope="col">Action</th>
                            </tr>
                            </thead>
                            <tbody class="text-center">
                            @php
                            $i=1
                            @endphp

                            @foreach($units as $u)
                            <tr>
                                <td>{{$i++}}</td>
                                <td> {{$u->part_number}} </td>
                                <td> {{$u->eff_code}}</td>
                                <td class="">
                                    <a href="#" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <form action="#" method="POST" style="display:inline-block;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Вы уверены, что хотите удалить этот компонент?');">
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
                <div class="tab-pane fade" id="nav-parts" role="tabpanel" aria-labelledby="nav-profile-tab" tabindex="0">
                    <div class="m-2">
                        <div class="mb-4 d-flex justify-content-between" style="width: 1200px;">
                            <input type="text" style="width: 300px"
                                   id="parts-search"
                                   class="form-control form-control-sm "
                                   placeholder="Search ...">
                            <a href="#" class="btn btn-outline-primary " style="height: 40px">
                                {{__('Add Parts')}}
                            </a>
                        </div>
                        <div class="parts-table-container">
                        <table class="table table-hover table-bordered">
                            <thead class="bg-gradient" style="height: 60px">
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
                                    <td>{{$p->ipl_num}}</td>
                                    <td> {{$p->assy_ipl_num}} </td>
                                    <td> {{$p->part_number}} </td>
                                    <td >{{$p->assy_part_number}} </td>
                                    <td>{{$p->name}} </td>
                                    <td>{{$p->units_assy}} </td>
                                    <td>
                                        @if($p->getMedia('components')->isNotEmpty())
                                            <a href="{{ $component->getFirstMediaBigUrl('components') }}" data-fancybox="gallery">
                                                <img class="rounded-circle" src="{{ $component->getFirstMediaThumbnailUrl('components') }}" width="40" height="40" alt="IMG"/>
                                            </a>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="#" class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <form action="#" method="POST" style="display:inline-block;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Вы уверены, что хотите удалить этот компонент?');">
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
                </div>
                <div class="tab-pane fade" id="nav-processes" role="tabpanel" aria-labelledby="nav-contact-tab" tabindex="0">
                    <div class="m-2 text-end" style="width: 900px;">
                        <a href="#" class="btn btn-outline-primary " style="height: 40px">
                            {{__('Add Process')}}
                        </a>
                    </div>
                    <div class=" process-table-container">
                        <table class="table table-hover table-bordered">
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
                                    <td> {{$mp->process->process}} </td>
                                    <td class="align-content-center">
                                        <a href="#" class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <form action="#" method="POST" style="display:inline-block;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Вы уверены, что хотите удалить этот компонент?');">
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

                <div class="tab-pane fade" id="nav-disabled" role="tabpanel" aria-labelledby="nav-disabled-tab" tabindex="0">
                    .4
                    .
                </div>
            </div>


        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const input = document.getElementById('parts-search');
            const table = document.querySelector('#nav-parts table');

            if (!input || !table) {
                return;
            }

            const rows = table.querySelectorAll('tbody tr');

            input.addEventListener('input', function () {
                const query = this.value.trim().toLowerCase();

                rows.forEach(function (row) {
                    const text = row.innerText.toLowerCase();
                    row.style.display = text.includes(query) ? '' : 'none';
                });
            });
        });
    </script>

@endsection
