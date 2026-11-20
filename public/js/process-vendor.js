(() => {
    'use strict';
    const root = document.getElementById('process-vendor');
    if (!root) return;
    document.title = 'Process Vendor · AVIA';
    const config = JSON.parse(document.getElementById('pv-data').textContent);
    const $ = id => document.getElementById(id);
    const groupKey = row => `${row.workorder_id}|${row.process_group}`;
    let rows = config.rows, groups = new Map(), busy = false, fetching = false, process = '', timer;
    const selected = new Set();
    let subProcess = '', subtabRevision = '';
    const searching = () => !!$('pv-search').value.trim();
    const selectedProcess = () => searching() ? null : (subProcess || null);
    const scopedRows = items => selectedProcess() ? items.filter(row => row.process_name === subProcess && row.process_group === process) : items;
    let settingsRevision = 0;
    const refreshInterval = 300000;
    let manualRefreshing = false, awaitingManualRefresh = false;
    let nextCheckAt = Date.now() + refreshInterval, refreshFailed = false;
    let enabled = config.preferences.enabled;
    const initialFilters = config.filters || {};
    process = enabled.includes(initialFilters.process) ? initialFilters.process : (enabled[0] || '');
    subProcess = typeof initialFilters.subProcess === 'string' ? initialFilters.subProcess : '';
    $('pv-search').value = typeof initialFilters.query === 'string' ? initialFilters.query : '';
    const settingsDialog = $('pv-settings');
    const vendorFields = {address:'pv-address',contact_name:'pv-contact',phone:'pv-phone',email:'pv-email',vendor_number:'pv-vendor-number',ship_via:'pv-via',terms:'pv-terms'};
    const vendorDrafts = new Map(); let selectedVendorId = '';
    $('pv-vendor').addEventListener('change', () => {
        if (selectedVendorId) vendorDrafts.set(selectedVendorId, Object.fromEntries(Object.entries(vendorFields).map(([field,id]) => [field,$(id).value])));
        selectedVendorId = $('pv-vendor').value;
        const vendor = vendorDrafts.get(selectedVendorId) || config.vendors.find(vendor => String(vendor.id) === selectedVendorId) || {};
        Object.entries(vendorFields).forEach(([field,id]) => $(id).value = vendor[field] || '');
    });
    const notify = (message, type = 'success') => window.showNotification?.(message, type, type === 'error' ? 10000 : 5000);
    const selection = () => scopedRows([...selected].flatMap(key => groups.get(key) || []));
    const payload = items => items.map(({ key, workorder_id, fingerprint }) => ({ key, workorder_id, fingerprint }));
    const visiblePicks = () => [...root.querySelectorAll('.pv-pick:not(:disabled)')].filter(input => !input.closest('.pv-workorder').hidden && !input.closest('.pv-process').hidden);
    const woCount = items => new Set(items.map(row => row.workorder_id)).size;
    const quantity = items => items.reduce((sum, row) => sum + row.qty, 0);
    const groupRevision = items => JSON.stringify((items || []).map(row => [row.key, row.fingerprint]).sort());
    function regroup() {
        groups = new Map();
        rows.forEach(row => { const key = groupKey(row); if (!groups.has(key)) groups.set(key, []); groups.get(key).push(row); });
    }
    function renderSelection() {
        const items = selection();
        $('pv-selected-count').textContent = woCount(items);
        $('pv-check').disabled = busy || !items.length;
        $('pv-uncheck').disabled = busy || !items.some(row => row.checked);
        $('pv-create').disabled = busy || !items.length || items.some(row => !row.checked);
        $('pv-clear').disabled = busy || !items.length;
        $('pv-settings-open').disabled = busy;
        root.querySelectorAll('.pv-workorder').forEach(wo => {
            const input = wo.querySelector('.pv-pick'), parts = scopedRows(groups.get(wo.dataset.groupKey) || []);
            input.checked = selected.has(wo.dataset.groupKey);
            input.disabled = busy || !parts.length;
            wo.classList.toggle('is-selected', input.checked);
            const status = wo.querySelector('.pv-wo-status');
            const checked = parts.length && parts.every(row => row.checked);
            status.textContent = checked ? 'At shipping' : parts.some(row => row.checked) ? 'Check all parts' : 'Not checked';
            status.classList.toggle('checked', !!checked);
        });
        const visible = visiblePicks();
        $('pv-select-visible').disabled = busy || !visible.length;
        $('pv-select-visible').checked = visible.length > 0 && visible.every(input => input.checked);
        $('pv-select-visible').indeterminate = visible.some(input => input.checked) && !visible.every(input => input.checked);
    }
    function renderSubtabs() {
        const group = config.preferences.groups.find(group => group.name === process && group.enabled !== false);
        const members = group ? group.members : [process];
        const names = [...new Set([
            ...config.preferences.options.filter(option => members.includes(option.name)).flatMap(option => option.processes || [option.name]),
            ...rows.filter(row => row.process_group === process).map(row => row.process_name)
        ])].sort((a,b) => a.localeCompare(b));
        const show = !!group || names.length > 1;
        if (subProcess && (!show || !names.includes(subProcess))) { subProcess = ''; selected.clear(); }
        const nav = $('pv-subtabs'); nav.hidden = !show || searching();
        const entries = ['', ...names].map(name => [name, woCount(rows.filter(row => row.process_group === process && (!name || row.process_name === name)))]);
        const revision = JSON.stringify([process, entries]);
        if (revision !== subtabRevision) {
            nav.replaceChildren(); subtabRevision = revision;
            entries.forEach(([name,count]) => {
                const tab = document.createElement('button'); tab.type = 'button'; tab.className = 'pv-subtab'; tab.dataset.subprocess = name;
                tab.textContent = `${name || 'All'} (${count})`; nav.append(tab);
            });
        }
        nav.querySelectorAll('button').forEach(tab => {
            const active = tab.dataset.subprocess === subProcess;
            tab.classList.toggle('active', active); tab.setAttribute('aria-pressed', String(active)); tab.disabled = busy;
        });
    }
    function filter(save = true) {
        renderSubtabs();
        const rawQuery = $('pv-search').value.trim().toLowerCase();
        const query = rawQuery.replace(/^(?:wo\s*#?\s*|w)(\d+)$/, '$1'), shown = [];
        $('pv-search-scope').hidden = !query;
        root.querySelectorAll('[data-process-group]').forEach(section => {
            const sectionRows = [];
            section.querySelectorAll('.pv-workorder').forEach(wo => {
                const parts = scopedRows(groups.get(wo.dataset.groupKey) || []), keys = new Set(parts.map(row => row.key));
                wo.querySelectorAll('tr[data-key]').forEach(tr => { tr.hidden = !keys.has(tr.dataset.key); });
                const matches = parts.length > 0 && (!!query || (!!process && section.dataset.processGroup === process)) && (!query || [...wo.querySelectorAll('tr[data-search]')].some(tr => !tr.hidden && tr.dataset.search.includes(query)));
                wo.hidden = !matches;
                const processNames = wo.querySelector('.pv-wo-processes');
                processNames.hidden = !query; processNames.textContent = [...new Set(parts.map(row => row.process_name))].join(' · ');
                const partCount = wo.querySelector('.pv-part-count');
                partCount.replaceChildren(document.createTextNode(`${quantity(parts)} parts `));
                const lines = document.createElement('small'); lines.textContent = `· ${parts.length} lines`; partCount.append(lines);
                if (matches) { sectionRows.push(...parts); shown.push(...parts); }
            });
            section.hidden = sectionRows.length === 0;
            section.querySelector('.pv-process-title').textContent = !query && subProcess && section.dataset.processGroup === process ? `${process} / ${subProcess}` : section.dataset.processGroup;
            section.querySelector('.pv-process-count').textContent = `${woCount(sectionRows)} WO · ${quantity(sectionRows)} parts`;
        });
        root.querySelectorAll('[data-process]').forEach(tab => {
            const active = !query && tab.dataset.process === process;
            tab.classList.toggle('active', active); tab.setAttribute('aria-pressed', String(active));
        });
        $('pv-empty-message').textContent = query ? 'No ready workorders match this search in any process.' : enabled.length ? 'No workorders match this process.' : 'Choose processes in Settings to show their tabs.';
        $('pv-visible-count').textContent = quantity(shown);
        $('pv-wo-count').textContent = woCount(shown);
        $('pv-empty').hidden = !config.cache.ready || shown.length > 0;
        renderSelection();
        if (save) { clearTimeout(timer); timer = setTimeout(() => window.UserUiSettings?.set('process-vendor.index', 'filters', { process, subProcess, query }), 300); }
    }
    function cacheStatus(meta) {
        config.cache = meta;
        renderRefreshStatus();
    }
    function renderRefreshStatus() {
        const duration = seconds => seconds < 60 ? `${seconds} sec` : `${Math.floor(seconds / 60)} min ${seconds % 60} sec`;
        const meta = config.cache, stamp = Date.parse(meta.updated_at || '');
        const age = Number.isFinite(stamp) ? Math.max(0, Math.floor((Date.now() - stamp) / 1000)) : null;
        const remaining = Math.max(0, Math.ceil((nextCheckAt - Date.now()) / 1000));
        const last = meta.ready && age !== null ? `Updated ${duration(age)} ago` : 'Waiting for the first background update';
        const next = manualRefreshing ? 'Updating queue now…' : awaitingManualRefresh ? 'Waiting for the current queue update…' : fetching ? 'Checking for updates…' : remaining === 0 && busy ? 'Next check after the current action' : `Next check in ${duration(remaining)}`;
        const warning = refreshFailed ? ' · Connection interrupted' : age !== null && age > 660 ? ' · Background update delayed' : '';
        $('pv-load-status').textContent = `${last} · ${next}${warning}`;
        $('pv-load-status').classList.toggle('is-stale', refreshFailed || age > 660);
        const spinning = manualRefreshing || (awaitingManualRefresh && !refreshFailed);
        $('pv-load-status').classList.toggle('is-refreshing', spinning);
        $('pv-load-status').setAttribute('aria-busy', String(spinning));
    }
    function applyQueue(data) {
        data.rows = data.search_rows || data.rows;
        cacheStatus(data.cache);
        const preferencesChanged = JSON.stringify(config.preferences) !== JSON.stringify(data.preferences);
        enabled = data.preferences.enabled; config.preferences = data.preferences;
        if (JSON.stringify(rows) === JSON.stringify(data.rows) && !preferencesChanged) { filter(false); return; }
        const old = groups, expanded = new Set([...root.querySelectorAll('.pv-workorder[open]')].map(wo => wo.dataset.groupKey));
        rows = data.rows; regroup();
        let changed = false;
        [...selected].forEach(key => {
            if (!groups.has(key) || groupRevision(old.get(key)) !== groupRevision(groups.get(key))) { selected.delete(key); changed = true; }
        });
        $('pv-groups').innerHTML = data.html;
        root.querySelectorAll('.pv-workorder').forEach(wo => { wo.open = expanded.has(wo.dataset.groupKey); });
        const tabs = root.querySelector('.pv-tabs'); tabs.replaceChildren();
        const names = enabled;
        names.forEach(name => {
            const button = document.createElement('button'); button.type = 'button'; button.className = 'pv-tab'; button.dataset.process = name;
            button.append(document.createTextNode(name));
            const count = document.createElement('span'); count.textContent = `(${woCount(rows.filter(row => row.process_group === name))})`;
            button.append(count); tabs.append(button);
        });
        if (!names.includes(process)) process = names[0] || '';
        if (changed) notify('The queue changed. Affected workorders were deselected; review their current parts.', 'info');
        filter(false);
    }
    async function loadQueue(manual = false) {
        if (fetching || busy) return;
        fetching = true; manualRefreshing = manual; if (manual) { busy = true; renderSelection(); } $('pv-refresh').disabled = true;
        renderRefreshStatus();
        const revision = settingsRevision;
        const controller = new AbortController(), timeout = setTimeout(() => controller.abort(), manual ? 310000 : 15000);
        try {
            const response = await fetch(manual ? config.refreshUrl : config.queueUrl, {method:manual ? 'POST' : 'GET',headers:{Accept:'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content},signal:controller.signal,spinner:false});
            if (!response.ok) throw new Error('Unable to update the saved queue.');
            const data = await response.json();
            refreshFailed = false;
            if (manual) awaitingManualRefresh = !!data.refresh?.busy;
            else if (!data.cache.refreshing) awaitingManualRefresh = false;
            if ((!busy || manual) && revision === settingsRevision) applyQueue(data);
            if (manual) notify(awaitingManualRefresh ? 'A queue update is already running. The result will appear when it finishes.' : 'Queue updated.');
        } catch (error) {
            refreshFailed = true;
            if (manual) { awaitingManualRefresh = true; notify('Unable to finish the update request. The previous queue is retained; checking for a background result.', 'error'); }
        } finally {
            clearTimeout(timeout); fetching = false; manualRefreshing = false; if (manual) busy = false; $('pv-refresh').disabled = false;
            nextCheckAt = Date.now() + (awaitingManualRefresh ? 5000 : refreshInterval); renderRefreshStatus(); renderSelection();
        }
    }
    async function confirmAction(options) {
        if (typeof window.confirmDialog !== 'function') { notify('The confirmation dialog is unavailable. Please reload the page.', 'error'); return false; }
        return (await window.confirmDialog(options)) === true;
    }
    async function post(url, body) {
        const response = await fetch(url, {method:'POST',headers:{'Content-Type':'application/json',Accept:'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content},body:JSON.stringify(body)});
        const data = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(Object.values(data.errors || {}).flat().join(' ') || data.message || 'Unable to save. Refresh the page and try again.');
        return data;
    }
    const ordersDialog = $('pv-orders');
    let ordersPage = 1, ordersRequest, ordersFilter = {q:'',status:''};
    async function loadOrders(page = 1) {
        ordersRequest?.abort();
        const controller = new AbortController(); ordersRequest = controller;
        const timeout = setTimeout(() => controller.abort(), 15000);
        $('pv-orders-list').replaceChildren();
        $('pv-orders-message').textContent = 'Loading Repair Orders…';
        $('pv-orders-page').textContent = '';
        $('pv-orders-prev').disabled = $('pv-orders-next').disabled = true;
        try {
            const url = new URL(config.ordersUrl, location.origin);
            Object.entries({...ordersFilter,page}).forEach(([key,value]) => url.searchParams.set(key,value));
            const response = await fetch(url, {headers:{Accept:'application/json'},signal:controller.signal,spinner:false});
            if (!response.ok) throw new Error('Unable to load Repair Orders. Try Search again.');
            const data = await response.json();
            if (ordersRequest !== controller) return;
            ordersPage = data.page;
            data.orders.forEach(order => {
                const link = document.createElement('a'); link.className = 'pv-order-link'; link.href = order.url;
                [['strong',order.number,''],['small',order.status,''],['span',order.vendor,'pv-order-vendor'],['small',order.date,''],['small',`WO: ${order.workorders.join(', ') || '—'}`,'pv-order-wo']].forEach(([tag,text,className]) => {
                    const node = document.createElement(tag); node.textContent = text; node.className = className; link.append(node);
                });
                $('pv-orders-list').append(link);
            });
            $('pv-orders-message').textContent = data.total ? `${data.total} Repair Orders · Prepared does not mean dispatched.` : 'No Repair Orders found.';
            $('pv-orders-page').textContent = `Page ${data.page} of ${data.last_page}`;
            $('pv-orders-prev').disabled = data.page <= 1;
            $('pv-orders-next').disabled = data.page >= data.last_page;
        } catch (error) {
            if (ordersRequest !== controller || !ordersDialog.open) return;
            $('pv-orders-message').textContent = 'Unable to load Repair Orders. Try Search again.';
            notify('Unable to load Repair Orders. Try Search again.', 'error');
        } finally { clearTimeout(timeout); }
    }
    $('pv-orders-open').addEventListener('click', () => { ordersDialog.showModal(); loadOrders(1); });
    $('pv-orders-close').addEventListener('click', () => ordersDialog.close());
    ordersDialog.addEventListener('close', () => { ordersRequest?.abort(); ordersRequest = null; });
    function searchOrders() {
        ordersFilter = {q:$('pv-orders-search').value.trim(),status:$('pv-orders-status').value};
        loadOrders(1);
    }
    $('pv-orders-search-form').addEventListener('submit', event => { event.preventDefault(); searchOrders(); });
    $('pv-orders-status').addEventListener('change', searchOrders);
    $('pv-orders-prev').addEventListener('click', () => loadOrders(ordersPage - 1));
    $('pv-orders-next').addEventListener('click', () => loadOrders(ordersPage + 1));
    async function check(checked) {
        if (busy || !selected.size) return;
        const items = selection(); busy = true; renderSelection();
        try {
            if (!await confirmAction({title:checked ? 'Confirm workorders at Shipping' : 'Clear shipping confirmation',message:`${checked ? 'Confirm the presence of all' : 'Clear confirmation for all'} ${quantity(items)} parts in ${woCount(items)} selected workorders (${selected.size} process groups).`,okText:checked ? 'Confirm at shipping' : 'Clear confirmation'})) return;
            await post(config.checkUrl, {items:payload(items),checked,selection_process:selectedProcess(),search_all:searching()});
            items.forEach(row => { row.checked = checked; });
            root.querySelectorAll('tr[data-key]').forEach(tr => {
                const row = items.find(item => item.key === tr.dataset.key); if (!row) return;
                const status = tr.querySelector('.pv-status'); status.textContent = checked ? 'At shipping' : 'Not checked'; status.classList.toggle('checked', checked); status.removeAttribute('title');
            });
            notify(checked ? 'Workorders confirmed at Shipping.' : 'Shipping confirmation cleared.');
        } catch (error) { notify(error.message, 'error'); }
        finally { busy = false; renderSelection(); }
    }
    root.addEventListener('change', event => {
        if (!event.target.matches('.pv-pick') || busy) return;
        const key = event.target.closest('.pv-workorder').dataset.groupKey;
        event.target.checked ? selected.add(key) : selected.delete(key); renderSelection();
    });
    $('pv-select-visible').addEventListener('change', event => {
        if (busy) return;
        visiblePicks().forEach(input => { const key = input.closest('.pv-workorder').dataset.groupKey; event.target.checked ? selected.add(key) : selected.delete(key); }); renderSelection();
    });
    $('pv-clear').addEventListener('click', () => { selected.clear(); renderSelection(); });
    $('pv-selection-jump').addEventListener('click', () => {
        const button = $('pv-selection-jump'), back = button.dataset.back === 'true';
        root.querySelector(back ? '.pv-queue' : '.pv-selection').scrollIntoView({block:'start'});
        button.dataset.back = String(!back); button.textContent = back ? 'Shipping selection' : 'Back to queue';
    });
    $('pv-check').addEventListener('click', () => check(true)); $('pv-uncheck').addEventListener('click', () => check(false));
    $('pv-refresh').addEventListener('click', () => loadQueue(true));
    $('pv-search').addEventListener('input', () => { selected.clear(); filter(); });
    root.querySelector('.pv-tabs').addEventListener('click', event => { const tab = event.target.closest('[data-process]'); if (tab && !busy) { if (searching()) { $('pv-search').value = ''; selected.clear(); } if (process !== tab.dataset.process) { selected.clear(); subProcess = ''; } process = tab.dataset.process; filter(); } });
    $('pv-subtabs').addEventListener('click', event => {
        const tab = event.target.closest('[data-subprocess]'); if (!tab || busy) return;
        if (subProcess !== tab.dataset.subprocess) selected.clear();
        subProcess = tab.dataset.subprocess; filter();
    });
    $('pv-order-form').addEventListener('submit', async event => {
        event.preventDefault(); if (busy || !selected.size || selection().some(row => !row.checked)) return;
        const items = selection(), body = {items:payload(items),selection_process:selectedProcess(),search_all:searching(),vendor_id:$('pv-vendor').value,vendor_address:$('pv-address').value,vendor_contact_name:$('pv-contact').value,vendor_phone:$('pv-phone').value,vendor_email:$('pv-email').value,vendor_number:$('pv-vendor-number').value,ship_via:$('pv-via').value,terms:$('pv-terms').value,instructions:$('pv-instructions').value};
        busy = true; renderSelection();
        try {
            const vendor = $('pv-vendor').selectedOptions[0].textContent;
            if (!await confirmAction({title:'Prepare Repair Order',message:`Create an AVIA Repair Order for ${vendor}: ${woCount(items)} workorders, ${quantity(items)} parts? All listed parts in the selected process groups will be reserved.`,okText:'Create Repair Order'})) return;
            const result = await post(config.createUrl, body); window.location.assign(result.url);
        } catch (error) { notify(error.message, 'error'); }
        finally { busy = false; renderSelection(); }
    });
    let draftGroups = [], draftVisible = new Set(), assignments = new Map(), draftGroupSequence = 0;
    const groupSelection = new Set(), expandedGroups = new Set(), groupDialog = $('pv-group-dialog');
    let renameGroupId = '';
    const groupMembers = id => [...assignments].filter(([,groupId]) => groupId === id).map(([member]) => member);
    function renderGroupSelection() {
        $('pv-group-add').disabled = groupSelection.size === 0;
        $('pv-group-clear').hidden = groupSelection.size === 0;
        $('pv-group-selection').textContent = groupSelection.size ? `${groupSelection.size} selected` : 'Select processes on the right';
    }
    function processSetting(option) {
        const row = document.createElement('div'); row.className = 'pv-setting-row';
        const assigned = draftGroups.find(group => group.id === assignments.get(option.name));
        const label = document.createElement('label'), input = document.createElement('input'); input.type = 'checkbox'; input.name = 'processes'; input.value = option.name;
        input.disabled = !!assigned?.enabled; input.checked = !input.disabled && draftVisible.has(option.name); input.setAttribute('aria-label', `Show ${option.name} separate tab`);
        if (input.disabled) label.title = `Shown in ${assigned.name}. Turn off the group tab to show separate tabs.`;
        const text = document.createElement('span'), title = document.createElement('strong'); title.textContent = option.name; text.append(title);
        if (option.members !== option.name) { const small = document.createElement('small'); small.textContent = option.members; text.append(small); }
        label.append(input, text);
        const memberLabel = document.createElement('label'); memberLabel.className = 'pv-member-check';
        const member = document.createElement('input'); member.type = 'checkbox'; member.dataset.member = option.name; member.checked = groupSelection.has(option.name); member.setAttribute('aria-label', `Select ${option.name} for grouping`);
        memberLabel.append(member); row.append(label, memberLabel); return row;
    }
    function renderSettings() {
        const list = settingsDialog.querySelector('.pv-settings-list'), top = list.scrollTop;
        list.replaceChildren();
        const entries = [...draftGroups.map(group => ({name:group.name,group})),
            ...config.preferences.options.filter(option => !assignments.has(option.name)).map(option => ({name:option.name,option}))];
        entries.sort((a,b) => a.name.localeCompare(b.name, undefined, {sensitivity:'base'}));
        entries.forEach(entry => {
            if (entry.option) { list.append(processSetting(entry.option)); return; }
            const group = entry.group, members = groupMembers(group.id);
            const section = document.createElement('section'); section.className = 'pv-setting-group'; section.dataset.groupId = group.id;
            const header = document.createElement('div'); header.className = 'pv-group-heading';
            const visible = document.createElement('input'); visible.type = 'checkbox'; visible.checked = group.enabled; visible.dataset.groupVisible = group.id; visible.setAttribute('aria-label', `Show ${group.name} group tab`);
            const toggle = document.createElement('button'); toggle.type = 'button'; toggle.className = 'pv-group-toggle'; toggle.dataset.toggleGroup = group.id;
            toggle.setAttribute('aria-expanded', String(expandedGroups.has(group.id))); toggle.setAttribute('aria-controls', `pv-members-${group.id}`);
            const title = document.createElement('strong'); title.textContent = group.name;
            const badge = document.createElement('small'); badge.className = 'pv-group-badge'; badge.textContent = 'group';
            const count = document.createElement('small'); count.textContent = `(${members.length})`;
            toggle.append(title, badge, count); header.append(visible, toggle);
            const content = document.createElement('div'); content.id = `pv-members-${group.id}`; content.className = 'pv-group-members'; content.hidden = !expandedGroups.has(group.id);
            const actions = document.createElement('div'); actions.className = 'pv-group-actions';
            const rename = document.createElement('button'); rename.type = 'button'; rename.className = 'btn btn-link'; rename.dataset.renameGroup = group.id; rename.textContent = 'Rename'; rename.setAttribute('aria-label', `Rename ${group.name} group`);
            const remove = document.createElement('button'); remove.type = 'button'; remove.className = 'btn btn-link'; remove.dataset.removeGroup = group.id; remove.textContent = 'Ungroup'; remove.setAttribute('aria-label', `Ungroup ${group.name}`);
            actions.append(rename, remove); content.append(actions);
            config.preferences.options.filter(option => members.includes(option.name)).forEach(option => content.append(processSetting(option)));
            section.append(header, content); list.append(section);
        });
        list.scrollTop = top; renderGroupSelection();
    }
    $('pv-settings-open').addEventListener('click', () => {
        draftVisible = new Set(config.preferences.visible);
        draftGroups = config.preferences.groups.map((group, index) => ({id:`g${index}`,name:group.name,enabled:group.enabled !== false})); assignments = new Map();
        config.preferences.groups.forEach((group, index) => group.members.forEach(member => assignments.set(member, `g${index}`)));
        groupSelection.clear(); expandedGroups.clear(); renderSettings(); settingsDialog.showModal();
    });
    $('pv-settings-close').addEventListener('click', () => settingsDialog.close());
    function askGroupName(group = null) {
        renameGroupId = group?.id || '';
        $('pv-group-title').textContent = group ? 'Rename group' : 'Add to group';
        $('pv-group-submit').textContent = group ? 'Rename' : 'Add';
        $('pv-group-help').textContent = group ? 'Enter a new name for this group.' : 'Enter a new name or an existing group name.';
        $('pv-group-name').value = group?.name || ''; $('pv-group-error').hidden = true;
        groupDialog.showModal(); $('pv-group-name').focus(); $('pv-group-name').select();
    }
    $('pv-group-add').addEventListener('click', () => { if (groupSelection.size) askGroupName(); });
    $('pv-group-clear').addEventListener('click', () => { groupSelection.clear(); renderSettings(); });
    ['pv-group-close','pv-group-cancel'].forEach(id => $(id).addEventListener('click', () => groupDialog.close()));
    $('pv-group-name').addEventListener('input', () => { $('pv-group-error').hidden = true; });
    $('pv-group-form').addEventListener('submit', event => {
        event.preventDefault();
        const name = $('pv-group-name').value.trim().replace(/\s+/g, ' '), key = name.toLowerCase();
        const existing = draftGroups.find(group => group.name.toLowerCase() === key);
        const target = renameGroupId ? draftGroups.find(group => group.id === renameGroupId) : existing;
        const members = new Set([...(target ? groupMembers(target.id) : []), ...(!renameGroupId ? groupSelection : [])]);
        const collision = config.preferences.options.find(option => option.name.trim().toLowerCase() === key && !members.has(option.name));
        let error = !name ? 'Enter a group name.' : renameGroupId && existing && existing.id !== renameGroupId ? 'A group with this name already exists.' : collision ? `Select ${collision.name} too, or choose another group name.` : '';
        // Moving a same-name catalog item must not leave its previous group with a conflicting name.
        if (!error && !renameGroupId) {
            const conflict = draftGroups.find(group => group.id !== target?.id && groupMembers(group.id).some(member => !groupSelection.has(member))
                && config.preferences.options.some(option => option.name.toLowerCase() === group.name.toLowerCase() && groupSelection.has(option.name) && assignments.get(option.name) === group.id));
            if (conflict) error = `Rename ${conflict.name} first, or select all its processes to move them together.`;
        }
        if (error) { $('pv-group-error').textContent = error; $('pv-group-error').hidden = false; $('pv-group-name').focus(); return; }
        const group = target || {id:`new${++draftGroupSequence}`,name,enabled:true};
        if (!target) draftGroups.push(group);
        group.name = name;
        if (!renameGroupId) { groupSelection.forEach(member => assignments.set(member, group.id)); groupSelection.clear(); }
        draftGroups = draftGroups.filter(item => groupMembers(item.id).length > 0);
        expandedGroups.add(group.id); renderSettings(); groupDialog.close();
        const toggle = [...settingsDialog.querySelectorAll('[data-toggle-group]')].find(button => button.dataset.toggleGroup === group.id);
        toggle?.focus({preventScroll:true}); toggle?.scrollIntoView({block:'nearest'});
    });
    settingsDialog.addEventListener('change', event => {
        if (event.target.name === 'processes') { event.target.checked ? draftVisible.add(event.target.value) : draftVisible.delete(event.target.value); }
        if (event.target.dataset.member) {
            event.target.checked ? groupSelection.add(event.target.dataset.member) : groupSelection.delete(event.target.dataset.member);
            renderGroupSelection();
        }
        if (event.target.dataset.groupVisible) {
            draftGroups.find(group => group.id === event.target.dataset.groupVisible).enabled = event.target.checked;
            renderSettings();
        }
    });
    settingsDialog.addEventListener('click', event => {
        const toggle = event.target.closest('[data-toggle-group]');
        if (toggle) {
            const id = toggle.dataset.toggleGroup, open = !expandedGroups.has(id);
            open ? expandedGroups.add(id) : expandedGroups.delete(id);
            toggle.setAttribute('aria-expanded', String(open)); $(`pv-members-${id}`).hidden = !open; return;
        }
        const rename = event.target.closest('[data-rename-group]');
        if (rename) { askGroupName(draftGroups.find(group => group.id === rename.dataset.renameGroup)); return; }
        const button = event.target.closest('[data-remove-group]'); if (!button) return;
        draftGroups = draftGroups.filter(group => group.id !== button.dataset.removeGroup);
        assignments.forEach((id, member) => { if (id === button.dataset.removeGroup) assignments.delete(member); });
        expandedGroups.delete(button.dataset.removeGroup);
        renderSettings();
    });
    $('pv-settings-all').addEventListener('click', () => { draftVisible = new Set(config.preferences.options.map(option => option.name)); draftGroups.forEach(group => group.enabled = true); renderSettings(); });
    $('pv-settings-none').addEventListener('click', () => { draftVisible.clear(); draftGroups.forEach(group => group.enabled = false); renderSettings(); });
    $('pv-settings-defaults').addEventListener('click', () => {
        const defaults = config.preferences.defaults;
        if (!defaults) { notify('The default process groups are unavailable. Reload the page.', 'error'); return; }
        draftVisible = new Set(defaults.visible);
        draftGroups = defaults.groups.map((group, index) => ({id:`g${index}`,name:group.name,enabled:group.enabled !== false}));
        assignments = new Map();
        defaults.groups.forEach((group, index) => group.members.forEach(member => assignments.set(member, `g${index}`)));
        groupSelection.clear(); expandedGroups.clear(); renameGroupId = ''; renderSettings();
    });
    $('pv-settings-form').addEventListener('submit', async event => {
        event.preventDefault();
        const button = $('pv-settings-save'); if (button.disabled) return;
        button.disabled = true;
        try {
            const groups = draftGroups.map(group => ({name:group.name,enabled:group.enabled,members:[...assignments].filter(([,id]) => id === group.id).map(([member]) => member)}));
            if (groups.some(group => !group.members.length)) throw new Error('Assign at least one process to each group, or remove the empty group.');
            const data = await post(config.preferencesUrl, {processes:[...draftVisible],groups});
            settingsRevision++; applyQueue(data); settingsDialog.close(); notify('Process tabs and groups saved.');
        } catch (error) { notify(error.message, 'error'); }
        finally { button.disabled = false; }
    });
    regroup(); cacheStatus(config.cache); filter(false);
    // Reveal only the final filtered layout; never paint the complete server-rendered queue.
    requestAnimationFrame(() => {
        root.removeAttribute('data-pv-initializing');
        root.setAttribute('aria-busy', 'false');
    });
    setInterval(() => {
        renderRefreshStatus();
        if (!document.hidden && Date.now() >= nextCheckAt) loadQueue();
    }, 1000);
    document.addEventListener('visibilitychange', () => { if (!document.hidden && Date.now() >= nextCheckAt) loadQueue(); });
})();
