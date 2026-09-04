(function () {
    'use strict';

    const MESSAGE = 'Turn your phone sideways before taking the photo. Mobile photos must be landscape.';
    const SCREEN_WARNING = 'TURN YOUR PHONE HORIZONTALLY!';
    const approvedChangeEvents = new WeakSet();
    let warningTimer = null;

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

    async function validateFiles(files) {
        for (const file of Array.from(files || [])) {
            const dimensions = await imageDimensions(file);
            if (dimensions.width <= dimensions.height) {
                warn();
                return false;
            }
        }

        return true;
    }

    function canOpenCamera() {
        if (!isPortraitViewport()) {
            return true;
        }

        warn();
        return false;
    }

    document.addEventListener('click', event => {
        const input = event.target;
        if (!isPhotoInput(input)) {
            return;
        }

        if (!canOpenCamera()) {
            event.preventDefault();
            event.stopImmediatePropagation();
        }
    }, true);

    document.addEventListener('change', event => {
        const input = event.target;
        if (!isPhotoInput(input)) {
            return;
        }
        if (approvedChangeEvents.has(input)) {
            approvedChangeEvents.delete(input);
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();

        validateFiles(input.files)
            .then(valid => {
                if (!valid) {
                    input.value = '';
                    return;
                }

                approvedChangeEvents.add(input);
                input.dispatchEvent(new Event('change', { bubbles: true }));
            })
            .catch(error => {
                input.value = '';
                if (typeof window.notifyError === 'function') {
                    window.notifyError(error.message || 'The selected image could not be read.');
                }
            });
    }, true);

    window.MobileLandscapePhoto = Object.freeze({
        message: MESSAGE,
        canOpenCamera,
        validateFiles,
    });
})();
