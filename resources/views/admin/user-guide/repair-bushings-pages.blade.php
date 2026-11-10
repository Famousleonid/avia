@php
    // Whole articles stay together when the guide orders them from the contents.
    $repairBushingPages = [
        ['id' => 'workorder-processes', 'key' => 'rm', 'number' => '6.1', 'group' => 'Repair & Modification', 'blocks' => [
            ['image' => 'repair-editor.png', 'steps' => [1]],
            ['image' => 'repair-fields.png', 'steps' => [2, 3, 4]],
        ]],
        ['id' => 'repair-templates', 'key' => 'templates', 'number' => '6.2', 'group' => 'Repair & Modification', 'blocks' => [
            ['image' => 'repair-templates.png', 'steps' => [1, 2, 3, 4], 'split' => true],
        ]],
        ['id' => 'repair-form', 'key' => 'rmForm', 'number' => '6.3', 'group' => 'Repair & Modification', 'blocks' => [
            ['image' => 'repair-form.png', 'steps' => [1, 2, 3, 4], 'split' => true],
        ]],
        ['id' => 'bushing-selection', 'key' => 'bushSelect', 'number' => '7.1', 'group' => 'Bushings', 'blocks' => [
            ['image' => 'bushing-selection.png', 'steps' => [1, 2, 3, 4], 'split' => true],
        ]],
        ['id' => 'bushing-processes', 'key' => 'bushProcesses', 'number' => '7.2', 'group' => 'Bushings', 'blocks' => [
            ['image' => 'bushing-processes.png', 'steps' => [1, 2, 3]],
            ['image' => 'bushing-save.png', 'steps' => [4], 'caption' => false],
        ]],
        ['id' => 'bushing-batches', 'key' => 'bushBatches', 'number' => '7.3', 'group' => 'Bushings', 'blocks' => [
            ['image' => 'bushing-batches.png', 'steps' => [1, 2, 3, 4]],
        ]],
        ['id' => 'bushing-forms', 'key' => 'bushForms', 'number' => '7.4', 'group' => 'Bushings', 'blocks' => [
            ['image' => 'bushing-form-selection.png', 'steps' => [1, 2]],
            ['image' => 'bushing-form.png', 'steps' => [3, 4], 'split' => true],
        ]],
    ];
@endphp
@foreach($repairBushingPages as $pageIndex => $guideSection)
    @php($prefix = 'rb'.$guideSection['key'])
    <article class="guide-page guide-page--repair-bushings" id="{{ $guideSection['id'] }}" data-guide-page data-guide-order="{{ 32.5 + $pageIndex / 100 }}">
        <div class="chapter-label">{{ $guideSection['number'] }} · {{ $guideSection['group'] }}</div>
        <h2 class="page-title" data-i18n="{{ $prefix }}Title">{{ $repairBushingGuideTranslations['en'][$prefix.'Title'] }}</h2>
        @foreach($guideSection['blocks'] as $block)
            <div class="guide-repair-bushing-block {{ !empty($block['split']) ? 'guide-page__split' : '' }}">
                <figure class="guide-figure">
                    <div class="guide-photo-shot">
                        <img src="{{ asset('img/user-guide/'.$block['image']) }}?v={{ filemtime(public_path('img/user-guide/'.$block['image'])) }}" alt="{{ $repairBushingGuideTranslations['en'][$prefix.'Caption'] }}" data-i18n-alt="{{ $prefix }}Caption">
                    </div>
                    @if($block['caption'] ?? true)
                        <figcaption data-i18n="{{ $prefix }}Caption">{{ $repairBushingGuideTranslations['en'][$prefix.'Caption'] }}</figcaption>
                    @endif
                </figure>
                <div class="guide-copy guide-copy--single">
                    <ol class="guide-log-card-steps guide-log-card-steps--wide" start="{{ $block['steps'][0] }}">
                        @foreach($block['steps'] as $step)
                            <li data-i18n="{{ $prefix }}Step{{ $step }}">{{ $repairBushingGuideTranslations['en'][$prefix.'Step'.$step] }}</li>
                        @endforeach
                    </ol>
                </div>
            </div>
        @endforeach
    </article>
@endforeach
