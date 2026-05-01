@php
    /** Drag … Part, Processes + колонка Date sent — объединённые с подписью Step */
    $leadCols = ($canReorderMachining ?? false) ? 7 : 6;
    $stepLeadColspan = $leadCols + 1;
    $fmt = static function ($d) {
        if ($d === null) {
            return '';
        }

        return $d->format('d').'.'.strtolower($d->format('M')).'.'.$d->format('Y');
    };
    $stepsOrdered = $parentForSteps->machiningWorkSteps->sortBy('step_index')->values();
    $hasAnyStepFinish = $stepsOrdered->contains(static fn ($s) => filled($s->date_finish ?? null));
    $s1ForEff = $stepsOrdered->firstWhere('step_index', 1);
    $effStartStep1 = $s1ForEff?->date_start;
    if ($effStartStep1 === null && $hasAnyStepFinish && $parentForSteps->date_start !== null) {
        $effStartStep1 = $parentForSteps->date_start;
    }
@endphp
@for($si = 1; $si <= $stepCount; $si++)
    @php
        $stepRow = $stepsOrdered->firstWhere('step_index', $si);
        $effStart = $si === 1
            ? $effStartStep1
            : $stepsOrdered->firstWhere('step_index', $si - 1)?->date_finish;
        $finishYmd = $stepRow?->date_finish?->format('Y-m-d') ?? '';
        $finishDisp = $fmt($stepRow?->date_finish);
        $stepSearch = implode(' ', array_filter([
            'step'.$si,
            $finishDisp,
            (string) ($stepRow?->machinist?->name ?? ''),
            trim((string) ($stepRow?->description ?? '')),
            $si === 1 && $parentForSteps instanceof \App\Models\TdrProcess ? trim((string) ($parentForSteps->description ?? '')) : '',
        ]));
        $stepSearchNorm = function_exists('mb_strtolower')
            ? mb_strtolower($stepSearch, 'UTF-8')
            : strtolower($stepSearch);
        $stepMachinistCsv = '';
        if ($stepRow && (int) ($stepRow->machinist_user_id ?? 0) > 0) {
            $stepMachinistCsv = (string) (int) $stepRow->machinist_user_id;
        }
    @endphp
    <tr @if($si === 1) id="machining-steps-body-{{ $machiningGroupId }}" @endif
        data-machining-group="{{ $machiningGroupId }}"
        data-wo-id="{{ (int) ($woId ?? 0) }}"
        data-machining-search="{{ $machiningSearch }} {{ $stepSearchNorm }}"
        data-machining-finish-ymd="{{ $rowFinishYmd ?? '' }}"
        data-machining-machinist-ids="{{ $stepMachinistCsv }}"
        @if($rowHasDateFinish) data-machining-closed="1" @endif
        @if(! empty($machiningWoMasterIsExtra)) data-machining-wo-extra="1" @endif
        class="machining-row-child machining-row-unqueued {{ $isBushingRow ? 'machining-row-bushing' : '' }} {{ ! empty($collapseStepRowsDefault) ? 'd-none' : '' }}">
        <td colspan="{{ $stepLeadColspan }}" class="machining-step-lead-cell small text-secondary py-2">
            @if($stepRow)
                <div class="d-flex align-items-center gap-2 flex-nowrap w-100 machining-step-lead-row">
                    <label class="visually-hidden" for="mach-step-desc-{{ $stepRow->id }}">Step {{ $si }} note</label>
                    <textarea id="mach-step-desc-{{ $stepRow->id }}"
                              class="form-control form-control-sm js-machining-step-description flex-grow-1 min-w-0"
                              rows="1"
                              placeholder="Step note…"
                              data-step-patch-url="{{ route('machining.work_steps.update', $stepRow) }}"
                              style="min-width: 8rem; min-height: calc(1.5em + .5rem + 2px); max-height: 4rem; resize: vertical;">{{ $stepRow->description }}</textarea>
                    <span class="text-info flex-shrink-0">Step {{ $si }}</span>
                </div>
            @else
                <span class="text-info">Step {{ $si }}</span>
            @endif
            @if($si === 1 && $parentForSteps instanceof \App\Models\TdrProcess)
                @php $machiningLeadDesc = trim((string) ($parentForSteps->description ?? '')); @endphp
                @if($machiningLeadDesc !== '')
                    <div class="small text-muted mt-1 text-wrap machining-step-parent-desc"
                         style="max-height: 5rem; overflow-y: auto; line-height: 1.25;"
                         title="{{ e($machiningLeadDesc) }}">{{ $machiningLeadDesc }}</div>
                @endif
            @endif
        </td>
        <td class="text-center machining-col-work align-middle">
            @if($stepRow)
                <label class="visually-hidden" for="machinist-step-{{ $stepRow->id }}">Machinist step {{ $si }}</label>
                <select id="machinist-step-{{ $stepRow->id }}"
                        class="form-select form-select-sm dir-input js-machining-step-machinist"
                        data-step-patch-url="{{ route('machining.work_steps.update', $stepRow) }}"
                        autocomplete="off">
                    <option value="">—</option>
                    @foreach($machiningMachinists as $mu)
                        <option value="{{ (int) $mu->id }}" @selected((int) ($stepRow->machinist_user_id ?? 0) === (int) $mu->id)>{{ $mu->name }}</option>
                    @endforeach
                </select>
            @else
                <span class="text-muted small">—</span>
            @endif
        </td>
        <td class="machining-col-date-cell">
            <input type="text"
                   readonly
                   tabindex="-1"
                   class="form-control form-control-sm finish-input machining-date-readonly w-100 {{ $effStart ? 'has-finish' : '' }}"
                   value="{{ $fmt($effStart) }}"
                   title="Effective start (from parent or previous step)">
        </td>
        <td class="machining-col-date-cell">
            @if($stepRow)
                <form method="POST"
                      action="{{ route('machining.work_steps.update', $stepRow) }}"
                      class="js-ajax mb-0 js-machining-step-finish-form"
                      data-no-spinner
                      data-success="Saved"
                      autocomplete="off">
                    @csrf
                    @method('PATCH')
                    <div class="machining-date-input-wrap">
                        <input type="hidden"
                               name="date_finish"
                               value="{{ $finishYmd }}"
                               class="js-machining-date-ymd"
                               data-original="{{ $finishYmd }}">
                        <input type="text"
                               readonly
                               value="{{ $finishDisp }}"
                               class="form-control form-control-sm finish-input machining-native-date machining-date-display {{ $finishYmd !== '' ? 'has-finish' : '' }} {{ $finishYmd !== '' ? '' : 'machining-date-empty' }}"
                               tabindex="0"
                               inputmode="none"
                               autocomplete="off">
                        <span class="machining-date-fake-ph" aria-hidden="true">…</span>
                        <input type="date"
                               class="js-machining-picker-aid"
                               value="{{ $finishYmd }}"
                               tabindex="-1"
                               aria-hidden="true">
                    </div>
                </form>
            @else
                <input type="text"
                       readonly
                       tabindex="-1"
                       class="form-control form-control-sm finish-input machining-date-readonly w-100"
                       placeholder="…"
                       value="">
            @endif
        </td>
        <td class="text-center small machining-col-wrap"></td>
    </tr>
@endfor
