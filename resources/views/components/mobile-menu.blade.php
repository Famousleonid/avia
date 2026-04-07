@php
    $menuId = $position === 'top' ? 'logout-form-top' : 'logout-form-bottom';
    $borderClass = $position === 'top' ? ' border-bottom' : ' border-top';
    $isActive = fn($route) => request()->routeIs($route);
    $currentWorkorderId = $workorder->id ?? null;

    $onShowPage = request()->routeIs('mobile.show','mobile.tasks', 'mobile.components','mobile.process'); // страница одного воркордера
    $isPaintUser = auth()->check() && auth()->user()->roleIs('Paint');
    $isPaintRoute = request()->routeIs('mobile.paint');
    $isMachiningUser = auth()->check() && auth()->user()->roleIs('Machining');
    $isMachiningRoute = request()->routeIs('mobile.machining');
    $usePaintMenu = $isPaintUser || $isPaintRoute;
    $useMachiningMenu = $isMachiningUser || $isMachiningRoute;
    $useShopDeptMenu = $usePaintMenu || $useMachiningMenu;
    $showDeptLost = $usePaintMenu;
    if ($isPaintRoute) {
        $deptWoUrl = route('mobile.paint', ['tab' => 'wo']);
        $deptLostUrl = route('mobile.paint', ['tab' => 'lost']);
        $deptActive = 'paint';
    } elseif ($isMachiningRoute) {
        $deptWoUrl = route('mobile.machining', ['tab' => 'wo']);
        $deptLostUrl = route('mobile.index');
        $deptActive = 'machining';
    } elseif ($isPaintUser) {
        $deptWoUrl = route('mobile.paint', ['tab' => 'wo']);
        $deptLostUrl = route('mobile.paint', ['tab' => 'lost']);
        $deptActive = 'paint';
    } elseif ($isMachiningUser) {
        $deptWoUrl = route('mobile.machining', ['tab' => 'wo']);
        $deptLostUrl = route('mobile.index');
        $deptActive = 'machining';
    } else {
        $deptWoUrl = route('mobile.index');
        $deptLostUrl = route('mobile.index');
        $deptActive = null;
    }
    $paintTab = request()->query('tab', 'wo');
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

<div class="{{ $borderClass }} bg-primary d-flex justify-content-between align-items-center" style="height: 60px;">

    <a href="{{ $useShopDeptMenu ? $deptWoUrl : route('mobile.index') }}" data-spinner
       class="flex-fill text-center d-flex flex-column align-items-center justify-content-center text-white">
        <div class="menu-icon-wrapper {{ (($isPaintRoute || $isMachiningRoute) && $paintTab === 'wo') || (!$useShopDeptMenu && $isActive('mobile.index')) ? 'active' : '' }}">
            <i class="bi {{ $deptActive === 'machining' ? 'bi-hammer' : 'bi-brush' }}"></i>
            <svg viewBox="0 0 36 36">
                <circle cx="18" cy="18" r="18"/>
            </svg>
        </div>
        <span class="menu-label">{{ $useShopDeptMenu ? 'WO' : 'WO List' }}</span>
    </a>

    @if($showDeptLost)
        <a href="{{ $deptLostUrl }}" data-spinner
           class="flex-fill text-center d-flex flex-column align-items-center justify-content-center text-white">
            <div class="menu-icon-wrapper {{ (($isPaintRoute || $isMachiningRoute) && $paintTab === 'lost') ? 'active' : '' }}">
                <i class="bi bi-camera"></i>
                <svg viewBox="0 0 36 36">
                    <circle cx="18" cy="18" r="18"/>
                </svg>
            </div>
            <span class="menu-label">Lost</span>
        </a>

        <a href="{{ route('mobile.profile') }}" data-spinner
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
    @elseif($useMachiningMenu)
        <a href="{{ route('mobile.profile') }}" data-spinner
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
    @elseif($onShowPage && !$useShopDeptMenu)

        @if($onShowPage && isset($workorder))
            <a href="{{ route('mobile.show', $currentWorkorderId) }}" data-spinner
               class="flex-fill text-center d-flex flex-column align-items-center justify-content-center text-white">
                <div class="menu-icon-wrapper {{ $isActive('mobile.show') ? 'active' : '' }}">
                    <span class="">W</span>
                    <svg viewBox="0 0 36 36">
                        <circle cx="18" cy="18" r="18"/>
                    </svg>
                </div>
                <span class="menu-label">Workorder</span>
            </a>
        @endif
        @notrole('Shipping')
        <a href="{{ route('mobile.tasks', $currentWorkorderId) }}" data-spinner
           class="flex-fill text-center d-flex flex-column align-items-center justify-content-center text-white">
            <div class="menu-icon-wrapper {{ $isActive('mobile.tasks') ? 'active' : '' }}">
                <i class="bi bi-alarm"></i>
                <svg viewBox="0 0 36 36">
                    <circle cx="18" cy="18" r="18"/>
                </svg>
            </div>
            <span class="menu-label">Tasks</span>
        </a>

        <a href="{{ route('mobile.components', $currentWorkorderId) }}" data-spinner
           class="flex-fill text-center d-flex flex-column align-items-center justify-content-center text-white border-0 bg-transparent js-menu-photo">
            <div class="menu-icon-wrapper {{ $isActive('mobile.components') ? 'active' : '' }}">
                <i class="bi bi-gear"></i>
                <svg viewBox="0 0 36 36">
                    <circle cx="18" cy="18" r="18"/>
                </svg>
            </div>
            <span class="menu-label">Parts</span>
        </a>

        <a href="{{ route('mobile.process', $currentWorkorderId) }}" data-spinner
           class="flex-fill text-center d-flex flex-column align-items-center justify-content-center text-white">
            <div class="menu-icon-wrapper {{ $isActive('mobile.process') ? 'active' : '' }}">
                <i class="bi bi-activity"></i>
                <svg viewBox="0 0 36 36">
                    <circle cx="18" cy="18" r="18"/>
                </svg>
            </div>
            <span class="menu-label">Process</span>
        </a>

        @endnotrole

    @else

        @notrole('Shipping')

        <a href="{{ route('mobile.materials') }}" data-spinner
           class="flex-fill text-center d-flex flex-column align-items-center justify-content-center text-white">
            <div class="menu-icon-wrapper {{ $isActive('mobile.materials') ? 'active' : '' }}">
                <i class="bi bi-diagram-3"></i>
                <svg viewBox="0 0 36 36">
                    <circle cx="18" cy="18" r="18"/>
                </svg>
            </div>
            <span class="menu-label">Material</span>
        </a>

        <a href="{{ route('mobile.profile') }}" data-spinner
           class="flex-fill text-center d-flex flex-column align-items-center justify-content-center text-white">
            <div class="menu-icon-wrapper {{ $isActive('mobile.profile') ? 'active' : '' }}">
                <i class="bi bi-person-bounding-box"></i>
                <svg viewBox="0 0 36 36">
                    <circle cx="18" cy="18" r="18"/>
                </svg>
            </div>
            <span class="menu-label">Profile</span>
        </a>
        @endnotrole

        @roles('Shipping|Manager|Admin')
        <a href="{{ route('mobile.draft') }}" data-spinner
           class="flex-fill text-center d-flex flex-column align-items-center justify-content-center text-white">
            <div class="menu-icon-wrapper {{ $isActive('mobile.draft') ? 'active' : '' }}">
                <i class="bi bi-wallet"></i>
                <svg viewBox="0 0 36 36">
                    <circle cx="18" cy="18" r="18"/>
                </svg>
            </div>
            <span class="menu-label">Create Draft</span>
        </a>
        @endroles

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
    @endif
</div>

