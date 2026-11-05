(() => {
    'use strict';
    const modal = document.getElementById('sendMsgModal');
    if (!modal || window.MessageGroups) return;
    if (modal.dataset.canGroupMessages !== '1') {
        window.MessageGroups = {
            load: async () => {}, selectedIds: () => [], clear: () => {},
            recipientCount: ids => new Set(ids.map(Number)).size,
        };
        return;
    }
    const list = document.getElementById('msgGroupsList');
    const editor = document.getElementById('msgGroupEditor');
    const members = document.getElementById('msgGroupMembers');
    const name = document.getElementById('msgGroupName');
    const error = document.getElementById('msgGroupError');
    const search = document.getElementById('msgGroupSearch');
    const save = document.getElementById('msgGroupSave');
    const remove = document.getElementById('msgGroupDelete');
    const cancel = document.getElementById('msgGroupCancel');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    let groups = [], users = [], editing = null, busy = false;
    const selectedIds = () => [...list.querySelectorAll('input:checked')].map(input => Number(input.value));
    const changed = () => modal.dispatchEvent(new Event('message-recipients-changed'));
    function checkbox(user, checked = false) {
        const label = document.createElement('label'); label.className = 'user-row';
        const input = document.createElement('input'); input.type = 'checkbox'; input.className = 'form-check-input m-0';
        input.value = user.id; input.checked = checked;
        const text = document.createElement('span'); text.textContent = user.name;
        label.append(input, text); return label;
    }
    async function load() {
        const selected = new Set(selectedIds());
        const response = await fetch(modal.dataset.groupsUrl, { headers: { Accept: 'application/json' }, spinner: false });
        if (!response.ok || response.redirected) throw new Error('Could not load recipient groups.');
        const data = await response.json(); groups = data.groups; users = data.users;
        list.replaceChildren();
        document.getElementById('msgGroupNew').classList.toggle('d-none', !data.can_manage);
        if (!groups.length) { const empty = document.createElement('div'); empty.className = 'text-secondary small'; empty.textContent = 'No recipient groups yet'; list.append(empty); }
        groups.forEach(group => {
            const row = document.createElement('div'); row.className = 'msg-group-row';
            const count = group.user_ids.filter(id => Number(id) !== Number(modal.dataset.userId)).length;
            row.append(checkbox({ id: group.id, name: `${group.name} (${count})` }, selected.has(group.id)));
            if (data.can_manage) {
                const edit = document.createElement('button'); edit.type = 'button'; edit.className = 'btn btn-outline-secondary btn-sm';
                edit.textContent = 'Edit'; edit.setAttribute('aria-label', `Edit ${group.name}`);
                edit.addEventListener('click', () => openEditor(group)); row.append(edit);
            }
            list.append(row);
        });
        changed();
    }
    function openEditor(group = null) {
        editing = group;
        document.getElementById('msgGroupEditorTitle').textContent = group ? 'Edit recipient group' : 'New recipient group';
        name.value = group?.name || ''; search.value = ''; error.hidden = true;
        members.replaceChildren(...users.map(user => checkbox(user, group?.user_ids.includes(user.id))));
        remove.hidden = !group;
        modal.querySelector('.msg-layout').classList.add('d-none');
        modal.querySelector('.modal-footer').classList.add('d-none');
        editor.hidden = false; name.focus();
    }
    function back() {
        if (busy) return;
        editor.hidden = true;
        modal.querySelector('.msg-layout').classList.remove('d-none');
        modal.querySelector('.modal-footer').classList.remove('d-none');
        document.getElementById('msgText').focus();
    }
    function showError(message) { error.textContent = message; error.hidden = false; }
    async function mutate(method, body) {
        if (busy) return;
        busy = true; save.disabled = remove.disabled = cancel.disabled = true; error.hidden = true;
        try {
            const response = await fetch(modal.dataset.groupsUrl + (editing ? '/' + editing.id : ''), {
                method, headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: body ? JSON.stringify(body) : undefined,
            });
            const data = await response.json();
            if (!response.ok) throw new Error(Object.values(data.errors || {}).flat().join(' ') || data.message || 'Could not save group.');
            await load(); busy = false; back();
            window.showNotification?.(method === 'DELETE' ? 'Recipient group deleted.' : 'Recipient group saved.', 'success');
        } catch (e) { showError(e.message || 'Could not save group. Please try again.'); }
        finally { busy = false; save.disabled = remove.disabled = cancel.disabled = false; }
    }
    document.getElementById('msgGroupNew').addEventListener('click', () => openEditor());
    cancel.addEventListener('click', back);
    save.addEventListener('click', () => {
        const ids = [...members.querySelectorAll('input:checked')].map(input => Number(input.value));
        if (!name.value.trim() || !ids.length) return showError('Enter a group name and select at least one user.');
        mutate(editing ? 'PUT' : 'POST', { name: name.value.trim(), user_ids: ids });
    });
    remove.addEventListener('click', async () => {
        if (busy) return;
        if (!window.confirmDialog) return showError('Confirmation dialog is unavailable.');
        if (await window.confirmDialog({ title: 'Delete recipient group?', message: 'Previously sent messages will remain unchanged.', okText: 'Delete', cancelText: 'Cancel', danger: true })) mutate('DELETE');
    });
    search.addEventListener('input', () => {
        const term = search.value.trim().toLocaleLowerCase();
        [...members.children].forEach(label => { label.classList.toggle('d-none', !label.textContent.toLocaleLowerCase().includes(term)); });
    });
    list.addEventListener('change', changed);
    modal.addEventListener('hide.bs.modal', event => { if (busy) event.preventDefault(); });
    modal.addEventListener('hidden.bs.modal', () => { if (!editor.hidden) back(); });
    window.MessageGroups = {
        load, selectedIds,
        clear() { list.querySelectorAll('input').forEach(input => { input.checked = false; }); changed(); },
        recipientCount(directIds) {
            const selected = new Set(selectedIds());
            const ids = new Set(directIds.map(Number));
            groups.filter(group => selected.has(group.id)).forEach(group => group.user_ids.forEach(id => ids.add(Number(id))));
            ids.delete(Number(modal.dataset.userId)); return ids.size;
        },
    };
})();
