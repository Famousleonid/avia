document.addEventListener('DOMContentLoaded', function () {
        const selectAllCheckbox = document.getElementById('selectAllConditions');
        const conditionCheckboxes = document.querySelectorAll('.condition-checkbox');
        const saveBtn = document.getElementById('saveUnitInspectionsBtn');
        const form = document.getElementById('unitInspectionForm');

        const manageConditionBtn = document.querySelector('[data-bs-target="#manageConditionModal"]');
        const unitInspectionModal = document.getElementById('unitInspectionModal');
        const manageConditionModal = document.getElementById('manageConditionModal');

        if (manageConditionBtn && unitInspectionModal && manageConditionModal) {
            manageConditionBtn.addEventListener('click', function () {
                manageConditionModal.dataset.returnToModal = 'unitInspectionModal';
            });
            manageConditionModal.addEventListener('hidden.bs.modal', function () {
                if (manageConditionModal.dataset.returnToModal === 'unitInspectionModal') {
                    const unitModal = new bootstrap.Modal(unitInspectionModal);
                    unitModal.show();
                    delete manageConditionModal.dataset.returnToModal;
                }
            });
        }

        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function () {
                conditionCheckboxes.forEach(checkbox => { checkbox.checked = this.checked; });
            });
        }
        conditionCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function () {
                const allChecked = Array.from(conditionCheckboxes).every(cb => cb.checked);
                const someChecked = Array.from(conditionCheckboxes).some(cb => cb.checked);
                if (selectAllCheckbox) {
                    selectAllCheckbox.checked = allChecked;
                    selectAllCheckbox.indeterminate = someChecked && !allChecked;
                }
            });
        });
        document.querySelectorAll('.condition-note-slot').forEach(input => {
            input.addEventListener('input', function () {
                if (this.value.trim() === '') return;

                const row = this.closest('.unit-inspection-note-row');
                const checkbox = row?.querySelector('.condition-checkbox');
                if (checkbox && !checkbox.checked) {
                    checkbox.checked = true;
                    checkbox.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
        });

        if (saveBtn && form) {
            saveBtn.addEventListener('click', function () {
                const formData = new FormData(form);
                const conditionsData = {};
                conditionCheckboxes.forEach(checkbox => {
                    if (checkbox.checked) {
                        const conditionId = checkbox.getAttribute('data-condition-id');
                        const notesInput = document.querySelector(`input[name="conditions[${conditionId}][notes]"]`);
                        const inspectionIdInput = document.querySelector(`input[name="conditions[${conditionId}][inspection_id]"]`);
                        conditionsData[conditionId] = {
                            selected: true,
                            notes: notesInput ? notesInput.value : '',
                            inspection_id: inspectionIdInput ? inspectionIdInput.value : null
                        };
                    }
                });
                saveBtn.disabled = true;
                saveBtn.innerHTML = ("<i class=\"fas fa-spinner fa-spin\"></i> " + String(window.TdrshowConfig2.value0) + "");
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || formData.get('_token') || ("" + String(window.TdrshowConfig2.value1) + "");
                fetch(("" + String(window.TdrshowConfig2.value2) + ""), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify({ workorder_id: formData.get('workorder_id'), conditions: conditionsData })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('unitInspectionModal'));
                        if (modal) modal.hide();
                        const tabTdr = document.getElementById('tab-tdr');
                        if (tabTdr) { const t = new bootstrap.Tab(tabTdr); t.show(); }
                        window.location.reload();
                    } else {
                        if (typeof showNotification === 'function') showNotification(data.message || ("" + String(window.TdrshowConfig2.value3) + ""), 'error');
                        saveBtn.disabled = false;
                        saveBtn.innerHTML = ("<i class=\"fas fa-save\"></i> " + String(window.TdrshowConfig2.value4) + "");
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    (typeof showNotification === 'function' ? (m) => showNotification(m, 'error') : (window.NotificationHandler?.error || window.notifyError))(("" + String(window.TdrshowConfig2.value5) + ""));
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = ("<i class=\"fas fa-save\"></i> " + String(window.TdrshowConfig2.value6) + "");
                });
            });
        }
    });
