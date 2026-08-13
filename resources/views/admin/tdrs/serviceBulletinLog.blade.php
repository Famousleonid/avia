<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Service Bulletin Log - {{ $current_wo->number }}</title>
    @include('partials.user-ui-settings')
    <link rel="stylesheet" href="{{ asset('css/forms/service-bulletin-log.css') }}?v={{ filemtime(public_path('css/forms/service-bulletin-log.css')) }}">
</head>
<body>
@php
    $serviceBulletinReadOnly = (bool) ($serviceBulletinAccess['read_only'] ?? false);
    $serviceBulletinReadOnlyMessage = $serviceBulletinAccess['message'] ?? null;
    $totalPages = max(1, $bulletinPages->count());
@endphp

<main class="sb-page">
    <form class="sb-log-form" method="post" action="{{ route('tdrs.serviceBulletinLog.update', ['workorder' => $current_wo->id]) }}">
        @csrf
        <input type="hidden" name="clear_status_bulletin_id" value="">

        <div class="sb-screen-actions">
            <div class="sb-note-presets" aria-label="Notes templates">
                <span class="sb-note-presets-label">Notes templates:</span>
                <button
                    class="sb-note-preset sb-note-preset-danger"
                    type="button"
                    data-note-preset-text="P/N doesn't match"
                    data-note-preset-color="danger"
                    disabled
                >P/N doesn't match</button>
                <button
                    class="sb-note-preset sb-note-preset-primary"
                    type="button"
                    data-note-preset-text="Superseded by"
                    data-note-preset-color="primary"
                    disabled
                >Superseded by</button>
                <button
                    class="sb-note-preset sb-note-preset-violet"
                    type="button"
                    data-note-preset-text="S/N doesn't match"
                    data-note-preset-color="violet"
                    disabled
                >S/N doesn't match</button>
            </div>
            <button class="sb-print" type="button" onclick="window.print()">Print</button>
            <button class="sb-settings" type="button" data-open-print-settings>Print Settings</button>
            <button class="sb-save" type="submit" @disabled($serviceBulletinReadOnly)>
                <span class="sb-save-spinner" aria-hidden="true"></span>
                <span class="sb-save-text">Save</span>
            </button>
        </div>

        @if($serviceBulletinReadOnly && $serviceBulletinReadOnlyMessage)
            <p class="sb-message sb-readonly-message">{{ $serviceBulletinReadOnlyMessage }}</p>
        @endif

        @if(! $manual)
            <p class="sb-empty">This work order does not have a manual assigned through its unit.</p>
        @elseif($serviceBulletins->isEmpty())
            <p class="sb-empty">No Service Bulletin rows have been created for manual {{ $manual->number }}.</p>
        @else
            @foreach($bulletinPages as $pageIndex => $pageRows)
                <section class="sb-form-page">
                    <header class="sb-page-header">
                        <div class="sb-logo-cell">
                            <img class="sb-logo" src="{{ asset('img/icons/AT_logo-rb.svg') }}" alt="Aviatechnik">
                        </div>
                        <div class="sb-title-cell">
                            <h1>Service Bulletin Log</h1>
                            <div class="sb-meta-line"><span>Work Order No.:</span><strong>W{{ $current_wo->number }}</strong></div>
                            <div class="sb-meta-line"><span>Component Part No.:</span><strong>{{ $current_wo->unit?->part_number ?? 'N/A' }}</strong></div>
                            <div class="sb-meta-line"><span>Component Description:</span><strong>{{ $current_wo->displayDescription() ?: 'N/A' }}</strong></div>
                        </div>
                    </header>

                    <div class="sb-table-wrap">
                        <table class="sb-table">
                            <colgroup>
                                <col class="sb-col-year">
                                <col class="sb-col-ac">
                                <col class="sb-col-oem">
                                <col class="sb-col-awd">
                                <col class="sb-col-ident">
                                <col class="sb-col-desc">
                                <col class="sb-col-status">
                                <col class="sb-col-status">
                                <col class="sb-col-status">
                                <col class="sb-col-req">
                                <col class="sb-col-req">
                                <col class="sb-col-req">
                            </colgroup>
                            <thead>
                            <tr>
                                <th>Year Introduced</th>
                                <th>A/C MFG Service Bulletin No.</th>
                                <th>OEM Service Bulletin No.</th>
                                <th>A.W.D. No.</th>
                                <th>Identification Method</th>
                                <th>Description</th>
                                @foreach($statusOptions as $label)
                                    <th>{{ $label }}</th>
                                @endforeach
                                <th>Optional</th>
                                <th>Recommended</th>
                                <th>Mandatory</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($pageRows as $bulletin)
                                @php
                                    $log = $logsByBulletin->get($bulletin->id);
                                    $currentStatus = old("rows.{$bulletin->id}.status", $log?->status);
                                    $requirement = $bulletin->default_requirement;
                                    $stampNumber = trim((string) ($log?->stampUser?->stamp ?? ''));
                                    $stampNumber = $stampNumber !== '' ? $stampNumber : ($log?->stamp_user_id ? (string) $log->stamp_user_id : '');
                                @endphp
                                <tr>
                                    <td>{{ $bulletin->year_introduced }}</td>
                                    <td>{{ $bulletin->ac_mfg_service_bulletin_no }}</td>
                                    <td>{{ $bulletin->oem_service_bulletin_no }}</td>
                                    <td>{{ $bulletin->awd_no ?: 'N/A' }}</td>
                                    <td>{{ $bulletin->identification_method }}</td>
                                    <td class="sb-description-cell">{{ $bulletin->description }}</td>
                                    @foreach($statusOptions as $status => $label)
                                        <td class="sb-status-cell">
                                            <label class="sb-stamp-option">
                                                <input type="radio" name="rows[{{ $bulletin->id }}][status]" value="{{ $status }}" @checked($currentStatus === $status) @disabled($serviceBulletinReadOnly)>
                                                <span class="sb-screen-stamp">STAMP</span>
                                                <span class="sb-print-stamp {{ $currentStatus === null ? 'is-na' : ($currentStatus === $status && $stampNumber !== '' ? 'is-selected' : 'is-placeholder') }}">{{ $currentStatus === null ? 'N/A' : ($currentStatus === $status && $stampNumber !== '' ? $stampNumber : 'STAMP') }}</span>
                                            </label>
                                        </td>
                                    @endforeach
                                    <td class="sb-mark-cell">{{ $requirement === \App\Models\ManualServiceBulletin::REQUIREMENT_OPTIONAL ? 'X' : '' }}</td>
                                    <td class="sb-mark-cell">{{ $requirement === \App\Models\ManualServiceBulletin::REQUIREMENT_RECOMMENDED ? 'X' : '' }}</td>
                                    <td class="sb-mark-cell">{{ $requirement === \App\Models\ManualServiceBulletin::REQUIREMENT_MANDATORY ? 'X' : '' }}</td>
                                </tr>
                                <tr class="sb-notes-row">
                                    <td colspan="12">
                                        <div class="sb-notes-strip">
                                            <label>
                                                <span>Notes</span>
                                                <input type="text" name="rows[{{ $bulletin->id }}][notes]" value="{{ old("rows.{$bulletin->id}.notes", $log?->notes) }}" @disabled($serviceBulletinReadOnly)>
                                            </label>
                                            @if($log?->stampUser || $log?->stamped_at)
                                                <span class="sb-stamp-meta">
                                                    Last stamp:
                                                    {{ $log?->stampUser?->selection_name ?? 'Unknown user' }}
                                                    @if($log?->stamped_at)
                                                        on {{ $log->stamped_at->format('d/M/Y H:i') }}
                                                    @endif
                                                </span>
                                            @endif
                                            <button class="sb-clear-status" type="button" data-bulletin-id="{{ $bulletin->id }}" @disabled($serviceBulletinReadOnly)>
                                                <span class="sb-clear-spinner" aria-hidden="true"></span>
                                                <span class="sb-clear-text">Clear status</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    <footer class="sb-page-footer">
                        <div>Form # 007</div>
                        <div>{{ $pageIndex + 1 }} of {{ $totalPages }}</div>
                        <div>Rev # 0, 15/Dec/2012</div>
                    </footer>
                </section>
            @endforeach
        @endif
    </form>

    <dialog class="sb-settings-dialog" id="serviceBulletinPrintSettings" aria-labelledby="serviceBulletinPrintSettingsTitle">
        <form class="sb-settings-panel" method="dialog">
            <div class="sb-settings-header">
                <h2 id="serviceBulletinPrintSettingsTitle">Print Settings</h2>
                <button class="sb-settings-close" type="submit" value="cancel" aria-label="Close">&times;</button>
            </div>
            <div class="sb-settings-body">
                <label for="serviceBulletinTableFontSize">Table Font (pt)</label>
                <input id="serviceBulletinTableFontSize" type="number" min="6" max="14" step="0.1" value="8.3">
            </div>
            <div class="sb-settings-footer">
                <button class="sb-settings-reset" type="button" data-reset-print-settings>Reset</button>
                <button class="sb-settings-save" type="button" data-save-print-settings>Save</button>
            </div>
        </form>
    </dialog>
</main>
<script>
    (function () {
        var settingsScope = 'tdrs.service-bulletin-log';
        var tableFontSizeKey = 'table_font_size_pt';
        var defaultTableFontSize = 8.3;
        var appliedTableFontSize = defaultTableFontSize;
        var settingsDialog = document.getElementById('serviceBulletinPrintSettings');
        var fontSizeInput = document.getElementById('serviceBulletinTableFontSize');
        var form = document.querySelector('.sb-log-form');
        var saveButton = document.querySelector('.sb-save');
        var notePresetButtons = Array.from(document.querySelectorAll('[data-note-preset-text]'));
        var notesInputs = Array.from(document.querySelectorAll('.sb-notes-row input[type="text"]'));
        var activeNotesInput = null;

        function noteColorFromValue(value) {
            var normalized = String(value || '').toLowerCase();
            if (normalized.includes("p/n doesn't match")) return 'danger';
            if (normalized.includes("s/n doesn't match")) return 'violet';
            if (normalized.includes('superseded by')) return 'primary';
            return null;
        }

        function applyNoteColor(input, color) {
            input.classList.remove('is-note-preset-danger', 'is-note-preset-primary', 'is-note-preset-violet');
            var resolvedColor = color || noteColorFromValue(input.value);
            if (resolvedColor === 'danger') input.classList.add('is-note-preset-danger');
            if (resolvedColor === 'primary') input.classList.add('is-note-preset-primary');
            if (resolvedColor === 'violet') input.classList.add('is-note-preset-violet');
        }

        function selectNotesInput(input) {
            activeNotesInput?.classList.remove('is-note-target');
            activeNotesInput = input;
            activeNotesInput.classList.add('is-note-target');
            notePresetButtons.forEach(function (button) {
                button.disabled = false;
            });
        }

        function insertNotePreset(input, text, color) {
            var currentValue = input.value || '';
            var selectionStart = input.selectionStart ?? currentValue.length;
            var selectionEnd = input.selectionEnd ?? selectionStart;
            var before = currentValue.slice(0, selectionStart);
            var after = currentValue.slice(selectionEnd);
            var leadingSeparator = before !== '' && !/\s$/.test(before) ? ' ' : '';
            var trailingSeparator = text === 'Superseded by' ? ' ' : '';
            var insertedText = leadingSeparator + text + trailingSeparator;

            input.value = before + insertedText + after;
            var caretPosition = before.length + insertedText.length;
            input.focus();
            input.setSelectionRange(caretPosition, caretPosition);
            applyNoteColor(input, color);
            markDirty();
        }

        notesInputs.forEach(function (input) {
            applyNoteColor(input);
            input.addEventListener('focus', function () {
                selectNotesInput(input);
            });
            input.addEventListener('input', function () {
                applyNoteColor(input);
            });
        });

        notePresetButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                if (!activeNotesInput || activeNotesInput.disabled) return;
                insertNotePreset(
                    activeNotesInput,
                    button.getAttribute('data-note-preset-text') || '',
                    button.getAttribute('data-note-preset-color') || ''
                );
            });
        });

        function normalizeTableFontSize(value) {
            var parsed = Number.parseFloat(String(value));
            if (!Number.isFinite(parsed)) return defaultTableFontSize;
            return Math.min(14, Math.max(6, Math.round(parsed * 10) / 10));
        }

        function applyTableFontSize(value) {
            appliedTableFontSize = normalizeTableFontSize(value);
            document.documentElement.style.setProperty('--sb-table-font-size', appliedTableFontSize + 'pt');
            if (fontSizeInput) fontSizeInput.value = String(appliedTableFontSize);
        }

        async function loadPrintSettings() {
            var savedValue = await window.UserUiSettings.get(settingsScope, tableFontSizeKey, defaultTableFontSize);
            applyTableFontSize(savedValue);
        }

        document.querySelector('[data-open-print-settings]')?.addEventListener('click', function () {
            if (!settingsDialog || typeof settingsDialog.showModal !== 'function') return;
            if (fontSizeInput) fontSizeInput.value = String(appliedTableFontSize);
            settingsDialog.showModal();
        });

        document.querySelector('[data-save-print-settings]')?.addEventListener('click', async function () {
            var value = normalizeTableFontSize(fontSizeInput?.value);
            applyTableFontSize(value);
            await window.UserUiSettings.set(settingsScope, tableFontSizeKey, value);
            settingsDialog?.close();
        });

        document.querySelector('[data-reset-print-settings]')?.addEventListener('click', async function () {
            applyTableFontSize(defaultTableFontSize);
            await window.UserUiSettings.set(settingsScope, tableFontSizeKey, null);
        });

        loadPrintSettings().catch(function (error) {
            console.error('Failed to load Service Bulletin Log print settings', error);
            applyTableFontSize(defaultTableFontSize);
        });

        if (!form || !saveButton) return;

        function markDirty() {
            if (saveButton.classList.contains('is-saving')) return;
            saveButton.classList.add('is-dirty');
        }

        form.addEventListener('change', function (event) {
            if (event.target.matches('input[type="radio"], input[type="text"]')) {
                markDirty();
            }
        });

        form.addEventListener('input', function (event) {
            if (event.target.matches('input[type="text"]')) {
                markDirty();
            }
        });

        form.addEventListener('click', function (event) {
            var clearButton = event.target.closest('.sb-clear-status');
            if (!clearButton) return;

            var bulletinId = clearButton.getAttribute('data-bulletin-id');
            if (!bulletinId) return;

            form.querySelectorAll('input[type="radio"][name="rows[' + bulletinId + '][status]"]').forEach(function (radio) {
                radio.checked = false;
            });

            var clearInput = form.querySelector('input[name="clear_status_bulletin_id"]');
            if (clearInput) clearInput.value = bulletinId;

            clearButton.classList.add('is-saving');
            clearButton.disabled = true;
            clearButton.querySelectorAll('.sb-clear-text').forEach(function (text) {
                text.textContent = 'Clearing';
            });

            form.submit();
        });

        form.addEventListener('submit', function () {
            saveButton.classList.remove('is-dirty');
            saveButton.classList.add('is-saving');
            saveButton.disabled = true;
            var text = saveButton.querySelector('.sb-save-text');
            if (text) text.textContent = 'Saving';
        });
    })();
</script>
</body>
</html>
