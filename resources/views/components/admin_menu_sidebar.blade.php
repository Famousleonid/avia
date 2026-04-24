<style>
    .nav-link.flex-nowrap {
        white-space: nowrap;
    }
    button[aria-expanded="true"] .lib-chevron {
        transform: rotate(180deg);
    }

    .lib-chevron {
        transition: transform .2s ease;
    }
    #menu-lib .nav-link{
        display: flex;
        align-items: center;
        flex-wrap: nowrap;
        white-space: nowrap;
        overflow: hidden;
    }

    #menu-lib .nav-link span{
        min-width: 0;
        flex: 1 1 auto;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    #menu-lib .nav-link{
        display: flex;
        align-items: center;
        padding: .35rem .75rem;
    }
    .sidebar .nav-item{
        display: block;
    }
</style>


<ul class="nav flex-column" data-accordion="false">
@php
    $sidebarUser = auth()->user();
    $minimalShopSidebar = $sidebarUser
        && $sidebarUser->roleIs(['Paint', 'Machining'])
        && ! $sidebarUser->roleIs(['Admin', 'Manager']);
@endphp
@if($minimalShopSidebar)
    <li class="nav-item">
        <a class="nav-link press-spinner" href="{{ route('users.index') }}">
            <i class="bi bi-person-arms-up me-2"></i> <span>Techniks</span>
        </a>
    </li>
    @can('feature.paint')
        @if($sidebarUser->roleIs('Paint'))
            <li class="nav-item">
                <a class="nav-link press-spinner" href="{{ route('paint.index') }}">
                    <i class="bi bi-palette me-2"></i> <span>Paint</span>
                </a>
            </li>
        @endif
    @endcan
    @can('feature.machining')
        @if($sidebarUser->roleIs('Machining'))
            <li class="nav-item">
                <a class="nav-link press-spinner" href="{{ route('machining.index') }}">
                    <i class="bi bi-hammer me-2"></i> <span>Machining</span>
                </a>
            </li>
        @endif
    @endcan
@else
    <li class="nav-item">
        <a class="nav-link press-spinner" href="{{route('workorders.index')}}">
            <i class="bi bi-file-earmark-word fs-6 me-2 "></i> <span>Workorder</span>
        </a>
    </li>


    <li class="nav-item">
        <a class="nav-link press-spinner" href="{{route('trainings.index')}}">
            <i class="bi bi-list-check me-2"></i> <span>Training</span>
        </a>
    </li>

    @roles("Admin|Team Leader|Manager")

    <li class="nav-item">
        <a class="nav-link press-spinner" href="{{route('trainings.showAll')}}">
            <i class="bi bi-list-check me-2"></i> <span>Training All</span>
        </a>
    </li>
    @endroles
    <li class="nav-item">
        <a class="nav-link press-spinner" href="{{route('users.index')}}">
            <i class="bi bi-person-arms-up me-2"></i> <span>Technician</span>
        </a>
    </li>
    <li class="nav-item press-spinner">
        <a href="{{route('materials.index')}}" class="nav-link">
            <i class="bi bi-body-text me-2"></i> <span>Materials</span>
        </a>
    </li>


    {{--------------------------------------------------------------}}
    @hasanyrole("Admin|Manager")
    @php
        $libraryAdmin = auth()->user()->roleIs('Admin');
    @endphp
    <li class="nav-item">
        <button class="nav-link w-100 d-flex align-items-center flex-nowrap text-start"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#menu-lib"
                aria-expanded="false"
                data-persist="1"
                aria-controls="menu-lib">

            <i class="bi bi-collection me-2"></i>
            <span class="flex-grow-1 text-nowrap">Library</span>
            <i class="bi bi-chevron-down ms-auto lib-chevron"></i>
        </button>


        <ul class="list-unstyled collapse ms-4" id="menu-lib">

            @if($libraryAdmin)
            <li class="nav-item press-spinner">
                <a href="{{route('general-tasks.index')}}" class="nav-link"><i class="bi bi-dot"></i><span>General Tasks</span></a>
            </li>

            <li class="nav-item press-spinner">
                <a href="{{route('tasks.index')}}" class="nav-link"><i class="bi bi-dot"></i><span>Tasks</span></a>
            </li>

            <li class="nav-item press-spinner">
                <a href="{{route('roles.index')}}" class="nav-link"><i class="bi bi-dot"></i><span>Roles</span></a>
            </li>
            @endif

            <li class="nav-item press-spinner">
                <a href="{{route('teams.index')}}" class="nav-link"><i class="bi bi-dot"></i><span>Teams</span></a>
            </li>

            <li class="nav-item press-spinner">
                <a href="{{route('builders.index')}}" class="nav-link"><i class="bi bi-dot"></i> <span>Builders</span></a>
            </li>

            <li class="nav-item press-spinner">
                <a href="{{route('vendors.index')}}" class="nav-link"><i class="bi bi-dot"></i> <span>Vendors</span></a>
            </li>

            @if(auth()->user()->roleIs(['Admin', 'Manager']))
            <li class="nav-item press-spinner">
                <a href="{{route('customers.index')}}" class="nav-link"><i class="bi bi-dot"></i> <span>Customers</span></a>
            </li>
            @endif

            <li class="nav-item press-spinner">
                <a href="{{route('codes.index')}}" class="nav-link"><i class="bi bi-dot"></i> <span>Codes</span></a>
            </li>

            @if($libraryAdmin)
            <li class="nav-item press-spinner">
                <a href="{{route('process_names.index')}}" class="nav-link"><i class="bi bi-dot"></i><span>Process Names</span></a>
            </li>
            @endif

            <li class="nav-item press-spinner">
                <a href="{{route('scopes.index')}}" class="nav-link"><i class="bi bi-dot"></i><span>Scopes</span></a>
            </li>

            <li class="nav-item press-spinner">
                <a href="{{route('planes.index')}}" class="nav-link"><i class="bi bi-dot"></i><span>Planes</span></a>
            </li>

            @if($libraryAdmin)
            <li class="nav-item press-spinner">
                <a href="{{route('instructions.index')}}" class="nav-link"><i class="bi bi-dot"></i><span>Instructions</span></a>
            </li>

            <li class="nav-item press-spinner">
                <a href="{{route('necessaries.index')}}" class="nav-link"><i class="bi bi-dot"></i><span>Necessaries</span></a>
            </li>
            @endif

        </ul>
    </li>

    {{--------------------------------------------------------------}}

    @endhasanyrole

    @php
        $showManualsMenu = auth()->user()->roleIs('Admin')
            || auth()->user()->permittedManuals()->exists();
    @endphp

    @if ($showManualsMenu)
        <li class="nav-item">
            <a class="nav-link press-spinner" href="{{route('manuals.index')}}">
                <i class="bi bi-book-half me-2"></i> <span>Manuals</span>
            </a>
        </li>
    @endif

    @if (auth()->user()->roleIs('Admin'))
        <li class="nav-item">
            <a class="nav-link press-spinner" href="{{route('units.index')}}">
                <i class="bi bi-unity me-2"></i> <span>Component CMM</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link press-spinner" href="{{route('components.index')}}">
                <i class="bi bi-gear me-2"></i> <span>Replaceable Parts</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link press-spinner" href="{{route('processes.index')}}">
                <i class="bi bi-bar-chart-steps me-2"></i> <span>Processes</span>
            </a>
        </li>


    @endif

    @can('feature.paint')
        <li class="nav-item">
            <a class="nav-link press-spinner" href="{{ route('paint.index') }}">
                <i class="bi bi-palette me-2"></i> <span>Paint</span>
            </a>
        </li>
    @endcan

    @can('feature.machining')
        <li class="nav-item">
            <a class="nav-link press-spinner" href="{{ route('machining.index') }}">
                <i class="bi bi-hammer me-2"></i> <span>Machining</span>
            </a>
        </li>
    @endcan

    @hasanyrole('Admin|Manager')
        <li class="nav-item press-spinner">
            <a href="{{ route('vendor-tracking.index') }}" class="nav-link">
                <i class="bi bi-truck me-2"></i> <span>Vendor tracking</span>
            </a>
        </li>
    @endhasanyrole

    @admin
        <li class="nav-item press-spinner">
            <a href="{{ route('admin.notification-rules.index') }}" class="nav-link">
                <i class="bi bi-bell me-2"></i> <span>Notification rules</span>
            </a>
        </li>
        <li class="nav-item press-spinner">
            <a href="{{ route('admin.date-notifications.index') }}" class="nav-link">
                <i class="bi bi-calendar-event me-2"></i> <span>Date notifications</span>
            </a>
        </li>

        @systemadmin
            <li class="nav-item press-spinner">
                <a href="{{route('admin.activity.index')}}" class="nav-link">
                    <i class="bi bi-stickies me-2"></i> <span>Log</span>
                </a>
            </li>

        <li class="nav-item press-spinner">
            <a href="{{route('mobile.index')}}" class="nav-link">
                <i class="bi bi-phone me-2"></i> <span>Mobile</span>
            </a>
        </li>
        @endsystemadmin

        @systemadmin
            <li class="nav-item press-spinner">
                <form action="{{ route('admin.database.backup') }}" method="post" class="m-0" id="admin-database-backup-form">
                    @csrf
                    <button type="submit"
                            class="nav-link w-100 text-start border-0 bg-transparent"
                            style="color: inherit;"
                            title="Create full database backup (stored under storage/app/backups)"
                            onclick="return confirm('Create a full database backup now? This may take a minute.');">
                        <i class="bi bi-database-down me-2"></i><span>Database backup</span>
                    </button>
                </form>
            </li>
        @endsystemadmin

        <li class="nav-item border-top">
            <a class="nav-link " href="#" id="{{ $themeToggleId }}">
                <i class="bi bi-moon me-2"></i>&nbsp; <span>Thema</span>
            </a>
        </li>
    @endadmin

@endif

</ul>

{{-- Flash success/error: только components/status в admin.master (тёмный тост с таймером), без дубля showNotification из main.js --}}

<script>
    (function () {
        const KEY_PREFIX = 'sidebar_collapse:'; // можно поменять, если хочешь

        // находим все collapse в сайдбаре, у которых кнопка помечена data-persist="1"
        const toggles = document.querySelectorAll('[data-bs-toggle="collapse"][data-persist="1"]');

        toggles.forEach((btn) => {
            const targetSel = btn.getAttribute('data-bs-target');
            if (!targetSel) return;

            const el = document.querySelector(targetSel);
            if (!el) return;

            const key = KEY_PREFIX + el.id;

            // 1) восстановление состояния
            const saved = localStorage.getItem(key); // "1" | "0" | null
            if (saved === '1') {
                el.classList.add('show');
                btn.setAttribute('aria-expanded', 'true');
            } else if (saved === '0') {
                el.classList.remove('show');
                btn.setAttribute('aria-expanded', 'false');
            }

            // 2) подписываемся на события bootstrap collapse
            el.addEventListener('shown.bs.collapse', () => {
                localStorage.setItem(key, '1');
                btn.setAttribute('aria-expanded', 'true');
            });

            el.addEventListener('hidden.bs.collapse', () => {
                localStorage.setItem(key, '0');
                btn.setAttribute('aria-expanded', 'false');
            });
        });
    })();
</script>
