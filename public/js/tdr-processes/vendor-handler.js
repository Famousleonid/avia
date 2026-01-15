/**
 * VendorHandler - модуль для работы с поставщиками (vendors)
 * Обрабатывает выбор vendor и добавление новых vendors
 */
class VendorHandler {
    /**
     * Инициализирует обработчики для работы с vendors
     * @param {string} storeVendorUrl - URL для создания нового vendor
     */
    static init(storeVendorUrl) {
        this.storeVendorUrl = storeVendorUrl;
        
        // Инициализируем обработчики для селектов vendor
        this.initVendorSelects();
        
        // Инициализируем обработчик добавления vendor
        this.initAddVendorHandler();
    }

    /**
     * Инициализирует обработчики для дропдаунов vendors в таблице
     */
    static initVendorSelects() {
        const vendorSelects = document.querySelectorAll('.vendor-select:not(#groupFormsModal .vendor-select):not(.disabled)');
        vendorSelects.forEach(select => {
            // Пропускаем неактивные селекты
            if (select.disabled || select.classList.contains('disabled')) {
                return;
            }
            
            select.addEventListener('change', function() {
                const tdrProcessId = this.getAttribute('data-tdr-process-id');
                const process = this.getAttribute('data-process');
                const vendorId = this.value;
                const vendorName = this.options[this.selectedIndex].text;

                if (vendorId) {
                    console.log('Selected vendor:', {
                        tdrProcessId: tdrProcessId,
                        process: process,
                        vendorId: vendorId,
                        vendorName: vendorName
                    });
                }
            });
        });
    }

    /**
     * Инициализирует обработчик для добавления нового vendor
     */
    static initAddVendorHandler() {
        const saveVendorButton = document.getElementById('saveVendorButton');
        const addVendorForm = document.getElementById('addVendorForm');
        const vendorNameInput = document.getElementById('vendorName');

        if (!saveVendorButton || !addVendorForm || !vendorNameInput) {
            console.warn('Add vendor form elements not found');
            return;
        }

        saveVendorButton.addEventListener('click', function() {
            const vendorName = vendorNameInput.value.trim();

            if (!vendorName) {
                alert('Please enter vendor name');
                return;
            }

            VendorHandler.createVendor(vendorName, addVendorForm);
        });
    }

    /**
     * Создает нового vendor через AJAX
     * @param {string} vendorName - Название vendor
     * @param {HTMLElement} form - Форма для очистки
     */
    static createVendor(vendorName, form) {
        fetch(this.storeVendorUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                name: vendorName
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Добавляем новый vendor в дропдауны
                const newOption = document.createElement('option');
                newOption.value = data.vendor.id;
                newOption.textContent = data.vendor.name;

                // Добавляем во все дропдауны
                document.querySelectorAll('.vendor-select').forEach(select => {
                    select.appendChild(newOption.cloneNode(true));
                });

                // Закрываем модальное окно
                const modal = bootstrap.Modal.getInstance(document.getElementById('addVendorModal'));
                if (modal) {
                    modal.hide();
                }

                // Очищаем форму
                form.reset();

                if (window.NotificationHandler) {
                    window.NotificationHandler.success('Vendor added successfully!');
                } else {
                    alert('Vendor added successfully!');
                }
            } else {
                const errorMsg = data.message || 'Error adding vendor';
                if (window.NotificationHandler) {
                    window.NotificationHandler.error('Error: ' + errorMsg);
                } else {
                    alert('Error: ' + errorMsg);
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (window.NotificationHandler) {
                window.NotificationHandler.error('Error adding vendor');
            } else {
                alert('Error adding vendor');
            }
        });
    }
}

// Экспорт для использования в других модулях
if (typeof module !== 'undefined' && module.exports) {
    module.exports = VendorHandler;
}




