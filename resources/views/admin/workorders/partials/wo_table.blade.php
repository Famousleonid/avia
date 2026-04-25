<div class="table-wrapper p-2 pt-0 ready" id="printArea">
    <table id="show-workorder" class="table table-sm table-bordered table-hover w-100 table-panel">
        <thead class="bg-gradient">
        <tr>
            <th class="text-center text-primary sortable col-number" data-sort="number">
                Number <i class="bi bi-chevron-expand ms-1"></i>
            </th>

            <th class="text-center text-primary col-approve no-print">
                Approve
            </th>

            @hasanyrole('Admin|Manager')
            <th class="text-center text-primary col-stages no-print">
                Stages
            </th>
            @endhasanyrole

            <th class="text-center text-primary">Component</th>
            <th class="text-center text-primary">Description</th>
            <th class="text-center text-primary">Serial number</th>
            <th class="text-center text-primary no-print">Manual</th>

            <th class="text-center text-primary sortable" data-sort="customer">
                Customer <i class="bi bi-chevron-expand ms-1"></i>
            </th>

            <th class="text-center text-primary sortable" data-sort="instruction">
                Instruction <i class="bi bi-chevron-expand ms-1"></i>
            </th>

            <th class="text-center text-primary sortable col-date" data-sort="open_at">
                Open Date <i class="bi bi-chevron-expand ms-1"></i>
            </th>

            <th class="text-center text-primary col-date">
                Customer PO
            </th>

            <th class="text-center text-primary col-edit no-print">
                Edit
            </th>

            <th class="text-center text-primary sortable no-print" data-sort="technik">
                Technik <i class="bi bi-chevron-expand ms-1"></i>
            </th>

            @role('Admin')
            <th class="text-center text-primary col-delete no-print">
                Delete
            </th>
            @endrole
        </tr>
        </thead>

        <tbody>
        @forelse ($workorders as $workorder)
            @php
                $unit = $workorder->unit ?? null;
                $customer = $workorder->customer ?? null;
                $instruction = $workorder->instruction ?? null;
                $technik = $workorder->user ?? null;

                $manual = null;
                if ($unit && isset($unit->manuals)) {
                    if ($unit->manuals instanceof \Illuminate\Support\Collection) {
                        $manual = $unit->manuals->first();
                    } else {
                        $manual = $unit->manuals;
                    }
                }

                $openAt = $workorder->open_at ?? null;
                $openAtFormatted = '';
                $openAtSort = '';

                try {
                    if ($openAt instanceof \Carbon\CarbonInterface) {
                        $openAtFormatted = $openAt->format('d.m.Y');
                        $openAtSort = $openAt->format('Ymd');
                    } elseif (!empty($openAt)) {
                        $openAtFormatted = \Illuminate\Support\Carbon::parse($openAt)->format('d.m.Y');
                        $openAtSort = \Illuminate\Support\Carbon::parse($openAt)->format('Ymd');
                    }
                } catch (\Throwable $e) {
                    $openAtFormatted = (string) $openAt;
                    $openAtSort = '';
                }

                $approveAt = $workorder->approve_at ?? null;
                $approveAtIso = '';
                $approveTitle = '';

                try {
                    if ($approveAt instanceof \Carbon\CarbonInterface) {
                        $approveAtIso = $approveAt->format('Y-m-d');
                        $approveTitle = $approveAt->format('d.m.Y') . ' ' . ($workorder->approve_name ?? '');
                    } elseif (!empty($approveAt)) {
                        $approveAtIso = \Illuminate\Support\Carbon::parse($approveAt)->format('Y-m-d');
                        $approveTitle = \Illuminate\Support\Carbon::parse($approveAt)->format('d.m.Y') . ' ' . ($workorder->approve_name ?? '');
                    }
                } catch (\Throwable $e) {
                    $approveAtIso = '';
                    $approveTitle = '';
                }

                $isDone = false;
                try {
                    $isDone = method_exists($workorder, 'isDone') ? (bool) $workorder->isDone() : false;
                } catch (\Throwable $e) {
                    $isDone = false;
                }

                $description = $workorder->description ?? '';
                $serial = $workorder->serial_number ?? '';
                $amdt = $workorder->amdt ?? null;
                $customerPo = $workorder->customer_po ?? '';

                $byGt = collect();
                $mainsByTask = collect();

                try {
                    $byGt = $workorder->generalTaskStatuses
                        ? $workorder->generalTaskStatuses->keyBy('general_task_id')
                        : collect();
                } catch (\Throwable $e) {
                    $byGt = collect();
                }

                try {
                    $mainsByTask = $workorder->main
                        ? $workorder->main->whereNotNull('task_id')->keyBy('task_id')
                        : collect();
                } catch (\Throwable $e) {
                    $mainsByTask = collect();
                }
            @endphp

            <tr
                data-id="{{ $workorder->id }}"
                data-tech-id="{{ $workorder->user_id }}"
                data-customer-id="{{ $workorder->customer_id }}"
                data-approved="{{ !empty($approveAtIso) ? '1' : '0' }}"
                data-draft="{{ !empty($workorder->is_draft) ? '1' : '0' }}"
                data-status="{{ $isDone ? 'Completed' : 'active' }}"
                @if(!empty($onlyActive) && $isDone) style="display:none;" @endif
            >
                <td class="text-center">
                    @if($isDone)
                        <a href="{{ route('mains.show', $workorder->id) }}" class="text-decoration-none" data-spinner>
                            <span class="text-muted">{{ $workorder->number }}</span>
                        </a>
                    @elseif(!empty($workorder->is_draft))
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
                       data-approve-at="{{ $approveAtIso }}"
                       data-approve-title="{{ $approveTitle }}"
                       onclick="return false;">
                        @if(!empty($approveAtIso))
                            <img class="approve-icon"
                                 src="{{ asset('img/ok.png') }}"
                                 width="20"
                                 title="{{ $approveTitle }}">
                        @else
                            <img class="approve-icon"
                                 src="{{ asset('img/icon_no.png') }}"
                                 width="12">
                        @endif
                    </a>
                    @else
                        @if(!empty($approveAtIso))
                            <img src="{{ asset('img/ok.png') }}"
                                 width="20"
                                 title="{{ $approveTitle }}">
                        @else
                            <img src="{{ asset('img/icon_no.png') }}"
                                 width="12">
                        @endif
                        @endhasanyrole
                </td>

                @hasanyrole('Admin|Manager')
                <td class="text-center no-print">
                    <div class="d-inline-flex gap-1 align-items-center">
                        @foreach($generalTasks as $gt)
                            @php
                                $st = $byGt->get($gt->id);

                                $gtTasks = $tasksByGeneral->get($gt->id, collect());
                                $started = $gtTasks->pluck('id')->contains(fn($tid) => $mainsByTask->has($tid));

                                if (!$started) {
                                    $class = 'empty';
                                    $title = $gt->name . ' (not started)';
                                } elseif ($st && !empty($st->is_done)) {
                                    $class = 'done';
                                    $title = $gt->name . ' (done)';
                                } else {
                                    $class = 'todo';
                                    $title = $gt->name . ' (in progress)';
                                }
                            @endphp

                            <span class="stage-dot {{ $class }}" title="{{ $title }}"></span>
                        @endforeach
                    </div>
                </td>
                @endhasanyrole

                <td class="text-center">
                    {{ $unit?->part_number ?? '' }}
                </td>

                <td class="text-center"
                    data-bs-toggle="tooltip"
                    title="{{ $description }}">
                    {{ $description }}
                </td>

                <td class="text-center">
                    {{ $serial }}
                    @if(!empty($amdt) && (int)$amdt > 0)
                        Amdt {{ $amdt }}
                    @endif
                </td>

                <td class="text-center no-print">
                    {{ $manual?->number ?? '' }}&nbsp;
                    <span class="text-white-50">({{ $manual?->lib ?? '' }})</span>
                </td>

                <td class="text-center"
                    data-bs-toggle="tooltip"
                    title="{{ $customer?->name ?? '' }}">
                    {{ $customer?->name ?? '' }}
                </td>

                <td class="text-center">
                    {{ $instruction?->name ?? '' }}
                </td>

                <td class="text-center">
                    @if($openAtFormatted !== '')
                        <span style="display:none">{{ $openAtSort }}</span>
                        {{ $openAtFormatted }}
                    @else
                        <span style="display:none"></span>
                    @endif
                </td>

                <td class="text-center td-customer_po">
                    {{ $customerPo }}
                </td>

                <td class="text-center no-print">
                    <a href="{{ route('workorders.edit', $workorder->id) }}">
                        <img src="{{ asset('img/set.png') }}" width="30" alt="Edit">
                    </a>
                </td>

                <td class="text-center td-technik no-print">
                    {{ $technik?->name ?? '' }}
                </td>

                @role('Admin')
                <td class="text-center no-print">
                    <div class="d-flex justify-content-center gap-1">
                        <form id="deleteForm_{{ $workorder->id }}"
                              action="{{ route('workorders.destroy', $workorder->id) }}"
                              method="POST"
                              style="display:inline;">
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
                                  method="POST"
                                  style="display:inline;">
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
            <tr>
                <td colspan="20" class="text-center text-muted py-4">
                    No workorders found
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>

    @if(method_exists($workorders, 'links'))
        <div class="mt-2 no-print" id="workordersPagination">
            {{ $workorders->links() }}
        </div>
    @endif
</div>
