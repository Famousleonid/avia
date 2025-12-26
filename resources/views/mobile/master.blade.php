<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="{{asset('img/favicon.webp')}}" type="image/png">
    <link rel="manifest" href="{{asset('manifest.json')}}">
    <meta name="keywords" content="avia, repair">
    <meta name="robots" content="none"> <!-- Выключение поисковых роботов  -->
    {{--<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"> --}}
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Aviatechnik') }}</title>
    <link rel="stylesheet" href="{{asset('assets/Bootstrap 5/bootstrap.min.css')}}">
    <link href="{{asset('assets/Bootstrap 5/bootstrap-icons.css')}}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css"/>
    <link href="{{asset('assets/select2/css/select2.min.css')}}" rel="stylesheet"/>
    <link rel="stylesheet" href="{{asset('css/main.css')}}">

    <script>
        (function () {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-bs-theme', 'dark');
        })();

        (function () {
            const gtId = sessionStorage.getItem('restore_gt');
            if (!gtId) return;
            document.documentElement.dataset.restoreGt = gtId;
        })();


    </script>

    @include('components.status')
    @yield('style')

    <style>
        html, body {
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
            background-color: #343A40;
        }

        .app-container {
            display: flex;
            flex-direction: column;
            height: 100vh;
        }

        .app-header {
            flex-shrink: 0; /* Запрет меню сжиматься */
        }

        .app-content {
            flex-grow: 1; /* Растягивается на все оставшееся место */
            overflow-y: auto; /* прокрутку ТОЛЬКО для контента, если он не помещается */
            min-height: 0; /* правильная работы flex-grow */
        }
    </style>

</head>
<body class="fade-page">
<div class="app-container">

    <div id="spinner-load" class=" spinner-border text-warning spinner-win" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>

    <div class="app-header">
        @include('components.mobile-menu', ['position'   => 'top','workorder'  => $workorder ?? null,])
    </div>

    <main class="app-content">
        @yield('content')
    </main>

    <div style="height: 10px;"></div>

</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="{{asset('assets/Bootstrap 5/bootstrap.bundle.min.js')}}"></script>
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>
<script src="{{asset('assets/select2/js/select2.min.js')}}"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="{{ asset('js/main.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const gtId = document.documentElement.dataset.restoreGt;
        const taskId = sessionStorage.getItem('restore_task');
        if (!gtId) return;

        const collapse = document.getElementById(`gt-col-${gtId}`);
        if (collapse) {
            // отключаем анимацию на один раз
            collapse.classList.add('no-collapse-anim');

            // делаем "открыто" сразу (как будто уже открыто)
            collapse.classList.add('show');
            if (typeof initDatePickers === 'function') initDatePickers();
            collapse.setAttribute('aria-expanded', 'true');

            // кнопку тоже в правильное состояние
            const btn = document.querySelector(`[data-bs-target="#gt-col-${gtId}"]`);
            if (btn) btn.classList.remove('collapsed');

            // на всякий: убираем transition после кадра
            requestAnimationFrame(() => {
                collapse.classList.remove('no-collapse-anim');
                if (typeof initDatePickers === 'function') initDatePickers();
            });
        }

        // скроллим к нужной задаче после того как блок уже открыт
        if (taskId) {
            requestAnimationFrame(() => {
                const el = document.getElementById(`task-${taskId}`);
                if (typeof initDatePickers === 'function') initDatePickers();
                if (el) el.scrollIntoView({ block: 'center', behavior: 'auto' });
                sessionStorage.removeItem('restore_gt');
                sessionStorage.removeItem('restore_task');
            });
        }
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const gtId   = sessionStorage.getItem('restore_gt');
        const taskId = sessionStorage.getItem('restore_task');

        if (!gtId || !taskId) return;

        // раскрываем нужный GT
        const collapseEl = document.getElementById(`gt-col-${gtId}`);
        if (collapseEl) {
            const bs = bootstrap.Collapse.getOrCreateInstance(collapseEl, { toggle: false });
            bs.show();
        }

        // после раскрытия — скроллим к task (без дёргания)
        setTimeout(() => {
            const el = document.getElementById(`task-${taskId}`);
            if (el) el.scrollIntoView({ block: 'center', behavior: 'auto' });

            sessionStorage.removeItem('restore_gt');
            sessionStorage.removeItem('restore_task');
            sessionStorage.removeItem('restore_scroll');
        }, 150);
    });

    function formatWo(number) {
        return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
    }

    function initAfterLoad() {
        hideLoadingSpinner();


        // сюда добавить общий код для всех моб.страниц общие слушатели и т.д

    }
    window.safeShowSpinner = function () {
        if (typeof showLoadingSpinner === 'function') showLoadingSpinner();
        else {
            const el = document.querySelector('#spinner-load');
            if (el) el.classList.remove('d-none');
        }
    };

    window.safeHideSpinner = function () {
        if (typeof hideLoadingSpinner === 'function') hideLoadingSpinner();
        else {
            const el = document.querySelector('#spinner-load');
            if (el) el.classList.add('d-none');
        }
    };

    function initDatePickers() {
        if (typeof flatpickr === 'undefined') return;

        document.querySelectorAll('input[data-fp]').forEach(src => {
            if (src._flatpickr) return;

            flatpickr(src, {
                altInput: true,
                altFormat: "d.m.Y",
                dateFormat: "Y-m-d",
                allowInput: true,
                disableMobile: true,

                onChange(selectedDates, dateStr, instance) {
                    const alt  = instance.altInput;
                    const wrap = alt ? alt.closest('.date-field') : null;

                    if (wrap) {
                        // src.value = "Y-m-d" или пусто
                        if (src.value) wrap.classList.add('has-finish');
                        else wrap.classList.remove('has-finish');
                    }

                    const form = src.closest('form');
                    if (!form) return;
                    // 1) сохранить какой GT открыт + какой task + scroll
                    const gtId   = src.dataset.gt;
                    const taskId = src.dataset.task;

                    if (gtId && taskId) {
                        sessionStorage.setItem('restore_gt', gtId);
                        sessionStorage.setItem('restore_task', taskId);
                    }
                    sessionStorage.setItem('restore_scroll', String(window.scrollY));
                    safeShowSpinner();
                    if (form.requestSubmit) form.requestSubmit();
                    else form.submit();
                },

                onReady(selectedDates, dateStr, instance) {
                    instance.altInput.classList.add('form-control','form-control-sm','w-100','fp-alt');
                    src.style.display = 'none';

                    const wrap = instance.altInput.closest('.date-field');
                    if (wrap) wrap.classList.toggle('has-finish', !!src.value);
                },

            });
        });

        document.body.classList.add('fp-ready');
    }


    window.addEventListener('load', initAfterLoad);
</script>

@yield('scripts')


</body>
</html>

