<dialog id="mobileCameraPilot" aria-label="Camera">
    <button type="button" id="pilotCameraClose" aria-label="Close camera">
        <i class="bi bi-x-lg" aria-hidden="true"></i>
    </button>
    <div class="pilot-camera-body">
        <div class="pilot-camera-stage">
            <video id="pilotCameraVideo" autoplay muted playsinline webkit-playsinline></video>
            <img id="pilotCameraPreview" alt="Photo preview — not saved yet" hidden>
        </div>
        <p id="pilotCameraStatus" role="status" aria-live="polite" class="m-0"></p>
    </div>
    <section id="pilotCameraAccessHelp" aria-labelledby="pilotCameraAccessTitle" hidden>
        <h2 id="pilotCameraAccessTitle">Camera access blocked</h2>
        <div class="pilot-camera-access-body">
            <p id="pilotCameraAccessResult" role="status" aria-live="polite" hidden></p>
            <p>Tap <strong>Request access</strong> to try again. If the browser asks, choose <strong>Allow</strong>. This button cannot change a saved permission.</p>
            <p>If Safari does not ask again:</p>
            <ol>
                <li>Tap the menu beside the website address, then <strong>Website Settings</strong> (or <strong>…</strong>).</li>
                <li>For <strong id="pilotCameraPermissionSite"></strong>, choose <strong>Camera → Allow</strong>.</li>
                <li>Return here and tap <strong>Request access</strong> again.</li>
            </ol>
            <p class="small">In other browsers, allow Camera in this site's permissions. This page cannot change the permission for you.</p>
            <p id="pilotCameraRegularHint" hidden>Turn your phone horizontally, then tap Use regular camera again.</p>
        </div>
        <div class="pilot-camera-access-actions">
            <button type="button" class="btn btn-info" id="pilotCameraAllow">Request access</button>
            <button type="button" class="btn btn-outline-light" id="pilotCameraRegular">Use regular camera</button>
        </div>
    </section>
    <footer class="pilot-camera-footer">
        <button type="button" class="btn btn-outline-light" id="pilotCameraResume" hidden>Resume camera</button>
        <button type="button" class="btn btn-outline-light" id="pilotCameraRetake" hidden>Retake</button>
        <button type="button" id="pilotCameraCapture" aria-label="Take photo" disabled>
            <i class="bi bi-camera" aria-hidden="true"></i>
        </button>
        <button type="button" class="btn btn-success" id="pilotCameraSave" hidden>Save photo</button>
    </footer>
</dialog>
<script src="{{ asset('js/mobile-camera-pilot.js') }}?v={{ filemtime(public_path('js/mobile-camera-pilot.js')) }}" defer></script>
