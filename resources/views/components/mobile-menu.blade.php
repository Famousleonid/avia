@php
    $menuId = $position === 'top' ? 'logout-form-top' : 'logout-form-bottom';
     $borderClass = $position === 'top' ? ' border-bottom' : ' border-top';
     $isActive = fn($route) => request()->routeIs($route);
@endphp

<style>
    .menu-icon-wrapper {
        position: relative;
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .menu-icon-wrapper svg {
        position: absolute;
        width: 100%;
        height: 100%;
        transform: rotate(-90deg);
        z-index: 0;
        pointer-events: none;
    }

    .menu-icon-wrapper circle {
        fill: none;
        stroke: #00ff66;
        stroke-width: 2;
        stroke-dasharray: 113;
        stroke-dashoffset: 113;
        stroke-linecap: round;
    }

    .menu-icon-wrapper.active circle {
        animation: drawCircle 2s ease forwards;
    }

    @keyframes drawCircle {
        from {
            stroke-dashoffset: 113;
        }
        to {
            stroke-dashoffset: 0;
        }
    }

    .menu-icon-wrapper i {
        z-index: 1;
        font-size: 1.2rem;
        color: white;
    }

    .menu-label {
        font-size: 0.75rem;
        line-height: 1;
    }


</style>

<div class="{{ $borderClass }} bg-primary d-flex justify-content-between align-items-center " style="height: 60px;">
    <a href="{{ route('mobile.index') }}"
       class="flex-fill text-center d-flex flex-column align-items-center justify-content-center text-white">
        <div class="menu-icon-wrapper {{ $isActive('mobile.index') ? 'active' : '' }}">
            <i class="bi bi-list-ol"></i>
            <svg viewBox="0 0 36 36">
                <circle cx="18" cy="18" r="18"/>
            </svg>
        </div>
        <span class="menu-label">Workorder</span>
    </a>

    <a href="{{ route('mobile.materials') }}"
       class="flex-fill text-center d-flex flex-column align-items-center justify-content-center text-white">
        <div class="menu-icon-wrapper {{ $isActive('mobile.materials') ? 'active' : '' }}">
            <i class="bi bi-diagram-3"></i>
            <svg viewBox="0 0 36 36">
                <circle cx="18" cy="18" r="18"/>
            </svg>
        </div>
        <span class="menu-label">Material</span>
    </a>

    <a href="{{route('mobile.components')}}"
       class="flex-fill text-center d-flex flex-column align-items-center justify-content-center text-white">
        <div class="menu-icon-wrapper {{ $isActive('mobile.component') ? 'active' : '' }}">
            <i class="bi bi-puzzle"></i>
            <svg viewBox="0 0 36 36">
                <circle cx="18" cy="18" r="18"/>
            </svg>
        </div>
        <span class="menu-label">Component</span>
    </a>

    <a href="{{ route('mobile.profile') }}"
       class="flex-fill text-center d-flex flex-column align-items-center justify-content-center text-white">
        <div class="menu-icon-wrapper {{ $isActive('mobile.profile') ? 'active' : '' }}">
            <i class="bi bi-person-bounding-box"></i>
            <svg viewBox="0 0 36 36">
                <circle cx="18" cy="18" r="18"/>
            </svg>
        </div>
        <span class="menu-label">Profile</span>
    </a>

    <form id="{{ $menuId }}" action="{{ route('logout') }}" method="POST" style="display: none;">@csrf</form>
    <a href="#"
       class="flex-fill text-center d-flex flex-column align-items-center justify-content-center text-white"
       onclick="event.preventDefault(); document.getElementById('{{ $menuId }}').submit();">
        <div class="menu-icon-wrapper">
            <i class="bi bi-box-arrow-right"></i>
            <svg viewBox="0 0 36 36">
                <circle cx="18" cy="18" r="18"/>
            </svg>
        </div>
        <span class="menu-label">Logout</span>
    </a>
</div>
