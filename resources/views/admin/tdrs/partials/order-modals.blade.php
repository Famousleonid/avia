<div class="modal fade " id="missingModal{{$current_wo->number}}" tabindex="-1" role="dialog" aria-labelledby="missingModalLabel{{$current_wo->number}}" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document" style="max-width: min(1100px, calc(100vw - 2rem));">
        <div class="modal-content bg-gradient" style="width: 90%; margin: 0 auto;">
            <div class="modal-header">
                <h6 class="modal-title text-info">{{__('Work order ')}}{{$current_wo->number}} - {{__('Parts Missing ')}}</h6>
                <button type="button" class="btn-close pb-2" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="table-wrapper">
                <table class="display table table-cm table-hover table-striped align-middle table-bordered dir-table" style="font-size: 0.9rem">
                    <thead class="bg-gradient">
                    <tr>
                        <th class="text-primary ">{{__('IPL')}}</th>
                        <th class="text-primary ">{{__('Part Description')}}</th>
                        <th class="text-primary ">{{__('Part Number')}}</th>
                        <th class="text-primary  text-center" style="width: 5%">{{__('QTY')}}</th>
                        <th class="text-primary  text-center" style="width: 5%">{{__('Delete')}}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($missingParts as $part)
                        @php
                            $currentComponent = $part->orderComponent ?? $part->component;
                            $currentAssembly = $part->orderComponentAssembly;
                            $displayIpl = $currentAssembly->assy_ipl_num ?? $currentComponent->ipl_num ?? '';
                            $displayName = trim(($currentComponent->name ?? '') . ($currentAssembly ? ' (assy)' : ''));
                            $displayPartNumber = $currentAssembly->assy_part_number ?? $currentComponent->part_number ?? '';
                        @endphp
                        <tr>
                            <td class="p-2">{{ $displayIpl }}</td>
                            <td class="p-2">{{ $displayName }}</td>
                            <td class="p-2">{{ $displayPartNumber }}</td>
                            <td class="p-2 text-center">{{ $part->qty }}</td>
                            <td class="p-2 text-center">
                                <form action="{{ route('tdrs.destroy', $part->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="return_to" value="show">
                                    <button type="button"
                                            class="btn btn-link btn-sm p-0 border-0 bg-transparent text-danger"
                                            title="{{ __('Delete') }}"
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
    </div>
</div>
<div class="modal fade order-modal" id="orderModal{{$current_wo->number}}" tabindex="-1" role="dialog"
     aria-labelledby="orderModalLabel{{$current_wo->number}}" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document" style="max-width: min(1100px, calc(100vw - 2rem));">
        <div class="modal-content bg-gradient" style="width: 100%">
            <div class="modal-header">
                <h6 class="modal-title text-info">{{__('Work order W')}}{{$current_wo->number}} - {{__('Ordered Parts')}}</h6>
                <button type="button" class="btn-close pb-2" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            @if(count($ordersPartsNew))
                <div class="table-wrapper order-modal-table-wrapper">
                    <table class="table  table-hover table-striped align-middle table-bordered
                    dir-table">
                        <thead class="bg-gradient">
                        <tr>
                            <th class="text-primary bg-gradient" style="width: 8%">{{__('IPL')}}</th>
                            <th class="text-primary bg-gradient" style="width: 20%">{{__('Part Description')}}</th>
                            <th class="text-primary bg-gradient" style="width: 15%">{{__('Part Number')}}</th>
                            <th class="text-primary bg-gradient text-center" style="width: 42px">{{__('Img')}}</th>
                            <th class="text-primary bg-gradient" style="min-width: 30%;">{{ __('Description') }}</th>
                            <th class="text-primary bg-gradient text-center" style="width: 4%">{{__('QTY')}}</th>
                            <th class="text-primary bg-gradient" style="width: 7%">{{__('Conditions')}}</th>
                            <th class="text-primary bg-gradient " style="width: 5%">{{__('Actions')}}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($ordersPartsNew as $part)
                            @php
                                $orderPartFormId = 'orderPartForm'.$part->id;
                                $currentAssembly = $part->orderComponentAssembly;
                                $displayIpl = $currentAssembly->assy_ipl_num ?? $part->orderComponent->ipl_num ?? '';
                                $displayName = trim(($part->orderComponent->name ?? '') . ($currentAssembly ? ' (assy)' : ''));
                                $displayPartNumber = $currentAssembly->assy_part_number ?? $part->orderComponent->part_number ?? '';
                            @endphp
                            <tr>
                                <td class="p-2">{{ $displayIpl }}</td>
                                <td class="p-2">{{ $displayName }}
                                    @if($part->source_tdr_id)
                                        {{-- §5 linked part order — raised automatically by a repair route --}}
                                        <span class="badge bg-info text-dark ms-1" style="font-size:9px"
                                              title="{{ __('Ordered automatically by a repair route of the carrier part') }}">auto · from route</span>
                                    @endif
                                </td>
                                <td class="p-2">{{ $displayPartNumber }}</td>
                                <td class="p-1 text-center">
                                    @include('admin.tdrs.partials.component-image', [
                                        'component' => $part->orderComponent,
                                        'gallery' => 'ordered-component-images-'.$current_wo->id,
                                    ])
                                </td>
                                <td class="p-2 align-middle" style="min-width: 10rem;">
                                        <textarea name="description" rows="1" class="form-control form-control-sm" form="{{ $orderPartFormId }}"
                                                  placeholder="{{ __('Description') }}">{{ $part->description ?? '' }}</textarea>
                                </td>
                                <td class="p-2 align-middle text-center" style="min-width: 5.0rem;">
                                        <input type="number" name="qty" value="{{ $part->qty }}" min="1" max="999999"
                                               class="form-control form-control-sm" form="{{ $orderPartFormId }}">
                                </td>
                                <td class="p-2 align-middle" style="min-width: 8rem;">
                                    @if($part->isManufacturePairMember())
                                        {{ $part->codes->name ?? '' }}
                                    @else
                                        <select name="codes_id" class="form-select form-select-sm" form="{{ $orderPartFormId }}">
                                            @foreach($codes as $code)
                                                <option value="{{ $code->id }}" @selected($code->id == $part->codes_id)>{{ $code->name }}</option>
                                            @endforeach
                                        </select>
                                    @endif
                                </td>
                                <td class="p-2 align-middle">
                                    <div class="d-flex justify-content-evenly align-items-center w-100 text-center">
                                            <form id="{{ $orderPartFormId }}" method="POST" action="{{ route('tdrs.update', $part->id) }}" class="d-inline">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="workorder_id" value="{{ $current_wo->id }}">
                                                <button type="submit" class="btn btn-link btn-sm p-0 border-0 bg-transparent text-primary" title="{{ __('Save') }}" aria-label="{{ __('Save') }}">
                                                    <i class="bi bi-floppy"></i>
                                                </button>
                                            </form>
                                        <form action="{{ route('tdrs.destroy', $part->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="return_to" value="show">
                                            <button type="button"
                                                    class="btn btn-link btn-sm p-0 border-0 bg-transparent text-danger"
                                                    title="{{ __('Delete') }}"
                                                    aria-label="{{ __('Delete') }}"
                                                    @if($part->isManufacturePairMember())
                                                        data-tdr-manufacture-pair-delete
                                                    @else
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#useConfirmDelete"
                                                        data-title="{{ __('Delete Confirmation') }}"
                                                    @endif>
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </form>
                                    </div>
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