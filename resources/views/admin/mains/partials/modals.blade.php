{{-- modal Assembly --}}
<div class="offcanvas offcanvas-end text-bg-dark" tabindex="-1" id="assemblyCanvas">
    <div class="offcanvas-header border-bottom border-secondary">
        <h5 class="mb-0">Assembly</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <div class="text-muted small">Future data</div>
    </div>
</div>

<!--  Parts Modal -->
<div class="modal fade" id="partsModal{{$current_workorder->number}}" tabindex="-1"
     role="dialog" aria-labelledby="orderModalLabel{{$current_workorder->number}}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content bg-gradient" style="width: 900px">
            <div class="modal-header" style="width: 900px">
                <div class="d-flex ">
                    <h4 class="modal-title">{{__('Work order ')}}{{$current_workorder->number}}</h4>
                    <h4 class="modal-title ms-4">{{__('Extra Parts  ')}}</h4>
                </div>
                <button type="button" class="btn-close pb-2" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            @if(count($ordersPartsNew))
                <div class="table-wrapper">
                    <table class="display table table-cm table-hover table-striped align-middle table-bordered">
                        <thead class="bg-gradient">
                        <tr>
                            <th class="text-primary  bg-gradient " data-direction="asc">{{__('IPL')}}</th>
                            <th class="text-primary  bg-gradient "
                                data-direction="asc">{{__('Part Description') }}</th>
                            <th class="text-primary  bg-gradient " style="width: 250px;"
                                data-direction="asc">{{__('Part Number')}}</th>
                            <th class="text-primary  bg-gradient " data-direction="asc">{{__('QTY')}}</th>
                            <th class="text-primary  bg-gradient ">{{__('PO NO.')}} </th>
                            <th class="text-primary  bg-gradient ">{{__('Received')}}</th>
                        </tr>
                        </thead>
                        <tbody>

                        @foreach($prl_parts as $part)
                            @php
                                $currentComponent = $part->orderComponent ?? $part->component;
                            @endphp
                            <tr>

                                <td class="" style="width: 100px"> {{$currentComponent->ipl_num ?? ''}} </td>
                                <td class="" style="width: 250px"> {{$currentComponent->name ?? ''}} </td>
                                <td class="" style="width: 120px;"> {{$currentComponent->part_number ?? ''}} </td>
                                <td class="" style="width: 150px;"> {{$part->qty}} </td>
                                <td class="" style="width: 150px;">
                                    <div class="po-no-container">
                                        <select class="form-select form-select-sm po-no-select"
                                                data-tdrs-id="{{ $part->id }}"
                                                data-workorder-number="{{ $current_workorder->number }}"
                                                style="width: 100%;">
                                            <option value="">-- Select --</option>
                                            <option
                                                value="Customer" {{ $part->po_num === 'Customer' ? 'selected' : '' }}>
                                                Customer
                                            </option>
                                            <option
                                                value="Transfer from WO" {{ $part->po_num && \Illuminate\Support\Str::startsWith($part->po_num, 'Transfer from WO') ? 'selected' : '' }}>
                                                Transfer from WO
                                            </option>
                                            <option
                                                value="INPUT" {{ $part->po_num && !\Illuminate\Support\Str::startsWith($part->po_num, ['Customer', 'Transfer from WO']) ? 'selected' : '' }}>
                                                PO No.
                                            </option>
                                        </select>
                                        <input type="text"
                                               class="form-control form-control-sm po-no-input mt-1"
                                               data-tdrs-id="{{ $part->id }}"
                                               data-workorder-number="{{ $current_workorder->number }}"
                                               placeholder="Po No."
                                               value="{{ $part->po_num && !\Illuminate\Support\Str::startsWith($part->po_num, ['Customer', 'Transfer from WO']) ? $part->po_num : '' }}"
                                               style="display: {{ $part->po_num && !\Illuminate\Support\Str::startsWith($part->po_num, ['Customer', 'Transfer from WO']) ? 'block' : 'none' }};">
                                    </div>
                                </td>
                                <td class="" style="width: 150px;">
                                    <input type="date"
                                           class="form-control form-control-sm received-date"
                                           data-tdrs-id="{{ $part->id }}"
                                           data-workorder-number="{{ $current_workorder->number }}"
                                           value="{{ $part->received ? \Carbon\Carbon::parse($part->received)->format('Y-m-d') : '' }}">
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <h5 class="text-center mt-3 mb-3 text-primary">{{__('No Ordered Parts')}}</h5>
            @endif

        </div>
    </div>
</div>

{{-- Photo modal --}}
<div class="modal fade" id="photoModal" tabindex="-1"
     aria-labelledby="photoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content" style="background-color: #343A40">
            <div class="modal-header p-1">
                <h5 class="modal-title" id="photoModalLabel">Photos</h5>
                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="photoModalContent" class="row g-3"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button class="btn btn-primary" id="saveAllPhotos">Download All</button>
            </div>
        </div>
    </div>
</div>

{{-- Confirm delete photo --}}
<div class="modal fade" id="confirmDeletePhotoModal" tabindex="-1"
     aria-labelledby="confirmDeletePhotoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-center">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmDeletePhotoLabel">Confirm Deletion</h5>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this photo?
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal">Cancel
                </button>
                <button id="confirmPhotoDeleteBtn" class="btn btn-danger">Delete</button>
            </div>
        </div>
    </div>
</div>

{{-- Toast --}}
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1055">
    <div id="photoDeletedToast"
         class="toast bg-success text-white" role="alert"
         aria-live="assertive" aria-atomic="true">
        <div class="toast-body">
            Photo deleted successfully.
        </div>
    </div>
</div>

{{-- LOG MODAL --}}
<div class="modal fade" id="logModal" tabindex="-1"
     aria-labelledby="logModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="background-color:#212529;color:#f8f9fa;">
            <div class="modal-header">
                <h5 class="modal-title" id="logModalLabel">Activity log</h5>
                <button type="button" class="btn-close btn-close-white"
                        data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="logModalContent">
                    {{-- сюда подставится список логов --}}
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Update training — выбор даты (по умолчанию сегодня) --}}
<div class="modal fade" id="mainsUpdateTrainingModal" tabindex="-1" aria-labelledby="mainsUpdateTrainingModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mainsUpdateTrainingModalLabel">{{ __('Update training') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <label class="form-label">{{ __('Training date') }}</label>
                <input type="date" id="mainsUpdateTrainingDateInput" class="form-control">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-primary" id="mainsUpdateTrainingSaveBtn">{{ __('Add training') }}</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Add trainings (как в training.create: первая дата + последующие + доп. при 360 дней) --}}
<div class="modal fade " id="mainsAddTrainingsModal" tabindex="-1" aria-labelledby="mainsAddTrainingsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg ">
        <div class="modal-content dir-modal">
            <div class="modal-header modal-header">
                <h5 class="modal-title" id="mainsAddTrainingsModalLabel">{{ __('Add trainings for this unit1') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="mainsAddTrainingsForm" method="POST" action="{{ route('trainings.store') }}">
                @csrf
                <input type="hidden" name="return_url" value="{{ route('mains.show', $current_workorder->id) }}">
                <input type="hidden" name="manuals_id" id="mainsAddTrainingsManualId" value="">
                <div class="modal-body">
                    <div class="form-group mt-2">
                        <label for="mains_date_training">{{ __('First Training Date') }}</label>
                        <input type="date" id="mains_date_training" name="date_training" class="form-control" required>
                    </div>
                    <div class="form-group mt-3">
                        <label>{{ __('Subsequent Training Dates') }}</label>
                        <small class="form-text text-muted d-block mb-1">{{ __('Add all past training dates for this unit (after the first date).') }}</small>
                        <div id="mains_training_dates_list"></div>
                        <button type="button" class="btn btn-outline-secondary btn-sm mt-1" id="mains_add_training_date_btn"><i class="bi bi-plus"></i> {{ __('Add Date') }}</button>
                        <div id="mains_training_dates_error" class="text-danger mt-1" style="display: none;"></div>
                    </div>
                    <div class="form-group mt-3" id="mains_additional_training_date_group" style="display: none;">
                        <label for="mains_additional_training_date">{{ __('Additional Training Date') }}</label>
                        <input type="date" id="mains_additional_training_date" name="additional_training_date" class="form-control">
                        <small class="form-text text-muted">{{ __('Last training was more than 360 days ago. You can add a training on the date of adding the unit or choose another date.') }}</small>
                        <div id="mains_additional_training_date_error" class="text-danger mt-1" style="display: none;"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Add trainings') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal: 360 дней — добавить дополнительную тренировку? --}}
<div class="modal fade" id="mainsAdditionalTrainingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Additional Training') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>{{ __('Last training was more than 360 days ago. Add an additional training on the date of adding the unit?') }}</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="mainsAdditionalModalNo">{{ __('No') }}</button>
                <button type="button" class="btn btn-primary" id="mainsAdditionalModalYes">{{ __('Yes') }}</button>
            </div>
        </div>
    </div>
</div>

