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
    <script>
        window.forceDarkTheme = @role('Technician') true @else false @endrole;

        (function () {
            if (window.forceDarkTheme) {
                // Technician → только тёмная
                document.documentElement.setAttribute('data-bs-theme', 'dark');
                localStorage.setItem('theme', 'dark');
            } else {
                // Остальные → как было
                const savedTheme = localStorage.getItem('theme') || 'light';
                document.documentElement.setAttribute('data-bs-theme', savedTheme);
            }
        })();

    </script>
    <script>
        (function () {
            const collapsed = localStorage.getItem('adminSidebarCollapsed') === '1';
            document.documentElement.setAttribute('data-sidebar-collapsed', collapsed ? '1' : '0');
        })();
    </script>

    <script>
        // Ранняя обработка ошибок для подавления некритичных ошибок
        (function() {
            window.addEventListener('error', function(e) {
                const errorMessage = e.message || '';
                if (errorMessage.includes('is not iterable') ||
                    errorMessage.includes('identifyDuplicates') ||
                    errorMessage.includes('statements is not iterable')) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
            }, true);

            window.addEventListener('unhandledrejection', function(e) {
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

        .content {
            height: 100vh;
            overflow-y: auto;
            padding-right: 12px;
            padding-bottom: 5vh;
        }

        .content-inner {
            min-height: 100%;
            display: flex;
            flex-direction: column;
            position: relative;
        }

    </style>

    @yield('style')


</head>

<body class="p-0 m-0 g-0">

<div id="spinner-load" class="spinner-border text-warning spinner-win d-none" role="status">
    <span class="visually-hidden">Loading...</span>
</div>

<div class="row g-0 page-layout">

    <div id="sidebarColumn" class="bg-body p-0 col-auto">
        @include('components.sidebar')
    </div>

    <div class="content col bg-body pt-2">
        <div class="content-inner px-2">
            @include('components.status')
            @yield('content')
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
        hideLoadingSpinner();

        const themeToggle = document.getElementById('themeToggle');
        const themeToggleMobile = document.getElementById('themeToggleMobile');

        // Tippy подсказки
        tippy('[data-tippy-content]', {
            placement: 'top',
            animation: 'scale',
            theme: 'light-border',
            delay: [100, 50],
        });

        // Bootstrap tooltips (атрибут data-toggle="tooltip")
        $(function () {
            $('[data-toggle="tooltip"]').tooltip();
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
            let storedTheme = localStorage.getItem('theme') || 'light';
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

<script>
    // Подавляем ошибки MetaMask и другие некритичные ошибки
    window.addEventListener('error', function(e) {
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

    // Также обрабатываем необработанные промисы
    window.addEventListener('unhandledrejection', function(e) {
        const reason = e.reason || {};
        const message = reason.message || String(reason) || '';

        if (message.includes('MetaMask')) {
            e.preventDefault();
            return false;
        }

        if (message.includes('is not iterable') ||
            message.includes('identifyDuplicates') ||
            message.includes('statements is not iterable')) {
            e.preventDefault();
            return false;
        }

        return true;
    });

    // Дополнительный обработчик необработанных промисов
    window.addEventListener('unhandledrejection', function(e) {
        if (e.reason && e.reason.message && e.reason.message.includes('MetaMask')) {
            e.preventDefault();
            return false;
        }
        // Подавляем ошибки "is not iterable" в identifyDuplicates
        if (e.reason && e.reason.message &&
            (e.reason.message.includes('is not iterable') || e.reason.message.includes('identifyDuplicates'))) {
            console.warn('Suppressed promise rejection:', e.reason.message);
            e.preventDefault();
            return false;
        }
    });

    //------------------------------------------------------------------------------------------------------------------------

    // Ещё раз подсветка активного пункта в sidebar (можно удалить, если дублируется)
    $('#sidebarMenu a').each(function () {
        let location = window.location.protocol + '//' + window.location.host + window.location.pathname;
        let link = this.href;
        if (link === location) {
            $(this).addClass('text-white bg-primary');
        }
    });

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

</body>
</html>
