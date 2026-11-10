(() => {
    'use strict';
    const input = document.getElementById('birthdayBackgroundImage');
    const preview = document.getElementById('birthdayBackgroundPreview');
    let localUrl;
    input?.addEventListener('change', () => {
        if (localUrl) URL.revokeObjectURL(localUrl);
        localUrl = input.files[0] ? URL.createObjectURL(input.files[0]) : null;
        const src = localUrl || preview.dataset.savedUrl;
        if (src) preview.src = src; else preview.removeAttribute('src');
        preview.parentElement.hidden = !src;
        const hint = document.getElementById('birthdayBackgroundEmptyHint');
        if (hint) hint.hidden = !!src;
    });
    const audience = document.getElementById('birthdayBackgroundAudience');
    if (audience) {
        const describe = () => {
            const descriptions = {
                off: 'Birthday backgrounds are off for everyone. The saved image and personal backgrounds are kept.',
                birthday_person: 'Only the birthday Technician sees this background: one day before, the birthday itself and two days after (4 days).',
                birthday_and_everyone: 'The birthday Technician sees this background for 4 days (−1, birthday, +1, +2). All other employees see it only on the birthday, with the birthday names above the page.',
            };
            document.getElementById('birthdayBackgroundSchedule').textContent = descriptions[audience.value];
        };
        audience.addEventListener('change', describe);
        describe();
    }
    const configNode = document.getElementById('birthdayBackgroundConfig');
    if (!configNode) return;
    const config = JSON.parse(configNode.textContent);
    const content = document.querySelector('.content');
    const banner = document.getElementById('birthdayBackgroundBanner');
    let state = config.state, busy = false, currentUrl = state.url;
    function clear() {
        document.body.classList.remove('has-birthday-background', 'has-birthday-banner');
        content.style.removeProperty('--birthday-background');
        banner.hidden = true; banner.textContent = '';
        currentUrl = null;
    }
    function expire() {
        if (state.active && Date.now() >= Date.parse(state.expires_at)) clear();
    }
    async function refresh() {
        expire();
        if (busy || document.hidden) return;
        busy = true;
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 15000);
        try {
            const response = await fetch(config.endpoint, { headers: { Accept: 'application/json' },
                credentials: 'same-origin', cache: 'no-store', signal: controller.signal, spinner: false });
            if (!response.ok || response.redirected) return;
            const next = await response.json();
            if (!next.active || Date.now() >= Date.parse(next.expires_at)) { state = next; clear(); return; }
            const url = new URL(next.url, location.origin);
            if (url.origin !== location.origin) return;
            if (next.url !== currentUrl) {
                const image = new Image(); image.src = url.href;
                let imageTimer;
                try {
                    await Promise.race([image.decode(), new Promise((_, reject) => { imageTimer = setTimeout(reject, 10000); })]);
                } finally { clearTimeout(imageTimer); }
            }
            // The birthday may have ended while the image was downloading.
            if (Date.now() >= Date.parse(next.expires_at)) { state = next; clear(); return; }
            state = next; currentUrl = next.url;
            content.style.setProperty('--birthday-background', `url("${url.href.replaceAll('"', '%22')}")`);
            document.body.classList.add('has-birthday-background');
            banner.textContent = next.caption || '';
            banner.hidden = !next.caption;
            document.body.classList.toggle('has-birthday-banner', !!next.caption);
        } catch (_) {
            // Keep the last valid image during a transient failure, but never beyond its birthday window.
            expire();
        } finally { clearTimeout(timeout); busy = false; }
    }
    setInterval(refresh, 60000);
    document.addEventListener('visibilitychange', refresh);
    window.addEventListener('focus', refresh);
    refresh();
})();
