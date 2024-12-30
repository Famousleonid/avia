@props(['themeToggleId' => 'themeToggle'])

<ul class="nav flex-column" data-accordion="false">
    <li class="nav-item">
        <a class="nav-link" href="{{route('admin.workorders.index')}}">
            <i class="bi bi-file-earmark-word fs-6 me-2 "></i> Workorder
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link press-spinner" href="{{route('admin.customers.index')}}">
            <i class="bi bi-person-workspace me-2"></i> Customers
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link press-spinner" href="{{route('admin.users.index')}}">
            <i class="bi bi-person-arms-up me-2"></i> Techniks
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link press-spinner" href="{{route('admin.manuals.index')}}">
            <i class="bi bi-book-half me-2"></i> Manuals
        </a>
    </li>
    <li class="nav-item press-spinner">
        <a href="{{route('admin.materials.index')}}" class="nav-link">
            <i class="bi bi-body-text me-2"></i> Materials
        </a>
    </li>
    <li class="nav-item press-spinner">
        <a href="{{route('admin.roles.index')}}" class="nav-link">
            <i class="bi bi-award-fill me-2"></i> Roles
        </a>
    </li>
    <li class="nav-item press-spinner">
        <a href="{{route('admin.general-tasks.index')}}" class="nav-link">
            <i class="bi bi-stickies me-2"></i> General Tasks
        </a>
    </li>
    <li class="nav-item press-spinner">
        <a href="{{route('admin.tasks.index')}}" class="nav-link">
            <i class="bi bi-list-task me-2"></i> Tasks
        </a>
    </li>
    <li class="nav-item press-spinner">
        <a href="{{route('admin.teams.index')}}" class="nav-link" >
            <i class="bi bi-microsoft-teams me-2"></i> Teams
        </a>
    </li>
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
