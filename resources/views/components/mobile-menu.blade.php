@php
    $menuId = $position === 'top' ? 'logout-form-top' : 'logout-form-bottom';
    $borderClass = $position === 'top' ? ' border-bottom' : ' border-top';
    $isActive = fn($route) => request()->routeIs($route);
    $currentWorkorderId = $workorder->id ?? null;

    $onShowPage = request()->routeIs('mobile.show','mobile.tasks', 'mobile.components','mobile.process'); // страница одного воркордера
    $isPaintUser = auth()->check() && auth()->user()->roleIs('Paint');
    $isPaintRoute = request()->routeIs('mobile.paint');
    $isMachiningUser = auth()->check() && auth()->user()->roleIs('Machining');
    $isMachiningRoute = request()->routeIs(
        'mobile.machining',
        'mobile.machining.workorder',
        'mobile.machining.workorder.machining-photos',
        'mobile.machining.workorder.pdfs',
    );
    $usePaintMenu = $isPaintUser || $isPaintRoute;
    $useMachiningMenu = $isMachiningUser || $isMachiningRoute;
    $useShopDeptMenu = $usePaintMenu || $useMachiningMenu;
    $showDeptLost = $usePaintMenu;
    if ($isPaintRoute) {
        $deptWoUrl = route('mobile.paint', ['tab' => 'wo']);
        $deptLostUrl = route('mobile.paint', ['tab' => 'lost']);
        $deptActive = 'paint';
    } elseif ($isMachiningRoute) {
        $deptWoUrl = route('mobile.machining');
        $deptLostUrl = route('mobile.index');
        $deptActive = 'machining';
    } elseif ($isPaintUser) {
        $deptWoUrl = route('mobile.paint', ['tab' => 'wo']);
        $deptLostUrl = route('mobile.paint', ['tab' => 'lost']);
        $deptActive = 'paint';
    } elseif ($isMachiningUser) {
        $deptWoUrl = route('mobile.machining');
        $deptLostUrl = route('mobile.index');
        $deptActive = 'machining';
    } else {
        $deptWoUrl = route('mobile.index');
        $deptLostUrl = route('mobile.index');
        $deptActive = null;
    }
    $paintTab = request()->query('tab', 'wo');
    $machiningMyWoOnly = (bool) session('mobile_machining_my_wo', false);
    /** На карточке WO / фото / PDF — переключатель не уводит на список. */
    $machiningMyWoToggleUrl = ($isMachiningRoute && request()->route('workorder'))
        ? request()->url()
        : route('mobile.machining');
    $mobileProfileLabel = (string) (auth()->user()?->name ?: 'Profile');
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

    .menu-machining-my-wo {
        min-width: 0;
    }

    /* Same 36×36 “icon” band as WO / Logout so the label lines up with .menu-label under them. */
    .menu-machining-my-wo .menu-machining-my-wo-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .menu-machining-my-wo .menu-machining-my-wo-icon .form-check-input {
        width: 1.1em;
        height: 1.1em;
        margin: 0;
        position: relative;
        z-index: 1;
    }

    /* One underline from .menu-label; links must not underline (avoids double line on profile name). */
    .mobile-menu-bar {
        min-height: 60px;
        height: auto !important;
        align-items: flex-start !important;
        padding: 6px 0 4px;
    }

    .mobile-menu-bar > a.text-white,
    .mobile-menu-bar > label {
        text-decoration: none !important;
        justify-content: flex-start !important;
    }

    .mobile-menu-bar a.text-white:hover,
    .mobile-menu-bar a.text-white:focus {
        text-decoration: none !important;
    }

    .mobile-menu-bar .menu-label {
        text-decoration: underline;
        text-underline-offset: 2px;
    }

    /* Long names: min-w-0 + padding so underline is not cut by overflow:hidden. */
    .mobile-menu-bar .menu-label.text-truncate {
        min-width: 0;
        padding: 0 0.15rem 2px;
    }

    /* All icon glyphs share one horizontal band. */
    .menu-top-icon-slot {
        width: 100%;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .menu-top-icon-slot .menu-icon-wrapper,
    .menu-top-icon-slot .menu-machining-my-wo-icon {
        margin: 0;
    }

</style>

<div class="mobile-menu-bar {{ $borderClass }} bg-primary d-flex justify-content-between align-items-start">

    <a href="{{ $useShopDeptMenu ? $deptWoUrl : route('mobile.index') }}" data-spinner
       class="flex-fill text-center d-flex flex-column align-items-center justify-content-start text-white text-decoration-none">
        <div class="menu-top-icon-slot">
            <div class="menu-icon-wrapper {{ (($isPaintRoute || $isMachiningRoute) && $paintTab === 'wo') || (!$useShopDeptMenu && $isActive('mobile.index')) ? 'active' : '' }}">
            <i class="bi {{ $deptActive === 'machining' ? 'bi-hammer' : 'bi-brush' }}"></i>
            <svg viewBox="0 0 36 36">
                <circle cx="18" cy="18" r="18"/>
            </svg>
            </div>
        </div>
        <span class="menu-label text-nowrap">WO</span>
    </a>

    @if($useMachiningMenu && $deptActive === 'machining')
        <label class="menu-machining-my-wo flex-fill text-center d-flex flex-column align-items-center justify-content-start text-white user-select-none mb-0"
               title="{{ request()->route('workorder') ? 'On: only your steps on this WO. Off: all steps. List WO unchanged.' : 'On: only your WOs in the list. Off: all WOs in the list.' }}">
            <div class="menu-top-icon-slot">
                <div class="menu-icon-wrapper menu-machining-my-wo-icon">
                <input type="checkbox"
                       class="form-check-input border-light"
                       {{ $machiningMyWoOnly ? 'checked' : '' }}
                       onchange='window.location.href = @json($machiningMyWoToggleUrl) + (this.checked ? "?set_my_wo=1" : "?set_my_wo=0")'>
                </div>
            </div>
            <span class="menu-label text-nowrap">My&nbsp;WO</span>
        </label>
    @endif

    @if($showDeptLost)
        <a href="{{ $deptLostUrl }}" data-spinner
           class="flex-fill text-center d-flex flex-column align-items-center justify-content-start text-white text-decoration-none">
            <div class="menu-top-icon-slot">
            <div class="menu-icon-wrapper {{ (($isPaintRoute || $isMachiningRoute) && $paintTab === 'lost') ? 'active' : '' }}">
                <i class="bi bi-camera"></i>
                <svg viewBox="0 0 36 36">
                    <circle cx="18" cy="18" r="18"/>
                </svg>
            </div>
            </div>
            <span class="menu-label">Lost</span>
        </a>

        <a href="{{ route('mobile.profile') }}" data-spinner
           class="min-w-0 flex-fill text-center d-flex flex-column align-items-center justify-content-start text-white text-decoration-none">
            <div class="menu-top-icon-slot">
            <div class="menu-icon-wrapper {{ $isActive('mobile.profile') ? 'active' : '' }}">
                <i class="bi bi-person-bounding-box"></i>
                <svg viewBox="0 0 36 36">
                    <circle cx="18" cy="18" r="18"/>
                </svg>
            </div>
            </div>
            <span class="menu-label d-block w-100 min-w-0 text-center text-truncate"
                  title="{{ $mobileProfileLabel }}">{{ $mobileProfileLabel }}</span>
        </a>

        <form id="{{ $menuId }}" action="{{ route('logout') }}" method="POST" style="display: none;">@csrf</form>
        <a href="#"
           class="flex-fill text-center d-flex flex-column align-items-center justify-content-start text-white text-decoration-none"
           onclick="event.preventDefault(); document.getElementById('{{ $menuId }}').submit();">
            <div class="menu-top-icon-slot">
            <div class="menu-icon-wrapper">
                <i class="bi bi-box-arrow-right"></i>
                <svg viewBox="0 0 36 36">
                    <circle cx="18" cy="18" r="18"/>
                </svg>
            </div>
            </div>
            <span class="menu-label">Logout</span>
        </a>
    @elseif($useMachiningMenu)
        <a href="{{ route('mobile.profile') }}" data-spinner
           class="min-w-0 flex-fill text-center d-flex flex-column align-items-center justify-content-start text-white text-decoration-none">
            <div class="menu-top-icon-slot">
            <div class="menu-icon-wrapper {{ $isActive('mobile.profile') ? 'active' : '' }}">
                <i class="bi bi-person-bounding-box"></i>
                <svg viewBox="0 0 36 36">
                    <circle cx="18" cy="18" r="18"/>
                </svg>
            </div>
            </div>
            <span class="menu-label d-block w-100 min-w-0 text-center text-truncate"
                  title="{{ $mobileProfileLabel }}">{{ $mobileProfileLabel }}</span>
        </a>

        <form id="{{ $menuId }}" action="{{ route('logout') }}" method="POST" style="display: none;">@csrf</form>
        <a href="#"
           class="flex-fill text-center d-flex flex-column align-items-center justify-content-start text-white text-decoration-none"
           onclick="event.preventDefault(); document.getElementById('{{ $menuId }}').submit();">
            <div class="menu-top-icon-slot">
            <div class="menu-icon-wrapper">
                <i class="bi bi-box-arrow-right"></i>
                <svg viewBox="0 0 36 36">
                    <circle cx="18" cy="18" r="18"/>
                </svg>
            </div>
            </div>
            <span class="menu-label">Logout</span>
        </a>
    @elseif($onShowPage && !$useShopDeptMenu)

        @if($onShowPage && isset($workorder))
            <a href="{{ route('mobile.show', $currentWorkorderId) }}" data-spinner
               class="flex-fill text-center d-flex flex-column align-items-center justify-content-start text-white text-decoration-none">
                <div class="menu-top-icon-slot">
                <div class="menu-icon-wrapper {{ $isActive('mobile.show') ? 'active' : '' }}">
                    <span class="">W</span>
                    <svg viewBox="0 0 36 36">
                        <circle cx="18" cy="18" r="18"/>
                    </svg>
                </div>
                </div>
                <span class="menu-label">Workorder</span>
            </a>
        @endif
        @notrole('Shipping')
        <a href="{{ route('mobile.tasks', $currentWorkorderId) }}" data-spinner
           class="flex-fill text-center d-flex flex-column align-items-center justify-content-start text-white text-decoration-none">
            <div class="menu-top-icon-slot">
            <div class="menu-icon-wrapper {{ $isActive('mobile.tasks') ? 'active' : '' }}">
                <i class="bi bi-alarm"></i>
                <svg viewBox="0 0 36 36">
                    <circle cx="18" cy="18" r="18"/>
                </svg>
            </div>
            </div>
            <span class="menu-label">Tasks</span>
        </a>

        <a href="{{ route('mobile.components', $currentWorkorderId) }}" data-spinner
           class="flex-fill text-center d-flex flex-column align-items-center justify-content-start text-white text-decoration-none border-0 bg-transparent js-menu-photo">
            <div class="menu-icon-wrapper {{ $isActive('mobile.components') ? 'active' : '' }}">
                <i class="bi bi-gear"></i>
                <svg viewBox="0 0 36 36">
                    <circle cx="18" cy="18" r="18"/>
                </svg>
            </div>
            <span class="menu-label">Parts</span>
        </a>

        <a href="{{ route('mobile.process', $currentWorkorderId) }}" data-spinner
           class="flex-fill text-center d-flex flex-column align-items-center justify-content-start text-white text-decoration-none">
            <div class="menu-top-icon-slot">
            <div class="menu-icon-wrapper {{ $isActive('mobile.process') ? 'active' : '' }}">
                <i class="bi bi-activity"></i>
                <svg viewBox="0 0 36 36">
                    <circle cx="18" cy="18" r="18"/>
                </svg>
            </div>
            </div>
            <span class="menu-label">Process</span>
        </a>

        @endnotrole

    @else

        @notrole('Shipping')

        <a href="{{ route('mobile.materials') }}" data-spinner
           class="flex-fill text-center d-flex flex-column align-items-center justify-content-start text-white text-decoration-none">
            <div class="menu-top-icon-slot">
            <div class="menu-icon-wrapper {{ $isActive('mobile.materials') ? 'active' : '' }}">
                <i class="bi bi-diagram-3"></i>
                <svg viewBox="0 0 36 36">
                    <circle cx="18" cy="18" r="18"/>
                </svg>
            </div>
            </div>
            <span class="menu-label">Material</span>
        </a>

        <a href="{{ route('mobile.profile') }}" data-spinner
           class="min-w-0 flex-fill text-center d-flex flex-column align-items-center justify-content-start text-white text-decoration-none">
            <div class="menu-top-icon-slot">
            <div class="menu-icon-wrapper {{ $isActive('mobile.profile') ? 'active' : '' }}">
                <i class="bi bi-person-bounding-box"></i>
                <svg viewBox="0 0 36 36">
                    <circle cx="18" cy="18" r="18"/>
                </svg>
            </div>
            </div>
            <span class="menu-label d-block w-100 min-w-0 text-center text-truncate"
                  title="{{ $mobileProfileLabel }}">{{ $mobileProfileLabel }}</span>
        </a>
        @endnotrole

        @roles('Shipping|Manager|Admin')
        <a href="{{ route('mobile.draft') }}" data-spinner
           class="flex-fill text-center d-flex flex-column align-items-center justify-content-start text-white text-decoration-none">
            <div class="menu-top-icon-slot">
            <div class="menu-icon-wrapper {{ $isActive('mobile.draft') ? 'active' : '' }}">
                <i class="bi bi-wallet"></i>
                <svg viewBox="0 0 36 36">
                    <circle cx="18" cy="18" r="18"/>
                </svg>
            </div>
            </div>
            <span class="menu-label">Create Draft</span>
        </a>
        @endroles

        <form id="{{ $menuId }}" action="{{ route('logout') }}" method="POST" style="display: none;">@csrf</form>
        <a href="#"
           class="flex-fill text-center d-flex flex-column align-items-center justify-content-start text-white text-decoration-none"
           onclick="event.preventDefault(); document.getElementById('{{ $menuId }}').submit();">
            <div class="menu-top-icon-slot">
            <div class="menu-icon-wrapper">
                <i class="bi bi-box-arrow-right"></i>
                <svg viewBox="0 0 36 36">
                    <circle cx="18" cy="18" r="18"/>
                </svg>
            </div>
            </div>
            <span class="menu-label">Logout</span>
        </a>
    @endif
</div>

