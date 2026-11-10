@if($retainedManualLines->isNotEmpty())
    <section class="m-2 p-2 border border-warning rounded bushing-retained-manual-lines">
        <strong>{{ __('Saved assignments from previous manuals') }}</strong>
        <p class="small text-muted mb-2">{{ __('These assignments are read-only and remain unchanged when you save or clear the current list. Review specifications before moving them to the current manual.') }}</p>
        <div class="table-responsive" style="max-height: 16rem; overflow: auto;">
            <table class="table table-sm table-bordered mb-0" style="table-layout: fixed; width: 100%; overflow-wrap: anywhere;">
                <thead><tr><th style="width: 30%">{{ __('Part / Manual') }}</th><th style="width: 12%">{{ __('Qty') }}</th><th>{{ __('Saved processes') }}</th></tr></thead>
                <tbody>
                @foreach($retainedManualLines as $line)
                    <tr data-retained-bushing-line="{{ $line->id }}">
                        <td>{{ $line->component?->ipl_num }} — {{ $line->component?->part_number ?: '#'.$line->component_id }}
                            <div class="small text-muted">{{ $line->component?->manual?->number }}</div>
                        </td>
                        <td>{{ $line->qty }}</td>
                        <td>
                            @forelse($line->processes as $assignment)
                                <div class="mb-1"><strong>{{ $assignment->process?->process_name?->name }}</strong>:
                                    {{ $assignment->process?->process ?: '#'.$assignment->process_id }}
                                    @if($assignment->batch?->repair_order ?: $assignment->repair_order)
                                        <span class="text-muted">— {{ $assignment->batch?->repair_order ?: $assignment->repair_order }}</span>
                                    @endif
                                </div>
                            @empty
                                <span class="text-muted">{{ __('No processes assigned') }}</span>
                            @endforelse
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endif
