@php
    $colspan = 13;
    if (auth()->user()->hasAnyRole('Admin|Manager')) {
        $colspan++;
    }
    if (auth()->user()->roleIs('Admin')) {
        $colspan++;
    }
@endphp

@forelse ($workorders as $workorder)
    @php
        $isDone = $workorder->isDone();
        $ec = $ecStatuses[$workorder->id] ?? null;
        $ecState = $ec['state'] ?? 'none';

        $startDate = optional($ec['date_start'] ?? null)->format('d.m.Y');
        $finishDate = optional($ec['date_finish'] ?? null)->format('d.m.Y');

        $startBy = $ec['date_start_user'] ?? ($ec['user_name'] ?? '');
        $finishBy = $ec['date_finish_user'] ?? ($ec['user_name'] ?? '');

        $startDateText = $startDate ?: '—';
        $finishDateText = $finishDate ?: '—';

        $ecTitle = '';
        if ($ecState === 'none') {
            $ecTitle = 'There is no EC process';
        } elseif ($ecState === 'exists') {
            $ecTitle = 'EC: open: ' . ($startBy ?: ' ');
        } elseif ($ecState === 'started') {
            $ecTitle = 'EC: start ' . $startDateText . ' (' . ($startBy ?: '—') . ').';
        } elseif ($ecState === 'finished') {
            $ecTitle = 'EC: start ' . $startDateText . ' (' . ($startBy ?: '—') . '); finish ' . $finishDateText . ' (' . ($finishBy ?: '—') . ').';
        } else {
            $ecTitle = 'EC: —';
        }
    @endphp

    <tr
        data-id="{{ $workorder->id }}"
        data-tech-id="{{ $workorder->user_id }}"
        data-customer-id="{{ $workorder->customer_id }}"
        data-status="{{ $isDone ? 'Completed' : 'active' }}"
        data-approved="{{ $workorder->approve_at ? '1' : '0' }}"
        data-draft="{{ $workorder->is_draft ? '1' : '0' }}"
    >
        <td class="text-center">
            @if($isDone)
                <a href="{{ route('mains.show', $workorder->id) }}" class="text-decoration-none" data-spinner>
                    <span class="text-muted">{{ $workorder->number }}</span>
                </a>
            @elseif($workorder->is_draft)
                <a href="{{ route('mains.show', $workorder->id) }}" class="text-decoration-none" data-spinner>
                    <span style="font-size: 16px; color: yellowgreen;">
                        Draft&nbsp;{{ $workorder->number }}
                    </span>
                </a>
            @else
                <a href="{{ route('mains.show', $workorder->id) }}" class="text-decoration-none" data-spinner>
                    <span style="font-size: 16px; color: #0DDDFD;">
                        w&nbsp;{{ $workorder->number }}
                    </span>
                </a>
            @endif
        </td>

        <td class="text-center no-print">
            @hasanyrole('Admin|Manager')
            <a href="#"
               class="approve-btn"
               data-id="{{ $workorder->id }}"
               data-approve-at="{{ $workorder->approve_at ? $workorder->approve_at->format('Y-m-d') : '' }}"
               data-approve-title="{{ $workorder->approve_at ? ($workorder->approve_at->format('d.m.Y') . ' ' . $workorder->approve_name) : '' }}"
               onclick="return false;">
                @if($workorder->approve_at)
                    <img class="approve-icon"
                         src="{{ asset('img/ok.png') }}" width="20"
                         title="{{ $workorder->approve_at->format('d.m.Y') }} {{ $workorder->approve_name }}">
                @else
                    <img class="approve-icon" src="{{ asset('img/icon_no.png') }}" width="12">
                @endif
            </a>
            @else
                @if($workorder->approve_at)
                    <img src="{{ asset('img/ok.png') }}" width="20"
                         title="{{ $workorder->approve_at->format('d.m.Y') }} {{ $workorder->approve_name }}">
                @else
                    <img src="{{ asset('img/icon_no.png') }}" width="12">
                @endif
            @endhasanyrole
        </td>

        <td class="text-center no-print" title="{{ $ecTitle }}">
            @if($ecState === 'none')
                <img class="ec-icon-img" src="{{ asset('img/icon_no.png') }}" alt="EC none">
            @elseif($ecState === 'exists')
                <i class="ec-open-arrow">EC</i>
            @elseif($ecState === 'started')
                <i class="bi bi-arrow-right ec-arrow"></i>
            @elseif($ecState === 'finished')
                <i class="bi bi-box-arrow-in-left ec-arrow-finish"></i>
            @else
                <img class="ec-icon-img" src="{{ asset('img/icon_no.png') }}" alt="EC unknown">
            @endif
        </td>

        @hasanyrole('Admin|Manager')
        <td class="text-center no-print">
            @php
                $byGt = $workorder->generalTaskStatuses->keyBy('general_task_id');
                $mainsByTask = $workorder->main
                    ? $workorder->main->whereNotNull('task_id')->keyBy('task_id')
                    : collect();
            @endphp

            <div class="stage-strip">
                <span class="stage-strip-segments" aria-hidden="true">
                @foreach($generalTasks as $gt)
                    @php
                        $st = $byGt->get($gt->id);
                        $gtTasks = $tasksByGeneral->get($gt->id, collect());
                        $started = $gtTasks->pluck('id')->contains(fn($tid) => $mainsByTask->has($tid));

                        if (!$started) {
                            $class = 'empty';
                        } elseif ($st && $st->is_done) {
                            $class = 'done';
                        } else {
                            $class = 'todo';
                        }
                    @endphp
                    <span class="stage-seg {{ $class }}"></span>
                @endforeach
                </span>
            </div>
        </td>
        @endhasanyrole

        <td class="text-center">{{ data_get($workorder, 'unit.part_number', '-') }}</td>

        <td class="text-center" data-bs-toggle="tooltip" title="{{ $workorder->description }}">
            {{ $workorder->description }}
        </td>

        <td class="text-center">
            {{ $workorder->serial_number }}
            @if($workorder->amdt > 0)
                Amdt {{ $workorder->amdt }}
            @endif
        </td>

        <td class="text-center no-print">
            {{ data_get($workorder, 'unit.manual.number', '-') }}
            @if(data_get($workorder, 'unit.manual.lib'))
                <span class="text-white-50">({{ data_get($workorder, 'unit.manual.lib') }})</span>
            @endif
        </td>

        <td class="text-center" data-bs-toggle="tooltip" title="{{ data_get($workorder, 'customer.name', '') }}">
            {{ data_get($workorder, 'customer.name', '—') }}
        </td>

        <td class="text-center">
            {{ data_get($workorder, 'instruction.name', '—') }}
        </td>

        <td class="text-center">
            @if($workorder->open_at)
                <span style="display: none">{{ $workorder->open_at->format('Ymd') }}</span>
                {{ $workorder->open_at->format('d.m.Y') }}
            @else
                <span style="display: none"></span>
            @endif
        </td>

        <td class="text-center td-customer_po">
            {{ $workorder->customer_po }}
        </td>

        <td class="text-center no-print">
            <a href="{{ route('workorders.edit', $workorder->id) }}">
                <img src="{{ asset('img/set.png') }}" width="25" alt="Edit">
            </a>
        </td>

        <td class="text-center td-technik no-print">
            {{ data_get($workorder, 'user.name', '—') }}
        </td>

        @role('Admin')
        <td class="text-center no-print">
            <div class="d-flex justify-content-center gap-1">
                <form id="deleteForm_{{ $workorder->id }}"
                      action="{{ route('workorders.destroy', $workorder->id) }}"
                      method="POST" style="display:inline;">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger"
                            type="button"
                            name="btn_delete"
                            data-bs-toggle="modal"
                            data-bs-target="#useConfirmDelete"
                            data-form-id="deleteForm_{{ $workorder->id }}"
                            data-title="Delete Confirmation WO {{ $workorder->number }}"
                            data-message="Soft-delete workorder {{ $workorder->number }}? This will hide it from active records. Permanent deletion is irreversible.">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
                @systemadmin
                    <form id="forceDeleteForm_{{ $workorder->id }}"
                          action="{{ route('workorders.forceDestroy', $workorder->id) }}"
                          method="POST" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-danger"
                                type="button"
                                name="btn_force_delete"
                                title="Permanently delete workorder {{ $workorder->number }}"
                                data-bs-toggle="modal"
                                data-bs-target="#useConfirmDelete"
                                data-form-id="forceDeleteForm_{{ $workorder->id }}"
                                data-title="Permanent Delete WO {{ $workorder->number }}"
                                data-message="Permanently delete workorder {{ $workorder->number }} from the database? This action is irreversible.">
                            <i class="bi bi-trash-fill"></i>
                        </button>
                    </form>
                @endsystemadmin
            </div>
        </td>
        @endrole
    </tr>
@empty
    <tr class="wo-empty-row">
        <td colspan="{{ $colspan }}" class="text-center text-muted py-4">
            Workorders not found
        </td>
    </tr>
@endforelse
