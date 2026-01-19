@extends('mobile.master')

@section('style')

    <style>
        html, body {
            padding: 0;
            margin: 0;
        }

        .gradient-pane {
            background: #343A40;
            color: #f8f9fa;
        }

        .component-list-wrapper {
            max-height: calc(100vh - 210px);
            overflow: auto;
        }

        .process-table {
            font-size: 0.7rem;
        }

        .process-table th,
        .process-table td {
            padding: 4px 6px;
            text-align: center;
            vertical-align: middle;
        }

        .process-table th:first-child,
        .process-table td:first-child {
            text-align: left;
        }

        .process-table input[type="date"] {
            font-size: 0.75rem;
            padding: 2px 4px;
            height: 28px;
        }
        /* --- date placeholder hack --- */
        .process-table input[type="date"]{
            position: relative;
        }

        /* Когда дата пустая — рисуем "..." */
        .process-table input[type="date"]:not([value]),
        .process-table input[type="date"][value=""]{
            color: transparent; /* прячем системный текст */
        }

        .process-table input[type="date"]:not([value])::before,
        .process-table input[type="date"][value=""]::before{
            content: "...";
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,.45);
            pointer-events: none;
        }

        /* --- force dark date input, even when filled --- */
        .process-table .date-input{
            background-color: #212529 !important; /* как table-dark */
            color: #f8f9fa !important;
            border-color: #495057 !important;
            box-shadow: none !important;
            -webkit-appearance: none;
            appearance: none;
        }

        /* убирает зеленые/желтые подсветки автозаполнения */
        .process-table input[type="date"].form-control:-webkit-autofill,
        .process-table input[type="date"].form-control:-webkit-autofill:hover,
        .process-table input[type="date"].form-control:-webkit-autofill:focus{
            -webkit-text-fill-color: #f8f9fa !important;
            -webkit-box-shadow: 0 0 0px 1000px #212529 inset !important;
            transition: background-color 9999s ease-out 0s;
        }

        .date-wrap{
            position: relative;
            width: 100%;
        }

        .date-wrap .fake-ph{
            position: absolute;
            z-index: 5;
            height: 28px;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,.45);
            pointer-events: none;
        }

        .date-wrap .date-input{
            background-color: #212529 !important;
            color: #f8f9fa !important;
            border-color: #495057 !important;
            box-shadow: none !important;
            -webkit-appearance: none;
            appearance: none;
        }

        /* когда есть дата — скрываем "..." */
        .date-wrap.has-value .fake-ph{
            display:none;
        }

        /* скрываем системный текст даты */
        .date-wrap .date-input{
            text-indent: -9999px;      /* 👈 уводит дд.мм.гггг */
        }

        /* когда есть значение — возвращаем текст */
        .date-wrap.has-value .date-input{
            text-indent: 0;
        }

        /* пусто — тёмный */
        .date-wrap .date-input{
            background-color: #212529 !important;
        }

        /* есть дата — зелёный */
        .date-wrap.has-value .date-input{
            background-color: #202F2D !important; /* bootstrap success */
            border-color: #198754 !important;
            color: #fff !important;
        }
        .process-table .date-wrap.has-value .date-input{
            background-color: #202F2D !important;   /* зелёный */
            border-color: #198754 !important;
            color: #ffffff !important;
        }

    </style>
@endsection

@section('content')

    <div class="container-fluid d-flex flex-column bg-dark p-0"
         style="min-height: calc(100vh - 80px); padding-top: 60px;">

        {{-- Блок информации о воркордере --}}
        <div id="block-info" class="rounded-3 border border-info gradient-pane shadow-sm" style="margin: 5px; padding: 3px;">
            <div class="d-flex  align-items-center w-100 fw-bold fs-2 ms-3">
                <div class="d-flex align-items-center">
                    @if(!$workorder->isDone())
                        <span class="text-info">W {{ $workorder->number }}</span>
                    @else
                        <span class="text-secondary">{{ $workorder->number }}</span>
                    @endif
                </div>
                <div class="d-flex align-items-center ms-3">
                    @if($workorder->approve_at)
                        <img src="{{ asset('img/ok.png') }}" width="20"
                             title="{{ $workorder->approve_at->format('d.m.Y') }} {{ $workorder->approve_name }}">
                    @else
                        <img src="{{ asset('img/icon_no.png') }}" width="12">
                    @endif
                </div>
                <div class="d-flex align-items-center ms-auto ">
                    @if($workorder->open_at)
                        <span class="text-secondary fw-normal fs-6 me-4">Open at: {{ $workorder->open_at->format('d-M-Y') }}</span>
                    @else
                        <span class="text-secondary fw-normal fs-6 me-4">Open at: - null - </span>
                    @endif
                </div>
            </div>
        </div>

        <hr class="border-secondary opacity-50 my-2">

        <div class="row g-0 flex-grow-1" style="background-color:#343A40;">
            <div class="col-12 p-0">
                <div class="bg-dark py-1 px-3 d-flex justify-content-between align-items-center border-bottom mt-1">
                    <div class="d-flex align-items-center gap-2">
                        <h6 class="mb-0 text-primary">{{ __('Parts') }}</h6>
                        <span class="text-info">({{ $components->count() }})</span>
                    </div>
                </div>

                @if($components->isEmpty())
                    <div class="text-center text-muted small py-3">
                        {{ __('COMPONENTS NOT CREATED') }}
                    </div>
                @else
                    {{-- Список компонентов + мини-таблицы процессов --}}
                    <div class="list-group list-group-flush component-list-wrapper">
                        @foreach($components as $component)
                            @if(!$component) @continue @endif
                            <div class="list-group-item bg-transparent text-light border-secondary">

                                {{-- Шапка компонента: картинка + инфа --}}
                                <div class="d-flex align-items-center gap-2">
                                    <div class="flex-shrink-0">
                                        <a href="{{ $component->getFirstMediaBigUrl('components') }}"
                                           data-fancybox="component-{{ $component->id }}">
                                            <img class="rounded-circle"
                                                 src="{{ $component->getFirstMediaThumbnailUrl('components') }}"
                                                 alt="Img" width="40" height="40">
                                        </a>
                                    </div>

                                    <div class="flex-grow-1">
                                        <div class="fw-semibold text-info">
                                            {{ $component->name ?? ('#'.$component->id) }}
                                        </div>

                                        <div class="small text-muted">
                                            <span class="me-2"><span class="text-secondary">IPL:</span>{{ $component->ipl_num ?? '—' }}</span>
                                            <span class="me-2"><span class="text-secondary">P/N:</span>{{ $component->part_number ?? '—' }}</span>
                                        </div>

                                    </div>
                                </div>

                                @php
                                    $allProcesses = $component->processesForWorkorder ?? collect();
                                @endphp

                                @if($allProcesses->isNotEmpty())
                                    <div class="mt-2 ps-2">
                                        <table class="table table-sm table-dark table-bordered mb-2 align-middle process-table">
                                            <thead>
                                            <tr>
                                                <th style="width:40%;">
                                                    <div class="fw-semibold text-info">
                                                        Processes
                                                    </div>
                                                </th>
                                                <th style="width:30%; text-align: center"
                                                    class="fw-normal text-muted">
                                                    Sent (edit)
                                                </th>
                                                <th style="width:30%; text-align: center"
                                                    class="fw-normal text-muted">
                                                    Returned (edit)
                                                </th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($allProcesses as $pr)
                                                <tr>
                                                    <td class="text-start">
                                                        {{ $pr->processName->name ?? '—' }}
                                                    </td>
                                                    <td>
                                                        <form method="POST"
                                                              action="{{ route('tdrprocesses.updateDate', $pr) }}"
                                                              class="auto-submit-form">
                                                            @csrf
                                                            @method('PATCH')
                                                            <div class="date-wrap">
                                                                <input type="date"
                                                                       name="date_start"
                                                                       class="form-control form-control-sm date-input"
                                                                       value="{{ $pr->date_start?->format('Y-m-d') }}">
                                                                <span class="fake-ph">...</span>
                                                            </div>
                                                        </form>
                                                    </td>
                                                    <td>
                                                        <form method="POST"
                                                              action="{{ route('tdrprocesses.updateDate', $pr) }}"
                                                              class="auto-submit-form">
                                                            @csrf
                                                            @method('PATCH')
                                                            <div class="date-wrap">
                                                                <input type="date"
                                                                       name="date_finish"
                                                                       class="form-control form-control-sm date-input"
                                                                       value="{{ $pr->date_finish?->format('Y-m-d') }}">
                                                                <span class="fake-ph">...</span>
                                                            </div>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="mt-2 ps-2 small text-muted">
                                        No processes for parts on this workorder.
                                    </div>
                                @endif

                            </div>
                        @endforeach
                    </div>
                @endif

            </div>
        </div>
    </div>

@endsection

@section('scripts')
    <script>

        Fancybox.bind('[data-fancybox^="component-"]', {
            Toolbar: ["zoom", "fullscreen", "close"],
            dragToClose: false,
            showClass: "fancybox-fadeIn",
            hideClass: "fancybox-fadeOut"
        });


        // Автосабмит дат процессов (Sent / Returned)
        document.addEventListener('change', function (e) {
            const input = e.target;
            const form = input.closest('.auto-submit-form');
            if (form) {
                form.submit();
            }
        });

        // Показ/скрытие поля Bush IPL Number
        function toggleBushIPL() {
            const isBushCheckbox = document.getElementById('is_bush');
            const bushIPLContainer = document.getElementById('bush_ipl_container');
            const bushIPLInput = document.getElementById('bush_ipl_num');

            if (isBushCheckbox.checked) {
                bushIPLContainer.style.display = 'block';
                bushIPLInput.required = true;
            } else {
                bushIPLContainer.style.display = 'none';
                bushIPLInput.required = false;
                bushIPLInput.value = '';
            }
        }

        window.toggleBushIPL = toggleBushIPL;

        function syncDatePlaceholders() {
            document.querySelectorAll('.date-wrap').forEach(wrap => {
                const inp = wrap.querySelector('.date-input');
                if (!inp) return;
                wrap.classList.toggle('has-value', !!inp.value);
            });
        }

        document.addEventListener('DOMContentLoaded', syncDatePlaceholders);
        syncDatePlaceholders();

        // у тебя уже есть auto-submit по change — туда добавь syncDatePlaceholders()
        document.addEventListener('change', function (e) {
            if (e.target.classList.contains('date-input')) {
                syncDatePlaceholders();
            }
        });


    </script>
@endsection
