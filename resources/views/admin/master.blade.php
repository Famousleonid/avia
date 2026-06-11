<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="icon" href="{{asset('img/favicon.webp')}}" type="image/png">
    <title>Admin page</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}"/>
    @include('partials.user-scoped-storage')
    <script>
        window.UserUiSettings = window.UserUiSettings || (function () {
            const indexUrl = @json(route('user-ui-settings.index'));
            const storeUrl = @json(route('user-ui-settings.store'));
            const csrf = @json(csrf_token());
            const cache = {};

            async function loadScope(scope) {
                if (Object.prototype.hasOwnProperty.call(cache, scope)) {
                    return cache[scope];
                }

                const response = await fetch(`${indexUrl}?scope=${encodeURIComponent(scope)}`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    cache[scope] = {};
                    return cache[scope];
                }

                const data = await response.json();
                cache[scope] = data.settings && typeof data.settings === 'object' ? data.settings : {};

                return cache[scope];
            }

            async function get(scope, key, fallback = null) {
                const settings = await loadScope(scope);
                return Object.prototype.hasOwnProperty.call(settings, key) ? settings[key] : fallback;
            }

            async function set(scope, key, value) {
                if (!Object.prototype.hasOwnProperty.call(cache, scope)) {
                    cache[scope] = {};
                }

                cache[scope][key] = value;

                try {
                    await fetch(storeUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ scope, key, value }),
                    });
                } catch (error) {
                    console.error('Failed to save user UI setting', error);
                }
            }

            return { loadScope, get, set };
        })();
    </script>
    <link rel="stylesheet" href="{{asset('assets/Bootstrap 5/bootstrap.min.css')}}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link href="{{asset('assets/Bootstrap 5/bootstrap-icons.css')}}" rel="stylesheet">
    <link rel="stylesheet" href="{{asset('assets/jquery/jquery.fancybox.min.css')}}">
    <link href="{{asset('assets/select2/css/select2.min.css')}}" rel="stylesheet"/>
    <link rel="stylesheet" href="{{asset('css/custom_bootstrap.css')}}">
    <link rel="stylesheet" href="{{asset('css/main.css')}}">
    <link rel="stylesheet" href="{{ asset('css/paper-button.css') }}">
    <link rel="stylesheet" href="https://unpkg.com/tippy.js@6/dist/tippy.css">
    <link rel="stylesheet" href="{{ asset('css/admin-theme.css') }}?v={{ filemtime(public_path('css/admin-theme.css')) }}">


    <script>
        window.forceDarkTheme = {{ auth()->check() && auth()->user()->roleIs('Technician') ? 'true' : 'false' }};

        (function () {
            if (window.forceDarkTheme) {
                document.documentElement.setAttribute('data-bs-theme', 'dark');
                window.UserScopedStorage.setItem('theme', 'dark');
            } else {
                const savedTheme = window.UserScopedStorage.getItem('theme') || 'dark';
                document.documentElement.setAttribute('data-bs-theme', savedTheme);
                if (!window.UserScopedStorage.getItem('theme')) window.UserScopedStorage.setItem('theme', 'dark');
            }
        })();

        (function () {
            const collapsed = window.UserScopedStorage.getItem('adminSidebarCollapsed') === '1';
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

        html[data-bs-theme="dark"],
        html[data-bs-theme="dark"] body,
        html[data-bs-theme="dark"] .container-fluid,
        html[data-bs-theme="dark"] .page-layout {
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
        <div class="content col bg-body pt-0">
            <div class="content-inner px-0">
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
<script>
    if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
        window.jQuery.fn.select2.defaults.set('minimumResultsForSearch', 0);

        window.jQuery(document).on('select2:open', function () {
            const searchField = document.querySelector('.select2-container--open .select2-search__field');
            if (searchField) {
                searchField.focus();
            }
        });
    }
</script>
<script src="{{ asset('assets/jquery/jquery.fancybox.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://unpkg.com/@popperjs/core@2"></script>
<script src="https://unpkg.com/tippy.js@6"></script>
@include('components.session-heartbeat-config')
<script src="{{ asset('js/main.js') }}?v={{ filemtime(public_path('js/main.js')) }}"></script>

<script>
    (function () {
        const projectDateMonths = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];

        function parseProjectDate(value) {
            const match = String(value || '').trim().match(/^(\d{1,2})[/.]([a-z]{3})[/.](\d{4})$/i);
            if (!match) return null;

            const day = Number(match[1]);
            const month = projectDateMonths.indexOf(match[2].toLowerCase());
            const year = Number(match[3]);
            if (!day || month < 0 || !year) return null;

            const date = new Date(year, month, day);
            return date.getFullYear() === year && date.getMonth() === month && date.getDate() === day ? date : null;
        }

        function formatProjectDate(date, capitalizeMonth = true) {
            if (!(date instanceof Date) || Number.isNaN(date.getTime())) return '';
            const month = projectDateMonths[date.getMonth()];
            const displayMonth = capitalizeMonth ? month.charAt(0).toUpperCase() + month.slice(1) : month;
            return String(date.getDate()).padStart(2, '0') + '/' + displayMonth + '/' + date.getFullYear();
        }

        window.initProjectDatePickers = function (root = document) {
            if (typeof flatpickr === 'undefined') return;

            root.querySelectorAll('input[data-project-date]').forEach(input => {
                if (input._projectDatePicker) return;
                const capitalizeMonth = !input.hasAttribute('data-project-date-lower');

                input._projectDatePicker = flatpickr(input, {
                    allowInput: true,
                    dateFormat: 'd/M/Y',
                    defaultDate: parseProjectDate(input.value) || null,
                    disableMobile: true,
                    formatDate: date => formatProjectDate(date, capitalizeMonth),
                    parseDate: parseProjectDate,
                    onChange(selectedDates, dateStr, instance) {
                        input.value = selectedDates[0] ? formatProjectDate(selectedDates[0], capitalizeMonth) : '';
                    },
                    onClose(selectedDates) {
                        const parsed = parseProjectDate(input.value);
                        if (parsed) {
                            input.value = formatProjectDate(parsed, capitalizeMonth);
                        }
                    },
                });
            });
        };

        document.addEventListener('DOMContentLoaded', () => window.initProjectDatePickers());
    })();
</script>

<script>
    window.addEventListener('load', function () {

        safeHideSpinner();

        const themeToggle = document.getElementById('themeToggle');
        const themeToggleMobile = document.getElementById('themeToggleMobile');

        // Tippy подсказки
        const isMainsPage = document.body?.classList?.contains('page-mains')
            || window.location.pathname.startsWith('/mains/');
        tippy('[data-tippy-content]', {
            placement: 'top',
            animation: 'scale',
            theme: 'avia-dark',
            delay: isMainsPage ? [650, 100] : [100, 50],
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
            // Форсим тёмную тему и в DOM, и в window.UserScopedStorage
            document.documentElement.setAttribute('data-bs-theme', 'dark');
            window.UserScopedStorage.setItem('theme', 'dark');
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
                window.UserScopedStorage.setItem('theme', newTheme);
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

            // Инициализируем тему из window.UserScopedStorage
            let storedTheme = window.UserScopedStorage.getItem('theme') || 'dark';
            if (!window.UserScopedStorage.getItem('theme')) window.UserScopedStorage.setItem('theme', 'dark');
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
            window.UserScopedStorage.setItem(sidebarStorageKey, newValue === '1' ? '1' : '0');
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

@php($hideAiWidget = request()->routeIs('paint.index', 'machining.index', 'vendor-tracking.*'))
@if(! $hideAiWidget)
    @include('admin.ai_widget')
@endif

</body>
</html>
