@php
    $availableTravelerTemplates = \App\Services\TravelerProcessTemplates::forTdr($current_tdr)
        ->with('steps.process.process_name')->orderBy('name')->get();
    $appliedTravelerTemplates = \Illuminate\Support\Facades\DB::table('tdr_traveler_template_applications')
        ->where('tdr_id', $current_tdr->id)->get()
        ->filter(fn ($application) => \App\Services\TravelerProcessTemplates::applicationHasProcesses($application))
        ->pluck('template_id')->all();
    $autoTemplate = $availableTravelerTemplates->count() === 1 ? $availableTravelerTemplates->first() : null;
    $canApplyTemplate = $autoTemplate && !in_array($autoTemplate->id, $appliedTravelerTemplates);
    $templateBlockedReason = $canApplyTemplate ? '' : ($availableTravelerTemplates->count() > 1
        ? __('Multiple active templates: keep one active template for this P/N in the manual.')
        : ($autoTemplate ? __('Processes from the previous template application still remain. The template cannot be applied again until all those processes are removed.')
            : __('No active template for this P/N')));
@endphp
<style>
    @property --traveler-border-angle {
        syntax: '<angle>';
        initial-value: 0deg;
        inherits: false;
    }
    .traveler-template-apply {
        position: relative;
        isolation: isolate;
    }
    .traveler-template-apply .traveler-template-count {
        color: #ffc107;
    }
    .traveler-template-apply--available::after {
        content: '';
        position: absolute;
        inset: -1px;
        border-radius: inherit;
        padding: 2px;
        pointer-events: none;
        background: conic-gradient(from var(--traveler-border-angle), transparent 0deg 280deg, #0dcaf0 305deg, #b5f5ff 322deg, #fff 334deg 348deg, #b5f5ff 354deg, transparent 360deg);
        -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
        -webkit-mask-composite: xor;
        mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
        mask-composite: exclude;
        animation: traveler-template-border-run 4.3s linear infinite;
    }
    @keyframes traveler-template-border-run {
        0% { --traveler-border-angle: 0deg; opacity: 0; }
        2% { opacity: 1; }
        33% { opacity: 1; }
        35% { --traveler-border-angle: 360deg; opacity: 0; }
        100% { --traveler-border-angle: 360deg; opacity: 0; }
    }
    @media (prefers-reduced-motion: reduce) {
        .traveler-template-apply--available::after { animation: none; opacity: 0; }
    }
</style>
<div class="position-relative" data-traveler-picker data-template-id="{{ $autoTemplate?->id }}" data-tdr-id="{{ $current_tdr->id }}" data-url="{{ route('tdr.traveler-template.apply', $current_tdr) }}">
    <button type="button" class="btn btn-sm btn-outline-info text-nowrap traveler-template-apply {{ $availableTravelerTemplates->count() === 1 ? 'traveler-template-apply--available' : '' }} {{ $canApplyTemplate ? '' : 'opacity-50' }}" data-template-apply
            data-blocked-reason="{{ $templateBlockedReason }}" @if(!$canApplyTemplate) aria-disabled="true" @endif
            title="{{ $templateBlockedReason ?: $autoTemplate?->name }}">Apply template (<span class="traveler-template-count">{{ $availableTravelerTemplates->count() }}</span>)</button>
    <div class="text-danger small mt-1 d-none" role="alert" data-template-error></div>
    <script type="application/json" data-template-data>{!! $availableTravelerTemplates->toJson(JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
</div>
