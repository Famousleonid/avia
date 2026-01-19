@extends('mobile.master')

@section('style')
    <style>
        .mobile-page {
            min-height: 100vh;
            background: #000;
        }

        .search-bar {
            position: fixed;
            top: 60px;
            left: 0;
            right: 0;
            z-index: 1000;
            background: #000;
            padding: 20px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.6);
        }

        .search-input {
            border: 2px solid #444;
            background: #111;
            color: #fff;
        }

        .search-input:focus {
            background: #111;
            color: #fff;
            border-color: #0d6efd;
            box-shadow: none;
        }

        .search-wrapper {
            position: relative;
        }

        .search-icon {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.2rem;
            pointer-events: none;
            color: #bbb;
        }

        .search-input-with-icon {
            padding-left: 30px;
        }

        /* список ниже фиксированной полосы поиска */
        .wo-list-wrapper {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            padding-bottom: 20px;

            margin-top: 70px !important; /* 60 меню + ~60 панель поиска с чекбоксами */
        }

        .wo-item {
            display: block;
            width: 100%;
            padding: 18px 12px;

            border: 1px solid #0DCAF0;
            border-radius: 6px;

            background: #343A40;
            text-align: center;

            font-weight: 700;
            font-size: clamp(1.8rem, 4vw, 3rem);

            color: #0DCAF0;
            text-decoration: none;

            margin-bottom: 10px;
        }

        .wo-item:active {
            background: #09203f;
        }

        .wo-empty {
            color: #777;
            font-size: 0.9rem;
            text-align: center;
            margin-top: 20px;
        }

        .filter-label {
            font-size: 0.75rem;
            color: #ccc;
        }

        .big-check {
            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 0.95rem;   /* крупнее текст */
            font-weight: 600;     /* жирнее */
            color: #fff;          /* белый текст */
            column-gap: 6px;      /* расстояние между квадратом и текстом */
        }

        .big-check .form-check-input {
            width: 1.3rem;        /* размер квадрата */
            height: 1.3rem;       /* размер квадрата */
            margin: 0;            /* убираем стандартный вверхний отступ */
        }
        .wo-item[data-draft="1"] {
            border-color: #ffc107;
            color: #ffc107 !important;
        }

    </style>
@endsection

@section('content')
    <div class="container-fluid d-flex flex-column mobile-page p-2">

        <div class="search-bar">
            <div class="d-flex align-items-center w-100">

                {{-- SEARCH 40% --}}
                <div class="position-relative search-wrapper" style="flex: 0 0 40%;">
                    <span class="search-icon">
                        <i class="bi bi-search"></i>
                    </span>

                    <input type="text"
                           id="searchWorkorder"
                           class="form-control form-control-sm search-input search-input-with-icon"
                           placeholder="Search WO...">

                    <button type="button"
                            id="clearSearch"
                            class="btn btn-sm text-secondary position-absolute top-50 end-0 translate-middle-y me-2 px-1 py-0"
                            style="display:none; background:none; border:none;">
                        <i class="bi bi-x-circle" style="font-size: 1.1rem;"></i>
                    </button>
                </div>
                {{-- чекбокс All – 20% --}}
                <label class="form-check-label big-check d-flex align-items-center justify-content-center"
                       style="flex: 0 0 20%;">
                    <input class="form-check-input m-0" type="checkbox" id="showAllWo">
                    All
                </label>

                {{-- чекбокс Done – 20% --}}
                <label class="form-check-label big-check d-flex align-items-center justify-content-center"
                       style="flex: 0 0 20%;">
                    <input class="form-check-input m-0" type="checkbox" id="showDoneWo">
                    Done
                </label>
                @roles('Shipping|Manager|Admin')
                    <label class="form-check-label big-check d-flex align-items-center justify-content-center "
                           style="flex: 0 0 20%;">
                        <input class="form-check-input m-0" type="checkbox" id="showOnlyDraft">
                        Draft
                    </label>
                @endroles
            </div>
        </div>



        <div class="wo-list-wrapper">
            @if($workorders->count())
                @foreach($workorders as $workorder)
                    <a href="#"
                       class="wo-item js-wo-item {{ $workorder->isDone() ? 'text-secondary' : 'text-info' }}"
                       data-id="{{ $workorder->id }}"
                       data-number="{{ $workorder->number }}"
                       data-own="{{ $workorder->user_id == $userId ? 1 : 0 }}"
                       data-done="{{ $workorder->isDone() ? 1 : 0 }}"
                       data-draft="{{ $workorder->is_draft ? 1 : 0 }}">
                        {{ $workorder->number }}

                    </a>
                @endforeach
            @else
                <div class="wo-empty">No workorders found</div>
            @endif
        </div>

    </div>
@endsection

@section('scripts')

    <script>
        document.addEventListener('DOMContentLoaded', () => {

            const input = document.getElementById('searchWorkorder');
            const clearBtn = document.getElementById('clearSearch');
            const items = document.querySelectorAll('.js-wo-item');

            const cbShowAll = document.getElementById('showAllWo');
            const cbShowDone = document.getElementById('showDoneWo');
            const cbOnlyDraft = document.getElementById('showOnlyDraft');

            // Шаблон ссылки
            const showUrlTemplate = "{{ route('mobile.show', ['workorder' => '__ID__']) }}";

            // Форматируем номера (XXX XXX)
            items.forEach(item => {
                const raw = item.dataset.number;
                item.textContent = (typeof formatWo === 'function') ? formatWo(raw) : raw;
            });

            // ОДНА функция, которая применяет все фильтры
            function applyFilters() {
                const searchValue = (input?.value || '').trim().toLowerCase();
                const showAll = !!cbShowAll?.checked;
                const showDone = !!cbShowDone?.checked;
                const onlyDraft = !!cbOnlyDraft?.checked;

                items.forEach(item => {
                    const number = (item.dataset.number || '').toLowerCase();
                    const isOwn = item.dataset.own === '1';
                    const isDone = item.dataset.done === '1';
                    const isDraft = item.dataset.draft === '1';

                    let visible = true;

                    // 0) Draft режим:
                    // - если включен Only Draft -> показываем ТОЛЬКО draft
                    // - если выключен -> draft вообще НЕ показываем
                    if (onlyDraft) {
                        if (!isDraft) visible = false;
                    } else {
                        if (isDraft) visible = false;
                    }

                    // 1) поиск по номеру
                    if (visible && searchValue && !number.includes(searchValue)) {
                        visible = false;
                    }

                    // 2) свои / все
                    if (visible && !showAll && !isOwn) {
                        visible = false;
                    }

                    // 3) Done / не Done
                    if (visible && !showDone && isDone) {
                        visible = false;
                    }

                    item.style.display = visible ? 'block' : 'none';
                });
            }


            // применяем сразу
            applyFilters();

            // --- события ---

            // ввод в поиск
            input?.addEventListener('input', () => {
                const value = input.value.trim();
                if (clearBtn) clearBtn.style.display = value ? 'block' : 'none';
                applyFilters();
            });

            // очистка поиска
            clearBtn?.addEventListener('click', () => {
                input.value = '';
                clearBtn.style.display = 'none';
                input.focus();
                applyFilters();
            });

            // чекбоксы фильтров
            cbShowAll?.addEventListener('change', applyFilters);
            cbShowDone?.addEventListener('change', applyFilters);
            cbOnlyDraft?.addEventListener('change', applyFilters);

            // Переход на воркордер
            items.forEach(item => {
                item.addEventListener('click', (e) => {
                    e.preventDefault();
                    const id = item.dataset.id;
                    if (!id) return;

                    if (typeof hapticTap === 'function') hapticTap(10);
                    if (typeof safeShowSpinner === 'function') safeShowSpinner();

                    const url = showUrlTemplate.replace('__ID__', id);
                    window.location.href = url;
                });
            });



        });
    </script>


@endsection
