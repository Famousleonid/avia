@props(['themeToggleId' => 'themeToggle'])

<ul class="nav flex-column" data-accordion="false">
    <li class="nav-item">
        <a class="nav-link" href="{{route('cabinet.index')}}" onclick="showLoadingSpinner()">
            <i class="bi bi-file-earmark-word fs-4 me-2"></i> Main
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="{{route('progress.index')}}" onclick="showLoadingSpinner()">
            <i class="bi bi-graph-up-arrow me-2"></i> Progress
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="{{route('cabinet.profile')}}" onclick="showLoadingSpinner()">
            <i class="bi bi-person-bounding-box me-2"></i> Profile
        </a>
    </li>
    @if(Auth()->user()->getRole() === 2)
        <li class="nav-item">
            <a class="nav-link" href="#" onclick="showLoadingSpinner()">
                <i class="bi bi-person-workspace me-2"></i> Customers
            </a>
        </li>
    @endif
    <li class="nav-item">
        <a class="nav-link" href="{{route('technik.index')}}" onclick="showLoadingSpinner()">
            <i class="bi bi-airplane me-2"></i> Techniks
        </a>
    </li>
    <li class="nav-item">
        <a href="{{route('materials.index')}}" class="nav-link" onclick="showLoadingSpinner()">
            <i class="bi bi-body-text me-2"></i> Materials
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="#" id="{{ $themeToggleId }}">
            <i class="bi bi-moon me-2"></i>&nbsp; Thema
        </a>
    </li>
</ul>
