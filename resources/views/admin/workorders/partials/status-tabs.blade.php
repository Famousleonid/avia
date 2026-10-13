<nav class="nav nav-tabs flex-shrink-0" aria-label="WO Status tabs">
    @if(auth()->user()?->roleIs(['Admin', 'Manager']))
        <a class="nav-link {{ request()->routeIs('workorders.status') ? 'active' : '' }}" href="{{ route('workorders.status') }}" @if(request()->routeIs('workorders.status')) aria-current="page" @endif>WO Status</a>
    @endif
    @can('feature.wo_status.efficiency')
        <a class="nav-link {{ request()->routeIs('workorders.status.efficiency') ? 'active' : '' }}" href="{{ route('workorders.status.efficiency') }}" @if(request()->routeIs('workorders.status.efficiency')) aria-current="page" @endif>Efficiency</a>
    @endcan
</nav>
