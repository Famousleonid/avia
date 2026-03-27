{{-- Partial: Add Part Inspection form (from component-inspection) --}}
{{-- Used in modal on TDR show, requires: $current_wo, $components, $manuals, $manual_id, $codes, $necessaries, $component_conditions --}}
<form id="createForm" class="createForm" role="form" method="POST"
      action="{{ route('tdrs.store') }}" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="workorder_id" value="{{ $current_wo->id }}">
    <input type="hidden" name="return_to" value="show">

    <div class="row">
        <div class="col">
            <label for="i_component_id" class="form-label pe-2">{{ __('Part') }}</label>
            <div class="form-group">
                <select name="component_id" id="i_component_id" class="form-control" style="width: 100%; max-width: 400px">
                    <option selected value="">---</option>
                    @foreach($components as $component)
                        <option value="{{ $component->id }}"
                                data-has_assy="{{ $component->assy_part_number ? 'true' : 'false' }}"
                                data-title="{{ $component->name }}">
                            {{ $component->ipl_num }} : {{ $component->part_number }} - {{ $component->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="mt-1 d-flex" id="js-parts-actions" @class([
                'mt-1 d-flex',
                'd-none' => !($canManageManualParts ?? false),
            ])>
                <button type="button"
                        id="js-add-part-btn"
                        class="btn btn-link p-0 me-3"
                        data-bs-toggle="modal"
                        data-bs-target="#addComponentModal">
                    {{ __('Add Part') }}
                </button>
                <button type="button" id="js-edit-part-btn" class="btn btn-link p-0" id="editComponentBtn">
                    {{ __('Edit Part') }}
                </button>
            </div>
        </div>
        <div class="col">
            <label for="i_manual_id" class="form-label pe-2">{{ __('Manual') }}</label>
            <div class="form-group">
                <select name="manual_id" id="i_manual_id" class="form-control" style="width: 100%; max-width: 400px">
                    <option value="">---</option>
                    @foreach($manuals as $manual)
                        <option value="{{ $manual->id }}" {{ $manual->id == $manual_id ? 'selected' : '' }}>
                            {{ $manual->number }} : {{ $manual->title }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col">
            <!-- Code -->
            <div class=" form-group m-2">
                <label for="codes_id" class="form-label pe-2">Code Inspection</label>
                <select name="codes_id" id="codes_id" class="form-control" style="width: 300px">
                    <option  selected value="">---</option>
                    @foreach($codes as $code)
                        <option value="{{ $code->id }}" data-title="{{$code->name}}">
                            {{$code->name}}
                        </option>
                    @endforeach
                </select>
            </div>
            <!-- Necessaries -->
            <div class=" form-group m-2" id="necessary" style="display: none">
                <div class="d-flex align-items-center" id="necessary_select_group">
                    <div>
                        <label for="necessaries_id" class="form-label pe-2">Necessary to Do</label>
                        <select name="necessaries_id" id="necessaries_id" class="form-control"
                                style="width: 230px">
                            <option  selected value="">---</option>
                            @foreach($necessaries as $necessary)
                                <option value="{{ $necessary->id }}" data-title="{{$necessary->name}}">
                                    {{$necessary->name}}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <!-- Новый select для выбора компонента заказа -->
                    <div id="order_component_group" style="display: none; margin-left: 20px;">
                        <label for="order_component_id" class="form-label pe-2">{{ __('Order Component') }}</label>
                        <select name="order_component_id" id="order_component_id" class="form-control" style="width: 350px">
                            <option selected value="">---</option>
                            @foreach($components as $component)
                                <option value="{{ $component->id }}">
                                    {{ $component->assy_part_number ?: $component->part_number }} - {{ $component->name }} ({{ $component->ipl_num }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <!-- Description (отдельно для Manufacture и других кодов) -->
            <div class="form-group m-2" id="description_group" style="display: none">
                <label class="" for="description">{{ __('Description ')}}</label>
                <input id='description' type="text"
                       class="form-control " name="description" >
            </div>
            <!-- QTY -->
            <div class="form-group m-2" id="qty" style="display: none">
                <label class="" for="qty">{{__('QTY')}}</label>
                <input id="qty" type="number" class="form-control" name="qty" value="1" style="width: 60px">
            </div>

            <div class="form-group m-2" id="conditions" style="display: none">
                <label for="c_conditions_id" class="form-label pe-2" >Conditions</label>
                <select name="conditions_id" id="c_conditions_id" class="form-control">
                    <option value=""  selected>---</option> <!-- Пустое значение по умолчанию -->
                    @foreach($component_conditions as $component_condition)
                        <option value="{{ $component_condition->id }}" data-title="{{ $component_condition->name }}">
                            {{ $component_condition->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col">
            <div class="m-3"  >
                <div class="form-group ms-4  "  id="sns-group" style="display: none">
                    <div class="m-2">
                        <label class="" for="serial_number">{{ __('Serial Number')}}</label>
                        <input id='serial_number' type="text"
                               class="form-control " name="serial_number" >
                    </div>
                    <div class="m-2" >
                        <div class="" id="assy_serial_number_container" >
                            <label class="" for="assy_serial_number">{{__('Assy Serial Number')}}</label>
                            <input id='assy_serial_number' type="text"
                                   class="form-control " name="assy_serial_number" >
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <div class="text-end mt-3">
        <button type="submit" class="btn btn-outline-primary">{{ __('Save') }}</button>
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" aria-label="Close">{{ __('Cancel') }}</button>
    </div>
</form>
