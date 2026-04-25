{{-- Инициализация ссылок модалки Group Process Forms: только выбор деталей (без process_ids). --}}
<script>
(function() {
    'use strict';

    function apgfmUpdateRow(rowEl) {
        var link = rowEl.querySelector('.group-form-button');
        if (!link || !link.getAttribute('href')) {
            return;
        }
        var url = new URL(link.getAttribute('href'), window.location.origin);
        var vendorSelect = rowEl.querySelector('.vendor-select');
        if (vendorSelect && vendorSelect.value) {
            url.searchParams.set('vendor_id', vendorSelect.value);
        } else {
            url.searchParams.delete('vendor_id');
        }

        url.searchParams.delete('process_ids');
        var checkedBoxes = rowEl.querySelectorAll('.component-checkbox:checked:not([disabled])');
        if (checkedBoxes.length > 0) {
            url.searchParams.set('component_ids', Array.prototype.map.call(checkedBoxes, function(c) {
                return c.getAttribute('data-component-id');
            }).join(','));
            url.searchParams.set('serial_numbers', Array.prototype.map.call(checkedBoxes, function(c) {
                return c.getAttribute('data-serial-number') || '';
            }).join(','));
            url.searchParams.set('ipl_nums', Array.prototype.map.call(checkedBoxes, function(c) {
                return c.getAttribute('data-ipl-num') || '';
            }).join(','));
            url.searchParams.set('part_numbers', Array.prototype.map.call(checkedBoxes, function(c) {
                return c.getAttribute('data-part-number') || '';
            }).join(','));
        } else {
            url.searchParams.delete('component_ids');
            url.searchParams.delete('serial_numbers');
            url.searchParams.delete('ipl_nums');
            url.searchParams.delete('part_numbers');
        }
        link.setAttribute('href', url.toString());

        var badge = rowEl.querySelector('.process-qty-badge');
        if (!badge) {
            return;
        }
        var stdChecked = rowEl.querySelectorAll('.component-checkbox:checked:not([disabled])');
        var posUnit = (badge.getAttribute('data-position-unit') || 'pos.').trim();
        badge.textContent = stdChecked.length + ' ' + posUnit;
    }

    window.initAllPartsGroupFormModalRows = function(container) {
        if (!container) {
            return;
        }
        var rows = container.querySelectorAll('tr.all-parts-group-form-row[data-group-form-row]');
        Array.prototype.forEach.call(rows, function(rowEl) {
            function refresh() {
                apgfmUpdateRow(rowEl);
            }
            rowEl.querySelectorAll('.vendor-select').forEach(function(s) {
                s.addEventListener('change', refresh);
            });
            rowEl.querySelectorAll('.component-checkbox').forEach(function(c) {
                c.addEventListener('change', refresh);
            });
            rowEl.querySelectorAll('.group-form-button').forEach(function(b) {
                b.addEventListener('click', refresh);
            });
            refresh();
        });
    };
})();
</script>
