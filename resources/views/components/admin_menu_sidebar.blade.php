
<ul class="nav flex-column" data-accordion="false">
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
            <i class="bi bi-person-arms-up me-2"></i> <span>Techniks</span>
        </a>
    </li>
    <li class="nav-item press-spinner">
        <a href="{{route('materials.index')}}" class="nav-link">
            <i class="bi bi-body-text me-2"></i> <span>Materials</span>
        </a>
    </li>
    @if (auth()->user()->roleIs('Admin'))
        <li class="nav-item">
            <a class="nav-link press-spinner" href="{{route('customers.index')}}">
                <i class="bi bi-person-workspace me-2"></i> <span>Customers</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link press-spinner" href="{{route('manuals.index')}}">
                <i class="bi bi-book-half me-2"></i> <span>Manuals</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link press-spinner" href="{{route('units.index')}}">
                <i class="bi bi-unity me-2"></i> <span>Units</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link press-spinner" href="{{route('components.index')}}">
                <i class="bi bi-gear me-2"></i> <span>Components</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link press-spinner" href="{{route('processes.index')}}">
                <i class="bi bi-bar-chart-steps me-2"></i> <span>Processes</span>
            </a>
        </li>
        <li class="nav-item press-spinner">
            <a href="{{route('roles.index')}}" class="nav-link">
                <i class="bi bi-award-fill me-2"></i> <span>Roles</span>
            </a>
        </li>
        <li class="nav-item press-spinner">
            <a href="{{route('teams.index')}}" class="nav-link">
                <i class="bi bi-microsoft-teams me-2"></i> <span>Teams</span>
            </a>
        </li>
    @endif

    @roles("Admin|Manager")
    <li class="nav-item press-spinner">
            <a href="{{route('tasks.index')}}" class="nav-link">
                <i class="bi bi-list-task me-2"></i> <span>Tasks</span>
            </a>
        </li>
        <li class="nav-item press-spinner">
            <a href="{{route('general-tasks.index')}}" class="nav-link">
                <i class="bi bi-stickies me-2"></i> <span>General Tasks</span>
            </a>
        </li>
    @endrole

    @if (auth()->user()->roleIs('Admin'))
        <li class="nav-item press-spinner">
            <a href="{{route('workorders.logs')}}" class="nav-link">
                <i class="bi bi-stickies me-2"></i> <span>Log</span>
            </a>
        </li>

        <li class="nav-item press-spinner">
            <a href="{{route('mobile.index')}}" class="nav-link">
                <i class="bi bi-phone me-2"></i> <span>Mobile</span>
            </a>
        </li>
        <li class="nav-item border-top">
            <a class="nav-link " href="#" id="{{ $themeToggleId }}">
                <i class="bi bi-moon me-2"></i>&nbsp; <span>Thema</span>
            </a>
        </li>
    @endif


</ul>

