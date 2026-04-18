@extends('admin.master')

@section('style')
    <style>
        .photos-page {
            flex: 1 1 0%;
            min-height: 0;
            background: #212529;
            display: flex;
            flex-direction: column;
        }

        .photos-shell {
            background-color: #343A40;
            color: #f8f9fa;
            flex: 1 1 0%;
            min-height: 0;
            display: flex;
            flex-direction: column;
        }

        .photos-toolbar {
            border-bottom: 1px solid rgba(255, 255, 255, .12);
        }

        .photos-download-status {
            width: 220px;
            min-width: 180px;
        }

        .photos-download-status[hidden] {
            display: none !important;
        }

        .photos-download-status-text {
            font-size: .75rem;
            color: #adb5bd;
            line-height: 1.1;
            margin-bottom: 4px;
        }

        .photos-download-track {
            height: 4px;
            border-radius: 4px;
            overflow: hidden;
            background: rgba(255, 255, 255, .14);
        }

        .photos-download-bar {
            width: 45%;
            height: 100%;
            border-radius: 4px;
            background: #0dcaf0;
            animation: photosDownloadRun 1s infinite ease-in-out;
        }

        @keyframes photosDownloadRun {
            0% { transform: translateX(-110%); }
            100% { transform: translateX(240%); }
        }

        .photo-page-body {
            flex: 1 1 0%;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
            -webkit-overflow-scrolling: touch;
        }

        .group-dropzone {
            border: 0;
            background: transparent;
            min-height: 90px;
        }

        #photoPage.dnd-active .group-dropzone {
            border: 2px dashed rgba(255, 255, 255, .28);
            background: rgba(255, 255, 255, .04);
            box-shadow: inset 0 0 0 1px rgba(0, 0, 0, .45);
        }

        #photoPage.dnd-active .group-dropzone.drop-hover,
        .group-dropzone.drop-hover {
            border-color: rgba(13, 202, 240, .95);
            background: rgba(13, 202, 240, .12);
        }

        .group-dropzone::before {
            content: "Drop here";
            display: block;
            font-size: 11px;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, .35);
            margin-bottom: 6px;
        }

        .group-dropzone.drop-hover::before {
            color: rgba(13, 202, 240, .95);
        }

        .photo-item.dragging {
            opacity: .55;
        }

        .photo-thumbnail {
            width: 100%;
            aspect-ratio: 1 / 1;
            object-fit: cover;
        }

        .delete-photo-btn {
            top: -8px;
            right: -7px;
            width: 20px;
            height: 20px;
            z-index: 10;
            border: 1px solid rgba(var(--bs-primary-rgb), var(--bs-border-opacity, 1));
            border-radius: 50%;
            background: transparent;
            color: var(--bs-danger);
            line-height: 1;
            box-shadow: none;
        }

        .delete-photo-btn:hover,
        .delete-photo-btn:focus {
            border-color: rgba(var(--bs-primary-rgb), var(--bs-border-opacity, 1));
            background: transparent;
            color: var(--bs-danger);
            box-shadow: none;
        }

        .delete-photo-btn i {
            font-size: 15px;
            font-weight: 700;
        }

        @media (max-width: 1280px) {
            .delete-photo-btn {
                top: -6px;
                right: -5px;
                width: 16px;
                height: 16px;
            }

            .delete-photo-btn i {
                font-size: 11px;
            }
        }

        .group-hr {
            border: 0;
            border-top: 1px solid rgba(255, 255, 255, .10);
            opacity: 1;
        }
    </style>
@endsection

@section('content')
    <div id="photoPage"
         class="photos-page p-2"
         data-workorder-id="{{ $workorder->id }}">
        <div class="card photos-shell border-secondary shadow-lg">
            <div class="card-header photos-toolbar d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('mains.show', $workorder->id) }}"
                       class="btn btn-outline-secondary btn-sm"
                       onclick="showLoadingSpinner()">
                        <i class="bi bi-arrow-left"></i> Back to mains
                    </a>
                    <h5 class="mb-0 text-white">Pictures WO {{ $workorder->number }}</h5>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <div id="downloadAllPhotosStatus" class="photos-download-status" hidden>
                        <div id="downloadAllPhotosStatusText" class="photos-download-status-text">Preparing ZIP...</div>
                        <div class="photos-download-track">
                            <div class="photos-download-bar"></div>
                        </div>
                    </div>

                    <button type="button" class="btn btn-primary btn-sm" id="saveAllPhotos">
                        <i class="bi bi-download"></i> Download All
                    </button>
                </div>
            </div>

            <div class="card-body photo-page-body">
                <div id="photoPageContent" class="row g-3">
                    <div class="col-12 text-muted small">Loading...</div>
                </div>
            </div>
        </div>

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

        <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1055">
            <div id="photoDeletedToast"
                 class="toast bg-success text-white" role="alert"
                 aria-live="assertive" aria-atomic="true">
                <div class="toast-body">
                    Photo deleted successfully.
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        window.currentWorkorderId = {{ (int) $workorder->id }};
    </script>
    @include('admin.mains.partials.js.mains-photos-page')
@endsection
