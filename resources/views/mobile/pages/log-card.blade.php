@extends('mobile.master')

@section('style')
    <style>
        .mobile-log-card-page {
            height: 100%;
            min-height: 0;
            display: flex;
            flex-direction: column;
            background: #212529;
            color: #f8f9fa;
        }

        .mobile-log-card-header,
        .mobile-log-card-gallery,
        .mobile-log-card-actions {
            flex: 0 0 auto;
        }

        .mobile-log-card-scroll {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            overscroll-behavior-y: contain;
            -webkit-overflow-scrolling: touch;
            padding-bottom: calc(1rem + env(safe-area-inset-bottom));
        }

        .mobile-log-card-row {
            background: #2b3035;
            border: 1px solid #495057;
            border-radius: .65rem;
        }

        .mobile-log-card-row.is-photo-target {
            border-color: #0dcaf0;
            box-shadow: 0 0 0 1px rgba(13, 202, 240, .35);
        }

        .mobile-log-card-gallery-track {
            display: flex;
            gap: .45rem;
            overflow-x: auto;
            scrollbar-width: thin;
        }

        .mobile-log-card-thumb {
            width: 54px;
            height: 54px;
            flex: 0 0 54px;
            object-fit: cover;
            border: 1px solid #6c757d;
            border-radius: .45rem;
        }

        .mobile-log-card-field-label {
            color: #8fd7ff;
            font-size: .72rem;
            margin-bottom: .2rem;
        }

        .mobile-log-card-meta {
            font-size: .76rem;
            color: #adb5bd;
        }

        .mobile-log-card-review-modal .modal-dialog {
            width: min(720px, calc(100% - 1rem));
            height: calc(100dvh - 1rem);
            max-width: none;
            margin: .5rem auto;
        }

        .mobile-log-card-review-modal .modal-content {
            height: 100%;
            min-height: 0;
            overflow: hidden;
            border: 1px solid #0dcaf0;
            border-radius: .8rem;
            background: #17232a;
            color: #f8f9fa;
        }

        .mobile-log-card-review-modal .modal-body {
            display: flex;
            min-height: 0;
            flex: 1 1 auto;
            flex-direction: column;
            gap: 1rem;
            overflow-y: auto;
            overscroll-behavior-y: contain;
        }

        .mobile-log-card-review-photo-wrap {
            display: flex;
            height: clamp(190px, 38dvh, 460px);
            min-height: 190px;
            flex: 0 0 auto;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border: 1px solid #495057;
            border-radius: .65rem;
            background: #050708;
        }

        .mobile-log-card-review-photo {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .mobile-log-card-review-modal .form-control {
            min-height: 52px;
            background-color: #1f2327;
            color: #fff;
            border-color: #6c757d;
            font-size: 1.1rem;
        }

        .mobile-log-card-review-modal .modal-footer {
            flex: 0 0 auto;
            padding-bottom: calc(.75rem + env(safe-area-inset-bottom));
        }

        .mobile-log-card-review-modal .modal-footer .btn {
            min-height: 48px;
            flex: 1 1 0;
        }

        .mobile-log-card-pn-warning {
            margin-top: -.35rem;
            font-size: .78rem;
            line-height: 1.25;
        }

        .mobile-log-card-number-swap {
            width: 38px;
            height: 34px;
            padding: 0;
            border-radius: 50%;
            line-height: 1;
        }

        .mobile-log-card-draft-choice {
            min-width: 0;
        }

        .mobile-log-card-draft-choice .form-select,
        .mobile-log-card-draft-choice .form-control,
        .mobile-log-card-row .form-select,
        .mobile-log-card-row .form-control {
            background-color: #1f2327;
            color: #fff;
            border-color: #6c757d;
        }

        .mobile-log-card-draft-choice .form-select:disabled,
        .mobile-log-card-draft-choice .form-control:disabled,
        .mobile-log-card-row .form-select:disabled,
        .mobile-log-card-row .form-control:disabled {
            opacity: .65;
        }
    </style>
@endsection

@section('content')
    <div id="mobileLogCardApp"
         class="mobile-log-card-page"
         data-workorder-id="{{ $workorder->id }}"
         data-workorder-number="{{ $workorder->number }}"
         data-data-url="{{ route('mobile.log-card.data', $workorder->id) }}"
         data-template-url="{{ route('mobile.log-card.template', $workorder->id) }}"
         data-store-url="{{ route('mobile.log-card.store', $workorder->id) }}"
         data-row-url-template="{{ route('mobile.log-card.rows.update', ['logCard' => '__CARD__', 'row' => '__ROW__']) }}"
         data-variant-url-template="{{ route('mobile.log-card.rows.variant.update', ['logCard' => '__CARD__', 'row' => '__ROW__']) }}"
         data-assembly-url-template="{{ route('mobile.log-card.rows.assembly.update', ['logCard' => '__CARD__', 'row' => '__ROW__']) }}"
         data-photo-url="{{ route('mobile.log-card.photo.store', $workorder) }}">

        <header class="mobile-log-card-header border-bottom border-info px-3 py-2">
            <div class="d-flex align-items-center justify-content-between gap-2">
                <div class="min-w-0">
                    <div class="fw-bold fs-5 text-info">WO {{ $workorder->number }} · Log Card</div>
                    <div class="small text-white-50 text-truncate">
                        {{ $workorder->unit?->part_number ?? '—' }} · {{ $workorder->unit?->name ?? '—' }}
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-info text-nowrap" data-photo-only>
                    <i class="bi bi-camera me-1"></i>Photo only
                </button>
            </div>
        </header>

        <section class="mobile-log-card-gallery border-bottom border-secondary px-3 py-2">
            <div class="d-flex align-items-center justify-content-between mb-1">
                <span class="small text-info">Log Card Photos</span>
                <span id="mobileLogCardPhotoCount" class="badge bg-secondary">{{ count($logCardPhotos) }}</span>
            </div>
            <div id="mobileLogCardGallery" class="mobile-log-card-gallery-track">
                @forelse($logCardPhotos as $photo)
                    <a href="{{ $photo['big_url'] }}" data-fancybox="mobile-log-card-photos">
                        <img src="{{ $photo['thumb_url'] }}" class="mobile-log-card-thumb" alt="{{ $photo['alt'] }}">
                    </a>
                @empty
                    <span class="small text-white-50" data-empty-gallery>No Log Card photos yet.</span>
                @endforelse
            </div>
        </section>

        <div id="mobileLogCardStatus" class="px-3 pt-2"></div>

        <main id="mobileLogCardContent" class="mobile-log-card-scroll px-2 pb-3">
            <div class="text-center text-white-50 py-5">
                <span class="spinner-border spinner-border-sm me-2"></span>Loading Log Card…
            </div>
        </main>

        <form id="mobileLogCardPhotoForm" class="d-none" enctype="multipart/form-data">
            <input id="mobileLogCardPhotoInput" type="file" name="photo" accept="image/*" capture="environment">
        </form>

        <div id="mobileLogCardRecognition" class="modal fade mobile-log-card-review-modal" tabindex="-1"
             aria-labelledby="mobileLogCardRecognitionTitle" aria-hidden="true"
             data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header border-secondary">
                        <div>
                            <h2 id="mobileLogCardRecognitionTitle" class="modal-title fs-5 text-info mb-1">
                                <i class="bi bi-stars me-1"></i>Check photographed numbers
                            </h2>
                            <div id="mobileLogCardRecognitionMeta" class="small text-white-50" aria-live="polite"></div>
                        </div>
                    </div>
                    <div class="modal-body p-3">
                        <div class="mobile-log-card-review-photo-wrap">
                            <img id="mobileLogCardRecognitionPhoto" class="mobile-log-card-review-photo"
                                 alt="Photographed component nameplate">
                        </div>
                        <div id="mobileLogCardPartNumberWarning" class="mobile-log-card-pn-warning text-warning d-none"
                             role="status"></div>
                        <div class="d-grid gap-2">
                            <div>
                                <label class="mobile-log-card-field-label fs-6" for="recognizedPartNumber">Part number</label>
                                <input id="recognizedPartNumber" type="text" class="form-control" maxlength="255"
                                       autocomplete="off" autocapitalize="characters" spellcheck="false">
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-info mobile-log-card-number-swap mx-auto"
                                    data-swap-recognized-numbers title="Swap P/N and S/N" aria-label="Swap part number and serial number">
                                <i class="bi bi-arrow-down-up"></i>
                            </button>
                            <div>
                                <label class="mobile-log-card-field-label fs-6" for="recognizedSerialNumber">Serial number</label>
                                <input id="recognizedSerialNumber" type="text" class="form-control" maxlength="255"
                                       autocomplete="off" autocapitalize="characters" spellcheck="false">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer flex-nowrap border-secondary">
                        <button type="button" class="btn btn-outline-light" data-retake-recognition>
                            <i class="bi bi-camera me-1"></i>Retake
                        </button>
                        <button type="button" class="btn btn-info" data-apply-recognition disabled>
                            <i class="bi bi-check2-circle me-1"></i>Confirm
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('js/mobile-log-card.js') }}?v={{ filemtime(public_path('js/mobile-log-card.js')) }}"></script>
@endsection
