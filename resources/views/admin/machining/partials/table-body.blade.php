                            @php $machiningRowsSeq = $rows->values(); @endphp
                            @forelse ($rows as $row)
                                @php
                                    $wo = $row->workorder;
                                    $woIdInt = (int) $wo->id;
                                    $machiningLineCountForWo = (int) ($machiningLinesPerWo->get($woIdInt)
                                        ?? $machiningLinesPerWo->get((string) $woIdInt)
                                        ?? 1);
                                    $prevRow = $loop->index > 0 ? $machiningRowsSeq->get($loop->index - 1) : null;
                                    $isFirstMachiningLineForWo = $loop->first
                                        || $prevRow === null
                                        || (int) $prevRow->workorder->id !== $woIdInt;
                                    $machiningWoMasterIsExtra = ! $isFirstMachiningLineForWo && $machiningLineCountForWo > 1;
                                    $editTp = $row->edit_machining_process;
                                    $rowSource = $row->row_source ?? 'tdr';
                                    $isBushingRow = $rowSource === 'bushing';
                                    $bushingBatch = $row->bushing_batch ?? null;
                                    $bushingProcess = $row->bushing_process ?? null;
                                    $fmtMachiningDisp = static function ($d) {
                                        if ($d === null) {
                                            return '';
                                        }
                                        return $d->format('d') . '.' . strtolower($d->format('M')) . '.' . $d->format('Y');
                                    };
                                    $startStr = $fmtMachiningDisp($row->date_start);
                                    $finishStr = $fmtMachiningDisp($row->date_finish);
                                    if ($bushingBatch) {
                                        $tpStartYmd = $bushingBatch->date_start?->format('Y-m-d') ?? '';
                                        $tpStartDisp = $fmtMachiningDisp($bushingBatch->date_start);
                                        $tpFinishYmd = $bushingBatch->date_finish?->format('Y-m-d') ?? '';
                                        $tpFinishDisp = $fmtMachiningDisp($bushingBatch->date_finish);
                                    } elseif ($bushingProcess) {
                                        $tpStartYmd = $bushingProcess->date_start?->format('Y-m-d') ?? '';
                                        $tpStartDisp = $fmtMachiningDisp($bushingProcess->date_start);
                                        $tpFinishYmd = $bushingProcess->date_finish?->format('Y-m-d') ?? '';
                                        $tpFinishDisp = $fmtMachiningDisp($bushingProcess->date_finish);
                                    } else {
                                        $tpStartYmd = $editTp?->date_start?->format('Y-m-d') ?? '';
                                        $tpStartDisp = $fmtMachiningDisp($editTp?->date_start);
                                        $tpFinishYmd = $editTp?->date_finish?->format('Y-m-d') ?? '';
                                        $tpFinishDisp = $fmtMachiningDisp($editTp?->date_finish);
                                    }
                                    $canEditMachiningDates = $editTp || $bushingBatch || $bushingProcess;
                                    $dateStartAction = $editTp
                                        ? route('tdrprocesses.updateDate', $editTp)
                                        : ($bushingBatch
                                            ? route('wo_bushing_batches.updateDate', $bushingBatch)
                                            : ($bushingProcess ? route('wo_bushing_processes.updateDate', $bushingProcess) : ''));
                                    $dateFinishAction = $dateStartAction;
                                    $machiningSearchBlob = implode(' ', array_filter([
                                        'w' . $wo->number,
                                        (string) ($wo->customer?->name ?? ''),
                                        (string) ($wo->unit?->manual?->plane?->type ?? ''),
                                        (string) ($wo->unit?->part_number ?? ''),
                                        (string) ($wo->user?->name ?? ''),
                                        (string) ($row->detail_label ?? ''),
                                        (string) ($row->detail_name ?? ''),
                                        $isBushingRow ? 'bushing' : null,
                                        $startStr,
                                        $finishStr,
                                    ]));
                                    $machiningSearch = function_exists('mb_strtolower')
                                        ? mb_strtolower($machiningSearchBlob, 'UTF-8')
                                        : strtolower($machiningSearchBlob);
                                    $rowFinishForQueue = $row->date_finish ?? null;
                                    $rowHasDateFinish = $rowFinishForQueue !== null
                                        && ($rowFinishForQueue instanceof \DateTimeInterface || trim((string) $rowFinishForQueue) !== '');
                                    $parentForSteps = $editTp ?? $bushingBatch ?? $bushingProcess;
                                    $stepCount = (int) ($parentForSteps?->working_steps_count ?? 0);
                                    /** По умолчанию step-строки свёрнуты; раскрытие — из localStorage в JS. */
                                    $collapseMachiningStepRows = $stepCount >= 1;
                                    $machiningGroupId = '';
                                    if ($editTp) {
                                        $machiningGroupId = 'tdp-'.$editTp->id;
                                    } elseif ($bushingBatch) {
                                        $machiningGroupId = 'wbb-'.$bushingBatch->id;
                                    } elseif ($bushingProcess) {
                                        $machiningGroupId = 'wbp-'.$bushingProcess->id;
                                    }
                                    $stepsCountUrl = $editTp
                                        ? route('machining.tdr_working_steps_count', $editTp)
                                        : ($bushingBatch
                                            ? route('machining.batch_working_steps_count', $bushingBatch)
                                            : ($bushingProcess ? route('machining.process_working_steps_count', $bushingProcess) : ''));
                                    $parentHasStart = (bool) ($parentForSteps?->date_start);
                                @endphp
                                <tr data-wo-id="{{ $woIdInt }}"
                                    data-machining-search="{{ $machiningSearch }}"
                                    @if($machiningGroupId !== '') data-machining-group="{{ $machiningGroupId }}" @endif
                                    @if($rowHasDateFinish) data-machining-closed="1" @endif
                                    @if($machiningWoMasterIsExtra) data-machining-wo-extra="1" @endif
                                    class="{{ $wo->machining_queue_order !== null ? 'machining-row-queued' : 'machining-row-unqueued' }} {{ ($row->is_queue_master ?? false) ? 'machining-row-master' : '' }} {{ $isBushingRow ? 'machining-row-bushing' : '' }}">
                                    @if($canReorderMachining ?? false)
                                        <td class="text-center {{ $wo->machining_queue_order !== null && ($row->is_queue_master ?? false) ? 'machining-drag-handle' : '' }}"
                                            @if($wo->machining_queue_order !== null && ($row->is_queue_master ?? false)) title="Drag" @endif>
                                            @if($wo->machining_queue_order !== null && ($row->is_queue_master ?? false))
                                                <i class="bi bi-three-dots-vertical " aria-hidden="true"></i>
                                            @endif
                                        </td>
                                    @endif
                                    <td class="text-center align-middle machining-col-priority">
                                        @if($canReorderMachining ?? false)
                                            @if($wo->machining_queue_order === null && ($row->is_queue_master ?? false))
                                                <input type="text"
                                                       inputmode="numeric"
                                                       autocomplete="off"
                                                       class="form-control js-machining-position-input dir-input"
                                                       data-wo-id="{{ (int) $wo->id }}"
                                                       data-in-queue="0"
                                                       data-was="0"
                                                       value=""
                                                       title="Enter queue position (0 = not in queue)">
                                            @elseif($wo->machining_queue_order !== null && ($row->is_queue_master ?? false))
                                                <input type="text"
                                                       inputmode="numeric"
                                                       autocomplete="off"
                                                       class="form-control js-machining-position-input dir-input"
                                                       data-wo-id="{{ (int) $wo->id }}"
                                                       data-in-queue="1"
                                                       data-was="{{ (int) $row->machining_queue_position }}"
                                                       value="{{ (int) $row->machining_queue_position }}"
                                                       title="Position in queue for whole WO (0 = remove all parts from queue)">
                                            @elseif($wo->machining_queue_order !== null)
                                                {{-- Очередь на workorder — один номер на все machining-part этой WO --}}
                                                {{ (int) $row->machining_queue_position }}
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        @else
                                            @if($wo->machining_queue_order !== null)
                                                {{ $row->machining_queue_position }}
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="text-center text-light machining-wo-label machining-col-ellipsis">
                                        @if($isFirstMachiningLineForWo && $machiningLineCountForWo > 1)
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-secondary py-0 px-1 me-2
                                                    js-machining-toggle-wo-parts mb-1"
                                                    data-wo-parts="{{ $woIdInt }}"
                                                    aria-expanded="false"
                                                    title="Show other machining parts for this WO"
                                                    aria-label="Show other machining parts for this WO">
                                                <i class="bi bi-chevron-down" aria-hidden="true"></i>
                                            </button>
                                        @endif
                                        W{{ $wo->number }}
                                        <br>
                                        @if($wo->user_id && $wo->user)
                                            <button type="button"
                                                    class="btn btn-link btn-sm  p-0 js-machining-msg-owner"
                                                    data-user-id="{{ (int) $wo->user_id }}">
                                                {{ $wo->user->name }}
                                            </button>
                                        @endif
                                    </td>
                                    <td class="text-center small machining-col-wrap">{{ $wo->customer?->name ?? '' }}</td>
                                    @if($isFirstMachiningLineForWo && $machiningLineCountForWo > 1)
                                        <td class="text-center small machining-col-wrap js-machining-wo-head-col">
                                            <span class="machining-wo-head-col-placeholder text-muted">...</span>
                                            <span class="machining-wo-head-col-content d-none">{{ $wo->unit?->part_number ?? '' }}
                                                <span class=" text-secondary ms-1"> ({{$wo->unit?->manual?->lib ?? ''}}) </span> <br>
                                                <span class=" text-secondary">{{ $wo->unit?->manual?->plane?->type ?? ''}}</span>
                                            </span>
                                        </td>
                                    @else
                                        <td class="text-center small machining-col-wrap">{{ $wo->unit?->part_number ?? '' }}
                                            <span class=" text-secondary ms-1"> ({{$wo->unit?->manual?->lib ?? ''}}) </span> <br>
                                            <span class=" text-secondary">{{ $wo->unit?->manual?->plane?->type ?? ''}}</span>
                                        </td>
                                    @endif
                                    @if($isFirstMachiningLineForWo && $machiningLineCountForWo > 1)
                                        <td class="text-center machining-col-wrap js-machining-wo-head-col">
                                            <span class="machining-wo-head-col-placeholder text-muted">...</span>
                                            <span class="machining-wo-head-col-content d-none">{{ $row->detail_name ?? 'Name' }} <br>
                                                <span class="text-secondary">{{ $row->detail_label ?? 'List' }}</span>
                                            </span>
                                        </td>
                                    @else
                                        <td class="text-center machining-col-wrap">{{ $row->detail_name ?? 'Name' }} <br>
                                            <span class="text-secondary">{{ $row->detail_label ?? 'List' }}</span>
                                        </td>
                                    @endif
                                    @if($isFirstMachiningLineForWo && $machiningLineCountForWo > 1)
                                        <td class="text-center machining-col-work align-middle js-machining-wo-head-col">
                                            <span class="machining-wo-head-col-placeholder text-muted">...</span>
                                            <div class="machining-wo-head-col-content d-none">
                                            <div class="d-flex align-items-center gap-1 machining-steps-controls mx-auto" style="max-width: 100%;">
                                            @if($parentForSteps && $stepsCountUrl !== '')
                                                <label class="visually-hidden" for="machining-steps-{{ $machiningGroupId }}">Working steps (N)</label>
                                                <input type="number"
                                                       min="1"
                                                       max="50"
                                                       step="1"
                                                       class="form-control form-control-sm dir-input js-machining-steps-count text-center machining-steps-n-input"
                                                       id="machining-steps-{{ $machiningGroupId }}"
                                                       data-steps-url="{{ $stepsCountUrl }}"
                                                       value="{{ $stepCount >= 1 ? $stepCount : '' }}"
                                                       placeholder="N"
                                                       title="Number of working steps (1–50); set start date first"
                                                       @if(! $parentHasStart) disabled @endif
                                                       autocomplete="off">
                                            @endif
                                            @if($stepCount >= 1 && $machiningGroupId !== '')
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-secondary py-0 px-1  ms-2
                                                        js-machining-toggle-steps flex-shrink-0"
                                                        data-steps-group="{{ $machiningGroupId }}"
                                                        aria-expanded="{{ $collapseMachiningStepRows ? 'false' : 'true' }}"
                                                        aria-controls="machining-steps-body-{{ $machiningGroupId }}"
                                                        title="{{ $collapseMachiningStepRows ? 'Show step rows' : 'Hide step rows' }}"
                                                        aria-label="{{ $collapseMachiningStepRows ? 'Show step rows' : 'Hide step rows' }}">
                                                    <i class="bi {{ $collapseMachiningStepRows ? 'bi-chevron-down' : 'bi-chevron-up' }}" aria-hidden="true"></i>
                                                </button>
                                            @endif
                                            </div>
                                            </div>
                                        </td>
                                    @else
                                        <td class="text-center machining-col-work align-middle">
                                        <div class="d-flex align-items-center gap-1 machining-steps-controls mx-auto" style="max-width: 100%;">
                                            @if($parentForSteps && $stepsCountUrl !== '')
                                                <label class="visually-hidden" for="machining-steps-{{ $machiningGroupId }}">Working steps (N)</label>
                                                <input type="number"
                                                       min="1"
                                                       max="50"
                                                       step="1"
                                                       class="form-control form-control-sm dir-input js-machining-steps-count text-center machining-steps-n-input"
                                                       id="machining-steps-{{ $machiningGroupId }}"
                                                       data-steps-url="{{ $stepsCountUrl }}"
                                                       value="{{ $stepCount >= 1 ? $stepCount : '' }}"
                                                       placeholder="N"
                                                       title="Number of working steps (1–50); set start date first"
                                                       @if(! $parentHasStart) disabled @endif
                                                       autocomplete="off">
                                            @endif
                                            @if($stepCount >= 1 && $machiningGroupId !== '')
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-secondary py-0 px-1  ms-2
                                                        js-machining-toggle-steps flex-shrink-0"
                                                        data-steps-group="{{ $machiningGroupId }}"
                                                        aria-expanded="{{ $collapseMachiningStepRows ? 'false' : 'true' }}"
                                                        aria-controls="machining-steps-body-{{ $machiningGroupId }}"
                                                        title="{{ $collapseMachiningStepRows ? 'Show step rows' : 'Hide step rows' }}"
                                                        aria-label="{{ $collapseMachiningStepRows ? 'Show step rows' : 'Hide step rows' }}">
                                                    <i class="bi {{ $collapseMachiningStepRows ? 'bi-chevron-down' : 'bi-chevron-up' }}" aria-hidden="true"></i>
                                                </button>
                                            @endif
                                        </div>
                                        </td>
                                    @endif
                                    @if($isFirstMachiningLineForWo && $machiningLineCountForWo > 1)
                                        <td class="machining-col-date-cell js-machining-wo-head-col">
                                            <span class="machining-wo-head-col-placeholder text-muted d-block w-100 text-center">...</span>
                                            <div class="machining-wo-head-col-content d-none w-100">
                                        @if ($canEditMachiningDates)
                                            <form method="POST"
                                                  action="{{ $dateStartAction }}"
                                                  class="js-ajax mb-0"
                                                  data-no-spinner
                                                  data-success="Saved"
                                                  autocomplete="off">
                                                @csrf
                                                @method('PATCH')
                                                @if($editTp || $bushingBatch || $bushingProcess)
                                                    <input type="hidden" name="from_machining_index" value="1">
                                                @endif
                                                <div class="machining-date-input-wrap">
                                                    <input type="hidden"
                                                           name="date_start"
                                                           value="{{ $tpStartYmd }}"
                                                           class="js-machining-date-ymd"
                                                           data-original="{{ $tpStartYmd }}">
                                                    <input type="text"
                                                           readonly
                                                           value="{{ $tpStartDisp }}"
                                                           class="form-control form-control-sm finish-input machining-native-date machining-date-display {{ $tpStartYmd !== '' ? 'has-finish' : '' }} {{ $tpStartYmd !== '' ? '' : 'machining-date-empty' }}"
                                                           tabindex="0"
                                                           inputmode="none"
                                                           autocomplete="off">
                                                    <span class="machining-date-fake-ph" aria-hidden="true">…</span>
                                                    <input type="date"
                                                           class="js-machining-picker-aid"
                                                           value="{{ $tpStartYmd }}"
                                                           tabindex="-1"
                                                           aria-hidden="true">
                                                </div>
                                            </form>
                                        @elseif ($startStr !== '')
                                            <input type="text"
                                                   readonly
                                                   tabindex="-1"
                                                   class="form-control form-control-sm finish-input has-finish machining-date-readonly w-100"
                                                   value="{{ $startStr }}">
                                        @else
                                            <input type="text"
                                                   readonly
                                                   tabindex="-1"
                                                   class="form-control form-control-sm finish-input machining-date-readonly w-100"
                                                   placeholder="…"
                                                   value="">
                                        @endif
                                            </div>
                                        </td>
                                    @else
                                    <td class="machining-col-date-cell">
                                        @if ($canEditMachiningDates)
                                            <form method="POST"
                                                  action="{{ $dateStartAction }}"
                                                  class="js-ajax mb-0"
                                                  data-no-spinner
                                                  data-success="Saved"
                                                  autocomplete="off">
                                                @csrf
                                                @method('PATCH')
                                                @if($editTp || $bushingBatch || $bushingProcess)
                                                    <input type="hidden" name="from_machining_index" value="1">
                                                @endif
                                                <div class="machining-date-input-wrap">
                                                    <input type="hidden"
                                                           name="date_start"
                                                           value="{{ $tpStartYmd }}"
                                                           class="js-machining-date-ymd"
                                                           data-original="{{ $tpStartYmd }}">
                                                    <input type="text"
                                                           readonly
                                                           value="{{ $tpStartDisp }}"
                                                           class="form-control form-control-sm finish-input machining-native-date machining-date-display {{ $tpStartYmd !== '' ? 'has-finish' : '' }} {{ $tpStartYmd !== '' ? '' : 'machining-date-empty' }}"
                                                           tabindex="0"
                                                           inputmode="none"
                                                           autocomplete="off">
                                                    <span class="machining-date-fake-ph" aria-hidden="true">…</span>
                                                    <input type="date"
                                                           class="js-machining-picker-aid"
                                                           value="{{ $tpStartYmd }}"
                                                           tabindex="-1"
                                                           aria-hidden="true">
                                                </div>
                                            </form>
                                        @elseif ($startStr !== '')
                                            <input type="text"
                                                   readonly
                                                   tabindex="-1"
                                                   class="form-control form-control-sm finish-input has-finish machining-date-readonly w-100"
                                                   value="{{ $startStr }}">
                                        @else
                                            <input type="text"
                                                   readonly
                                                   tabindex="-1"
                                                   class="form-control form-control-sm finish-input machining-date-readonly w-100"
                                                   placeholder="…"
                                                   value="">
                                        @endif
                                    </td>
                                    @endif
                                    @if($isFirstMachiningLineForWo && $machiningLineCountForWo > 1)
                                        <td class="machining-col-date-cell js-machining-wo-head-col">
                                            <span class="machining-wo-head-col-placeholder text-muted d-block w-100 text-center">...</span>
                                            <div class="machining-wo-head-col-content d-none w-100">
                                        @if ($canEditMachiningDates && $stepCount >= 1)
                                            <input type="text"
                                                   readonly
                                                   tabindex="-1"
                                                   class="form-control form-control-sm finish-input machining-date-readonly w-100 {{ $tpFinishYmd !== '' ? 'has-finish' : '' }}"
                                                   title="Finish is driven by the last step"
                                                   value="{{ $tpFinishDisp }}"
                                                   placeholder="…">
                                        @elseif ($canEditMachiningDates)
                                            <form method="POST"
                                                  action="{{ $dateFinishAction }}"
                                                  class="js-ajax mb-0"
                                                  data-no-spinner
                                                  data-success="Saved"
                                                  autocomplete="off">
                                                @csrf
                                                @method('PATCH')
                                                @if($editTp || $bushingBatch || $bushingProcess)
                                                    <input type="hidden" name="from_machining_index" value="1">
                                                @endif
                                                <div class="machining-date-input-wrap">
                                                    <input type="hidden"
                                                           name="date_finish"
                                                           value="{{ $tpFinishYmd }}"
                                                           class="js-machining-date-ymd"
                                                           data-original="{{ $tpFinishYmd }}">
                                                    <input type="text"
                                                           readonly
                                                           value="{{ $tpFinishDisp }}"
                                                           class="form-control form-control-sm finish-input machining-native-date machining-date-display {{ $tpFinishYmd !== '' ? 'has-finish' : '' }} {{ $tpFinishYmd !== '' ? '' : 'machining-date-empty' }}"
                                                           tabindex="0"
                                                           inputmode="none"
                                                           autocomplete="off">
                                                    <span class="machining-date-fake-ph" aria-hidden="true">…</span>
                                                    <input type="date"
                                                           class="js-machining-picker-aid"
                                                           value="{{ $tpFinishYmd }}"
                                                           tabindex="-1"
                                                           aria-hidden="true">
                                                </div>
                                            </form>
                                        @elseif ($finishStr !== '')
                                            <input type="text"
                                                   readonly
                                                   tabindex="-1"
                                                   class="form-control form-control-sm finish-input has-finish machining-date-readonly w-100"
                                                   value="{{ $finishStr }}">
                                        @else
                                            <input type="text"
                                                   readonly
                                                   tabindex="-1"
                                                   class="form-control form-control-sm finish-input machining-date-readonly w-100"
                                                   placeholder="…"
                                                   value="">
                                        @endif
                                            </div>
                                        </td>
                                    @else
                                    <td class="machining-col-date-cell">
                                        @if ($canEditMachiningDates && $stepCount >= 1)
                                            <input type="text"
                                                   readonly
                                                   tabindex="-1"
                                                   class="form-control form-control-sm finish-input machining-date-readonly w-100 {{ $tpFinishYmd !== '' ? 'has-finish' : '' }}"
                                                   title="Finish is driven by the last step"
                                                   value="{{ $tpFinishDisp }}"
                                                   placeholder="…">
                                        @elseif ($canEditMachiningDates)
                                            <form method="POST"
                                                  action="{{ $dateFinishAction }}"
                                                  class="js-ajax mb-0"
                                                  data-no-spinner
                                                  data-success="Saved"
                                                  autocomplete="off">
                                                @csrf
                                                @method('PATCH')
                                                @if($editTp || $bushingBatch || $bushingProcess)
                                                    <input type="hidden" name="from_machining_index" value="1">
                                                @endif
                                                <div class="machining-date-input-wrap">
                                                    <input type="hidden"
                                                           name="date_finish"
                                                           value="{{ $tpFinishYmd }}"
                                                           class="js-machining-date-ymd"
                                                           data-original="{{ $tpFinishYmd }}">
                                                    <input type="text"
                                                           readonly
                                                           value="{{ $tpFinishDisp }}"
                                                           class="form-control form-control-sm finish-input machining-native-date machining-date-display {{ $tpFinishYmd !== '' ? 'has-finish' : '' }} {{ $tpFinishYmd !== '' ? '' : 'machining-date-empty' }}"
                                                           tabindex="0"
                                                           inputmode="none"
                                                           autocomplete="off">
                                                    <span class="machining-date-fake-ph" aria-hidden="true">…</span>
                                                    <input type="date"
                                                           class="js-machining-picker-aid"
                                                           value="{{ $tpFinishYmd }}"
                                                           tabindex="-1"
                                                           aria-hidden="true">
                                                </div>
                                            </form>
                                        @elseif ($finishStr !== '')
                                            <input type="text"
                                                   readonly
                                                   tabindex="-1"
                                                   class="form-control form-control-sm finish-input has-finish machining-date-readonly w-100"
                                                   value="{{ $finishStr }}">
                                        @else
                                            <input type="text"
                                                   readonly
                                                   tabindex="-1"
                                                   class="form-control form-control-sm finish-input machining-date-readonly w-100"
                                                   placeholder="…"
                                                   value="">
                                        @endif
                                    </td>
                                    @endif
                                        <td class="text-center small machining-col-wrap">
{{--                                            @if($isBushingRow)--}}
{{--                                                <span class="badge text-bg-secondary rounded-pill">Bush</span>--}}
{{--                                            @endif--}}
                                        </td>
                                </tr>
                                @if($stepCount >= 1 && $parentForSteps && $machiningGroupId !== '')
                                    @include('admin.machining.partials.work-step-rows', [
                                        'parentForSteps' => $parentForSteps,
                                        'stepCount' => $stepCount,
                                        'machiningGroupId' => $machiningGroupId,
                                        'machiningSearch' => $machiningSearch,
                                        'rowHasDateFinish' => $rowHasDateFinish,
                                        'collapseStepRowsDefault' => $collapseMachiningStepRows,
                                        'woId' => $woIdInt,
                                        'machiningWoMasterIsExtra' => $machiningWoMasterIsExtra,
                                        'isBushingRow' => $isBushingRow,
                                        'machiningMachinists' => $machiningMachinists,
                                        'canReorderMachining' => $canReorderMachining ?? false,
                                    ])
                                @endif
                            @empty
                                <tr>
                                    <td colspan="{{ ($canReorderMachining ?? false) ? 11 : 10 }}" class="text-center text-muted py-4">No workorders (approved, open, not draft).</td>
                                </tr>
                            @endforelse
