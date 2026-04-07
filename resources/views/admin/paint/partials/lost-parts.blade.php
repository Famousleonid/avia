@php
    $lostParts = $lostParts ?? collect();
    $authId = auth()->id();
    $lostTotal = $lostParts->count();
@endphp

<fieldset class="paint-lost-fieldset" id="paintLostPanel">
    <legend class="paint-lost-legend text-secondary">
        <span id="paintLostCountWrap"
              class="paint-lost-count flex-shrink-0"
              data-total="{{ $lostTotal }}"
              aria-live="polite">
            <span id="paintLostCountText">{{ $lostTotal }} {{ $lostTotal === 1 ? 'part lost' : 'parts lost' }}</span>
        </span>
        <input type="search"
               id="paintLostSearch"
               class="form-control form-control-sm paint-lost-search-input"
               placeholder="Search part number..."
               autocomplete="off"
               aria-label="Search part number">
    </legend>
    <div class="d-flex flex-row align-items-start gap-2">
        <div class="flex-grow-1 paint-lost-scroll overflow-x-auto overflow-y-hidden">
            @if($lostParts->isEmpty())
                <p class="text-muted small mb-0 py-2 px-1">No lost parts recorded.</p>
            @else
                <div class="d-flex flex-nowrap gap-3 pb-2 align-items-start paint-lost-track">
                    @foreach ($lostParts as $lost)
                        @php
                            $thumb = $lost->getFirstMediaThumbnailUrl('lost');
                            $big = $lost->getFirstMediaBigUrl('lost');
                            $caption = trim($lost->part_number . (($lost->serial_number ?? '') !== '' ? ' · S/N: ' . $lost->serial_number : ''));
                            $canDel = $authId !== null && ((int) $lost->user_id === (int) $authId || (auth()->user()?->roleIs('Admin') ?? false));
                            $lostSearchHay = \Illuminate\Support\Str::lower((string) ($lost->part_number ?? ''));
                        @endphp
                        <div class="flex-shrink-0 paint-lost-item text-center"
                             style="width: 100px;"
                             data-paint-lost-search="{{ e($lostSearchHay) }}">
                            <div class="small text-secondary text-truncate mb-1 px-0" title="{{ $lost->part_number }}">{{ $lost->part_number }}</div>
                            <div class="position-relative d-inline-block px-1 ">
                                <a href="{{ $big }}"
                                   class="d-block"
                                   data-fancybox="paint-lost"
                                   data-caption="{{ $caption }}">
                                    <img src="{{ $thumb }}"
                                         width="80"
                                         height="80"
                                         class="rounded border border-secondary border-opacity-50 paint-lost-thumb"
                                         alt=""
                                         loading="lazy">
                                </a>
                                @if($canDel)
                                    <button type="button"
                                            class="btn btn-danger btn-sm p-0 rounded-circle position-absolute js-paint-lost-delete"
                                            style="top: -4px; right: -4px; width: 18px; height: 18px; line-height: 1; font-size: 14px; z-index: 2;"
                                            data-delete-url="{{ route('paint.lost.destroy', $lost) }}"
                                            title="Delete"
                                            aria-label="Delete">&times;</button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="flex-shrink-0 pt-0">
            <button type="button"
                    class="btn btn-outline-success btn-sm"
                    data-bs-toggle="modal"
                    data-bs-target="#paintLostAddModal"
                    title="Add lost part">
                <i class="bi bi-plus-lg"></i>
            </button>
        </div>
    </div>
</fieldset>

{{-- Модалка: part number / serial / photo / comment --}}
<div class="modal fade" id="paintLostAddModal" tabindex="-1" aria-labelledby="paintLostAddModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-light border-secondary">
            <form id="paintLostForm"
                  enctype="multipart/form-data"
                  autocomplete="off">
                @csrf
                <div class="modal-header border-secondary py-2">
                    <h6 class="modal-title" id="paintLostAddModalLabel">Lost part</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label small text-secondary mb-1" for="paintLostPart">Part number</label>
                        <input type="text" name="part_number" id="paintLostPart" class="form-control form-control-sm dir-input" required maxlength="255">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small text-secondary mb-1" for="paintLostSerial">Serial #</label>
                        <input type="text" name="serial_number" id="paintLostSerial" class="form-control form-control-sm dir-input" maxlength="255" placeholder="optional">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small text-secondary mb-1" for="paintLostPhoto">Photo</label>
                        <input type="file" name="photo" id="paintLostPhoto" class="form-control form-control-sm dir-input" accept="image/*" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small text-secondary mb-1" for="paintLostComment">Comment</label>
                        <input type="text" name="comment" id="paintLostComment" class="form-control form-control-sm dir-input" maxlength="2000" placeholder="optional">
                    </div>
                    <div class="d-none text-danger small mb-0" id="paintLostErr"></div>
                </div>
                <div class="modal-footer border-secondary py-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm" id="paintLostSubmit">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
