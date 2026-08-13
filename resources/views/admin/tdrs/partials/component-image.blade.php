@php
    $componentImage = $component?->primaryImageMedia();
@endphp

@if($componentImage)
    <a href="{{ $component->primaryImageBigUrl() }}"
       data-fancybox="{{ $gallery ?? 'tdr-component-images' }}"
       title="{{ $component->part_number ?? __('Part image') }}">
        <img src="{{ $component->primaryImageThumbnailUrl() }}"
             alt="{{ $component->part_number ?? __('Part image') }}"
             class="tdr-component-thumb"
             width="30"
             height="30"
             loading="lazy">
    </a>
@endif
