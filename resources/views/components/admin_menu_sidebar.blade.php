@props(['themeToggleId' => 'themeToggle'])

<ul class="nav flex-column" data-accordion="false">
    <li class="nav-item">
        <a class="nav-link" href="#">
            <i class="bi bi-file-earmark-word fs-6 me-2 "></i> Workorder
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="{{route('admin.customers.index')}}">
            <i class="bi bi-person-workspace me-2"></i> Customers
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="{{route('admin.users.index')}}">
            <i class="bi bi-airplane me-2"></i> Techniks
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="{{route('admin.manuals.index')}}">
            <i class="bi bi-book-half me-2"></i> Manuals
        </a>
    </li>
    <li class="nav-item border-bottom">
        <a href="{{route('admin.materials.index')}}" class="nav-link">
            <i class="bi bi-body-text me-2"></i> Materials
        </a>
    </li>
    <li class="nav-item">
        <a href="#" class="nav-link">

        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="#" id="{{ $themeToggleId }}">
            <i class="bi bi-moon me-2"></i>&nbsp; Thema
        </a>
    </li>
</ul>
