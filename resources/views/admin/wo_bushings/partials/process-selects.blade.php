<style>
    .bushing-process-line > .select2-container { width: 100% !important; min-width: 0; }
    .bushing-process-line.is-hidden > .select2-container { display: none; }
    .bushing-process-line .select2-selection--single { height: 28px; font-size: .75rem; }
    .bushing-process-line .select2-selection__rendered { line-height: 26px !important; }
    .bushing-process-dropdown { font-size: .75rem; }
    .bushing-process-dropdown .select2-results__option { white-space: normal; overflow-wrap: anywhere; }
    .bushing-process-label { white-space: pre-line; overflow-wrap: anywhere; }
    .bushing-process-comment { color: #ffc107; display: block; white-space: pre-line; }
</style>
<script>
    window.populateBushingProcessOptions = function(select) {
        if (select.dataset.optionsLoaded) return;
        var pool = select.form?.querySelector('template[data-bushing-options="' + select.dataset.processField + '"]');
        if (!pool) return;
        var saved = select.value;
        select.replaceChildren(pool.content.cloneNode(true));
        if (saved && select.dataset.processField === 'ndt') {
            var equivalent = Array.from(select.options).find(o => (o.dataset.ndtAliases || '').split(',').includes(saved));
            if (equivalent) equivalent.value = saved;
        }
        select.value = saved;
        select.dataset.optionsLoaded = '1';
    };
    window.initBushingProcessSelects = function(form) {
        var table = form.querySelector('.bushing-itemized-table');
        if (table && !form.bushingColumnObserver) {
            var fitProcessColumns = function() {
                if (!form.isConnected) { form.bushingColumnObserver.disconnect(); return; }
                if (!table.getBoundingClientRect().width) return;
                var columns = Array.from(table.querySelectorAll('col'));
                var fixedWidth = columns.slice(0, 6).reduce(function(sum, col) { return sum + col.getBoundingClientRect().width; }, 0);
                var processWidth = Math.max(0, (table.clientWidth - 2 - fixedWidth) / 6.7);
                columns.slice(6).forEach(function(col, index) {
                    col.style.width = (processWidth * (index === 6 ? .7 : 1)) + 'px';
                });
            };
            form.bushingColumnObserver = new ResizeObserver(fitProcessColumns);
            form.bushingColumnObserver.observe(table.parentElement);
            window.requestAnimationFrame(fitProcessColumns);
        }
        function processTitle(select) {
            var option = select.selectedOptions[0];
            return select.value && option ? (option.title || option.textContent).trim() : '';
        }
        form.querySelectorAll('.bushing-process-control').forEach(function(select) {
            select.title = processTitle(select);
            if (select.dataset.titleBound) return;
            select.dataset.titleBound = '1';
            ['change', 'mouseover', 'focus'].forEach(function(event) {
                select.addEventListener(event, function() { select.title = processTitle(select); });
            });
        });
        if (!window.jQuery || !window.jQuery.fn.select2) {
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() { window.initBushingProcessSelects(form); }, { once: true });
            }
            return;
        }
        var $ = window.jQuery;
        function renderProcess(data) {
            if (!data.id) return data.text;
            var label = $('<span class="bushing-process-label">').text((data.element?.textContent || data.text).trim());
            var comment = (data.element?.dataset.processComment || '').trim();
            if (comment) label.append($('<span class="bushing-process-comment">').text(comment));
            return label;
        }
        window.enhanceBushingProcessControl = function(select) {
            window.populateBushingProcessOptions(select);
            if ($(select).hasClass('select2-hidden-accessible')) return;
            $(select).select2({
                width: '100%',
                minimumResultsForSearch: 8,
                dropdownParent: $(select.closest('.modal') || document.body),
                templateResult: renderProcess,
                templateSelection: function(data) {
                    data.title = processTitle(select);
                    select.title = data.title;
                    return data.text;
                }
            }).on('select2:open', function() {
                var instance = $(select).data('select2');
                var dropdown = instance.$dropdown.find('.select2-dropdown');
                dropdown.addClass('bushing-process-dropdown');
                window.requestAnimationFrame(function() {
                    if (!instance.isOpen()) return;
                    var canvas = document.createElement('canvas');
                    var context = canvas.getContext('2d');
                    var style = getComputedStyle(dropdown[0]);
                    context.font = style.font;
                    var width = select.getBoundingClientRect().width;
                    Array.from(select.options).forEach(function(option) {
                        var comment = (option.dataset.processComment || '').trim();
                        var lines = (option.textContent.trim() + '\n' + comment).split(/\r?\n/);
                        lines.forEach(function(line) { width = Math.max(width, context.measureText(line).width + 40); });
                    });
                    width = Math.min(Math.ceil(width), document.documentElement.clientWidth - 24);
                    dropdown.css('width', width + 'px');
                    // Select2 anchors to the narrow column; keep wide lists inside the viewport.
                    var wrapper = dropdown.parent();
                    var rect = wrapper[0].getBoundingClientRect();
                    var shift = Math.min(0, document.documentElement.clientWidth - 12 - rect.left - width);
                    shift = Math.max(shift, 12 - rect.left);
                    wrapper.css('left', (parseFloat(wrapper.css('left')) || 0) + shift + 'px');
                });
            });
            $(select).data('select2').$container.on('mouseenter focusin', function() {
                $(this).find('.select2-selection__rendered').attr('title', processTitle(select));
            });
        };
        var selectedPrefixes = new Set(Array.from(form.querySelectorAll('.component-checkbox:checked')).map(el => el.name.replace('[selected]', '')));
        var processPrefixes = new Set(Array.from(form.querySelectorAll('.bushing-need-processes:checked')).map(el => el.name.replace('[need_processes]', '')));
        form.querySelectorAll('.bushing-process-control').forEach(function(select) {
            var prefix = select.name.slice(0, select.name.lastIndexOf('['));
            if (selectedPrefixes.has(prefix) && processPrefixes.has(prefix)) window.enhanceBushingProcessControl(select);
        });
    };
</script>
