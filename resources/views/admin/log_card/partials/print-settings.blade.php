@include('partials.user-scoped-storage')

<div class="text-start m-3 no-print">
    <button class="btn btn-outline-primary" type="button" onclick="window.print()">
        Print Form
    </button>
    <button class="btn btn-outline-secondary ms-2" type="button" data-bs-toggle="modal" data-bs-target="#logCardPrintSettingsModal">
        Print Settings
    </button>
</div>

<div class="modal fade no-print" id="logCardPrintSettingsModal" tabindex="-1" aria-labelledby="logCardPrintSettingsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title" id="logCardPrintSettingsModalLabel">Print Settings</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <label for="logCardTableFontSize" class="form-label">Table Font (px)</label>
                <input type="number" class="form-control" id="logCardTableFontSize" min="8" max="22" step="0.5" value="14">
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="logCardPrintSettingsReset">Reset</button>
                <button type="button" class="btn btn-primary btn-sm" id="logCardPrintSettingsSave">Save</button>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('assets/Bootstrap 5/bootstrap.bundle.min.js') }}"></script>
<script>
    (function () {
        const storageKey = 'log_card_print_settings';
        const defaultSettings = { tableFontSize: 14 };

        function normalizeFontSize(value) {
            const parsed = Number.parseFloat(String(value ?? defaultSettings.tableFontSize));
            if (!Number.isFinite(parsed)) {
                return defaultSettings.tableFontSize;
            }

            return Math.min(22, Math.max(8, parsed));
        }

        function readSettings() {
            const raw = window.UserScopedStorage?.getItem(storageKey);
            if (!raw) {
                return Object.assign({}, defaultSettings);
            }

            try {
                return Object.assign({}, defaultSettings, JSON.parse(raw));
            } catch (error) {
                window.UserScopedStorage?.removeItem(storageKey);
                return Object.assign({}, defaultSettings);
            }
        }

        function applySettings(settings) {
            const tableFontSize = normalizeFontSize(settings.tableFontSize);
            document.documentElement.style.setProperty('--log-card-table-font-size', tableFontSize + 'px');

            const input = document.getElementById('logCardTableFontSize');
            if (input) {
                input.value = String(tableFontSize);
            }
        }

        function closeModal() {
            const modalElement = document.getElementById('logCardPrintSettingsModal');
            if (!modalElement || !window.bootstrap) {
                return;
            }

            const modal = window.bootstrap.Modal.getInstance(modalElement);
            if (modal) {
                modal.hide();
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            applySettings(readSettings());

            document.getElementById('logCardTableFontSize')?.addEventListener('input', function (event) {
                applySettings({ tableFontSize: event.target.value });
            });

            document.getElementById('logCardPrintSettingsSave')?.addEventListener('click', function () {
                const input = document.getElementById('logCardTableFontSize');
                const settings = { tableFontSize: normalizeFontSize(input?.value) };
                window.UserScopedStorage?.setItem(storageKey, JSON.stringify(settings));
                applySettings(settings);
                closeModal();
            });

            document.getElementById('logCardPrintSettingsReset')?.addEventListener('click', function () {
                window.UserScopedStorage?.removeItem(storageKey);
                applySettings(defaultSettings);
            });
        });
    })();
</script>
