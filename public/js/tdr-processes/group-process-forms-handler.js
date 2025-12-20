/**
 * GroupProcessFormsHandler - модуль для обработки групповых форм процессов
 * Управляет обновлением URL и badge с количеством для групповых форм
 */
class GroupProcessFormsHandler {
    /**
     * Инициализирует обработчики для групповых форм
     */
    static init() {
        const groupVendorSelects = document.querySelectorAll('#groupFormsModal .vendor-select');
        const groupFormLinks = document.querySelectorAll('.group-form-link');
        const groupFormButtons = document.querySelectorAll('#groupFormsModal .group-form-button');
        const groupProcessCheckboxes = document.querySelectorAll('#groupFormsModal .process-checkbox');

        // Обработчик изменения выбора vendor для каждого дропдауна
        groupVendorSelects.forEach(vendorSelect => {
            vendorSelect.addEventListener('change', function() {
                const processNameId = this.getAttribute('data-process-name-id');
                GroupProcessFormsHandler.updateGroupLinkUrl(processNameId);
            });
        });

        // Обработчик изменения чекбоксов процессов
        groupProcessCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const processNameId = this.getAttribute('data-process-name-id');
                GroupProcessFormsHandler.updateGroupLinkUrl(processNameId);
                GroupProcessFormsHandler.updateGroupQuantityBadge(processNameId);
            });
        });

        // Обработчик клика по кнопкам форм
        groupFormLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                const processNameId = this.getAttribute('data-process-name-id');
                GroupProcessFormsHandler.updateGroupLinkUrl(processNameId);
            });
        });

        // Обработчик клика по paper-button кнопкам форм
        groupFormButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                const processNameId = this.getAttribute('data-process-name-id');
                if (processNameId) {
                    // Обновляем URL перед переходом
                    GroupProcessFormsHandler.updateGroupLinkUrl(processNameId);
                    // Получаем обновленный URL и устанавливаем его
                    const updatedUrl = this.getAttribute('href');
                    if (updatedUrl) {
                        this.setAttribute('href', updatedUrl);
                    }
                }
            });
        });

        // Инициализация URL и badge при загрузке страницы
        document.querySelectorAll('#groupFormsModal .group-form-link, #groupFormsModal .group-form-button').forEach(link => {
            const processNameId = link.getAttribute('data-process-name-id');
            if (processNameId) {
                GroupProcessFormsHandler.updateGroupLinkUrl(processNameId);
                GroupProcessFormsHandler.updateGroupQuantityBadge(processNameId);
            }
        });
    }

    /**
     * Обновляет URL ссылки на групповую форму с учетом vendor и выбранных процессов
     * @param {string} processNameId - ID типа процесса
     */
    static updateGroupLinkUrl(processNameId) {
        // Пробуем найти ссылку или кнопку
        let link = document.querySelector(`.group-form-link[data-process-name-id="${processNameId}"]`);
        if (!link) {
            link = document.querySelector(`.group-form-button[data-process-name-id="${processNameId}"]`);
        }
        if (!link) return;

        const originalUrl = link.getAttribute('href');
        if (!originalUrl) return;

        const url = new URL(originalUrl, window.location.origin);

        // Добавляем vendor_id если выбран
        const vendorSelect = document.querySelector(
            `#groupFormsModal .vendor-select[data-process-name-id="${processNameId}"]`
        );
        if (vendorSelect && vendorSelect.value) {
            url.searchParams.set('vendor_id', vendorSelect.value);
        } else {
            url.searchParams.delete('vendor_id');
        }

        // Добавляем process_ids из выбранных чекбоксов
        const checkedBoxes = document.querySelectorAll(
            `#groupFormsModal .process-checkbox[data-process-name-id="${processNameId}"]:checked`
        );
        if (checkedBoxes.length > 0) {
            const selectedProcesses = Array.from(checkedBoxes).map(checkbox => checkbox.value);
            url.searchParams.set('process_ids', selectedProcesses.join(','));
        } else {
            url.searchParams.delete('process_ids');
        }

        link.setAttribute('href', url.toString());
    }

    /**
     * Обновляет badge с количеством выбранных процессов
     * @param {string} processNameId - ID типа процесса
     */
    static updateGroupQuantityBadge(processNameId) {
        const checkedBoxes = document.querySelectorAll(
            `#groupFormsModal .process-checkbox[data-process-name-id="${processNameId}"]:checked:not([disabled])`
        );
        const badge = document.querySelector(
            `#groupFormsModal .process-qty-badge[data-process-name-id="${processNameId}"]`
        );

        if (badge && checkedBoxes.length > 0) {
            let totalQty = 0;
            checkedBoxes.forEach(checkbox => {
                const qty = parseInt(checkbox.getAttribute('data-qty')) || 0;
                totalQty += qty;
            });
            badge.textContent = `${totalQty} pcs`;
        }
    }
}

// Экспорт для использования в других модулях
if (typeof module !== 'undefined' && module.exports) {
    module.exports = GroupProcessFormsHandler;
}

