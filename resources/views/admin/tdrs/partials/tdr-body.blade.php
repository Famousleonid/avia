{{-- TDR tab content: Inspection Unit + Inspection Component tables --}}

<div class="d-flex tdr-show-tables-layout">
    <div class="tdr-show-left-pane">
        <div class="table-wrapper me-1 p-1">
            <table id="tdr_inspect_Table" class="table table-sm table-hover align-middle table-bordered dir-table shadow-lg">
                <colgroup>
                    <col >
                    <col style="width: 55px;">
                </colgroup>
                <thead>
                <tr>
                    <th class="text-primary text-center" colspan="2">
                        <a href="#" data-bs-toggle="modal" data-bs-target="#unitInspectionModal">{{__('Teardown Inspection')}}</a>
                    </th>
                </tr>
                </thead>
                <tbody>
                @if($hasMissingParts && $missingCondition)
                    <tr>
                        <td class="text-center fs-8">
                            <span>
                                {{ $missingCondition->name }}
                                <span class="badge bg-primary rounded-pill ms-1 align-middle" style="font-size: 0.78em;">{{ $missingPartsCount ?? 0 }}</span>
                            </span>
                        </td>
                        <td class="text-center img-icon p-0">
                            <img src="{{ asset('img/missing.gif') }}" alt="missing" class="d-block"
                                 style="width: 55px;"
                                 data-bs-toggle="modal" data-bs-target="#missingModal{{$current_wo->number}}">
                        </td>
                    </tr>
                @endif
                @if($hasOrderedParts ?? false)
                    <tr>
                        <td class="text-center">
                            <span class="position-relative d-inline-block">
                                ORDERED PARTS
                                <sup class="badge bg-primary rounded-pill align-super ms-1" style="font-size: 0.78em;">{{ $orderedPartsCount ?? 0 }}</sup>
                            </span>
                        </td>
                        <td class="p-0 text-center img-icon" style="height: 55px; width: 55px; overflow: hidden;">
                            <img src="{{ asset('img/pay.gif')}}" alt="order" style="height: 55px; width: 55px; object-fit: cover; display: block;" data-bs-toggle="modal" data-bs-target="#orderModal{{$current_wo->number}}">
                        </td>
                    </tr>
                @endif
                @foreach($inspectsUnit as $inspection)
                    <tr>
                        <td class="text-center fs-8" >
                            @php
                                $conditionName = $inspection->condition->name ?? null;
                                if (!$conditionName) {
                                    foreach($conditions as $condition) {
                                        if ($condition->id == $inspection->condition_id) {
                                            $conditionName = $condition->name;
                                            break;
                                        }
                                    }
                                }

                                $isNoteCondition = $conditionName && preg_match('/^note\s+\d+$/i', $conditionName);
                            @endphp
                            @if(!$isNoteCondition)
                                @if($inspection->condition)
                                    {{ empty($inspection->condition->name) ? __('(No name)') : $inspection->condition->name }}
                                @else
                                    @foreach($conditions as $condition)
                                        @if($condition->id == $inspection->condition_id)
                                            {{ empty($condition->name) ? __('(No name)') : $condition->name }}
                                            @break
                                        @endif
                                    @endforeach
                                @endif
                            @endif
                            @if($inspection->notes)
                                {{ $isNoteCondition ? $inspection->notes : '(' . $inspection->notes . ')' }}
                            @endif
                        </td>
                        <td class="p-0 text-center img-icon">
                            <form action="{{ route('workorder-unit-inspections.destroy', $inspection) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="return_to" value="show">
                                <button type="button"
                                        class="btn btn-link btn-sm tdr-unit-trash-btn"
                                        aria-label="{{ __('Delete') }}"
                                        data-bs-toggle="modal"
                                        data-bs-target="#useConfirmDelete"
                                        data-title="{{ __('Delete Confirmation') }}">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="tdr-show-right-pane">
        <form id="tdrInlineCreateForm" class="d-none" method="POST" action="{{ route('tdrs.store') }}" data-no-spinner>
            @csrf
            <input type="hidden" name="inline_create" value="1">
            <input type="hidden" name="workorder_id" value="{{ $current_wo->id }}">
            <input type="hidden" name="return_to" value="show">
            <input type="hidden" name="use_tdr" id="tdr_inline_use_tdr" value="0">
            <input type="hidden" name="use_process_forms" id="tdr_inline_use_process_forms" value="0">
            <input type="hidden" name="conditions_id" id="tdr_inline_conditions_id" value="">
            <input type="hidden" name="order_component_id" id="tdr_inline_order_component_id_value" value="">
            <input type="hidden" name="order_component_assembly_id" id="tdr_inline_order_component_assembly_id" value="">
        </form>
        <div class="tdr-show-right-toolbar d-flex align-items-end justify-content-between">
            <div id="tdrInlineManualPicker" class="tdr-inline-manual-picker d-none">
                <select name="manual_id" id="tdr_inline_manual_id" class="form-control form-control-sm" form="tdrInlineCreateForm">
                    <option value="">---</option>
                    @foreach($manuals as $manual)
                        <option value="{{ $manual->id }}" {{ $manual->id == $manual_id ? 'selected' : '' }}>
                            {{ $manual->number }} : {{ $manual->title }}
                        </option>
                    @endforeach
                </select>
            </div>
            @php
                $showFcDocButton = auth()->user()?->roleIs('Admin') ?? false;
            @endphp
            @if($showFcDocButton)
                @php
                    // F&C Doc button color = aggregate data state of the document marks
                    $fcDocStatus = app(\App\Services\Measurements\ProcessDocumentRenderer::class)
                        ->fcDocumentStatus(optional($current_wo->unit)->manuals, $current_wo);
                    $fcDocBtnClass = match ($fcDocStatus) {
                        'pass'   => 'btn-success',
                        'fail'   => 'btn-danger',
                        'nodata' => 'btn-warning',
                        default  => 'btn-outline-info',
                    };
                    $fcDocBtnStyle = $fcDocStatus === 'repair'
                        ? 'background:#6f42c1;border-color:#6f42c1;color:#fff' : '';
                    $fcDocTitle = match ($fcDocStatus) {
                        'pass'   => __('F&C Document — all values in tolerance'),
                        'repair' => __('F&C Document — defect found, repair needed'),
                        'fail'   => __('F&C Document — some values out of tolerance'),
                        'nodata' => __('F&C Document — some values not measured'),
                        default  => __('F&C Document — filled manual pages with this WO measurements'),
                    };
                @endphp
                <a href="{{ route('workorders.fc-document', $current_wo->id) }}" target="_blank"
                   class="btn {{ $fcDocBtnClass }} btn-sm {{ count($processGroups ?? []) > 0 ? 'ms-auto me-2' : 'ms-auto' }}"
                   style="{{ $fcDocBtnStyle }}"
                   title="{{ $fcDocTitle }}"
                   data-fc-doc-button>
                    <i class="bi bi-file-earmark-richtext"></i> {{ __('F&C Doc') }}
                </a>
            @endif
            @if(count($processGroups ?? []) > 0)
                <button type="button"
                        class="btn btn-outline-primary btn-sm {{ $showFcDocButton ? '' : 'ms-auto' }}"
                        data-bs-toggle="modal"
                        data-bs-target="#tdrGroupProcessModal">
                    <i class="fas fa-print"></i> {{ __('Group Process') }}
                </button>
            @endif
        </div>
        @php
            // Inspect button: map TDR component → inspection component (Dimensions part),
            // но оставляем только IC с ИЗМЕРИМЫМ параметром — у которого есть orig/wear
            // размеры или repair-правило. У деталей без точек (или с точками без размеров/
            // правил) кнопки Inspect нет — мерять нечего (совпадает с фильтром дерева).
            $tdrInspectCompIds = collect($tdrs)
                ->filter(fn($t) => $t->use_tdr && $t->use_process_forms)
                ->pluck('component_id')->filter()->unique();
            $tdrInspectIcByComp = $tdrInspectCompIds->isNotEmpty()
                ? \App\Models\ManualInspectionComponentVariant::whereIn('component_id', $tdrInspectCompIds)
                    ->pluck('inspection_component_id', 'component_id')
                : collect();
            if ($tdrInspectIcByComp->isNotEmpty()) {
                $measurableIcIds = \App\Models\ManualParameter::query()
                    ->whereIn('inspection_component_id', $tdrInspectIcByComp->values()->unique()->all())
                    ->where(function ($q) {
                        $q->whereNotNull('orig_dim_min')
                          ->orWhereNotNull('orig_dim_max')
                          ->orWhereNotNull('wear_dim_min')
                          ->orWhereNotNull('wear_dim_max')
                          ->orWhereHas('repairRules');
                    })
                    ->pluck('inspection_component_id')
                    ->unique()
                    ->flip();
                $tdrInspectIcByComp = $tdrInspectIcByComp->filter(fn($icId) => $measurableIcIds->has($icId));
            }
        @endphp
        <div class="table-wrapper p-1 tdr-show-right-table-wrapper">
            <table id="tdr_process_Table"
                   class="table table-sm table-hover align-middle dir-table small shadow-lg"
                   data-reorder-url="{{ route('tdrs.reorder', ['workorder' => $current_wo->id]) }}">
                <colgroup>
                    <col class="tdr-drag-col">
                    <col style="width: 12ch;">
                    <col style="width: 19%;">
                    <col style="width: 42px;">
                    <col style="width: 12%;">
                    <col style="width: 10ch;">
                    <col style="width: 14%;">
                    <col style="width: 28%;">
                    <col style="width: 5.5ch;">
                    <col class="tdr-action-col">
                </colgroup>
                <thead class="bg-gradient">
                <tr>
                    <th class="tdr-drag-cell" aria-label="{{ __('Move row') }}">
                        <i class="bi bi-grip-vertical" aria-hidden="true"></i>
                    </th>
                    <th class="text-center text-primary sortable">{{__('IPL')}}</th>
                    <th class="text-center text-primary sortable">{{__('P/N')}}</th>
                    <th class="text-center text-primary">{{__('Img')}}</th>
                    <th class="text-center text-primary">{{__('Code')}}</th>
                    <th class="text-center text-primary">{{__('Necessary')}}</th>
                    <th class="text-center text-primary sortable">{{__('S/N')}}</th>
                    <th class="text-center text-primary sortable">{{__('Part name')}}</th>
                    <th class="text-center text-primary">{{__('EC')}}</th>
                    <th class="text-primary text-start">
                        <span>{{__('Action')}}</span>
                    </th>
                </tr>
                </thead>
                <tbody>
                @foreach($tdrs as $tdr)
                    {{-- Keep archived parts available to historical forms, but omit them from the active TDR table. --}}
                    @if($tdr->use_tdr == true && $tdr->use_process_forms == true && (!$tdr->component_id || ($tdr->component && !$tdr->component->trashed())))
                        @php
                            $tdrDeleteLockedByProcessRo = auth()->check()
                                && auth()->user()->roleIs(['Technician', 'Team Leader'])
                                && ($roLockedTdrIds ?? collect())->contains((int) $tdr->id);
                        @endphp
                        <tr data-tdr-id="{{ $tdr->id }}" data-tdr-ec="{{ $tdrEcIds->contains((int) $tdr->id) ? '1' : '0' }}">
                            <td class="tdr-drag-cell">
                                <button type="button"
                                        class="tdr-row-drag-handle"
                                        title="{{ __('Move row') }}"
                                        aria-label="{{ __('Move row') }}">
                                    <i class="bi bi-grip-vertical" aria-hidden="true"></i>
                                </button>
                            </td>
                            <td class="text-center">{{ $tdr->component->ipl_num ?? '' }}</td>
                            <td class="text-center">{{ $tdr->component->part_number ?? '' }}</td>
                            <td class="tdr-image-cell">
                                @include('admin.tdrs.partials.component-image', [
                                    'component' => $tdr->component,
                                    'gallery' => 'tdr-component-images-'.$current_wo->id,
                                ])
                            </td>
                            <td class="text-center">
                                @foreach($codes as $c)
                                    @if($c->id == $tdr->codes_id) {{ $c->name }} @endif
                                @endforeach
                            </td>
                            <td class="text-center">
                                @foreach($necessaries as $nec)
                                    @if($nec->id == $tdr->necessaries_id)
                                        {{ $nec->name }}
                                        @if($nec->name === 'Repair')<div class="small tdr-repair-qty">QTY: {{ max(1, (int) $tdr->qty) }}</div>@endif
                                    @endif
                                @endforeach
                            </td>
                            <td class="text-center">{{ $tdr->serial_number }}</td>
                            <td class="text-center tdr-part-name-cell">
                                {{ $tdr->component->name ?? '' }}
                                @if(filled($tdr->description))
                                    <span class="tdr-description-marker" title="{{ $tdr->description }}"></span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($tdrEcIds->contains((int) $tdr->id))
                                    <img src="{{ asset('img/ok.png') }}" alt="{{ __('EC') }}" width="20">
                                @endif
                            </td>
                            <td class="text-start tdr-action-cell">
                                <div class="d-flex">
                                    <button type="button" class="btn btn-outline-primary btn-sm me-2 open-part-processes-tab"
                                            title="{{ __('Part Processes') }}"
                                            data-tdr-action="processes"
                                            data-tdr-id="{{ $tdr->id }}">
                                        <img src="{{ asset('img/icons/tdr-process-train.png') }}"
                                             alt=""
                                             class="tdr-process-icon">
                                    </button>
                                        <button type="button" class="btn btn-outline-primary btn-sm me-2" title="{{ __('Edit') }}"
                                                data-bs-toggle="modal" data-bs-target="#editTdrModal"
                                                data-tdr-action="edit"
                                                data-tdr-id="{{ $tdr->id }}">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                    <form action="{{ route('tdrs.destroy', ['tdr' => $tdr->id]) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="return_to" value="show">
                                        <button type="button"
                                                class="btn btn-outline-danger btn-sm"
                                                data-tdr-action="delete"
                                                @if($tdrDeleteLockedByProcessRo)
                                                    disabled
                                                    aria-disabled="true"
                                                    data-tdr-delete-locked="process-ro"
                                                    title="{{ __('Assigned process RO: only Admin or Manager can delete this TDR.') }}"
                                                @elseif($tdr->isManufacturePairMember())
                                                    data-tdr-manufacture-pair-delete
                                                @else
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#useConfirmDelete"
                                                    data-title="{{ __('Delete Confirmation') }}"
                                                @endif>
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                    @php $tdrInspectIcId = $tdrInspectIcByComp[$tdr->component_id] ?? null; @endphp
                                    @if($tdrInspectIcId)
                                        <button type="button" class="btn btn-outline-info btn-sm me-2 tdr-inspect-part"
                                                title="{{ __('Inspect — measure this part') }}"
                                                data-tdr-action="inspect"
                                                data-ic-id="{{ $tdrInspectIcId }}">
                                            <i class="bi bi-rulers"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endif
                @endforeach
                <tr id="tdrInlineCreateRow" class="tdr-inline-create-row d-none">
                    <td class="tdr-drag-cell"></td>
                    <td class="text-center text-muted" id="tdr_inline_ipl_display"></td>
                    <td>
                        <div id="tdrInlineComponentPicker" class="tdr-inline-field">
                            <select name="component_id" id="tdr_inline_component_id" class="form-control form-control-sm" form="tdrInlineCreateForm">
                                <option selected value="">---</option>
                                @foreach($components as $component)
                                    @php
                                        $componentAssembliesJson = $component->assemblies
                                            ->map(function ($assembly) {
                                                return [
                                                    'id' => $assembly->id,
                                                    'assy_part_number' => $assembly->assy_part_number,
                                                    'assy_ipl_num' => $assembly->assy_ipl_num,
                                                    'units_assy' => $assembly->units_assy,
                                                ];
                                            })
                                            ->values()
                                            ->toJson();
                                    @endphp
                                    <option value="{{ $component->id }}"
                                            data-has_assy="{{ ($component->assy_part_number || $component->assemblies->isNotEmpty()) ? 'true' : 'false' }}"
                                            data-title="{{ $component->name }}"
                                            data-ipl="{{ $component->ipl_num }}"
                                            data-part-number="{{ $component->part_number }}"
                                            data-assy-part-number="{{ $component->assy_part_number }}"
                                            data-assy-ipl="{{ $component->assy_ipl_num }}"
                                            data-units-assy="{{ $component->units_assy }}"
                                            data-np="{{ $component->np ? '1' : '0' }}"
                                            data-kit="{{ $component->kit ? '1' : '0' }}"
                                            data-assemblies='{{ $componentAssembliesJson }}'>
                                        {{ $component->ipl_num }} : {{ $component->part_number }} - {{ $component->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div id="tdr-inline-parts-actions" @class([
                                'mt-1 d-flex',
                                'd-none' => !($canManageManualParts ?? false),
                            ])>
                                <button type="button"
                                        id="tdr-inline-add-part-btn"
                                        class="btn btn-link btn-sm p-0 me-3"
                                        data-bs-toggle="modal"
                                        data-bs-target="#addComponentModal">
                                    {{ __('Add Part') }}
                                </button>
                                <button type="button" id="tdr-inline-edit-part-btn" class="btn btn-link btn-sm p-0">
                                    {{ __('Edit Part') }}
                                </button>
                            </div>
                        </div>
                    </td>
                    <td class="tdr-image-cell"></td>
                    <td class="tdr-inline-cell tdr-inline-cell-disabled" data-tdr-inline-cell data-inline-step="code">
                        <div class="tdr-inline-placeholder text-info">{{ __('Click code') }}</div>
                        <div class="tdr-inline-field d-none">
                            <select name="codes_id" id="tdr_inline_codes_id" class="form-control form-control-sm mb-1" form="tdrInlineCreateForm">
                                <option selected value="">---</option>
                                @foreach($codes as $code)
                                    <option value="{{ $code->id }}" data-title="{{ $code->name }}">
                                        {{ $code->name }}
                                    </option>
                                @endforeach
                            </select>
                            <input type="number" name="qty" id="tdr_inline_qty" class="form-control form-control-sm d-none" form="tdrInlineCreateForm" value="1" min="1" style="max-width: 90px" placeholder="{{ __('QTY') }}">
                        </div>
                    </td>
                    <td class="tdr-inline-cell tdr-inline-cell-disabled" data-tdr-inline-cell data-inline-step="necessary">
                        <div class="tdr-inline-placeholder text-info">{{ __('Click necessary') }}</div>
                        <div class="tdr-inline-field d-none">
                            <select name="necessaries_id" id="tdr_inline_necessaries_id" class="form-control form-control-sm mb-1" form="tdrInlineCreateForm">
                                <option selected value="">---</option>
                                @foreach($necessaries as $necessaryItem)
                                    <option value="{{ $necessaryItem->id }}" data-title="{{ $necessaryItem->name }}">
                                        {{ $necessaryItem->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </td>
                    <td class="tdr-inline-cell tdr-inline-cell-disabled" data-tdr-inline-cell data-inline-step="serial">
                        <div class="tdr-inline-placeholder text-info"></div>
                        <div class="tdr-inline-field d-none">
                            <div id="tdr_inline_order_component_group" class="d-none">
                                <select id="tdr_inline_order_component_id" class="form-control form-control-sm">
                                    <option selected value="">---</option>
                                </select>
                            </div>
                            <input type="text" name="serial_number" id="tdr_inline_serial_number" class="form-control form-control-sm mb-1" form="tdrInlineCreateForm" placeholder="{{ __('Serial Number') }}">
                            <input type="text" name="assy_serial_number" id="tdr_inline_assy_serial_number" class="form-control form-control-sm d-none" form="tdrInlineCreateForm" placeholder="{{ __('Assy Serial Number') }}">
                        </div>
                    </td>
                    <td class="tdr-inline-cell tdr-inline-cell-disabled" data-tdr-inline-cell data-inline-step="description">
                        <div class="tdr-inline-placeholder text-info"></div>
                        <div class="tdr-inline-field d-none">
                            <div id="tdr_inline_order_qty_mount" class="d-none">
                                <span id="tdr_inline_order_selected_text" class="tdr-inline-order-selected"></span>
                                <span class="tdr-inline-qty-control">
                                    <label for="tdr_inline_qty" class="tdr-inline-qty-label">{{ __('QTY') }}</label>
                                </span>
                            </div>
                            <input type="text" name="description" id="tdr_inline_description" class="form-control form-control-sm d-none mb-1" form="tdrInlineCreateForm" placeholder="{{ __('Description') }}">
                        </div>
                    </td>
                    <td class="text-center" id="tdr_inline_repair_qty_mount">
                        <div class="tdr-inline-field" id="tdr_inline_repair_qty_field">
                            <label for="tdr_inline_qty" id="tdr_inline_repair_qty_label" class="small d-none">QTY</label>
                        </div>
                    </td>
                    <td class="text-start tdr-action-cell">
                        <div class="d-flex">
                            <button type="submit" form="tdrInlineCreateForm" class="btn btn-outline-info tdr-inline-save-btn">{{ __('Save') }}</button>
                        </div>
                    </td>
                </tr>
                <tr id="tdrInlineAddRow">
                    <td class="tdr-drag-cell"></td>
                    <td class="text-start">
                        <button type="button" class="btn btn-outline-info btn-sm" id="tdrInlineAddBtn">{{ __('Add') }}</button>
                    </td>
                    <td colspan="8"></td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

@unless($tdrFragment ?? false)
@include('components.delete')

<script>
// Inspect (📏) on a TDR row → temporary Inspect tab (measurements pane, single part)
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.tdr-inspect-part');
    if (!btn) return;
    const icId   = parseInt(btn.dataset.icId);
    const li     = document.getElementById('tab-ms-inspect-li');
    const tabBtn = document.getElementById('tab-ms-inspect');
    if (!icId || !li || !tabBtn) return;
    if (window.msInspectPart) window.msInspectPart(icId);
    li.classList.remove('d-none');
    bootstrap.Tab.getOrCreateInstance(tabBtn).show();
});
</script>

@endunless
