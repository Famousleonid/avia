document.addEventListener('DOMContentLoaded', function () {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ("" + String(window.TdrshowConfig3.value0) + "");
        document.querySelectorAll('.edit-condition-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const conditionId = this.getAttribute('data-condition-id');
                const row = document.querySelector(`tr[data-condition-id="${conditionId}"]`);
                if (row) {
                    this.closest('.btn-group').classList.add('d-none');
                    row.querySelector('.save-cancel-group').classList.remove('d-none');
                    row.querySelector('.condition-name-display').classList.add('d-none');
                    const editInput = row.querySelector('.condition-name-edit');
                    editInput.classList.remove('d-none');
                    editInput.focus();
                }
            });
        });
        document.querySelectorAll('.cancel-edit-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const conditionId = this.getAttribute('data-condition-id');
                const row = document.querySelector(`tr[data-condition-id="${conditionId}"]`);
                if (row) {
                    const input = row.querySelector('.condition-name-edit');
                    input.value = input.getAttribute('data-original-name');
                    input.classList.add('d-none');
                    row.querySelector('.condition-name-display').classList.remove('d-none');
                    row.querySelector('.save-cancel-group').classList.add('d-none');
                    row.querySelector('.btn-group').classList.remove('d-none');
                }
            });
        });
        document.querySelectorAll('.save-condition-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const conditionId = this.getAttribute('data-condition-id');
                const row = document.querySelector(`tr[data-condition-id="${conditionId}"]`);
                const input = row?.querySelector('.condition-name-edit');
                const newName = input?.value.trim() || '';
                this.disabled = true;
                this.innerHTML = ("<i class=\"fas fa-spinner fa-spin\"></i> " + String(window.TdrshowConfig3.value1) + "");
                fetch(`/admin/conditions/${conditionId}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify({ name: newName, unit: 1 })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        row.querySelector('.condition-name-display').textContent = newName || ("" + String(window.TdrshowConfig3.value2) + "");
                        input.setAttribute('data-original-name', newName);
                        input.classList.add('d-none');
                        row.querySelector('.condition-name-display').classList.remove('d-none');
                        row.querySelector('.save-cancel-group').classList.add('d-none');
                        row.querySelector('.btn-group').classList.remove('d-none');
                        const tabTdr = document.getElementById('tab-tdr');
                        if (tabTdr) { const t = new bootstrap.Tab(tabTdr); t.show(); }
                        window.location.reload();
                    } else {
                        (typeof showNotification === 'function' ? (m) => showNotification(m, 'error') : (window.NotificationHandler?.error || window.notifyError))(data.message || ("" + String(window.TdrshowConfig3.value3) + ""));
                        this.disabled = false;
                        this.innerHTML = ("<i class=\"fas fa-check\"></i> " + String(window.TdrshowConfig3.value4) + "");
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    this.disabled = false;
                    this.innerHTML = ("<i class=\"fas fa-check\"></i> " + String(window.TdrshowConfig3.value5) + "");
                });
            });
        });
        document.querySelectorAll('.delete-condition-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const conditionId = this.getAttribute('data-condition-id');
                const conditionName = this.getAttribute('data-condition-name');
                const button = this;
                window.tdrShowConfirm(`${window.TdrshowConfig3.value6} "${conditionName}"?`).then(function(confirmed) {
                    if (!confirmed) return;
                    button.disabled = true;
                    button.innerHTML = ("<i class=\"fas fa-spinner fa-spin\"></i> " + String(window.TdrshowConfig3.value7) + "");
                    const workorderId = document.querySelector('#unitInspectionForm input[name="workorder_id"]')?.value || document.querySelector('#addConditionFormFromManage input[name="workorder_id"]')?.value || window.currentWorkorderId;
                    const deleteUrl = workorderId ? `/admin/conditions/${conditionId}?workorder_id=${workorderId}` : `/admin/conditions/${conditionId}`;
                    fetch(deleteUrl, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
                    })
                    .then(response => {
                        if (response.redirected && (response.url.includes('/show2/') || response.url.includes('/tdrs/show/'))) {
                            window.top.location.href = response.url;
                            return null;
                        }
                        if (response.redirected && window.tdrShowUrl) {
                            window.top.location.href = window.tdrShowUrl;
                            return null;
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (!data) return;
                        if (data.success) {
                            const redirectUrl = data.redirect || window.tdrShowUrl;
                            delete manageConditionModal.dataset.returnToModal;
                            window.top.location.href = redirectUrl;
                        } else {
                            window.tdrShowNotify(data.message || ("" + String(window.TdrshowConfig3.value8) + ""), 'error');
                            button.disabled = false;
                            button.innerHTML = ("<i class=\"fas fa-trash\"></i> " + String(window.TdrshowConfig3.value9) + "");
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        window.tdrShowNotify(("" + String(window.TdrshowConfig3.value10) + ""), 'error');
                        button.disabled = false;
                        button.innerHTML = ("<i class=\"fas fa-trash\"></i> " + String(window.TdrshowConfig3.value11) + "");
                    });
                });
            });
        });
        const addConditionFormFromManage = document.getElementById('addConditionFormFromManage');
        if (addConditionFormFromManage) {
            addConditionFormFromManage.addEventListener('submit', function (e) {
                e.preventDefault();
                const formData = new FormData(this);
                const submitBtn = this.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.innerHTML = ("<i class=\"fas fa-spinner fa-spin\"></i> " + String(window.TdrshowConfig3.value12) + "");
                fetch(("" + String(window.TdrshowConfig3.value13) + ""), {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: formData
                })
                .then(response => response.headers.get('content-type')?.includes('application/json') ? response.json() : {})
                .then(data => {
                    if (data.success !== false) {
                        const addModal = bootstrap.Modal.getInstance(document.getElementById('addConditionModalFromManage'));
                        if (addModal) addModal.hide();
                        const tabTdr = document.getElementById('tab-tdr');
                        if (tabTdr) { const t = new bootstrap.Tab(tabTdr); t.show(); }
                        window.location.replace(window.tdrShowUrl);
                    } else {
                        (typeof showNotification === 'function' ? (m) => showNotification(m, 'error') : (window.NotificationHandler?.error || window.notifyError))(data.message || ("" + String(window.TdrshowConfig3.value14) + ""));
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = ("" + String(window.TdrshowConfig3.value15) + "");
                    }
                })
                .catch(() => {
                    (typeof showNotification === 'function' ? (m) => showNotification(m, 'error') : (window.NotificationHandler?.error || window.notifyError))(("" + String(window.TdrshowConfig3.value16) + ""));
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = ("" + String(window.TdrshowConfig3.value17) + "");
                });
            });
        }
    });
