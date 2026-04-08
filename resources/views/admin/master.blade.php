<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="icon" href="{{asset('img/favicon.webp')}}" type="image/png">
    <title>Admin page</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}"/>
    <link rel="stylesheet" href="{{asset('assets/Bootstrap 5/bootstrap.min.css')}}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link href="{{asset('assets/Bootstrap 5/bootstrap-icons.css')}}" rel="stylesheet">
    <link rel="stylesheet" href="{{asset('assets/jquery/jquery.fancybox.min.css')}}">
    <link href="{{asset('assets/select2/css/select2.min.css')}}" rel="stylesheet"/>
    <link rel="stylesheet" href="{{asset('css/custom_bootstrap.css')}}">
    <link rel="stylesheet" href="{{asset('css/main.css')}}">
    <link rel="stylesheet" href="{{ asset('css/paper-button.css') }}">
    <link rel="stylesheet" href="https://unpkg.com/tippy.js@6/dist/tippy.css">
    <link rel="stylesheet" href="{{ asset('css/admin-theme.css') }}">


    <script>
        window.forceDarkTheme = {{ auth()->check() && auth()->user()->roleIs('Technician') ? 'true' : 'false' }};

        (function () {
            if (window.forceDarkTheme) {
                document.documentElement.setAttribute('data-bs-theme', 'dark');
                localStorage.setItem('theme', 'dark');
            } else {
                const savedTheme = localStorage.getItem('theme') || 'dark';
                document.documentElement.setAttribute('data-bs-theme', savedTheme);
                if (!localStorage.getItem('theme')) localStorage.setItem('theme', 'dark');
            }
        })();

        (function () {
            const collapsed = localStorage.getItem('adminSidebarCollapsed') === '1';
            document.documentElement.setAttribute('data-sidebar-collapsed', collapsed ? '1' : '0');
        })();

        // Ранняя обработка ошибок для подавления некритичных ошибок
        (function () {
            window.addEventListener('error', function (e) {
                const errorMessage = e.message || '';
                if (errorMessage.includes('is not iterable') ||
                    errorMessage.includes('identifyDuplicates') ||
                    errorMessage.includes('statements is not iterable')) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
            }, true);

            window.addEventListener('unhandledrejection', function (e) {
                const reason = e.reason || {};
                const message = reason.message || String(reason) || '';
                if (message.includes('is not iterable') ||
                    message.includes('identifyDuplicates') ||
                    message.includes('statements is not iterable')) {
                    e.preventDefault();
                    return false;
                }
            });
        })();
    </script>

    <style>

        html, body,
        .container-fluid,
        .page-layout {
            background-color: #232525 !important;
        }

        .page-layout {
            height: calc(100vh - 35px);
            overflow: visible; /* allow sidebar dropdowns (notifications) */
        }

        .content {
            height: 100%;
            min-height: 0;
            overflow: hidden; /* скролл не тут */
            padding-bottom: 0; /* убираем 5vh, он больше не нужен */
        }

        .content-inner {
            height: 100%;
            min-height: 0;
            display: flex;
            flex-direction: column;
            /*background-color: black;*/
        }

        #sidebarColumn {
            height: 100%;
            min-height: 0;
        }



    </style>

    @yield('style')

</head>

<body class="p-0 m-0 g-0">

<div id="spinner-load" class="spinner-border text-warning spinner-win d-none" role="status">
    <span class="visually-hidden">Loading...</span>
</div>

<div class="container-fluid p-0">
    <div class="row g-0 page-layout">
        <div id="sidebarColumn" class="bg-body p-0 col-auto">
            @include('components.sidebar')
        </div>
        <div class="content col bg-body pt-2">
            <div class="content-inner px-1">
                @include('components.status')
                @yield('content')
            </div>
        </div>
    </div>
</div>

@include('components.footer')


<script src="{{asset('assets/jquery/jquery371min.js')}}"></script>
<script src="{{asset('assets/Bootstrap 5/bootstrap.bundle.min.js')}}"></script>
<script src="{{asset('assets/select2/js/select2.min.js')}}"></script>
<script src="{{ asset('assets/jquery/jquery.fancybox.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://unpkg.com/@popperjs/core@2"></script>
<script src="https://unpkg.com/tippy.js@6"></script>
<script src="{{ asset('js/main.js') }}"></script>



<script>
    window.addEventListener('load', function () {

        safeHideSpinner();

        const themeToggle = document.getElementById('themeToggle');
        const themeToggleMobile = document.getElementById('themeToggleMobile');

        // Tippy подсказки
        tippy('[data-tippy-content]', {
            placement: 'top',
            animation: 'scale',
            theme: 'avia-dark',
            delay: [100, 50],
            allowHTML: true,
        });

        // Bootstrap tooltips (атрибут data-toggle="tooltip")
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
            new bootstrap.Tooltip(el);
        });

        // Обновление иконки темы (солнышко / луна)
        function updateThemeIcon(theme) {
            const iconClass = theme === 'dark' ? 'bi-sun' : 'bi-moon';

            if (themeToggle) {
                const icon = themeToggle.querySelector('i');
                if (icon) {
                    icon.className = 'bi ' + iconClass;
                }
            }

            if (themeToggleMobile) {
                const icon = themeToggleMobile.querySelector('i');
                if (icon) {
                    icon.className = 'bi ' + iconClass;
                }
            }
        }

        // ------------------------------------
        // 🔥 ТОЛЬКО ДЛЯ Technician: всегда DARK
        // ------------------------------------
        if (window.forceDarkTheme) {
            // Форсим тёмную тему и в DOM, и в localStorage
            document.documentElement.setAttribute('data-bs-theme', 'dark');
            localStorage.setItem('theme', 'dark');
            updateThemeIcon('dark');

            // Кнопки темы отключаем (чтобы не путали)
            if (themeToggle) {
                themeToggle.addEventListener('click', function (e) {
                    e.preventDefault();
                });
            }

            if (themeToggleMobile) {
                themeToggleMobile.addEventListener('click', function (e) {
                    e.preventDefault();
                });
            }

        } else {
            // ------------------------------------
            // 🔥 ДЛЯ ДРУГИХ РОЛЕЙ: нормальное переключение
            // ------------------------------------
            function toggleTheme() {
                let currentTheme = document.documentElement.getAttribute('data-bs-theme');
                let newTheme = currentTheme === 'light' ? 'dark' : 'light';
                document.documentElement.setAttribute('data-bs-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                updateThemeIcon(newTheme);
            }

            if (themeToggle) {
                themeToggle.addEventListener('click', function (e) {
                    e.preventDefault();
                    toggleTheme();
                });
            }

            if (themeToggleMobile) {
                themeToggleMobile.addEventListener('click', function (e) {
                    e.preventDefault();
                    toggleTheme();
                });
            }

            // Инициализируем тему из localStorage
            let storedTheme = localStorage.getItem('theme') || 'dark';
            if (!localStorage.getItem('theme')) localStorage.setItem('theme', 'dark');
            document.documentElement.setAttribute('data-bs-theme', storedTheme);
            updateThemeIcon(storedTheme);
        }

        // Подсветка активного пункта в sidebar (один раз)
        $('#sidebarMenu a').each(function () {
            let location = window.location.protocol + '//' + window.location.host + window.location.pathname;
            let link = this.href;
            if (link === location) {
                $(this).addClass('text-white bg-primary');
            }
        });
    });
</script>

@yield('scripts')

@include('partials.notifications-settings-modal')

<script>
    // Подавляем ошибки MetaMask и другие некритичные ошибки
    window.addEventListener('error', function (e) {
        const errorMessage = e.message || '';
        const errorSource = e.filename || '';

        if (errorMessage.includes('MetaMask')) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }

        // Подавляем ошибки "is not iterable" в identifyDuplicates и других местах
        if (errorMessage.includes('is not iterable') ||
            errorMessage.includes('identifyDuplicates') ||
            errorMessage.includes('statements is not iterable') ||
            (errorMessage.includes('statements') && errorMessage.includes('iterable'))) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }

        return true;
    }, true);


    // ---------- СВЕРТЫВАНИЕ САЙДБАРА С ПЛАВНОЙ АНИМАЦИЕЙ ----------
    const sidebarToggleBtn = document.getElementById('collapseSidebarBtn');
    const root = document.documentElement;
    const sidebarStorageKey = 'adminSidebarCollapsed';

    if (sidebarToggleBtn) {
        const icon = sidebarToggleBtn.querySelector('i');

        function isCollapsed() {
            return root.getAttribute('data-sidebar-collapsed') === '1';
        }

        function setArrow(collapsed) {
            if (!icon) return;
            icon.className = 'bi ' + (collapsed ? 'bi-chevron-right' : 'bi-chevron-left');
        }

        setArrow(isCollapsed());

        sidebarToggleBtn.addEventListener('click', function () {
            const collapsed = isCollapsed();
            const newValue = collapsed ? '0' : '1';

            root.setAttribute('data-sidebar-collapsed', newValue);
            localStorage.setItem(sidebarStorageKey, newValue === '1' ? '1' : '0');
            setArrow(!collapsed);
        });
    }



</script>

{{-- Контекст страницы для AI: куда вести пользователя по интерфейсу --}}
<script>
    window.aiPageContext = @json([
        'route' => \Illuminate\Support\Facades\Route::currentRouteName(),
    ]);
</script>

@if(!in_array(\Illuminate\Support\Facades\Route::currentRouteName(), ['paint.index', 'machining.index'], true))
    @include('admin.ai_widget')
@endif

</body>
</html>
