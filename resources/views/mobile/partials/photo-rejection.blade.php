<style>
    #mobilePhotoRejected {
        width: min(560px, calc(100vw - 24px));
        max-height: calc(100dvh - 24px);
        margin: auto;
        padding: 0;
        overflow: hidden;
        border: 2px solid #dc3545;
        border-radius: 14px;
        background: #212529;
        color: #fff;
    }
    #mobilePhotoRejected::backdrop { background: rgba(0, 0, 0, .75); }
    #mobilePhotoRejected[open] { display: flex; flex-direction: column; }
    #mobilePhotoRejected .photo-rejection-header,
    #mobilePhotoRejected .photo-rejection-footer { flex: 0 0 auto; padding: 12px 16px; }
    #mobilePhotoRejected .photo-rejection-header { background: #b02a37; }
    #mobilePhotoRejected .photo-rejection-body { min-height: 0; overflow-y: auto; padding: 12px 16px; }
    #mobilePhotoRejectedPreview {
        display: block;
        width: 100%;
        height: min(36dvh, 300px);
        object-fit: contain;
        background: #111;
        border-radius: 6px;
    }
    #mobilePhotoRejectedPreview[hidden] { display: none; }
    #mobilePhotoRejected .photo-rejection-footer { display: flex; flex-wrap: wrap; gap: 8px; }
    #mobilePhotoRejected .photo-rejection-footer button { flex: 1 1 150px; min-height: 44px; }
</style>
<dialog id="mobilePhotoRejected" aria-labelledby="mobilePhotoRejectedTitle" aria-describedby="mobilePhotoRejectedMessage">
    <div class="photo-rejection-header">
        <h5 id="mobilePhotoRejectedTitle" class="mb-0">Photo not saved</h5>
    </div>
    <div class="photo-rejection-body">
        <p id="mobilePhotoRejectedMessage" role="alert"></p>
        <img id="mobilePhotoRejectedPreview" alt="Photo that has not been saved" hidden>
        <p id="mobilePhotoRejectedHint" class="small text-warning mt-2 mb-0">Turn your phone sideways, then press Retake.</p>
    </div>
    <div class="photo-rejection-footer">
        <button type="button" id="mobilePhotoRejectedClose" class="btn btn-outline-light">Close without saving</button>
        <button type="button" id="mobilePhotoRejectedRetake" class="btn btn-info" autofocus>Retake</button>
    </div>
    <input type="file" id="mobilePhotoRetryInput" accept="image/*" capture="environment" data-mobile-photo-retry hidden>
</dialog>
