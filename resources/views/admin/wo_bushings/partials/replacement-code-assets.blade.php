@php
    $storedReplacementCodes = \App\Models\Code::query()->whereNotNull('code')->where('code', '!=', '')
        ->get(['id', 'name', 'code']);
    $replacementCodeOrder = ['K', 'P', 'L', 'D', 'KIT', 'S'];
    $replacementCodes = collect($replacementCodeOrder)->map(fn ($code) => $storedReplacementCodes->firstWhere('code', $code))
        ->filter()->values();
@endphp
<style>
    .bushing-code-control { display: inline-flex; align-items: center; flex: 0 0 auto; margin-left: .25rem; }
    .bushing-code-control[hidden], .bushing-code-control [hidden] { display: none !important; }
    .bushing-code-select { width: 94px; min-width: 0; height: 28px; font-size: .75rem; padding: 0 18px 0 4px; }
    .bushing-code-control .bushing-code-select { color-scheme: dark; background-color: #142333 !important; color: #edf5ff !important; border: 1px solid #6788a5 !important; }
    .bushing-code-control .bushing-code-select:focus { border-color: #55c8d5 !important; box-shadow: 0 0 0 2px #55c8d533; }
    .bushing-code-control .bushing-code-select option { background-color: #1c3044; color: #edf5ff; }
    .bushing-code-control .bushing-code-select option:checked { background-color: #285b70; color: #fff; }
    .bushing-code-badge { padding: 0 4px; min-width: 24px; height: 25px; font-weight: 600; font-size: .8rem; }
</style>
<script>
(function () {
    const codes = @json($replacementCodes);
    const storedCodes = @json($storedReplacementCodes);
    if (!window.BushingReplacementCodes) {
        function fieldName(checkbox) {
            const name = checkbox.name || '';
            if (/^group_bushings\[.+\]\[items\]\[\d+\]\[selected\]$/.test(name)) {
                return name.replace(/\[selected\]$/, '[codes_id]');
            }
            if (/^group_bushings\[.+\]\[components\]\[\]$/.test(name)) {
                return name.replace(/\[components\]\[\]$/, '[codes][' + checkbox.value + ']');
            }
            return null;
        }
        function sync(checkbox) {
            const name = fieldName(checkbox);
            if (!name) return;
            let control = checkbox.nextElementSibling;
            if (!control || !control.classList.contains('bushing-code-control')) {
                control = document.createElement('span');
                control.className = 'bushing-code-control';
                const select = document.createElement('select');
                select.className = 'form-select bushing-code-select';
                select.name = name;
                select.setAttribute('aria-label', 'Replacement code for ' + (checkbox.dataset.ipl || checkbox.value));
                select.add(new Option('Code…', ''));
                window.BushingReplacementCodes.codes.forEach(code => {
                    const option = new Option((code.code === 'KIT' ? 'KIT' : code.name) + ' . ' + code.code, String(code.id));
                    option.dataset.code = code.code;
                    select.add(option);
                });
                const savedId = checkbox.dataset.codeId || '';
                if (savedId && !Array.from(select.options).some(option => option.value === savedId)) {
                    // Preserve an existing historical code without offering it for new selection.
                    const saved = window.BushingReplacementCodes.storedCodes.find(code => String(code.id) === savedId);
                    if (saved) {
                        const option = new Option(saved.name + ' . ' + saved.code, savedId);
                        option.dataset.code = saved.code;
                        option.hidden = true;
                        select.add(option);
                    }
                }
                select.value = savedId;
                const badge = document.createElement('button');
                badge.type = 'button';
                badge.className = 'btn btn-outline-info bushing-code-badge';
                badge.addEventListener('click', event => {
                    event.preventDefault();
                    event.stopPropagation();
                    badge.hidden = true;
                    select.hidden = false;
                    select.focus();
                });
                select.addEventListener('click', event => event.stopPropagation());
                select.addEventListener('change', () => sync(checkbox));
                select.addEventListener('blur', () => sync(checkbox));
                control.append(select, badge);
                checkbox.after(control);
            }
            const select = control.querySelector('select');
            const badge = control.querySelector('button');
            const option = select.selectedOptions[0];
            const hasCode = Boolean(select.value);
            control.hidden = !checkbox.checked;
            select.disabled = !checkbox.checked;
            select.required = checkbox.checked;
            select.setCustomValidity(checkbox.checked && !hasCode ? 'Select a replacement code for this bushing before saving.' : '');
            select.hidden = hasCode;
            badge.hidden = !hasCode;
            badge.textContent = option?.dataset.code || '';
            badge.title = (option?.textContent || '') + ' — change code';
            badge.setAttribute('aria-label', badge.title);
        }
        window.BushingReplacementCodes = { codes, storedCodes, sync };
        document.addEventListener('change', event => {
            if (event.target.matches('.component-checkbox')) sync(event.target);
        });
    }
    window.BushingReplacementCodes.codes = codes;
    window.BushingReplacementCodes.storedCodes = storedCodes;
    document.querySelectorAll('.component-checkbox').forEach(window.BushingReplacementCodes.sync);
})();
</script>
