<style>
    .bushing-process-line > .select2-container { width: 100% !important; min-width: 0; }
    .bushing-process-line.is-hidden > .select2-container { display: none; }
    .bushing-process-line .select2-selection--single { height: 28px; font-size: .75rem; }
    .bushing-process-line .select2-selection__rendered { line-height: 26px !important; }
    .bushing-process-dropdown { font-size: .75rem; }
    .bushing-process-dropdown .select2-results__option { white-space: normal; overflow-wrap: anywhere; }
    .bushing-process-comment { color: #ffc107; }
</style>
<script>
    window.initBushingProcessSelects = function(form) {
        if (!window.jQuery || !window.jQuery.fn.select2) {
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() { window.initBushingProcessSelects(form); }, { once: true });
            }
            return;
        }
        var $ = window.jQuery;
        function renderProcess(data) {
            if (!data.id) return data.text;
            var label = $('<span>').text(data.text.trim());
            var comment = (data.element?.dataset.processComment || '').trim();
            if (comment) label.append($('<span class="bushing-process-comment">').text(' (' + comment + ')'));
            return label;
        }
        $(form).find('.bushing-process-control').each(function() {
            var select = this;
            if ($(select).hasClass('select2-hidden-accessible')) return;
            $(select).select2({
                width: '100%',
                minimumResultsForSearch: 8,
                dropdownParent: $(form.closest('.modal') || document.body),
                templateResult: renderProcess,
                templateSelection: renderProcess
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
                        var text = option.text.trim() + (comment ? ' (' + comment + ')' : '');
                        width = Math.max(width, context.measureText(text).width + 40);
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
        });
    };
</script>
