@forelse($cards as $card)
    @php
        $wo = $card['workorder'];
    @endphp
    <section class="ws-card" data-workorder="{{ $wo->id }}">
        <div class="ws-identity" tabindex="0" aria-label="Toggle all panels for w{{ $wo->number }}">
            <a class="ws-number" href="{{ route('mains.show', $wo->id) }}">w{{ $wo->number }}</a>
            <span @class(['ws-active' => !$wo->done_at && $wo->approve_at, 'ws-awaiting-approval' => !$wo->done_at && !$wo->approve_at])>{{ $wo->done_at ? 'Completed' : 'approve' }}@if(!$wo->done_at && $wo->approve_at) (@projectDate($wo->approve_at))@endif</span>
            <span>{{ $wo->unit?->part_number }}</span><span class="ws-secondary-data">S/N {{ $wo->serial_number ?: '—' }}</span>
            <span>{{ $wo->customer?->name ?: 'No customer' }}</span>
            @php
                $manualEcd = $wo->expected_completion_date?->copy()->startOfDay();
                $manualEcdClass = !$manualEcd ? '' : ($manualEcd->lt(today()) ? 'ws-esd-overdue' : ($manualEcd->lte(today()->addDays(14)) ? 'ws-esd-soon' : 'ws-esd-future'));
            @endphp
            <form class="ws-ecd-form" action="{{ route('workorders.status.completion-date', $wo) }}" data-no-spinner>
                <label for="ws-ecd-{{ $wo->id }}">ECD:</label>
                <input id="ws-ecd-{{ $wo->id }}" class="ws-ecd-input {{ $manualEcdClass }}" name="expected_completion_date" type="text" data-project-date autocomplete="off" placeholder="dd/Mmm/yyyy" value="{{ format_project_date($manualEcd) }}" data-saved="{{ format_project_date($manualEcd) }}" aria-label="ECD for w{{ $wo->number }}">
                <button type="button" class="ws-ecd-calendar-button ws-ecd-save" data-ws-calendar aria-label="Open ECD calendar for w{{ $wo->number }}" title="Choose ECD"><i class="bi bi-calendar3"></i></button>
                <span class="ws-ecd-message" role="status"></span>
            </form>
            @if($wo->approve_at)
                <span class="ws-estimate ws-secondary-data" title="Routes start at Approval in parallel. Each part follows its processes sequentially; STD lists follow their own sequence. Actual return dates adjust remaining time. Traveler uses its longest included process duration.">Process days: <span>{{ $card['duration_days'] }}</span></span>
                @php
                    $esd = $card['isd']?->copy()->startOfDay();
                    $esdClass = !$esd ? '' : ($esd->lt(today()) ? 'ws-esd-overdue' : ($esd->lte(today()->addDays(14)) ? 'ws-esd-soon' : 'ws-esd-future'));
                @endphp
                <span class="ws-estimate">Calculated ECD: <span class="{{ $esdClass }}">@if($esd)@projectDate($esd)@else — @endif</span></span>
                <button type="button" class="ws-remaining-toggle" data-ws-forecast data-ws-remaining="ws-{{ $wo->id }}-forecast" aria-haspopup="dialog" aria-expanded="false" aria-controls="ws-{{ $wo->id }}-forecast" aria-label="Process days breakdown for w{{ $wo->number }}" title="Process days breakdown"><i class="bi bi-eye"></i></button>
                <template id="ws-{{ $wo->id }}-forecast-template">
                    @include('admin.workorders.partials.status-forecast')
                </template>
            @endif
        </div>
        <div class="ws-halves">
            <div class="ws-half">
                <button type="button" class="ws-half-toggle" data-ws-detail="ws-{{ $wo->id }}-processes" aria-controls="ws-{{ $wo->id }}-processes" aria-expanded="{{ in_array("ws-{$wo->id}-processes", $uiState['open'] ?? [], true) ? 'true' : 'false' }}">
                    <span class="ws-half-label">Parts / Processes</span>
                    <span>Total <strong>{{ $card['process_total_qty'] }}</strong></span>
                    <span>Unfinished <strong class="{{ $card['unfinished_qty'] ? 'ws-pending' : 'ws-clear' }}">{{ $card['unfinished_qty'] }}</strong></span>
                    <i class="bi bi-chevron-down"></i>
                </button>
                <div class="ws-detail" id="ws-{{ $wo->id }}-processes" @if(!in_array("ws-{$wo->id}-processes", $uiState['open'] ?? [], true)) hidden @endif>
                    @include('admin.workorders.partials.status-processes', ['rows' => $card['processes'], 'sentOnly' => false])
                </div>
            </div>
            <div class="ws-half ws-ec">
                <button type="button" class="ws-half-toggle" data-ws-detail="ws-{{ $wo->id }}-ec" aria-controls="ws-{{ $wo->id }}-ec" aria-expanded="{{ in_array("ws-{$wo->id}-ec", $uiState['open'] ?? [], true) ? 'true' : 'false' }}">
                    <span class="ws-half-label">EC</span><strong>{{ $card['ec']->count() }}</strong><i class="bi bi-chevron-down"></i>
                </button>
                <div class="ws-detail" id="ws-{{ $wo->id }}-ec" @if(!in_array("ws-{$wo->id}-ec", $uiState['open'] ?? [], true)) hidden @endif>
                    <table class="dir-table ws-table ws-ec-table"><colgroup><col style="width:28%"><col style="width:12%"><col style="width:30%"><col style="width:30%"></colgroup><thead><tr><th>Part</th><th>EC</th><th>Sent</th><th>Received</th></tr></thead><tbody>
                    @forelse($card['ec'] as $ec)
                        @php
                            $component = $ec->tdr?->component ?? $ec->tdr?->orderComponent;
                            $partTitle = trim(($component?->ipl_num ?? '').' · '.($component?->part_number ?? '').' · '.($component?->name ?: $ec->description ?: $ec->tdr?->description));
                        @endphp
                        <tr><td title="{{ $partTitle }}">{{ $component?->part_number ?: $component?->name ?: '—' }}</td><td class="num" title="{{ $ec->date_finish ? 'EC received' : ($ec->date_start ? 'EC sent' : 'EC open') }}{{ $ec->concession_number ? ' · '.$ec->concession_number : '' }}">
                            @if($ec->date_finish)
                                <i class="bi bi-box-arrow-in-left ws-ec-returned" role="img" aria-label="EC received"></i>
                            @elseif($ec->date_start)
                                <i class="bi bi-arrow-right ws-ec-sent" role="img" aria-label="EC sent"></i>
                            @else
                                <span class="ws-ec-open" aria-label="EC open">EC</span>
                            @endif
                        </td><td title="{{ format_project_date($ec->date_start) }}">{{ format_project_date($ec->date_start) ?: '—' }}</td><td title="{{ format_project_date($ec->date_finish) }}">{{ format_project_date($ec->date_finish) ?: '—' }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="ws-empty">No EC records.</td></tr>
                    @endforelse
                    </tbody></table>
                </div>
            </div>
            <div class="ws-half">
                <button type="button" class="ws-half-toggle" data-ws-detail="ws-{{ $wo->id }}-parts" aria-controls="ws-{{ $wo->id }}-parts" aria-expanded="{{ in_array("ws-{$wo->id}-parts", $uiState['open'] ?? [], true) ? 'true' : 'false' }}">
                    <span class="ws-half-label">Replacement Parts</span>
                    <span>Ordered <strong>{{ $card['ordered_qty'] }}</strong></span>
                    <span>Missing <strong class="{{ $card['missing_qty'] ? 'ws-pending' : 'ws-clear' }}">{{ $card['missing_qty'] }}</strong></span>
                    <i class="bi bi-chevron-down"></i>
                </button>
                <div class="ws-detail" id="ws-{{ $wo->id }}-parts" @if(!in_array("ws-{$wo->id}-parts", $uiState['open'] ?? [], true)) hidden @endif>
                    @if($card['parts']->isEmpty())
                        <div class="ws-empty">No outstanding quantities recorded.</div>
                    @else
                        <table class="dir-table ws-table ws-receipt-table"><colgroup><col style="width:12%"><col style="width:22%"><col style="width:24%"><col style="width:8%"><col style="width:11%"><col style="width:12%"><col style="width:11%"></colgroup><thead><tr><th>IPL</th><th>Part number</th><th>Description</th><th>List</th><th class="num">Ordered</th><th class="num">Received</th><th class="num">Missing</th></tr></thead><tbody>
                        @foreach($card['parts'] as $part)
                            <tr><td title="{{ $part['ipl'] }}">{{ $part['ipl'] }}</td><td title="{{ $part['pn'] }}">{{ $part['pn'] ?: '—' }}</td><td title="{{ $part['name'] }}">{{ $part['name'] }}</td><td>{{ $part['kit'] ? 'KIT' : 'PRL' }}</td><td class="num">{{ $part['ordered'] }}</td><td class="num">{{ $part['received'] }}</td><td class="num ws-missing">{{ $part['missing'] }}</td></tr>
                        @endforeach
                        </tbody></table>
                    @endif
                </div>
            </div>
        </div>
    </section>
@empty
    <div class="ws-empty">No workorders match these filters.</div>
@endforelse
