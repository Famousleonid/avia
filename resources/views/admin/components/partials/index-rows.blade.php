@php
    $showManualColumn = $showManualColumn ?? true;
    $editButtonClass = $editButtonClass ?? 'open-edit-component-drawer';
    $deleteRedirect = $deleteRedirect ?? null;
    $componentFlags = [
        'log_card' => 'LC',
        'is_bush' => 'Bush',
        'kit' => 'Kit',
        'kit_e' => 'Kit_E',
        'ndt_list' => 'NDT',
        'cad_list' => 'CAD',
        'stress_relief_list' => 'Stress',
        'paint_list' => 'Paint',
    ];
@endphp

@foreach($components as $component)
    @php
        $manualPartLock = $component->manual?->partLock;
        $canManageLockedManualParts = auth()->user()?->canManageLockedManualParts() ?? false;
        $partMutationLocked = $manualPartLock && ! $canManageLockedManualParts;
        $assemblyRows = $component->relationLoaded('assemblies')
            ? $component->assemblies
            : collect();
        $assemblyRows = $assemblyRows->filter(function ($assembly) {
            return filled($assembly->assy_ipl_num ?? null)
                || filled($assembly->assy_part_number ?? null);
        })->values();

        $firstAssembly = $assemblyRows->first();
        $unitsAssy = trim((string) ($component->units_assy ?? ''));
        $popoverHtml = '<div class="assy-popover-list">';
        foreach ($assemblyRows as $assemblyIndex => $assembly) {
            $assyIpl = trim((string) ($assembly->assy_ipl_num ?? ''));
            $assyPart = trim((string) ($assembly->assy_part_number ?? ''));
            $units = trim((string) ($assembly->units_assy ?? ''));
            $notes = trim((string) ($assembly->notes ?? ''));

            $popoverHtml .= '<div class="assy-popover-item">';
            $popoverHtml .= '<div><strong>' . e($assyIpl !== '' ? $assyIpl : '-') . '</strong>';
            $popoverHtml .= ' <span class="text-muted">/</span> ' . e($assyPart !== '' ? $assyPart : '-') . '</div>';
            if ($units !== '') {
                $popoverHtml .= '<div class="small text-muted">Units: ' . e($units) . '</div>';
            }
            if ($notes !== '') {
                $popoverHtml .= '<div class="small assy-popover-notes">' . e($notes) . '</div>';
            }
            $popoverHtml .= '</div>';
        }
        $popoverHtml .= '</div>';
    @endphp
    <tr data-manual-id="{{ $component->manual_id ?? '' }}" @if(! $showManualColumn) id="manual-part-row-{{ $component->id }}" @endif>
        <td class="text-center">{{ $component->ipl_num }}</td>
        <td class="text-center">{{ $component->part_number }}</td>
        <td class="text-center">{{ $component->name }}</td>
        <td class="text-center">
            @if($firstAssembly)
                @php
                    $firstAssyIpl = trim((string) ($firstAssembly->assy_ipl_num ?? ''));
                    $firstAssyPart = trim((string) ($firstAssembly->assy_part_number ?? ''));
                    $firstLabel = trim(($firstAssyIpl !== '' ? $firstAssyIpl : '-') . ' / ' . ($firstAssyPart !== '' ? $firstAssyPart : '-'));
                    $extraCount = max($assemblyRows->count() - 1, 0);
                @endphp
                <button type="button"
                        class="btn btn-link btn-sm assy-popover-button text-decoration-none"
                        data-bs-toggle="popover"
                        data-bs-trigger="focus"
                        data-bs-placement="left"
                        data-bs-html="true"
                        data-bs-container="body"
                        data-bs-custom-class="component-assy-popover"
                        data-bs-title="{{ __('Assemblies') }}"
                        data-bs-content="{{ $popoverHtml }}">
                    <span class="assy-summary">
                        <span class="assy-summary-main">{{ $firstLabel }}</span>
                        @if($extraCount > 0)
                            <span class="badge text-bg-primary">+{{ $extraCount }}</span>
                        @endif
                    </span>
                </button>
            @else
                <span class="text-muted small">-</span>
            @endif
        </td>
        <td class="text-center">{{ $unitsAssy !== '' ? $unitsAssy : '-' }}</td>
        <td class="text-center" style="width:120px;">
            @if($component->getMedia('components')->isNotEmpty())
                <a href="{{ $component->getFirstMediaBigUrl('components') }}" data-fancybox="gallery">
                    <img
                        src="{{ $component->getFirstMediaThumbnailUrl('components') }}"
                        class="component-avatar"
                        alt="IMG"
                    >
                </a>
            @else
                <span class="text-muted small">-</span>
            @endif
        </td>
        @foreach($componentFlags as $flagField => $flagLabel)
            @php
                $flagTitle = $flagField === 'is_bush' && filled($component->bush_ipl_num)
                    ? $component->bush_ipl_num
                    : $flagLabel;
            @endphp
            <td class="text-center component-flag-cell">
                <input type="checkbox"
                       class="form-check-input component-flag-toggle"
                       title="{{ $flagTitle }}"
                       aria-label="{{ $flagLabel }}"
                       data-component-id="{{ $component->id }}"
                       data-field="{{ $flagField }}"
                       data-bush-ipl-num="{{ $flagField === 'is_bush' ? $component->bush_ipl_num : '' }}"
                       data-url="{{ route('components.updateFlags', ['component' => $component->id]) }}"
                       @disabled($partMutationLocked)
                       @checked((bool) ($component->{$flagField} ?? false))>
            </td>
        @endforeach
        @if($showManualColumn)
            <td class="text-center">
                @if($component->manual)
                    <a href="#"
                       data-bs-toggle="modal"
                       data-bs-target="#manualModal{{ $component->manual->id }}">
                        {{ $component->manual->number }}
                    </a>
                @else
                    <span class="text-muted">-</span>
                @endif
            </td>
        @endif
        <td class="text-center">
            <div class="d-inline-flex align-items-center justify-content-center gap-2">
                <button type="button"
                        class="btn btn-outline-primary btn-sm {{ $editButtonClass }}"
                        data-component-url="{{ route('components.showJson', ['component' => $component->id]) }}"
                        data-update-url="{{ route('components.update', ['component' => $component->id]) }}"
                        @disabled($partMutationLocked)
                        @if($partMutationLocked) title="{{ __('Manual parts are locked') }}" @endif>
                    <i class="bi bi-pencil-square"></i>
                </button>
                <form action="{{ route('components.destroy', $component->id) }}" method="POST" class="m-0">
                    @csrf
                    @method('DELETE')
                    @if($deleteRedirect)
                        <input type="hidden" name="redirect" value="{{ $deleteRedirect }}">
                    @endif
                    <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure you want to delete this component?');" @disabled($partMutationLocked) @if($partMutationLocked) title="{{ __('Manual parts are locked') }}" @endif>
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
            </div>
        </td>
    </tr>
@endforeach
