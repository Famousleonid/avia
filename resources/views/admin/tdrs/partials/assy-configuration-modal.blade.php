<div class="modal fade" id="woAssyConfigurationModal" tabindex="-1" aria-labelledby="woAssyConfigurationTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="woAssyConfigurationTitle">{{ __('ASSY composition') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">{{ __('Choose the part actually included in the assembly. This does not order another part.') }}</p>
                <div id="woAssyConfigurationPendingAlert" class="alert alert-warning d-none" role="status">{{ __('ASSY composition is incomplete. Choose all variants before using PRL or STD forms.') }}</div>
                <div id="woAssyConfigurationError" class="alert alert-danger d-none" role="alert"></div>
                <div id="woAssyConfigurationSlots" class="d-grid gap-3"></div>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const openButton = document.getElementById('woAssyConfigurationOpen');
    const modalElement = document.getElementById('woAssyConfigurationModal');
    if (!openButton || !modalElement) return;
    const list = document.getElementById('woAssyConfigurationSlots');
    const error = document.getElementById('woAssyConfigurationError');
    const pending = document.getElementById('woAssyConfigurationPending');
    const pendingAlert = document.getElementById('woAssyConfigurationPendingAlert');
    let slots = [];

    function showError(message) {
        error.textContent = message;
        error.classList.remove('d-none');
        if (typeof window.showNotification === 'function') window.showNotification(message, 'error');
        else if (window.NotificationHandler?.error) window.NotificationHandler.error(message);
    }

    function render() {
        list.replaceChildren();
        const outstanding = slots.filter(slot => !slot.selected_coverage_id).length;
        pending.textContent = `${outstanding} {{ __('pending') }}`;
        pending.classList.toggle('d-none', outstanding === 0);
        pendingAlert.classList.toggle('d-none', outstanding === 0);
        openButton.classList.toggle('btn-outline-warning', outstanding > 0);
        openButton.classList.toggle('btn-outline-info', outstanding === 0);
        openButton.classList.toggle('d-none', slots.length === 0);
        slots.forEach(slot => {
            const card = document.createElement('div');
            card.className = 'border rounded p-3';
            const heading = document.createElement('div');
            heading.className = 'fw-semibold mb-2';
            heading.textContent = `${slot.parent_part_number} · ${slot.slot.replaceAll('_', ' ')}`;
            const select = document.createElement('select');
            select.className = 'form-select form-select-sm';
            select.setAttribute('aria-label', heading.textContent);
            const empty = new Option('{{ __('Choose installed variant') }}', '');
            select.add(empty);
            slot.candidates.forEach(candidate => {
                select.add(new Option(`${candidate.ipl} · ${candidate.part_number}`, String(candidate.coverage_id)));
            });
            select.value = slot.selected_coverage_id ? String(slot.selected_coverage_id) : '';
            select.disabled = openButton.dataset.locked === '1';
            const save = document.createElement('button');
            save.type = 'button';
            save.className = 'btn btn-primary btn-sm mt-2';
            save.textContent = '{{ __('Save selection') }}';
            save.disabled = openButton.dataset.locked === '1';
            save.addEventListener('click', async () => {
                error.classList.add('d-none');
                if (typeof window.confirmDialog !== 'function') {
                    showError('{{ __('Confirmation dialog is unavailable. Nothing was saved.') }}');
                    return;
                }
                const choice = slot.candidates.find(candidate => String(candidate.coverage_id) === select.value);
                const confirmed = await window.confirmDialog({
                    title: '{{ __('Save ASSY composition?') }}',
                    message: choice ? `${slot.parent_part_number}: ${choice.part_number}` : '{{ __('Remove this ASSY selection?') }}',
                    okText: '{{ __('Save') }}', cancelText: '{{ __('Cancel') }}'
                });
                if (!confirmed) return;
                save.disabled = true;
                try {
                    const response = await fetch(openButton.dataset.updateUrl, {
                        method: 'PATCH',
                        headers: {'Content-Type': 'application/json', 'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''},
                        body: JSON.stringify({parent_option_id: slot.parent_option_id,
                            choice_slot: slot.slot, selected_coverage_id: select.value || null})
                    });
                    const result = await response.json();
                    if (!response.ok || !result.success) {
                        showError(result.message || '{{ __('Unable to save ASSY composition.') }}');
                        return;
                    }
                    window.location.reload();
                } catch (exception) {
                    showError('{{ __('Unable to save ASSY composition.') }}');
                } finally {
                    save.disabled = false;
                }
            });
            card.append(heading, select, save);
            list.append(card);
        });
    }

    fetch(openButton.dataset.indexUrl, {headers: {'Accept': 'application/json'}})
        .then(response => response.ok ? response.json() : Promise.reject(response))
        .then(result => { slots = result.slots || []; render(); })
        .catch(() => { openButton.classList.remove('d-none'); showError('{{ __('Unable to load ASSY composition.') }}'); });
    openButton.addEventListener('click', () => {
        error.classList.add('d-none');
        if (window.bootstrap?.Modal) window.bootstrap.Modal.getOrCreateInstance(modalElement).show();
        else showError('{{ __('Unable to open ASSY composition.') }}');
    });
});
</script>
