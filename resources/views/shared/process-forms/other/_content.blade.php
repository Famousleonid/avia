{{--
    Контент форм Other (CAD, Chrome, Bake, Machining, Anodizing и т.д.).
    Переменные: process_tdr_components, process_components, manuals, current_wo, process_name
    formConfig: other_table_rows (default 21)
--}}
@php
    $totalRows = $formConfig['other_table_rows'] ?? 21;
    $dataRows = 0;
    if (isset($table_data)) {
        $dataRows = count($table_data);
    } else {
        foreach ($process_tdr_components ?? [] as $component) {
            $processData = json_decode($component->processes, true);
            $dataRows += is_array($processData) ? count($processData) : 1;
        }
    }
    $emptyRows = max(0, $totalRows - $dataRows);
    $rowIndex = 1;
@endphp

<h6 class="mt-4 ms-3"><strong>
    Perform the {{ ucwords(strtolower($process_name->process_sheet_name ?? $process_name->name ?? 'Process')) }}
    as the specified under Process No. and in accordance with CMM No
</strong>.</h6>

<div class="page table-header">
    <div class="row mt-3">
        <div class="col-1 border-l-t-b pt-2 details-row text-center"><h6 class="fs-75"><strong>ITEM No.</strong></h6></div>
        <div class="col-2 border-l-t-b pt-2 details-row text-center"><h6 class="fs-75"><strong>PART No.</strong></h6></div>
        <div class="col-2 border-l-t-b pt-2 details-row text-center"><h6 class="fs-75"><strong>DESCRIPTION</strong></h6></div>
        <div class="col-4 border-l-t-b pt-2 details-row text-center"><h6 class="fs-75"><strong>PROCESS No.</strong></h6></div>
        <div class="col-1 border-l-t-b pt-2 details-row text-center"><h6 class="fs-75"><strong>QTY</strong></h6></div>
        <div class="col-2 border-all pt-2 details-row text-center"><h6 class="fs-75"><strong>CMM No.</strong></h6></div>
    </div>
</div>
<div class="page data-page">
    @if(isset($table_data) && count($table_data) > 0)
        @foreach($table_data as $data)
            @php $comp = $data['component'] ?? null; $proc = $data['process'] ?? null; $ep = $data['extra_process'] ?? null; @endphp
            @if($comp)
            <div class="row fs-85 data-row" data-row-index="{{ $rowIndex }}">
                <div class="col-1 border-l-b details-cell text-center" style="min-height: 34px; line-height: 1.1">{{
                $comp->ipl_num }}</div>
                <div class="col-2 border-l-b details-cell text-center" style="min-height: 34px; line-height: 1.1">
                    {{ $comp->part_number }}
                    @if(isset($ep) && $ep->serial_num)<br>SN {{ $ep->serial_num }}@endif
                </div>
                <div class="col-2 border-l-b details-cell text-center" style="min-height: 34px">{{ $comp->name }}</div>
                <div class="col-4 border-l-b details-cell text-center process-cell" style="min-height: 34px">
                    @php
                        $procText = null;
                        if ($proc && is_object($proc)) {
                            $procText = $proc->process ?? $proc->name ?? null;
                        }
                        if (!$procText && isset($data['process_name']) && is_object($data['process_name'])) {
                            $procText = $data['process_name']->name ?? null;
                        }
                        if (!$procText && ($process_components ?? [])) {
                            foreach ($process_components as $component_process) {
                                if ($component_process->id == ($proc->id ?? $proc ?? null)) { $procText = $component_process->process; break; }
                            }
                        }
                    @endphp
                    @if($procText)
                        <span @if(strlen($procText) > 40) class="process-text-long" @endif>
                            {{ $procText }}
                            @if(isset($data['description']) && $data['description'])<br><span>{{ $data['description'] }}</span>@endif
                        </span>
                    @endif
                </div>
                <div class="col-1 border-l-b details-cell text-center" style="min-height: 34px">{{ $ep->qty ?? $data['qty'] ?? 1 }}</div>
                <div class="col-2 border-l-b-r details-cell text-center" style="min-height: 34px">
                    @foreach($manuals ?? [] as $manual)
                        @if($manual->id == $current_wo->unit->manual_id)
                            <h6 class="text-center mt-2">{{ substr($manual->number, 0, 8) }}</h6>
                        @endif
                    @endforeach
                </div>
            </div>
            @php $rowIndex++; @endphp
            @endif
        @endforeach
    @else
    @foreach($process_tdr_components ?? [] as $component)
        @php $processData = json_decode($component->processes, true); @endphp
        @foreach($processData ?? [] as $process)
            <div class="row fs-85 data-row" data-row-index="{{ $rowIndex }}">
                <div class="col-1 border-l-b details-cell text-center" style="min-height: 34px">{{ $component->tdr->component->ipl_num }}</div>
                <div class="col-2 border-l-b details-cell text-center" style="min-height: 34px">
                    {{ $component->tdr->component->part_number }}
                    @if($component->tdr->serial_number)<br>SN {{ $component->tdr->serial_number }}@endif
                </div>
                <div class="col-2 border-l-b details-cell text-center" style="min-height: 34px">{{ $component->tdr->component->name }}</div>
                <div class="col-4 border-l-b details-cell text-center process-cell" style="min-height: 34px">
                    @foreach($process_components ?? [] as $component_process)
                        @if($component_process->id == $process)
                            <span @if(strlen($component_process->process) > 25) class="process-text-long" @endif>
                                {{ $component_process->process }}
                                @if($component->description)<br><span>{{ $component->description }}</span>@endif
                            </span>
                        @endif
                    @endforeach
                </div>
                <div class="col-1 border-l-b details-cell text-center" style="min-height: 34px">{{ $component->tdr->qty }}</div>
                <div class="col-2 border-l-b-r details-cell text-center" style="min-height: 34px">
                    @foreach($manuals ?? [] as $manual)
                        @if($manual->id == $current_wo->unit->manual_id)
                            <h6 class="text-center mt-2">{{ substr($manual->number, 0, 8) }}</h6>
                        @endif
                    @endforeach
                </div>
            </div>
            @php $rowIndex++; @endphp
        @endforeach
    @endforeach
    @endif

    @for ($i = 0; $i < $emptyRows; $i++)
        <div class="row empty-row data-row" data-row-index="{{ $rowIndex }}">
            <div class="col-1 border-l-b text-center" style="height: 32px"></div>
            <div class="col-2 border-l-b text-center" style="height: 32px"></div>
            <div class="col-2 border-l-b text-center" style="height: 32px"></div>
            <div class="col-4 border-l-b text-center" style="height: 32px"></div>
            <div class="col-1 border-l-b text-center" style="height: 32px"></div>
            <div class="col-2 border-l-b-r text-center" style="height: 32px"></div>
        </div>
        @php $rowIndex++; @endphp
    @endfor
</div>
