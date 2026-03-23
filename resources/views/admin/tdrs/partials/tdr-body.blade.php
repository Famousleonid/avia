{{-- TDR tab content: Inspection Unit + Inspection Component tables --}}
<div class="d-flex justify-content-center" style="height: 75vh">
    <div class="me-3" style="width: 450px; max-height: 70vh; overflow-y: auto;">
        <div class="table-wrapper me-3 p-2">
            <table id="tdr_inspect_Table" class="table table-sm table-hover align-middle table-bordered dir-table shadow-lg">
                <thead>
                <tr>
                    <th class="text-primary text-center" colspan="2" style="height: 42px">
                        <a href="#" data-bs-toggle="modal" data-bs-target="#unitInspectionModal">{{__('Teardown Inspection')}}</a>
                    </th>
                </tr>
                </thead>
                <tbody>
                @if($hasMissingParts && $missingCondition)
                    <tr>
                        <td class="text-center fs-8">{{ $missingCondition->name }}</td>
                        <td class="text-center img-icon p-0">
                            <img src="{{ asset('img/missing.gif') }}" alt="missing" class="d-block"
                                 style="width: 55px;"
                                 data-bs-toggle="modal" data-bs-target="#missingModal{{$current_wo->number}}">
                        </td>
                    </tr>
                @endif
                @foreach($inspectsUnit->whereNull('component_id') as $tdr)
                    <tr>
                        <td class="text-center fs-8">
                            @php
                                $conditionName = $tdr->conditions->name ?? null;
                                if (!$conditionName) {
                                    foreach($conditions as $condition) {
                                        if ($condition->id == $tdr->conditions_id) {
                                            $conditionName = $condition->name;
                                            break;
                                        }
                                    }
                                }
                                $isNoteCondition = $conditionName && preg_match('/^note\s+\d+$/i', $conditionName);
                            @endphp
                            @if(!$isNoteCondition)
                                @if($tdr->conditions)
                                    {{ empty($tdr->conditions->name) ? __('(No name)') : $tdr->conditions->name }}
                                @else
                                    @foreach($conditions as $condition)
                                        @if($condition->id == $tdr->conditions_id)
                                            {{ empty($condition->name) ? __('(No name)') : $condition->name }}
                                            @break
                                        @endif
                                    @endforeach
                                @endif
                            @endif
                            @if($tdr->description)
                                {{ $isNoteCondition ? $tdr->description : '(' . $tdr->description . ')' }}
                            @endif
                        </td>
                        <td class="p-0 text-center img-icon">
                            <form action="{{ route('tdrs.destroy', $tdr->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this item?');">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="return_to" value="show2">
                                <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                @if($hasOrderedParts ?? false)
                    <tr>
                        <td class="text-center">
                            <span class="position-relative d-inline-block mt-2">
                                Ordered Parts
                                <sup class="badge bg-primary rounded-pill position-absolute" style="top: 0.1em; right: -3.0em; font-size: 0.65em;">{{ $orderedPartsCount ?? 0 }}</sup>
                            </span>
                        </td>
                        <td class="p-0 text-center img-icon">
                            <img src="{{ asset('img/scrap.gif')}}" alt="order" style="width: 55px;" data-bs-toggle="modal" data-bs-target="#orderModal{{$current_wo->number}}">
                        </td>
                    </tr>
                @endif
                </tbody>
            </table>
        </div>
    </div>
    <div class="me-3">
        <div class="table-wrapper me-3 p-2" style="max-height: 60vh; overflow-y: auto;">
            <table id="tdr_process_Table" class="table table-sm table-hover align-middle dir-table small shadow-lg">
                <thead class="bg-gradient">
                <tr>
                    <th class="text-center text-primary sortable" style="width: 9%">{{__('IPL')}}</th>
                    <th class="text-center text-primary sortable" style="width: 200px">{{__('Description')}}</th>
                    <th class="text-center text-primary sortable" style="width: 15%">{{__('P/N')}}</th>
                    <th class="text-center text-primary sortable" style="width: 120px">{{__('S/N')}}</th>
                    <th class="text-center text-primary" style="width: 12%">{{__('Necessary')}}</th>
                    <th class="text-center text-primary" style="width: 12%">{{__('Code')}}</th>
                    <th class="text-center text-primary" style="width: 5%">{{__('EC')}}</th>
                    <th class="text-primary text-center d-flex justify-content-center" style="width: 150px;">
                        <div class="text-center">{{__('Action')}}</div>
                        <button type="button" class="btn btn-outline-info btn-sm ms-3" style="height: 32px"
                                data-bs-toggle="modal" data-bs-target="#componentInspectionModal">{{ __('Add') }}</button>
                    </th>
                </tr>
                </thead>
                <tbody>
                @foreach($tdrs as $tdr)
                    @if($tdr->use_tdr == true && $tdr->use_process_forms == true)
                        <tr>
                            <td class="text-center">{{ $tdr->component->ipl_num ?? '' }}</td>
                            <td class="text-center">{{ $tdr->component->name ?? '' }}</td>
                            <td class="text-center">{{ $tdr->component->part_number ?? '' }}</td>
                            <td class="text-center">{{ $tdr->serial_number }}</td>
                            <td class="text-center">
                                @foreach($necessaries as $nec)
                                    @if($nec->id == $tdr->necessaries_id) {{ $nec->name }} @endif
                                @endforeach
                            </td>
                            <td class="text-center">
                                @foreach($codes as $c)
                                    @if($c->id == $tdr->codes_id) {{ $c->name }} @endif
                                @endforeach
                            </td>
                            <td class="text-center" style="width: 60px">
                                @php $found = false; @endphp
                                @foreach($tdr_proc as $tdr_ec)
                                    @if($tdr_ec->tdrs_id == $tdr->id)
                                        @php $found = true; @endphp
                                        Yes
                                        @break
                                    @endif
                                @endforeach
                                @if(!$found) No @endif
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center">
                                    <button type="button" class="btn btn-outline-primary btn-sm me-2 open-part-processes-tab" title="{{ __('Component Processes') }}"
                                            data-tdr-id="{{ $tdr->id }}">
                                        <i class="bi bi-bar-chart-steps"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-primary btn-sm me-2" title="{{ __('Component Inspection Edit') }}"
                                            data-bs-toggle="modal" data-bs-target="#editTdrModal"
                                            data-tdr-id="{{ $tdr->id }}">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <form action="{{ route('tdrs.destroy', ['tdr' => $tdr->id]) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this item?');">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="return_to" value="show2">
                                        <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endif
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
