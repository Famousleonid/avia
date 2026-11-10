(function () {
    'use strict';

    const dialog = document.getElementById('mobileCameraPilot');
    if (!dialog) return;
    const video = document.getElementById('pilotCameraVideo');
    const stage = video.parentElement;
    const preview = document.getElementById('pilotCameraPreview');
    const status = document.getElementById('pilotCameraStatus');
    const capture = document.getElementById('pilotCameraCapture');
    const save = document.getElementById('pilotCameraSave');
    const retake = document.getElementById('pilotCameraRetake');
    const resume = document.getElementById('pilotCameraResume');
    const close = document.getElementById('pilotCameraClose');
    // Build compatibility controls in JS so older cached Blade still works.
    const phoneCamera = document.createElement('button');
    phoneCamera.id = 'pilotCameraPhone';
    phoneCamera.type = 'button';
    phoneCamera.className = 'btn btn-outline-light';
    phoneCamera.textContent = 'Use phone camera';
    phoneCamera.hidden = true;
    capture.parentElement.insertBefore(phoneCamera, capture);
    const zoomButton = document.createElement('button');
    zoomButton.id = 'pilotCameraZoom';
    zoomButton.type = 'button';
    zoomButton.className = 'btn btn-outline-light';
    zoomButton.hidden = true;
    capture.parentElement.insertBefore(zoomButton, capture);
    const phoneInput = document.createElement('input');
    phoneInput.type = 'file';
    phoneInput.accept = 'image/*';
    phoneInput.setAttribute('capture', 'environment');
    // This picker has its own preview/validation below, inside the camera dialog.
    phoneInput.setAttribute('data-mobile-photo-retry', '');
    phoneInput.hidden = true;
    dialog.appendChild(phoneInput);
    // Create the overlay here so updating /public also supports older Blade HTML.
    const categoryLabel = document.createElement('p');
    categoryLabel.id = 'pilotCameraCategory';
    stage.appendChild(categoryLabel);
    const accessHelp = document.getElementById('pilotCameraAccessHelp');
    const allow = document.getElementById('pilotCameraAllow');
    const regular = document.getElementById('pilotCameraRegular');
    const regularHint = document.getElementById('pilotCameraRegularHint');
    // /public may be deployed before Blade views (or a phone may still have
    // older HTML open). Optional permission feedback must never block capture.
    let accessResult = document.getElementById('pilotCameraAccessResult');
    if (!accessResult) {
        accessResult = document.createElement('p');
        accessResult.id = 'pilotCameraAccessResult';
        accessResult.hidden = true;
        accessResult.setAttribute('role', 'status');
        accessResult.setAttribute('aria-live', 'polite');
        accessHelp.querySelector('.pilot-camera-access-body')?.prepend(accessResult);
    }
    document.getElementById('pilotCameraPermissionSite').textContent = window.location.hostname;
    let state = 'closed';
    let target = null;
    let stream = null;
    let cameraRequest = 0;
    let session = 0;
    let frame = null;
    let frameValid = false;
    let phoneMode = false;
    let phonePickerSession = null;
    let frameUrl = null;
    let orientationVersion = 0;
    let stableAfter = 0;
    let refreshTimer = null;
    let confirming = false;
    let videoFrameCallback = null;
    let videoSize = '';
    let viewportLandscape = window.matchMedia('(orientation: landscape)').matches;
    const MAX_ZOOM = 4;
    let zoom = null;
    let pinch = null;

    function updateZoomLabel() {
        zoomButton.textContent = `${(zoom?.applied || 1).toFixed(1)}×`;
        zoomButton.title = `${zoom?.hardware ? 'Camera zoom' : 'Digital zoom (reduced detail)'}. Pinch to zoom; tap to reset.`;
        zoomButton.setAttribute('aria-label', `${zoomButton.textContent} zoom. Reset zoom`);
        zoomButton.hidden = state !== 'live' || !zoom;
    }

    function configureZoom(track) {
        zoom = { track, hardware: false, base: 1, max: MAX_ZOOM, step: 0, requested: 1, applied: 1, digital: 1, busy: false };
        try {
            const range = track.getCapabilities?.().zoom;
            const current = Number(track.getSettings?.().zoom);
            if (range && Number.isFinite(range.min) && Number.isFinite(range.max) && range.min > 0
                && Number.isFinite(current) && current >= range.min && current < range.max && track.applyConstraints) {
                zoom.hardware = true;
                zoom.base = current;
                zoom.min = range.min;
                zoom.step = Number(range.step) || 0;
                zoom.max = Math.min(MAX_ZOOM, range.max / current);
            }
        } catch (_) { /* Optional capabilities must never prevent camera use. */ }
        fitLiveVideo();
        updateZoomLabel();
    }

    async function applyCameraZoom(active) {
        if (active.busy) return;
        active.busy = true;
        refreshCapture();
        try {
            while (zoom === active && Math.abs(active.requested - active.applied) > .001) {
                const desired = active.requested;
                const constraints = { ...(active.track.getConstraints?.() || {}) };
                delete constraints.zoom;
                const advanced = (constraints.advanced || []).map(item => {
                    const copy = { ...item }; delete copy.zoom; return copy;
                });
                await active.track.applyConstraints({ ...constraints, advanced: [...advanced, { zoom: desired * active.base }] });
                if (zoom !== active) return;
                const actual = Number(active.track.getSettings?.().zoom) / active.base;
                if (!Number.isFinite(actual) || Math.abs(actual - desired) > Math.max(.02, active.step / active.base)) {
                    throw new Error('Zoom was not applied');
                }
                active.applied = actual;
                // A device may quantize the requested value to its supported step.
                if (active.requested === desired) active.requested = actual;
                updateZoomLabel();
            }
        } catch (_) {
            if (zoom !== active) return;
            let actual = NaN;
            try { actual = Number(active.track.getSettings?.().zoom) / active.base; } catch (_) { /* Keep the last confirmed zoom. */ }
            if (Math.abs(actual - 1) < .001) {
                // A browser may advertise zoom but ignore/reject the constraint.
                active.hardware = false;
                active.max = MAX_ZOOM;
                active.digital = active.applied = active.requested;
                fitLiveVideo();
            } else {
                if (Number.isFinite(actual) && actual >= 1) active.applied = actual;
                active.requested = active.applied;
                window.showNotification?.('Camera zoom could not change. The current zoom has been kept.', 'warning');
                const notification = document.querySelector('.notify-status');
                if (notification) dialog.appendChild(notification);
            }
        } finally {
            if (zoom === active) {
                active.busy = false;
                stableAfter = Math.max(stableAfter, performance.now() + 250);
                updateZoomLabel();
                refreshCapture();
                window.clearTimeout(refreshTimer);
                refreshTimer = window.setTimeout(refreshCapture, 300);
            }
        }
    }

    function setZoom(value) {
        if (!zoom || state !== 'live') return;
        let next = Math.max(1, Math.min(zoom.max, value));
        if (zoom.hardware && zoom.step > 0) {
            next = Math.max(1, Math.min(zoom.max,
                (zoom.min + Math.round((next * zoom.base - zoom.min) / zoom.step) * zoom.step) / zoom.base));
        }
        zoom.requested = next;
        if (zoom.hardware) void applyCameraZoom(zoom);
        else {
            zoom.digital = zoom.applied = next;
            fitLiveVideo();
            updateZoomLabel();
        }
    }

    function pinchDistance(touches) {
        return Math.hypot(touches[1].clientX - touches[0].clientX, touches[1].clientY - touches[0].clientY);
    }
    stage.addEventListener('touchstart', event => {
        if (state !== 'live' || !zoom || event.touches.length !== 2) { pinch = null; return; }
        event.preventDefault();
        pinch = { distance: pinchDistance(event.touches), zoom: zoom.requested,
            ids: Array.from(event.touches, touch => touch.identifier).join(':') };
        refreshCapture();
    }, { passive: false });
    stage.addEventListener('touchmove', event => {
        if (!pinch || state !== 'live' || event.touches.length !== 2) return;
        event.preventDefault();
        if (pinch.ids !== Array.from(event.touches, touch => touch.identifier).join(':')) { pinch = null; return; }
        if (pinch.distance > 0) setZoom(pinch.zoom * pinchDistance(event.touches) / pinch.distance);
    }, { passive: false });
    ['touchend', 'touchcancel'].forEach(name => stage.addEventListener(name, () => {
        pinch = null;
        refreshCapture();
    }));
    zoomButton.addEventListener('click', () => setZoom(1));

    // Crop in the source's upright coordinate system; never rotate pixels to
    // manufacture landscape dimensions. Both the viewfinder and JPEG use this.
    function landscapeCrop(width, height, digital = 1) {
        const baseHeight = Math.min(height, width * 3 / 4);
        const units = Math.floor(baseHeight / 3);
        const cropHeight = baseHeight / digital;
        const cropWidth = cropHeight * 4 / 3;
        return { x: (width - cropWidth) / 2, y: (height - cropHeight) / 2,
            width: cropWidth, height: cropHeight, outputWidth: units * 4, outputHeight: units * 3 };
    }

    function fitLiveVideo() {
        if (!dialog.open || !video.videoWidth || !video.videoHeight) return;
        const bounds = stage.getBoundingClientRect();
        if (!bounds.width || !bounds.height) return;
        videoSize = `${video.videoWidth}:${video.videoHeight}`;
        // Size the element itself to cover its clipping parent. This avoids a
        // second object-fit scaling step in the native video layer on rotation.
        // Digital zoom uses the same centred crop here and in the saved JPEG.
        const crop = landscapeCrop(video.videoWidth, video.videoHeight, zoom?.digital || 1);
        const scale = bounds.width / crop.width;
        const width = `${video.videoWidth * scale}px`;
        const height = `${video.videoHeight * scale}px`;
        if (video.style.width === width && video.style.height === height) return;
        Object.assign(video.style, {
            width, height, left: '50%', top: '50%', right: 'auto', bottom: 'auto',
            transform: 'translate(-50%, -50%)', objectFit: 'fill',
        });
    }

    function watchVideoSize() {
        if (videoFrameCallback !== null) return;
        const request = cameraRequest;
        const update = () => {
            videoFrameCallback = null;
            if (request !== cameraRequest || !stream) return;
            if (videoSize !== `${video.videoWidth}:${video.videoHeight}`) fitLiveVideo();
            // Video resize events can arrive a frame after the dimensions.
            // Check before paint; reading the stage is needed only on change.
            videoFrameCallback = requestAnimationFrame(update);
        };
        update();
    }

    function setStatus(message = '') {
        status.textContent = message;
        status.hidden = !message;
    }

    function setState(next, message) {
        state = next;
        if (next !== 'live') pinch = null;
        updateZoomLabel();
        dialog.dataset.state = next;
        dialog.dataset.permissionDenied = 'false';
        accessHelp.hidden = true;
        regularHint.hidden = true;
        setStatus(message);
        const reviewing = next === 'review' || next === 'saving';
        // Keep the live feed running behind the opaque review image.
        video.hidden = false;
        preview.hidden = !reviewing;
        capture.hidden = reviewing || next === 'error' || next === 'playback';
        save.hidden = !reviewing;
        retake.hidden = !reviewing;
        resume.hidden = !['paused', 'error', 'playback'].includes(next);
        resume.textContent = next === 'playback' ? 'Start preview' : 'Resume camera';
        close.disabled = next === 'saving' || next === 'capturing';
        save.disabled = next === 'saving';
        if (reviewing && !frameValid) save.disabled = true;
        retake.disabled = next === 'saving';
        save.textContent = next === 'saving' ? 'Saving…' : 'Save photo';
        capture.disabled = true;
        dialog.dataset.blocked = 'false';
        phoneCamera.hidden = next !== 'error' && next !== 'playback';
        if (next === 'live' || next === 'native') refreshCapture();
    }

    function stopCamera() {
        cameraRequest++;
        zoom = null;
        pinch = null;
        updateZoomLabel();
        if (videoFrameCallback !== null) {
            cancelAnimationFrame(videoFrameCallback);
            videoFrameCallback = null;
        }
        const previous = stream;
        stream = null;
        previous?.getTracks().forEach(track => track.stop());
        video.srcObject = null;
    }

    function releaseFrame() {
        frame = null;
        frameValid = false;
        preview.removeAttribute('src');
        if (frameUrl) URL.revokeObjectURL(frameUrl);
        frameUrl = null;
    }

    function finish() {
        session++;
        stopCamera();
        releaseFrame();
        window.clearTimeout(refreshTimer);
        dialog.querySelectorAll('.notify-status').forEach(notification => notification.remove());
        dialog.close();
        setState('closed');
        phoneMode = false;
        phonePickerSession = null;
        target = null;
    }

    function captureBlocker() {
        if (document.hidden || !stream || !stream.getVideoTracks().some(track => track.readyState === 'live' && !track.muted)) {
            return 'Camera is not ready. Wait or close and reopen it.';
        }
        if (video.paused || video.ended) return 'Camera preview paused. Tap Start preview.';
        if (pinch || zoom?.busy) return 'Hold still while the camera adjusts…';
        if (performance.now() < stableAfter || video.readyState < 2 || video.videoWidth < 4 || video.videoHeight < 3) {
            return 'Hold still while the camera adjusts…';
        }
        return '';
    }

    function refreshCapture() {
        if (state === 'native') {
            capture.disabled = false;
            dialog.dataset.blocked = 'false';
            setStatus('Tap the camera button to take a photo.');
            return;
        }
        if (state !== 'live') return;
        updateZoomLabel();
        const reason = captureBlocker();
        capture.disabled = !!reason;
        dialog.dataset.blocked = String(!!reason);
        setStatus(reason);
        resume.hidden = true;
        phoneCamera.hidden = true;
        capture.hidden = false;
    }

    function orientationChanged() {
        const landscape = window.matchMedia('(orientation: landscape)').matches;
        // Video resize/playing and browser-toolbar resizes are not new turns.
        if (landscape !== viewportLandscape) {
            pinch = null;
            viewportLandscape = landscape;
        }
        fitLiveVideo();
        orientationVersion++;
        stableAfter = performance.now() + 500;
        refreshCapture();
        window.clearTimeout(refreshTimer);
        refreshTimer = window.setTimeout(refreshCapture, 550);
    }

    function returnToCamera() {
        if (phoneMode) {
            setState('native');
        } else if (document.hidden) {
            stopCamera();
            setState('paused', 'Camera paused when you left the page. Tap Resume camera.');
        } else if (stream?.getVideoTracks().some(track => track.readyState === 'live' && !track.muted)) {
            if (video.paused || video.ended) startPreview();
            else setState('live');
        } else {
            startCamera();
        }
    }

    async function startPreview() {
        const request = cameraRequest;
        setState('opening', 'Starting camera preview…');
        try {
            video.muted = true;
            video.defaultMuted = true;
            video.playsInline = true;
            fitLiveVideo();
            watchVideoSize();
            // A retry reaches play() directly in the user's click, without
            // another permission request or replacing an already allowed stream.
            await video.play();
            if (request !== cameraRequest) return;
            const track = stream?.getVideoTracks()[0];
            if (track && zoom?.track !== track) configureZoom(track);
            stableAfter = performance.now() + 500;
            setState('live');
            refreshTimer = window.setTimeout(refreshCapture, 550);
        } catch (error) {
            if (request !== cameraRequest) return;
            if (error.name === 'NotAllowedError') {
                setState('playback', 'Camera access was received. Tap Start preview to show the picture.');
            } else {
                stopCamera();
                setState('error', 'Camera access was received, but the preview could not start. Tap Resume camera to try again.');
            }
        }
    }

    async function startCamera(permissionRetry = false) {
        stopCamera();
        accessResult.hidden = true;
        accessResult.textContent = '';
        if (!window.isSecureContext || !navigator.mediaDevices?.getUserMedia) {
            setState('error', 'This camera needs HTTPS and camera access. Close this view to use the regular camera.');
            return;
        }
        const request = cameraRequest;
        setState('opening', 'Allow camera access when your phone asks.');
        try {
            const constraints = { facingMode: { ideal: 'environment' }, width: { ideal: 2560 }, height: { ideal: 1920 } };
            const acquired = await navigator.mediaDevices.getUserMedia({ audio: false, video: constraints });
            if (request !== cameraRequest || state === 'closed' || document.hidden) {
                acquired.getTracks().forEach(track => track.stop());
                return;
            }
            stream = acquired;
            acquired.getVideoTracks().forEach(track => {
                track.addEventListener('mute', refreshCapture);
                track.addEventListener('unmute', orientationChanged);
                track.addEventListener('ended', () => {
                    if (stream === acquired && ['live', 'opening', 'playback'].includes(state)) {
                        stopCamera();
                        setState('paused', 'Camera access stopped. Tap Resume camera.');
                    }
                });
            });
            video.playsInline = true;
            video.srcObject = acquired;
            await startPreview();
        } catch (error) {
            if (request !== cameraRequest) return;
            stopCamera();
            const message = error.name === 'NotAllowedError'
                ? 'Camera access was denied. Allow it in your browser settings, or close this view to use the regular camera.'
                : 'The camera could not start. Close other camera apps and tap Resume camera.';
            setState('error', message);
            if (error.name === 'NotAllowedError') {
                dialog.dataset.permissionDenied = 'true';
                accessHelp.hidden = false;
                setStatus();
                if (permissionRetry) {
                    accessResult.textContent = 'The browser refused access again. Permission has not changed. Follow the settings steps below, or use the regular camera.';
                    accessResult.hidden = false;
                    accessHelp.querySelector('.pilot-camera-access-body').scrollTop = 0;
                }
            }
        }
    }

    async function confirmAction(options) {
        if (confirming) return false;
        if (typeof window.confirmDialog !== 'function') {
            setStatus('Confirmation is unavailable. Your photo has not been changed.');
            return false;
        }
        confirming = true;
        const current = session;
        // The shared confirmation must be above this native dialog's top layer.
        dialog.close();
        try {
            const answer = await window.confirmDialog(options);
            const confirmation = document.getElementById('globalConfirmModal');
            // confirmDialog resolves before Bootstrap's fade-out finishes. Do not allow
            // the next confirmation to open until the previous hidden event is complete.
            if (confirmation && getComputedStyle(confirmation).display !== 'none') {
                await new Promise(resolve => confirmation.addEventListener('hidden.bs.modal', resolve, { once: true }));
            }
            return answer === true && current === session;
        } catch (error) {
            setStatus('Confirmation is unavailable. Your photo has not been changed.');
            return false;
        } finally {
            confirming = false;
            if (current === session && state !== 'closed') dialog.showModal();
        }
    }

    async function requestClose() {
        if (confirming || state === 'saving' || state === 'capturing') return;
        if (frame && !await confirmAction({ title: 'Photo not saved', message: 'Discard this unsaved photo and close the test camera?', okText: 'Discard photo', danger: true })) return;
        finish();
    }

    capture.addEventListener('click', async () => {
        if (state === 'native') { openPhoneCamera(); return; }
        if (state !== 'live' || captureBlocker()) { refreshCapture(); return; }
        dialog.querySelectorAll('.notify-status').forEach(notification => notification.remove());
        const current = session;
        const orientation = orientationVersion;
        const canvas = document.createElement('canvas');
        const crop = landscapeCrop(video.videoWidth, video.videoHeight, zoom?.digital || 1);
        canvas.width = crop.outputWidth;
        canvas.height = crop.outputHeight;
        setState('capturing', 'Preparing photo…');
        try {
            canvas.getContext('2d').drawImage(video,
                crop.x, crop.y, crop.width, crop.height,
                0, 0, canvas.width, canvas.height);
            const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', 0.94));
            if (current !== session) return;
            if (!blob) throw new Error('The camera could not prepare this photo. Try again.');
            // A turn during encoding invalidates the old framing, in either direction.
            if (orientation !== orientationVersion || canvas.width <= canvas.height) {
                returnToCamera();
                return;
            }
            frame = new File([blob], `camera-${Date.now()}.jpg`, { type: 'image/jpeg' });
            frameValid = true;
            frameUrl = URL.createObjectURL(frame);
            preview.src = frameUrl;
            setState('review', 'Photo not saved.');
        } catch (error) {
            if (current !== session) return;
            returnToCamera();
            setStatus(error.message || 'The photo could not be taken. Try again.');
        } finally {
            canvas.width = canvas.height = 0;
        }
    });

    retake.addEventListener('click', async () => {
        if (state !== 'review' || confirming) return;
        if (!await confirmAction({ title: 'Retake photo', message: 'Discard this unsaved photo and take another one?', okText: 'Retake' })) return;
        releaseFrame();
        returnToCamera();
    });

    save.addEventListener('click', async () => {
        if (state !== 'review' || !frame || !frameValid || confirming) return;
        setState('saving', 'Saving photo… Please keep this page open.');
        const currentTarget = target;
        const body = new FormData();
        body.append('photos[]', frame);
        let data;
        try {
            const response = await fetch(currentTarget.url, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                body,
            });
            data = await response.json().catch(() => ({}));
            if (!response.ok || data.success !== true) {
                const reason = Object.values(data.errors || {}).flat()[0] || data.message || `Upload failed (HTTP ${response.status}).`;
                throw new Error(reason);
            }
        } catch (error) {
            setState('review', `Saving was not confirmed. ${error.message} Your photo is still here. Check the gallery before retrying if the connection was interrupted.`);
            return;
        }
        // Save photo is the user's confirmation. Keep this dialog and stream open.
        releaseFrame();
        returnToCamera();
        window.MobileLandscapePhoto.saved();
        const notification = document.querySelector('.notify-status');
        if (notification) dialog.appendChild(notification);
        document.dispatchEvent(new CustomEvent('mobile:camera-pilot-saved', { detail: { ...currentTarget, response: data } }));
    });

    close.addEventListener('click', requestClose);
    function openPhoneCamera() {
        if (frame || confirming || ['closed', 'saving', 'capturing', 'review'].includes(state)) return;
        phoneMode = true;
        stopCamera(); // Release the camera before the system camera needs it.
        setState('native');
        phoneInput.value = '';
        phonePickerSession = session;
        phoneInput.click(); // Must remain synchronous with the user's tap.
    }
    phoneCamera.addEventListener('click', openPhoneCamera);
    phoneInput.addEventListener('change', async () => {
        const selected = phoneInput.files?.[0];
        phoneInput.value = '';
        if (!selected || phonePickerSession !== session || !phoneMode || state === 'closed' || state === 'saving') return;
        const current = session;
        releaseFrame();
        frame = selected;
        frameUrl = URL.createObjectURL(selected);
        preview.src = frameUrl;
        setState('review', 'Checking photo… Photo not saved.');
        const canvas = document.createElement('canvas');
        try {
            // Only the Test camera fallback is cropped. HTML image decoding
            // respects EXIF orientation before we choose the upright crop.
            const image = new Image();
            image.src = frameUrl;
            await image.decode();
            if (current !== session || frame !== selected) return;
            const crop = landscapeCrop(image.naturalWidth, image.naturalHeight);
            canvas.width = crop.outputWidth;
            canvas.height = crop.outputHeight;
            canvas.getContext('2d').drawImage(image, crop.x, crop.y, crop.width, crop.height,
                0, 0, canvas.width, canvas.height);
            const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', 0.94));
            if (current !== session || frame !== selected) return;
            if (!blob || !canvas.height || canvas.width <= canvas.height) throw new Error('Invalid photo');
            releaseFrame();
            frame = new File([blob], `camera-${Date.now()}.jpg`, { type: 'image/jpeg' });
            frameUrl = URL.createObjectURL(frame);
            preview.src = frameUrl;
            frameValid = true;
            setState('review', 'Photo not saved.');
        } catch (error) {
            if (current !== session || frame !== selected) return;
            setState('review', 'Photo not saved. This image could not be checked. Retake it as a JPEG photo.');
        } finally {
            canvas.width = canvas.height = 0;
        }
    });
    dialog.addEventListener('cancel', event => {
        // A file input's cancel event bubbles too; cancelling the system picker
        // must not be interpreted as Escape/back on the camera dialog.
        if (event.target !== dialog) return;
        event.preventDefault();
        requestClose();
    });
    resume.addEventListener('click', () => {
        if (state === 'playback' && stream?.getVideoTracks().some(track => track.readyState === 'live')) startPreview();
        else if (['paused', 'error', 'playback'].includes(state)) startCamera();
    });
    allow.addEventListener('click', () => {
        // A user gesture retries the browser request; a remembered Deny still
        // requires the user to change Safari's website permission themselves.
        if (state === 'error' && !accessHelp.hidden) startCamera(true);
    });
    regular.addEventListener('click', () => {
        if (state !== 'error' || accessHelp.hidden || frame || confirming) return;
        if (!window.matchMedia('(orientation: landscape)').matches) {
            regularHint.hidden = false;
            regularHint.scrollIntoView({ block: 'nearest' });
            return;
        }
        const button = target?.regularButton;
        if (!button) return;
        finish();
        // Keep the native file chooser in this same user gesture (no await).
        button.click();
    });
    ['loadedmetadata', 'loadeddata', 'resize', 'playing'].forEach(name => video.addEventListener(name, orientationChanged));
    // Container layout and source orientation can settle in either order.
    // ResizeObserver runs before paint and covers changes without window.resize.
    if (typeof ResizeObserver === 'function') new ResizeObserver(fitLiveVideo).observe(stage);
    video.addEventListener('timeupdate', refreshCapture);
    video.addEventListener('pause', () => {
        // A live track alone does not guarantee that the viewfinder is playing.
        if (state === 'live' && stream && video.paused) {
            setState('playback', 'Camera preview paused. Tap Start preview.');
        }
    });
    window.addEventListener('resize', orientationChanged);
    window.addEventListener('orientationchange', orientationChanged);
    window.screen.orientation?.addEventListener('change', orientationChanged);
    function suspendCamera() {
        if (state !== 'closed') {
            stopCamera();
            if (['live', 'opening', 'playback'].includes(state)) {
                setState('paused', 'Camera paused when you left the page. Tap Resume camera.');
            }
        }
    }
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) suspendCamera();
    });
    window.addEventListener('pagehide', suspendCamera);

    document.addEventListener('click', event => {
        const button = event.target.closest('.js-pilot-camera-btn');
        if (!button || state !== 'closed') return;
        event.preventDefault();
        if (typeof dialog.showModal !== 'function') {
            window.showNotification('Test camera is not supported on this browser. Use the regular camera.', 'error');
            return;
        }
        const category = button.dataset.photoCategory || document.querySelector('.category-label.active')?.dataset.category || 'received';
        const label = Array.from(document.querySelectorAll('.category-label')).find(element => element.dataset.category === category)?.textContent.trim() || category;
        const workorderId = button.dataset.workorderId;
        const template = document.getElementById('photo-upload-form').dataset.urlTemplate;
        target = {
            workorderId, workorderNumber: button.dataset.workorderNumber, category, label,
            url: template.replace('WORKORDER_ID', workorderId) + '?category=' + encodeURIComponent(category),
            regularButton: button.parentElement.querySelector('.js-camera-btn'),
        };
        categoryLabel.textContent = target.label;
        session++;
        // Prepare the full-screen opening state before the first visible frame.
        setState('opening', 'Allow camera access when your phone asks.');
        dialog.showModal();
        startCamera();
    });
})();
