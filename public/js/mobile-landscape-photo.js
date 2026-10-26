(function () {
    'use strict';

    const MESSAGE = 'Turn your phone sideways before taking the photo. Mobile photos must be landscape.';
    const SCREEN_WARNING = 'TURN YOUR PHONE HORIZONTALLY!';
    const approvedChangeEvents = new WeakSet();
    const selections = new WeakMap();
    let warningTimer = null;
    let dialogInput = null;
    let previewUrl = null;

    function clearWarning() {
        window.clearTimeout(warningTimer);
        warningTimer = null;
        const warning = document.getElementById('mobileLandscapeWarning');
        warning?.classList.remove('is-visible');
        warning?.setAttribute('aria-hidden', 'true');
    }

    function saved(count = 1) {
        clearWarning();
        window.showNotification(count > 1 ? `${count} photos saved.` : 'Photo saved.', 'success', 5000);
    }

    function isPhotoInput(input) {
        return input instanceof HTMLInputElement
            && input.type === 'file'
            && String(input.accept || '').toLowerCase().includes('image')
            && !input.hasAttribute('data-allow-portrait');
    }

    function isPortraitViewport() {
        if (window.matchMedia) {
            return window.matchMedia('(orientation: portrait)').matches;
        }

        return window.innerHeight > window.innerWidth;
    }

    function warn() {
        const warning = document.getElementById('mobileLandscapeWarning');
        if (warning) {
            warning.textContent = SCREEN_WARNING;
            warning.classList.add('is-visible');
            warning.setAttribute('aria-hidden', 'false');
            if (warningTimer !== null) {
                window.clearTimeout(warningTimer);
            }
            warningTimer = window.setTimeout(() => {
                warning.classList.remove('is-visible');
                warning.setAttribute('aria-hidden', 'true');
                warningTimer = null;
            }, 3000);
            return;
        }

        if (typeof window.notifyError === 'function') {
            window.notifyError(SCREEN_WARNING, 3000);
        }
    }

    function imageDimensions(file) {
        return new Promise((resolve, reject) => {
            const url = URL.createObjectURL(file);
            const image = new Image();

            image.onload = () => {
                const dimensions = {
                    width: Number(image.naturalWidth || 0),
                    height: Number(image.naturalHeight || 0),
                };
                URL.revokeObjectURL(url);
                resolve(dimensions);
            };
            image.onerror = () => {
                URL.revokeObjectURL(url);
                reject(new Error('The selected image could not be read.'));
            };
            image.src = url;
        });
    }

    async function inspectFiles(files) {
        for (const file of Array.from(files || [])) {
            try {
                const dimensions = await imageDimensions(file);
                if (dimensions.width <= dimensions.height) {
                    return { file, message: 'This photo is vertical or square. Take a new photo horizontally.' };
                }
            } catch (error) {
                return { file, message: error.message };
            }
        }
        return null;
    }

    async function validateFiles(files) { return !(await inspectFiles(files)); }

    function canSubmit(input) {
        if (!input?.files?.length) {
            if (input) selections.delete(input);
            return true;
        }
        const selection = selections.get(input);
        if (!selection) return true;
        if (selection.pending) {
            window.showNotification('Please wait while the photo is checked.', 'warning');
        } else {
            showRejection(input, selection);
        }
        return false;
    }

    function closeRejection() {
        document.getElementById('mobilePhotoRejected')?.close();
        const preview = document.getElementById('mobilePhotoRejectedPreview');
        preview?.removeAttribute('src');
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        previewUrl = null;
        dialogInput = null;
    }

    function updateRetakeButton() {
        const button = document.getElementById('mobilePhotoRejectedRetake');
        if (button) button.disabled = isPortraitViewport();
    }

    function showRejection(input, selection) {
        clearWarning();
        const dialog = document.getElementById('mobilePhotoRejected');
        if (!dialog || typeof dialog.showModal !== 'function') {
            window.showNotification(`Photo not saved. ${selection.error.message}`, 'error', 15000);
            return;
        }
        dialogInput = input;
        document.getElementById('mobilePhotoRejectedMessage').textContent = selection.error.message
            + (selection.files.length > 1 ? ` None of the ${selection.files.length} selected photos were uploaded. Select or retake the photos again.` : '')
            + ' The photo is only held on this page until you leave or select another photo.';
        const preview = document.getElementById('mobilePhotoRejectedPreview');
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        previewUrl = URL.createObjectURL(selection.error.file);
        preview.hidden = false;
        preview.onerror = () => { preview.hidden = true; };
        preview.src = previewUrl;
        updateRetakeButton();
        if (!dialog.open) dialog.showModal();
    }

    async function processSelection(input, files, replaceFiles = false) {
        if (!files?.length) return; // Cancelling the picker must keep the rejected preview.
        const selection = { files: Array.from(files), pending: true, error: null };
        selections.set(input, selection);
        selection.error = await inspectFiles(selection.files);
        if (selections.get(input) !== selection || !input.isConnected) return;
        selection.pending = false;
        if (selection.error) {
            showRejection(input, selection);
            return;
        }
        // The retry picker lives inside the dialog, above any underlying Bootstrap modal.
        if (replaceFiles) input.files = files;
        selections.delete(input);
        if (dialogInput === input) closeRejection();
        clearWarning();
        approvedChangeEvents.add(input);
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }

    document.getElementById('mobilePhotoRejectedClose')?.addEventListener('click', closeRejection);
    document.getElementById('mobilePhotoRejected')?.addEventListener('cancel', event => {
        event.preventDefault();
        closeRejection();
    });
    document.getElementById('mobilePhotoRejectedRetake')?.addEventListener('click', () => {
        if (!dialogInput || isPortraitViewport()) return;
        const retry = document.getElementById('mobilePhotoRetryInput');
        retry.accept = dialogInput.accept;
        retry.multiple = dialogInput.multiple;
        retry.value = '';
        retry.click(); // Keep this synchronous with the tap for mobile camera permissions.
    });
    document.getElementById('mobilePhotoRetryInput')?.addEventListener('change', event => {
        if (dialogInput) processSelection(dialogInput, event.target.files, true);
    });
    window.addEventListener('resize', updateRetakeButton);
    window.addEventListener('orientationchange', updateRetakeButton);

    function canOpenCamera() {
        if (!isPortraitViewport()) {
            return true;
        }

        warn();
        return false;
    }

    document.addEventListener('click', event => {
        const input = event.target;
        if (!isPhotoInput(input) || input.hasAttribute('data-mobile-photo-retry')) {
            return;
        }

        if (!canOpenCamera()) {
            event.preventDefault();
            event.stopImmediatePropagation();
        }
    }, true);

    document.addEventListener('change', event => {
        const input = event.target;
        if (!isPhotoInput(input) || input.hasAttribute('data-mobile-photo-retry')) {
            return;
        }
        if (approvedChangeEvents.has(input)) {
            approvedChangeEvents.delete(input);
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();

        if (!input.files?.length) {
            selections.delete(input);
            approvedChangeEvents.add(input);
            input.dispatchEvent(new Event('change', { bubbles: true }));
            return;
        }
        processSelection(input, input.files);
    }, true);

    // Retaining a rejected file must not allow a normal form to submit it.
    document.addEventListener('submit', event => {
        for (const input of Array.from(event.target.elements || [])) {
            if (canSubmit(input)) continue;
            event.preventDefault();
            event.stopImmediatePropagation();
            return;
        }
    }, true);

    window.MobileLandscapePhoto = Object.freeze({
        message: MESSAGE,
        canOpenCamera,
        validateFiles,
        canSubmit,
        saved,
    });
})();
