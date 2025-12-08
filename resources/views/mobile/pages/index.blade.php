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
            padding-bottom: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.6);
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
            padding: 0 8px;
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

        .wo-list-wrapper {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            padding-bottom: 20px;

            margin-top: 60px !important;   /* 60 (меню) + ~70–80 (поиск) – подгони по факту */
        }

        .wo-item {
            display: block;
            width: 100%;
            padding: 18px 12px;

            border: 1px solid whitesmoke;
            border-radius: 6px;

            background: #111;
            text-align: center;

            font-weight: 700;
            font-size: clamp(1.8rem, 4vw, 3rem);

            color: #0DDDFD;
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
    </style>
@endsection



@section('content')
    <div class="container-fluid d-flex flex-column mobile-page p-2">

        <div class="search-bar p-2">

            <div class="search-wrapper p-2 position-relative">

    <span class="search-icon">
        <i class="bi bi-search"></i>
    </span>

                <input type="text"
                       id="searchWorkorder"
                       class="form-control form-control-sm search-input search-input-with-icon"
                       placeholder="Search workorder number...">

                <button type="button"
                        id="clearSearch"
                        class="btn btn-sm text-secondary position-absolute top-50 end-0 translate-middle-y me-3 px-1 py-0"
                        style="display:none; background:none; border:none;">
                    <i class="bi bi-x-circle" style="font-size: 1.1rem;"></i>
                </button>

            </div>

        </div>

        <div class="wo-list-wrapper">
            @if($workorders->count())
                @foreach($workorders as $workorder)
                    <a href="#"
                       class="wo-item js-wo-item {{ $workorder->isDone() ? 'text-secondary' : 'text-info' }}"
                       data-id="{{ $workorder->id }}"
                       data-number="{{ $workorder->number }}">
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
            const items = document.querySelectorAll('.js-wo-item');

            const clearBtn = document.getElementById('clearSearch');

            input.addEventListener('input', () => {
                const value = input.value.trim().toLowerCase();

                // показать/скрыть крестик
                clearBtn.style.display = value ? 'block' : 'none';

                // фильтр списка
                items.forEach(item => {
                    const number = (item.dataset.number || '').toLowerCase();
                    item.style.display = number.includes(value) ? 'block' : 'none';
                });
            });

            clearBtn.addEventListener('click', () => {
                input.value = '';
                clearBtn.style.display = 'none';

                // вернуть список
                items.forEach(item => item.style.display = 'block');

                input.focus();
            });





            // Шаблон ссылки
            const showUrlTemplate = "{{ route('mobile.show', ['workorder' => '__ID__']) }}";

            document.querySelectorAll('.js-wo-item').forEach(item => {
                const raw = item.dataset.number;
                item.textContent = formatWo(raw);
            });

            // Фильтрация
            input.addEventListener('input', () => {
                const value = input.value.trim().toLowerCase();

                items.forEach(item => {
                    const number = (item.dataset.number || '').toLowerCase();
                    item.style.display = number.includes(value) ? 'block' : 'none';
                });
            });

            // Переход на воркордер
            items.forEach(item => {
                item.addEventListener('click', (e) => {
                    e.preventDefault();
                    const id = item.dataset.id;
                    if (!id) return;

                    const url = showUrlTemplate.replace('__ID__', id);
                    window.location.href = url;
                });
            });

        });
    </script>
@endsection
