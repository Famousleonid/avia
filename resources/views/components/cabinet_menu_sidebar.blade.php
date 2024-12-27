@props(['themeToggleId' => 'themeToggle'])

<ul class="nav flex-column" data-accordion="false">
    <li class="nav-item">
        <a class="nav-link press-spinner" href="{{route('cabinet.index')}}" >
            <i class="bi bi-house fs-5 me-2"></i> Main
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link press-spinner" href="#" >
            <i class="bi bi-graph-up-arrow me-2"></i> Progress
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link press-spinner" href="{{route('cabinet.trainings.index')}}" >
            <i class="bi bi-list-check me-2"></i> Training
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link press-spinner" href="{{route('cabinet.manuals.index')}}">
            <i class="bi bi-book-half me-2"></i> Manuals
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link press-spinner" href="#" >
            <i class="bi bi-file-earmark-word fs-6 me-2 "></i> Workorder
        </a>
    </li>
    <li class="nav-item press-spinner">
        <a class="nav-link" href="{{route('cabinet.profile')}}" >
            <i class="bi bi-person-bounding-box me-2"></i> Profile
        </a>
    </li>
    <li class="nav-item press-spinner">
        <a class="nav-link" href="{{route('cabinet.users.index')}}" >
            <i class="bi bi-airplane me-2"></i> Techniks
        </a>
    </li>
    <li class="nav-item press-spinner ">
        <a href="{{route('cabinet.materials.index')}}" class="nav-link" >
            <i class="bi bi-body-text me-2"></i> Materials
        </a>
    </li>

    <li class="nav-item border-top mt-3">
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
