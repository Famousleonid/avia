<div class="std-print-toolbar no-print">
    <button class="btn btn-outline-primary" onclick="window.print()">
        Print Form
    </button>
    <button class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#printSettingsModal">
        Print Settings
    </button>
    @if(isset($ndt_show_excluded) || isset($cad_show_excluded))
        @php $showExcluded = $ndt_show_excluded ?? $cad_show_excluded; @endphp
        <button type="button" class="btn btn-outline-secondary" id="{{ isset($ndt_show_excluded) ? 'ndt' : 'cad' }}-toggle-excluded"
                aria-pressed="{{ $showExcluded ? 'true' : 'false' }}">
            {{ $showExcluded ? 'Hide crossed-out rows' : 'Show crossed-out rows' }}
        </button>
    @endif
</div>
