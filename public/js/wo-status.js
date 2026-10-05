(() => {
    const form = document.getElementById('woStatusFilters');
    if (!form) return;
    const scroll = document.querySelector('.ws-scroll');
    const cards = document.getElementById('wsCards');
    const sentinel = document.getElementById('wsSentinel');
    const retry = document.getElementById('wsRetry');
    const total = document.getElementById('wsTotal');
    let next = sentinel.dataset.next || null;
    let loading = false, controller = null, generation = 0, timer = null, failed = null;
    const initial = JSON.parse(document.getElementById('wsInitialState').textContent);
    let pages = initial.pages || 1, lastWorkorder = initial.lastWorkorder || null;
    let restoring = true, dirty = false, saveTimer = null, pendingSave = null, saving = null;
    let remainingPopover = null, remainingTrigger = null, remainingAnchor = null;
    function closeRemaining(restoreFocus = false) {
        const trigger = remainingTrigger;
        remainingPopover?.remove();
        trigger?.setAttribute('aria-expanded', 'false');
        remainingPopover = remainingTrigger = null;
        remainingAnchor = null;
        if (restoreFocus && trigger?.isConnected) trigger.focus({preventScroll:true});
    }
    function openRemaining(button) {
        const same = remainingTrigger === button;
        closeRemaining();
        if (same) return;
        const template = document.getElementById(button.dataset.wsRemaining + '-template');
        if (!template) return;
        const popup = document.createElement('div');
        popup.className = 'ws-remaining-popover' + (button.hasAttribute('data-ws-forecast') ? ' ws-forecast-popover' : '');
        popup.id = button.dataset.wsRemaining;
        popup.setAttribute('role', 'dialog');
        popup.setAttribute('aria-label', button.getAttribute('aria-label'));
        popup.append(template.content.cloneNode(true));
        document.body.append(popup);
        remainingPopover = popup; remainingTrigger = button;
        const anchor = button.getBoundingClientRect();
        remainingAnchor = {left:anchor.left, top:anchor.top};
        const width = popup.offsetWidth, height = popup.offsetHeight;
        popup.style.left = Math.max(8, Math.min(anchor.right - width, innerWidth - width - 8)) + 'px';
        const top = anchor.bottom + 6 + height <= innerHeight - 8 ? anchor.bottom + 6 : anchor.top - height - 6;
        popup.style.top = Math.max(8, Math.min(top, innerHeight - height - 8)) + 'px';
        button.setAttribute('aria-expanded', 'true');
        popup.querySelector('[data-ws-remaining-close]').focus({preventScroll:true});
    }
    document.addEventListener('click', event => {
        if (event.target.closest('[data-ws-remaining-close]')) closeRemaining(true);
        else if (remainingPopover && !remainingPopover.contains(event.target) && !event.target.closest('[data-ws-remaining]')) closeRemaining();
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && remainingPopover) { event.preventDefault(); closeRemaining(true); }
    });
    document.addEventListener('scroll', event => {
        if (!remainingPopover || remainingPopover.contains(event.target)) return;
        const anchor = remainingTrigger?.getBoundingClientRect();
        if (!remainingTrigger?.isConnected || !anchor || Math.abs(anchor.left - remainingAnchor.left) > 1 || Math.abs(anchor.top - remainingAnchor.top) > 1) closeRemaining();
    }, true);
    window.addEventListener('resize', () => closeRemaining());
    function initCalendars() {
        window.initProjectDatePickers?.(cards);
        cards.querySelectorAll('.ws-ecd-input').forEach(input => {
            const picker = input._flatpickr;
            if (!picker || picker._wsAutoSave) return;
            picker._wsAutoSave = true;
            picker.calendarContainer.classList.add('ws-ecd-calendar');
            picker.config.onChange.push(() => saveCompletionDate(input));
            picker.config.onClose.push(() => saveCompletionDate(input));
        });
    }
    function destroyCalendars() {
        cards.querySelectorAll('.ws-ecd-input').forEach(input => input._flatpickr?.destroy());
    }
    const panelAnimations = new WeakMap();
    function setPanelOpen(button, open) {
        const detail = document.getElementById(button.dataset.wsDetail);
        if (!detail) return;
        const from = detail.hidden ? 0 : detail.getBoundingClientRect().height;
        panelAnimations.get(detail)?.cancel();
        panelAnimations.delete(detail);
        button.setAttribute('aria-expanded', String(open));
        detail.hidden = false;
        const to = open ? parseFloat(getComputedStyle(detail).height) : 0;
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || !detail.animate) {
            detail.hidden = !open;
            detail.style.overflow = '';
            return;
        }
        detail.style.overflow = 'hidden';
        const animation = detail.animate([{height: `${from}px`}, {height: `${to}px`}], {
            duration: 260, easing: 'cubic-bezier(.22,.61,.36,1)', fill: 'forwards',
        });
        panelAnimations.set(detail, animation);
        animation.onfinish = () => {
            if (panelAnimations.get(detail) !== animation) return;
            detail.hidden = !open;
            detail.style.overflow = '';
            animation.cancel();
            panelAnimations.delete(detail);
        };
    }
    function toggleAllPanels(identity) {
        const card = identity.closest('.ws-card');
        const buttons = [...card.querySelectorAll('[data-ws-detail]')];
        const open = buttons.some(button => button.getAttribute('aria-expanded') !== 'true');
        closeRemaining();
        buttons.forEach(button => {
            setPanelOpen(button, open);
        });
        lastWorkorder = card.dataset.workorder;
        remember();
    }

    function snapshot() {
        const detailScroll = {};
        const openPanels = [...cards.querySelectorAll('[data-ws-detail][aria-expanded="true"]')]
            .map(button => document.getElementById(button.dataset.wsDetail)).filter(Boolean);
        openPanels.forEach(element => {
            detailScroll[element.id] = {top: element.scrollTop, left: element.scrollLeft};
        });
        return {
            filters: Object.fromEntries(new FormData(form)), pages, lastWorkorder,
            open: openPanels.map(element => element.id),
            scroll: {top: scroll.scrollTop, left: scroll.scrollLeft}, detailScroll,
        };
    }
    function flushState() {
        clearTimeout(saveTimer);
        if (dirty) { pendingSave = snapshot(); dirty = false; }
        if (!saving && pendingSave) {
            saving = (async () => {
                while (pendingSave) {
                    const value = pendingSave;
                    pendingSave = null;
                    await window.UserUiSettings.set('wo-status', 'state', value, {keepalive: true});
                }
            })().finally(() => { saving = null; });
        }
        return saving || Promise.resolve();
    }
    function remember() {
        if (restoring) return;
        dirty = true;
        clearTimeout(saveTimer);
        saveTimer = setTimeout(flushState, 250);
    }

    function showLoading() {
        sentinel.innerHTML = 'Loading<span class="ws-loading-dots" aria-hidden="true"><span>.</span><span>.</span><span>.</span></span>';
    }

    function filterUrl() {
        const url = new URL(form.action);
        for (const [key, value] of new FormData(form)) if (String(value).trim()) url.searchParams.set(key, String(value).trim());
        return url.toString();
    }
    function nearEnd() {
        return sentinel.getBoundingClientRect().top <= scroll.getBoundingClientRect().bottom + 240;
    }
    function maybeLoad() {
        if (next && !loading && !failed && nearEnd()) load(next, false);
    }
    async function load(url, replace) {
        if (loading) return;
        closeRemaining();
        loading = true;
        const version = generation;
        controller = new AbortController();
        showLoading();
        retry.classList.add('d-none');
        scroll.setAttribute('aria-busy', 'true');
        try {
            const response = await fetch(url, {headers: {'Accept': 'application/json'}, signal: controller.signal});
            if (!response.ok) throw new Error('Unable to load workorders.');
            const data = await response.json();
            if (version !== generation) return;
            if (replace) { destroyCalendars(); cards.innerHTML = data.html; }
            else cards.insertAdjacentHTML('beforeend', data.html);
            initCalendars();
            pages = replace ? 1 : pages + 1;
            total.textContent = data.total;
            next = data.next;
            failed = null;
            sentinel.textContent = next ? '' : (data.total ? 'All matching workorders loaded' : '');
            if (replace) history.replaceState(null, '', url);
            remember();
        } catch (error) {
            if (version !== generation || error.name === 'AbortError') return;
            failed = {url, replace};
            sentinel.textContent = 'Unable to load workorders. Please retry.';
            retry.classList.remove('d-none');
        } finally {
            if (version === generation) {
                loading = false;
                scroll.setAttribute('aria-busy', 'false');
                requestAnimationFrame(maybeLoad);
            }
        }
    }
    function refresh(delay = 0) {
        clearTimeout(timer);
        generation++;
        controller?.abort();
        loading = false;
        failed = null;
        next = null;
        retry.classList.add('d-none');
        destroyCalendars();
        cards.replaceChildren();
        pages = 1;
        lastWorkorder = null;
        total.textContent = '…';
        showLoading();
        scroll.scrollTop = 0;
        remember();
        timer = setTimeout(() => load(filterUrl(), true), delay);
    }
    window.jQuery('#wsCustomer').select2({
        width: '100%', minimumResultsForSearch: 0, placeholder: 'All customers', allowClear: true
    }).on('select2:open', () => {
        window.jQuery('.select2-container--open .select2-dropdown').addClass('ws-customer-dropdown');
    }).on('change', () => refresh());
    document.getElementById('wsWorkorder').addEventListener('input', () => refresh(300));
    const statusBoxes = [...form.querySelectorAll('[data-ws-status]')];
    statusBoxes.forEach(box => box.addEventListener('change', () => {
        document.getElementById('wsState').value = statusBoxes.filter(item => item.checked).map(item => item.dataset.wsStatus).join(',') || 'all';
        refresh();
    }));
    form.addEventListener('submit', event => { event.preventDefault(); refresh(); });
    cards.addEventListener('click', event => {
        const calendarButton = event.target.closest('[data-ws-calendar]');
        if (calendarButton) {
            initCalendars();
            const input = calendarButton.closest('form').querySelector('.ws-ecd-input');
            if (input._flatpickr) input._flatpickr.open();
            else calendarButton.closest('form').querySelector('.ws-ecd-message').textContent = 'Calendar unavailable. Enter dd/Mmm/yyyy.';
            return;
        }
        const remainingButton = event.target.closest('[data-ws-remaining]');
        if (remainingButton) {
            openRemaining(remainingButton);
            return;
        }
        const identity = event.target.closest('.ws-identity');
        if (identity) {
            if (!event.target.closest('a,button,input,select,textarea,label,form') && !window.getSelection()?.toString()) toggleAllPanels(identity);
            return;
        }
        const button = event.target.closest('[data-ws-detail]');
        if (!button) return;
        const detail = document.getElementById(button.dataset.wsDetail);
        if (!detail) return;
        const open = button.getAttribute('aria-expanded') !== 'true';
        closeRemaining();
        setPanelOpen(button, open);
        lastWorkorder = button.closest('.ws-card').dataset.workorder;
        remember();
    });
    cards.addEventListener('keydown', event => {
        if (event.target.matches('.ws-identity') && ['Enter',' '].includes(event.key)) {
            event.preventDefault(); toggleAllPanels(event.target);
        }
    });
    async function saveCompletionDate(input) {
        const editor = input.closest('.ws-ecd-form');
        if (!editor || editor.dataset.saving === 'true') return;
        const message = editor.querySelector('.ws-ecd-message');
        const value = input.value.trim();
        if (!value) {
            input.value = input.dataset.saved || '';
            input._flatpickr?.setDate(input.value, false, 'd/M/Y');
            return;
        }
        if (value === input.dataset.saved) return;
        editor.dataset.saving = 'true';
        const button = editor.querySelector('[data-ws-calendar]');
        button.disabled = true;
        try {
            input.disabled = true;
            message.textContent = 'Saving…';
            const response = await fetch(editor.action, {
                method:'PATCH', keepalive:true, headers:{'Accept':'application/json', 'Content-Type':'application/json', 'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},
                body:JSON.stringify({expected_completion_date:value || null})
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.errors?.expected_completion_date?.[0] || data.message || 'Could not save ECD.');
            input.value = input.dataset.saved = data.date || '';
            input._flatpickr?.setDate(data.date || '', false, 'd/M/Y');
            input.classList.remove('ws-esd-overdue','ws-esd-soon','ws-esd-future');
            if (data.date) {
                const [day, month, year] = data.date.split('/');
                const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                const date = new Date(Number(year),months.indexOf(month),Number(day));
                const today = new Date(); today.setHours(0,0,0,0);
                const soon = new Date(today); soon.setDate(soon.getDate()+14);
                input.classList.add(date<today ? 'ws-esd-overdue' : date<=soon ? 'ws-esd-soon' : 'ws-esd-future');
            }
            message.textContent = 'Saved';
        } catch (error) {
            input.value = input.dataset.saved || '';
            input._flatpickr?.setDate(input.value, false, 'd/M/Y');
            message.textContent = error.message || 'Could not save ECD.';
        } finally {
            input.disabled = false;
            button.disabled = false;
            delete editor.dataset.saving;
        }
    }
    cards.addEventListener('submit', event => {
        const editor = event.target.closest('.ws-ecd-form');
        if (!editor) return;
        event.preventDefault();
        saveCompletionDate(editor.querySelector('.ws-ecd-input'));
    });
    cards.addEventListener('change', event => {
        if (event.target.matches('.ws-ecd-input') && !event.target._flatpickr) saveCompletionDate(event.target);
    });
    cards.addEventListener('scroll', remember, true);
    // Wait for the latest write before navigating, including an immediately opened WO.
    document.addEventListener('click', async event => {
        const link = event.target.closest('a[href]');
        if (!link || event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || link.target === '_blank') return;
        if (link.classList.contains('ws-number')) {
            lastWorkorder = link.closest('.ws-card').dataset.workorder;
            remember();
        }
        const url = new URL(link.href, location.href);
        if (url.origin !== location.origin || url.pathname === location.pathname && url.hash) return;
        if (!dirty && !saving && !pendingSave) return;
        event.preventDefault();
        await flushState();
        location.assign(link.href);
    });
    window.addEventListener('pagehide', flushState);
    document.addEventListener('visibilitychange', () => { if (document.hidden) flushState(); });
    retry.addEventListener('click', () => { if (failed) load(failed.url, failed.replace); });
    new IntersectionObserver(entries => {
        if (entries.some(entry => entry.isIntersecting)) maybeLoad();
    }, {root: scroll, rootMargin: '240px'}).observe(sentinel);
    scroll.addEventListener('scroll', () => { maybeLoad(); remember(); }, {passive:true});
    sentinel.textContent = next ? '' : (cards.querySelector('.ws-card') ? 'All matching workorders loaded' : '');
    Object.entries(initial.detailScroll || {}).forEach(([id, position]) => {
        const detail = document.getElementById(id);
        if (detail && cards.contains(detail)) { detail.scrollTop = position.top || 0; detail.scrollLeft = position.left || 0; }
    });
    scroll.scrollTop = initial.scroll?.top || 0;
    scroll.scrollLeft = initial.scroll?.left || 0;
    initCalendars();
    document.querySelector('.wo-status').classList.remove('ws-restoring');
    requestAnimationFrame(() => { restoring = false; });
    requestAnimationFrame(maybeLoad);
})();
