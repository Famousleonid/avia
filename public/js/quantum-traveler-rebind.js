(function () {
    'use strict';
    const headers = { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
    async function request(url, options = {}) {
        const response = await fetch(url, { credentials: 'same-origin', headers, ...options });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'Quantum Rebind check failed.');
        return data;
    }
    function notify(message, type) {
        // The shared notification renderer accepts HTML; source labels and errors are plain text.
        const text = document.createElement('span');
        text.textContent = message;
        window.showNotification(text.innerHTML, type, type === 'error' ? 10000 : 5000);
    }
    async function review(url, intro = '') {
        if (typeof window.confirmDialog !== 'function') {
            throw new Error('Confirmation dialog is unavailable. Rebind was not performed.');
        }
        const preview = await request(url);
        const value = (key, val) => {
            if (val === null || val === '') return 'empty';
            if (key === 'vendor_id') return preview.vendors?.[val] || String(val);
            if (key === 'date_start' || key === 'date_finish') {
                const match = String(val).match(/^(\d{4})-(\d{2})-(\d{2})/);
                if (match) return `${match[3]}/${['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'][Number(match[2])-1]}/${match[1]}`;
            }
            return String(val);
        };
        const labels = { repair_order: 'RO', vendor_id: 'Vendor', date_start: 'Sent', date_finish: 'Returned',
            date_start_user: 'Sent by', date_finish_user: 'Returned by', date_start_user_id: 'Sent user ID', date_finish_user_id: 'Returned user ID' };
        const describe = row => `${row.process}: ` + Object.keys(row.after)
            .filter(key => String(row.before[key] ?? '') !== String(row.after[key] ?? ''))
            .map(key => `${labels[key] || key}: ${value(key, row.before[key])} → ${value(key, row.after[key])}`).join('; ');
        const message = `${intro}${preview.ro} / ${preview.wo} / ${preview.pn} / ${preview.ref}\n\nRelease previous processes:\n${preview.release.map(describe).join('\n')}\n\nApply to current Traveler:\n${preview.apply.map(describe).join('\n')}\n\nApply these changes now? This action is recorded in the activity log.`;
        const confirmation = window.confirmDialog({ title: 'Rebind Quantum Traveler?', message,
            okText: 'Rebind', cancelText: 'Later', danger: true });
        const body = document.getElementById('globalConfirmBody');
        const oldStyle = body?.getAttribute('style');
        if (body) { body.style.whiteSpace = 'pre-line'; body.style.maxHeight = '60vh'; body.style.overflowY = 'auto'; }
        let confirmed;
        try { confirmed = await confirmation; }
        finally {
            if (body) {
                if (oldStyle === null) body.removeAttribute('style'); else body.setAttribute('style', oldStyle);
            }
        }
        if (!confirmed) return false;
        await request(url, { method: 'POST', headers: { ...headers, 'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
            body: JSON.stringify({ token: preview.token }) });
        return true;
    }
    async function afterSave(url) {
        if (!url) return;
        try {
            const data = await request(url);
            for (const item of data.suggestions) {
                if (!item.url) {
                    notify(`Traveler saved. ${item.ro}: ${item.message} Review this row in Vendor Tracking → Quantum.`, 'error');
                    continue;
                }
                // Fetch a fresh preview for each row: another Rebind can affect shared members.
                try {
                    const applied = await review(item.url, 'Traveler saved. Its previous Quantum RO binding needs review.\n\n');
                    notify(applied ? `${item.ro}: Quantum RO rebound to the current Traveler.`
                        : `Traveler saved. ${item.ro} was not rebound. Review it in Vendor Tracking → Quantum.`, applied ? 'success' : 'warning');
                } catch (error) {
                    notify(`Traveler saved. ${item.ro}: ${error.message} Review it in Vendor Tracking → Quantum.`, 'error');
                }
            }
        } catch (error) {
            notify(`Traveler saved, but the Quantum Rebind check failed. ${error.message} Review Vendor Tracking → Quantum.`, 'error');
        }
    }
    window.QuantumTravelerRebind = { review, afterSave };
})();
