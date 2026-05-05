@foreach($components as $component)
    @php
        $assemblyRows = $component->relationLoaded('assemblies')
            ? $component->assemblies
            : collect();
        $assemblyRows = $assemblyRows->filter(function ($assembly) {
            return filled($assembly->assy_ipl_num ?? null)
                || filled($assembly->assy_part_number ?? null);
        })->values();

        if ($assemblyRows->isEmpty() && (
            filled($component->assy_ipl_num)
            || filled($component->assy_part_number)
        )) {
            $assemblyRows = collect([(object) [
                'assy_ipl_num' => $component->assy_ipl_num,
                'assy_part_number' => $component->assy_part_number,
                'units_assy' => $component->units_assy,
                'notes' => null,
            ]]);
        }

        $firstAssembly = $assemblyRows->first();
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
    <tr data-manual-id="{{ $component->manual_id ?? '' }}">
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
        <td class="text-center">
            <div class="d-inline-flex align-items-center justify-content-center gap-2">
                <button type="button"
                        class="btn btn-outline-primary btn-sm open-edit-component-drawer"
                        data-component-url="{{ route('components.showJson', ['component' => $component->id]) }}"
                        data-update-url="{{ route('components.update', ['component' => $component->id]) }}">
                    <i class="bi bi-pencil-square"></i>
                </button>
                <form action="{{ route('components.destroy', $component->id) }}" method="POST" class="m-0">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure you want to delete this component?');">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
            </div>
        </td>
    </tr>
@endforeach
