(() => {
    'use strict';
    const safeUrl = (value, hosts) => {
        try { const url = new URL(value); return url.protocol === 'https:' && !url.username && !url.password && hosts.includes(url.hostname) ? url.href : null; }
        catch (_) { return null; }
    };
    function credit(element, photo) {
        if (!element) return;
        element.replaceChildren();
        const source = document.createElement('a');
        source.href = safeUrl(photo.source, ['commons.wikimedia.org']);
        source.target = '_blank'; source.rel = 'noopener noreferrer';
        source.textContent = `${photo.title} · ${photo.author || 'Wikimedia Commons'}`;
        const license = document.createElement('a');
        license.href = safeUrl(photo.license_url, ['creativecommons.org', 'commons.wikimedia.org']);
        license.target = '_blank'; license.rel = 'noopener noreferrer'; license.textContent = photo.license;
        element.append(source, document.createTextNode(' · '), license); element.hidden = false;
    }
    const requests = new Map();
    function requestPhoto(url, method = 'GET', data) {
        const key = `${method}:${url}`;
        if (!requests.has(key)) {
            const controller = new AbortController();
            const timeout = setTimeout(() => controller.abort(), 30000);
            const promise = fetch(url, {
                method,
                headers: {Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''},
                body: data ? JSON.stringify(data) : undefined,
                cache: 'no-store', spinner: false, signal: controller.signal,
            }).then(async response => {
                const result = await response.json();
                if (!response.ok || response.redirected) throw Object.assign(new Error(), {userMessage: result.message});
                return result;
            }).finally(() => { clearTimeout(timeout); requests.delete(key); });
            requests.set(key, promise);
        }
        return requests.get(key);
    }
    const owner = document.getElementById('internetBackgroundCredit');
    const preview = document.querySelector('[data-internet-background-preview]');
    const sourceSelect = document.getElementById('backgroundSource');
    if (sourceSelect) {
        const syncSource = () => {
            const internet = sourceSelect.value === 'internet';
            sourceSelect.form.querySelector('[data-background-internet-fields]').hidden = !internet;
            document.getElementById('backgroundTopic').required = internet;
        };
        sourceSelect.addEventListener('change', syncSource);
        syncSource();
    }
    function bind(element, url, isOwner) {
        let busy = false, sequence = 0, cadence = 'button', mode = 'internet', initialized = false, lastPhotoUrl = '';
        const button = element.querySelector('[data-background-next]');
        const form = element.querySelector('[data-background-form]');
        const save = element.querySelector('[data-background-save]');
        // Polling must never replace an unsaved topic or schedule in the open editor.
        function settings(value, saved = false) {
            cadence = value?.cadence || 'button';
            mode = value?.mode || 'internet';
            button.hidden = mode === 'off';
            if (form && (!initialized || saved)) {
                form.elements.enabled.checked = mode !== 'off';
                form.elements.topic.required = mode !== 'off';
                form.elements.topic.value = value?.topic || '';
                form.elements.cadence.value = cadence;
                initialized = true;
            }
        }
        function turnOff() {
            ++sequence;
            lastPhotoUrl = '';
            if (isOwner) {
                document.body.classList.remove('has-user-project-background');
                document.querySelector('.content')?.style.removeProperty('--user-project-background');
                document.querySelector('.cabinet-aircraft-stage')?.classList.remove('cabinet-aircraft-stage--photo');
            } else {
                element.querySelector('[data-background-image]').parentElement.hidden = true;
                element.querySelector('[data-background-credit]').hidden = true;
            }
            const status = element.querySelector('[data-background-status]');
            if (status) status.hidden = true;
        }
        async function apply(photo) {
            const ticket = ++sequence;
            const src = photo && safeUrl(photo.url, ['upload.wikimedia.org', 'thumb.wikimedia.org']);
            if (!src || !safeUrl(photo.source, ['commons.wikimedia.org'])) throw new Error();
            if (src === lastPhotoUrl) return;
            const image = new Image(); image.referrerPolicy = 'no-referrer'; image.src = src;
            let timeout;
            try {
                await Promise.race([image.decode(), new Promise((_, reject) => { timeout = setTimeout(reject, 25000); })]);
            } catch (_) {
                throw Object.assign(new Error(), {userMessage: 'A photo was found, but the image could not be downloaded. Press New photo to try another image.'});
            } finally { clearTimeout(timeout); }
            if (ticket !== sequence) return;
            if (isOwner) {
                document.querySelector('.content')?.style.setProperty('--user-project-background', `url("${src.replaceAll('"', '%22')}")`);
                document.body.classList.add('has-user-project-background');
            } else {
                const target = element.querySelector('[data-background-image]');
                target.src = src; target.parentElement.hidden = false;
                element.querySelector('[data-background-status]').hidden = true;
            }
            credit(element.querySelector('[data-background-credit]'), photo);
            lastPhotoUrl = src;
        }
        async function load(method = 'GET', data) {
            if (busy) return;
            busy = true; button.disabled = true; button.textContent = 'Loading…';
            if (save) save.disabled = true;
            try {
                const result = await requestPhoto(url, method, data);
                settings(result.settings, method === 'PATCH');
                if (method === 'PATCH') window.showNotification?.('Background settings saved.', 'success');
                if (mode === 'off') turnOff();
                else if (result.photo) await apply(result.photo);
                if (result.issue) throw Object.assign(new Error(), {userMessage: result.issue.message});
                if (!result.photo && mode !== 'off') throw new Error();
                const status = element.querySelector('[data-background-status]');
                if (status) status.hidden = true;
                document.dispatchEvent(new CustomEvent('project-background-updated', {detail: {url, ...result, origin: element}}));
            } catch (error) {
                const message = error.userMessage || 'Photo could not be loaded. The previous background is kept. Try New photo again later.';
                const status = element.querySelector('[data-background-status]');
                if (status) { status.textContent = message; status.hidden = false; }
                if (method !== 'GET') window.showNotification?.(message, 'warning');
            } finally { busy = false; button.disabled = false; button.textContent = 'New photo'; if (save) save.disabled = false; }
        }
        button.addEventListener('click', event => { event.preventDefault(); event.stopPropagation(); load('POST'); });
        form?.addEventListener('submit', event => {
            event.preventDefault(); event.stopPropagation();
            load('PATCH', {mode: form.elements.enabled.checked ? 'internet' : 'off', topic: form.elements.topic.value.trim(), cadence: form.elements.cadence.value});
        });
        form?.elements.enabled.addEventListener('change', () => { form.elements.topic.required = form.elements.enabled.checked; });
        document.addEventListener('project-background-updated', event => {
            if (event.detail.url === url && event.detail.origin !== element) {
                settings(event.detail.settings);
                if (mode === 'off') turnOff();
                else apply(event.detail.photo).catch(() => {});
            }
        });
        if (isOwner) {
            const refreshDaily = () => { if (mode === 'internet' && cadence === 'daily' && !document.hidden) load(); };
            setInterval(refreshDaily, 60000);
            document.addEventListener('visibilitychange', refreshDaily);
        }
        load();
    }
    if (owner) {
        // Background controls must not trigger outside-click resets in inline editors.
        ['click', 'dblclick', 'pointerdown', 'pointerup', 'mousedown', 'mouseup', 'keydown', 'keyup'].forEach(type => owner.addEventListener(type, event => event.stopPropagation()));
        owner.addEventListener('pointerdown', event => { if (event.target.closest('[data-background-next]')) event.preventDefault(); });
        bind(owner, owner.dataset.url, true);
    }
    if (preview) bind(preview, preview.dataset.internetBackgroundPreview, false);
})();
