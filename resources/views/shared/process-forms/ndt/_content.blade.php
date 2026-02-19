{{--
    Контент NDT формы (tdr-processes).
    Переменные: ndt_processes, ndt_components, ndt1_name_id..ndt8_name_id, manuals, current_wo
    formConfig: ndt_table_rows (default 17)
--}}
@php
    $ndt_processes_by_id = [];
    if (isset($ndt_processes) && is_iterable($ndt_processes)) {
        foreach ($ndt_processes as $process) {
            $ndt_processes_by_id[$process->process_names_id] = $process;
        }
    }
    $totalRows = $formConfig['ndt_table_rows'] ?? 17;
    $dataRows = isset($ndt_components) ? count($ndt_components) : (isset($table_data) ? count($table_data) : 0);
    $emptyRows = max(0, $totalRows - $dataRows);
    $rowIndex = 1;
@endphp

<div class="parent mt-3">
    <div class="div1">
        <div class="text-start fs-7 ndt-process-label"><strong>MAGNETIC PARTICLE AS PER:</strong></div>
        <div class="row ndt-process-row">
            <div class="col-1 fs-7">#1</div>
            <div class="col-10 border-b">
                @if(isset($ndt_processes_by_id[$ndt1_name_id ?? null]))
                    <span @if(strlen($ndt_processes_by_id[$ndt1_name_id]->process) > 20) class="process-text-long" @endif>{{ $ndt_processes_by_id[$ndt1_name_id]->process }}</span>
                @endif
            </div>
        </div>
        <div class="text-start fs-75 ndt-process-label"><strong>LIQUID/FLUID PENETRANT AS PER:</strong></div>
        <div class="row ndt-process-row">
            <div class="col-1 fs-7">#4</div>
            <div class="col-10 border-b">
                @if(isset($ndt_processes_by_id[$ndt4_name_id ?? null]))
                    <span @if(strlen($ndt_processes_by_id[$ndt4_name_id]->process) > 20) class="process-text-long" @endif>{{ $ndt_processes_by_id[$ndt4_name_id]->process }}</span>
                @endif
            </div>
        </div>
        <div class="text-start fs-7 ndt-process-label"><strong>ULTRASOUND AS PER:</strong></div>
        <div class="row ndt-process-row">
            <div class="col-1 fs-7">#7</div>
            <div class="col-10 border-b">
                @if(isset($ndt_processes_by_id[$ndt7_name_id ?? null]))
                    <span @if(strlen($ndt_processes_by_id[$ndt7_name_id]->process) > 20) class="process-text-long" @endif>{{ $ndt_processes_by_id[$ndt7_name_id]->process }}</span>
                @endif
            </div>
        </div>
    </div>
    <div class="div2">
        <div class="row ndt-process-row-tall mt-4">
            <div class="col-1 fs-7">#2</div>
            <div class="col-10 border-b">
                @if(isset($ndt_processes_by_id[$ndt2_name_id ?? null]))
                    <span @if(strlen($ndt_processes_by_id[$ndt2_name_id]->process) > 20) class="process-text-long" @endif>{{ $ndt_processes_by_id[$ndt2_name_id]->process }}</span>
                @endif
            </div>
        </div>
        <div class="row ndt-process-row-tall mt-4">
            <div class="col-1 fs-7">#5</div>
            <div class="col-10 border-b">
                @if(isset($ndt_processes_by_id[$ndt5_name_id ?? null]))
                    <span @if(strlen($ndt_processes_by_id[$ndt5_name_id]->process) > 25) class="process-text-long" @endif>{{ $ndt_processes_by_id[$ndt5_name_id]->process }}</span>
                @endif
            </div>
        </div>
        <div class="row ndt-process-row mt-4">
            <div class="col-1 fs-7">#8</div>
            <div class="col-10 border-b">
                @if(isset($ndt_processes_by_id[$ndt8_name_id ?? null]))
                    <span @if(strlen($ndt_processes_by_id[$ndt8_name_id]->process) > 25) class="process-text-long" @endif>{{ $ndt_processes_by_id[$ndt8_name_id]->process }}</span>
                @endif
            </div>
        </div>
    </div>
    <div class="div3">
        <div class="row ndt-process-row-tall mt-4">
            <div class="col-1 fs-7">#3</div>
            <div class="col-10 border-b">
                @if(isset($ndt_processes_by_id[$ndt3_name_id ?? null]))
                    <span @if(strlen($ndt_processes_by_id[$ndt3_name_id]->process) > 20) class="process-text-long" @endif>{{ $ndt_processes_by_id[$ndt3_name_id]->process }}</span>
                @endif
            </div>
        </div>
        <div class="text-start ms-3 fs-7 ndt-process-label"><strong>EDDY CURRENT AS PER:</strong></div>
        <div class="row ndt-process-row">
            <div class="col-1 fs-7 text-end">#6</div>
            <div class="col-10 border-b">
                @if(isset($ndt_processes_by_id[$ndt6_name_id ?? null]))
                    <span @if(strlen($ndt_processes_by_id[$ndt6_name_id]->process) > 40) class="process-text-long" @endif>{{ $ndt_processes_by_id[$ndt6_name_id]->process }}</span>
                @endif
            </div>
        </div>
        <div class="row ndt-process-row-cmm mt-2">
            <div class="col-4 fs-7 text-end mt-3"><strong>CMM No:</strong></div>
            <div class="col-8 border-all">
                @foreach($manuals as $manual)
                    @if($manual->id == $current_wo->unit->manual_id)
                        <h6 class="text-center mt-3"><strong>{{ substr($manual->number, 0, 8) }}</strong></h6>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
</div>

<div class="page table-header">
    <div class="row mt-2">
        <div class="col-1 border-l-t-b pt-2 details-row text-center"><h6 class="fs-75">ITEM No.</h6></div>
        <div class="col-3 border-l-t-b pt-2 details-row text-center"><h6 class="fs-75">Part No</h6></div>
        <div class="col-3 border-l-t-b pt-2 details-row text-center"><h6 class="fs-75">DESCRIPTION</h6></div>
        <div class="col-2 border-l-t-b pt-2 details-row text-center"><h6 class="fs-75">PROCESS No.</h6></div>
        <div class="col-1 border-l-t-b pt-2 details-row text-center"><h6 class="fs-75">QTY</h6></div>
        <div class="col-1 border-l-t-b pt-2 details-row text-center"><h6 class="fs-75">ACCEPT</h6></div>
        <div class="col-1 border-all pt-2 details-row text-center"><h6 class="fs-75">REJECT</h6></div>
    </div>
</div>
<div class="page ndt-data-container">
    @if(isset($ndt_components))
        @foreach($ndt_components as $component)
            @php
                $processNumbers = [substr($component->processName->name, -1)];
                if ($component->plus_process) {
                    $plusProcessIds = explode(',', $component->plus_process);
                    foreach ($plusProcessIds as $plusProcessId) {
                        $plusProcessName = \App\Models\ProcessName::find($plusProcessId);
                        if ($plusProcessName && strpos($plusProcessName->name, 'NDT-') === 0) {
                            $processNumbers[] = substr($plusProcessName->name, -1);
                        }
                    }
                }
                sort($processNumbers);
            @endphp
            <div class="row fs-8 data-row-ndt" data-row-index="{{ $rowIndex }}">
                <div class="col-1 border-l-b details-row text-center" style="height: 32px">{{ $component->tdr->component->ipl_num }}</div>
                <div class="col-3 border-l-b details-row text-center" style="height: 32px; line-height: 1">
                    {{ $component->tdr->component->part_number }}
                    @if($component->tdr->serial_number)<br>SN {{ $component->tdr->serial_number }}@endif
                </div>
                <div class="col-3 border-l-b details-row text-center" style="height: 32px">{{ $component->tdr->component->name }}</div>
                <div class="col-2 border-l-b details-row text-center" style="height: 32px">{{ implode(' / ', $processNumbers) }}</div>
                <div class="col-1 border-l-b details-row text-center" style="height: 32px">{{ $component->tdr->qty }}</div>
                <div class="col-1 border-l-b details-row text-center" style="height: 32px"></div>
                <div class="col-1 border-l-b-r details-row text-center" style="height: 32px"></div>
            </div>
            @php $rowIndex++; @endphp
        @endforeach
    @elseif(isset($table_data) && count($table_data) > 0)
        @foreach($table_data as $data)
            @php
                $comp = $data['component'] ?? null;
                $processNumbers = isset($data['combined_ndt_number']) ? $data['combined_ndt_number'] : '';
                if (empty($processNumbers) && isset($data['process_name']) && $data['process_name']) {
                    $pn = $data['process_name'];
                    if (strpos($pn->name, 'NDT-') === 0) { $processNumbers = substr($pn->name, 4); }
                    elseif ($pn->name === 'Eddy Current Test') { $processNumbers = '6'; }
                    elseif ($pn->name === 'BNI') { $processNumbers = '5'; }
                    else { $processNumbers = substr($pn->name, -1); }
                }
                $serial = isset($data['extra_process']) ? ($data['extra_process']->serial_num ?? null) : null;
                $qty = isset($data['extra_process']) ? ($data['extra_process']->qty ?? 1) : ($data['qty'] ?? 1);
            @endphp
            @if($comp)
            <div class="row fs-8 data-row-ndt" data-row-index="{{ $rowIndex }}">
                <div class="col-1 border-l-b details-row text-center" style="height: 32px">{{ $comp->ipl_num }}</div>
                <div class="col-3 border-l-b details-row text-center" style="height: 32px; line-height: 1">
                    {{ $comp->part_number }}
                    @if($serial)<br>SN {{ $serial }}@endif
                </div>
                <div class="col-3 border-l-b details-row text-center" style="height: 32px">{{ $comp->name }}</div>
                <div class="col-2 border-l-b details-row text-center" style="height: 32px">{{ $processNumbers }}</div>
                <div class="col-1 border-l-b details-row text-center" style="height: 32px">{{ $qty }}</div>
                <div class="col-1 border-l-b details-row text-center" style="height: 32px"></div>
                <div class="col-1 border-l-b-r details-row text-center" style="height: 32px"></div>
            </div>
            @php $rowIndex++; @endphp
            @endif
        @endforeach
    @endif

    @for ($i = 0; $i < $emptyRows; $i++)
        <div class="row fs-85 data-row-ndt empty-row" data-row-index="{{ $rowIndex }}">
            <div class="col-1 border-l-b details-row text-center" style="height: 32px"></div>
            <div class="col-3 border-l-b details-row text-center" style="height: 32px"></div>
            <div class="col-3 border-l-b details-row text-center" style="height: 32px"></div>
            <div class="col-2 border-l-b details-row text-center" style="height: 32px"></div>
            <div class="col-1 border-l-b details-row text-center" style="height: 32px"></div>
            <div class="col-1 border-l-b details-row text-center" style="height: 32px"></div>
            <div class="col-1 border-l-b-r details-row text-center" style="height: 32px"></div>
        </div>
        @php $rowIndex++; @endphp
    @endfor
</div>
