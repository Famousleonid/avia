@extends('admin.master')

@section('content')
<style>
.sortable {
    cursor: pointer;
    user-select: none;
}

.sortable:hover {
    background-color: rgba(0, 0, 0, 0.05);
}

.sortable i {
    transition: transform 0.2s ease;
}

.sortable[data-direction="asc"] i {
    transform: rotate(180deg);
}

.sortable[data-direction="desc"] i {
    transform: rotate(0deg);
}
</style>
    <div class="card shadow">
        <div class="card-header my-1 shadow d-flex justify-content-between align-items-center">
            <h5 class="text-primary manage-header">{{__('Manual')}}: {{$manual->number}} — {{$manual->title}}</h5>
            <div>
                <a href="{{ route('components.create', ['manual_id' => $manual->id, 'redirect' => request()->fullUrl()]) }}" class="btn btn-outline-primary me-2">{{ __('Add Component') }}</a>
                <a href="{{ route('components.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
            </div>
        </div>

        @if($components->count())
            <div class="table-responsive p-2">
                <table id="componentsTable" class="table table-sm table-hover table-striped align-middle table-bordered">
                    <thead>
                    <tr>
                        <th class="text-center sortable" data-sort="ipl-num">{{ __('IPL Number') }} <i class="bi bi-chevron-expand ms-1"></i></th>
                        <th class="text-center sortable" data-sort="name">{{ __('Component Description') }} <i class="bi bi-chevron-expand ms-1"></i></th>
                        <th class="text-center sortable" data-sort="part-number">{{ __('Part Number') }} <i class="bi bi-chevron-expand ms-1"></i></th>
                        <th class="text-center">{{ __('Image') }}</th>
                        <th class="text-center">{{ __('Assy') }}</th>
                        <th class="text-center">{{ __('Action') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($components as $component)
                        <tr>
                            <td class="text-center">{{$component->ipl_num}}</td>
                            <td class="text-center">{{$component->name}}</td>
                            <td class="text-center">{{$component->part_number}}</td>
                            <td class="text-center" style="width: 120px;">
                                <a href="{{ $component->getFirstMediaBigUrl('components') }}" data-fancybox="gallery">
                                    <img class="rounded-circle" src="{{ $component->getFirstMediaThumbnailUrl('components') }}" width="40" height="40" alt="IMG"/>
                                </a>
                            </td>
                            <td class="text-center" style="width: 120px;">
                                <a href="{{ $component->getFirstMediaBigUrl('assy_components') }}" data-fancybox="gallery">
                                    <img class="rounded-circle" src="{{ $component->getFirstMediaThumbnailUrl('assy_components') }}" width="40" height="40" alt="IMG"/>
                                </a>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('components.edit',['component' => $component->id]) }}" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <form action="{{ route('components.destroy', $component->id) }}" method="POST" style="display:inline-block;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Вы уверены, что хотите удалить этот компонент?');">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <h5 class="text-center p-3">{{ __('No components for this manual') }}</h5>
        @endif
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const table = document.getElementById('componentsTable');
    const headers = document.querySelectorAll('.sortable');
    
    headers.forEach(header => {
        header.addEventListener('click', () => {
            const columnIndex = Array.from(header.parentNode.children).indexOf(header);
            const rows = Array.from(table.querySelectorAll('tbody tr'));
            const direction = header.dataset.direction === 'asc' ? 'desc' : 'asc';
            
            // Update header direction
            header.dataset.direction = direction;
            
            // Update header icon
            const icon = header.querySelector('i');
            icon.className = direction === 'asc' ? 'bi bi-chevron-up ms-1' : 'bi bi-chevron-down ms-1';
            
            // Sort rows
            rows.sort((a, b) => {
                const aText = a.cells[columnIndex].innerText.trim();
                const bText = b.cells[columnIndex].innerText.trim();
                
                // Special sorting for IPL numbers
                if (header.dataset.sort === 'ipl-num') {
                    return sortIplNumbers(aText, bText, direction);
                }
                
                // Regular text sorting
                return direction === 'asc' ? aText.localeCompare(bText) : bText.localeCompare(aText);
            });
            
            // Reorder rows in table
            rows.forEach(row => table.querySelector('tbody').appendChild(row));
        });
    });
    
    // Custom sorting function for IPL numbers
    function sortIplNumbers(a, b, direction) {
        const aParts = a.split('-');
        const bParts = b.split('-);
        
        // Extract major and minor numbers
        const aMajor = parseInt(aParts[0]) || 0;
        const bMajor = parseInt(bParts[0]) || 0;
        
        // Compare major numbers first
        if (aMajor !== bMajor) {
            return direction === 'asc' ? aMajor - bMajor : bMajor - aMajor;
        }
        
        // If major numbers are equal, extract and compare minor parts
        const aMinorPart = aParts[1] || '';
        const bMinorPart = bParts[1] || '';
        
        // Extract numeric part and letter part
        const aMinorNum = parseInt(aMinorPart) || 0;
        const bMinorNum = parseInt(bMinorPart) || 0;
        const aMinorLetter = aMinorPart.replace(/\d+/g, '') || '';
        const bMinorLetter = bMinorPart.replace(/\d+/g, '') || '';
        
        // Compare numeric parts first
        if (aMinorNum !== bMinorNum) {
            return direction === 'asc' ? aMinorNum - bMinorNum : bMinorNum - aMinorNum;
        }
        
        // If numeric parts are equal, compare letters (empty string comes first)
        if (aMinorLetter === '' && bMinorLetter !== '') {
            return direction === 'asc' ? -1 : 1;
        }
        if (aMinorLetter !== '' && bMinorLetter === '') {
            return direction === 'asc' ? 1 : -1;
        }
        
        // If both have letters, compare them alphabetically
        return direction === 'asc' ? aMinorLetter.localeCompare(bMinorLetter) : bMinorLetter.localeCompare(aMinorLetter);
    }
});
</script>
@endpush


