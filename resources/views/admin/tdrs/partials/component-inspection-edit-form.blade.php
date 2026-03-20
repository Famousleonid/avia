{{-- Partial: Component Inspection Edit form (from edit.blade.php) --}}
{{-- Used in modal, requires: $current_tdr, $codes, $necessaries, $manuals --}}
<div class="mb-3 p-2 rounded" style="background: rgba(0,0,0,.15);">
    <small class="text-muted">{{ __('Part') }}: {{ $current_tdr->component->name ?? '' }}</small><br>
    <small class="text-muted">{{ __('PN') }}: {{ $current_tdr->component->part_number ?? '' }} | {{ __('IPL') }}: {{ $current_tdr->component->ipl_num ?? '' }}</small>
</div>
<form id="editTdrForm" class="editForm" role="form" method="POST"
      action="{{ route('tdrs.update', $current_tdr->id) }}"
      enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <input type="hidden" name="workorder_id" value="{{ $current_tdr->workorder->id }}">
    <input type="hidden" name="use_process_forms" value="{{ $current_tdr->use_process_forms }}">
    <input type="hidden" name="return_to" value="show2">

    <div class="mb-3">
        <div class="row">
            <div class="col-md-6">
                <label for="edit_serial_number" class="form-label">{{ __('Serial Number') }}</label>
                <input id="edit_serial_number" type="text" value="{{ $current_tdr->serial_number }}"
                       class="form-control" name="serial_number">
            </div>
            @if($current_tdr->assy_serial_number != null)
                <div class="col-md-6">
                    <label for="edit_assy_serial_number" class="form-label">{{ __('Assy Serial Number') }}</label>
                    <input id="edit_assy_serial_number" type="text" value="{{ $current_tdr->assy_serial_number }}"
                           class="form-control" name="assy_serial_number">
                </div>
            @endif
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <label for="edit_codes_id" class="form-label">{{ __('Code Inspection') }}</label>
            <select name="codes_id" id="edit_codes_id" class="form-control" style="width: 100%">
                @foreach($codes as $code)
                    <option value="{{ $code->id }}" {{ $code->id == $current_tdr->codes_id ? 'selected' : '' }}>
                        {{ $code->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label for="edit_necessaries_id" class="form-label">{{ __('Necessary to Do') }}</label>
            <select name="necessaries_id" id="edit_necessaries_id" class="form-control" style="width: 100%">
                @foreach($necessaries as $necessary)
                    <option value="{{ $necessary->id }}" {{ $necessary->id == $current_tdr->necessaries_id ? 'selected' : '' }}>
                        {{ $necessary->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="mb-3">
        <label for="edit_description" class="form-label">{{ __('Description') }}</label>
        <input id="edit_description" type="text" value="{{ $current_tdr->description }}"
               class="form-control" name="description">
    </div>

    <div class="text-end">
        <button type="submit" class="btn btn-outline-primary">{{ __('Update') }}</button>
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
    </div>
</form>
