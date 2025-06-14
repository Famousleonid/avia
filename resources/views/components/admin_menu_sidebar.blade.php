@props(['themeToggleId' => 'themeToggle'])

<ul class="nav flex-column" data-accordion="false">
    <li class="nav-item">
        <a class="nav-link" href="{{route('workorders.index')}}">
            <i class="bi bi-file-earmark-word fs-6 me-2 "></i> Workorder
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link press-spinner" href="{{route('progress.index')}}" onclick="showLoadingSpinner()">
            <i class="bi bi-graph-up-arrow me-2"></i> Work in Progress
        </a>
    </li>
    <li class="nav-item press-spinner">
        <a href="{{route('materials.index')}}" class="nav-link">
            <i class="bi bi-body-text me-2"></i> Materials
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link press-spinner" href="{{route('trainings.index')}}">
            <i class="bi bi-list-check me-2"></i> Training
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link press-spinner" href="{{route('users.index')}}">
            <i class="bi bi-person-arms-up me-2"></i> Techniks
        </a>
    </li>
    @if (is_admin())
        <li class="nav-item">
            <a class="nav-link press-spinner" href="{{route('customers.index')}}">
                <i class="bi bi-person-workspace me-2"></i> Customers
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link press-spinner" href="{{route('manuals.index')}}">
                <i class="bi bi-book-half me-2"></i> Manuals
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link press-spinner" href="{{route('units.index')}}">
                <i class="bi bi-unity me-2"></i> Units
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link press-spinner" href="{{route('components.index')}}">
                <i class="bi bi-gear me-2"></i> Components
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link press-spinner" href="{{route('processes.index')}}">
                <i class="bi bi-bar-chart-steps me-2"></i> Processes
            </a>
        </li>
        <li class="nav-item press-spinner">
            <a href="{{route('roles.index')}}" class="nav-link">
                <i class="bi bi-award-fill me-2"></i> Roles
            </a>
        </li>
        <li class="nav-item press-spinner">
            <a href="{{route('teams.index')}}" class="nav-link">
                <i class="bi bi-microsoft-teams me-2"></i> Teams
            </a>
        </li>
        <li class="nav-item press-spinner">
            <a href="{{route('tasks.index')}}" class="nav-link">
                <i class="bi bi-list-task me-2"></i> Tasks
            </a>
        </li>
        <li class="nav-item press-spinner">
            <a href="{{route('general-tasks.index')}}" class="nav-link">
                <i class="bi bi-stickies me-2"></i> General Tasks
            </a>
        </li>
        <li class="nav-item press-spinner">
            <a href="{{route('mobile.index')}}" class="nav-link">
                <i class="bi bi-phone me-2"></i> Mobile
            </a>
        </li>
    @endif
    <li class="nav-item border-top">
        <a class="nav-link" href="#" id="{{ $themeToggleId }}">
            <i class="bi bi-moon me-2"></i>&nbsp; Thema
        </a>
    </li>

</ul>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const spinnerLinks = document.querySelectorAll('a.press-spinner');
        spinnerLinks.forEach(link => {
            link.addEventListener('click', function () {
                showLoadingSpinner();
            });
        });
    });

</script>
