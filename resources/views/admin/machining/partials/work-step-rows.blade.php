@php
    /** Drag + № + WO + Customer + Component + Part */
    $leadCols = ($canReorderMachining ?? false) ? 6 : 5;
    $fmt = static function ($d) {
        if ($d === null) {
            return '';
        }

        return $d->format('d').'.'.strtolower($d->format('M')).'.'.$d->format('Y');
    };
    $stepsOrdered = $parentForSteps->machiningWorkSteps->sortBy('step_index')->values();
@endphp
@for($si = 1; $si <= $stepCount; $si++)
    @php
        $stepRow = $stepsOrdered->firstWhere('step_index', $si);
        $effStart = $si === 1
            ? $parentForSteps->date_start
            : $stepsOrdered->firstWhere('step_index', $si - 1)?->date_finish;
        $finishYmd = $stepRow?->date_finish?->format('Y-m-d') ?? '';
        $finishDisp = $fmt($stepRow?->date_finish);
        $stepSearch = implode(' ', array_filter([
            'step'.$si,
            $finishDisp,
            (string) ($stepRow?->machinist?->name ?? ''),
        ]));
        $stepSearchNorm = function_exists('mb_strtolower')
            ? mb_strtolower($stepSearch, 'UTF-8')
            : strtolower($stepSearch);
    @endphp
    <tr @if($si === 1) id="machining-steps-body-{{ $machiningGroupId }}" @endif
        data-machining-group="{{ $machiningGroupId }}"
        data-wo-id="{{ (int) ($woId ?? 0) }}"
        data-machining-search="{{ $machiningSearch }} {{ $stepSearchNorm }}"
        @if($rowHasDateFinish) data-machining-closed="1" @endif
        @if(! empty($machiningWoMasterIsExtra)) data-machining-wo-extra="1" @endif
        class="machining-row-child machining-row-unqueued {{ $isBushingRow ? 'machining-row-bushing' : '' }} {{ ! empty($collapseStepRowsDefault) ? 'd-none' : '' }}">
        <td colspan="{{ $leadCols }}" class="machining-step-lead-cell small text-secondary py-2">
            <span class="text-info">Step {{ $si }}</span>
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
